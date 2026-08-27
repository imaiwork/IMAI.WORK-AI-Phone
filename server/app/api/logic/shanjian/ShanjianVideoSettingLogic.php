<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\HumanLogic;
use app\api\logic\service\TokenLogService;
use app\common\exception\MaterialNotReadyException;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\MemberService;
use app\common\service\UserDisplaySanitizer;
use think\facade\Db;
use think\facade\Log;

/**
 * 闪剪视频设置逻辑处理
 * Class ShanjianVideoSettingLogic
 * @package app\api\logic\shanjian
 */
class ShanjianVideoSettingLogic extends ApiLogic
{
    /** 无包装引擎：闪剪 */
    public const ENGINE_TYPE_SHANJIAN = 1;
    /** 无包装引擎：蝉镜 */
    public const ENGINE_TYPE_CHANJING = 2;
    /** 蝉镜视频原音占位 voice_id，无真实音色，由 cron 从形象视频克隆 */
    public const CHANJING_ORIGINAL_VOICE_ID = '-1';

    /**
     * 蝉镜视频原音：voice 未传、空串或 -1
     */
    public static function isChanjingOriginalVoiceId($voiceId): bool
    {
        $id = trim((string)$voiceId);
        return $id === '' || $id === self::CHANJING_ORIGINAL_VOICE_ID;
    }

