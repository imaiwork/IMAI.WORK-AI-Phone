<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use app\api\lists\aiPersona\MaterialUseLogLists;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvMediaMaterialGroup;
use app\common\service\ffmpeg\MaterialService as VideoSliceMaterialService;
use app\common\service\MusicDurationService;
use app\common\service\OriginalMaterialSpec;
use app\common\service\ConfigService;
use think\facade\Cache;
use think\facade\Db;

class MaterialLogic extends ApiLogic
{
    public static function add(array $params): bool
    {
        try {
            $params['publish_mode'] = (int)($params['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO);
            $fileUrl = Material::rawFileUrl($params);
            if ($fileUrl === '') {
                self::setError(
                    $params['publish_mode'] === Material::PUBLISH_MODE_DIRECT_SEND
                        ? '成品库直发素材文件地址不能为空'
                        : '文件URL是必填项'
                );
                return false;
            }
            $params['file_url'] = $fileUrl;

            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $params['user_id'] = self::$uid;
            $params['use_status'] = $params['use_status'] ?? 1;
            $params['material_type'] = (int)($params['material_type'] ?? 1);
            if (OriginalMaterialSpec::wantsOriginal($params)) {
                $guard = self::evaluateOriginalIngestGuard($params);
                if ($guard !== true) {
                    self::setError($guard);
                    return false;
                }
                $params['keep_original'] = Material::KEEP_ORIGINAL_YES;
                $params['source_type'] = Material::SOURCE_TYPE_ORIGINAL;
            } else {
                unset($params['keep_original']);
            }
            unset($params['material_kind'], $params['fps'], $params['codec'], $params['format_name']);
            $groupId = self::resolveGroupId(
                (int)$params['persona_id'],
                (int)($params['group_id'] ?? 0),
                (int)$params['publish_mode'],
                (int)$params['material_type'],
                (int)($params['keep_original'] ?? Material::KEEP_ORIGINAL_NO)
            );
            if ($groupId === false) {
                return false;
            }
            $params['group_id'] = $groupId;
            if ($params['material_type'] === Material::MATERIAL_TYPE_MUSIC) {
                $params['thumbnail_url'] = $params['thumbnail_url'] ?? '';
                // 前端不取音频时长，服务端 ffprobe 补全后校验闪剪 300s 上限；
                // 探测失败也拒绝入库，避免超长音乐漏进音乐库、直到合成才报错
                $musicDuration = (int)($params['duration'] ?? 0);
                if ($musicDuration <= 0) {
                    $musicDuration = MusicDurationService::probe((string)($params['file_url'] ?? ''));
                    $params['duration'] = $musicDuration;
                }
                if ($musicDuration <= 0) {
                    self::setError('无法识别音频时长，请稍后重试或更换文件');
                    return false;
                }
                if ($musicDuration > Material::MUSIC_MAX_DURATION) {
                    self::setError('背景音乐时长不能超过' . Material::MUSIC_MAX_DURATION . '秒，请上传较短的音乐');
                    return false;
                }
            }
            $params['create_time'] = time();
            $params['update_time'] = time();
            // 音乐素材不走视频切片
            if ($params['material_type'] !== Material::MATERIAL_TYPE_MUSIC
                && VideoSliceMaterialService::isOriginalMaterialBeingSliced($params)
            ) {
                self::$returnData = ['skipped' => true, 'reason' => 'video_slice'];
                return true;
            }

            $result = Material::create($params);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function addBatch(array $params): bool
    {
        try {
            $items = $params['items'] ?? [];
            if (empty($items)) {
                self::setError('批量数据不能为空');
                return false;
            }
            foreach ($items as $item) {
                $file_url = Material::rawFileUrl($item);
                $publishMode = (int)($item['publish_mode'] ?? $params['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO);
                if ($file_url === '' && $publishMode === Material::PUBLISH_MODE_DIRECT_SEND) {
                    $name = trim((string)($item['material_name'] ?? ''));
                    self::setError('成品库直发素材文件地址不能为空' . ($name !== '' ? '：' . $name : ''));
                    return false;
                }
            }

            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $batchGroupId = (int)($params['group_id'] ?? 0);

            // 整组导入的来源（素材库分组 → 人设素材库）：同一分组重复导入时按
            // (persona_id, source_group_id, source_material_id, 库 + 模式) 判重，已导入过的直接跳过。
            // 「库 + 模式」必须参与判重：剪辑原片库的自动适配 / 原图各是一套独立列表（keep_original），
            // 成品直发库又是另一套（publish_mode）。不带这两维判重的话，在自动适配下整组导入过的分组
            // 切到原图模式再整组导入会被全部当成「已导入」跳过，表现为提示已跳过、列表里一条没有。
            // 单独点选的素材不带来源，保持可以重复导入的原有行为。
            $sourceGroupKeys = [];
            foreach ($items as $item) {
                $sourceGroupId = max(0, (int)($item['source_group_id'] ?? 0));
                $sourceMaterialId = max(0, (int)($item['source_material_id'] ?? 0));
                if ($sourceGroupId > 0 && $sourceMaterialId > 0) {
                    $sourceGroupKeys[$sourceGroupId][$sourceMaterialId] = true;
                }
            }
            $importedGroupMaterials = self::loadImportedGroupMaterials((int)$params['persona_id'], $sourceGroupKeys);
            $skippedCount = 0;

            $insertData = [];
            foreach ($items as $key => $item) {
                $file_url = Material::rawFileUrl($item);
                $publishMode = (int)($item['publish_mode'] ?? $params['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO);
                if ($file_url === '') {
                    continue;
                }
                $materialType = (int)($item['material_type'] ?? 1);
                $sourceGroupId = max(0, (int)($item['source_group_id'] ?? 0));
                $sourceMaterialId = max(0, (int)($item['source_material_id'] ?? 0));
                // 原图判定提前到这里：判重键要带模式，后面入库与门禁复用同一个结果
                $wantsOriginal = OriginalMaterialSpec::wantsOriginal($item) || OriginalMaterialSpec::wantsOriginal($params);
                $keepOriginal = $wantsOriginal ? Material::KEEP_ORIGINAL_YES : Material::KEEP_ORIGINAL_NO;
                if ($sourceGroupId > 0 && $sourceMaterialId > 0
                    && isset($importedGroupMaterials[$sourceGroupId][self::groupImportDedupeKey(
                        $materialType,
                        $publishMode,
                        $keepOriginal,
                        $sourceMaterialId
                    )])
                ) {
                    $skippedCount++;
                    continue;
                }
                $itemData = [
                    'persona_id' => $params['persona_id'],
                    'user_id' => self::$uid,
                    'material_name' => $item['material_name'] ?? '',
                    'material_type' => $materialType,
                    'file_url' => $file_url,
                    'thumbnail_url' => $item['thumbnail_url'] ?? '',
                    'duration' => $item['duration'] ?? 0,
                    'width' => $item['width'] ?? 0,
                    'is_wechat' => $item['is_wechat'] ?? 0,
                    'height' => $item['height'] ?? 0,
                    'use_status' => $item['use_status'] ?? 1,
                    'publish_mode' => $publishMode,
                    'source_group_id' => $sourceGroupId,
                    'source_material_id' => $sourceGroupId > 0 ? $sourceMaterialId : 0,
                    'source_group_name' => $sourceGroupId > 0
                        ? mb_substr(trim((string)($item['source_group_name'] ?? '')), 0, 60)
                        : '',
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                if ($wantsOriginal) {
                    $guard = self::evaluateOriginalIngestGuard(array_merge($item, [
                        'material_type' => $materialType,
                        'file_url' => $file_url,
                        'publish_mode' => $itemData['publish_mode'],
                    ]), $params);
                    if ($guard !== true) {
                        self::setError($guard);
                        return false;
                    }
                    $itemData['keep_original'] = Material::KEEP_ORIGINAL_YES;
                    $itemData['source_type'] = Material::SOURCE_TYPE_ORIGINAL;
                }

                $itemGroupId = self::resolveGroupId(
                    (int)$params['persona_id'],
                    (int)($item['group_id'] ?? $batchGroupId),
                    $publishMode,
                    $materialType,
                    (int)($itemData['keep_original'] ?? Material::KEEP_ORIGINAL_NO)
                );
                if ($itemGroupId === false) {
                    return false;
                }
                $itemData['group_id'] = $itemGroupId;

                if ($materialType === Material::MATERIAL_TYPE_MUSIC) {
                    // 前端不取音频时长，服务端 ffprobe 补全后校验闪剪 300s 上限；
                    // 探测失败也拒绝入库，避免超长音乐漏进音乐库、直到合成才报错
                    $name = trim((string)($item['material_name'] ?? ''));
                    $musicDuration = (int)($item['duration'] ?? 0);
                    if ($musicDuration <= 0) {
                        $musicDuration = MusicDurationService::probe((string)$file_url);
                        $itemData['duration'] = $musicDuration;
                    }
                    if ($musicDuration <= 0) {
                        self::setError('无法识别音频时长，请稍后重试或更换文件' . ($name !== '' ? '：' . $name : ''));
                        return false;
                    }
                    if ($musicDuration > Material::MUSIC_MAX_DURATION) {
                        self::setError(
                            '背景音乐时长不能超过' . Material::MUSIC_MAX_DURATION . '秒'
                            . ($name !== '' ? '：' . $name : '')
                        );
                        return false;
                    }
                    $itemData['thumbnail_url'] = $itemData['thumbnail_url'] ?? '';
                } elseif (VideoSliceMaterialService::isOriginalMaterialBeingSliced($itemData)) {
                    continue;
                }

                $insertData[] = $itemData;
                if ($sourceGroupId > 0 && (int)$itemData['source_material_id'] > 0) {
                    // 同一批里重复提交同一素材也不重复入库
                    $importedGroupMaterials[$sourceGroupId][self::groupImportDedupeKey(
                        (int)$itemData['material_type'],
                        (int)$itemData['publish_mode'],
                        (int)($itemData['keep_original'] ?? Material::KEEP_ORIGINAL_NO),
                        (int)$itemData['source_material_id']
                    )] = true;
                }
            }

            if (empty($insertData)) {
                self::$returnData = [
                    'count' => 0,
                    'skipped' => $skippedCount,
                    'items' => []
                ];
                return true;
            }

            $result = (new Material())->saveAll($insertData);
            self::$returnData = [
                'count' => count($result),
                'skipped' => $skippedCount,
                'items' => $result
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 当前人设下已通过「素材库分组整组导入」进来的来源集合。
     * 返回 [source_group_id => [判重键 => true]]，只查本次提交涉及的分组。
     * Material 是软删模型，已被删掉的素材不在此集合里，再次导入会重新入库。
     */
    private static function loadImportedGroupMaterials(int $personaId, array $sourceGroupKeys): array
    {
        if ($personaId <= 0 || empty($sourceGroupKeys)) {
            return [];
        }
        $rows = Material::where('persona_id', $personaId)
            ->where('user_id', self::$uid)
            ->whereIn('source_group_id', array_keys($sourceGroupKeys))
            ->field('source_group_id,source_material_id,material_type,publish_mode,keep_original')
            ->select();
        $map = [];
        foreach ($rows as $row) {
            $groupId = (int)($row['source_group_id'] ?? 0);
            $materialId = (int)($row['source_material_id'] ?? 0);
            if ($groupId > 0 && $materialId > 0) {
                $map[$groupId][self::groupImportDedupeKey(
                    (int)($row['material_type'] ?? 0),
                    (int)($row['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO),
                    (int)($row['keep_original'] ?? Material::KEEP_ORIGINAL_NO),
                    $materialId
                )] = true;
            }
        }
        return $map;
    }

    /**
     * 整组导入的判重键。口径跟着列表可见范围走，否则会出现「提示已导入、列表里看不见」：
     * - 剪辑原片库分自动适配 / 原图两套列表，各自独立（keep_original）；
     * - 成品直发库与剪辑原片库是两套列表（publish_mode）；
     * - 音乐库两种模式共用一份列表（见 useMaterialsTab::queryMusicList 不传 keep_original），
     *   所以音乐不分库也不分模式：按模式判重只会在第二个模式里插出一条一模一样的音频。
     */
    private static function groupImportDedupeKey(
        int $materialType,
        int $publishMode,
        int $keepOriginal,
        int $sourceMaterialId
    ): string {
        if ($materialType === Material::MATERIAL_TYPE_MUSIC) {
            return 'music:' . $sourceMaterialId;
        }
        return $publishMode . ':' . $keepOriginal . ':' . $sourceMaterialId;
    }

    public static function batchUpdateGroup(array $params): bool
    {
        try {
            $personaId = (int)($params['persona_id'] ?? 0);
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', (array)($params['ids'] ?? [])))));
            if (!$ids) {
                self::setError('请选择要移动的素材');
                return false;
            }
            $keepValues = Material::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->whereIn('id', $ids)
                ->column('keep_original');
            $keepValues = array_values(array_unique(array_map('intval', $keepValues)));
            if (count($keepValues) > 1) {
                self::setError('不同剪辑模式的素材不能放进同一个分组');
                return false;
            }
            $groupId = self::resolveGroupId(
                $personaId,
                (int)($params['group_id'] ?? 0),
                Material::PUBLISH_MODE_MAKE_VIDEO,
                Material::MATERIAL_TYPE_VIDEO,
                $keepValues[0] ?? Material::KEEP_ORIGINAL_NO
            );
            if ($groupId === false) {
                return false;
            }
            $count = Material::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->where('publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
                ->whereIn('material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE])
                ->whereIn('id', $ids)
                ->update([
                    'group_id' => $groupId,
                    'update_time' => time(),
                ]);
            self::$returnData = ['count' => (int)$count];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @return int|false 0=未分组；false=分组不存在
     */
    private static function resolveGroupId(int $personaId, int $groupId, int $publishMode, int $materialType, int $keepOriginal = Material::KEEP_ORIGINAL_NO): int|false
    {
        return MaterialGroupLogic::resolveForMaterial(self::$uid, $personaId, $groupId, $publishMode, $materialType, $keepOriginal);
    }

    public static function update(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }

            unset($params['id']);
            $params['update_time'] = time();
            $material->save($params);
            self::$returnData = $material->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        Db::startTrans();
        try {
            $material = Material::where('id', $id)
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                Db::rollback();
                return false;
            }

            $material->use_status = 0;
            $material->delete_time = time();
            $material->save();
            self::deleteUseLogs([$id]);

            Db::commit();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function batchDelete(array $ids): bool
    {
        Db::startTrans();
        try {
            $ids = self::normalizeIds($ids);
            if (empty($ids)) {
                self::setError('请选择要删除的素材');
                Db::rollback();
                return false;
            }

            $delIds = Material::whereIn('id', $ids)
                ->where('user_id', self::$uid)
                ->column('id');
            if (empty($delIds)) {
                self::setError('素材不存在');
                Db::rollback();
                return false;
            }

            Material::whereIn('id', $delIds)
                ->where('user_id', self::$uid)
                ->update([
                    'use_status' => 0,
                    'delete_time' => time(),
                    'update_time' => time(),
                ]);
            self::deleteUseLogs($delIds);

            Db::commit();
            self::$returnData = ['id' => $delIds];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 一键删除指定人设下切割失败的视频素材（use_status=2, slice_status=4）。
     */
    public static function deleteFailedSlices(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = (int)($params['persona_id'] ?? 0);
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                Db::rollback();
                return false;
            }

            $materials = Material::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->where('material_type', Material::MATERIAL_TYPE_VIDEO)
                ->where('source_type', 'original')
                ->where('use_status', Material::USE_STATUS_DISABLED)
                ->where('slice_status', Material::SLICE_STATUS_FAILED)
                ->column('id');

            $delIds = array_map('intval', $materials);
            if (empty($delIds)) {
                Db::commit();
                self::$returnData = ['deleted_count' => 0, 'ids' => []];
                return true;
            }

            $now = time();
            Material::whereIn('id', $delIds)
                ->where('user_id', self::$uid)
                ->update([
                    'use_status' => Material::USE_STATUS_DELETED,
                    'delete_time' => $now,
                    'update_time' => $now,
                ]);
            self::deleteUseLogs($delIds);

            Db::commit();
            self::$returnData = [
                'deleted_count' => count($delIds),
                'ids' => $delIds,
            ];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 后台未配置时的清理次数阈值默认值（与 adminapi 端 PersonaMaterialSettingLogic 保持同默认值）
     */
    const DEFAULT_CLEAN_THRESHOLD = 3;

    /**
     * 一键清理当前人设下「使用次数达到阈值」的素材：软删除素材 + 删除使用记录。
     * 使用次数与素材列表「已用N次」同口径：使用状态成功的记录，手动热点任务(auto_type=0)不计次。
     */
    public static function cleanOverUsed(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = (int)($params['persona_id'] ?? 0);
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                Db::rollback();
                return false;
            }

            $threshold = (int)ConfigService::get('aiPersona', 'material_clean_threshold', self::DEFAULT_CLEAN_THRESHOLD);
            if ($threshold < 1) {
                $threshold = self::DEFAULT_CLEAN_THRESHOLD;
            }

            $materialIds = Material::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->column('id');
            if (empty($materialIds)) {
                Db::commit();
                self::$returnData = ['deleted_count' => 0, 'ids' => []];
                return true;
            }

            $delIds = MaterialUseLog::alias('log')
                ->leftJoin('shanjian_video_task task', 'task.id = log.task_id')
                ->whereIn('log.material_id', $materialIds)
                ->where('log.use_status', 1)
                ->where(function ($query) {
                    $query->whereNull('task.id')
                        ->whereOr('task.auto_type', '=', MaterialUseLogLists::AUTO_TASK);
                })
                ->group('log.material_id')
                ->having('COUNT(*) >= ' . $threshold)
                ->column('log.material_id');
            $delIds = array_values(array_map('intval', $delIds));
            if (empty($delIds)) {
                Db::commit();
                self::$returnData = ['deleted_count' => 0, 'ids' => []];
                return true;
            }

            // 软删使用记录前先收集素材用过的设备号，供事务提交后清理素材-设备维度的 Redis 缓存
            $deviceCodes = MaterialUseLog::alias('log')
                ->leftJoin('shanjian_video_task task', 'task.id = log.task_id')
                ->whereIn('log.material_id', $delIds)
                ->where('task.device_code', '<>', '')
                ->group('task.device_code')
                ->column('task.device_code');

            $now = time();
            Material::whereIn('id', $delIds)
                ->where('user_id', self::$uid)
                ->update([
                    'use_status' => Material::USE_STATUS_DELETED,
                    'delete_time' => $now,
                    'update_time' => $now,
                ]);
            self::deleteUseLogs($delIds);

            Db::commit();

            // 素材-设备维度的计数/状态缓存与设备解绑人设的清理口径一致；updateStatus 与合成计数分属 redis / material_redis 两个 store，同名 key 各删一次
            foreach ($delIds as $materialId) {
                foreach ($deviceCodes as $deviceCode) {
                    $redisKey = 'material_' . $materialId . '_device_' . $deviceCode;
                    Cache::store('redis')->delete($redisKey);
                    Cache::store('material_redis')->delete($redisKey);
                }
            }

            self::$returnData = [
                'deleted_count' => count($delIds),
                'ids' => $delIds,
            ];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $devices = SvDevice::where('user_id', self::$uid)->where('persona_id',$material->persona_id)->column('device_code,device_name,device_model');
            $devicelist = [];
            foreach ($devices as $key => $value) {
                $devices[$key]['device_code'] = $value['device_code'];
                $rediskey = 'material_' . $material->id . '_device_' . $value['device_code'];
                $device_use_num = Cache::store('redis')->get($rediskey) ?? 0;
                $devicelist[] = [
                    'device_code' => $value['device_code'],
                    'device_name' => $value['device_name'],
                    'device_model' => $value['device_model'],
                    'use_num' => $device_use_num,
                ];
            }

            $materialData = $material->toArray();
            $materialData['source_type_text'] = Material::getSourceTypeText(
                (string)($materialData['source_type'] ?? ''),
                (int)($materialData['keep_original'] ?? 0)
            );
            // 来源分组名以素材库当前名称为准，源分组删掉时保留入库时的快照
            $sourceGroupId = (int)($materialData['source_group_id'] ?? 0);
            if ($sourceGroupId > 0) {
                $sourceGroupName = SvMediaMaterialGroup::where('user_id', self::$uid)
                    ->where('id', $sourceGroupId)
                    ->value('name');
                if ($sourceGroupName !== null && $sourceGroupName !== '') {
                    $materialData['source_group_name'] = (string)$sourceGroupName;
                }
            }

            self::$returnData = [
                'material' => $materialData,
                'devicelist' => $devicelist,
                'devicenum' => count($devicelist),

            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function updateStatus(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $device_code = $params['device_code'] ?? '';
            $device = SvDevice::where('user_id', self::$uid)
                ->where('device_code', $device_code)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $rediskey = 'material_' . $material->id . '_device_' . $device_code;
            $status = $params['status'] ?? 0;
            Cache::store('redis')->set($rediskey, $status);
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 成品库直发不走闪剪合成，跳过原图入库硬规格（59秒/编码/帧率/分辨率）。
     * 以素材条目 publish_mode 为准，缺省再看批量参数；不看人设工作方式。
     */
    public static function shouldSkipOriginalIngestGuard(array $item, array $batchParams = []): bool
    {
        $mode = (int)($item['publish_mode'] ?? $batchParams['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO);
        return $mode === Material::PUBLISH_MODE_DIRECT_SEND;
    }

    /**
     * 原图入库门禁。非原图或成品直发返回 true；剪辑原片库走闪剪硬规格。
     *
     * @return true|string
     */
    public static function evaluateOriginalIngestGuard(array $item, array $batchParams = [])
    {
        if (!OriginalMaterialSpec::wantsOriginal($item) && !OriginalMaterialSpec::wantsOriginal($batchParams)) {
            return true;
        }
        if (self::shouldSkipOriginalIngestGuard($item, $batchParams)) {
            return true;
        }
        return self::assertOriginalMaterialParams($item);
    }

    /**
     * @return true|string
     */
    private static function assertOriginalMaterialParams(array $params)
    {
        $type = (int)($params['material_type'] ?? 0);
        $payload = [
            'url'         => (string)($params['file_url'] ?? $params['url'] ?? ''),
            'name'        => (string)($params['material_name'] ?? $params['name'] ?? ''),
            'duration'    => (float)($params['duration'] ?? 0),
            'width'       => (int)($params['width'] ?? 0),
            'height'      => (int)($params['height'] ?? 0),
            'fps'         => (float)($params['fps'] ?? 0),
            'codec'       => (string)($params['codec'] ?? ''),
            'format_name' => (string)($params['format_name'] ?? ''),
        ];
        return match ($type) {
            Material::MATERIAL_TYPE_VIDEO => OriginalMaterialSpec::assertVideoForIngest($payload),
            Material::MATERIAL_TYPE_IMAGE => OriginalMaterialSpec::assertImage($payload),
            Material::MATERIAL_TYPE_MUSIC => OriginalMaterialSpec::assertMusic($payload),
            default => '原图模式不支持该素材类型',
        };
    }

    private static function normalizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    private static function deleteUseLogs(array $materialIds): void
    {
        $logIds = MaterialUseLog::whereIn('material_id', $materialIds)
            ->where('user_id', self::$uid)
            ->column('id');
        if (!empty($logIds)) {
            MaterialUseLog::destroy($logIds);
        }
    }
}
