<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\service\ffmpeg\MaterialService as VideoSliceMaterialService;
use app\common\service\MusicDurationService;
use app\common\service\OriginalMaterialSpec;
use app\common\model\sv\SvDevice;
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

            $insertData = [];
            foreach ($items as $key => $item) {
                $file_url = Material::rawFileUrl($item);
                $publishMode = (int)($item['publish_mode'] ?? $params['publish_mode'] ?? Material::PUBLISH_MODE_MAKE_VIDEO);
                if ($file_url === '') {
                    continue;
                }
                $materialType = (int)($item['material_type'] ?? 1);
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
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                if (OriginalMaterialSpec::wantsOriginal($item) || OriginalMaterialSpec::wantsOriginal($params)) {
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
            }

            if (empty($insertData)) {
                self::$returnData = [
                    'count' => 0,
                    'items' => []
                ];
                return true;
            }

            $result = (new Material())->saveAll($insertData);
            self::$returnData = [
                'count' => count($result),
                'items' => $result
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
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