    /**
     * 下发前预检:转码就绪 + 分辨率上限
     * 在所有"下发素材给闪剪"的方法事务开始前调用,任一不通过即写错误并返回 false
     * 设为 public,供项目内其他下发入口复用(传入相同结构的 decodedData + params 即可)
     *
     * @param array $decodedData 已解析的 JSON 字段集合(必须含 material 键,每项形如 ['fileUrl' => '...', 'type' => 'video|image'])
     * @param array $params      原始 params(读取 pic 字段作为封面)
     * @return bool  true 通过,false 已 setError,调用方应直接 return false
     */
    public static function preflightMaterials(array $decodedData, array $params): bool
    {
        // 收集本次要发给闪剪的所有素材 URL(material + pic)
        $submittedUrls = [];
        foreach (($decodedData['material'] ?? []) as $m) {
            if (!empty($m['fileUrl'])) {
                $submittedUrls[] = [
                    'url' => $m['fileUrl'],
                    'type' => ($m['type'] ?? '') === 'video' ? 'video' : 'image',
                ];
            }
        }
        if (!empty($params['pic'])) {
            $picUrl = str_starts_with((string)$params['pic'], 'http')
                ? $params['pic']
                : \app\common\service\FileService::getFileUrl($params['pic']);
            $submittedUrls[] = ['url' => $picUrl, 'type' => 'image'];
        }
        if (empty($submittedUrls)) {
            return true;
        }

        // 门禁 1:转码就绪检查
        // 任一素材在 iw_file 表里 transcode_status=1(待转码) 或 2(转码中) → 不下发
        $relativeUris = [];
        foreach ($submittedUrls as $item) {
            $uri = (string)$item['url'];
            if (str_starts_with($uri, 'http')) {
                $path = parse_url($uri, PHP_URL_PATH) ?: '';
                $uri = ltrim($path, '/');
            } else {
                $uri = ltrim($uri, '/');
            }
            if ($uri !== '') {
                $relativeUris[] = $uri;
            }
        }
        if (!empty($relativeUris)) {
            $check = \app\common\service\MaterialReadinessService::checkFileUrlsForSubmit($relativeUris);
            $blockedUris = $check['pending_uris'] ?? [];
            if (!empty($blockedUris)) {
                // 转码未就绪是"暂时性"状态(等待转码完成),不是失败:
                // - 抛 MaterialNotReadyException,cron 路径会透传清缓存等下一轮(不落库失败 task)
                // - 用户主动触发的 add* 路径会被外层 catch 转成友好提示返回(不落库)
                // 绝不能 return false 让上游当成永久失败而落库"生成失败"记录
                throw new MaterialNotReadyException(
                    '素材正在转码处理中(' . count($blockedUris) . ' 条未就绪)'
                );
            }
            $failedUris = $check['failed_uris'] ?? [];
            if (!empty($failedUris)) {
                Log::channel('shanjiannotice')->write('[素材转码失败预检记录] ' . json_encode([
                        'failed_count' => count($failedUris),
                        'failed_uris' => $failedUris,
                        'submitted_count' => count($relativeUris),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

            }
        }

        // 门禁 2:分辨率兜底预检
        // 针对绕过转码直接上传的图(封面、anchor 头像等)、历史未转码的老视频。
        // 超标时优先自动投递转码,抛 MaterialNotReadyException 让 cron 保持"生成中"等下一轮;
        // 仅当无法投递(无 file 记录/入队失败)时才视为永久失败。
        $violations = \app\common\service\ResolutionValidator::findViolations($submittedUrls);
        if (!empty($violations)) {
            $dispatch = \app\common\service\UploadService::dispatchTranscodeForOversizedUrls($violations);
            $waiting = count($dispatch['dispatched']) + count($dispatch['pending']);
            Log::channel('shanjiannotice')->write('[分辨率超标自动转码] ' . json_encode([
                    'violations' => $violations,
                    'dispatched' => $dispatch['dispatched'],
                    'pending' => $dispatch['pending'],
                    'skipped' => $dispatch['skipped'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

            if ($waiting > 0) {
                throw new MaterialNotReadyException(
                    '素材分辨率超标,已自动投递转码('
                    . count($dispatch['dispatched']) . ' 条新投递,'
                    . count($dispatch['pending']) . ' 条处理中)'
                );
            }

            self::setError(\app\common\service\ResolutionValidator::formatViolationMessage($violations));
            return false;
        }

        return true;
    }

    /**
     * 闪剪素材时长门禁（仅自动化任务使用；手动任务前端已限制总时长，不做过滤）
     * - 图片按 2s
     * - 视频按实际 duration，≤0 过滤；单条 >59s 过滤
     * - 累计总时长 >290s 时过滤当前素材
     * 兼容 material_type(1视频/其它图片) 与 type(video|image)
     */
    public static function trimMaterialsByDuration(array $materials, float $maxTotal = 290, float $maxSingle = 59): array
    {
        $total = 0.0;
        $kept = [];
        foreach ($materials as $material) {
            if (!is_array($material)) {
                continue;
            }

            $type = (string)($material['type'] ?? '');
            $materialType = (int)($material['material_type'] ?? 0);
            $isVideo = $type === 'video' || $materialType === 1;
            $isImage = $type === 'image' || ($materialType > 0 && $materialType !== 1);

            // 无明确类型时：有 duration>0 按视频，否则按图片 2s
            if (!$isVideo && !$isImage) {
                $guessDuration = (float)($material['duration'] ?? 0);
                $isVideo = $guessDuration > 0;
                $isImage = !$isVideo;
            }

            if ($isVideo) {
                $duration = (float)($material['duration'] ?? 0);
                if ($duration <= 0 || $duration > $maxSingle) {
                    continue;
                }
            } else {
                $duration = 2.0;
                $material['duration'] = 2;
                if ($type === '' && $materialType === 0) {
                    $material['type'] = 'image';
                }
            }

            if (($total + $duration) > $maxTotal) {
                continue;
            }

            $total += $duration;
            $kept[] = $material;
        }

        return array_values($kept);
    }

    public static function hasNonEmptyMaterialFileUrl(array $materialData): bool
    {
        $fileUrl = $materialData['fileUrl'] ?? null;
        if ((is_string($fileUrl) || is_numeric($fileUrl)) && trim((string)$fileUrl) !== '') {
            return true;
        }

        foreach ($materialData as $item) {
            if (is_array($item) && self::hasNonEmptyMaterialFileUrl($item)) {
                return true;
            }
        }

        return false;
    }

    private static function calculateTextDuration(array $copywriting): float
    {
        $length = 0;
        foreach ($copywriting as $item) {
            if (!empty($item['content'])) {
                $length += mb_strlen((string)$item['content'], 'UTF-8');
            }
        }
        return $length > 0 ? $length / 3 : 0;
    }

    private static function calculateAudioDefaultDuration(array $audio): float
    {
        return count($audio) * 30;
    }

    private static function calculateAudioActualDuration(array $audio): float
    {
        $duration = 0;
        foreach ($audio as $item) {
            if (is_string($item)) {
                $duration += 30;
            } elseif (is_array($item) && isset($item['duration'])) {
                $duration += max((float)$item['duration'], 0);
            }
        }
        return $duration;
    }

    /**
     * 规范化音频项：兼容字符串 URL / minimax 结构化对象{url,text,words}
     * @param mixed $audioItem
     * @return array{url:string,text:string,words:array}
     */
    public static function normalizeAudioItem($audioItem): array
    {
        $url = '';
        $text = '';
        $words = [];

        if (is_string($audioItem)) {
            $url = trim($audioItem, " `'\"`");
        } elseif (is_array($audioItem)) {
            $rawUrl = $audioItem['url'] ?? ($audioItem['audio_url'] ?? '');
            if (is_array($rawUrl)) {
                $rawUrl = $rawUrl['url'] ?? '';
            }
            $url = is_string($rawUrl) ? trim($rawUrl, " `'\"`") : '';
            $text = (string)($audioItem['text'] ?? $audioItem['aligned_text'] ?? '');
            $words = is_array($audioItem['words'] ?? null) ? $audioItem['words'] : [];
        }

        return [
            'url'   => $url,
            'text'  => $text,
            'words' => $words,
        ];
    }

    private static function calculateVirtualmanDuration(array $decodedData): float
    {
        $extra = $decodedData['extra'] ?? [];
        $videoCount = max((float)($extra['video_count'] ?? 0), 0);
        $audio = $decodedData['audio'] ?? [];
        if (!empty($audio)) {
            return self::calculateAudioDefaultDuration($audio) * $videoCount;
        }
        return self::calculateTextDuration($decodedData['copywriting'] ?? []) * $videoCount;
    }

    private static function calculateRealmanDuration(array $decodedData): float
    {
        $extra = $decodedData['extra'] ?? [];
        $videoCount = max((float)($extra['video_count'] ?? 0), 0);
        $anchors = $decodedData['anchor'] ?? [];
        $totalDuration = 0;
        $anchorCount = 0;
        foreach ($anchors as $anchor) {
            if (!empty($anchor['duration'])) {
                $totalDuration += (float)$anchor['duration'];
                $anchorCount++;
            }
        }
        return $anchorCount > 0 ? ($totalDuration / $anchorCount) * $videoCount : 0;
    }

    private static function calculateMixcutDuration(array $decodedData): float
    {
        $extra = $decodedData['extra'] ?? [];
        $videoCount = max((float)($extra['video_count'] ?? 0), 0);
        $materialCount = !empty($decodedData['material']) && is_array($decodedData['material'])
            ? count($decodedData['material'])
            : 0;
        $audio = $decodedData['audio'] ?? [];
        $baseDuration = !empty($audio)
            ? self::calculateAudioDefaultDuration($audio)
            : self::calculateTextDuration($decodedData['copywriting'] ?? []);
        return $baseDuration * $videoCount * $materialCount;
    }

    private static function calculateNewsMixcutDuration(array $decodedData): float
    {
        $extra = $decodedData['extra'] ?? [];
        $videoCount = max((float)($extra['video_count'] ?? 0), 0);
        $materialCount = !empty($decodedData['material']) && is_array($decodedData['material'])
            ? count($decodedData['material'])
            : 0;
        return self::calculateTextDuration($decodedData['copywriting'] ?? []) * $videoCount * $materialCount;
    }

    private static function formatBillingDuration(float $duration): float
    {
        return round(max($duration, 0), 4);
    }

    private static function taskTextDuration(array $item): float
    {
        return !empty($item['content'])
            ? mb_strlen((string)$item['content'], 'UTF-8') / 3
            : 0;
    }

    private static function normalizeSoundSwitch($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }
        return false;
    }

    /**
     * 添加闪剪视频设置
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            // 验证audio和copywriting不能同时存在
            $audio = $decodedData['audio'] ?? [];
            $copywriting = $decodedData['copywriting'] ?? [];

            if (!empty($audio) && !empty($copywriting)) {
                self::setError("audio参数和copywriting参数不能同时存在，只能使用其中一个");
                return false;
            }

            $duration = self::calculateVirtualmanDuration($decodedData);
            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $video_count = $extra['video_count'] ?? 0;
            if ($video_count <= 0) {
                self::setError("视频数量必须大于0，当前值为：$video_count");
                return false;
            }
            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;

            // 根据使用的参数类型计算视频数量
            if (!empty($audio)) {
                $params['video_count'] = count($audio) *  $video_count;
            } else {
                $params['video_count'] = count($copywriting) * $video_count;
            }

            // 音色校验: 文案驱动时必填; 音频驱动时可空（直接使用上传音频）
            // MiniMax 兼容: model_version=10/11、人设 provider、voice_id 含 minimax 前缀
            $voiceData = $decodedData['voice'] ?? [];
            $hasAudio = !empty($audio);
            $minimaxVoice = null;
            if (!$hasAudio) {
                if (count($voiceData) == 0) {
                    throw new \Exception("音色不能为空");
                }
                foreach ($voiceData as $data) {
                    $vid = trim((string)($data['voice_id'] ?? ''));
                    if ($vid === '') {
                        throw new \Exception("音色还没有生成");
                    }
                    $isMinimax = self::isMinimaxVoiceId($vid, (int)self::$uid)
                        || (isset($data['model_version']) && in_array((int)$data['model_version'], [10, 11], true));
                    if ($isMinimax) {
                        $minimaxVoice = $vid;
                        if (empty($copywriting)) {
                            self::setError('选择MiniMax音色时，必须填写文案');
                            return false;
                        }
                    }
                }
            } else {
                foreach ($voiceData as $data) {
                    $vid = trim((string)($data['voice_id'] ?? ''));
                    if ($vid === '') {
                        continue;
                    }
                    $isMinimax = self::isMinimaxVoiceId($vid, (int)self::$uid)
                        || (isset($data['model_version']) && in_array((int)$data['model_version'], [10, 11], true));
                    if ($isMinimax) {
                        $minimaxVoice = $vid;
                        if (empty($copywriting)) {
                            self::setError('选择MiniMax音色时，必须填写文案');
                            return false;
                        }
                    }
                }
            }

            // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
            // 这里只建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。

            // 开始事务
            Db::startTrans();
            try {
                $unit = TokenLogService::checkToken(self::$uid, 'human_video_shanjian', $duration);
                $params['request_json'] = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $setting = ShanjianVideoSetting::create($params);

                if (isset($minimaxVoice)) {
                    //创建音频合成定时任务
                    $minimaxTask = self::createAudioTask($setting->id, $minimaxVoice, $copywriting);
                    // 同步建占位任务(status=-1)让创作记录立即可见，TTS/ASR 完成后由 VoiceLogic 回填激活
                    self::createVideoTasks($setting->id, $params, $decodedData, (int)$minimaxTask->id);
                } else {
                    // 如果状态为1，创建对应的视频任务
                    self::createVideoTasks($setting->id, $params, $decodedData);
                }


                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新闪剪视频设置
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $setting = ShanjianVideoSetting::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'title', 'character_design', 'material', 'clip', 'music', 'extra'];

            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            $params['update_time'] = time();

            // 开始事务
            Db::startTrans();
            try {
                $setting->save($params);

                // 如果状态变为1，重新创建视频任务
                if (isset($params['status']) && $params['status'] == 1) {
                    // 删除旧的视频任务
                    ShanjianVideoTask::where('video_setting_id', $params['id'])->delete();
                    // 创建新的视频任务
                    self::createVideoTasks($params['id'], $params, $decodedData);
                } elseif (isset($params['status']) && $params['status'] == 0) {
                    // 如果状态变为0，删除所有关联的视频任务
                    ShanjianVideoTask::where('video_setting_id', $params['id'])->delete();
                }

                Db::commit();
                self::$returnData = $setting->refresh()->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateName(array $params): bool
    {
        try {
            $find = ShanjianVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($find->isEmpty()) {
                self::setError('视频设置不存在');
                return false;
            }
            $find->name = $params['name'];
            $find->update_time = time();
            $find->save();
            self::$returnData = $find->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 获取闪剪视频设置详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $setting = ShanjianVideoSetting::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            $settingData = $setting->toArray();

            // 处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($settingData[$field])) {
                    $settingData[$field] = json_decode($settingData[$field], true);
                } else {
                    $settingData[$field] = [];
                }
            }

            self::$returnData = $settingData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除闪剪视频设置
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {
            // 仅允许删除当前用户自己的设置,避免越权按 id 删除他人设置及其全部任务
            $ids = array_values(array_filter(array_map('intval', is_array($id) ? $id : [$id])));
            if (!$ids) {
                throw new \Exception('参数错误');
            }
            $ownedIds = ShanjianVideoSetting::whereIn('id', $ids)
                ->where('user_id', (int)self::$uid)
                ->column('id');
            if (count($ownedIds) !== count(array_unique($ids))) {
                throw new \Exception('视频设置不存在或无权限删除');
            }
            ShanjianVideoSetting::whereIn('id', $ownedIds)->select()->delete();
            // 派生包装任务挂在自己的 setting 下，按 setting 删除时须显式级联，避免孤儿
            $type5Ids = ShanjianVideoTask::whereIn('video_setting_id', $ownedIds)
                ->where('shanjian_type', 5)
                ->column('id');
            ShanjianVideoTask::deleteDerivedPackaging($type5Ids);
            // 删除关联的视频任务
            ShanjianVideoTask::whereIn('video_setting_id', $ownedIds)->select()->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 创建视频任务
     * @param int $settingId
     * @param array $params
     * @return void
     */
    public static function createVideoTasks(int $settingId, array $params, $decodedData, int $minimaxTaskId = 0): void
    {
        $taskData = [];
        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $defaultPic = (string)($params['pic'] ?? '');
        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_id', $data) || trim($data['anchor_id']) === '') {
                throw new \Exception("形象不存在");
            }
        }

        // 音频驱动时可无音色; 文案驱动时音色必填
        if (count($voiceData) == 0 && empty($audioData)) {
            throw new \Exception("音色不能为空");
        }
        if (empty($audioData)) {
            foreach ($voiceData as $data) {
                if (!array_key_exists('voice_id', $data) || trim($data['voice_id']) === '') {
                    throw new \Exception("音色还没有生成");
                }
            }
        }

        // 验证文案或音频数据
        if (empty($audioData) && empty($copywritingData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证copywriting数据
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        // 解析控制参数：1=顺序，0=随机
        $controlParams = $extraData ?? [];
        $humanMode = $controlParams['human'] ?? 1; // human控制anchorData
        $musicMode = $controlParams['music'] ?? 1; // music控制music_url
        $clipMode = $controlParams['clip'] ?? 1; // clip控制clip_id
        $materialMode = $controlParams['material'] ?? 1; // material控制material选择
        $aimusicMode = $controlParams['ai_music'] ?? false;

        $copywritingDatanum = count($copywritingData) * 0.5;
        $materialDatanum = count($materialData);
        $randcopywriting = false;
        if ($materialDatanum > $copywritingDatanum && $materialDatanum > 4) {
            $randcopywriting = true;
        }

        // 确定每个文案/音频要生成的视频数量
        $videoCountPerItem = $extraData['video_count'] ?? 5; // 每个文案/音频生成的视频数量

        // 确定主要数据源（文案或音频）
        $primaryDataSource = !empty($audioData) ? $audioData : $copywritingData;
        $primaryDataCount = count($primaryDataSource);

        $globalTaskIndex = 0; // 全局任务索引

        // 遍历每个文案/音频，为每个生成指定数量的视频
        for ($dataIndex = 0; $dataIndex < $primaryDataCount; $dataIndex++) {
            for ($videoIndex = 0; $videoIndex < $videoCountPerItem; $videoIndex++) {

                // 获取当前数据项
                $currentDataItem = $primaryDataSource[$dataIndex];

                // 人设选择：1=顺序，0=随机
                $selectedAnchor = null;
                $selectedVoice = null;

                if ($humanMode == 1) {
                    // 顺序选择：形象按顺序循环使用
                    $anchorIndex = $globalTaskIndex % count($anchorData);
                    $selectedAnchor = $anchorData[$anchorIndex];
                    $selectedVoice = count($voiceData) > 0
                        ? $voiceData[$globalTaskIndex % count($voiceData)]
                        : ['voice_id' => ''];
                } else {
                    // 随机选择：文案和形象随机匹配
                    $randomAnchorIndex = random_int(0, count($anchorData) - 1);
                    $selectedAnchor = $anchorData[$randomAnchorIndex];
                    $selectedVoice = count($voiceData) > 0
                        ? $voiceData[random_int(0, count($voiceData) - 1)]
                        : ['voice_id' => ''];
                }

                $number = random_int(1, 20);
                $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                // 音乐选择：1=顺序，0=随机
                if (count($musicData) == 0) {
                    if ($aimusicMode) {
                        $music_url = $music;
                    } else {
                        $music_url = '';
                    }
                } else {
                    if ($musicMode == 1) {
                        // 顺序选择
                        $music_url = $musicData[$globalTaskIndex % count($musicData)] ?? $music;
                    } else {
                        // 随机选择
                        $randomIndex = random_int(0, count($musicData) - 1);
                        $music_url = $musicData[$randomIndex] ?? $music;
                    }
                }

                // 剪辑模板选择：1=顺序，0=随机
                if (count($clipData) == 0) {
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'virtualman')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    if ($clipMode == 1) {
                        // 顺序选择
                        $clip = $globalTaskIndex % ($clip_template_total + 1);
                        $clip_id = $clip_template_id[$clip];
                    } else {
                        // 随机选择
                        $clip = random_int(0, $clip_template_total);
                        $clip_id = $clip_template_id[$clip];
                    }
                } else {
                    if ($clipMode == 1) {
                        // 顺序选择
                        $clip_id = $clipData[$globalTaskIndex % count($clipData)]['clip_template_id'] ?? '';
                    } else {
                        // 随机选择
                        $randomIndex = random_int(0, count($clipData) - 1);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? '';
                    }
                }

                // 素材选择：1=顺序，0=随机；手动任务前端已限总时长，此处不过滤
                if ($materialMode == 1) {
                    // 顺序选择：使用全部素材
                    $selectedMaterial = $materialData;
                } else {
                    // 随机选择：根据randcopywriting逻辑随机选择素材
                    if ($randcopywriting) {
                        $numberOfItems = rand(3, 4);
                        $randomKeys = array_rand($materialData, $numberOfItems);
                        if (is_array($randomKeys)) {
                            // 如果抽取多个元素
                            $selectedMaterial = array_intersect_key($materialData, array_flip($randomKeys));
                        } else {
                            // 如果抽取一个元素
                            $selectedMaterial = [$materialData[$randomKeys]];
                        }
                        $selectedMaterial = array_values($selectedMaterial);
                    } else {
                        // 简单随机选择一个素材
                        $randomIndex = random_int(0, count($materialData) - 1);
                        $selectedMaterial = [$materialData[$randomIndex]];
                    }
                }
                $material = json_encode(array_values($selectedMaterial), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // 确定任务内容（文案或音频）
                $taskContent = [];
                $audioType = 1; // 默认文案驱动

                if (!empty($audioData)) {
                    // 音频驱动模式
                    $audioItem = $audioData[$dataIndex % count($audioData)] ?? null;
                    if ($audioItem) {
                        $normalizedAudio = self::normalizeAudioItem($audioItem);
                        $taskContent['audio_url'] = $normalizedAudio['url'];
                        if ($normalizedAudio['text'] !== '') {
                            $taskContent['content'] = $normalizedAudio['text'];
                        }
                        if (!empty($normalizedAudio['words'])) {
                            $taskContent['words'] = $normalizedAudio['words'];
                        }
                        $audioType = 2; // 音频驱动
                    }
                }

                if (!empty($copywritingData)) {
                    // 文案数据
                    $copywritingItem = $copywritingData[$dataIndex % count($copywritingData)] ?? null;
                    if ($copywritingItem) {
                        $taskContent['title'] = $copywritingItem['title'] ??  '数字人口播混剪' . date('YmdHis');
                        if (empty($taskContent['content'])) {
                            $taskContent['content'] = $copywritingItem['content'] ?? '';
                        }
                        if ($audioType != 2) {
                            $taskContent['audio_url'] = ''; // 文案模式下不设置audio_url
                        }
                    }
                }
                $taskDuration = !empty($audioData) ? 30 : self::taskTextDuration($copywritingItem ?? []);

                $extra = [
                    'setting_index' => $globalTaskIndex,
                    'create_type' => 'batch',
                    'data_source_index' => $dataIndex,
                    'video_index_in_source' => $videoIndex,
                    'billing_duration' => self::formatBillingDuration($taskDuration)
                ];
                if (!empty($taskContent['words'])) {
                    // minimax ASR 对齐后的逐字字幕，合成时作为 subtitle 提交
                    $extra['timed_words'] = $taskContent['words'];
                }
                $mergedArray = array_merge($extra, $extraData);
                //                $taskname = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($globalTaskIndex + 1);
                $taskContent['title'] = $taskContent['title'] ?? '';
                $taskname = !empty($taskContent['title']) ? $taskContent['title'] : "数字人口播混剪" . date('YmdHis');
                $taskname = mb_substr($taskname, 0, 120, 'UTF-8');
                // 未指定全局封面时，按当前形象独立取封面
                $taskPic = $defaultPic !== '' ? $defaultPic : (string)($selectedAnchor['pic'] ?? '');
                $taskItem = [
                    'name' => $taskname,
                    'pic' => $taskPic,
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => $audioType,
                    'user_id' => self::$uid !== 0 ? self::$uid : $params['user_id'],
                    'video_setting_id' => $settingId,
                    'anchor_id' => $selectedAnchor['anchor_id'] ?? '',
                    'voice_id' => $selectedVoice['voice_id'] ?? '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $taskContent['title'] ?? '',
                    'msg' => $taskContent['content'] ?? '',
                    'audio_url' => $taskContent['audio_url'] ?? '',
                    'material' => $material,
                    'music_url' => $music_url,
                    'thumb_status' => $taskPic !== '' ? 2 : 4,
                    'clip_id' => $clip_id,
                    'duration' => (int)ceil($taskDuration),
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => time(),
                    'update_time' => time()
                ];

                if ($minimaxTaskId > 0) {
                    // MiniMax 音色需先 TTS：建占位(status=-1)，TTS/ASR 完成后回填 audio_url 并激活
                    $taskItem['status'] = -1;
                    $taskItem['minimax_task_id'] = $minimaxTaskId;
                    $mergedArray['create_type'] = 'minimax_type1_pending';
                    $taskItem['extra'] = json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $taskData[] = $taskItem;
                $globalTaskIndex++;
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 文案内容长度上限(统一放开到1800字)
     */
    const COPYWRITING_MAX_LENGTH = 1800;

    /**
     * 会员软冻结状态值（与 MemberService 一致，音色/数字人均为 9）
     */
    public static function memberFrozenStatus(): int
    {
        return (int)(MemberService::ENTITIES['voice']['frozen_value'] ?? 9);
    }

    /** 音色因会员到期被冻结时的提示 */
    public static function frozenVoiceTip(): string
    {
        return '该音色因会员权益到期被冻结，恢复会员后可继续使用';
    }

    /** 形象因会员到期被冻结时的提示 */
    public static function frozenAnchorTip(): string
    {
        return '该形象因会员权益到期被冻结，恢复会员后可继续使用';
    }

    /**
     * 音色是否被会员软冻结（human_voice.status = frozen）
     */
    public static function isVoiceMemberFrozen(string $voiceId, ?int $userId = null): bool
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return false;
        }
        $query = HumanVoice::where('voice_id', $voiceId)
            ->where('status', self::memberFrozenStatus())
            ->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        return $query->count() > 0;
    }

    /**
     * 闪剪形象是否被会员软冻结（shanjian_anchor.status = frozen）
     */
    public static function isShanjianAnchorMemberFrozen(string $anchorId, ?int $userId = null): bool
    {
        $anchorId = trim($anchorId);
        if ($anchorId === '') {
            return false;
        }
        $query = ShanjianAnchor::where('anchor_id', $anchorId)
            ->where('status', self::memberFrozenStatus());
        if ($userId !== null && $userId > 0) {
            $query->where('user_id', $userId);
        }
        return $query->count() > 0;
    }

    /**
     * 视频原音对应的闪剪形象是否被冻结（按 voice_id 反查）
     */
    public static function isShanjianVoiceAnchorMemberFrozen(string $voiceId, ?int $userId = null): bool
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return false;
        }
        $query = ShanjianAnchor::where('voice_id', $voiceId)
            ->where('status', self::memberFrozenStatus());
        if ($userId !== null && $userId > 0) {
            $query->where('user_id', $userId);
        }
        return $query->count() > 0;
    }

    /**
     * 蝉镜形象是否被会员软冻结（human_anchor.status = frozen）
     */
    public static function isChanjingAnchorMemberFrozen(string $anchorId, ?int $userId = null): bool
    {
        $anchorId = trim($anchorId);
        if ($anchorId === '') {
            return false;
        }
        $query = HumanAnchor::where('anchor_id', $anchorId)
            ->where('status', self::memberFrozenStatus())
            ->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $query->where('user_id', $userId);
        }
        return $query->count() > 0;
    }

    /**
     * 校验闪剪音色库可用音色
     * @param string $voiceId
     * @param int|null $userId 传入时按用户隔离；视频原音需匹配该用户 status=6 的形象
     * @return bool
     */
    private static function isShanjianVoiceAvailable(string $voiceId, ?int $userId = null): bool
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return false;
        }
        // 闪剪音色库: HumanVoice model_version=8, 已生成(status=1)
        $voiceQuery = HumanVoice::where('model_version', 8)
            ->where('status', 1)
            ->where('voice_id', $voiceId);
        if ($userId !== null && $userId > 0) {
            $voiceQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        if ($voiceQuery->count() > 0) {
            return true;
        }
        // 视频原音: 该用户闪剪形象 status=6 且 voice_id 匹配
        $anchorQuery = ShanjianAnchor::where('status', 6)
            ->where('voice_id', $voiceId);
        if ($userId !== null && $userId > 0) {
            $anchorQuery->where('user_id', $userId);
        }
        if ($anchorQuery->count() > 0) {
            return true;
        }
        return false;
    }

    /**
     * 音色不可用时的友好提示（优先识别会员冻结，再区分生成中/失败/类型不支持）
     * 需同时判断 HumanVoice 与视频原音（iw_shanjian_anchor 该用户 status=6）
     */
    private static function voiceUnavailableMessage(string $voiceId, ?int $userId = null): string
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return '音色还没有生成';
        }

        // 会员软冻结：音色库 或 视频原音绑定的形象
        if (self::isVoiceMemberFrozen($voiceId, $userId)
            || self::isShanjianVoiceAnchorMemberFrozen($voiceId, $userId)) {
            return self::frozenVoiceTip();
        }

        // 视频原音可用：该用户闪剪形象 status=6 的 voice_id（与 isShanjianVoiceAvailable 对齐，避免误报）
        $anchorOkQuery = ShanjianAnchor::where('voice_id', $voiceId)->where('status', 6);
        if ($userId !== null && $userId > 0) {
            $anchorOkQuery->where('user_id', $userId);
        }
        if ($anchorOkQuery->count() > 0) {
            return '';
        }

        // 视频原音未就绪：按形象状态给出提示
        $anchorQuery = ShanjianAnchor::where('voice_id', $voiceId);
        if ($userId !== null && $userId > 0) {
            $anchorQuery->where('user_id', $userId);
        }
        $anchor = $anchorQuery->order('id', 'desc')->findOrEmpty();
        if (!$anchor->isEmpty()) {
            $anchorStatus = (int)($anchor->status ?? -1);
            if ($anchorStatus === self::memberFrozenStatus()) {
                return self::frozenVoiceTip();
            }
            if (in_array($anchorStatus, [1, 3, 4], true)) {
                return '音色正在生成中，请稍后再试';
            }
            if (in_array($anchorStatus, [2, 5], true)) {
                return '音色生成失败，请重新克隆后再试';
            }
        }

        $query = HumanVoice::where('voice_id', $voiceId)->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        $voice = $query->order('id', 'desc')->findOrEmpty();
        if (!$voice->isEmpty()) {
            $status = (int)($voice->status ?? -1);
            if ($status === self::memberFrozenStatus()) {
                return self::frozenVoiceTip();
            }
            if ($status === 0) {
                return '音色正在生成中，请稍后再试';
            }
            if ($status === 2) {
                return '音色生成失败，请重新克隆后再试';
            }
            $modelVersion = (int)($voice->model_version ?? 0);
            if (in_array($modelVersion, [10, 11], true)) {
                // 理论上应走 MiniMax 分支；这里兜底提示
                return 'MiniMax音色暂不可用，请重新选择';
            }
            if ($modelVersion === 8 && $status !== 1) {
                $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(8) ?: '数字人';
                return $modelName . '音色暂不可用，请重新选择已生成的音色';
            }
        }

        $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(8) ?: '数字人';
        return '音色不可用，请选择已生成的' . $modelName . '音色、视频原音或MiniMax音色';
    }

    /**
     * 新增数字人口播视频(无包装, shanjian_type=5)独立创建接口
     * engine_type=1 闪剪；engine_type=2 蝉镜(HumanVideoTask model=7)
     * 蝉镜视频原音: voice 传空数组或 voice_id=-1，由 cron 从形象视频克隆原音
     * AI智剪关闭: type=5 即为最终视频(is_final=1)
     * AI智剪开启: type=5 为中间产物(is_final=0), 成功后由回调自动派生 type=2 真人口播混剪包装
     * @param array $params
     * @return bool
     */
    public static function addType5(array $params): bool
    {
        try {
            // 提取并剥离非视频设置表字段, 避免写入 setting 报未知列
            $aiClipEnabled = !empty($params['ai_clip_enabled'])
                && filter_var($params['ai_clip_enabled'], FILTER_VALIDATE_BOOLEAN);
            $packaging = [];
            if (!empty($params['packaging'])) {
                if (is_array($params['packaging'])) {
                    $packaging = $params['packaging'];
                } else {
                    $decodedPackaging = json_decode($params['packaging'], true);
                    $packaging = json_last_error() === JSON_ERROR_NONE ? ($decodedPackaging ?: []) : [];
                }
            }
            $engineType = (int)($params['engine_type'] ?? self::ENGINE_TYPE_SHANJIAN);
            if (!in_array($engineType, [self::ENGINE_TYPE_SHANJIAN, self::ENGINE_TYPE_CHANJING], true)) {
                $v2Name = UserDisplaySanitizer::digitalHumanModelNameByVersion(8) ?: '数字人';
                $v1Name = UserDisplaySanitizer::digitalHumanModelNameByVersion(7) ?: '数字人';
                self::setError('引擎类型不正确，1=' . $v2Name . ' 2=' . $v1Name);
                return false;
            }
            unset($params['ai_clip_enabled'], $params['packaging'], $params['engine_type']);

            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            // 空串也要落默认名（前端清空输入框会传 ""，?? 不会兜底）
            $params['name'] = trim((string)($params['name'] ?? ''));
            if ($params['name'] === '') {
                $params['name'] = '数字人口播' . date('YmdHi');
            }
            $params['shanjian_type'] = 5;

            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra', 'audio'];
            $decodedData = [];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            // 验证audio和copywriting不能同时存在
            $audio = $decodedData['audio'] ?? [];
            $copywriting = $decodedData['copywriting'] ?? [];
            if (!empty($audio) && !empty($copywriting)) {
                self::setError("audio参数和copywriting参数不能同时存在，只能使用其中一个");
                return false;
            }

            // 蝉镜模型：开启包装限制1750字，关闭包装限制3900字；其他类型统一1800字
            $copywritingMaxLen = self::COPYWRITING_MAX_LENGTH;
            if ($engineType === self::ENGINE_TYPE_CHANJING) {
                $copywritingMaxLen = $aiClipEnabled ? 1750 : 3900;
            }
            foreach ($copywriting as $data) {
                $content = (string)($data['content'] ?? '');
                if (mb_strlen($content, 'UTF-8') > $copywritingMaxLen) {
                    self::setError('文案长度不能超过' . $copywritingMaxLen . '个字符');
                    return false;
                }
            }

            $extra = $decodedData['extra'] ?? [];
            // extra.volume = 口播人声音量(0~1)，仅第一步无包装生效
            $volume = isset($extra['volume']) ? (float)$extra['volume'] : 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("口播音量必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $video_count = (int)($extra['video_count'] ?? 0);
            if ($video_count <= 0) {
                self::setError("视频数量必须大于0，当前值为：$video_count");
                return false;
            }

            $anchor = $decodedData['anchor'] ?? [];
            if (count($anchor) == 0) {
                self::setError("形象不能为空");
                return false;
            }
            foreach ($anchor as $data) {
                if (!array_key_exists('anchor_id', $data) || trim((string)$data['anchor_id']) === '') {
                    self::setError("形象不存在");
                    return false;
                }
            }

            if ($engineType === self::ENGINE_TYPE_CHANJING) {
                return self::addType5Chanjing($params, $decodedData, $aiClipEnabled, $packaging, $volume, $video_count);
            }

            return self::addType5Shanjian($params, $decodedData, $aiClipEnabled, $packaging, $volume, $video_count);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * type5 闪剪引擎创建
     */
    private static function addType5Shanjian(
        array $params,
        array $decodedData,
        bool $aiClipEnabled,
        array $packaging,
        float $volume,
        int $videoCount
    ): bool {
        $audio = $decodedData['audio'] ?? [];
        $copywriting = $decodedData['copywriting'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $anchorData = $decodedData['anchor'] ?? [];
        $hasAudio = !empty($audio);
        $minimaxVoice = null;

        foreach ($anchorData as $data) {
            $aid = trim((string)($data['anchor_id'] ?? ''));
            if ($aid !== '' && self::isShanjianAnchorMemberFrozen($aid, (int)self::$uid)) {
                self::setError(self::frozenAnchorTip());
                return false;
            }
        }

        if (!$hasAudio) {
            if (count($voiceData) == 0) {
                self::setError("音色不能为空");
                return false;
            }
            foreach ($voiceData as $data) {
                $vid = trim((string)($data['voice_id'] ?? ''));
                if ($vid === '') {
                    self::setError("音色还没有生成");
                    return false;
                }
                if (self::isVoiceMemberFrozen($vid, (int)self::$uid)
                    || self::isShanjianVoiceAnchorMemberFrozen($vid, (int)self::$uid)) {
                    self::setError(self::frozenVoiceTip());
                    return false;
                }
                $isMinimax = self::isMinimaxVoiceId($vid, (int)self::$uid)
                    || (isset($data['model_version']) && in_array((int)$data['model_version'], [10, 11], true));
                if ($isMinimax) {
                    $minimaxVoice = $vid;
                    if (empty($copywriting)) {
                        self::setError('选择MiniMax音色时，必须填写文案');
                        return false;
                    }
                    continue;
                }
                if (!self::isShanjianVoiceAvailable($vid, (int)self::$uid)) {
                    $msg = self::voiceUnavailableMessage($vid, (int)self::$uid);
                    if ($msg !== '') {
                        self::setError($msg);
                        return false;
                    }
                }
            }
        } else {
            foreach ($voiceData as $data) {
                $vid = trim((string)($data['voice_id'] ?? ''));
                if ($vid === '') {
                    continue;
                }
                if (self::isVoiceMemberFrozen($vid, (int)self::$uid)
                    || self::isShanjianVoiceAnchorMemberFrozen($vid, (int)self::$uid)) {
                    self::setError(self::frozenVoiceTip());
                    return false;
                }
                if (self::isMinimaxVoiceId($vid, (int)self::$uid)
                    || (isset($data['model_version']) && in_array((int)$data['model_version'], [10, 11], true))) {
                    continue;
                }
                if (!self::isShanjianVoiceAvailable($vid, (int)self::$uid)) {
                    $msg = self::voiceUnavailableMessage($vid, (int)self::$uid);
                    if ($msg !== '') {
                        self::setError($msg);
                        return false;
                    }
                }
            }
        }

        $duration = self::calculateVirtualmanDuration($decodedData);
        $params['status'] = 1;
        $params['video_count'] = !empty($audio)
            ? count($audio) * $videoCount
            : count($copywriting) * $videoCount;

        $requestSnapshot = $params;
        $requestSnapshot['engine_type'] = self::ENGINE_TYPE_SHANJIAN;
        $requestSnapshot['ai_clip_enabled'] = $aiClipEnabled ? 1 : 0;
        $requestSnapshot['packaging'] = $packaging;
        $requestSnapshot['speech_volume'] = $volume;
        $params['request_json'] = json_encode($requestSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 写入引擎标记到 extra，供合成/包装读取
        $extraArr = $decodedData['extra'] ?? [];
        $extraArr['engine_type'] = self::ENGINE_TYPE_SHANJIAN;
        $extraArr['volume'] = $volume;
        $decodedData['extra'] = $extraArr;
        $params['extra'] = json_encode($extraArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Db::startTrans();
        try {
            TokenLogService::checkToken(self::$uid, 'human_video_shanjian', $duration);
            $setting = ShanjianVideoSetting::create($params);
            if ($minimaxVoice !== null) {
                $minimaxTask = self::createAudioTask($setting->id, $minimaxVoice, $copywriting);
                self::createVideoTasksType5(
                    (int)$setting->id,
                    $params,
                    $decodedData,
                    $aiClipEnabled,
                    $packaging,
                    $minimaxVoice,
                    (int)$minimaxTask->id
                );
            } else {
                self::createVideoTasksType5($setting->id, $params, $decodedData, $aiClipEnabled, $packaging);
            }
            Db::commit();
            self::$returnData = $setting->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * type5 蝉镜引擎：建 setting + type5 桥接任务 + HumanVideoTask(model=7)
     */
    private static function addType5Chanjing(
        array $params,
        array $decodedData,
        bool $aiClipEnabled,
        array $packaging,
        float $volume,
        int $videoCount
    ): bool {
        $audio = $decodedData['audio'] ?? [];
        $copywriting = $decodedData['copywriting'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $anchorData = $decodedData['anchor'] ?? [];
        $extra = $decodedData['extra'] ?? [];
        $hasAudio = !empty($audio);
        $minimaxVoice = null;

        $width = trim((string)($extra['width'] ?? $params['width'] ?? ''));
        $height = trim((string)($extra['height'] ?? $params['height'] ?? ''));
        if ($width === '' || $height === '' || (float)$width <= 0 || (float)$height <= 0) {
            $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(7) ?: '数字人';
            self::setError($modelName . '模型宽高不能为空');
            return false;
        }

        foreach ($anchorData as $data) {
            $aid = trim((string)($data['anchor_id'] ?? ''));
            if ($aid !== '' && self::isChanjingAnchorMemberFrozen($aid, (int)self::$uid)) {
                self::setError(self::frozenAnchorTip());
                return false;
            }
            if ($aid === '' || !self::isChanjingAnchorAvailable($aid)) {
                $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(7) ?: '数字人';
                self::setError($modelName . '形象不存在或不可用');
                return false;
            }
        }

        if (!$hasAudio) {
            // 视频原音不传音色，跳过校验，由 cron 从形象视频克隆
            foreach ($voiceData as $data) {
                $vid = trim((string)($data['voice_id'] ?? ''));
                if (self::isChanjingOriginalVoiceId($vid)) {
                    continue;
                }
                if (self::isVoiceMemberFrozen($vid, (int)self::$uid)) {
                    self::setError(self::frozenVoiceTip());
                    return false;
                }
                $isMinimax = self::isMinimaxVoiceId($vid, (int)self::$uid)
                    || (isset($data['model_version']) && in_array((int)$data['model_version'], [10, 11], true));
                if ($isMinimax) {
                    $minimaxVoice = $vid;
                    if (empty($copywriting)) {
                        self::setError('选择MiniMax音色时，必须填写文案');
                        return false;
                    }
                    continue;
                }
                if (!self::isChanjingVoiceAvailable($vid)) {
                    self::setError(self::chanjingVoiceUnavailableMessage($vid, (int)self::$uid));
                    return false;
                }
            }
        } elseif (empty($audio)) {
            self::setError('音频文件不能为空');
            return false;
        }

        if (empty($audio) && empty($copywriting)) {
            self::setError('文案和音频不能同时为空');
            return false;
        }

        $params['status'] = 1;
        $params['video_count'] = !empty($audio)
            ? count($audio) * $videoCount
            : count($copywriting) * $videoCount;

        $extraArr = $extra;
        $extraArr['engine_type'] = self::ENGINE_TYPE_CHANJING;
        $extraArr['volume'] = $volume;
        $extraArr['width'] = $width;
        $extraArr['height'] = $height;
        $decodedData['extra'] = $extraArr;
        $params['extra'] = json_encode($extraArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $requestSnapshot = $params;
        $requestSnapshot['engine_type'] = self::ENGINE_TYPE_CHANJING;
        $requestSnapshot['ai_clip_enabled'] = $aiClipEnabled ? 1 : 0;
        $requestSnapshot['packaging'] = $packaging;
        $requestSnapshot['speech_volume'] = $volume;
        $params['request_json'] = json_encode($requestSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Db::startTrans();
        try {
            // 蝉镜算力预检（与 human/videoTask model=7 一致）
            TokenLogService::checkToken(self::$uid, HumanLogic::VIDEO_TRAINING_CHANJING);
            $setting = ShanjianVideoSetting::create($params);

            $minimaxTaskId = 0;
            if ($minimaxVoice !== null) {
                $minimaxTask = self::createAudioTask($setting->id, $minimaxVoice, $copywriting);
                $minimaxTaskId = (int)$minimaxTask->id;
            }

            self::createChanjingBridgeTasksType5(
                (int)$setting->id,
                $params,
                $decodedData,
                $aiClipEnabled,
                $packaging,
                $minimaxVoice,
                $minimaxTaskId > 0 ? $minimaxTaskId : null
            );

            Db::commit();
            self::$returnData = $setting->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 蝉镜形象是否可用
     */
    private static function isChanjingAnchorAvailable(string $anchorId): bool
    {
        return HumanAnchor::where('anchor_id', $anchorId)
                ->where('model_version', 7)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->count() > 0;
    }

    /**
     * 蝉镜音色是否可用
     */
    private static function isChanjingVoiceAvailable(string $voiceId): bool
    {
        return HumanVoice::where('voice_id', $voiceId)
                ->where('model_version', 7)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->count() > 0;
    }

    /**
     * 蝉镜音色不可用提示（优先识别会员冻结）
     */
    private static function chanjingVoiceUnavailableMessage(string $voiceId, ?int $userId = null): string
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return '音色还没有生成';
        }
        if (self::isVoiceMemberFrozen($voiceId, $userId)) {
            return self::frozenVoiceTip();
        }
        $query = HumanVoice::where('voice_id', $voiceId)->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        $voice = $query->order('id', 'desc')->findOrEmpty();
        if (!$voice->isEmpty()) {
            $status = (int)($voice->status ?? -1);
            if ($status === self::memberFrozenStatus()) {
                return self::frozenVoiceTip();
            }
            if ($status === 0) {
                return '音色正在生成中，请稍后再试';
            }
            if ($status === 2) {
                return '音色生成失败，请重新克隆后再试';
            }
        }
        $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(7) ?: '数字人';
        return '音色不可用，请选择已生成的' . $modelName . '音色或MiniMax音色';
    }

    /**
     * 创建蝉镜桥接：每条视频一对 HumanVideoTask + ShanjianVideoTask(type=5,status=-1)
     * 共用 task_id，便于蝉镜回调回写与包装派生
     */
    public static function createChanjingBridgeTasksType5(
        int $settingId,
        array $params,
        array $decodedData,
        bool $aiClipEnabled,
        array $packaging = [],
        ?string $minimaxVoice = null,
        ?int $minimaxTaskId = null
    ): void {
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $pic = (string)($params['pic'] ?? '');
        $isMinimaxPending = $minimaxVoice !== null && $minimaxVoice !== '' && (int)$minimaxTaskId > 0;
        $videoCountPerItem = max(1, (int)($extraData['video_count'] ?? 1));
        $primaryDataSource = !empty($audioData) ? $audioData : $copywritingData;
        $primaryDataCount = count($primaryDataSource);
        if ($primaryDataCount <= 0) {
            throw new \Exception('文案和音频不能同时为空');
        }
        if (count($anchorData) === 0) {
            throw new \Exception('形象不能为空');
        }

        $isFinal = $aiClipEnabled ? 0 : 1;
        $bridgeTasks = [];
        $globalTaskIndex = 0;
        $width = trim((string)($extraData['width'] ?? ''));
        $height = trim((string)($extraData['height'] ?? ''));
        $speechVolume = isset($extraData['volume']) ? (float)$extraData['volume'] : 0.3;

        for ($dataIndex = 0; $dataIndex < $primaryDataCount; $dataIndex++) {
            for ($videoIndex = 0; $videoIndex < $videoCountPerItem; $videoIndex++) {
                $selectedAnchor = $anchorData[$globalTaskIndex % count($anchorData)];
                $selectedVoice = count($voiceData) > 0
                    ? $voiceData[$globalTaskIndex % count($voiceData)]
                    : ['voice_id' => ''];
                $voiceId = $isMinimaxPending
                    ? (string)$minimaxVoice
                    : trim((string)($selectedVoice['voice_id'] ?? ''));
                if (!$isMinimaxPending && self::isChanjingOriginalVoiceId($voiceId)) {
                    $voiceId = '';
                }

                $taskContent = ['title' => '', 'content' => '', 'audio_url' => ''];
                $audioType = 1;
                if (!$isMinimaxPending && !empty($audioData)) {
                    $audioItem = $audioData[$dataIndex % count($audioData)] ?? null;
                    if ($audioItem) {
                        $normalizedAudio = self::normalizeAudioItem($audioItem);
                        $taskContent['audio_url'] = $normalizedAudio['url'];
                        if ($normalizedAudio['text'] !== '') {
                            $taskContent['content'] = $normalizedAudio['text'];
                        }
                        $audioType = 2;
                    }
                }
                if (!empty($copywritingData)) {
                    $copywritingItem = $copywritingData[$dataIndex % count($copywritingData)] ?? null;
                    if ($copywritingItem) {
                        // 文案 title 可选；缺省时回退到设置层 name（前端「视频名称」）
                        $taskContent['title'] = trim((string)($copywritingItem['title'] ?? ''));
                        if (empty($taskContent['content'])) {
                            $taskContent['content'] = $copywritingItem['content'] ?? '';
                        }
                    }
                }
                $taskDuration = (!$isMinimaxPending && !empty($audioData))
                    ? 30
                    : self::taskTextDuration($copywritingItem ?? []);

                $sharedTaskId = generate_unique_task_id();
                $taskUserId = self::$uid !== 0 ? (int)self::$uid : (int)$params['user_id'];
                $settingName = trim((string)($params['name'] ?? ''));
                $taskname = $taskContent['title'] !== ''
                    ? $taskContent['title']
                    : ($settingName !== '' ? $settingName : '数字人口播' . date('YmdHis'));
                $taskname = mb_substr($taskname, 0, 50, 'UTF-8');

                // 先建蝉镜任务（MiniMax 时 audio_url 先空，TTS 完成后回填）
                $humanTask = HumanLogic::createType5ChanjingVideoTask([
                    'user_id' => $taskUserId,
                    'name' => $taskname,
                    'pic' => $pic,
                    'task_id' => $sharedTaskId,
                    'anchor_id' => (string)($selectedAnchor['anchor_id'] ?? ''),
                    'voice_id' => $voiceId,
                    'msg' => (string)($taskContent['content'] ?? ''),
                    'audio_type' => $isMinimaxPending ? 2 : $audioType,
                    'audio_url' => $isMinimaxPending ? '' : (string)($taskContent['audio_url'] ?? ''),
                    'width' => $width,
                    'height' => $height,
                    'minimax_pending' => $isMinimaxPending,
                ]);

                $extra = [
                    'setting_index' => $globalTaskIndex,
                    'create_type' => $isMinimaxPending ? 'minimax_type5_chanjing_pending' : 'type5_chanjing_bridge',
                    'data_source_index' => $dataIndex,
                    'video_index_in_source' => $videoIndex,
                    'billing_duration' => self::formatBillingDuration($taskDuration),
                    'ai_clip_enabled' => $aiClipEnabled,
                    'engine_type' => self::ENGINE_TYPE_CHANJING,
                    'waiting_engine' => 'chanjing',
                    'human_video_task_id' => (int)$humanTask->id,
                    'volume' => $speechVolume,
                    'billing_team_id' => \app\common\service\TeamContextService::currentTeamId($taskUserId),
                ];
                if ($isMinimaxPending) {
                    $extra['minimax_task_id'] = (int)$minimaxTaskId;
                }
                if (!empty($packaging)) {
                    $extra['packaging'] = $packaging;
                }
                $mergedArray = array_merge($extra, $extraData);
                $mergedArray['ai_clip_enabled'] = $aiClipEnabled;
                $mergedArray['engine_type'] = self::ENGINE_TYPE_CHANJING;
                $mergedArray['waiting_engine'] = 'chanjing';
                $mergedArray['human_video_task_id'] = (int)$humanTask->id;
                $mergedArray['billing_team_id'] = $extra['billing_team_id'];
                $mergedArray['volume'] = $speechVolume;
                if ($isMinimaxPending) {
                    $mergedArray['create_type'] = 'minimax_type5_chanjing_pending';
                    $mergedArray['minimax_task_id'] = (int)$minimaxTaskId;
                }
                if (!empty($packaging)) {
                    $mergedArray['packaging'] = $packaging;
                }

                $bridgeTasks[] = [
                    'name' => $taskname,
                    'pic' => $pic ?: (string)($humanTask->pic ?? ''),
                    'task_id' => $sharedTaskId,
                    'status' => -1, // 等待蝉镜/MiniMax，禁止闪剪 cron 下发
                    'audio_type' => $isMinimaxPending ? 1 : $audioType,
                    'shanjian_type' => 5,
                    'is_final' => $isFinal,
                    'user_id' => $taskUserId,
                    'video_setting_id' => $settingId,
                    'minimax_task_id' => $isMinimaxPending ? (int)$minimaxTaskId : 0,
                    'anchor_id' => (string)($selectedAnchor['anchor_id'] ?? ''),
                    'voice_id' => $voiceId,
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $taskContent['title'] ?? '',
                    'msg' => $taskContent['content'] ?? '',
                    'audio_url' => $isMinimaxPending ? '' : ($taskContent['audio_url'] ?? ''),
                    'material' => '[]',
                    'music_url' => '',
                    'thumb_status' => ($pic !== '' || !empty($humanTask->pic)) ? 2 : 4,
                    'duration' => (int)ceil($taskDuration),
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                $globalTaskIndex++;
            }
        }

        if (!empty($bridgeTasks)) {
            (new ShanjianVideoTask())->saveAll($bridgeTasks);
        }
    }

    /**
     * 创建数字人口播无包装(type=5)视频任务
     * @param int $settingId
     * @param array $params
     * @param array $decodedData
     * @param bool $aiClipEnabled AI智剪开关
     * @param array $packaging 智剪开启时派生 type=2 的包装参数
     * @param string|null $minimaxVoice MiniMax voice_id；传入时创建 status=-1 占位任务
     * @param int|null $minimaxTaskId MiniMax TTS 任务 ID
     * @return void
     */
    public static function createVideoTasksType5(
        int $settingId,
        array $params,
        $decodedData,
        bool $aiClipEnabled,
        array $packaging = [],
        ?string $minimaxVoice = null,
        ?int $minimaxTaskId = null
    ): void {
        $taskData = [];

        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $pic = $params['pic'] ?? '';
        $isMinimaxPending = $minimaxVoice !== null && $minimaxVoice !== '' && (int)$minimaxTaskId > 0;

        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }
        // 音频驱动时可无音色; 文案驱动时音色必填
        if (count($voiceData) == 0 && empty($audioData) && !$isMinimaxPending) {
            throw new \Exception("音色不能为空");
        }
        if (empty($audioData) && empty($copywritingData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        $controlParams = $extraData ?? [];
        $humanMode = $controlParams['human'] ?? 1;
        $musicMode = $controlParams['music'] ?? 1;
        $aimusicMode = $controlParams['ai_music'] ?? false;
        $videoCountPerItem = $extraData['video_count'] ?? 1;

        // is_final: 智剪关闭=1(最终视频), 智剪开启=0(中间产物)
        $isFinal = $aiClipEnabled ? 0 : 1;

        $primaryDataSource = !empty($audioData) ? $audioData : $copywritingData;
        $primaryDataCount = count($primaryDataSource);
        $globalTaskIndex = 0;
        $anchorPicMap = self::getAnchorPicMap($anchorData, self::$uid !== 0 ? self::$uid : (int)$params['user_id']);

        for ($dataIndex = 0; $dataIndex < $primaryDataCount; $dataIndex++) {
            for ($videoIndex = 0; $videoIndex < $videoCountPerItem; $videoIndex++) {

                if ($humanMode == 1) {
                    $selectedAnchor = $anchorData[$globalTaskIndex % count($anchorData)];
                    $selectedVoice = count($voiceData) > 0
                        ? $voiceData[$globalTaskIndex % count($voiceData)]
                        : ['voice_id' => ''];
                } else {
                    $selectedAnchor = $anchorData[random_int(0, count($anchorData) - 1)];
                    $selectedVoice = count($voiceData) > 0
                        ? $voiceData[random_int(0, count($voiceData) - 1)]
                        : ['voice_id' => ''];
                }

                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
                if (count($musicData) == 0) {
                    $music_url = $aimusicMode ? $defaultMusic : '';
                } else {
                    if ($musicMode == 1) {
                        $music_url = $musicData[$globalTaskIndex % count($musicData)] ?? $defaultMusic;
                    } else {
                        $music_url = $musicData[random_int(0, count($musicData) - 1)] ?? $defaultMusic;
                    }
                }

                $material = json_encode(is_array($materialData) ? array_values($materialData) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $taskContent = ['title' => '', 'content' => '', 'audio_url' => ''];
                $audioType = 1;
                if (!$isMinimaxPending && !empty($audioData)) {
                    $audioItem = $audioData[$dataIndex % count($audioData)] ?? null;
                    if ($audioItem) {
                        $normalizedAudio = self::normalizeAudioItem($audioItem);
                        $taskContent['audio_url'] = $normalizedAudio['url'];
                        if ($normalizedAudio['text'] !== '') {
                            $taskContent['content'] = $normalizedAudio['text'];
                        }
                        if (!empty($normalizedAudio['words'])) {
                            $taskContent['words'] = $normalizedAudio['words'];
                        }
                        $audioType = 2;
                    }
                }
                if (!empty($copywritingData)) {
                    $copywritingItem = $copywritingData[$dataIndex % count($copywritingData)] ?? null;
                    if ($copywritingItem) {
                        // 文案 title 可选；缺省时回退到设置层 name（前端「视频名称」）
                        $taskContent['title'] = trim((string)($copywritingItem['title'] ?? ''));
                        if (empty($taskContent['content'])) {
                            $taskContent['content'] = $copywritingItem['content'] ?? '';
                        }
                    }
                }
                $taskDuration = (!$isMinimaxPending && !empty($audioData)) ? 30 : self::taskTextDuration($copywritingItem ?? []);

                $taskUserId = self::$uid !== 0 ? self::$uid : (int)$params['user_id'];
                $extra = [
                    'setting_index' => $globalTaskIndex,
                    'create_type' => $isMinimaxPending ? 'minimax_type5_pending' : 'batch',
                    'data_source_index' => $dataIndex,
                    'video_index_in_source' => $videoIndex,
                    'billing_duration' => self::formatBillingDuration($taskDuration),
                    'ai_clip_enabled' => $aiClipEnabled,
                    'engine_type' => self::ENGINE_TYPE_SHANJIAN,
                    // 创建时企业空间:包装派生/异步回调扣费按此结算,避免切换空间后扣错主体
                    'billing_team_id' => \app\common\service\TeamContextService::currentTeamId($taskUserId),
                ];
                if ($isMinimaxPending) {
                    $extra['minimax_task_id'] = (int)$minimaxTaskId;
                }
                if (!empty($taskContent['words'])) {
                    $extra['timed_words'] = $taskContent['words'];
                }
                if (!empty($packaging)) {
                    $extra['packaging'] = $packaging;
                }
                $mergedArray = array_merge($extra, $extraData);
                // ai_clip_enabled / billing_team_id 以本方法显式写入为准, 避免被 extraData 覆盖
                $mergedArray['ai_clip_enabled'] = $aiClipEnabled;
                $mergedArray['engine_type'] = self::ENGINE_TYPE_SHANJIAN;
                $mergedArray['billing_team_id'] = $extra['billing_team_id'];
                if ($isMinimaxPending) {
                    $mergedArray['create_type'] = 'minimax_type5_pending';
                    $mergedArray['minimax_task_id'] = (int)$minimaxTaskId;
                }
                if (!empty($packaging)) {
                    $mergedArray['packaging'] = $packaging;
                }

                // 优先文案 title，其次设置层自定义视频名称，最后默认名
                $settingName = trim((string)($params['name'] ?? ''));
                $taskname = $taskContent['title'] !== ''
                    ? $taskContent['title']
                    : ($settingName !== '' ? $settingName : '数字人口播' . date('YmdHis'));
                $taskname = mb_substr($taskname, 0, 120, 'UTF-8');
                $taskPic = $pic ?: ($selectedAnchor['pic'] ?? '');
                if ($taskPic === '') {
                    $taskPic = $anchorPicMap[$selectedAnchor['anchor_id'] ?? ''] ?? '';
                }

                $voiceId = $isMinimaxPending
                    ? $minimaxVoice
                    : (string)($selectedVoice['voice_id'] ?? '');

                $taskData[] = [
                    'name' => $taskname,
                    'pic' => $taskPic,
                    'task_id' => generate_unique_task_id(),
                    'status' => $isMinimaxPending ? -1 : 0,
                    'audio_type' => $audioType,
                    'shanjian_type' => 5,
                    'is_final' => $isFinal,
                    'user_id' => self::$uid !== 0 ? self::$uid : $params['user_id'],
                    'video_setting_id' => $settingId,
                    'minimax_task_id' => $isMinimaxPending ? (int)$minimaxTaskId : 0,
                    'anchor_id' => $selectedAnchor['anchor_id'] ?? '',
                    'voice_id' => $voiceId,
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $taskContent['title'] ?? '',
                    'msg' => $taskContent['content'] ?? '',
                    'audio_url' => $isMinimaxPending ? '' : ($taskContent['audio_url'] ?? ''),
                    'material' => $material,
                    'music_url' => $music_url,
                    'thumb_status' => empty($taskPic) ? 4 : 2,
                    'duration' => (int)ceil($taskDuration),
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                $globalTaskIndex++;
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 根据 anchor_id 兜底查询形象封面, 支持调用方只传 anchor_id 创建任务
     */
    private static function getAnchorPicMap(array $anchorData, int $userId): array
    {
        $anchorIds = array_values(array_filter(array_unique(array_map(function ($item) {
            return trim((string)($item['anchor_id'] ?? ''));
        }, $anchorData))));
        if (empty($anchorIds)) {
            return [];
        }

        $rows = ShanjianAnchor::whereIn('anchor_id', $anchorIds)
            ->where('user_id', $userId)
            ->field('anchor_id,pic,anchor_url,authorized_pic')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $pic = (string)($row->pic ?: $row->anchor_url ?: $row->authorized_pic);
            if ($pic !== '') {
                $map[(string)$row->anchor_id] = $pic;
            }
        }
        return $map;
    }


    /**
     * 超时收口:创建超过 1440 分钟仍处于生成中的设置任务,
     * 将其未完成子任务标失败,并按成功/失败数回写设置状态。
     *
     * 设置 status: 3全部成功 / 4部分成功 / 5全部失败
     * 子任务 status: 0待处理、1处理中 → 2失败
     */
    public static function check()
    {
        try {
            $settings = ShanjianVideoSetting::whereIn('status', [1, 2])
                ->where('create_time', '<=', strtotime('-1440 minutes'))
                ->order('id', 'asc')
                ->limit(5)
                ->select();

            foreach ($settings as $item) {
                Db::startTrans();
                try {
                    $settingId = (int)$item->id;
                    $now = time();

                    // 获取需要标记为失败的子任务（用于后续退费）
                    // 加行锁:notify 回调同样 lock(true) 该行,保证 SELECT→UPDATE→退费期间状态不会被并发翻成 SUCCESS,
                    // 否则会出现"视频已交付却被全额退费"
                    $failedTasks = ShanjianVideoTask::where('video_setting_id', $settingId)
                        ->whereIn('status', [
                            ShanjianVideoTask::STATUS_PENDING,
                            ShanjianVideoTask::STATUS_PROCESSING,
                        ])
                        ->lock(true)
                        ->select();
                    $failedTaskIds = [];
                    foreach ($failedTasks as $task) {
                        $failedTaskIds[] = (int)$task->id;
                    }

                    // 未完成子任务一并标失败,避免父任务已收口但子任务仍挂"生成中"
                    if ($failedTaskIds) {
                        ShanjianVideoTask::whereIn('id', $failedTaskIds)
                            ->whereIn('status', [
                                ShanjianVideoTask::STATUS_PENDING,
                                ShanjianVideoTask::STATUS_PROCESSING,
                            ])
                            ->update([
                                'status' => ShanjianVideoTask::STATUS_FAILED,
                                'remark' => '父任务超时未完成，系统自动标记失败',
                                'update_time' => $now,
                            ]);
                    }

                    // 对超时失败的子任务进行退费处理(仅退本事务内确实被标记失败的任务)
                    foreach ($failedTasks as $task) {
                        if ((int)ShanjianVideoTask::where('id', $task->id)->value('status') !== ShanjianVideoTask::STATUS_FAILED) {
                            continue;
                        }
                        try {
                            self::refundTimeoutTaskTokens($task);
                        } catch (\Throwable $e) {
                            Log::channel('shanjiannotice')->write(
                                '[设置超时收口退费失败] task_id=' . $task->task_id . ' err=' . $e->getMessage()
                            );
                        }
                    }

                    $successNum = (int)ShanjianVideoTask::where('video_setting_id', $settingId)
                        ->where('status', ShanjianVideoTask::STATUS_SUCCESS)
                        ->count();
                    $videoCount = max((int)$item->video_count, 0);
                    $errorNum = max($videoCount - $successNum, 0);

                    if ($successNum <= 0) {
                        $status = 5; // 全部失败
                    } elseif ($errorNum <= 0) {
                        $status = 3; // 全部成功
                    } else {
                        $status = 4; // 部分成功
                    }

                    ShanjianVideoSetting::where('id', $settingId)->update([
                        'success_num' => $successNum,
                        'error_num' => $errorNum,
                        'status' => $status,
                        'update_time' => $now,
                    ]);

                    Db::commit();
                    Log::channel('shanjiannotice')->write('[设置超时收口] ' . json_encode([
                        'video_setting_id' => $settingId,
                        'success_num' => $successNum,
                        'error_num' => $errorNum,
                        'video_count' => $videoCount,
                        'status' => $status,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } catch (\Throwable $e) {
                    Db::rollback();
                    Log::channel('shanjiannotice')->write(
                        '[设置超时收口失败] setting_id=' . ($item->id ?? 0) . ' err=' . $e->getMessage()
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 退费处理：超时失败的任务退还已扣除的算力
     */
    private static function refundTimeoutTaskTokens(ShanjianVideoTask $task): bool
    {
        $userId = (int)$task->user_id;
        $taskId = (string)$task->task_id;
        
        // 根据 shanjian_type 获取对应的扣费类型
        $typeID = match ((int)$task->shanjian_type) {
            2 => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
            3 => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
            4 => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
            5 => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
            default => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
        };

        // 计算已扣除的算力
        $deducted = (float)UserTokensLog::where('user_id', $userId)
            ->where('change_type', $typeID)
            ->where('action', AccountLogEnum::DEC)
            ->where('task_id', $taskId)
            ->sum('change_amount');

        if ($deducted <= 0) {
            return false;
        }

        // 计算已退还的算力
        $refunded = (float)UserTokensLog::where('user_id', $userId)
            ->where('change_type', $typeID)
            ->where('action', AccountLogEnum::INC)
            ->where('status', 2)
            ->where('task_id', $taskId)
            ->sum('change_amount');

        // 计算需要退还的算力（已扣除 - 已退还）
        $points = round(max(0, $deducted - $refunded), 2);
        if ($points <= 0) {
            return false;
        }

        // 执行退费
        AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId, [
            '扣费项目' => '超时收口算力退回',
            '失败原因' => '父任务超时未完成，系统自动标记失败',
        ]);

        Log::channel('shanjiannotice')->write('[超时收口退费] ' . json_encode([
            'user_id' => $userId,
            'task_id' => $taskId,
            'deducted' => $deducted,
            'refunded' => $refunded,
            'refund_points' => $points,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return true;
    }


    /**
     * 计算组合数 C(n,k)
     * @param int $n 总数
     * @param int $k 选取数量
     * @return int 组合数结果
     */
    private static function combination(int $n, int $k): int
    {
        if ($k > $n || $k < 0) return 0;
        if ($k == 0 || $k == $n) return 1;
        $k = min($k, $n - $k); // 取较小值以减少计算
        $result = 1;
        for ($i = 1; $i <= $k; $i++) {
            $result = $result * ($n - $k + $i) / $i;
        }
        return (int)$result;
    }

    /**
     * 计算视频生成数量上限
     * @param int $materialCount 素材数量
     * @param int $themeVideoCount 主题视频数量
     * @return int 视频生成数量上限
     */
    private static function calculateMaxVideoCount(int $materialCount, int $themeVideoCount = 1): int
    {
        if ($materialCount <= 0 || $themeVideoCount <= 0) return 0;

        // 计算所有可能的素材组合数
        $combinationCount = 0;

        // 单素材组合: C(n,1)
        $combinationCount += self::combination($materialCount, 1);

        // 双素材组合: C(n,2)
        if ($materialCount >= 2) {
            $combinationCount += self::combination($materialCount, 2);
        }

        // 三素材组合: C(n,3)
        if ($materialCount >= 3) {
            $combinationCount += self::combination($materialCount, 3);
        }

        // 全素材组合: C(n,n)
        $combinationCount += self::combination($materialCount, $materialCount);

        // 计算最终上限：组合数 * 主题视频数量
        return $combinationCount * $themeVideoCount;
    }

    /**
     * 类型2视频设置添加
     * @param array $params 请求参数
     * @return bool
     */
    public static function addType2(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $copywriting = $decodedData['copywriting'] ?? [];
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;
            $themeVideoCount = !empty($decodedData['anchor']) && is_array($decodedData['anchor']) ? count($decodedData['anchor']) : 0;

            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $themeVideoCount;
            if ($params['video_count'] == 0) {
                self::setError("形象数量不能为0或者生成的视频数量不能为0");
                return false;
            }
            $anchor = $decodedData['anchor'] ?? [];
            if (count($anchor) == 0) {
                self::setError("形象不能为空");
                return false;
            }
            $duration = self::calculateRealmanDuration($decodedData);

            $params['status'] = 1;

            // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
            // 这里只建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。

            // 开始事务
            Db::startTrans();
            try {
                TokenLogService::checkToken(self::$uid, 'shanjian_realman_broadcast', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType2($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType2(int $settingId, array $params, $decodedData): void
    {

        $taskData = [];

        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $defaultPic = (string)($params['pic'] ?? '');
        $name = $params['name'] ?? '';
        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_url', $data) || trim($data['anchor_url']) === '') {
                throw new \Exception("视频不存在");
            }
        }

        // 模式控制参数：1=按顺序，0=随机
        $controlParams = $extraData ?? [];
        $humanMode = $controlParams['human'] ?? 1; // 控制anchorData选择模式
        $musicMode = $controlParams['music'] ?? 1; // 控制music_url选择模式
        $clipMode = $controlParams['clip'] ?? 1; // 控制clip_id选择模式
        $video_count = $controlParams['video_count'] ?? 1; // 每个素材生成的视频数量
        $aimusicMode = $controlParams['ai_music'] ?? false; // 控制是否使用默认音乐

        // 获取基础资源数量
        $anchorCount = count($anchorData);
        $musicCount = count($musicData);
        $materialCount = count($materialData);
        $clipCount = count($clipData);

        // 外层循环：遍历每个素材
        foreach ($anchorData as $anchorIndex => $currentAnchor) {
            // 内层循环：每个素材生成$video_count个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if ($musicCount > 0) {
                    if ($musicMode == 1) {
                        // 按顺序选择音乐，基于素材索引*视频数量+视频索引循环
                        $musicIndex = $videoIndex % $musicCount;
                        // 检查当前索引的音乐是否有值
                        if (isset($musicData[$musicIndex]) && !empty($musicData[$musicIndex])) {
                            $music_url = $musicData[$musicIndex];
                            $musicStrategy = 'sequential';
                        } else {
                            // 当前索引不存在，从初始开始循环选择
                            $music_url = $musicData[0] ?? $defaultMusic;
                            $musicStrategy = 'sequential_restart_from_beginning';
                        }
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 没有music数据时，每个视频使用不同的随机音乐
                    if ($aimusicMode) {
                        $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 按顺序选择剪辑模板，当$video_count > $anchorCount时使用不同模板
                        $clipIndex = ($anchorIndex * $video_count + $videoIndex) % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 没有clip数据时，从数据库获取模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'realMan')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // 编码为JSON
                $material = json_encode(is_array($materialData) ? array_values($materialData) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // 计算当前视频的全局索引
                $globalIndex = $anchorIndex * $video_count + $videoIndex;
                $taskDuration = self::formatBillingDuration((float)($currentAnchor['duration'] ?? 0));

                $extra = [
                    'setting_index' => $globalIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop',
                    'billing_duration' => $taskDuration,
                    'control_mode' => [
                        'human' => $humanMode,
                        'music' => $musicMode,
                        'clip' => $clipMode
                    ],
                    'selection_info' => [
                        'anchor_index' => $anchorIndex,
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'video_count_per_material' => $video_count,
                        'total_videos' => $materialCount * $video_count,
                        'material_index' => $anchorIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];
                $mergedArray = array_merge($extra, $extraData);
                // $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_形象' . ($anchorIndex + 1) . '_视频' . ($videoIndex + 1);
                // 生成视频名称时包含素材和视频信息
                $videoName = empty($name) ? '真人口播混剪' . date('YmdHi') : $name;
                $modeSuffix = '';
                if ($humanMode == 0) $modeSuffix .= 'H';
                if ($musicMode == 0) $modeSuffix .= 'M';
                if ($clipMode == 0) $modeSuffix .= 'C';

                if (!empty($modeSuffix)) {
                    $videoName .= '_' . $modeSuffix;
                }
                $videoName = mb_substr($videoName, 0, 120, 'UTF-8');
                // 未指定全局封面时，按当前形象独立取封面，避免多形象任务共用第一张
                $taskPic = $defaultPic !== '' ? $defaultPic : (string)($currentAnchor['pic'] ?? '');
                $taskItem = [
                    'name' => $videoName,
                    'pic' => $taskPic,
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => 1, // 文案驱动
                    'shanjian_type' => $params['shanjian_type'] ?? 1,
                    'user_id' => self::$uid,
                    'video_setting_id' => $settingId,
                    'anchor_id' => $currentAnchor['anchor_url'] ?? '',
                    'voice_id' => '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => '',
                    'msg' => '',
                    'material' => $material,
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'thumb_status' => $taskPic !== '' ? 2 : 4,
                    'duration' => (int)ceil($taskDuration),
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time()
                ];

                $taskData[] = $taskItem;
            } // 内层循环结束

        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    public static function addType3(array $params): bool
    {
        try {
            $minimaxVoice = null;
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');

            // 预处理JSON字段
            $jsonFields = ['copywriting', 'material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            // 验证audio和copywriting不能同时存在
            $audio = $decodedData['audio'] ?? [];
            $copywriting = array_values($decodedData['copywriting'] ?? []);
            $decodedData['copywriting'] = $copywriting;
            if (!self::hasNonEmptyMaterialFileUrl($decodedData['material'] ?? [])) {
                self::setError("素材不能为空");
                return false;
            }

            if (!empty($audio) && !empty($copywriting)) {
                self::setError("audio参数和copywriting参数不能同时存在，只能使用其中一个");
                return false;
            }

            $duration = self::calculateMixcutDuration($decodedData);
            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $params['status'] = 1;
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;

            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $materialCount;
            if ($params['video_count'] == 0) {
                self::setError("素材数量不能为0或者生成的视频数量不能为0");
                return false;
            }

            if (isset($params['model_version']) && in_array($params['model_version'], [10, 11])) {
                $minimaxVoice = trim((string)($params['voice'] ?? ''));
                $minimaxVoiceRecord = HumanVoice::where('voice_id', $minimaxVoice)
                    ->where('user_id', self::$uid)
                    ->where('status', 1)
                    ->whereNull('delete_time')
                    ->findOrEmpty();
                if ($minimaxVoiceRecord->isEmpty()
                    || !in_array((int)$minimaxVoiceRecord->model_version, [10, 11], true)) {
                    self::setError('Minimax音色不存在、已失效或模型类型不匹配');
                    return false;
                }
                if (empty($copywriting)) {
                    self::setError('选择MiniMax音色时，必须填写文案');
                    return false;
                }
            }

            // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
            // 这里只建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。

            // 开始事务
            Db::startTrans();
            try {
                $unit = TokenLogService::checkToken(self::$uid, 'shanjian_broadcast_mixcut', $duration);
                $params['request_json'] = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $setting = ShanjianVideoSetting::create($params);

                if (isset($minimaxVoice)) {
                    // 仅对实际会落到占位视频上的文案建 MiniMax TTS。
                    // 文案数 > 总占位数时，多余文案不再建音频任务，避免完成后无占位而误走整批新建。
                    $videoCountPerMaterial = max(1, (int)($extra['video_count'] ?? 1));
                    $contentMode = (int)($extra['content_mode'] ?? 1);
                    $copywritingCount = count($copywriting);
                    $usedCopywritingIndexes = [];
                    if ($contentMode === 1 && $copywritingCount > 0) {
                        $totalVideos = $materialCount * $videoCountPerMaterial;
                        for ($i = 0; $i < $totalVideos; $i++) {
                            $usedCopywritingIndexes[$i % $copywritingCount] = true;
                        }
                    } else {
                        // 随机文案模式无法预知选中哪条，仍为全部文案建 TTS
                        foreach (array_keys($copywriting) as $copywritingIndex) {
                            $usedCopywritingIndexes[$copywritingIndex] = true;
                        }
                    }

                    $minimaxTaskIds = [];
                    foreach ($copywriting as $copywritingIndex => $copywritingItem) {
                        if (empty($usedCopywritingIndexes[$copywritingIndex])) {
                            continue;
                        }
                        $minimaxTask = self::createAudioTask(
                            $setting->id,
                            $minimaxVoice,
                            [$copywritingItem]
                        );
                        $minimaxTaskIds[$copywritingIndex] = (int)$minimaxTask->id;
                    }
                    // 同步按「素材组数 × 每组视频数」创建占位任务。TTS/ASR 完成后批量回填。
                    self::createPendingMinimaxVideoTasksType3(
                        (int)$setting->id,
                        $params,
                        $decodedData,
                        $minimaxVoice,
                        $minimaxTaskIds
                    );
                } else {
                    // 如果状态为1，创建对应的视频任务
                    self::createVideoTasksType3($setting->id, $params, $decodedData);
                }


                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 为 MiniMax type3 建立等待音频的批量视频任务。
     *
     * 每条文案对应一个 MiniMax 音频任务；视频任务按素材组和 video_count（每组数量）展开，
     * 并通过 minimax_task_id 关联到其选中的文案音频。
     */
    private static function createPendingMinimaxVideoTasksType3(
        int $settingId,
        array $params,
        array $decodedData,
        string $minimaxVoice,
        array $minimaxTaskIds
    ): void {
        $materialData = $decodedData['material'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $videoCount = max(1, (int)($extraData['video_count'] ?? 1));
        $contentMode = (int)($extraData['content_mode'] ?? 1);
        $musicMode = (int)($extraData['music'] ?? 1);
        // 所有调用方写入的键是 ai_music（兼容读取历史 aimusic 拼写）
        $useDefaultMusic = !empty($extraData['ai_music']) || !empty($extraData['aimusic']);
        $defaultPic = (string)($params['pic'] ?? '');
        $taskData = [];
        $copywritingCount = count($copywritingData);

        if ($copywritingCount === 0) {
            throw new \Exception('MiniMax音色合成文案不能为空');
        }

        foreach ($materialData as $materialIndex => $currentMaterial) {
            for ($videoIndex = 0; $videoIndex < $videoCount; $videoIndex++) {
                $globalIndex = $materialIndex * $videoCount + $videoIndex;
                $selectedMaterial = is_array($currentMaterial) ? $currentMaterial : [];
                $taskPic = $defaultPic;
                foreach ($selectedMaterial as $key => &$material) {
                    if (!is_array($material)) {
                        continue;
                    }
                    if (isset($material['cover'])) {
                        if ($taskPic === '') {
                            $taskPic = (string)$material['cover'];
                        }
                        unset($selectedMaterial[$key]['cover']);
                    }
                    if (($material['type'] ?? '') === 'video') {
                        $material['soundSwitch'] = self::normalizeSoundSwitch($extraData['soundSwitch'] ?? false);
                    }
                }
                unset($material);

                $copywritingIndex = $contentMode === 1
                    ? $globalIndex % $copywritingCount
                    : array_rand($copywritingData);
                $copywritingItem = $copywritingData[$copywritingIndex];
                $minimaxTaskId = (int)($minimaxTaskIds[$copywritingIndex] ?? 0);
                if ($minimaxTaskId <= 0) {
                    throw new \Exception('MiniMax音频任务创建失败');
                }
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . random_int(1, 20) . '.mp3';
                if (!empty($musicData)) {
                    $musicIndex = $musicMode === 1
                        ? $globalIndex % count($musicData)
                        : array_rand($musicData);
                    $musicItem = $musicData[$musicIndex] ?? '';
                    $musicUrl = is_array($musicItem) ? (string)($musicItem['fileUrl'] ?? '') : (string)$musicItem;
                } else {
                    $musicUrl = $useDefaultMusic ? $defaultMusic : '';
                }

                $taskExtra = is_array($extraData) ? $extraData : [];
                $taskExtra['minimax_task_id'] = $minimaxTaskId;
                $taskExtra['setting_index'] = $globalIndex;
                $taskExtra['material_index'] = $materialIndex;
                $taskExtra['video_index'] = $videoIndex;
                $taskExtra['create_type'] = 'minimax_type3_pending';

                $title = (string)($copywritingItem['title'] ?? '');
                $taskData[] = [
                    'name'             => mb_substr($title !== '' ? $title : ($params['name'] ?? 'MiniMax混剪'), 0, 120, 'UTF-8'),
                    'pic'              => $taskPic,
                    'task_id'          => generate_unique_task_id(),
                    'status'           => -1,
                    'audio_type'       => 1,
                    'shanjian_type'    => 3,
                    'user_id'          => self::$uid,
                    'video_setting_id' => $settingId,
                    'minimax_task_id'  => $minimaxTaskId,
                    'anchor_id'        => '',
                    'voice_id'         => $minimaxVoice,
                    'card_name'        => '',
                    'card_introduced'  => '',
                    'title'            => $title,
                    'msg'              => (string)($copywritingItem['content'] ?? ''),
                    'material'         => json_encode(array_values($selectedMaterial), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'music_url'        => $musicUrl,
                    'clip_id'          => self::resolveType3ClipId($clipData, $extraData, $globalIndex),
                    'audio_url'        => '',
                    'duration'         => (int)ceil(self::formatBillingDuration(self::taskTextDuration($copywritingItem))),
                    'extra'            => json_encode($taskExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'thumb_status'     => $taskPic !== '' ? 2 : 4,
                    'create_time'      => time(),
                    'update_time'      => time(),
                ];
            }
        }

        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    public static function createVideoTasksType3(int $settingId, array $params, $decodedData): void
    {


        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $aimusicMode = $extraData['ai_music'] ?? false; // 控制是否使用默认音乐
        // 用户显式传入的封面；各任务在此基础上按「当前素材组」独立取封面，避免多素材组共用第一组封面
        $defaultPic = (string)($params['pic'] ?? '');

        // 音频驱动时可无音色; 文案驱动时音色必填
        $voice = trim((string)($params['voice'] ?? ''));
        if ($voice === '' && empty($audioData)) {
            throw new \Exception("音色不能为空");
        }
        // 验证素材数据
        if (!self::hasNonEmptyMaterialFileUrl($materialData)) {
            throw new \Exception("素材不能为空");
        }

        // 获取模式控制参数
        $musicMode = $extraData['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $extraData['clip'] ?? 1; // 1=顺序，0=随机
        $contentMode = $extraData['content_mode'] ?? 1; // 1=顺序，0=随机
        $video_count = $extraData['video_count'] ?? 1; // 每个素材生成的视频数量

        // 计算资源数量
        $materialCount = count($materialData);
        $copywritingCount = count($copywritingData);
        $audioCount = count($audioData);
        $clipCount = count($clipData);

        // 验证文案或音频数据（其中一个必须存在）
        if (empty($audioData) && empty($copywritingData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证copywriting数据
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }
        // 外层循环：遍历每个素材
        foreach ($materialData as $materialIndex => $currentMaterial) {
            // 内层循环：每个素材生成 $video_count 个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // 计算当前视频的全局索引
                $globalIndex = $materialIndex * $video_count + $videoIndex;

                // ===== 素材选择逻辑 =====
                // 当前素材就是选中的素材
                $selectedMaterial = $currentMaterial;
                $taskPic = $defaultPic;
                foreach ($selectedMaterial as $key => &$value) {
                    if (isset($value['cover'])) {
                        // 未指定全局封面时，取当前素材组第一个 cover 作为本任务封面
                        if ($taskPic === '') {
                            $taskPic = (string)$value['cover'];
                        }
                        unset($selectedMaterial[$key]['cover']);
                    }
                    if (isset($value['type']) && $value['type'] == 'video') {
                        $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                        $value['soundSwitch'] = self::normalizeSoundSwitch($soundSwitch);
                    }
                }
                unset($value);
                $taskThumbStatus = $taskPic !== '' ? 2 : 4;
                $materialJson = json_encode(is_array($selectedMaterial) ? array_values($selectedMaterial) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 顺序选择剪辑模板
                        $clipIndex = $globalIndex % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 获取剪辑模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'oralMixCutting')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    // 数据验证
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    // 从数据库获取模板
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if (count($musicData) > 0) {
                    if ($musicMode == 1) {
                        // 顺序选择音乐
                        $musicIndex = $globalIndex % count($musicData);
                        $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                        $musicStrategy = 'sequential';
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 控制是否使用默认音乐
                    if ($aimusicMode) {
                        $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }

                // ===== 内容选择逻辑（文案或音频）=====
                $taskItem = [];

                if (!empty($copywritingData)) {
                    // 使用文案
                    $copywritingItem = null;

                    if ($contentMode == 1) {
                        // 顺序选择文案
                        $copywritingIndex = $globalIndex % $copywritingCount;
                        $copywritingItem = $copywritingData[$copywritingIndex];
                        $contentStrategy = 'sequential_copywriting';
                    } else {
                        // 随机选择文案
                        $copywritingIndex = array_rand($copywritingData);
                        $copywritingItem = $copywritingData[$copywritingIndex];
                        $contentStrategy = 'random_copywriting';
                    }
                    $taskDuration = self::formatBillingDuration(self::taskTextDuration($copywritingItem));

                    $taskItem = [
                        'pic' => $taskPic,
                        'thumb_status' => $taskThumbStatus,
                        'task_id' => generate_unique_task_id(),
                        'status' => 0, // 待处理
                        'audio_type' => 1, // 文案驱动
                        'shanjian_type' => 3,
                        'user_id' => self::$uid,
                        'video_setting_id' => $settingId,
                        'anchor_id' => '',
                        'voice_id' => $voice ?? '',
                        'card_name' => $characterDesignData[0]['name'] ?? '',
                        'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                        'title' => $copywritingItem['title'] ?? '',
                        'msg' => $copywritingItem['content'] ?? '',
                        'material' => $materialJson,
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'duration' => (int)ceil($taskDuration),
                        'extra' => json_encode($extraData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'audio_url' => '', // 文案模式下不包含音频地址
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                } elseif (!empty($audioData)) {
                    // 使用音频
                    $audioItem = null;

                    if ($contentMode == 1) {
                        // 顺序选择音频
                        $audioIndex = $globalIndex % $audioCount;
                        $audioItem = $audioData[$audioIndex];
                        $contentStrategy = 'sequential_audio';
                    } else {
                        // 随机选择音频
                        $audioIndex = array_rand($audioData);
                        $audioItem = $audioData[$audioIndex];
                        $contentStrategy = 'random_audio';
                    }
                    $taskDuration = 30;

                    $normalizedAudio = self::normalizeAudioItem($audioItem);
                    $taskExtra = is_array($extraData) ? $extraData : [];
                    if (!empty($normalizedAudio['words'])) {
                        $taskExtra['timed_words'] = $normalizedAudio['words'];
                    }

                    $taskItem = [
                        'pic' => $taskPic,
                        'thumb_status' => $taskThumbStatus,
                        'task_id' => generate_unique_task_id(),
                        'status' => 0, // 待处理
                        'audio_type' => 2, // 音频驱动
                        'shanjian_type' => 3,
                        'user_id' => self::$uid !== 0 ? self::$uid : $params['user_id'],
                        'video_setting_id' => $settingId,
                        'anchor_id' => '',
                        'voice_id' => $voice ?? '',
                        'card_name' => '',
                        'card_introduced' => '',
                        'title' =>  '',
                        'msg' =>  $normalizedAudio['text'],
                        'extra' => json_encode($taskExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'material' => $materialJson,
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'duration' => (int)ceil($taskDuration),
                        'audio_url' => $normalizedAudio['url'],
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                }

                // 添加额外的调试信息
                $extra = [
                    'setting_index' => $globalIndex,
                    'material_index' => $materialIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop_type3',
                    'content_mode' => $contentMode,
                    'video_count_per_material' => $video_count,
                    'total_videos' => $materialCount * $video_count,
                    'billing_duration' => self::formatBillingDuration($taskDuration ?? 0),
                    'selection_info' => [
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                        'content_strategy' => $contentStrategy
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'material_index' => $materialIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];

                // 合并额外的调试信息到任务项
                if (isset($taskItem['extra'])) {
                    $existingExtra = json_decode($taskItem['extra'], true);
                    $mergedExtra = array_merge($extra, $existingExtra);
                    $taskItem['extra'] = json_encode($mergedExtra, JSON_UNESCAPED_UNICODE);
                }
                $taskItem['title'] = $taskItem['title'] ?? '';
                $taskItem['name'] = !empty($taskItem['title']) ? $taskItem['title'] : "素材混剪" . date('YmdHis');
                $taskItem['name'] = mb_substr($taskItem['name'], 0, 120, 'UTF-8');
                $taskData[] = $taskItem;
            } // 内层循环结束
        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 解析 type3 MiniMax 占位任务的剪辑模板。
     *
     * MiniMax 音频完成后只回填音频相关字段，不会补写 clip_id，
     * 因此这里必须在创建占位任务时得到有效模板。
     *
     * @throws \Exception 模板库为空时中断建单事务
     */
    private static function resolveType3ClipId(array $clipData, array $extraData, int $globalIndex = 0): string
    {
        $clipIds = array_values(array_filter(array_map(static function ($clip) {
            return is_array($clip) ? trim((string)($clip['clip_template_id'] ?? '')) : '';
        }, $clipData)));

        if (!empty($clipIds)) {
            $clipMode = (int)($extraData['clip'] ?? 1);
            return $clipMode === 1
                ? $clipIds[$globalIndex % count($clipIds)]
                : $clipIds[array_rand($clipIds)];
        }

        $defaultClipIds = ShanjianClipTemplate::where('scene', 'oralMixCutting')->column('id');
        if (empty($defaultClipIds)) {
            throw new \Exception('缺少剪辑模版');
        }

        return (string)$defaultClipIds[array_rand($defaultClipIds)];
    }

    /**
     * 创建任务项（基于音频）
     */
    private static function createTaskItemWithAudio(
        int $index,
        array $params,
        $audioItem,
        string $material,
        array $musicData,
        array $clipData,
        array $clip_template_id,
        int $clip_template_total,
        int $settingId,
        array $selectionContext = []
    ): array {

        // ===== 数据选择逻辑 =====
        $musicMode = $selectionContext['music_mode'] ?? 1; // 1=顺序，0=随机
        $clipMode = $selectionContext['clip_mode'] ?? 1; // 1=顺序，0=随机
        $globalIndex = $selectionContext['global_index'] ?? $index;
        $materialIndex = $selectionContext['material_index'] ?? 0;
        $videoIndex = $selectionContext['video_index'] ?? 0;
        $video_count = $selectionContext['video_count'] ?? 1;
        $materialCount = $selectionContext['material_count'] ?? 1;
        $clipCount = count($clipData);
        $musicCount = count($musicData);

        // ===== 音乐选择逻辑 =====
        $number = random_int(1, 20);
        $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

        if ($musicCount > 0) {
            if ($musicMode == 1) {
                // 顺序选择音乐
                $musicIndex = $globalIndex % $musicCount;
                $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                $musicStrategy = 'sequential';
            } else {
                // 随机选择音乐
                $randomIndex = array_rand($musicData);
                $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                $musicStrategy = 'random';
            }
        } else {
            // 没有music数据时使用默认音乐
            $music_url = $defaultMusic;
            $musicStrategy = 'default_random';
        }

        // ===== 剪辑模板选择逻辑 =====
        if ($clipCount > 0) {
            if ($clipMode == 1) {
                // 顺序选择剪辑模板
                $clipIndex = $globalIndex % $clipCount;
                $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $clipIndex;
                $clipStrategy = 'sequential';
            } else {
                // 随机选择剪辑模板
                $randomIndex = array_rand($clipData);
                $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $randomIndex;
                $clipStrategy = 'random';
            }
        } else {
            // 从数据库获取模板
            $clip = random_int(0, $clip_template_total);
            $clip_id = $clip_template_id[$clip];
            $clipTemplateIndex = $clip;
            $clipStrategy = 'default_random';
        }

        // ===== 素材处理逻辑 =====
        $material = json_decode($material, true);
        $pic = "";
        $extra = $params['extra'] ?? [];
        $decodedData['extra'] = json_decode($extra, true);

        foreach ($material as $key => &$value) {
            if (isset($value['cover'])) {
                $pic = $value['cover'];
                unset($material[$key]['cover']);
            }
            if (isset($value['type']) && $value['type'] == 'video') {
                $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                $value['soundSwitch'] = self::normalizeSoundSwitch($soundSwitch);
            }
        }
        $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ===== 基础参数处理 =====
        $voice = $params['voice'] ?? false;
        $shanjian_type = $params['shanjian_type'] ?? 3;

        // ===== 调试信息构建 =====
        $extra = [
            'setting_index' => $globalIndex,
            'material_index' => $materialIndex,
            'video_index' => $videoIndex,
            'create_type' => 'batch_with_context_audio',
            'video_count_per_material' => $video_count,
            'total_videos' => $materialCount * $video_count,
            'selection_info' => [
                'clip_template_index' => $clipTemplateIndex,
                'clip_strategy' => $clipStrategy,
                'music_strategy' => $musicStrategy,
                'content_strategy' => 'audio'
            ],
            'loop_info' => [
                'material_count' => $materialCount,
                'material_index' => $materialIndex,
                'video_index_in_material' => $videoIndex,
                'global_video_index' => $globalIndex
            ]
        ];

        $mergedArray = array_merge($extra, $decodedData['extra']);

        // ===== 视频名称生成 =====
        $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
        $modeSuffix = $musicMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
        $videoName .= '_' . $modeSuffix;

        $normalizedAudio = self::normalizeAudioItem($audioItem);
        if (!empty($normalizedAudio['words'])) {
            $mergedArray['timed_words'] = $normalizedAudio['words'];
        }

        return [
            'name' => $videoName,
            'pic' => $pic,
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 2, // 音频驱动
            'shanjian_type' => $shanjian_type,
            'user_id' => self::$uid,
            'video_setting_id' => $settingId,
            'anchor_id' => '',
            'voice_id' => $voice ?? '',
            'card_name' => '',
            'card_introduced' => '',
            'title' =>  '',
            'msg' =>  $normalizedAudio['text'],
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'audio_url' => $normalizedAudio['url'],
            'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    /**
     * 创建任务项（基于文案）
     */
    private static function createTaskItem(
        int $index,
        array $params,
        array $copywritingItem,
        array $characterDesignData,
        string $material,
        array $musicData,
        array $clipData,
        array $clip_template_id,
        int $clip_template_total,
        int $settingId,
        array $selectionContext = []
    ): array {

        // ===== 数据选择逻辑 =====
        $musicMode = $selectionContext['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $selectionContext['clip'] ?? 1; // 1=顺序，0=随机
        $globalIndex = $selectionContext['global_index'] ?? $index;
        $materialIndex = $selectionContext['material_index'] ?? 0;
        $videoIndex = $selectionContext['video_index'] ?? 0;
        $video_count = $selectionContext['video_count'] ?? 1;
        $materialCount = $selectionContext['material_count'] ?? 1;
        $clipCount = count($clipData);
        $musicCount = count($musicData);

        // ===== 音乐选择逻辑 =====
        $number = random_int(1, 20);
        $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

        if ($musicCount > 0) {
            if ($musicMode == 1) {
                // 顺序选择音乐
                $musicIndex = $globalIndex % $musicCount;
                $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                $musicStrategy = 'sequential';
            } else {
                // 随机选择音乐
                $randomIndex = array_rand($musicData);
                $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                $musicStrategy = 'random';
            }
        } else {
            // 没有music数据时使用默认音乐
            $music_url = $defaultMusic;
            $musicStrategy = 'default_random';
        }

        // ===== 剪辑模板选择逻辑 =====
        if ($clipCount > 0) {
            if ($clipMode == 1) {
                // 顺序选择剪辑模板
                $clipIndex = $globalIndex % $clipCount;
                $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $clipIndex;
                $clipStrategy = 'sequential';
            } else {
                // 随机选择剪辑模板
                $randomIndex = array_rand($clipData);
                $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $randomIndex;
                $clipStrategy = 'random';
            }
        } else {
            // 从数据库获取模板
            $clip = random_int(0, $clip_template_total);
            $clip_id = $clip_template_id[$clip];
            $clipTemplateIndex = $clip;
            $clipStrategy = 'default_random';
        }

        // ===== 素材处理逻辑 =====
        $material = json_decode($material, true);
        $pic = "";
        $extra = $params['extra'] ?? [];
        $decodedData['extra'] = json_decode($extra, true);

        foreach ($material as $key => &$value) {
            if (isset($value['cover'])) {
                $pic = $value['cover'];
                unset($material[$key]['cover']);
            }
            if (isset($value['type']) && $value['type'] == 'video') {
                $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                $value['soundSwitch'] = self::normalizeSoundSwitch($soundSwitch);
            }
        }
        $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ===== 基础参数处理 =====
        $voice = $params['voice'] ?? false;
        $shanjian_type = $params['shanjian_type'] ?? 3;

        // ===== 调试信息构建 =====
        $extra = [
            'setting_index' => $globalIndex,
            'material_index' => $materialIndex,
            'video_index' => $videoIndex,
            'create_type' => 'batch_with_context',
            'video_count_per_material' => $video_count,
            'total_videos' => $materialCount * $video_count,
            'selection_info' => [
                'clip_template_index' => $clipTemplateIndex,
                'clip_strategy' => $clipStrategy,
                'music_strategy' => $musicStrategy,
                'content_strategy' => 'copywriting'
            ],
            'loop_info' => [
                'material_count' => $materialCount,
                'material_index' => $materialIndex,
                'video_index_in_material' => $videoIndex,
                'global_video_index' => $globalIndex
            ]
        ];

        $mergedArray = array_merge($extra, $decodedData['extra']);

        // ===== 视频名称生成 =====
        $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
        $modeSuffix = $musicMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
        $videoName .= '_' . $modeSuffix;

        return [
            'name' => $videoName,
            'pic' => $pic,
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 1, // 文案驱动
            'shanjian_type' => $shanjian_type,
            'user_id' => self::$uid,
            'video_setting_id' => $settingId,
            'anchor_id' => '',
            'voice_id' => $voice ?? '',
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => $copywritingItem['title'] ?? '',
            'msg' => $copywritingItem['content'] ?? '',
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'audio_url' => '', // 文案模式下不包含音频地址
            'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    public static function addType4(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');

            // 预处理JSON字段
            $jsonFields = ['copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            $copywritingData = $decodedData['copywriting'] ?? [];
            $audioData = $decodedData['audio'] ?? [];

            // 文案和音频不能同时为空
            if (empty($copywritingData)) {
                throw new \Exception('请提供文案内容');
            }
            if (!empty($audioData)) {
                throw new \Exception('新闻体不能传音频内容');
            }

            // 验证文案数据
            foreach ($copywritingData as $data) {
                // 检查标题是否存在且为非空数组
                if (!isset($data['title']) || !is_array($data['title']) || empty($data['title'])) {
                    throw new \Exception("标题必须填写");
                }
            }
            $extra = $decodedData['extra'] ?? [];
            if (!self::hasNonEmptyMaterialFileUrl($decodedData['material'] ?? [])) {
                self::setError("素材不能为空");
                return false;
            }

            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $params['status'] = 1;
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;

            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $materialCount;
            if ($params['video_count'] == 0) {
                self::setError("素材数量不能为0或者生成的视频数量不能为0");
                return false;
            }
            $duration = self::calculateNewsMixcutDuration($decodedData);

            // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
            // 这里只建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。

            // 开始事务
            Db::startTrans();
            try {
                TokenLogService::checkToken(self::$uid, 'shanjian_news_mixcut', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType41($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType4(int $settingId, array $params, $decodedData): void
    {
        // 获取剪辑模板
        $clip_template_id = ShanjianClipTemplate::where('scene', 'newsMixCutting')->column('id');
        $clip_template_total = count($clip_template_id) - 1;

        // 设置视频数量上下限：1-50
        $videoCount = $params['video_count'] ?? 1;
        if ($videoCount > 50) {
            throw new \Exception("视频数量不能超过50");
        }
        if ($videoCount < 1) {
            throw new \Exception("视频数量不能小于1");
        }

        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        // 数据验证
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }

        // 文案和音频不能同时为空
        if (empty($copywritingData) && empty($audioData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证文案数据
        if (!empty($copywritingData)) {
            foreach ($copywritingData as $data) {
                if (!isset($data['title']) || !is_array($data['title']) || empty($data['title'])) {
                    throw new \Exception("标题必须填写");
                }
            }
        }
        // 验证音频数据
        if (!empty($audioData)) {
            foreach ($audioData as $data) {
                if (!array_key_exists('url', $data) || trim($data['url']) === '') {
                    throw new \Exception("音频地址不能为空");
                }
                if (!array_key_exists('duration', $data) || floatval($data['duration']) <= 0) {
                    throw new \Exception("音频时长必须大于0");
                }
            }
        }

        if (!self::hasNonEmptyMaterialFileUrl($materialData)) {
            throw new \Exception("素材不能为空");
        }
        foreach ($copywritingData as &$data) {;
            if (isset($data['title']) && is_array($data['title'])) {
                $data['title'] = implode('\n', $data['title']);
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        // 计算文案数量和素材组数量
        $copywritingCount = !empty($copywritingData) ? count($copywritingData) : 0;
        $audioCount = !empty($audioData) ? count($audioData) : 0;
        $materialGroupCount = count($materialData); // 素材组数量

        // 根据参数类型计算视频数量
        $effectiveCount = !empty($audioData) ? $audioCount : $copywritingCount;
        // 随机选择素材组的函数
        $getRandomMaterialGroup = function () use ($materialData) {
            // 从已有的素材组中随机选择一组（手动任务不过滤时长）
            $randomGroupIndex = array_rand($materialData);
            $group = $materialData[$randomGroupIndex];
            if (!is_array($group)) {
                $group = [];
            }
            // 组可能是「素材列表」或「单个素材」
            $isList = isset($group[0]) && is_array($group[0]);
            $list = $isList ? array_values($group) : [$group];
            return json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };
        // 生成视频任务
        if ($effectiveCount > $materialGroupCount) {
            // 有效数据数量 > 素材组数量
            // 将有效数据分别匹配素材组以此达成请求数量
            for ($i = 0; $i < $videoCount; $i++) {
                $effectiveIndex = $i % $effectiveCount;
                $materialGroupIndex = $i % $materialGroupCount;

                // 根据数据类型获取对应的数据项
                if ($audioData) {
                    $effectiveData = $audioData[$effectiveIndex];
                    $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $materialData[$materialGroupIndex], $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                } else {
                    $effectiveData = $copywritingData[$effectiveIndex];
                    $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $materialData[$materialGroupIndex], $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                }

                $taskData[] = $taskItem;
            }
        } else {

            // 有效数据数量 <= 素材组数量
            $maxPossible = $effectiveCount * $materialGroupCount;
            if ($videoCount < $maxPossible) {
                // 随机选择素材组与有效数据匹配生成对应的视频
                // 确保每个有效数据都有机会被使用
                $usedCombinations = [];

                for ($i = 0; $i < $videoCount; $i++) {
                    // 根据数据类型随机选择有效数据
                    if ($audioData) {
                        $effectiveIndex = array_rand($audioData);
                        $effectiveData = $audioData[$effectiveIndex];
                    } else {
                        $effectiveIndex = array_rand($copywritingData);
                        $effectiveData = $copywritingData[$effectiveIndex];
                    }

                    // 随机选择一个素材组（手动任务不过滤时长）
                    $selectedMaterial = $materialData[array_rand($materialData)];
                    $materialList = (is_array($selectedMaterial) && isset($selectedMaterial[0]) && is_array($selectedMaterial[0]))
                        ? array_values($selectedMaterial)
                        : (is_array($selectedMaterial) ? [$selectedMaterial] : []);
                    $material = json_encode(array_values($materialList), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    // 生成组合键，避免重复
                    $combinationKey = $effectiveIndex . '-' . $material;

                    // 如果该组合已使用，重新选择
                    $attempts = 0;
                    while (in_array($combinationKey, $usedCombinations) && $attempts < 10) {
                        if ($audioData) {
                            $effectiveIndex = array_rand($audioData);
                            $effectiveData = $audioData[$effectiveIndex];
                        } else {
                            $effectiveIndex = array_rand($copywritingData);
                            $effectiveData = $copywritingData[$effectiveIndex];
                        }
                        $selectedMaterial = $materialData[array_rand($materialData)];
                        $materialList = (is_array($selectedMaterial) && isset($selectedMaterial[0]) && is_array($selectedMaterial[0]))
                            ? array_values($selectedMaterial)
                            : (is_array($selectedMaterial) ? [$selectedMaterial] : []);
                        $material = json_encode(array_values($materialList), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $combinationKey = $effectiveIndex . '-' . $material;
                        $attempts++;
                    }

                    $usedCombinations[] = $combinationKey;

                    // 根据数据类型创建任务项
                    if ($audioData) {
                        $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    } else {
                        $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    }

                    $taskData[] = $taskItem;
                }
            } else {
                // 循环使用有效数据和素材组生成视频
                for ($i = 0; $i < $videoCount; $i++) {
                    $effectiveIndex = $i % $effectiveCount;
                    $selectedMaterial = $materialData[array_rand($materialData)];

                    // 根据数据类型获取对应的数据项
                    if ($audioData) {
                        $effectiveData = $audioData[$effectiveIndex];
                        $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    } else {
                        $effectiveData = $copywritingData[$effectiveIndex];
                        $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    }

                    $taskData[] = $taskItem;
                }
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    public static function addType41(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $copywriting = $decodedData['copywriting'] ?? [];
            $audioData = $decodedData['audio'] ?? [];

            // 验证文案和音频不能同时为空
            if (empty($copywriting) && empty($audioData)) {
                self::setError("文案和音频至少需要提供一种");
                return false;
            }

            // 验证音频数据（如果提供）
            if (!empty($audioData)) {
                foreach ($audioData as $audioItem) {
                    if (empty($audioItem['url'])) {
                        self::setError("音频URL不能为空");
                        return false;
                    }
                    if (empty($audioItem['duration']) || $audioItem['duration'] <= 0) {
                        self::setError("音频时长必须大于0");
                        return false;
                    }
                }
            }

            // 计算token消耗时长
            $duration = 0;
            foreach ($copywriting as $key => $value) {
                if (!empty($value['content'])) {
                    $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                }
            }
            if ($duration > 0) {
                $duration = $duration / 3;
            }

            // 如果有音频数据，根据音频时长计算
            if (!empty($audioData)) {
                $audioDuration = 0;
                foreach ($audioData as $audioItem) {
                    if (is_string($audioItem)) {
                        // 字符串格式：支持双反引号嵌套格式，默认时长30秒
                        $audioDuration += 30;
                    } elseif (is_array($audioItem) && isset($audioItem['duration'])) {
                        // 对象格式
                        $audioDuration += $audioItem['duration'];
                    }
                }
                $duration += $audioDuration;
            }

            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;
            if (!self::hasNonEmptyMaterialFileUrl($decodedData['material'] ?? [])) {
                self::setError("素材不能为空");
                return false;
            }

            // 动态计算视频数量：优先使用音频数量，否则使用文案数量
            $copywritingCount = !empty($copywriting) ? count($copywriting) : 0;
            $audioCount = !empty($audioData) ? count($audioData) : 0;
            $params['video_count'] = max($copywritingCount, $audioCount);

            // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
            // 这里只建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。

            // 开始事务
            Db::startTrans();
            try {
                $unit = TokenLogService::checkToken(self::$uid, 'human_video_shanjian', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType4($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType41(int $settingId, array $params, $decodedData): void
    {
        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        // 用户显式传入的封面；各任务按当前素材组独立取封面
        $defaultPic = (string)($params['pic'] ?? '');
        // 验证素材数据
        if (!self::hasNonEmptyMaterialFileUrl($materialData)) {
            throw new \Exception("素材不能为空");
        }

        // 验证文案和音频不能同时为空
        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }

        // 验证文案数据（如果提供）
        if (count($copywritingData) > 0) {
            foreach ($copywritingData as &$data) {
                if (!array_key_exists('title', $data) || count($data['title']) == 0) {
                    throw new \Exception("标题不能为空");
                }
                if (isset($data['title']) && is_array($data['title'])) {
                    $data['title'] = implode('\n', $data['title']);
                }
            }
        }

        // 获取模式控制参数
        $musicMode = $extraData['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $extraData['clip'] ?? 1; // 1=顺序，0=随机
        $contentMode = $extraData['content_mode'] ?? 1; // 1=顺序，0=随机
        $video_count = $extraData['video_count'] ?? 1; // 每个素材生成的视频数量
        $aimusicMode = $extraData['ai_music'] ?? false; // 控制是否使用默认音乐

        // 计算资源数量
        $materialCount = count($materialData);
        $copywritingCount = count($copywritingData);
        $clipCount = count($clipData);

        // 外层循环：遍历每个素材
        foreach ($materialData as $materialIndex => $currentMaterial) {
            // 内层循环：每个素材生成 $video_count 个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // 计算当前视频的全局索引
                $globalIndex = $materialIndex * $video_count + $videoIndex;

                // ===== 素材选择逻辑 =====
                // 当前素材就是选中的素材
                $selectedMaterial = $currentMaterial;
                $taskPic = $defaultPic;
                foreach ($selectedMaterial as $key => &$value) {
                    if (isset($value['cover'])) {
                        if ($taskPic === '') {
                            $taskPic = (string)$value['cover'];
                        }
                        unset($selectedMaterial[$key]['cover']);
                    }
                }
                unset($value);
                $taskThumbStatus = $taskPic !== '' ? 2 : 4;
                $materialJson = json_encode(is_array($selectedMaterial) ? array_values($selectedMaterial) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 顺序选择剪辑模板
                        $clipIndex = $globalIndex % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 获取剪辑模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'newsMixCutting')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;

                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    // 使用数据库默认剪辑模板
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if (count($musicData) > 0) {
                    if ($musicMode == 1) {
                        // 顺序选择音乐
                        $musicIndex = $globalIndex % count($musicData);
                        $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                        $musicStrategy = 'sequential';
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 控制是否使用默认音乐
                    if ($aimusicMode) {
                        $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }

                // ===== 内容选择逻辑（文案或音频）=====
                $taskItem = [];
                // 使用文案
                $copywritingItem = null;

                // 顺序选择文案
                $copywritingIndex = $globalIndex % $copywritingCount;
                $copywritingItem = $copywritingData[$copywritingIndex];
                $contentStrategy = 'sequential_copywriting';
                $taskDuration = self::formatBillingDuration(self::taskTextDuration($copywritingItem));
                $taskItem = [
                    'pic' => $taskPic,
                    'thumb_status' => $taskThumbStatus,
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => 1, // 文案驱动
                    'shanjian_type' => 4,
                    'user_id' => self::$uid,
                    'video_setting_id' => $settingId,
                    'anchor_id' => '',
                    'voice_id' => $params['voice'] ?? '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $copywritingItem['title'] ?? '',
                    'msg' => $copywritingItem['content'] ?? '',
                    'material' => $materialJson,
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'duration' => (int)ceil($taskDuration),
                    'extra' => json_encode($extraData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'audio_url' => '', // 文案模式下不包含音频地址
                    'create_time' => time(),
                    'update_time' => time()
                ];


                // 添加额外的调试信息
                $extra = [
                    'setting_index' => $globalIndex,
                    'material_index' => $materialIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop_type41',
                    'video_count_per_material' => $video_count,
                    'total_videos' => $materialCount * $video_count,
                    'billing_duration' => $taskDuration,
                    'selection_info' => [
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                        'content_strategy' => $contentStrategy ?? 'unknown'
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'material_index' => $materialIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];

                // 合并额外的调试信息到任务项
                if (isset($taskItem['extra'])) {
                    $existingExtra = json_decode($taskItem['extra'], true);
                    $mergedExtra = array_merge($extra, $existingExtra);
                    $taskItem['extra'] = json_encode($mergedExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $taskItem['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                // 生成视频名称
                //                $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
                //                $modeSuffix = $contentMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
                //                $videoName .= '_' . $modeSuffix;
                $taskItem['title'] = $taskItem['title'] ?? '';
                $taskItem['name'] = !empty($taskItem['title']) ? $taskItem['title'] : "新闻体混剪" . date('YmdHis');
                $taskItem['name'] = mb_substr($taskItem['name'], 0, 120, 'UTF-8');
                $taskData[] = $taskItem;
            } // 内层循环结束
        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 为Type4创建音频驱动的任务项
     * @param int $index 任务索引
     * @param array $params 请求参数
     * @param array|string $audioItem 音频数据（支持字符串格式和对象格式）
     * @param array $characterDesignData 人设数据
     * @param array $selectedMaterial 素材数据
     * @param array $musicData 音乐数据
     * @param array $clipData 剪辑数据
     * @param array $clip_template_id 剪辑模板ID数组
     * @param int $clip_template_total 剪辑模板总数
     * @param int $settingId 设置ID
     * @return array 任务项数据
     */
    private static function createTaskItemWithAudioType4(int $index, array $params, $audioItem, array $characterDesignData, array $selectedMaterial, array $musicData, array $clipData, array $clip_template_id, int $clip_template_total, int $settingId): array
    {
        $number = random_int(1, 20);
        $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
        $music_url = !empty($musicData) ? $musicData[$index % count($musicData)]['fileUrl'] ?? $music : $music;

        $clip = random_int(0, $clip_template_total);
        $clip_id = !empty($clipData) ? $clipData[$index % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip] : $clip_template_id[$clip];

        $material = json_encode(is_array($selectedMaterial) ? array_values($selectedMaterial) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 处理音频数据，支持字符串格式（含双反引号嵌套）和对象格式
        $normalizedAudio = self::normalizeAudioItem($audioItem);
        $audioUrl = $normalizedAudio['url'];
        $audioFormat = is_string($audioItem) ? 'string' : 'object';

        return [
            'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($index + 1),
            'pic' => '', // Type4可能没有anchor pic
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 2, // 音频驱动
            'user_id' => self::$uid,
            'shanjian_type' => 4,
            'video_setting_id' => $settingId,
            'anchor_id' => '', // Type4可能没有anchor_id
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => '', // 音频模式下可能没有title
            'msg' => $normalizedAudio['text'],
            'audio_url' => $audioUrl, // 音频URL
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'extra' => json_encode([
                'setting_index' => $index,
                'create_type' => 'batch',
                'audio_format' => $audioFormat,
                'timed_words' => $normalizedAudio['words'],
            ], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    /**
     * 为Type4创建文案驱动的任务项
     * @param int $index 任务索引
     * @param array $params 请求参数
     * @param array $copywritingItem 文案数据
     * @param array $characterDesignData 人设数据
     * @param array $selectedMaterial 素材数据
     * @param array $musicData 音乐数据
     * @param array $clipData 剪辑数据
     * @param array $clip_template_id 剪辑模板ID数组
     * @param int $clip_template_total 剪辑模板总数
     * @param int $settingId 设置ID
     * @return array 任务项数据
     */
    private static function createTaskItemWithCopywritingType4(int $index, array $params, array $copywritingItem, array $characterDesignData, array $selectedMaterial, array $musicData, array $clipData, array $clip_template_id, int $clip_template_total, int $settingId): array
    {
        $number = random_int(1, 20);
        $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
        $music_url = !empty($musicData) ? $musicData[$index % count($musicData)]['fileUrl'] ?? $music : $music;

        $clip = random_int(0, $clip_template_total);
        $clip_id = !empty($clipData) ? $clipData[$index % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip] : $clip_template_id[$clip];

        $material = json_encode(is_array($selectedMaterial) ? array_values($selectedMaterial) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return [
            'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($index + 1),
            'pic' => '', // Type4可能没有anchor pic
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 1, // 文案驱动
            'user_id' => self::$uid,
            'shanjian_type' => 4,
            'video_setting_id' => $settingId,
            'anchor_id' => '', // Type4可能没有anchor_id
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => $copywritingItem['title'] ?? '',
            'msg' => $copywritingItem['content'] ?? '',
            'audio_url' => '', // 文案模式下不设置audio_url
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'extra' => json_encode([
                'setting_index' => $index,
                'create_type' => 'batch'
            ], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    /**
     * 是否为 MiniMax 音色（human_voice.model_version: 10=hd, 11=turbo）
     * @param string $voiceId HumanVoice.voice_id / 人设 third_voice_id
     * @param int|null $userId 传入时优先按用户归属匹配，避免跨用户同 voice_id 误判
     */
    public static function isMinimaxVoiceId(string $voiceId, ?int $userId = null): bool
    {
        $voiceId = trim($voiceId);
        if ($voiceId === '') {
            return false;
        }

        // 1) human_voice.model_version ∈ {10,11}
        // 不强制 status=1：绑定音色可能短暂非成功态，漏检会导致误把 MiniMax id 当 speakerId
        $voiceQuery = HumanVoice::where('voice_id', $voiceId)->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $voiceQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        foreach ($voiceQuery->column('model_version') as $modelVersion) {
            if (in_array((int)$modelVersion, [10, 11], true)) {
                return true;
            }
        }

        // 2) 人设绑定 provider=minimax（兼容历史 model_version 未写对的数据）
        $providerQuery = \app\common\model\aiPersona\AiPersonaDigitalVoice::where('third_voice_id', $voiceId)
            ->where('provider', \app\common\model\aiPersona\AiPersonaDigitalVoice::PROVIDER_MINIMAX)
            ->whereNull('delete_time');
        if ($userId !== null && $userId > 0) {
            $providerQuery->where('user_id', $userId);
        }
        if ($providerQuery->value('id')) {
            return true;
        }

        // 3) voice_id 命名兜底（如 minimax_28turbo_xxx）
        if (stripos($voiceId, 'minimax') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 创建 MiniMax TTS 中间任务（手动/自动化共用）
     * @param int|string $settingId
     * @param string $minimaxVoice HumanVoice.voice_id
     * @param array $contents [['content'=>文案], ...] 或纯字符串列表
     * @param int|null $userId 自动化 cron 下 self::$uid 可能为 0，需显式传入
     */
    public static function createAudioTask($settingId, $minimaxVoice, $contents, ?int $userId = null)
    {
        $text = [];
        foreach ($contents as $content) {
            if (is_array($content)) {
                $text[] = (string)($content['content'] ?? '');
            } else {
                $text[] = (string)$content;
            }
        }
        $text = array_values(array_filter($text, static function ($item) {
            return trim((string)$item) !== '';
        }));
        if (empty($text)) {
            throw new \Exception('MiniMax音色合成文案不能为空');
        }

        $uid = $userId ?? (int)self::$uid;
        if ($uid <= 0) {
            throw new \Exception('MiniMax音色合成缺少用户ID');
        }

        $insert = [
            'user_id' => $uid,
            'shanjian_setting_id' => $settingId,
            'voice_id' => $minimaxVoice,
            'contents' => json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 0,
            'create_time' => time(),
        ];
        $model = new MinimaxShanjianTask();
        $model->save($insert);
        return $model;
    }
}
