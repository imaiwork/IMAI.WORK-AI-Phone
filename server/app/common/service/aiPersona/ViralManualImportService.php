<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\api\logic\aiPersona\PublishLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceViralManualImport;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\ToolsService;
use app\common\service\storage\Driver as StorageDriver;
use think\facade\Db;
use think\facade\Log;

/**
 * 爆款库手动导入：排队 + 00:00-03:00 解析扇出
 */
class ViralManualImportService
{
    private const WINDOW_START_HOUR = 0;
    private const WINDOW_END_HOUR = 3;
    private const VIDEO_PARSE_MAX_RETRY = 3;
    private const CLAIM_MAX_RETRY = 3;
    private const PARSED_CACHE_VERSION = 1;
    private const FAN_OUT_PAUSED = 'paused';
    private const FAN_OUT_QUOTA_WAIT = 'quota_wait';
    private const FAN_OUT_SUCCESS = 'success';
    private const FAN_OUT_PARTIAL = 'partial';
    private const FAN_OUT_FAIL = 'fail';
    private const KEYWORD_LABEL = '手动入库';
    private const REMARK_NOT_INTERESTED = '已标记不感兴趣，暂停解析';
    /** @deprecated 历史终态文案，兼容已有失败行 */
    private const REMARK_INVALID_SHARE_LINK = '无效的分享链接';
    /** 真视频笔记：终态失败，不重试 */
    private const REMARK_XHS_VIDEO_UNSUPPORTED = '暂不支持小红书视频分享链接';
    /** 分享文案无链接：终态失败，不重试 */
    private const REMARK_XHS_NO_LINK = '未从分享内容中提取到小红书链接';
    /** TikHub/结构解析失败：可重试 */
    private const REMARK_XHS_PARSE_FAIL = '小红书笔记解析失败';
    /** 原图下载失败：可重试 */
    private const REMARK_XHS_DOWNLOAD_FAIL = '小红书原图下载失败';
    private const XHS_IMAGE_DOWNLOAD_MAX_ATTEMPT = 3;
    /** 图文配额已满：回写待执行，等待下一日窗 */
    private const REMARK_QUOTA_IMAGE_FULL = '当天与次日图文配额已满，等待下一日窗再解析';
    /** 视频配额已满：回写待执行，等待下一日窗 */
    private const REMARK_QUOTA_VIDEO_FULL = '当天与次日视频配额已满，等待下一日窗再解析';
    /** 视频与图文配额均已满 */
    private const REMARK_QUOTA_ALL_FULL = '当天与次日手动解析配额已满足，无需再解析';

    /**
     * 统一写 viral_manual 通道日志（中文）
     */
    private static function log(string $message, array $context = []): void
    {
        if (!empty($context)) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('viral_manual')->write($message);
    }

    /**
     * 提交排队
     * @return array 成功返回 data；失败抛异常
     */
    public static function enqueue(int $userId, int $personaId, string $shareContent): array
    {
        $shareContent = trim($shareContent);
        self::log('【排队】开始手动导入', [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'share_content_len' => mb_strlen($shareContent),
            'share_content' => mb_substr($shareContent, 0, 500),
        ]);

        if ($shareContent === '') {
            self::log('【排队】失败：分享内容为空', ['user_id' => $userId, 'persona_id' => $personaId]);
            throw new \RuntimeException('分享内容不能为空');
        }

        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
        if ($persona->isEmpty()) {
            self::log('【排队】失败：人设不存在', ['user_id' => $userId, 'persona_id' => $personaId]);
            throw new \RuntimeException('人设不存在');
        }

        try {
            $detected = ViralSharePlatformDetector::detect($shareContent);
        } catch (\Throwable $th) {
            self::log('【排队】平台识别失败：' . $th->getMessage(), ['user_id' => $userId, 'persona_id' => $personaId]);
            throw $th;
        }
        self::log('【排队】平台识别结果', [
            'platform' => $detected['platform'],
            'platform_name' => $detected['platform_name'],
            'url' => $detected['url'],
            'supported_manual' => $detected['supported_manual'],
        ]);
        if (!$detected['supported_manual']) {
            self::log('【排队】失败：平台暂不支持手动解析', $detected);
            throw new \RuntimeException('暂不支持该平台手动解析：' . $detected['platform_name']);
        }

        $dedup = ViralTitleDedupService::isDuplicate($userId, $shareContent);
        self::log('【排队】去重检查', [
            'duplicate' => $dedup['duplicate'],
            'reason' => $dedup['reason'] ?? '',
            'matched_id' => $dedup['matched_id'] ?? 0,
            'hash' => $dedup['hash'],
            'title_normalized' => $dedup['title_normalized'],
        ]);
        if ($dedup['duplicate']) {
            $msg = $dedup['reason'] === 'similarity'
                ? '与已有爆款标题高度相似，请勿重复导入'
                : '该链接已在30天历史池中，请勿重复导入';
            self::log('【排队】失败：' . $msg);
            throw new \RuntimeException($msg);
        }

        $pending = SvDeviceViralManualImport::where('user_id', $userId)
            ->where('hash', $dedup['hash'])
            ->whereIn('status', [
                SvDeviceViralManualImport::STATUS_PENDING,
                SvDeviceViralManualImport::STATUS_PROCESSING,
            ])
            ->findOrEmpty();
        if (!$pending->isEmpty()) {
            self::log('【排队】失败：链接已在待执行队列', ['pending_id' => (int)$pending->id]);
            throw new \RuntimeException('该链接已在待执行队列中');
        }

        $window = self::resolveScheduledWindow();
        $row = SvDeviceViralManualImport::create([
            'user_id' => $userId,
            'persona_id' => $personaId,
            'share_content' => $shareContent,
            'share_url' => $detected['url'],
            'publish_platform' => $detected['platform'],
            'publish_media_type' => 0,
            'hash' => $dedup['hash'],
            'title_normalized' => $dedup['title_normalized'],
            'status' => SvDeviceViralManualImport::STATUS_PENDING,
            'is_interested' => 1,
            'retry' => 0,
            'remark' => SvDeviceViralManualImport::REMARK_QUEUED,
            'scheduled_day' => $window['scheduled_day'],
            'create_time' => time(),
            'update_time' => time(),
        ]);

        self::log('【排队】成功写入待执行表', [
            'import_id' => (int)$row->id,
            'scheduled_day' => $window['scheduled_day'],
            'window' => $window,
        ]);

        return [
            'id' => (int)$row->id,
            'persona_id' => $personaId,
            'platform' => $detected['platform'],
            'platform_name' => $detected['platform_name'],
            'publish_platform' => $detected['platform'],
            'status' => SvDeviceViralManualImport::STATUS_PENDING,
            'share_content' => $shareContent,
            'remark' => SvDeviceViralManualImport::REMARK_QUEUED,
            'scheduled_window' => [
                'start' => $window['start'],
                'end' => $window['end'],
            ],
            'scheduled_day' => $window['scheduled_day'],
        ];
    }

    public static function isInExecuteWindow(?int $now = null): bool
    {
        $now = $now ?? time();
        $hour = (int)date('G', $now);
        return $hour >= self::WINDOW_START_HOUR && $hour < self::WINDOW_END_HOUR;
    }

    /**
     * @return array{start:string,end:string,scheduled_day:string}
     */
    public static function resolveScheduledWindow(?int $now = null): array
    {
        $now = $now ?? time();
        $today = date('Y-m-d', $now);
        $todayEnd = strtotime($today . ' 03:00:00');
        if ($now < $todayEnd) {
            $day = $today;
        } else {
            $day = date('Y-m-d', strtotime($today . ' +1 day'));
        }

        return [
            'start' => $day . ' 00:00:00',
            'end' => $day . ' 03:00:00',
            'scheduled_day' => $day,
        ];
    }

    /**
     * 窗口内消费待执行队列
     * @return array{processed:int,success:int,failed:int,skipped:int,skip_reason:string}
     */
    public static function processPending(int $limit = 10): array
    {
        $stats = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skip_reason' => '',
        ];
        self::log('【调度】开始消费待执行队列', [
            'limit' => $limit,
            'now' => date('Y-m-d H:i:s'),
            'in_window' => self::isInExecuteWindow(),
        ]);
        if (!self::isInExecuteWindow()) {
            self::log('【调度】非执行窗口（仅00:00-03:00），跳过');
            $stats['skip_reason'] = 'out_of_window';
            return $stats;
        }

        $recovered = self::recoverStaleProcessing();
        if ($recovered > 0) {
            self::log('【调度】已回收超时执行中记录', ['count' => $recovered]);
        }

        $personaIds = SvDeviceViralManualImport::where('status', SvDeviceViralManualImport::STATUS_PENDING)
            ->where('is_interested', 1)
            ->group('persona_id')
            ->column('persona_id');
        if (empty($personaIds)) {
            self::log('【调度】无待执行记录，结束');
            $stats['skip_reason'] = 'empty';
            return $stats;
        }
        self::log('【调度】待处理人设列表', ['persona_ids' => array_map('intval', $personaIds)]);

        $quotaSatisfiedPersonas = 0;
        foreach ($personaIds as $personaId) {
            $personaId = (int)$personaId;
            if ($personaId <= 0) {
                continue;
            }
            self::log('【调度】开始处理人设', ['persona_id' => $personaId, '剩余limit' => $limit]);
            $result = self::processPersonaPending($personaId, $limit);
            $stats['processed'] += $result['processed'];
            $stats['success'] += $result['success'];
            $stats['failed'] += $result['failed'];
            $stats['skipped'] += $result['skipped'];
            if (($result['skip_reason'] ?? '') === 'quota_satisfied') {
                $quotaSatisfiedPersonas++;
            }
            self::log('【调度】人设处理结束', [
                'persona_id' => $personaId,
                'result' => $result,
                '累计stats' => $stats,
            ]);
            $limit -= $result['processed'];
            if ($limit <= 0) {
                self::log('【调度】已达本轮处理上限，结束');
                break;
            }
        }

        if (
            $stats['processed'] === 0
            && $stats['success'] === 0
            && $stats['failed'] === 0
            && $quotaSatisfiedPersonas > 0
        ) {
            $stats['skip_reason'] = 'quota_satisfied';
        }

        self::log('【调度】本轮消费完成', $stats);
        return $stats;
    }

    /**
     * @return array{processed:int,success:int,failed:int,skipped:int,skip_reason:string}
     */
    private static function processPersonaPending(int $personaId, int $limit): array
    {
        $stats = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skip_reason' => '',
        ];
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            self::log('【人设】不存在，标记待执行失败', ['persona_id' => $personaId]);
            self::failPendingByPersona($personaId, 'IP人设不存在');
            $stats['failed']++;
            return $stats;
        }

        $videoPublishCount = self::getManualPublishCount($persona, AiPersona::PUBLISH_MEDIA_TYPE_VIDEO);
        $imagePublishCount = self::getManualPublishCount($persona, AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT);
        self::log('【人设】分媒体发布数 P', [
            'persona_id' => $personaId,
            'user_id' => (int)$persona->user_id,
            'video_P' => $videoPublishCount,
            'image_text_P' => $imagePublishCount,
        ]);
        $devices = SvDevice::where('user_id', (int)$persona->user_id)
            ->where('persona_id', $personaId)
            ->order('id', 'asc')
            ->select();
        if ($devices->isEmpty()) {
            self::log('【人设】未绑定设备，本窗跳过（保留待执行）', ['persona_id' => $personaId]);
            $stats['skipped']++;
            return $stats;
        }

        $finalized = self::finalizeCoveredPendingImports($personaId, $devices, $limit);
        $stats['processed'] += $finalized['processed'];
        $stats['success'] += $finalized['success'];
        $limit -= $finalized['processed'];
        if ($limit <= 0) {
            return $stats;
        }

        if ($videoPublishCount <= 0 && $imagePublishCount <= 0) {
            self::log('【人设】视频与图文发布数均为0，跳过本窗解析', ['persona_id' => $personaId]);
            $stats['skipped']++;
            return $stats;
        }

        $today = date('Y-m-d');
        $nextDay = date('Y-m-d', strtotime('+1 day'));
        $remainMapVideo = self::buildRemainMap(
            $devices,
            $personaId,
            $today,
            $nextDay,
            AiPersona::PUBLISH_MEDIA_TYPE_VIDEO,
            $videoPublishCount
        );
        $remainMapImage = self::buildRemainMap(
            $devices,
            $personaId,
            $today,
            $nextDay,
            AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
            $imagePublishCount
        );

        if (!self::hasAnyRemaining($remainMapVideo) && !self::hasAnyRemaining($remainMapImage)) {
            self::log('【配额】当天与次日手动解析配额已满足，无需再解析', [
                'persona_id' => $personaId,
                'remark' => self::REMARK_QUOTA_ALL_FULL,
                'remain_map_video' => $remainMapVideo,
                'remain_map_image' => $remainMapImage,
            ]);
            $stats['skipped']++;
            $stats['skip_reason'] = 'quota_satisfied';
            return $stats;
        }

        $skipImportIds = [];
        while ($limit > 0 && (self::hasAnyRemaining($remainMapVideo) || self::hasAnyRemaining($remainMapImage))) {
            $row = self::claimNextPending($personaId, $skipImportIds, $remainMapVideo, $remainMapImage);
            if ($row === null) {
                self::log('【认领】无人设待执行记录可认领', [
                    'persona_id' => $personaId,
                    'skip_import_ids' => $skipImportIds,
                    'video_quota' => self::hasAnyRemaining($remainMapVideo),
                    'image_quota' => self::hasAnyRemaining($remainMapImage),
                ]);
                break;
            }
            self::log('【认领】成功认领待执行记录', [
                'import_id' => (int)$row->id,
                'persona_id' => $personaId,
                'platform' => (int)$row->publish_platform,
                'share_url' => (string)$row->share_url,
                'retry' => (int)$row->retry,
                'share_content' => mb_substr((string)$row->share_content, 0, 300),
            ]);

            if (self::pauseClaimIfUninterested($row, '解析前')) {
                $stats['skipped']++;
                continue;
            }

            if (self::shouldSkipParseDueToQuota($row, $remainMapVideo, $remainMapImage)) {
                $quotaRemark = self::resolveQuotaRemarkByPlatform((int)$row->publish_platform);
                self::releaseClaimToPending($row, $quotaRemark);
                $skipImportIds[] = (int)$row->id;
                self::log('【配额】解析前预检跳过，未调用第三方', [
                    'import_id' => (int)$row->id,
                    'platform' => (int)$row->publish_platform,
                    'remark' => $quotaRemark,
                    'remain_map_video' => $remainMapVideo,
                    'remain_map_image' => $remainMapImage,
                ]);
                $stats['skipped']++;
                $stats['skip_reason'] = 'quota_satisfied';
                continue;
            }

            $stats['processed']++;
            $limit--;

            try {
                if ($devices->isEmpty()) {
                    self::markImportFail($row, '人设未绑定设备');
                    self::log('【解析】失败：人设未绑定设备', ['import_id' => (int)$row->id]);
                    $stats['failed']++;
                    continue;
                }

                $parsed = self::getCachedParsedPayload($row);
                if ($parsed === null) {
                    $parsed = self::parseOnce($row, $persona);
                    $parsed = self::persistParsedPayloadAndCharge($row, $parsed);
                } else {
                    self::log('【解析】复用已缓存解析结果', [
                        'import_id' => (int)$row->id,
                        'publish_media_type' => (int)($parsed['publish_media_type'] ?? 0),
                    ]);
                }
                $mediaType = (int)($parsed['publish_media_type'] ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO);
                $mediaLabel = $mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT ? '图文' : '视频';
                self::log('【解析】完成，准备扇出', [
                    'import_id' => (int)$row->id,
                    'publish_media_type' => $mediaType,
                    'media_label' => $mediaLabel,
                    'video_duration' => $parsed['video_duration'] ?? 0,
                    'original_text_len' => mb_strlen((string)($parsed['original_text'] ?? '')),
                    'original_images_count' => count($parsed['original_images'] ?? []),
                    'title' => $parsed['title'] ?? '',
                ]);

                if (self::pauseClaimIfUninterested($row, '扇出前')) {
                    $stats['skipped']++;
                    continue;
                }

                if ($mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
                    if (!self::hasAnyRemaining($remainMapImage)) {
                        self::releaseClaimToPending($row, self::REMARK_QUOTA_IMAGE_FULL);
                        $skipImportIds[] = (int)$row->id;
                        self::log('【配额】该媒体类型已满，回写待执行并跳过本轮', [
                            'import_id' => (int)$row->id,
                            'media_type' => $mediaType,
                            'media_label' => $mediaLabel,
                            'remark' => self::REMARK_QUOTA_IMAGE_FULL,
                            'skip_import_ids' => $skipImportIds,
                            'remain_map_video' => $remainMapVideo,
                            'remain_map_image' => $remainMapImage,
                        ]);
                        $stats['skipped']++;
                        $stats['skip_reason'] = 'quota_satisfied';
                        continue;
                    }
                    $fanOut = self::fanOutToDevices($row, $persona, $parsed, $devices, $remainMapImage, $today, $nextDay);
                } else {
                    if (!self::hasAnyRemaining($remainMapVideo)) {
                        self::releaseClaimToPending($row, self::REMARK_QUOTA_VIDEO_FULL);
                        $skipImportIds[] = (int)$row->id;
                        self::log('【配额】该媒体类型已满，回写待执行并跳过本轮', [
                            'import_id' => (int)$row->id,
                            'media_type' => $mediaType,
                            'media_label' => $mediaLabel,
                            'remark' => self::REMARK_QUOTA_VIDEO_FULL,
                            'skip_import_ids' => $skipImportIds,
                            'remain_map_video' => $remainMapVideo,
                            'remain_map_image' => $remainMapImage,
                        ]);
                        $stats['skipped']++;
                        $stats['skip_reason'] = 'quota_satisfied';
                        continue;
                    }
                    $fanOut = self::fanOutToDevices($row, $persona, $parsed, $devices, $remainMapVideo, $today, $nextDay);
                }

                $fanOutOutcome = self::resolveFanOutOutcome($fanOut);
                if ($fanOutOutcome === self::FAN_OUT_PAUSED) {
                    if (!self::pauseClaimIfUninterested($row, '扇出中', $fanOut['details'])) {
                        self::releaseProcessingClaimToPending($row, '任务状态已变化，等待继续解析', $fanOut['details']);
                    }
                    $stats['skipped']++;
                    continue;
                }
                if ($fanOutOutcome === self::FAN_OUT_QUOTA_WAIT) {
                    $quotaRemark = $mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT
                        ? self::REMARK_QUOTA_IMAGE_FULL
                        : self::REMARK_QUOTA_VIDEO_FULL;
                    if (self::pauseClaimIfUninterested($row, '配额回写前', $fanOut['details'])) {
                        $stats['skipped']++;
                        continue;
                    }
                    self::releaseProcessingClaimToPending($row, $quotaRemark, $fanOut['details']);
                    $skipImportIds[] = (int)$row->id;
                    $stats['skipped']++;
                    $stats['skip_reason'] = 'quota_satisfied';
                    continue;
                }

                if ($fanOutOutcome === self::FAN_OUT_FAIL) {
                    $status = SvDeviceViralManualImport::STATUS_FAIL;
                    $remark = '解析成功但未写入任何设备：' . ($fanOut['message'] ?: '无可用配额');
                    if (!self::completeProcessingClaim($row, $status, $remark, $mediaType, $fanOut['details'])) {
                        if (!self::pauseClaimIfUninterested($row, '失败状态回写前', $fanOut['details'])) {
                            self::releaseProcessingClaimToPending($row, '任务状态已变化，等待继续解析', $fanOut['details']);
                        }
                        $stats['skipped']++;
                        continue;
                    }
                    self::log('【扇出】失败：未写入任何设备', [
                        'import_id' => (int)$row->id,
                        'media_type' => $mediaType,
                        'media_label' => $mediaLabel,
                        'message' => $fanOut['message'],
                        'details' => $fanOut['details'],
                        'remain_map_video' => $remainMapVideo,
                        'remain_map_image' => $remainMapImage,
                    ]);
                    $stats['failed']++;
                    continue;
                }

                $status = $fanOutOutcome === self::FAN_OUT_PARTIAL
                    ? SvDeviceViralManualImport::STATUS_PARTIAL
                    : SvDeviceViralManualImport::STATUS_SUCCESS;
                $remark = $fanOutOutcome === self::FAN_OUT_PARTIAL
                    ? '部分设备入库成功'
                    : '解析入库成功';
                if (!self::completeProcessingClaim($row, $status, $remark, $mediaType, $fanOut['details'])) {
                    if (!self::pauseClaimIfUninterested($row, '完成状态回写前', $fanOut['details'])) {
                        self::releaseProcessingClaimToPending($row, '任务状态已变化，等待继续解析', $fanOut['details']);
                    }
                    $stats['skipped']++;
                    continue;
                }
                self::log('【扇出】完成', [
                    'import_id' => (int)$row->id,
                    'media_type' => $mediaType,
                    'media_label' => $mediaLabel,
                    'status' => (int)$row->status,
                    'success' => $fanOut['success'],
                    'failed' => $fanOut['failed'],
                    'details' => $fanOut['details'],
                    'remain_map_video' => $remainMapVideo,
                    'remain_map_image' => $remainMapImage,
                ]);
                $stats['success']++;
            } catch (\Throwable $th) {
                $errMsg = $th->getMessage();
                self::log('【解析】执行异常 import_id=' . $row->id . '：' . $errMsg, [
                    'trace' => $th->getTraceAsString(),
                ]);
                if (self::pauseClaimIfUninterested($row, '异常处理前')) {
                    $stats['skipped']++;
                    continue;
                }
                if (self::isXhsTerminalFailRemark($errMsg)) {
                    self::markImportFail($row, $errMsg);
                    self::log('【解析】小红书终态失败，不重试', [
                        'import_id' => (int)$row->id,
                        'retry' => (int)$row->retry,
                        'status' => (int)$row->status,
                        'remark' => (string)$row->remark,
                    ]);
                } else {
                    self::markImportRetryOrFail($row, $errMsg);
                    self::log('【解析】已标记重试/失败', [
                        'import_id' => (int)$row->id,
                        'retry' => (int)$row->retry,
                        'status' => (int)$row->status,
                        'remark' => (string)$row->remark,
                    ]);
                }
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * 解析前按平台预判：对应媒体配额已满则跳过第三方解析。
     * 小红书手动入库成功路径仅为图文，图文配额满即跳过（勿等视频也满）。
     */
    private static function shouldSkipParseDueToQuota(
        SvDeviceViralManualImport $row,
        array $remainMapVideo,
        array $remainMapImage
    ): bool {
        $platform = (int)$row->publish_platform;
        // 抖音/视频号/快手：仅视频链路
        if (in_array($platform, [
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_SPH,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ], true)) {
            return !self::hasAnyRemaining($remainMapVideo);
        }
        // 小红书：仅图文链路（真视频笔记会终态失败）
        if ($platform === DeviceEnum::ACCOUNT_TYPE_XHS) {
            return !self::hasAnyRemaining($remainMapImage);
        }
        return !self::hasAnyRemaining($remainMapVideo) && !self::hasAnyRemaining($remainMapImage);
    }

    /**
     * 按平台返回配额已满回写文案。
     */
    private static function resolveQuotaRemarkByPlatform(int $platform): string
    {
        if ($platform === DeviceEnum::ACCOUNT_TYPE_XHS) {
            return self::REMARK_QUOTA_IMAGE_FULL;
        }
        if (in_array($platform, [
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_SPH,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ], true)) {
            return self::REMARK_QUOTA_VIDEO_FULL;
        }
        return self::REMARK_QUOTA_ALL_FULL;
    }

    /**
     * 对齐 ViralRewriterHandler::getPublishTimeCount：图文仅统计含小红书的时段。
     */
    private static function getManualPublishCount(AiPersona $persona, int $mediaType): int
    {
        $schedules = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
            ->where('scene', 5)
            ->field('id,platform')
            ->select();
        if ($schedules->isEmpty()) {
            return 0;
        }

        $disabledIds = array_map('intval', AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
            ->where('template_id', $persona->workflow_template_id)
            ->where('user_id', (int)$persona->user_id)
            ->where('scene', 5)
            ->where('status', 0)
            ->column('schedule_id'));

        $isImageText = $mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
        $count = 0;
        foreach ($schedules as $schedule) {
            if (in_array((int)$schedule->id, $disabledIds, true)) {
                continue;
            }
            if ($isImageText && !self::scheduleContainsPlatform($schedule->platform, AiPersona::PUBLISH_PLATFORM_XHS)) {
                continue;
            }
            $count++;
        }
        return $count;
    }

    private static function scheduleContainsPlatform(mixed $platforms, int $platform): bool
    {
        if ($platform <= 0) {
            return false;
        }
        if (is_string($platforms)) {
            $decoded = json_decode($platforms, true);
            $platforms = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($platforms)) {
            return false;
        }
        foreach ($platforms as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (is_array($item) && (int)($item['account_type'] ?? 0) === $platform) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, array{today:int,next:int}>
     */
    private static function buildRemainMap(mixed $devices, int $personaId, string $today, string $nextDay, int $mediaType, int $publishCount): array
    {
        $remainMap = [];
        $mediaLabel = $mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT ? '图文' : '视频';
        foreach ($devices as $device) {
            $code = (string)$device->device_code;
            $todayOcc = self::getManualOccupied($code, $personaId, $today, $mediaType);
            $nextOcc = self::getManualOccupied($code, $personaId, $nextDay, $mediaType);
            $remainMap[$code] = [
                'today' => max(0, $publishCount - $todayOcc),
                'next' => max(0, $publishCount - $nextOcc),
            ];
            self::log('【配额】' . $mediaLabel, [
                'device_code' => $code,
                'media_type' => $mediaType,
                'today' => $today,
                'next_day' => $nextDay,
                'P' => $publishCount,
                'today_occupied' => $todayOcc,
                'next_occupied' => $nextOcc,
                'today_remaining' => $remainMap[$code]['today'],
                'next_remaining' => $remainMap[$code]['next'],
            ]);
        }
        return $remainMap;
    }

    private static function hasAnyRemaining(array $remainMap): bool
    {
        foreach ($remainMap as $item) {
            if ((int)($item['today'] ?? 0) > 0 || (int)($item['next'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 对齐 ViralRewriterHandler::getOccupiedQuotaCount：按媒体类型分账；图文再筛小红书平台。
     * 手动配额仍仅统计 auto_type=0。
     */
    private static function getManualOccupied(string $deviceCode, int $personaId, string $day, int $mediaType): int
    {
        $query = SvDeviceViralRecord::where('device_code', $deviceCode)
            ->where('persona_id', $personaId)
            ->where('auto_type', 0)
            ->where('day', $day)
            ->where('publish_media_type', $mediaType)
            ->whereIn('status', [0, 3, 4, 6])
            ->where('is_interested', 1);
        if ($mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
            $query->where('publish_platform', AiPersona::PUBLISH_PLATFORM_XHS);
        }
        return (int)$query->count();
    }

    /**
     * 按配额余量决定认领平台约束。
     * @return array{mode:string,platforms:int[]} mode=any|in|none
     */
    private static function resolveClaimPlatformConstraint(array $remainMapVideo, array $remainMapImage): array
    {
        $videoOk = self::hasAnyRemaining($remainMapVideo);
        $imageOk = self::hasAnyRemaining($remainMapImage);
        $videoPlatforms = [
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_SPH,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ];
        if ($videoOk && !$imageOk) {
            return ['mode' => 'in', 'platforms' => $videoPlatforms];
        }
        if ($imageOk && !$videoOk) {
            return ['mode' => 'in', 'platforms' => [DeviceEnum::ACCOUNT_TYPE_XHS]];
        }
        if (!$videoOk && !$imageOk) {
            return ['mode' => 'none', 'platforms' => []];
        }
        return ['mode' => 'any', 'platforms' => []];
    }

    /**
     * @param int[] $skipImportIds 本轮因配额已满回写待执行的 id，避免 FIFO 死循环
     * @param array<string, array{today:int,next:int}> $remainMapVideo
     * @param array<string, array{today:int,next:int}> $remainMapImage
     */
    private static function claimNextPending(
        int $personaId,
        array $skipImportIds = [],
        array $remainMapVideo = [],
        array $remainMapImage = []
    ): ?SvDeviceViralManualImport {
        $constraint = self::resolveClaimPlatformConstraint($remainMapVideo, $remainMapImage);
        if ($constraint['mode'] === 'none') {
            return null;
        }

        $excludedIds = array_values(array_unique(array_map('intval', $skipImportIds)));
        for ($attempt = 1; $attempt <= self::CLAIM_MAX_RETRY; $attempt++) {
            $query = SvDeviceViralManualImport::where('persona_id', $personaId)
                ->where('status', SvDeviceViralManualImport::STATUS_PENDING)
                ->where('is_interested', 1)
                ->order('id', 'asc');
            if (!empty($excludedIds)) {
                $query->whereNotIn('id', $excludedIds);
            }
            if ($constraint['mode'] === 'in') {
                $query->whereIn('publish_platform', $constraint['platforms']);
            }

            $row = $query->find();
            if (!$row) {
                return null;
            }

            $affected = SvDeviceViralManualImport::where('id', (int)$row->id)
                ->where('status', SvDeviceViralManualImport::STATUS_PENDING)
                ->where('is_interested', 1)
                ->update([
                    'status' => SvDeviceViralManualImport::STATUS_PROCESSING,
                    'remark' => SvDeviceViralManualImport::REMARK_PROCESSING,
                    'started_at' => time(),
                    'update_time' => time(),
                ]);
            if ($affected > 0) {
                $claimed = SvDeviceViralManualImport::where('id', (int)$row->id)->find();
                return $claimed ?: null;
            }

            $excludedIds[] = (int)$row->id;
            self::log('【认领】候选记录状态已变化，继续尝试下一条', [
                'import_id' => (int)$row->id,
                'attempt' => $attempt,
            ]);
        }

        return null;
    }

    private static function pauseClaimIfUninterested(
        SvDeviceViralManualImport $row,
        string $stage,
        ?array $resultDetails = null
    ): bool
    {
        $update = [
            'status' => SvDeviceViralManualImport::STATUS_PENDING,
            'remark' => self::REMARK_NOT_INTERESTED,
            'started_at' => 0,
            'finished_at' => 0,
            'update_time' => time(),
        ];
        if ($resultDetails !== null) {
            $update['result_json'] = json_encode($resultDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $affected = SvDeviceViralManualImport::where('id', (int)$row->id)
            ->where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->where('is_interested', 0)
            ->update($update);
        if ($affected <= 0) {
            return false;
        }

        $row->status = SvDeviceViralManualImport::STATUS_PENDING;
        $row->remark = self::REMARK_NOT_INTERESTED;
        $row->started_at = 0;
        $row->finished_at = 0;
        if ($resultDetails !== null) {
            $row->result_json = $resultDetails;
        }
        self::log('【解析】任务已标记不感兴趣，暂停处理', [
            'import_id' => (int)$row->id,
            'stage' => $stage,
        ]);
        return true;
    }

    private static function releaseProcessingClaimToPending(
        SvDeviceViralManualImport $row,
        string $remark,
        array $resultDetails = []
    ): void {
        $affected = SvDeviceViralManualImport::where('id', (int)$row->id)
            ->where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->update([
                'status' => SvDeviceViralManualImport::STATUS_PENDING,
                'remark' => $remark,
                'result_json' => json_encode($resultDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'started_at' => 0,
                'finished_at' => 0,
                'update_time' => time(),
            ]);
        if ($affected > 0) {
            $row->status = SvDeviceViralManualImport::STATUS_PENDING;
            $row->remark = $remark;
            $row->result_json = $resultDetails;
            $row->started_at = 0;
            $row->finished_at = 0;
        }
    }

    private static function completeProcessingClaim(
        SvDeviceViralManualImport $row,
        int $status,
        string $remark,
        int $mediaType,
        array $resultDetails
    ): bool {
        $now = time();
        $affected = SvDeviceViralManualImport::where('id', (int)$row->id)
            ->where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->where('is_interested', 1)
            ->update([
                'status' => $status,
                'publish_media_type' => $mediaType,
                'remark' => $remark,
                'result_json' => json_encode($resultDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'finished_at' => $now,
                'update_time' => $now,
            ]);
        if ($affected <= 0) {
            return false;
        }

        $row->status = $status;
        $row->publish_media_type = $mediaType;
        $row->remark = $remark;
        $row->result_json = $resultDetails;
        $row->finished_at = $now;
        $row->update_time = $now;
        return true;
    }

    /**
     * 配额不足时回写待执行，不增加 retry。
     */
    private static function releaseClaimToPending(SvDeviceViralManualImport $row, string $remark): void
    {
        $affected = SvDeviceViralManualImport::where('id', (int)$row->id)
            ->where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->where('is_interested', 1)
            ->update([
                'status' => SvDeviceViralManualImport::STATUS_PENDING,
                'remark' => $remark,
                'started_at' => 0,
                'finished_at' => 0,
                'update_time' => time(),
            ]);
        if ($affected > 0) {
            $row->status = SvDeviceViralManualImport::STATUS_PENDING;
            $row->remark = $remark;
            $row->started_at = 0;
            $row->finished_at = 0;
            return;
        }

        self::pauseClaimIfUninterested($row, '配额回写前');
    }

    private static function recoverStaleProcessing(): int
    {
        $deadline = time() - SvDeviceViralManualImport::STALE_SECONDS;
        return (int)SvDeviceViralManualImport::where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->where('started_at', '>', 0)
            ->where('started_at', '<', $deadline)
            ->update([
                'status' => SvDeviceViralManualImport::STATUS_PENDING,
                'remark' => '解析超时已回收，等待重试',
                'update_time' => time(),
            ]);
    }

    private static function failPendingByPersona(int $personaId, string $remark): void
    {
        SvDeviceViralManualImport::where('persona_id', $personaId)
            ->where('status', SvDeviceViralManualImport::STATUS_PENDING)
            ->where('is_interested', 1)
            ->update([
                'status' => SvDeviceViralManualImport::STATUS_FAIL,
                'remark' => $remark,
                'finished_at' => time(),
                'update_time' => time(),
            ]);
    }

    private static function markImportFail(SvDeviceViralManualImport $row, string $remark): void
    {
        $row->status = SvDeviceViralManualImport::STATUS_FAIL;
        $row->remark = $remark;
        $row->finished_at = time();
        $row->update_time = time();
        $row->save();
    }

    private static function markImportRetryOrFail(SvDeviceViralManualImport $row, string $remark): void
    {
        $row->retry = (int)$row->retry + 1;
        $row->update_time = time();
        if ((int)$row->retry >= SvDeviceViralManualImport::MAX_RETRY) {
            $row->status = SvDeviceViralManualImport::STATUS_FAIL;
            $row->remark = '解析失败已达重试上限：' . $remark;
            $row->finished_at = time();
        } else {
            $row->status = SvDeviceViralManualImport::STATUS_PENDING;
            $row->remark = '解析失败待重试：' . $remark;
            $row->started_at = 0;
        }
        $row->save();
    }

    private static function getCachedParsedPayload(SvDeviceViralManualImport $row): ?array
    {
        $payload = $row->parsed_payload;
        if (
            !is_array($payload)
            || (int)($payload['version'] ?? 0) !== self::PARSED_CACHE_VERSION
            || empty($payload['complete'])
            || empty($payload['charged'])
            || !is_array($payload['data'] ?? null)
        ) {
            return null;
        }

        return self::normalizeParsedPayload($payload['data']);
    }

    private static function persistParsedPayloadAndCharge(
        SvDeviceViralManualImport $row,
        array $parsed
    ): array {
        Db::startTrans();
        try {
            $locked = SvDeviceViralManualImport::where('id', (int)$row->id)->lock(true)->findOrEmpty();
            if ($locked->isEmpty()) {
                throw new \RuntimeException('手动导入任务不存在');
            }

            $cached = self::getCachedParsedPayload($locked);
            if ($cached !== null) {
                Db::commit();
                $row->parsed_payload = $locked->parsed_payload;
                return $cached;
            }

            $normalized = self::normalizeParsedPayload($parsed);
            self::chargeParsedPayload($locked, $parsed, $normalized);

            $payload = [
                'version' => self::PARSED_CACHE_VERSION,
                'complete' => 1,
                'charged' => 1,
                'charged_at' => time(),
                'data' => $normalized,
            ];
            $locked->publish_media_type = (int)$normalized['publish_media_type'];
            $locked->parsed_payload = $payload;
            $locked->update_time = time();
            $locked->save();
            Db::commit();

            $row->publish_media_type = (int)$normalized['publish_media_type'];
            $row->parsed_payload = $payload;
            return $normalized;
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    private static function chargeParsedPayload(
        SvDeviceViralManualImport $row,
        array $parsed,
        array $normalized
    ): void {
        if ((int)$normalized['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
            $unit = (float)($parsed['_charge_unit'] ?? 0);
            self::chargeImageExtract((int)$row->user_id, (int)$row->id, (string)$row->share_url, $unit);
            return;
        }

        self::deductParseFee(
            (int)$row->user_id,
            (int)$normalized['video_duration'],
            'manual_viral_' . $row->id
        );
    }

    private static function normalizeParsedPayload(array $parsed): array
    {
        return [
            'publish_media_type' => (int)($parsed['publish_media_type'] ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO),
            'original_text' => (string)($parsed['original_text'] ?? ''),
            'video_duration' => (int)($parsed['video_duration'] ?? 0),
            'copywriting' => is_array($parsed['copywriting'] ?? null) ? $parsed['copywriting'] : [],
            'original_images' => is_array($parsed['original_images'] ?? null) ? $parsed['original_images'] : [],
            'title' => (string)($parsed['title'] ?? ''),
            'likes' => (int)($parsed['likes'] ?? 0),
            'comments' => (int)($parsed['comments'] ?? 0),
            'tikhub_raw' => is_array($parsed['tikhub_raw'] ?? null) ? $parsed['tikhub_raw'] : [],
            'copywriting_type' => (int)($parsed['copywriting_type'] ?? 1),
        ];
    }

    /**
     * 解析阶段只产出公共素材；仿写文案在 fanOutToDevices 按设备生成。
     *
     * @return array{
     *   publish_media_type:int,
     *   original_text:string,
     *   video_duration:int,
     *   copywriting:array,
     *   original_images:array,
     *   title:string,
     *   likes:int,
     *   comments:int,
     *   tikhub_raw:array,
     *   copywriting_type:int
     * }
     */
    private static function parseOnce(SvDeviceViralManualImport $row, AiPersona $persona): array
    {
        $platform = (int)$row->publish_platform;
        $shareContent = (string)$row->share_content;
        self::log('【解析】开始 parseOnce', [
            'import_id' => (int)$row->id,
            'platform' => $platform,
            'platform_name' => DeviceEnum::getAccountTypeDesc($platform),
            'share_url' => (string)$row->share_url,
        ]);

        if ($platform === DeviceEnum::ACCOUNT_TYPE_XHS) {
            self::log('【解析】小红书链路：先尝试 TikHub 图文提取', ['import_id' => (int)$row->id]);
            $note = self::tryExtractXhsNote($shareContent);
            $noteType = strtolower(trim((string)($note['type'] ?? '')));
            $imageCount = count($note['images'] ?? []);
            self::log('【解析】小红书 TikHub 提取结果', [
                'import_id' => (int)$row->id,
                'has_note' => !empty($note),
                'note_type' => $noteType,
                'image_count' => $imageCount,
                'title' => $note['title'] ?? '',
                'desc_len' => mb_strlen((string)($note['desc'] ?? '')),
                'error' => (string)($note['error'] ?? ''),
            ]);
            // 真视频（无可用多图）：终态拒绝；解析/下载失败走可重试文案
            if ($noteType === 'video' && $imageCount <= 0) {
                $msg = (string)($note['error'] ?? self::REMARK_XHS_VIDEO_UNSUPPORTED);
                self::log('【解析】判定为小红书视频笔记，直接失败', [
                    'import_id' => (int)$row->id,
                    'note_type' => $noteType,
                    'image_count' => $imageCount,
                    'msg' => $msg,
                ]);
                throw new \RuntimeException($msg !== '' ? $msg : self::REMARK_XHS_VIDEO_UNSUPPORTED);
            }
            if ($imageCount <= 0) {
                $msg = (string)($note['error'] ?? self::REMARK_XHS_PARSE_FAIL);
                self::log('【解析】小红书无可用图，按可重试失败处理', [
                    'import_id' => (int)$row->id,
                    'note_type' => $noteType,
                    'msg' => $msg,
                ]);
                throw new \RuntimeException($msg !== '' ? $msg : self::REMARK_XHS_PARSE_FAIL);
            }
            self::log('【解析】判定为图文笔记，走图文解析', ['import_id' => (int)$row->id]);
            return self::buildImageTextParsed($row, $persona, $note);
        }

        self::log('【解析】走视频解析链路', ['import_id' => (int)$row->id]);
        return self::buildVideoParsed($row, $persona, $shareContent);
    }

    private static function buildVideoParsed(SvDeviceViralManualImport $row, AiPersona $persona, string $shareContent): array
    {
        self::log('【视频】校验算力', ['import_id' => (int)$row->id, 'user_id' => (int)$row->user_id]);
        TokenLogService::checkToken((int)$row->user_id, 'video_imitation_copywriting_parse');

        $audioText = '';
        $duration = 0;
        $lastError = '';
        for ($i = 0; $i < self::VIDEO_PARSE_MAX_RETRY; $i++) {
            $attempt = $i + 1;
            try {
                self::log('【视频】video2text 第' . $attempt . '次请求', [
                    'import_id' => (int)$row->id,
                    'share_content' => mb_substr($shareContent, 0, 300),
                ]);
                $response = ToolsService::VideoImitation()->video2text($shareContent);
                self::log('【视频】video2text 第' . $attempt . '次响应', [
                    'import_id' => (int)$row->id,
                    'code' => $response['code'] ?? null,
                    'msg' => $response['msg'] ?? ($response['message'] ?? ''),
                    'duration' => $response['data']['duration'] ?? null,
                    'audio_text_len' => mb_strlen(trim((string)($response['data']['audio_text'] ?? ''))),
                    'raw' => $response,
                ]);
                if (isset($response['code']) && (int)$response['code'] === 10000) {
                    $resData = $response['data'] ?? [];
                    $audioText = trim((string)($resData['audio_text'] ?? ''));
                    $duration = (int)($resData['duration'] ?? 0);
                    if ($audioText === '') {
                        self::log('【视频】口播为空，降级 MCP videoImitation', ['import_id' => (int)$row->id]);
                        $res = ToolsService::Copywriting()->videoImitation([
                            'input' => ['prompt' => $shareContent],
                            'version' => 'v2',
                        ]);
                        self::log('【视频】MCP 降级响应', [
                            'import_id' => (int)$row->id,
                            'code' => $res['code'] ?? null,
                            'raw' => $res,
                        ]);
                        if (isset($res['code']) && (int)$res['code'] === 10000) {
                            $messageJson = $res['data']['message'] ?? '';
                            $parsedMsg = json_decode((string)$messageJson, true);
                            if (is_array($parsedMsg)) {
                                $audioText = trim((string)($parsedMsg['original_text'] ?? ''));
                            }
                        }
                    }
                    break;
                }
                $lastError = (string)($response['msg'] ?? '视频解析失败');
            } catch (\Throwable $th) {
                $lastError = $th->getMessage();
                self::log('【视频】video2text 第' . $attempt . '次异常：' . $th->getMessage(), [
                    'import_id' => (int)$row->id,
                ]);
            }
        }

        self::log('【视频】解析汇总', [
            'import_id' => (int)$row->id,
            'audio_text_len' => mb_strlen($audioText),
            'duration' => $duration,
            'last_error' => $lastError,
            'audio_preview' => mb_substr($audioText, 0, 200),
        ]);

        if ($audioText === '' || mb_strlen($audioText) < 30) {
            throw new \RuntimeException($lastError !== '' ? ('视频解析失败：' . $lastError) : '未识别到有效口播文案');
        }

        self::log('【视频】开始意图检验', ['import_id' => (int)$row->id, 'persona_id' => (int)$persona->id]);
        if (!self::checkIntentRelevance($audioText, $persona)) {
            self::log('【视频】意图检验未通过：内容与人设严重偏离', ['import_id' => (int)$row->id]);
            throw new \RuntimeException('视频内容与人设严重偏离');
        }
        self::log('【视频】意图检验通过', ['import_id' => (int)$row->id]);

        self::log('【视频】素材解析完成，仿写延后至按设备扇出', [
            'import_id' => (int)$row->id,
            'audio_text_len' => mb_strlen($audioText),
        ]);

        return [
            'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_VIDEO,
            'original_text' => $audioText,
            'video_duration' => $duration,
            'copywriting' => [],
            'original_images' => [],
            'title' => '',
            'likes' => 0,
            'comments' => 0,
            'tikhub_raw' => [],
            'copywriting_type' => 1,
        ];
    }

    private static function buildImageTextParsed(SvDeviceViralManualImport $row, AiPersona $persona, array $note): array
    {
        self::log('【图文】校验信息抓取算力', [
            'import_id' => (int)$row->id,
            'user_id' => (int)$row->user_id,
            'image_count' => count($note['images'] ?? []),
        ]);
        $unit = (float)TokenLogService::checkToken((int)$row->user_id, 'images_explosion_rewrite');
        self::log('【图文】信息抓取计费校验完成，仿写延后至按设备扇出', [
            'import_id' => (int)$row->id,
            'unit' => $unit,
            'original_images' => $note['images'] ?? [],
        ]);

        // 解析阶段仅校验人设规则存在；正式仿写在扇出时按设备执行
        if (self::resolvePersonaRule($persona) === null) {
            throw new \RuntimeException('IP人设规则不存在');
        }

        return [
            'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
            'original_text' => (string)($note['desc'] ?? ''),
            'video_duration' => 0,
            'copywriting' => [],
            'original_images' => $note['images'] ?? [],
            'title' => (string)($note['title'] ?? ''),
            'likes' => (int)($note['likes'] ?? 0),
            'comments' => (int)($note['comments'] ?? 0),
            'tikhub_raw' => $note['tikhub_raw'] ?? [],
            'copywriting_type' => 1,
            '_charge_unit' => $unit,
        ];
    }

    /**
     * @param array $remainMap by-ref
     * @return array{target:int,covered:int,success:int,failed:int,existing:int,skipped_quota:int,paused:bool,message:string,details:array}
     */
    private static function fanOutToDevices(
        SvDeviceViralManualImport $row,
        AiPersona $persona,
        array $parsed,
        mixed $devices,
        array &$remainMap,
        string $today,
        string $nextDay
    ): array {
        $details = [];
        $success = 0;
        $failed = 0;
        $existing = 0;
        $paused = false;
        $skippedQuota = 0;
        $target = count($devices);
        $generationTypes = self::resolveGenerationTypes($persona);
        self::log('【扇出】开始按设备写入', [
            'import_id' => (int)$row->id,
            'persona_id' => (int)$persona->id,
            'device_count' => count($devices),
            'generation_types' => $generationTypes,
            'today' => $today,
            'next_day' => $nextDay,
            'remain_map' => $remainMap,
            'publish_media_type' => $parsed['publish_media_type'] ?? null,
        ]);

        foreach ($devices as $device) {
            $deviceCode = (string)$device->device_code;
            if (!self::isImportClaimActive((int)$row->id)) {
                $paused = true;
                break;
            }
            $existingRecord = self::findManualDeviceRecord((int)$row->id, $deviceCode);
            if ($existingRecord !== null) {
                $existing++;
                $details[] = [
                    'device_code' => $deviceCode,
                    'record_id' => (int)$existingRecord->id,
                    'existing' => true,
                    'ok' => true,
                ];
                continue;
            }

            $remain = $remainMap[$deviceCode] ?? ['today' => 0, 'next' => 0];
            $day = '';
            if ((int)$remain['today'] > 0) {
                $day = $today;
                $remainMap[$deviceCode]['today']--;
            } elseif ((int)$remain['next'] > 0) {
                $day = $nextDay;
                $remainMap[$deviceCode]['next']--;
            } else {
                $skippedQuota++;
                self::log('【扇出】设备双日配额已满，跳过', [
                    'import_id' => (int)$row->id,
                    'device_code' => $deviceCode,
                    'remain' => $remain,
                ]);
                continue;
            }

            self::log('【扇出】写入设备', [
                'import_id' => (int)$row->id,
                'device_code' => $deviceCode,
                'day' => $day,
                'remain_after_reserve' => $remainMap[$deviceCode],
            ]);

            try {
                Db::startTrans();
                $lockedImport = SvDeviceViralManualImport::where('id', (int)$row->id)->lock(true)->findOrEmpty();
                if (
                    $lockedImport->isEmpty()
                    || (int)$lockedImport->status !== SvDeviceViralManualImport::STATUS_PROCESSING
                    || (int)$lockedImport->is_interested !== 1
                ) {
                    Db::rollback();
                    self::restoreRemainingReservation($remainMap, $deviceCode, $day, $today, $nextDay);
                    $paused = true;
                    break;
                }

                $existingRecord = self::findManualDeviceRecord((int)$row->id, $deviceCode);
                if ($existingRecord !== null) {
                    Db::commit();
                    self::restoreRemainingReservation($remainMap, $deviceCode, $day, $today, $nextDay);
                    $existing++;
                    $details[] = [
                        'device_code' => $deviceCode,
                        'day' => $day,
                        'record_id' => (int)$existingRecord->id,
                        'existing' => true,
                        'ok' => true,
                    ];
                    continue;
                }

                $mediaType = (int)$parsed['publish_media_type'];
                $isImageText = $mediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
                self::log('【扇出】开始按设备生成仿写文案', [
                    'import_id' => (int)$row->id,
                    'device_code' => $deviceCode,
                    'publish_media_type' => $mediaType,
                ]);
                if ($isImageText) {
                    $copywriting = self::generateImageTextCopywriting(
                        $persona,
                        (int)$row->user_id,
                        (string)$parsed['original_text'],
                        (string)($parsed['title'] ?? ''),
                        $deviceCode
                    );
                } else {
                    $copywriting = self::generateVideoCopywriting(
                        (string)$parsed['original_text'],
                        $persona,
                        (int)$row->user_id,
                        $deviceCode
                    );
                }
                self::log('【扇出】设备仿写文案生成完成', [
                    'import_id' => (int)$row->id,
                    'device_code' => $deviceCode,
                    'copywriting' => $copywriting,
                ]);

                $record = SvDeviceViralRecord::create([
                    'user_id' => (int)$row->user_id,
                    'viral_id' => 0,
                    'viral_account_id' => 0,
                    'manual_import_id' => (int)$row->id,
                    'auto_type' => 0,
                    'device_code' => $deviceCode,
                    'account' => '',
                    'nickname' => '',
                    'persona_id' => (int)$row->persona_id,
                    'keyword' => self::KEYWORD_LABEL,
                    'content' => (string)$row->share_content,
                    'title_normalized' => (string)$row->title_normalized,
                    'generation_types' => $generationTypes,
                    'publish_platform' => (int)$row->publish_platform,
                    'publish_media_type' => $mediaType,
                    'video_duration' => (int)$parsed['video_duration'],
                    'original_text' => (string)$parsed['original_text'],
                    'copywriting' => $copywriting,
                    'copywriting_type' => (int)$parsed['copywriting_type'],
                    'day' => $day,
                    'status' => 4,
                    'remark' => $isImageText
                        ? '图文文案生成成功，等待图片改写'
                        : '文案生成成功',
                    'hash' => (string)$row->hash,
                    'original_images' => $parsed['original_images'] ?? [],
                    'rewritten_images' => [],
                    'image_rewrite_status' => $isImageText
                        ? SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT
                        : SvDeviceViralRecord::IMAGE_REWRITE_STATUS_NONE,
                    'tikhub_raw' => $parsed['tikhub_raw'] ?? [],
                    'likes' => (string)($parsed['likes'] ?? 0),
                    'comments' => (string)($parsed['comments'] ?? 0),
                    'is_interested' => 1,
                    'use_time' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                $copywritingPayload = is_string($copywriting)
                    ? $copywriting
                    : json_encode($copywriting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $copywritingId = AiPersonaSynthesisCopywriting::create([
                    'user_id' => (int)$row->user_id,
                    'device_code' => $deviceCode,
                    'persona_id' => (int)$row->persona_id,
                    'sv_device_viral_record_id' => (int)$record->id,
                    'publish_media_type' => $mediaType,
                    'copywriting' => $copywritingPayload,
                    'status' => AiPersonaSynthesisCopywriting::STATUS_SUCCESS,
                    'use_state' => AiPersonaSynthesisCopywriting::USE_STATE_UNUSED,
                    'day' => $day,
                    'create_time' => time(),
                ]);
                Db::commit();
                $success++;
                $details[] = [
                    'device_code' => $deviceCode,
                    'day' => $day,
                    'record_id' => (int)$record->id,
                    'copywriting_id' => (int)($copywritingId->id ?? 0),
                    'ok' => true,
                ];
                self::log('【扇出】设备写入成功', [
                    'import_id' => (int)$row->id,
                    'device_code' => $deviceCode,
                    'day' => $day,
                    'record_id' => (int)$record->id,
                    'copywriting_id' => (int)($copywritingId->id ?? 0),
                ]);
            } catch (\Throwable $th) {
                Db::rollback();
                self::restoreRemainingReservation($remainMap, $deviceCode, $day, $today, $nextDay);
                $failed++;
                $details[] = ['device_code' => $deviceCode, 'day' => $day, 'ok' => false, 'msg' => $th->getMessage()];
                self::log('【扇出】设备写入失败：' . $th->getMessage(), [
                    'import_id' => (int)$row->id,
                    'device_code' => $deviceCode,
                    'day' => $day,
                    'trace' => $th->getTraceAsString(),
                ]);
            }
        }

        $covered = $success + $existing;

        self::log('【扇出】汇总', [
            'import_id' => (int)$row->id,
            'target' => $target,
            'covered' => $covered,
            'success' => $success,
            'failed' => $failed,
            'existing' => $existing,
            'paused' => $paused,
            'skipped_quota' => $skippedQuota,
            'details' => $details,
        ]);

        return [
            'target' => $target,
            'covered' => $covered,
            'success' => $success,
            'failed' => $failed,
            'existing' => $existing,
            'skipped_quota' => $skippedQuota,
            'paused' => $paused,
            'message' => $paused
                ? self::REMARK_NOT_INTERESTED
                : (($success + $existing) > 0 ? '' : '没有可写入的设备配额'),
            'details' => $details,
        ];
    }

    /**
     * @param array{target?:int,covered?:int,failed?:int,skipped_quota?:int,paused?:bool} $fanOut
     */
    private static function resolveFanOutOutcome(array $fanOut): string
    {
        if (!empty($fanOut['paused'])) {
            return self::FAN_OUT_PAUSED;
        }

        $target = max(0, (int)($fanOut['target'] ?? 0));
        $covered = max(0, (int)($fanOut['covered'] ?? 0));
        $failed = max(0, (int)($fanOut['failed'] ?? 0));
        $skippedQuota = max(0, (int)($fanOut['skipped_quota'] ?? 0));
        if ($target > 0 && $covered >= $target) {
            return self::FAN_OUT_SUCCESS;
        }
        if ($skippedQuota > 0) {
            return self::FAN_OUT_QUOTA_WAIT;
        }
        if ($covered > 0 && $failed > 0) {
            return self::FAN_OUT_PARTIAL;
        }
        return self::FAN_OUT_FAIL;
    }

    /**
     * 进程可能在所有设备事务提交后、任务状态回写前退出；配额判断前补做收尾。
     *
     * @return array{processed:int,success:int}
     */
    private static function finalizeCoveredPendingImports(int $personaId, mixed $devices, int $limit): array
    {
        $stats = ['processed' => 0, 'success' => 0];
        if ($limit <= 0) {
            return $stats;
        }

        $deviceCodes = [];
        foreach ($devices as $device) {
            $deviceCode = (string)$device->device_code;
            if ($deviceCode !== '') {
                $deviceCodes[$deviceCode] = true;
            }
        }
        $deviceCodes = array_keys($deviceCodes);
        if (empty($deviceCodes)) {
            return $stats;
        }

        $lastId = 0;
        $batchSize = max(50, min(200, $limit * 5));
        while ($stats['processed'] < $limit) {
            $rows = SvDeviceViralManualImport::where('persona_id', $personaId)
                ->where('status', SvDeviceViralManualImport::STATUS_PENDING)
                ->where('is_interested', 1)
                ->where('id', '>', $lastId)
                ->whereNotNull('parsed_payload')
                ->where('parsed_payload', '<>', '')
                ->order('id', 'asc')
                ->limit($batchSize)
                ->select();
            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $pending) {
                $lastId = (int)$pending->id;
                $parsed = self::getCachedParsedPayload($pending);
                if ($parsed === null) {
                    continue;
                }

                $recordMap = [];
                $records = SvDeviceViralRecord::where('manual_import_id', (int)$pending->id)
                    ->whereIn('device_code', $deviceCodes)
                    ->order('id', 'asc')
                    ->select();
                foreach ($records as $record) {
                    $deviceCode = (string)$record->device_code;
                    if ($deviceCode !== '' && !isset($recordMap[$deviceCode])) {
                        $recordMap[$deviceCode] = $record;
                    }
                }
                if (count($recordMap) < count($deviceCodes)) {
                    continue;
                }

                $details = [];
                foreach ($deviceCodes as $deviceCode) {
                    if (!isset($recordMap[$deviceCode])) {
                        continue 2;
                    }
                    $record = $recordMap[$deviceCode];
                    $details[] = [
                        'device_code' => $deviceCode,
                        'day' => (string)$record->day,
                        'record_id' => (int)$record->id,
                        'existing' => true,
                        'ok' => true,
                    ];
                }

                $now = time();
                $affected = SvDeviceViralManualImport::where('id', (int)$pending->id)
                    ->where('status', SvDeviceViralManualImport::STATUS_PENDING)
                    ->where('is_interested', 1)
                    ->update([
                        'status' => SvDeviceViralManualImport::STATUS_SUCCESS,
                        'publish_media_type' => (int)$parsed['publish_media_type'],
                        'remark' => '解析入库成功',
                        'result_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'finished_at' => $now,
                        'update_time' => $now,
                    ]);
                if ($affected <= 0) {
                    continue;
                }

                $stats['processed']++;
                $stats['success']++;
                self::log('【收尾】设备已全部覆盖，恢复任务成功状态', [
                    'import_id' => (int)$pending->id,
                    'persona_id' => $personaId,
                    'target' => count($deviceCodes),
                    'details' => $details,
                ]);
                if ($stats['processed'] >= $limit) {
                    break 2;
                }
            }
        }

        return $stats;
    }

    private static function isImportClaimActive(int $importId): bool
    {
        return SvDeviceViralManualImport::where('id', $importId)
            ->where('status', SvDeviceViralManualImport::STATUS_PROCESSING)
            ->where('is_interested', 1)
            ->count() > 0;
    }

    private static function findManualDeviceRecord(int $importId, string $deviceCode): ?SvDeviceViralRecord
    {
        $record = SvDeviceViralRecord::where('manual_import_id', $importId)
            ->where('device_code', $deviceCode)
            ->find();
        return $record ?: null;
    }

    private static function restoreRemainingReservation(
        array &$remainMap,
        string $deviceCode,
        string $day,
        string $today,
        string $nextDay
    ): void {
        if ($day === $today) {
            $remainMap[$deviceCode]['today']++;
        } elseif ($day === $nextDay) {
            $remainMap[$deviceCode]['next']++;
        }
    }

    private static function resolveGenerationTypes(AiPersona $persona): array
    {
        $config = AiPersonaSynthesisConfig::where('persona_id', (int)$persona->id)->order('id', 'desc')->findOrEmpty();
        if ($config->isEmpty()) {
            return [1];
        }
        $types = $config->generation_types ?? [];
        return is_array($types) ? $types : [1];
    }

    private static function resolvePersonaRule(AiPersona $persona)
    {
        if ((int)$persona->persona_type === 1) {
            return $persona->individual;
        }
        if ((int)$persona->persona_type === 2) {
            return $persona->enterprise;
        }
        if ((int)$persona->persona_type === 3) {
            return $persona->local;
        }
        return null;
    }

    private static function checkIntentRelevance(string $audioText, AiPersona $persona): bool
    {
        try {
            $rule = self::resolvePersonaRule($persona);
            if ($rule === null) {
                self::log('【意图】人设规则为空，跳过检验视为通过', ['persona_id' => (int)$persona->id]);
                return true;
            }
            $request = [
                'keywords' => $audioText,
                'persona' => $rule->getClueContent($persona),
            ];
            self::log('【意图】发起检验请求', [
                'persona_id' => (int)$persona->id,
                'audio_text_len' => mb_strlen($audioText),
                'audio_preview' => mb_substr($audioText, 0, 200),
                'persona_clue_len' => mb_strlen((string)$request['persona']),
            ]);
            $response = ToolsService::Coze()->checkIntentRelevance($request);
            self::log('【意图】检验响应', [
                'persona_id' => (int)$persona->id,
                'code' => $response['code'] ?? null,
                'msg' => $response['msg'] ?? ($response['message'] ?? ''),
                'result' => $response['data']['content']['result'] ?? null,
                'raw_data' => $response['data'] ?? null,
            ]);
            if ((int)($response['code'] ?? 0) !== 10000) {
                self::log('【意图】接口返回非成功码，判定未通过', ['persona_id' => (int)$persona->id]);
                return false;
            }
            $result = $response['data'] ?? [];
            // result=1 表示严重偏离
            $pass = (int)($result['content']['result'] ?? 0) !== 1;
            self::log('【意图】判定结果', [
                'persona_id' => (int)$persona->id,
                'pass' => $pass,
                'result' => $result['content']['result'] ?? null,
            ]);
            return $pass;
        } catch (\Throwable $th) {
            self::log('【意图】检验异常，放行继续：' . $th->getMessage(), [
                'persona_id' => (int)$persona->id,
                'trace' => $th->getTraceAsString(),
            ]);
            return true;
        }
    }

    private static function generateVideoCopywriting(
        string $audioText,
        AiPersona $persona,
        int $userId,
        string $deviceCode = ''
    ): array {
        $rule = self::resolvePersonaRule($persona);
        if ($rule === null) {
            throw new \RuntimeException('IP人设规则不存在');
        }
        $productContent = "我的IP人设产品内容：\n主营业务/产品：{$persona['main_business']}\n目标客户与痛点：{$persona['target_pain_points']}\n差异化优势与行为引导：{$persona['conversion_hook']}";
        $promptContent = "我的IP人设内容是：\n" . $rule->getClueContent($persona) . "\n\n" . $productContent . "\n\n视频文案：\n{$audioText}";
        self::log('【仿写】开始生成视频文案', [
            'user_id' => $userId,
            'persona_id' => (int)$persona->id,
            'device_code' => $deviceCode,
            'prompt_len' => mb_strlen($promptContent),
            'prompt_preview' => mb_substr($promptContent, 0, 400),
        ]);
        $imitationResult = AutoDeviceSettingLogic::copywriting(['keywords' => $promptContent], $userId, 5);
        self::log('【仿写】生成结果', [
            'user_id' => $userId,
            'persona_id' => (int)$persona->id,
            'device_code' => $deviceCode,
            'result' => $imitationResult,
        ]);
        $content = $imitationResult['content'] ?? [];
        return is_array($content) ? $content : ['rewritten_text' => (string)$content];
    }

    /**
     * 图文发布文案：按设备独立调用，避免多设备复用同一份结果。
     */
    private static function generateImageTextCopywriting(
        AiPersona $persona,
        int $userId,
        string $originalText,
        string $fallbackTitle = '',
        string $deviceCode = ''
    ): array {
        $rule = self::resolvePersonaRule($persona);
        if ($rule === null) {
            throw new \RuntimeException('IP人设规则不存在');
        }

        $taskId = generate_unique_task_id();
        $keywords = (string)$rule->getClueContent($persona);
        $originalText = trim($originalText);
        if ($originalText !== '') {
            $keywords .= "\n\n原笔记正文：\n" . $originalText;
        }
        self::log('【图文仿写】开始生成发布文案', [
            'user_id' => $userId,
            'persona_id' => (int)$persona->id,
            'device_code' => $deviceCode,
            'task_id' => $taskId,
            'fallback_title' => $fallbackTitle,
            'original_text_len' => mb_strlen($originalText),
        ]);
        $response = PublishLogic::resolveContentPublishCopywriting(
            $persona,
            $keywords,
            $taskId,
            $userId,
            DeviceEnum::ACCOUNT_TYPE_XHS,
            true
        );
        self::log('【图文仿写】发布文案生成响应', [
            'user_id' => $userId,
            'persona_id' => (int)$persona->id,
            'device_code' => $deviceCode,
            'code' => $response['code'] ?? null,
            'msg' => $response['msg'] ?? ($response['message'] ?? ''),
            'data' => $response['data'] ?? null,
        ]);
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new \RuntimeException((string)($response['msg'] ?? $response['message'] ?? '内容发布文案生成失败'));
        }
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        if (!empty($data['library_empty'])) {
            throw new \RuntimeException((string)($data['library_message'] ?? '发布文案库暂无可用文案'));
        }

        $content = trim((string)($data['content'] ?? $originalText));
        return [
            'title' => trim((string)($data['title'] ?? $fallbackTitle)),
            'content' => $content,
            'tag' => trim((string)($data['tag'] ?? '')),
            'rewritten_text' => $content,
        ];
    }

    private static function deductParseFee(int $userId, int $duration, string $taskId): void
    {
        $unit = TokenLogService::getTypeScore('video_imitation_copywriting_parse');
        $minutes = max(1, (int)ceil($duration / 60));
        $deductPoint = $unit * $minutes;
        self::log('【扣费】视频文案提取', [
            'user_id' => $userId,
            'task_id' => $taskId,
            'duration' => $duration,
            'minutes' => $minutes,
            'unit' => $unit,
            'deduct_point' => $deductPoint,
        ]);
        if ($deductPoint <= 0) {
            self::log('【扣费】金额为0，跳过', ['user_id' => $userId, 'task_id' => $taskId]);
            return;
        }
        User::userTokensChange($userId, $deductPoint);
        AccountLogLogic::recordUserTokensLog(
            true,
            $userId,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
            $deductPoint,
            $taskId,
            [
                '扣费项目' => '视频文案提取',
                '算力单价' => $unit,
                '原视频时长' => $duration . '秒',
                '实际消耗算力' => $deductPoint,
                '场景' => '手动入库',
            ]
        );
        self::log('【扣费】视频文案提取完成', ['user_id' => $userId, 'task_id' => $taskId, 'deduct_point' => $deductPoint]);
    }

    private static function chargeImageExtract(int $userId, int $importId, string $shareUrl, float $unit): void
    {
        if ($unit <= 0) {
            self::log('【扣费】图文信息抓取金额为0，跳过', ['user_id' => $userId, 'import_id' => $importId]);
            return;
        }
        $taskId = 'viral_manual_info_extract_' . $importId;
        $user = User::where('id', $userId)->findOrEmpty();
        if ($user->isEmpty()) {
            throw new \RuntimeException('用户查询失败');
        }
        // 企业空间成员看企业钱包，勿用个人 tokens 预检
        $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
        self::log('【扣费】图文信息抓取前余额检查', [
            'user_id' => $userId,
            'import_id' => $importId,
            'tokens' => $spendable,
            'unit' => $unit,
            'share_url' => $shareUrl,
        ]);
        if ($spendable < $unit) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \RuntimeException($msg);
        }
        User::userTokensChange($userId, $unit);
        AccountLogLogic::recordUserTokensLog(
            true,
            $userId,
            AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
            $unit,
            $taskId,
            [
                '扣费项目' => '图文爆款仿写信息抓取',
                '算力单价' => $unit,
                '实际消耗算力' => $unit,
                'import_id' => $importId,
                'share_url' => $shareUrl,
                '场景' => '手动入库',
            ]
        );
        self::log('【扣费】图文信息抓取完成', [
            'user_id' => $userId,
            'import_id' => $importId,
            'task_id' => $taskId,
            'unit' => $unit,
        ]);
    }

    private static function tryExtractXhsNote(string $shareContent): array
    {
        try {
            $shareUrl = self::extractXhsShareUrl($shareContent);
            self::log('【小红书】提取分享链接', [
                'share_url' => $shareUrl,
                'share_content_preview' => mb_substr($shareContent, 0, 300),
            ]);
            if ($shareUrl === '') {
                throw new \RuntimeException(self::REMARK_XHS_NO_LINK);
            }

            // TikHub share_text：优先传短链 URL（完整分享文案常导致 40000）；失败再回退完整文案
            $shareCandidates = array_values(array_unique(array_filter([
                $shareUrl,
                trim($shareContent) !== $shareUrl ? trim($shareContent) : '',
            ])));
            $response = [];
            $lastApiMsg = '';
            foreach ($shareCandidates as $idx => $shareText) {
                self::log('【小红书】请求 TikHub 笔记详情', [
                    'attempt' => $idx + 1,
                    'share_url' => $shareUrl,
                    'share_text_len' => mb_strlen($shareText),
                    'share_text_preview' => mb_substr($shareText, 0, 120),
                ]);
                $response = ToolsService::TikHub()->getXhsImageNoteDetail($shareText);
                $code = $response['code'] ?? $response['status_code'] ?? 0;
                $lastApiMsg = trim((string)($response['msg'] ?? $response['message'] ?? ''));
                $hasData = !empty($response['data']);
                $ok = in_array((int)$code, [0, 1, 200, 10000], true) || $hasData;
                if ($ok && $hasData) {
                    break;
                }
                self::log('【小红书】TikHub 本次入参失败，尝试下一候选', [
                    'code' => $code,
                    'msg' => $lastApiMsg,
                    'has_data' => $hasData,
                ]);
                $response = [];
            }
            if (empty($response) || empty($response['data'])) {
                throw new \RuntimeException(
                    self::REMARK_XHS_PARSE_FAIL . '：' . ($lastApiMsg !== '' ? $lastApiMsg : '接口无数据')
                );
            }
            $code = $response['code'] ?? $response['status_code'] ?? 0;
            $payload = self::resolveXhsNotePayload($response);
            $noteType = strtolower(trim((string)($payload['type'] ?? $payload['note_type'] ?? '')));
            $imagesList = self::resolveXhsImagesList($payload, $response);
            self::log('【小红书】TikHub 响应摘要', [
                'code' => $code,
                'has_data' => !empty($response['data']),
                'note_type' => $noteType,
                'images_list_count' => is_array($imagesList) ? count($imagesList) : 0,
                'payload_keys' => is_array($payload) ? array_slice(array_keys($payload), 0, 30) : [],
                'msg' => $response['msg'] ?? ($response['message'] ?? ''),
                'raw' => $response,
            ]);
            if (!in_array((int)$code, [0, 1, 200, 10000], true) && empty($response['data'])) {
                $apiMsg = trim((string)($response['msg'] ?? $response['message'] ?? ''));
                throw new \RuntimeException(
                    self::REMARK_XHS_PARSE_FAIL . '：' . ($apiMsg !== '' ? $apiMsg : '接口无数据')
                );
            }

            $imagesListCount = is_array($imagesList) ? count($imagesList) : 0;
            // 真视频常带 1 张封面；仅当 type=video 且无「多图」时终态拒绝
            if ($noteType === 'video' && $imagesListCount <= 1) {
                self::log('【小红书】TikHub 判定为视频笔记', [
                    'share_url' => $shareUrl,
                    'images_list_count' => $imagesListCount,
                ]);
                return [
                    'type' => 'video',
                    'images' => [],
                    'title' => trim((string)($payload['title'] ?? $payload['display_title'] ?? '')),
                    'desc' => trim((string)($payload['desc'] ?? '')),
                    'error' => self::REMARK_XHS_VIDEO_UNSUPPORTED,
                    'tikhub_raw' => $response,
                ];
            }

            if (!is_array($imagesList) || $imagesListCount <= 0) {
                self::log('【小红书】无图片列表', ['note_type' => $noteType]);
                throw new \RuntimeException(self::REMARK_XHS_PARSE_FAIL . '：无图片列表');
            }

            $images = [];
            foreach ($imagesList as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $downloaded = false;
                foreach (self::buildXhsImageDownloadCandidates($item) as $url) {
                    $stored = self::downloadImageToLocal($url);
                    if ($stored !== '') {
                        $images[] = $stored;
                        $downloaded = true;
                        self::log('【小红书】图片下载成功', [
                            'index' => $idx,
                            'stored' => $stored,
                            'url' => mb_substr($url, 0, 200),
                        ]);
                        break;
                    }
                    self::log('【小红书】图片下载失败，尝试下一候选', [
                        'index' => $idx,
                        'url' => mb_substr($url, 0, 200),
                    ]);
                }
                if (!$downloaded) {
                    self::log('【小红书】该图片所有候选 URL 均下载失败', ['index' => $idx]);
                }
            }
            $images = array_values(array_unique($images));
            if (empty($images)) {
                self::log('【小红书】图片全部下载失败');
                throw new \RuntimeException(self::REMARK_XHS_DOWNLOAD_FAIL);
            }

            // type=video 但多图下载成功：按图文误标恢复
            if ($noteType === 'video') {
                self::log('【小红书】type=video 但多图可用，按图文继续', [
                    'image_count' => count($images),
                ]);
                $noteType = 'normal';
            }

            $title = '';
            $desc = '';
            foreach (['title', 'note_title', 'display_title'] as $k) {
                if (!empty($payload[$k]) && is_scalar($payload[$k])) {
                    $title = trim((string)$payload[$k]);
                    break;
                }
            }
            foreach (['desc', 'description', 'content', 'note_desc', 'text'] as $k) {
                if (!empty($payload[$k]) && is_scalar($payload[$k])) {
                    $desc = trim((string)$payload[$k]);
                    break;
                }
            }

            $note = [
                'type' => $noteType !== '' ? $noteType : 'normal',
                'title' => $title,
                'desc' => $desc,
                'images' => $images,
                'likes' => (int)($payload['liked_count'] ?? $payload['likes'] ?? 0),
                'comments' => (int)($payload['comment_count'] ?? $payload['comments'] ?? 0),
                'tikhub_raw' => $response,
            ];
            self::log('【小红书】笔记提取成功', [
                'type' => $note['type'],
                'title' => $title,
                'desc_len' => mb_strlen($desc),
                'desc_preview' => mb_substr($desc, 0, 200),
                'image_count' => count($images),
                'images' => $images,
                'likes' => $note['likes'],
                'comments' => $note['comments'],
            ]);
            return $note;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $th) {
            self::log('【小红书】提取异常：' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            throw new \RuntimeException(self::REMARK_XHS_PARSE_FAIL . '：' . $th->getMessage(), 0, $th);
        }
    }

    /**
     * 小红书终态失败（不重试）
     */
    private static function isXhsTerminalFailRemark(string $remark): bool
    {
        return in_array($remark, [
            self::REMARK_INVALID_SHARE_LINK,
            self::REMARK_XHS_VIDEO_UNSUPPORTED,
            self::REMARK_XHS_NO_LINK,
        ], true);
    }

    private static function extractXhsShareUrl(string $shareContent): string
    {
        $shareContent = trim($shareContent);
        if ($shareContent === '') {
            return '';
        }
        try {
            $detected = ViralSharePlatformDetector::detect($shareContent);
            if ((int)$detected['platform'] === DeviceEnum::ACCOUNT_TYPE_XHS) {
                return (string)$detected['url'];
            }
        } catch (\Throwable $th) {
            // 回落正则
        }
        if (!preg_match('/https?:\/\/(?:www\.)?(?:xiaohongshu\.com|xhslink\.com|xhslink\.cn|xhsurl\.com)\/[^\s]+/iu', $shareContent, $matches)) {
            return '';
        }
        return rtrim(html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
    }

    /**
     * 兼容多种 TikHub 笔记结构，优先返回带 images_list 的 note
     */
    private static function resolveXhsNotePayload(array $response): array
    {
        $candidates = [
            $response['data']['data'][0]['note_list'][0] ?? null,
            $response['data']['data']['note_list'][0] ?? null,
            $response['data']['data']['note_card'] ?? null,
            $response['data']['data']['note'] ?? null,
            $response['data']['note_list'][0] ?? null,
            $response['data']['note_card'] ?? null,
            $response['data']['note'] ?? null,
            $response['data']['items'][0]['note_card'] ?? null,
            $response['data']['item'] ?? null,
            $response['data']['data'] ?? null,
            $response['data'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate) || empty($candidate)) {
                continue;
            }
            $imagesList = self::pickXhsImagesListFromArray($candidate);
            if (!empty($imagesList)) {
                return $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return $candidate;
            }
        }
        return [];
    }

    private static function resolveXhsImagesList(array $payload, array $response): array
    {
        $fromPayload = self::pickXhsImagesListFromArray($payload);
        if (!empty($fromPayload)) {
            return $fromPayload;
        }
        $fallback = $response['data']['data'][0]['note_list'][0]['images_list'] ?? [];
        return is_array($fallback) ? $fallback : [];
    }

    private static function pickXhsImagesListFromArray(array $data): array
    {
        foreach (['images_list', 'image_list', 'images', 'imageList', 'pictures', 'pics'] as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        return [];
    }

    /**
     * @return list<string>
     */
    private static function buildXhsImageDownloadCandidates(array $item): array
    {
        $candidates = [];
        foreach (['original', 'url_size_large', 'url_8k', 'url', 'url_default', 'url_pre', 'origin_url'] as $key) {
            if (empty($item[$key]) || !is_scalar($item[$key])) {
                continue;
            }
            $url = self::normalizeXhsImageUrl((string)$item[$key]);
            if ($url !== '' && !in_array($url, $candidates, true)) {
                $candidates[] = $url;
            }
        }
        return $candidates;
    }

    private static function normalizeXhsImageUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = rtrim($url, " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
        return preg_match('/^https?:\/\//i', $url) ? $url : '';
    }

    private static function downloadImageToLocal(string $url): string
    {
        $url = self::normalizeXhsImageUrl($url);
        if ($url === '') {
            return '';
        }

        for ($attempt = 1; $attempt <= self::XHS_IMAGE_DOWNLOAD_MAX_ATTEMPT; $attempt++) {
            $stored = self::downloadImageToLocalOnce($url);
            if ($stored !== '') {
                if ($attempt > 1) {
                    self::log('【小红书】原图下载重试成功', [
                        'attempt' => $attempt,
                        'url' => mb_substr($url, 0, 200),
                        'stored' => $stored,
                    ]);
                }
                return $stored;
            }
            if ($attempt < self::XHS_IMAGE_DOWNLOAD_MAX_ATTEMPT) {
                usleep(500000 * $attempt);
            }
        }
        self::log('【小红书】原图下载全部重试失败', ['url' => mb_substr($url, 0, 200)]);
        return '';
    }

    private static function downloadImageToLocalOnce(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                // 优先 jpeg/png，避免 CDN 返回 GD 难解码的 avif/webp
                'Accept: image/jpeg,image/png,image/jpg,image/*,*/*;q=0.8',
                'Referer: https://www.xiaohongshu.com/',
            ],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($errno !== 0 || $statusCode < 200 || $statusCode >= 300 || !is_string($body) || $body === '') {
            self::log('【小红书】原图下载 HTTP 失败', [
                'status' => $statusCode,
                'errno' => $errno,
                'error' => $error,
                'content_type' => $contentType,
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            self::log('【小红书】原图保存失败：缺少 GD 扩展');
            return '';
        }
        $image = @imagecreatefromstring($body);
        if ($image === false) {
            self::log('【小红书】原图 GD 解码失败', [
                'content_type' => $contentType,
                'body_len' => strlen($body),
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }
        $date = date('Ymd');
        $directory = public_path() . 'uploads' . DIRECTORY_SEPARATOR . 'rewrite' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $date;
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            imagedestroy($image);
            self::log('【小红书】原图目录创建失败：' . $th->getMessage());
            return '';
        }
        $filename = date('YmdHis') . md5($url . microtime(true) . mt_rand()) . '.png';
        $absolutePath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $saved = imagepng($image, $absolutePath);
        imagedestroy($image);
        if (!$saved || !is_file($absolutePath) || filesize($absolutePath) <= 0) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            self::log('【小红书】原图 PNG 写入失败', ['path' => $absolutePath]);
            return '';
        }
        FileService::ensureWritableFile($absolutePath);
        $relativeUri = 'uploads/rewrite/images/' . $date . '/' . $filename;

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return $relativeUri;
        }

        try {
            self::uploadLocalOriginalImageToOss($absolutePath, $relativeUri, $storageDefault);
        } catch (\Throwable $th) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            self::log('【小红书】原图上传 OSS 失败', [
                'path' => $relativeUri,
                'error' => $th->getMessage(),
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }

        if (is_file($absolutePath) && !@unlink($absolutePath)) {
            self::log('【小红书】原图上传 OSS 成功但本地删除失败', [
                'path' => $relativeUri,
                'absolute' => $absolutePath,
            ]);
        } else {
            self::log('【小红书】原图上传 OSS 成功并已删除本地', [
                'path' => $relativeUri,
            ]);
        }

        return $relativeUri;
    }

    /**
     * 将本地原图上传到当前非 local 存储，相对路径保持不变。
     */
    private static function uploadLocalOriginalImageToOss(
        string $absolutePath,
        string $relativeUri,
        string $storageDefault
    ): void {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        $filename = basename($relativeUri);
        $saveDir = dirname($relativeUri);
        if ($saveDir === '.' || $saveDir === '\\') {
            $saveDir = '';
        }

        $storageConfig = [
            'default' => $storageDefault,
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ];
        $storageDriver = new StorageDriver($storageConfig);
        $storageDriver->setUploadFileByFileName($absolutePath, $filename);
        if (!$storageDriver->upload($saveDir)) {
            throw new \RuntimeException($storageDriver->getError() ?: '上传失败');
        }
    }
}
