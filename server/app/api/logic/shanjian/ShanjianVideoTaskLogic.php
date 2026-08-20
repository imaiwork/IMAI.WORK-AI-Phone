<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\device\TaskLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\exception\MaterialNotReadyException;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\ModelConfig;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\service\FileService;
use app\common\service\MaterialReadinessService;
use app\common\service\ShanjianQueueService;
use app\common\service\TeamBillingService;
use app\common\service\TeamContextService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * ShanjianVideoTaskLogic
 * 闪剪视频任务逻辑处理
 */
class ShanjianVideoTaskLogic extends ApiLogic
{

    const COPYWRITING_CREATE = 'copywritingCreate'; //文案创作
    const SHANJIAN_VIDEO = 'shanjianVideo';
    const SHANJIAN_REALMAN_BROADCAST = 'shanjianRealmanBroadcast';
    const SHANJIAN_BROADCAST_MIXCUT = 'shanjianBroadcastMixcut';
    const SHANJIAN_NEWS_MIXCUT = 'shanjianNewsMixcut';
    const SHANJIAN_VIRTUALMAN = 'shanjianVirtualman';
    /** 成片自动下载最大尝试次数（独立于合成 tries） */
    private const AUTO_DOWNLOAD_MAX_TRIES = 3;
    /** 下载中超时秒数：超时后回收为失败，供补偿重试（需大于下载 curl 超时 600s） */
    private const DOWNLOAD_STUCK_TIMEOUT = 900;
    /** 自动转存仅处理创建时间在最近 N 秒内的任务（默认 24 小时；不用 update_time，下载过程会刷新它） */
    private const AUTO_DOWNLOAD_WITHIN_SECONDS = 86400;

    private static function billingDurationFromTask(ShanjianVideoTask $task): float
    {
        $extra = is_array($task->extra) ? $task->extra : [];
        if (isset($extra['billing_duration']) && (float)$extra['billing_duration'] > 0) {
            return self::formatBillingDuration((float)$extra['billing_duration']);
        }
        if (isset($task->duration) && (float)$task->duration > 0) {
            return self::formatBillingDuration((float)$task->duration);
        }
        if (!empty($task->msg)) {
            return self::formatBillingDuration(mb_strlen((string)$task->msg, 'UTF-8') / 3);
        }
        if (!empty($task->audio_url)) {
            return 30;
        }
        return 0;
    }

    /**
     * 任务创建时的计费企业空间(extra.billing_team_id);缺失时回落当前空间
     */
    private static function taskBillingTeamId(ShanjianVideoTask $task): int
    {
        $extra = is_array($task->extra)
            ? $task->extra
            : (json_decode((string)$task->extra, true) ?: []);
        if (array_key_exists('billing_team_id', $extra) && $extra['billing_team_id'] !== null && $extra['billing_team_id'] !== '') {
            return max(0, (int)$extra['billing_team_id']);
        }
        return TeamContextService::currentTeamId((int)$task->user_id);
    }

    /** 按任务创建时的企业空间执行扣费/预检,避免异步回调时用户已切空间 */
    private static function withTaskBillingTeam(ShanjianVideoTask $task, callable $fn)
    {
        return TeamBillingService::runWithBillingTeam(
            (int)$task->user_id,
            self::taskBillingTeamId($task),
            $fn
        );
    }

    private static function formatBillingDuration(float $duration): float
    {
        return round(max($duration, 0), 4);
    }

    /**
     * 兼容 JSON 字段：已是数组则直接返回，字符串再 decode
     */
    private static function normalizeJsonArrayField($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
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

    private static function applyMaterialSoundSwitch(array $materials, bool $soundSwitch): array
    {
        foreach ($materials as &$material) {
            if (is_array($material) && ($material['type'] ?? '') === 'video') {
                $material['soundSwitch'] = $soundSwitch;
            }
        }
        unset($material);

        return $materials;
    }

    private static function formatCompositeVideoErrorMessage(string $message): string
    {
        if (
            strpos($message, '超出了权益并发 10') !== false
            || strpos($message, '超出了权益并发 20') !== false
        ) {
            return '当前队列通道拥挤，请稍后再试';
        }

        return $message;

    }

    private static function isClipStyleNotFoundError(string $message): bool
    {
        return strpos($message, '视频风格不存在') !== false;
    }

    private static function extractShanjianResponseMessage(array $response): string
    {
        if (!empty($response['message'])) {
            return (string)$response['message'];
        }
        if (!empty($response['data']['message'])) {
            return (string)$response['data']['message'];
        }

        return '';
    }

    /**
     * 保存中台提交结果。waiting 是已受理状态，业务 status 保持 0，避免重复提交。
     */
    private static function acceptQueueSubmission(
        ShanjianVideoTask $task,
        array $response,
        float $unit,
        int $tokenCode,
        string $billingName
    ): bool {
        $queue = ShanjianQueueService::normalizeSubmission($response);
        $isWaiting = $queue['queue_status'] === ShanjianQueueService::STATUS_WAITING;
        $isSubmitted = $queue['queue_status'] === ShanjianQueueService::STATUS_SUBMITTED
            && $queue['upstream_task_id'] !== '';
        if (!$isWaiting && !$isSubmitted) {
            return false;
        }

        Db::startTrans();
        try {
            $lockedTask = ShanjianVideoTask::where('id', (int)$task->id)
                ->whereIn('status', [ShanjianVideoTask::STATUS_PENDING, ShanjianVideoTask::STATUS_PROCESSING])
                ->lock(true)
                ->find();
            // 终态回调可能早于提交接口返回，此时直接接受回调结果，禁止状态回退。
            if (!$lockedTask || ($isWaiting && (int)$lockedTask->status !== ShanjianVideoTask::STATUS_PENDING)) {
                Db::commit();
                return true;
            }

            $lockedTask->queue_status = $queue['queue_status'];
            $lockedTask->queue_position = $isWaiting ? $queue['queue_position'] : 0;
            $lockedTask->queue_updated_time = time();
            $lockedTask->remark = '';
            if ($isSubmitted) {
                $lockedTask->result_id = $queue['upstream_task_id'];
                if ((int)$lockedTask->status === ShanjianVideoTask::STATUS_PENDING) {
                    $lockedTask->status = ShanjianVideoTask::STATUS_PROCESSING;
                }
            }

            // 队列未回时长时回落任务预估(包装派生 type=2 常见 queue.duration=0)
            $billDuration = $queue['duration'] > 0
                ? (float)$queue['duration']
                : self::billingDurationFromTask($lockedTask);
            if ($billDuration > 0 && (float)$lockedTask->video_token <= 0) {
                $points = round($billDuration * $unit, 2);
                if ($points > 0) {
                    $lockedTask->video_token = $points;
                    self::withTaskBillingTeam($lockedTask, static function () use ($lockedTask, $points, $tokenCode, $billingName, $billDuration, $unit) {
                        User::userTokensChange((int)$lockedTask->user_id, $points);
                        AccountLogLogic::recordUserTokensLog(true, (int)$lockedTask->user_id, $tokenCode, $points, (string)$lockedTask->task_id, [
                            '扣费项目' => $billingName,
                            '音视频时长' => $billDuration,
                            '算力单价' => $unit,
                            '实际消耗算力' => $points,
                        ]);
                    });
                }
            }

            $lockedTask->save();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 队列轮询更新；仅允许 waiting -> waiting/submitted/failed，禁止覆盖业务终态。
     */
    public static function handleQueueStatus(string $taskId, array $queue): bool
    {
        Db::startTrans();
        try {
            $task = ShanjianVideoTask::where('task_id', $taskId)
                ->whereIn('status', [ShanjianVideoTask::STATUS_PENDING, ShanjianVideoTask::STATUS_PROCESSING])
                ->lock(true)
                ->find();
            if (!$task || in_array((int)$task->status, [ShanjianVideoTask::STATUS_FAILED, ShanjianVideoTask::STATUS_SUCCESS], true)) {
                Db::commit();
                return true;
            }

            $status = (string)($queue['queue_status'] ?? '');
            $task->queue_updated_time = time();
            if ($status === ShanjianQueueService::STATUS_WAITING) {
                Log::channel('shanjianQueue')->write('[闪剪队列任务id：' . $taskId . ' 等待中] ' . json_encode($queue));
                if ((int)$task->status !== ShanjianVideoTask::STATUS_PENDING) {
                    Db::commit();
                    return true;
                }
                $task->queue_status = $status;
                $task->queue_position = max(0, (int)($queue['queue_position'] ?? 0));
                $task->save();
            } elseif ($status === ShanjianQueueService::STATUS_SUBMITTED) {
                Log::channel('shanjianQueue')->write('[闪剪队列任务id：' . $taskId . ' 已提交] ' . json_encode($queue));
                $upstreamTaskId = trim((string)($queue['upstream_task_id'] ?? ''));
                if ($upstreamTaskId === '') {
                    Db::commit();
                    return true;
                }
                $task->queue_status = $status;
                $task->queue_position = 0;
                $task->result_id = $upstreamTaskId;
                if ((int)$task->status === ShanjianVideoTask::STATUS_PENDING) {
                    $task->status = ShanjianVideoTask::STATUS_PROCESSING;
                }
                $task->save();
            } elseif ($status === ShanjianQueueService::STATUS_FAILED) {
                Log::channel('shanjianQueue')->write('[闪剪队列任务id：' . $taskId . ' 失败] ' . json_encode($queue));
                if (
                    (int)$task->status !== ShanjianVideoTask::STATUS_PENDING
                    || (string)$task->queue_status !== ShanjianQueueService::STATUS_WAITING
                ) {
                    Db::commit();
                    return true;
                }
                $task->queue_status = $status;
                $task->queue_position = 0;
                $task->status = ShanjianVideoTask::STATUS_FAILED;
                $task->remark = mb_substr((string)((($queue['message'] ?? '') ?: '闪剪排队失败')), 0, 250, 'UTF-8');
                if ((int)$task->queue_refund_status === 0) {
                    self::refundShanjianVideoTokens($task, null, [
                        '扣费项目' => '闪剪排队失败算力退回',
                        '失败原因' => $task->remark,
                    ]);
                    $task->queue_refund_status = 1;
                }
                self::updateVideoSettingStatus((int)$task->video_setting_id, false);
                self::handleMaterialUseFailure($task);
                $task->save();
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::channel('shanjiannotice')->error('闪剪队列状态更新失败 task_id=' . $taskId . ' error=' . $e->getMessage());
            return false;
        }
    }

    private static function deleteClipTemplateIfExists(string $clipId, string $taskId = ''): void
    {
        $clipId = trim($clipId);
        if ($clipId === '') {
            return;
        }

        $deleted = ShanjianClipTemplate::where('id', $clipId)->delete();
        if ($deleted) {
            $message = '视频风格不存在,已删除模版 clip_id=' . $clipId;
            if ($taskId !== '') {
                $message .= ' task_id=' . $taskId;
            }
            Log::channel('shanjianClipTemplate')->write($message);
        }
    }

    /**
     * 闪剪合成提交失败后的任务状态处理
     */
    private static function markCompositeSubmitFailure(
        ShanjianVideoTask $task,
        string $errorMessage = '',
        bool $handleMaterialFailure = false
    ): void {
        $errorMessage = trim($errorMessage);

        if ($errorMessage !== '' && self::isClipStyleNotFoundError($errorMessage)) {
            $task->tries = 10;
            $task->status = 2;
            $task->remark = self::formatCompositeVideoErrorMessage($errorMessage);
            self::deleteClipTemplateIfExists((string)$task->clip_id, (string)$task->task_id);
            self::updateVideoSettingStatus($task->video_setting_id, false);
            self::handleMaterialUseFailure($task);
            $task->save();
            return;
        }

        $task->tries = $task->tries + 1;
        if ($task->tries >= 10) {
            $task->status = 2;
            $task->remark = $errorMessage !== ''
                ? self::formatCompositeVideoErrorMessage($errorMessage)
                : '视频合成10次失败';
            self::updateVideoSettingStatus($task->video_setting_id, false);
            if ($handleMaterialFailure) {
                self::handleMaterialUseFailure($task);
            }
        }
        $task->save();
    }

    /**
     * 更新闪剪视频任务
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $task = ShanjianVideoTask::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$task) {
                self::setError('视频任务不存在');
                return false;
            }

            $data = [];

            // 只更新允许修改的字段
            $allowFields = ['name', 'title', 'msg', 'card_name', 'card_introduced', 'material', 'music_url', 'clip_id'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    if ($field === 'material' || $field === 'extra') {
                        $data[$field] = json_encode($params[$field]);
                    } else {
                        $data[$field] = $params[$field];
                    }
                }
            }

            if (!empty($data)) {
                $data['update_time'] = time();
                $task->save($data);
            }

            self::$returnData = $task->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function handleMaterialUseFailure($task): void
    {
        if ($task->persona_id > 0) {
            MaterialUseLog::where('task_id', $task->id)
                ->where('persona_id', $task->persona_id)
                ->update(['use_status' => 2, 'fail_reason' => $task->remark]);

            $materialIds = MaterialUseLog::where('task_id', $task->id)
                ->where('persona_id', $task->persona_id)
                ->column('material_id');

            foreach ($materialIds as $materialId) {
                $redisKey = 'material_' . $materialId . '_device_' . $task->device_code;
                if (Cache::store('redis')->has($redisKey)) {
                    Cache::store('redis')->delete($redisKey);
                }
                $count = Cache::store('material_redis')->get($redisKey);
                if ($count > 0) {
                    Cache::store('material_redis')->dec($redisKey);
                }
            }
        }
    }

    private static function safeRollback(string $context = ''): void
    {
        try {
            Db::rollback();
        } catch (\Throwable $rollbackEx) {
            $prefix = $context ? $context . ' ' : '';
            Log::channel('shanjiannotice')->warning($prefix . '回滚失败: ' . $rollbackEx->getMessage());
        }
    }

    /**
     * 原子获取 Redis 锁（setnx + expire）
     */
    public static function acquireRedisLock(string $lockKey, int $ttl = 180): bool
    {
        try {
            $redis = Cache::store('concurrent_redis')->handler();
            $fullKey = (string)config('cache.stores.concurrent_redis.prefix', '') . $lockKey;
            if (!$redis->setnx($fullKey, 1)) {
                return false;
            }
            $redis->expire($fullKey, $ttl);
            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjiannotice')->warning('获取Redis锁失败: ' . $e->getMessage());
            return false;
        }
    }

    public static function releaseRedisLock(string $lockKey): void
    {
        try {
            Cache::store('concurrent_redis')->delete($lockKey);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function keepRedisLock(string $lockKey, int $ttl = 20): void
    {
        try {
            Cache::store('concurrent_redis')->set($lockKey, 1, $ttl);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * 是否为 MySQL 1205 锁等待超时
     */
    public static function isLockWaitTimeout(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return strpos($msg, 'Lock wait timeout exceeded') !== false
            || strpos($msg, '1205') !== false;
    }

    public static function isLockWaitTimeoutMessage(string $msg): bool
    {
        return strpos($msg, 'Lock wait timeout exceeded') !== false
            || strpos($msg, '1205') !== false;
    }

    private static function getShanjianVideoTokenChangeType($shanjianType): int
    {
        return match ((int)$shanjianType) {
            2 => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
            3 => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
            4 => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
            5 => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
            default => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
        };
    }

    private static function refundShanjianVideoTokens(ShanjianVideoTask $task, ?int $typeID = null, array $extra = []): bool
    {
        $userId = (int)$task->user_id;
        $taskId = (string)$task->task_id;
        $typeID = $typeID ?: self::getShanjianVideoTokenChangeType($task->shanjian_type);

        $deducted = (float)UserTokensLog::where('user_id', $userId)
            ->where('change_type', $typeID)
            ->where('action', AccountLogEnum::DEC)
            ->where('task_id', $taskId)
            ->sum('change_amount');

        if ($deducted <= 0) {
            return false;
        }

        $refunded = (float)UserTokensLog::where('user_id', $userId)
            ->where('change_type', $typeID)
            ->where('action', AccountLogEnum::INC)
            ->where('status', 2)
            ->where('task_id', $taskId)
            ->sum('change_amount');

        $points = round(max(0, $deducted - $refunded), 2);
        if ($points <= 0) {
            return false;
        }

        AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId, $extra);
        return true;
    }

    private static function normalizeSubmitUri(string $url): string
    {
        $url = trim(str_replace('\\', '/', $url));
        if ($url === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            return ltrim((string)$path, '/');
        }

        return ltrim($url, '/');
    }

    private static function hasValidMaterialFileUrl(array $materials): bool
    {
        foreach ($materials as $material) {
            if (!is_array($material)) {
                continue;
            }

            if (array_key_exists('fileUrl', $material)) {
                $fileUrl = trim((string)($material['fileUrl'] ?? ''));
                if ($fileUrl !== '' && $fileUrl !== '0') {
                    return true;
                }
                continue;
            }

            if (self::hasValidMaterialFileUrl($material)) {
                return true;
            }
        }

        return false;
    }

    private static function failEmptyMaterialTask(ShanjianVideoTask $task): void
    {
        $task->status = 2;
        $task->remark = '由于素材会进行特殊处理，未能有足够素材驱动视频合成';
        $task->update_time = time();
        $task->save();
        self::updateVideoSettingStatus($task->video_setting_id, false);
        self::handleMaterialUseFailure($task);
        Log::channel('shanjiannotice')->write('素材为空,标记失败并跳过 task_id=' . $task->task_id);
    }

    /**
     * 等待任务素材全部转码收敛,再将转码失败素材从任务表 material 字段中删除。
     *
     * 有素材仍待转码/转码中时抛 MaterialNotReadyException,等待下一轮 cron。
     *
     * @return array<int, mixed>
     */

    private static function prepareSubmitMaterialsAfterTranscode(ShanjianVideoTask $task, array $materialArr): array
    {
        $uriSourceMap = [];
        foreach ($materialArr as $index => $material) {
            if (!is_array($material) || empty($material['fileUrl'])) {
                continue;
            }

            $uri = self::normalizeSubmitUri((string)$material['fileUrl']);
            if ($uri !== '') {
                $uriSourceMap[$uri][] = [
                    'field' => 'material',
                    'index' => $index,
                    'url' => (string)$material['fileUrl'],
                ];
            }
        }

        if (empty($uriSourceMap)) {
            return $materialArr;
        }

        $check = MaterialReadinessService::checkFileUrlsForSubmit(array_keys($uriSourceMap));
        if (!empty($check['pending_uris'])) {
            throw MaterialNotReadyException::fromCheck($check, 'task_id=' . $task->task_id);
        }

        $failedUris = $check['failed_uris'] ?? [];
        if (empty($failedUris)) {
            return $materialArr;
        }

        $failedSet = array_fill_keys($failedUris, true);
        $filteredMaterials = [];
        $removed = [];

        foreach ($materialArr as $index => $material) {
            $uri = is_array($material) && !empty($material['fileUrl'])
                ? self::normalizeSubmitUri((string)$material['fileUrl'])
                : '';

            if ($uri !== '' && isset($failedSet[$uri])) {
                $removed[] = [
                    'field' => 'material',
                    'index' => $index,
                    'uri' => $uri,
                    'url' => (string)$material['fileUrl'],
                ];
                continue;
            }

            $filteredMaterials[] = $material;
        }

        if (empty($removed)) {
            return $materialArr;
        }

        $filteredMaterials = array_values($filteredMaterials);
        $materialJson = json_encode(
            $filteredMaterials,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (!is_string($materialJson)) {
            throw new \Exception('删除转码失败素材后material编码失败: ' . json_last_error_msg());
        }

        $task->material = $materialJson;
        $task->update_time = time();
        if (!$task->save()) {
            throw new \Exception('删除转码失败素材后保存任务material失败');
        }

        Log::channel('shanjiannotice')->write('[素材转码失败删除] ' . json_encode([
            'task_id' => $task->task_id,
            'video_setting_id' => (int)$task->video_setting_id,
            'user_id' => (int)$task->user_id,
            'shanjian_type' => (int)$task->shanjian_type,
            'table' => 'iw_shanjian_video_task',
            'field' => 'material',
            'failed_count' => count($removed),
            'failed_uri_count' => count(array_unique(array_column($removed, 'uri'))),
            'failed_uris' => $failedUris,
            'removed' => $removed,
            'original_material_count' => count($materialArr),
            'submit_material_count' => count($filteredMaterials),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));


        return $filteredMaterials;
    }


    /**
     * 蝉镜 HumanVideoTask 成功/失败后，回写同 task_id 的 type5 桥接任务；
     * 成功且 ai_clip_enabled 时派生闪剪 type=2 包装。
     *
     * @param \app\common\model\human\HumanVideoTask $humanTask
     * @return bool 是否命中 type5 桥接
     */
    public static function syncType5BridgeFromChanjing($humanTask): bool
    {
        $bridge = ShanjianVideoTask::where('task_id', (string)$humanTask->task_id)
            ->where('shanjian_type', 5)
            ->find();
        if (!$bridge) {
            return false;
        }

        $extra = is_array($bridge->extra)
            ? $bridge->extra
            : (json_decode((string)$bridge->extra, true) ?: []);
        if ((int)($extra['engine_type'] ?? 0) !== ShanjianVideoSettingLogic::ENGINE_TYPE_CHANJING
            && ($extra['waiting_engine'] ?? '') !== 'chanjing') {
            // 兼容：同 task_id 即视为桥接
        }

        $humanStatus = (int)$humanTask->status;
        if ($humanStatus === 2) {
            if ((int)$bridge->status !== ShanjianVideoTask::STATUS_FAILED) {
                $bridge->status = ShanjianVideoTask::STATUS_FAILED;
                $bridge->remark = (string)($humanTask->remark ?: '蝉镜无包装合成失败');
                $bridge->update_time = time();
                $bridge->save();
                self::updateVideoSettingStatus((int)$bridge->video_setting_id, false);
            }
            return true;
        }

        if ($humanStatus !== 1) {
            return true;
        }

        $videoUrl = trim((string)$humanTask->result_url);
        if ($videoUrl === '') {
            Log::channel('shanjiannotice')->warning('蝉镜桥接回写跳过:无成片 task_id=' . $humanTask->task_id);
            return true;
        }

        $duration = (float)($humanTask->duration ?? 0);
        $bridge->status = ShanjianVideoTask::STATUS_SUCCESS;
        $bridge->video_result_url = $videoUrl;
        $bridge->video_source_url = $videoUrl;
        if ($duration > 0) {
            $bridge->duration = (int)ceil($duration);
        }
        $bridge->audio_url = (string)($humanTask->audio_url ?? $bridge->audio_url);
        $bridge->update_time = time();
        $bridge->save();

        // 派生包装（口播 volume 不带入包装）
        self::createPackagingTaskIfNeeded($bridge, $videoUrl);
        self::updateVideoSettingStatus((int)$bridge->video_setting_id, true);

        Log::channel('shanjiannotice')->write('蝉镜桥接type5已回写' . json_encode([
            'human_task_id' => (int)$humanTask->id,
            'bridge_task_id' => (int)$bridge->id,
            'ai_clip_enabled' => !empty($extra['ai_clip_enabled']) ? 1 : 0,
            'video_url' => $videoUrl,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * 数字人口播无包装(type=5)成功后, 若开启AI智剪则派生 type=2 真人口播混剪包装任务
     * 用 type=5 成片作为输入视频, 两条任务通过 origin_task_id/packaging_task_id 双向关联
     * 必须在调用方事务内执行, 异常向上抛出以保证一致性
     * 注意：包装阶段不处理口播 volume（第一步人声音量），BGM 固定默认 0.3
     *
     * @param ShanjianVideoTask $type5Task 已锁定的 type=5 任务(成功态)
     * @param string $videoUrl type=5 成片地址(相对uri)
     * @return void
     */
    public static function createPackagingTaskIfNeeded(ShanjianVideoTask $type5Task, string $videoUrl): void
    {
        if ((int)$type5Task->shanjian_type !== 5) {
            return;
        }

        $extra = is_array($type5Task->extra)
            ? $type5Task->extra
            : (json_decode((string)$type5Task->extra, true) ?: []);

        // 智剪关闭: type=5 即最终视频, 不派生
        if (empty($extra['ai_clip_enabled'])) {
            return;
        }

        if (trim($videoUrl) === '') {
            Log::channel('shanjiannotice')->warning('type5派生包装任务跳过:无成片地址, task_id=' . $type5Task->task_id);
            return;
        }

        // 幂等: 已派生则跳过(防止重复回调重复创建)
        if ((int)$type5Task->packaging_task_id > 0) {
            return;
        }
        $exists = ShanjianVideoTask::where('origin_task_id', (int)$type5Task->id)
            ->where('shanjian_type', 2)
            ->find();
        if ($exists) {
            $type5Task->packaging_task_id = (int)$exists->id;
            $type5Task->is_final = 0;
            $type5Task->save();
            return;
        }

        $packaging = $extra['packaging'] ?? [];
        $fullVideoUrl = FileService::getFileUrl($videoUrl);
        $resultDuration = (float)($type5Task->duration ?? 0);

        // 包装模板: 优先 packaging, 否则取 realMan 模板
        $clipId = '';
        if (!empty($packaging['clip_id'])) {
            $clipId = $packaging['clip_id'];
        } elseif (!empty($packaging['clip'][0]['clip_template_id'])) {
            $clipId = $packaging['clip'][0]['clip_template_id'];
        }
        if ($clipId === '') {
            $clipTemplates = \app\common\model\shanjian\ShanjianClipTemplate::where('scene', 'realMan')->column('id');
            if (!empty($clipTemplates)) {
                $clipId = (string)$clipTemplates[random_int(0, count($clipTemplates) - 1)];
            }
        }

        // 素材/音乐：优先 packaging；包装阶段不继承第一步口播 volume
        $material = $packaging['material'] ?? $type5Task->material; // getter 返回数组
        $musicUrl = $packaging['music_url'] ?? $type5Task->getData('music_url');
        $pic = (string)$type5Task->getData('pic');
        $packagingName = mb_substr(($type5Task->name ?: '数字人口播') . '-智剪包装', 0, 50, 'UTF-8');

        $setting = ShanjianVideoSetting::create([
            'user_id' => (int)$type5Task->user_id,
            'task_id' => generate_unique_task_id(),
            'name' => $packagingName,
            'shanjian_type' => 2,
            'status' => 1,
            'video_count' => 1,
            'auto_type' => (int)($type5Task->auto_type ?? 0),
            'wechat_type' => (int)($type5Task->wechat_type ?? 0),
            'device_code' => (string)$type5Task->device_code,
            'persona_id' => (int)($type5Task->persona_id ?? 0),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        $type5Extra = is_array($type5Task->extra)
            ? $type5Task->extra
            : (json_decode((string)$type5Task->extra, true) ?: []);
        // 继承 type5 创建时的企业空间;缺失则按 type5 用户当前空间兜底
        $billingTeamId = array_key_exists('billing_team_id', $type5Extra)
            ? (int)$type5Extra['billing_team_id']
            : TeamContextService::currentTeamId((int)$type5Task->user_id);
        $newExtra = [
            'create_type' => 'ai_clip_packaging',
            'billing_duration' => self::formatBillingDuration($resultDuration),
            'origin_task_id' => (int)$type5Task->id,
            // 包装 BGM 固定默认，不使用第一步口播 volume
            'volume' => 0.3,
            'billing_team_id' => $billingTeamId,
        ];

        $newTask = new ShanjianVideoTask();
        $newTask->save([
            'shanjian_type' => 2,
            'is_final' => 1,
            'origin_task_id' => (int)$type5Task->id,
            'device_code' => (string)$type5Task->device_code,
            'name' => $packagingName,
            'pic' => $pic,
            'task_id' => generate_unique_task_id(),
            'persona_id' => (int)($type5Task->persona_id ?? 0),
            'status' => 0,
            'audio_type' => 1,
            'auto_type' => (int)($type5Task->auto_type ?? 0),
            'wechat_type' => (int)($type5Task->wechat_type ?? 0),
            'user_id' => (int)$type5Task->user_id,
            'video_setting_id' => (int)$setting->id,
            'anchor_id' => $fullVideoUrl, // 真人口播混剪以成片地址作为 videoUrl
            'voice_id' => '',
            'card_name' => (string)$type5Task->card_name,
            'card_introduced' => (string)$type5Task->card_introduced,
            'title' => '',
            'msg' => '',
            'material' => $material,
            'music_url' => $musicUrl,
            'clip_id' => $clipId,
            'thumb_status' => trim($pic) !== '' ? 2 : 4,
            'duration' => (int)ceil($resultDuration),
            'extra' => json_encode($newExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        $type5Task->packaging_task_id = (int)$newTask->id;
        $type5Task->is_final = 0;
        $type5Task->save();

        Log::channel('shanjiannotice')->write('type5智剪派生type2包装任务' . json_encode([
            'origin_task_id' => (int)$type5Task->id,
            'packaging_task_id' => (int)$newTask->id,
            'video_url' => $fullVideoUrl,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


    private static function failCheckingVideoTask(ShanjianVideoTask $task, string $remark): bool
    {
        try {
            Db::startTrans();

            $item = ShanjianVideoTask::where('id', $task->id)
                ->where('status', 1)
                ->lock(true)
                ->find();
            if (!$item) {
                Db::commit();
                return false;
            }

            $item->status = 2;
            $item->video_token = 0;
            $item->remark = $remark;
            $item->update_time = time();
            $item->save();

            self::refundShanjianVideoTokens($item, null, [
                '扣费项目' => '壹传媒视频合成Check异常退费',
                '失败原因' => $remark,
            ]);
            self::updateVideoSettingStatus((int)$item->video_setting_id, false);

            Db::commit();

            try {
                self::handleMaterialUseFailure($item);
            } catch (\Throwable $materialEx) {
                Log::channel('shanjiannotice')->warning('Check异常处理素材失败, task_id: ' . $item->task_id . ', Error: ' . $materialEx->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            self::safeRollback('Check异常失败处理');
            Log::channel('shanjiannotice')->error('Check异常标记失败及退费失败, task_id: ' . ($task->task_id ?? '') . ', Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 按实际子任务重算视频设置的状态和计数。
     *
     * 任务可能由旧逻辑分批创建，不能依赖递增计数，否则父任务会在仍有
     * 子任务处理中时被提前收口为完成状态。
     *
     * @param int $videoSettingId 视频设置ID
     * @param bool $isSuccess 保留调用参数兼容既有调用点
     */
    private static function updateVideoSettingStatus(int $videoSettingId, bool $isSuccess): bool
    {
        try {
            $setPublish = false;

            $videoSetting = ShanjianVideoSetting::where('id', $videoSettingId)->lock(true)->find();
            if (!$videoSetting) {
                return $setPublish;
            }

            $taskQuery = ShanjianVideoTask::where('video_setting_id', $videoSettingId);
            $videoCount = (clone $taskQuery)->count();
            $successNum = (clone $taskQuery)
                ->where('status', ShanjianVideoTask::STATUS_SUCCESS)
                ->count();
            $errorNum = (clone $taskQuery)
                ->where('status', ShanjianVideoTask::STATUS_FAILED)
                ->count();

            $videoSetting->video_count = $videoCount;
            $videoSetting->success_num = $successNum;
            $videoSetting->error_num = $errorNum;

            // 检查是否所有实际创建的任务都已处理完成
            if ($videoCount > 0 && $successNum + $errorNum >= $videoCount) {
                if ($errorNum === 0) {
                    // 全部成功
                    $setPublish = true;

                    $videoSetting->status = 3;
                } elseif ($successNum === 0) {
                    // 全部失败
                    $setPublish = false;
                    $videoSetting->status = 5;
                } else {
                    // 部分成功
                    $setPublish = true;
                    $videoSetting->status = 4;
                }
            } elseif (in_array((int)$videoSetting->status, [3, 4, 5], true)) {
                // 有待处理子任务时父任务必须回到运行中，确保超时收口等流程可见。
                $videoSetting->status = 2;
            }

            $videoSetting->save();

            if ($videoSetting->wechat_type == 1 && $setPublish && $videoSetting->auto_type == 1) {
                return true;
            } elseif ($videoSetting->auto_type == 1 && $setPublish) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::channel('shanjiannotice')->write('更新视频设置状态错误' . $e->getMessage());
            return false;
        }
    }

    /**
     * 是否为开启AI智剪的 type=5 中间过渡任务(非最终视频, 不应直接发布)
     */
    private static function isIntermediateAiClipTask($task): bool
    {
        if ((int)$task->shanjian_type !== 5) {
            return false;
        }
        $extra = is_array($task->extra) ? $task->extra : (json_decode((string)$task->extra, true) ?: []);
        return !empty($extra['ai_clip_enabled']);
    }

    private static function syncPendingPublishDetailVideo(ShanjianVideoTask $task, string $videoUrl): void
    {
        if (trim($videoUrl) === '') {
            return;
        }
        // 开启智剪的 type=5 是中间产物, 等派生 type=2 包装完成再同步发布
        if (self::isIntermediateAiClipTask($task)) {
            return;
        }

        try {
            $update = [
                'material_url' => FileService::getFileUrl($videoUrl),
                'remark' => '',
                'update_time' => time(),
            ];
            if (trim((string)$task->pic) !== '') {
                $update['pic'] = FileService::getFileUrl((string)$task->pic);
            }

            $taskIds = [(int)$task->id];
            if ((int)($task->origin_task_id ?? 0) > 0) {
                $taskIds[] = (int)$task->origin_task_id;
                $update['video_task_id'] = (int)$task->id;
            }

            $count = SvPublishSettingDetail::where('video_task_id', 'in', array_values(array_unique($taskIds)))
                ->where('user_id', (int)$task->user_id)
                ->where('status', 0)
                ->where('account_type', 'in', [1, 3, 4, 5])
                ->update($update);

            Log::channel('shanjian')->write('闪剪视频生成成功同步待发布详情' . json_encode([
                'user_id' => (int)$task->user_id,
                'shanjian_video_task_id' => (int)$task->id,
                'origin_task_id' => (int)($task->origin_task_id ?? 0),
                'task_id' => (string)$task->task_id,
                'video_result_url' => $videoUrl,
                'publish_detail_count' => $count,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::channel('shanjian')->error('闪剪视频同步待发布详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除闪剪视频任务
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {

            if (is_string($id)) {
                $task = ShanjianVideoTask::where('id', $id)
                    ->where('user_id', self::$uid)
                    ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                    ->find();

                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                ShanjianVideoTask::where('id', $id)->select()->delete();
            } else {
                $task = ShanjianVideoTask::whereIn('id', $id)->where(['user_id' => self::$uid])
                    ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                    ->column('id');
                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                ShanjianVideoTask::whereIn('id', $id)->select()->delete();
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取闪剪视频任务详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $task = ShanjianVideoTask::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$task) {
                self::setError('视频任务不存在');
                return false;
            }

            $taskData = $task->toArray();
            $taskData['queue_status_text'] = ShanjianQueueService::statusText(
                (string)($taskData['queue_status'] ?? ''),
                (int)($taskData['queue_position'] ?? 0)
            );
            $taskData['download_status'] = (int)($taskData['download_status'] ?? 0);
            $taskData['download_status_text'] = ShanjianVideoTask::getDownloadStatusText($taskData['download_status']);
            $taskData['video_source_url'] = trim((string)($taskData['video_source_url'] ?? '')) !== ''
                ? FileService::getFileUrl((string)$task->getData('video_source_url'))
                : '';

            // material/extra 经模型 getter 后 toArray 已是数组，勿再 json_decode
            $taskData['material'] = self::normalizeJsonArrayField($taskData['material'] ?? null);
            $taskData['extra'] = self::normalizeJsonArrayField($taskData['extra'] ?? null);

            // 处理文件URL
            //            $taskData['pic'] = trim($taskData['pic']) ? FileService::getFileUrl($taskData['pic']) : "";
            //            $taskData['music_url'] = trim($taskData['music_url']) ? FileService::getFileUrl($taskData['music_url']) : "";
            //            $taskData['video_result_url'] = trim($taskData['video_result_url']) ? FileService::getFileUrl($taskData['video_result_url']) : "";

            self::$returnData = $taskData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 从回调更新闪剪视频任务
     * @param array $data
     * @return bool
     */
    public static function notify(array $data): bool
    {
        if (!isset($data['task_id']) || empty($data['task_id'])) {
            self::setError('缺少任务ID');
            return false;
        }

        // 已终态幂等
        $existStatus = ShanjianVideoTask::where('task_id', $data['task_id'])->value('status');
        if ($existStatus !== null && in_array((int)$existStatus, [
            ShanjianVideoTask::STATUS_FAILED,
            ShanjianVideoTask::STATUS_SUCCESS,
        ], true)) {
            return true;
        }

        $statusmsg = '';
        $video_result_url = '';
        $notice = $setPublish = false;

        // 回调不阻塞下载：短事务只落原链接并标待下载，由定时任务 shanjian_video_task 转存。
        if (($data['status'] ?? '') === 'succeed' && !empty($data['result']['videoUrl'])) {
            $old = trim((string)$data['result']['videoUrl']);
            $video_result_url = $old;
        }

        // 仅针对 1205：短事务失败后退避重试，不重复下载
        $maxAttempts = 3;
        $lastError = '';
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $notice = $setPublish = false;
            $needUpdateVideoSetting = false;
            $statusmsg = '';
            $task = null;

            Db::startTrans();
            try {
                $task = ShanjianVideoTask::where('task_id', $data['task_id'])
                    ->whereIn('status', [ShanjianVideoTask::STATUS_PENDING, ShanjianVideoTask::STATUS_PROCESSING])
                    ->lock(true)
                    ->find();
                if (!$task) {
                    Db::commit();
                    return true;
                }

                if (isset($data['status'])) {
                    // 已下发的视频任务仍可能在父设置先收口后收到回调，不能用父设置
                    // 的运行状态拦截回调，否则子任务会永久停留在“处理中”。
                    $ShanjianVideoSetting = ShanjianVideoSetting::where('id', $task->video_setting_id)
                        ->lock(true)
                        ->findOrEmpty();
                    if ($ShanjianVideoSetting->isEmpty()) {
                        throw new \Exception('关联的视频设置不存在或已删除');
                    }
                    $typeIDArray = [
                        '1' => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
                        '2' => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
                        '3' => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
                        '4' => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
                        '5' => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN
                    ];
                    $typeID = $typeIDArray[$task->shanjian_type] ?? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN;
                    $sceneArray = [
                        '1' => 'human_video_shanjian',
                        '2' => 'shanjian_realman_broadcast',
                        '3' => 'shanjian_broadcast_mixcut',
                        '4' => 'shanjian_news_mixcut',
                        '5' => 'human_video_shanjian'
                    ];
                    $scene = $sceneArray[$task->shanjian_type] ?? 'human_video_shanjian';
                    $remarkArray = [
                        '1' => '数字人口播混剪视频',
                        '2' => '真人口播混剪视频',
                        '3' => '素材混剪视频',
                        '4' => '新闻体混剪视频',
                        '5' => '数字人口播视频(无包装)'
                    ];
                    $remark = $remarkArray[$task->shanjian_type] ?? '数字人口播混剪视频';
                    $task->queue_status = ShanjianQueueService::STATUS_SUBMITTED;
                    $task->queue_position = 0;
                    $task->queue_updated_time = time();
                    switch ($data['status']) {
                        case 'failed':
                            $notice = true;
                            $statusmsg = '合成失败';
                            $needUpdateVideoSetting = true;
                            $task->status = 2;
                            $task->remark = $data['errorMessage'] ?? '处理失败';

                            self::refundShanjianVideoTokens($task, $typeID);
                            self::handleMaterialUseFailure($task);
                            break;
                        case 'succeed':
                            $notice = true;
                            $statusmsg = '合成成功';
                            $needUpdateVideoSetting = true;
                            $task->status = 3;
                            if ($video_result_url !== '') {
                                $task->video_source_url = $video_result_url;
                                $task->video_result_url = $video_result_url;
                                $task->download_tries = 0;
                                $task->download_status = FileService::isRemoteHttpUrl($video_result_url)
                                    ? ShanjianVideoTask::DOWNLOAD_PENDING
                                    : ShanjianVideoTask::DOWNLOAD_SUCCESS;
                                $task->duration = $data['result']['duration'] ?? '0';
                            }

                            if ($task->persona_id > 0) {
                                MaterialUseLog::where('task_id', $task->id)->where('persona_id', $task->persona_id)->update(['use_status' => 1]);
                            }
                            self::syncPendingPublishDetailVideo($task, $video_result_url);

                            $user = User::where('id', $task->user_id)->lock(true)->find();
                            if (!$user) {
                                throw new \Exception('用户不存在, user_id: ' . $task->user_id);
                            }
                            $unit = ModelConfig::where('scene', $scene)->value('score', 0);
                            $duration = $data['result']['duration'] ?? 1;
                            $points = round($duration * $unit, 2);
                            $newpoints = $task->video_token;
                            $sl = $newpoints - $points;
                            $task->video_token = $points;
                            // 多退少补按任务创建时企业空间结算(包装派生无预扣流水时尤其依赖 billing_team_id)
                            self::withTaskBillingTeam($task, static function () use (
                                $task, $user, $typeID, $remark, $sl, $points, $newpoints, $duration, $unit
                            ) {
                                if ($sl > 0) {
                                    $kf = match ((int)$task->shanjian_type) {
                                        2 => '真人口播混剪视频预扣费超额扣费退费',
                                        3 => '素材混剪视频预扣费超额扣费退费',
                                        4 => '新闻体混剪视频预扣费超额扣费退费',
                                        5 => '数字人口播视频(无包装)预扣费超额扣费退费',
                                        default => '克隆数字人混剪剪辑视频预扣费超额扣费退费',
                                    };
                                    $refundAmt = round($sl, 2);
                                    $extra = ['扣费项目' => $kf, '实际视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '退费算力' => $refundAmt];
                                    $logTeamId = TeamBillingService::refundByOriginalLog(
                                        (int)$user->id,
                                        $refundAmt,
                                        (int)$typeID,
                                        (string)$task->task_id
                                    );
                                    AccountLogLogic::add(
                                        $user->id,
                                        $typeID,
                                        AccountLogEnum::INC,
                                        $refundAmt,
                                        1,
                                        $task->task_id,
                                        $remark,
                                        $extra,
                                        $logTeamId
                                    );
                                    return;
                                }
                                $addAmt = round($points - $newpoints, 2);
                                if ($addAmt <= 0) {
                                    return;
                                }
                                $kf = match ((int)$task->shanjian_type) {
                                    2 => '真人口播混剪视频预扣费补足费用补扣',
                                    3 => '素材混剪视频预扣费补足费用补扣',
                                    4 => '新闻体混剪视频预扣费补足费用补扣',
                                    5 => '数字人口播视频(无包装)预扣费补足费用补扣',
                                    default => '克隆数字人混剪剪辑视频预扣费补足费用补扣',
                                };
                                $extra = ['扣费项目' => $kf, '实际视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '补扣算力' => $addAmt];
                                $logTeamId = TeamBillingService::deductByOriginalLog(
                                    (int)$user->id,
                                    $addAmt,
                                    (int)$typeID,
                                    (string)$task->task_id
                                );
                                $addTypeMap = [
                                    1 => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD,
                                    2 => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD,
                                    3 => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN_ADD,
                                    4 => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN_ADD,
                                    5 => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD,
                                ];
                                $addType = $addTypeMap[(int)$task->shanjian_type] ?? $typeID;
                                AccountLogLogic::add(
                                    $user->id,
                                    $addType,
                                    AccountLogEnum::DEC,
                                    $addAmt,
                                    1,
                                    $task->task_id,
                                    $kf,
                                    $extra,
                                    $logTeamId
                                );
                            });

                            self::createPackagingTaskIfNeeded($task, $video_result_url);
                            break;
                    }
                }

                $task->update_time = time();
                $task->save();
                if ($needUpdateVideoSetting) {
                    $setPublish = self::updateVideoSettingStatus((int)$task->video_setting_id, $task->status === ShanjianVideoTask::STATUS_SUCCESS);
                }
                Db::commit();

                if ($notice) {
                    ApiLogic::sendNotice([
                        'userId' => $task->user_id,
                        'content' => $task->name,
                        'status' => $statusmsg
                    ], 402);
                }
                if ($task->wechat_type == 1 && trim($video_result_url) !== '' && ($setPublish || (int)($task->is_publish ?? 0) === 1)) {
                    self::createWechatCirclePublish($task, $video_result_url);
                }
                if ($setPublish) {
                    $param = [
                        'device_code' => $task->device_code,
                        'sj_video_id' => $task->id
                    ];
                    //\app\api\logic\auto\PublishLogic::setShanjianPublish($param);
                }
                return true;
            } catch (\Exception $e) {
                self::safeRollback('Notify');
                $lastError = $e->getMessage();

                // 仅 1205：退避后重试短事务
                if (self::isLockWaitTimeout($e) && $attempt < $maxAttempts) {
                    Log::channel('shanjiannotice')->warning(
                        'Notify 1205锁等待超时，第' . $attempt . '/' . $maxAttempts . '次重试, task_id: ' . ($data['task_id'] ?? '')
                    );
                    $existStatus = ShanjianVideoTask::where('task_id', $data['task_id'])->value('status');
                    if ($existStatus !== null && in_array((int)$existStatus, [
                        ShanjianVideoTask::STATUS_FAILED,
                        ShanjianVideoTask::STATUS_SUCCESS,
                    ], true)) {
                        return true;
                    }
                    usleep(400000 * $attempt);
                    continue;
                }

                Log::channel('shanjiannotice')->error('Notify 处理失败, task_id: ' . ($data['task_id'] ?? '') . ', Error: ' . $lastError);
                self::setError($lastError);
                return false;
            }
        }

        Log::channel('shanjiannotice')->error('Notify 1205重试耗尽, task_id: ' . ($data['task_id'] ?? '') . ', Error: ' . $lastError);
        self::setError($lastError);
        return false;
    }


    public static function copywriting(array $data)
    {
        $keywords = $data['keywords'] ?? '';
        $number = $data['number'] ?? '';
        if (empty($keywords) || empty($number)) {
            message('参数错误');
        }

        $taskId = generate_unique_task_id();
        $request = [
            'keywords' => $keywords,
            'number' => $number,
            'channelVersion' => 5,
        ];
        $scene = self::COPYWRITING_CREATE;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);
        if (!empty($result) && isset($result['content'])) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    public static function createWechatCirclePublish($task, $video_result_url): bool
    {
        $videoUrl = $video_result_url ?? '';
        if (empty($videoUrl)) {
            Log::channel('wechatCircle')->error($task->device_code . ', 错误信息: 生产的视频链接错误');
            return false;
        }
        // 开启智剪的 type=5 是中间产物, 不直接发布朋友圈, 等派生 type=2 包装完成
        if (self::isIntermediateAiClipTask($task)) {
            return true;
        }
        $videoUrl = FileService::getFileUrl($videoUrl);

        $existingCircleTasks = self::findExistingWechatCirclePublishTasks($task);
        if (!$existingCircleTasks->isEmpty()) {
            return self::replaceExistingWechatCirclePublishVideo($task, $videoUrl, $existingCircleTasks);
        }
        if ((int)($task->is_publish ?? 0) === 1) {
            Log::channel('wechatCircle')->warning('自动化朋友圈视频合成未找到原发布任务，跳过新建发布任务' . json_encode([
                'user_id' => (int)$task->user_id,
                'device_code' => (string)$task->device_code,
                'shanjian_video_task_id' => (int)$task->id,
                'shanjian_task_id' => (string)$task->task_id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return true;
        }

        $extrainfo = $task->extra;
        $wechatIds = [];
        $accounts = SvAccount::where('device_code', $task->device_code)
            ->field('id, account, type, nickname, avatar')
            ->where('type', 1)
            ->where('user_id', $task->user_id)
            ->select();
        foreach ($accounts as $account) {
            $wechatIds[] = [
                'account' => $account->account,
                'type' => $account->type,
                'nickname' => $account->nickname,
                'avatar' => $account->avatar
            ];
        }
        if (empty($wechatIds)) {
            Log::channel('wechatCircle')->error($task->device_code . ', 错误信息: 该设备没有绑定微信账号');
            return false;
        }

        $coze['sn'] = 9;
        $coze['number'] = 1;
        $coze['length'] = 15;
        $coze['keywords'] = $task->msg;
        $content = '';
        $maxRetries = 3;
        $retryCount = 0;
        while (empty($content) && $retryCount < $maxRetries) {
            $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $task->user_id, 6);
            $content = $copywritingResult['content'] ?? '';
            $retryCount++;
        }

        $execTime = $extrainfo['exec_time'] ?: '["07:50-08:00"]';
        if (is_string($execTime)) {
            $execTime = json_decode($execTime, true) ?: ['07:50-08:00'];
        }

        $sendTimeDelay = 2;
        $firstTimeRange = '07:50-08:00';
        $startTime = $baseTime = '07:50';
        $endTime = '08:00';
        if (!empty($execTime) && is_array($execTime)) {
            $firstTimeRange = $execTime[0] ?? '07:50-08:00';
            $timeParts = explode('-', $firstTimeRange);
            if (!empty($timeParts[0])) {
                $startTime = $baseTime = trim($timeParts[0]);
            }
            if (!empty($timeParts[1])) {
                $endTime = trim($timeParts[1]);
            }
        }

        $startTimeStr = $sendTimeStr = date('Y-m-d') . ' ' . $baseTime . ':00';
        $endTimeStr = date('Y-m-d') . ' ' . $endTime . ':00';
        $sendTimestamp = strtotime($sendTimeStr) + ($sendTimeDelay * 60);
        $startTimeTimestamp = strtotime($startTimeStr);
        $endTimeTimestamp = strtotime($endTimeStr);


        $allTaskInstall = [];
        $hasPendingCirclePublish = AiWechatCircleTask::where('user_id', (int)$task->user_id)
            ->where('send_status', 0)
            ->where('attachment_type', 'in', [2, 3])
            ->where('shanjian_video_task_id', (int)$task->id)
            ->count() > 0;

        foreach ($wechatIds as $wechat) {
            if (self::updateExistingWechatCirclePublishTask(
                $task,
                $wechat,
                $videoUrl,
                $content,
                $execTime,
                $firstTimeRange,
                $sendTimestamp,
                $startTimeTimestamp,
                $endTimeTimestamp
            )) {
                continue;
            }
            if ($hasPendingCirclePublish) {
                continue;
            }

            $taskConfig = AiWechatCircleTaskConfig::create([
                'user_id' => $task->user_id,
                'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                'content' => $content,
                'attachment_type' => 2,
                'attachment_content' => [$videoUrl],
                'wechat_ids' => [$wechat['account']],
                'status' => 1,
                'auto_type' => 1,
                'shanjian_video_task_id' => $task->id,
                'date' => date('Y-m-d'),
                'persona_id' => $task->persona_id,
                'time_config' => $firstTimeRange,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $circleTask = AiWechatCircleTask::create([
                'user_id' => $task->user_id,
                'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                'task_config_id' => $taskConfig->id,
                'device_code' => $task->device_code,
                'wechat_id' => $wechat['account'],
                'task_id' => time() . rand(100, 999),
                'task_type' => 1,
                'auto_type' => 1,
                'shanjian_video_task_id' => $task->id,
                'persona_id' => $task->persona_id,
                'content' => $content,
                'attachment_type' => 2,
                'attachment_content' => [$videoUrl],
                'send_time' => date('Y-m-d H:i:s', $sendTimestamp),
                'date' => date('Y-m-d H:i:s', time()),
                'send_status' => 0,
                'create_time' => time()
            ]);

            $allTaskInstall[] = [
                'user_id' => $task->user_id,
                'device_code' => $task->device_code,
                'nickname' => $wechat['nickname'],
                'avatar' => $wechat['avatar'],
                'persona_id' => $task->persona_id,
                'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
                'account' => $wechat['account'],
                'account_type' => 1,
                'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                'status' => 0,
                'auto_type' => 1,
                'day' => date('Y-m-d', $sendTimestamp),
                'time_config' => json_encode($execTime, JSON_UNESCAPED_UNICODE),
                'start_time' => $startTimeTimestamp,
                'end_time' => $endTimeTimestamp,
                'sub_task_id' => $taskConfig->id,
                'sub_data_id' => $circleTask->id,
                'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
                'create_time' => time(),
            ];

            TaskLogic::updateWechatRpaTaskTime($task->device_code, $sendTimestamp);
            if ($task instanceof \think\Model) {
                $task->is_publish = 1;
                $task->update_time = time();
                $task->save();
            }
        }

        if (!empty($allTaskInstall)) {
            TaskLogic::add($allTaskInstall);
            Log::channel('wechatCircle')->write('自动化朋友圈视频合成创建发布任务' . json_encode([
                'user_id' => (int)$task->user_id,
                'device_code' => (string)$task->device_code,
                'shanjian_video_task_id' => (int)$task->id,
                'shanjian_task_id' => (string)$task->task_id,
                'circle_task_count' => count($allTaskInstall),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return true;
    }

    private static function findExistingWechatCirclePublishTasks($task)
    {
        $taskIds = [(int)$task->id];
        if ((int)($task->origin_task_id ?? 0) > 0) {
            $taskIds[] = (int)$task->origin_task_id;
        }
        $taskIds = array_values(array_unique(array_filter($taskIds)));
        $userId = (int)$task->user_id;

        return AiWechatCircleTask::where('user_id', $userId)
            ->where('send_status', 0)
            ->where('attachment_type', 'in', [2, 3])
            ->where(function ($query) use ($taskIds, $userId) {
                $query->where('shanjian_video_task_id', 'in', $taskIds)
                    ->whereOr('task_config_id', 'in', function ($query) use ($taskIds, $userId) {
                        $query->name('ai_wechat_circle_task_config')
                            ->where('user_id', $userId)
                            ->where('shanjian_video_task_id', 'in', $taskIds)
                            ->field('id');
                    });
            })
            ->select();
    }

    private static function replaceExistingWechatCirclePublishVideo($task, string $videoUrl, $circleTasks): bool
    {
        $now = time();
        $attachmentContent = json_encode([$videoUrl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $configIds = [];
        $circleTaskIds = [];

        foreach ($circleTasks as $circleTask) {
            $circleTaskIds[] = (int)$circleTask->id;
            if ((int)$circleTask->task_config_id > 0) {
                $configIds[] = (int)$circleTask->task_config_id;
            }
        }

        $circleTaskIds = array_values(array_unique(array_filter($circleTaskIds)));
        if (!empty($circleTaskIds)) {
            Db::name('ai_wechat_circle_task')
                ->where('user_id', (int)$task->user_id)
                ->where('id', 'in', $circleTaskIds)
                ->where('send_status', 0)
                ->where('attachment_type', 'in', [2, 3])
                ->update([
                    'attachment_content' => $attachmentContent,
                    'shanjian_video_task_id' => (int)$task->id,
                    'update_time' => $now,
                ]);
        }

        if (empty($circleTaskIds)) {
            Log::channel('wechatCircle')->warning('自动化朋友圈视频合成替换原发布任务视频失败，未找到待更新任务' . json_encode([
                'user_id' => (int)$task->user_id,
                'device_code' => (string)$task->device_code,
                'shanjian_video_task_id' => (int)$task->id,
                'shanjian_task_id' => (string)$task->task_id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return false;
        }

        $configIds = array_values(array_unique(array_filter($configIds)));
        $traceTaskIds = [(int)$task->id];
        if ((int)($task->origin_task_id ?? 0) > 0) {
            $traceTaskIds[] = (int)$task->origin_task_id;
        }
        $traceTaskIds = array_values(array_unique(array_filter($traceTaskIds)));
        Db::name('ai_wechat_circle_task_config')
            ->where('user_id', (int)$task->user_id)
            ->where(function ($query) use ($traceTaskIds, $configIds) {
                $query->where('shanjian_video_task_id', 'in', $traceTaskIds);
                if (!empty($configIds)) {
                    $query->whereOr('id', 'in', $configIds);
                }
            })
            ->update([
                'attachment_content' => $attachmentContent,
                'shanjian_video_task_id' => (int)$task->id,
                'update_time' => $now,
            ]);

        if ($task instanceof \think\Model && (int)$task->is_publish !== 1) {
            $task->is_publish = 1;
            $task->update_time = $now;
            $task->save();
        }

        Log::channel('wechatCircle')->write('自动化朋友圈视频合成替换原发布任务视频' . json_encode([
            'user_id' => (int)$task->user_id,
            'device_code' => (string)$task->device_code,
            'shanjian_video_task_id' => (int)$task->id,
            'shanjian_task_id' => (string)$task->task_id,
            'circle_task_ids' => $circleTaskIds,
            'task_config_ids' => $configIds,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return true;
    }

    private static function updateExistingWechatCirclePublishTask(
        $task,
        array $wechat,
        string $videoUrl,
        $content,
        array $execTime,
        string $firstTimeRange,
        int $sendTimestamp,
        int $startTimeTimestamp,
        int $endTimeTimestamp
    ): bool {
        $circleTask = AiWechatCircleTask::where('user_id', (int)$task->user_id)
            ->where('device_code', (string)$task->device_code)
            ->where('wechat_id', (string)$wechat['account'])
            ->where('send_status', 0)
            ->where('shanjian_video_task_id', (int)$task->id)
            ->order('id desc')
            ->findOrEmpty();

        if ($circleTask->isEmpty()) {
            return false;
        }

        $now = time();
        $taskConfigId = (int)$circleTask->task_config_id;
        $taskConfig = null;
        if ($taskConfigId > 0) {
            $taskConfig = AiWechatCircleTaskConfig::where('id', $taskConfigId)
                ->where('user_id', (int)$task->user_id)
                ->findOrEmpty();
            if ($taskConfig->isEmpty()) {
                $taskConfig = null;
                $taskConfigId = 0;
            }
        }

        if ($taskConfigId <= 0) {
            $taskConfig = AiWechatCircleTaskConfig::create([
                'user_id' => $task->user_id,
                'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                'content' => trim((string)$circleTask->content) !== '' ? $circleTask->content : $content,
                'attachment_type' => 2,
                'attachment_content' => [$videoUrl],
                'wechat_ids' => [$wechat['account']],
                'status' => 1,
                'auto_type' => 1,
                'shanjian_video_task_id' => $task->id,
                'date' => date('Y-m-d'),
                'persona_id' => $task->persona_id,
                'time_config' => $firstTimeRange,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $taskConfigId = (int)$taskConfig->id;
        } else {
            $taskConfig->save([
                'attachment_type' => 2,
                'attachment_content' => [$videoUrl],
                'status' => 1,
                'auto_type' => 1,
                'shanjian_video_task_id' => $task->id,
                'persona_id' => $task->persona_id,
                'update_time' => $now,
            ]);
        }

        $circleTask->save([
            'task_config_id' => $taskConfigId,
            'task_type' => 1,
            'auto_type' => 1,
            'shanjian_video_task_id' => $task->id,
            'persona_id' => $task->persona_id,
            'attachment_type' => 2,
            'attachment_content' => [$videoUrl],
            'update_time' => $now,
        ]);

        $deviceTask = SvDeviceTask::where('user_id', (int)$task->user_id)
            ->where('source', DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH)
            ->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_CIRCLE)
            ->where('sub_data_id', (int)$circleTask->id)
            ->findOrEmpty();

        $deviceTaskData = [
            'user_id' => $task->user_id,
            'device_code' => $task->device_code,
            'nickname' => $wechat['nickname'],
            'avatar' => $wechat['avatar'],
            'persona_id' => $task->persona_id,
            'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
            'task_scene' => DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
            'account' => $wechat['account'],
            'account_type' => 1,
            'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
            'status' => 0,
            'auto_type' => 1,
            'sub_task_id' => $taskConfigId,
            'sub_data_id' => $circleTask->id,
            'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
            'update_time' => $now,
        ];

        $sendAt = strtotime((string)$circleTask->getData('send_time')) ?: $sendTimestamp;
        if ($deviceTask->isEmpty()) {
            $deviceTaskData += [
                'day' => date('Y-m-d', $sendAt),
                'time_config' => json_encode($execTime, JSON_UNESCAPED_UNICODE),
                'start_time' => $startTimeTimestamp ?: $sendAt,
                'end_time' => $endTimeTimestamp ?: ($sendAt + 1800),
                'create_time' => $now,
            ];
            TaskLogic::add([$deviceTaskData]);
        } else {
            $deviceTask->save($deviceTaskData);
        }

        TaskLogic::updateWechatRpaTaskTime((string)$task->device_code, $sendAt);
        if ($task instanceof \think\Model) {
            $task->is_publish = 1;
            $task->update_time = $now;
            $task->save();
        }

        Log::channel('wechatCircle')->write('自动化朋友圈视频合成更新原发布任务' . json_encode([
            'user_id' => (int)$task->user_id,
            'device_code' => (string)$task->device_code,
            'shanjian_video_task_id' => (int)$task->id,
            'shanjian_task_id' => (string)$task->task_id,
            'circle_task_id' => (int)$circleTask->id,
            'task_config_id' => $taskConfigId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return true;
    }


    /**
     * 闪剪视频合成提交前记录请求时间与参数
     */
    private static function logShanjianCompositeSubmit(ShanjianVideoTask $task, string $scene, array $requestdata): void
    {
        $logData = [
            'request_time' => date('Y-m-d H:i:s'),
            'task_id' => $task->task_id,
            'user_id' => $task->user_id,
            'shanjian_type' => $task->shanjian_type,
            'scene' => $scene,
            'params' => $requestdata,
        ];
        Log::channel('shanjiannotice')->write('[闪剪合成提交] ' . json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Minimax 音色：将 ASR 对齐后的逐字结果作为 subtitle 提交给闪剪
     * 文档字段：subtitle[{startMs,endMs,text}]，text 为单字符
     */
    private static function appendMinimaxSubtitle(array &$requestdata, $extra): void
    {
        $extraArr = is_array($extra) ? $extra : (json_decode((string)$extra, true) ?: []);
        $words = $extraArr['timed_words'] ?? ($extraArr['subtitle'] ?? []);
        if (empty($words) || !is_array($words)) {
            return;
        }

        $subtitle = [];
        foreach ($words as $word) {
            if (!is_array($word)) {
                continue;
            }
            $text = (string)($word['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $subtitle[] = [
                'text'    => $text,
                'startMs' => (int)($word['startMs'] ?? $word['start'] ?? 0),
                'endMs'   => (int)($word['endMs'] ?? $word['end'] ?? 0),
            ];
        }
        if (!empty($subtitle)) {
            $requestdata['subtitle'] = $subtitle;
        }
    }

    /**
     * MiniMax 音色不能作为闪剪 speakerId。
     * 无 audio_url 时禁止下发；已关联 minimax_task 则等待 TTS；
     * 误走普通建单的自动任务则补建 MiniMax TTS 并改为占位(status=-1)。
     *
     * @return bool true=可继续用 speakerId/文案下发；false=本轮已处理应跳过
     */
    private static function guardMinimaxRequiresAudio(ShanjianVideoTask $task): bool
    {
        $audioUrl = trim((string)($task->audio_url ?? ''));
        if ($audioUrl !== '') {
            return true;
        }

        $voiceId = trim((string)($task->voice_id ?? ''));
        if ($voiceId === '') {
            return true;
        }

        if (!ShanjianVideoSettingLogic::isMinimaxVoiceId($voiceId, (int)$task->user_id)) {
            return true;
        }

        $minimaxTaskId = (int)($task->minimax_task_id ?? 0);
        $logPayload = [
            'task_id' => $task->task_id,
            'db_id' => (int)$task->id,
            'voice_id' => $voiceId,
            'minimax_task_id' => $minimaxTaskId,
            'status' => (int)$task->status,
            'auto_type' => (int)$task->auto_type,
            'shanjian_type' => (int)$task->shanjian_type,
        ];

        // 已挂起等待 TTS/ASR
        if ($minimaxTaskId > 0 || (int)$task->status === -1) {
            ShanjianVideoTask::where('id', (int)$task->id)->update(['update_time' => time()]);
            Log::channel('ipVideoSynthesis')->write('拦截MiniMax空音频下发(等待TTS)' . json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return false;
        }

        // 误把 MiniMax 当 speakerId 建出的可下发任务：自动补建 TTS 占位，避免反复 40000
        $msg = trim((string)($task->msg ?? ''));
        if ((int)$task->auto_type === 1 && $msg !== '' && (int)$task->video_setting_id > 0) {
            try {
                $minimaxTask = ShanjianVideoSettingLogic::createAudioTask(
                    (int)$task->video_setting_id,
                    $voiceId,
                    [['content' => $msg]],
                    (int)$task->user_id
                );
                $newMinimaxTaskId = (int)($minimaxTask->id ?? 0);
                ShanjianVideoTask::where('id', (int)$task->id)
                    ->where('status', 0)
                    ->update([
                        'status' => -1,
                        'minimax_task_id' => $newMinimaxTaskId,
                        'audio_url' => '',
                        'remark' => 'MiniMax音色已补建音频任务，等待TTS',
                        'update_time' => time(),
                    ]);
                $logPayload['minimax_task_id'] = $newMinimaxTaskId;
                Log::channel('ipVideoSynthesis')->write('拦截MiniMax空音频下发(已补建TTS)' . json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return false;
            } catch (\Throwable $e) {
                $logPayload['error'] = $e->getMessage();
                Log::channel('ipVideoSynthesis')->write('拦截MiniMax空音频下发(补建TTS失败)' . json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        $task->status = 2;
        $task->remark = 'MiniMax音色需先合成音频，不能作为speakerId直接提交';
        $task->update_time = time();
        $task->save();
        self::updateVideoSettingStatus($task->video_setting_id, false);
        Log::channel('ipVideoSynthesis')->write('拦截MiniMax空音频下发(已标记失败)' . json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return false;
    }

    /**
     * 背景音乐打包规则：无 URL 或 volume=0 时关闭开关
     */
    private static function buildBackgroundMusicRule(string $musicUrl, float $volume): array
    {
        $musicUrl = trim($musicUrl);
        $volume = max(0.0, min(1.0, round($volume, 1)));
        // volume=0 视为静音关 BGM，与无 URL 一样关闭 audioSwitch
        if ($musicUrl === '' || $volume <= 0) {
            return [
                'audioSwitch' => false,
                'volume' => $volume,
            ];
        }
        return [
            'audioSwitch' => true,
            'volume' => $volume,
            'audioUrl' => $musicUrl,
        ];
    }

    /**
     * 闪剪 TTS：speakerId 路径下发语速；已有 audioUrl 时跳过
     */
    private static function appendSpeakerSpeedRatio(array &$requestdata, $extra): void
    {
        if (!empty($requestdata['audioUrl']) || empty($requestdata['speakerId'])) {
            return;
        }
        $extraArr = is_array($extra) ? $extra : (json_decode((string)$extra, true) ?: []);
        $speedRatio = $extraArr['speed_ratio'] ?? 1;
        if (!is_numeric($speedRatio)) {
            $speedRatio = 1;
        }
        $speedRatio = round((float)$speedRatio, 1);
        if ($speedRatio < 0.5 || $speedRatio > 2) {
            $speedRatio = 1.0;
        }
        $requestdata['speakerExtra'] = array_merge(
            is_array($requestdata['speakerExtra'] ?? null) ? $requestdata['speakerExtra'] : [],
            ['speedRatio' => $speedRatio]
        );
    }

    /**
     * 提交闪剪视频合成任务
     */
    private static function submitCompositeToShanjian(ShanjianVideoTask $task, string $scene, array $requestdata): array
    {
        self::logShanjianCompositeSubmit($task, $scene, $requestdata);
        return self::requestUrl($requestdata, $scene, $task->user_id, $task->task_id);
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::Shanjian();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::COPYWRITING_CREATE => ['shanjian_copywriting_create', AccountLogEnum::TOKENS_DEC_COZE_TEXT],
                self::SHANJIAN_VIDEO => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
                self::SHANJIAN_REALMAN_BROADCAST => ['shanjian_realman_broadcast', AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN],
                self::SHANJIAN_BROADCAST_MIXCUT => ['shanjian_broadcast_mixcut', AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN],
                self::SHANJIAN_NEWS_MIXCUT => ['shanjian_news_mixcut', AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN],
                self::SHANJIAN_VIRTUALMAN => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
            }; //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene); // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now'] = time();
             Log::channel('shanjiannotice')->write('定时任务合成视频(无包装)接口请求' . json_encode([
                        'scene' => $scene,
                        'user_id' => $userId,
                        'task_id' => $taskId,
                        'request' => $request
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            switch ($scene) {
                case self::COPYWRITING_CREATE:

                    $response = $response->text($request);
                    break;
                case self::SHANJIAN_VIDEO:
                    $response = $response->virtualmanBroadcast($request);
                    break;
                case self::SHANJIAN_REALMAN_BROADCAST:
                    $response = $response->realmanBroadcast($request);
                    break;
                case self::SHANJIAN_BROADCAST_MIXCUT:
                    $response = $response->mixcutBroadcast($request);
                    break;
                case self::SHANJIAN_NEWS_MIXCUT:
                    $response = $response->newsMixcut($request);
                    break;
                case self::SHANJIAN_VIRTUALMAN:
                    $response =  $response->virtualman($request);
                   
                    break;
                default:
            } //成功响应，需要扣费
            Log::channel('shanjiannotice')->write('定时任务合成视频(无包装)接口返回' . json_encode([
                        'scene' => $scene,
                        'user_id' => $userId,
                        'task_id' => $taskId,
                        'request' => $request,
                        'response' => $response,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (isset($response['code']) && $response['code'] == 10000) {
                $duration = $response['data']['data']['duration'] ?? 0;
                $points = $unit;
                if ($points > 0) {
                    $break = true;
                    $extra = [];
                    switch ($scene) {
                        case self::COPYWRITING_CREATE:
                            $break = false;
                            $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SHANJIAN_VIDEO:
                            break;
                        case self::SHANJIAN_REALMAN_BROADCAST:
                            break;
                        case self::SHANJIAN_BROADCAST_MIXCUT:
                            break;
                        case self::SHANJIAN_NEWS_MIXCUT:
                            break;
                        default:
                    }
                    if ($break) {
                        return $response['data'] ?? [];
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
                return $response['data'] ?? [];
            }
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * 处理视频合成任务
     * @param string $taskId 任务ID
     * @return void
     */
    public static function compositeVideoCron(string $taskId = '')
    {
        $setPublish = false;
        try {

            // 获取待处理的任务，限制5条
            $tasks = ShanjianVideoTask::where(function ($q) use ($taskId) {
                // 第一组条件
                $q->where('status', 0);

                if (!empty($taskId)) {
                    $q->where('task_id', $taskId);
                }
            })->whereIn('thumb_status', [2, 3, 4])
                ->where('tries', '<', 10)
                ->where('queue_status', '<>', ShanjianQueueService::STATUS_WAITING)
                ->order('tries DESC, update_time ASC, id ASC')
                ->limit(5)
                ->select();

            if ($tasks->isEmpty()) {
                return;
            }

            foreach ($tasks as $taskval) {
                try {
                    $task = ShanjianVideoTask::where('task_id', $taskval->task_id)
                        ->lock(true)
                        ->find();

                    if (!$task) {
                        Log::channel('shanjiannotice')->write('任务已删除或不存在: ' . $taskval->task_id);
                        continue;
                    }

                    if ($task->status != 0) {
                        Log::channel('shanjiannotice')->write('任务状态已变更，跳过处理: ' . $taskval->task_id . ', 当前状态: ' . $task->status);
                        continue;
                    }

                    // 蝉镜引擎 type5 桥接任务由 HumanVideoTask/cron 合成，禁止误交闪剪 virtualman
                    $taskExtra = is_array($task->extra)
                        ? $task->extra
                        : (json_decode((string)($task->extra ?? ''), true) ?: []);
                    if ((int)$task->shanjian_type === 5
                        && (
                            (int)($taskExtra['engine_type'] ?? 0) === ShanjianVideoSettingLogic::ENGINE_TYPE_CHANJING
                            || ($taskExtra['waiting_engine'] ?? '') === 'chanjing'
                        )
                    ) {
                        Log::channel('shanjiannotice')->write('跳过蝉镜桥接任务闪剪下发 task_id=' . $task->task_id);
                        continue;
                    }

                    // ===== 下发前门禁:转码就绪 + 分辨率 =====
                    // 设计:建 task 阶段不拦截(创作记录立即显示"生成中"=status0),真正下发闪剪前才把关。
                    //   - 转码未就绪 → 抛 MaterialNotReadyException → 保持 status=0(继续"生成中"),不增 tries,等下一轮 cron 自动下发
                    //   - 全部转码结束后统计失败素材,从 iw_shanjian_video_task.material 删除并记录日志
                    //   - 分辨率超标 → 自动投递转码并抛 MaterialNotReadyException(同未就绪);仅无法投递时才 return false 标失败
                    //   - 通过 → 继续下发
                    $submitMaterialArr = [];
                    $submitMaterials = [];
                    $submitPic = (string)$task->pic;
                    $requiresMaterials = in_array((int)$task->shanjian_type, [3, 4], true);
                    try {
                        $materialArr = is_string($task->material)
                            ? (json_decode($task->material, true) ?: [])
                            : (array)$task->material;
                        if ($requiresMaterials && !self::hasValidMaterialFileUrl($materialArr)) {
                            self::failEmptyMaterialTask($task);
                            continue;
                        }
                        if (empty($materialArr)) {
                            // 部分视频任务没有素材;先保留封面等预检,通过后再跳过素材转码收敛/失败删除判断。
                            $preflightOk = \app\api\logic\shanjian\ShanjianVideoSettingLogic::preflightMaterials(
                                ['material' => []],
                                ['pic' => $submitPic]
                            );
                        } else {
                            $submitMaterialArr = self::prepareSubmitMaterialsAfterTranscode(
                                $task,
                                $materialArr
                            );
                            $preflightOk = \app\api\logic\shanjian\ShanjianVideoSettingLogic::preflightMaterials(
                                ['material' => $submitMaterialArr],
                                ['pic' => $submitPic]
                            );
                        }
                        if (!$preflightOk) {
                            $task->status = 2;
                            $task->remark = \app\api\logic\shanjian\ShanjianVideoSettingLogic::getError() ?: '素材预检未通过';
                            $task->save();
                            self::updateVideoSettingStatus($task->video_setting_id, false);
                            Log::channel('shanjiannotice')->write('素材分辨率预检失败,标记失败 task_id=' . $task->task_id . ' - ' . $task->remark);
                            continue;
                        }
                    } catch (MaterialNotReadyException $e) {
                        // 转码未就绪:保持"生成中"(status=0),刷新 update_time 让老任务轮转到队尾,避免占满本轮 limit。
                        ShanjianVideoTask::where('id', $task->id)->update(['update_time' => time()]);
                        Log::channel('shanjiannotice')->write('素材转码未就绪,保持生成中等下轮 task_id=' . $task->task_id . ' - ' . $e->getMessage());
                        continue;
                    }
                    if (!empty($submitMaterialArr)) {
                        $beforeCount = count($submitMaterialArr);
                        // 手动任务(auto_type=0)前端已限制总时长，不做素材时长过滤；自动化任务仍走门禁
                        $submitMaterials = ((int)$task->auto_type === 1)
                            ? ShanjianVideoSettingLogic::trimMaterialsByDuration(array_values($submitMaterialArr))
                            : array_values($submitMaterialArr);
                        // 无 fileUrl 的项不参与下发
                        $submitMaterials = array_values(array_filter($submitMaterials, static function ($m) {
                            return is_array($m) && !empty($m['fileUrl']);
                        }));
                        if (count($submitMaterials) !== $beforeCount) {
                            Log::channel('shanjiannotice')->write(
                                '素材总时长裁剪 task_id=' . $task->task_id
                                . ' auto_type=' . (int)$task->auto_type
                                . ' before=' . $beforeCount
                                . ' after=' . count($submitMaterials)
                            );
                        }
                    }
                    if ($requiresMaterials && !self::hasValidMaterialFileUrl($submitMaterials)) {
                        self::failEmptyMaterialTask($task);
                        continue;
                    }

                    $extra = $task->extra;
                    $volume =  $extra['volume'] ?? 0.3;
                    $volume = (float)$volume;
                    $materialSoundSwitch = self::normalizeSoundSwitch($extra['soundSwitch'] ?? false);
                    if (in_array((int)$task->shanjian_type, [1, 3, 4], true) && !empty($submitMaterials)) {
                        $submitMaterials = self::applyMaterialSoundSwitch($submitMaterials, $materialSoundSwitch);
                    }
                    if (in_array((int)$task->shanjian_type, [3, 4], true)
                        && !ShanjianVideoSettingLogic::hasNonEmptyMaterialFileUrl($submitMaterials)
                    ) {
                        $task->status = 2;
                        $task->remark = '素材不能为空';
                        $task->update_time = time();
                        $task->save();
                        self::updateVideoSettingStatus($task->video_setting_id, false);
                        self::handleMaterialUseFailure($task);
                        Log::channel('shanjiannotice')->write('素材为空,跳过闪剪下发 task_id=' . $task->task_id);
                        continue;
                    }

                    // MiniMax 音色无 audio_url 时禁止走 speakerId，避免闪剪 40000 校验失败
                    if (in_array((int)$task->shanjian_type, [1, 3, 5], true)
                        && !self::guardMinimaxRequiresAudio($task)
                    ) {
                        continue;
                    }

                    $videoDuration = $extra['videoDuration'] ?? 0;
                    switch ($task->shanjian_type) {
                        case 1:
                            $duration = self::billingDurationFromTask($task);
                            $unit = self::withTaskBillingTeam($task, static function () use ($task, $duration) {
                                return TokenLogService::checkToken($task->user_id, 'human_video_shanjian', $duration);
                            });
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_VIDEO;
                            $volume =  $extra['volume'] ?? 0.4;
                            $requestdata = [
                                'styleId' => $task->clip_id,
                                'virtualmanId' => $task->anchor_id,
                                'packRules' => [
                                    "backgroundMusic" => self::buildBackgroundMusicRule((string)$task->music_url, (float)$volume),
                                ],
                                'processRules' => [
                                    "watermarkShow" => false,
                                ],
                                'materialSoundSwitch' => $materialSoundSwitch,
                                'duration' => $duration,
                            ];
                            if ($task->title != '') {
                                $requestdata['title'] = $task->title;
                            }
                            if ($task->audio_url != '') {
                                $requestdata['audioUrl'] = $task->audio_url;
                                // minimax：提交 ASR 对齐后的 subtitle
                                self::appendMinimaxSubtitle($requestdata, $extra);
                            } else {
                                $requestdata['speakerId'] =  $task->voice_id;
                                $requestdata['content'] =  $task->msg;
                                self::appendSpeakerSpeedRatio($requestdata, $extra);
                            }
                            if ($task->card_name != '') {
                                $requestdata['introduceCard']['name'] = $task->card_name;
                            }
                            if ($task->card_introduced != '') {
                                $requestdata['introduceCard']['description'] =  $task->card_introduced;
                            }

                            if (!empty($submitMaterials)) {
                                $requestdata['materials'] = $submitMaterials;
                            }
                            if (trim($submitPic) != '' && $task->thumb_status == 2) {
                                $requestdata['processRules']['firstFrameCover'] = [
                                    'coverSwitch' => true,
                                    'resultImageUrl' => FileService::getFileUrl($submitPic)
                                ];
                            }
                            $response = self::submitCompositeToShanjian($task, $scene, $requestdata);
                            if ($task->persona_id == 0) {
                                Log::channel('shanjiannotice')->write('合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            } else {
                                $logmsg = '设备号' . $task->device_code . '-人设' . AiPersona::formatLabel(null, (int)$task->persona_id) . '-视频类型' . $task->shanjian_type . '-合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                Log::channel('ipVideoSynthesis')->write($logmsg);
                            }

                            if (!self::acceptQueueSubmission(
                                $task,
                                $response,
                                (float)$unit,
                                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
                                '数字人口播混剪视频生成'
                            )) {
                                self::markCompositeSubmitFailure($task, self::extractShanjianResponseMessage($response), true);
                                continue 2;
                            }
                            break;

                        case 2:
                            $duration = self::billingDurationFromTask($task);
                            // 包装派生任务按创建时企业空间预检(用户可能已切个人空间)
                            $unit = self::withTaskBillingTeam($task, static function () use ($task, $duration) {
                                return TokenLogService::checkToken($task->user_id, 'shanjian_realman_broadcast', $duration);
                            });
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_REALMAN_BROADCAST;
                            $requestdata = [
                                'styleId' => $task->clip_id,
                                'videoUrl' => $task->anchor_id,
                                'packRules' => [
                                    "backgroundMusic" => self::buildBackgroundMusicRule((string)$task->music_url, (float)$volume),
                                ],
                                'processRules' => [
                                    "watermarkShow" => false,
                                ],
                                'duration' => $duration,
                            ];
                            if (!empty($submitMaterials)) {
                                $requestdata['materials'] = $submitMaterials;
                            }
                            if ($materialSoundSwitch) {
                                $requestdata['materialSoundSwitch'] = true;
                            }
                            if ($task->card_name != '') {
                                $requestdata['introduceCard']['name'] = $task->card_name;
                            }
                            if ($task->card_introduced != '') {
                                $requestdata['introduceCard']['description'] =  $task->card_introduced;
                            }
                            if (trim($submitPic) != '' && $task->thumb_status == 2) {
                                $requestdata['processRules']['firstFrameCover'] = [
                                    'coverSwitch' => true,
                                    'resultImageUrl' => $submitPic,
                                ];
                            }

                            $response = self::submitCompositeToShanjian($task, $scene, $requestdata);
                            if ($task->persona_id == 0) {
                                Log::channel('shanjiannotice')->write('合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            } else {
                                $logmsg = '设备号' . $task->device_code . '-人设' . AiPersona::formatLabel(null, (int)$task->persona_id) . '-视频类型' . $task->shanjian_type . '-合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                Log::channel('ipVideoSynthesis')->write($logmsg);
                            }

                            if (!self::acceptQueueSubmission(
                                $task,
                                $response,
                                (float)$unit,
                                AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
                                '真人口播混剪视频生成'
                            )) {
                                self::markCompositeSubmitFailure($task, self::extractShanjianResponseMessage($response));
                                continue 2;
                            }
                            break;
                        case 3:
                            $duration = self::billingDurationFromTask($task);
                            $unit = self::withTaskBillingTeam($task, static function () use ($task, $duration) {
                                return TokenLogService::checkToken($task->user_id, 'shanjian_broadcast_mixcut', $duration);
                            });
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_BROADCAST_MIXCUT;

                            $requestdata = [
                                'styleId' => $task->clip_id,
                                'packRules' => [
                                    "backgroundMusic" => self::buildBackgroundMusicRule((string)$task->music_url, (float)$volume),
                                ],
                                'processRules' => [
                                    "watermarkShow" => false,
                                ],
                                'materialSoundSwitch' => $materialSoundSwitch,
                                'duration' => $duration,
                            ];

                            if ($task->title != '') {
                                $requestdata['title'] = $task->title;
                            }
                            if ($task->audio_url != '') {
                                $requestdata['audioUrl'] = $task->audio_url;
                                // minimax：提交 ASR 对齐后的 subtitle
                                self::appendMinimaxSubtitle($requestdata, $extra);
                            } else {
                                $requestdata['speakerId'] =  $task->voice_id;
                                $requestdata['content'] =  $task->msg;
                                self::appendSpeakerSpeedRatio($requestdata, $extra);
                            }
                            if ($task->card_name != '') {
                                $requestdata['introduceCard']['name'] = $task->card_name;
                            }
                            if ($task->card_introduced != '') {
                                $requestdata['introduceCard']['description'] =  $task->card_introduced;
                            }
                            if (!empty($submitMaterials)) {
                                $requestdata['materials'] = $submitMaterials;
                            }
                            if (trim($submitPic) != '' && $task->thumb_status == 2) {
                                $requestdata['processRules']['firstFrameCover'] = [
                                    'coverSwitch' => true,
                                    'resultImageUrl' => $submitPic,
                                ];
                            }
                            $response = self::submitCompositeToShanjian($task, $scene, $requestdata);
                            if ($task->persona_id == 0) {
                                Log::channel('shanjiannotice')->write('合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            } else {
                                $logmsg = '设备号' . $task->device_code . '-人设' . AiPersona::formatLabel(null, (int)$task->persona_id) . '-视频类型' . $task->shanjian_type . '-合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                Log::channel('ipVideoSynthesis')->write($logmsg);
                            }

                            if (!self::acceptQueueSubmission(
                                $task,
                                $response,
                                (float)$unit,
                                AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
                                '素材混剪视频生成'
                            )) {
                                self::markCompositeSubmitFailure($task, self::extractShanjianResponseMessage($response));
                                continue 2;
                            }
                            break;
                        case 4:
                            $duration = self::billingDurationFromTask($task);
                            $unit = self::withTaskBillingTeam($task, static function () use ($task, $duration) {
                                return TokenLogService::checkToken($task->user_id, 'shanjian_news_mixcut', $duration);
                            });

                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_NEWS_MIXCUT;
                            $title = str_replace('\\n', "\n", $task->title);;
                            $requestdata = [
                                'styleId' => $task->clip_id,
                                'title' => $title,
                                'packRules' => [
                                    "backgroundMusic" => self::buildBackgroundMusicRule((string)$task->music_url, (float)$volume),
                                ],
                                'processRules' => [
                                    "watermarkShow" => false,
                                ],
                                'materialSoundSwitch' => $materialSoundSwitch,
                                'duration' => $duration,
                            ];
                            if ($task->card_name != '') {
                                $requestdata['introduceCard']['name'] = $task->card_name;
                            }
                            if ($task->card_introduced != '') {
                                $requestdata['introduceCard']['description'] =  $task->card_introduced;
                            }
                            if (!empty($submitMaterials)) {
                                $requestdata['materials'] = $submitMaterials;
                            }
                            if ($videoDuration > 0) {
                                $requestdata['processRules']['videoDuration'] = (int)$videoDuration;
                            }
                            if (trim($submitPic) != '' && $task->thumb_status == 2) {
                                $requestdata['processRules']['firstFrameCover'] = [
                                    'coverSwitch' => true,
                                    'resultImageUrl' => $submitPic,
                                ];
                            }
                            $response = self::submitCompositeToShanjian($task, $scene, $requestdata);
                            if ($task->persona_id == 0) {
                                Log::channel('shanjiannotice')->write('新闻合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            } else {
                                $logmsg = '设备号' . $task->device_code . '-人设' . AiPersona::formatLabel(null, (int)$task->persona_id) . '-视频类型' . $task->shanjian_type . '-合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                Log::channel('ipVideoSynthesis')->write($logmsg);
                            }
                            if (!self::acceptQueueSubmission(
                                $task,
                                $response,
                                (float)$unit,
                                AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
                                '新闻体混剪视频生成'
                            )) {
                                self::markCompositeSubmitFailure($task, self::extractShanjianResponseMessage($response));
                                continue 2;
                            }
                            break;
                        case 5:
                            $duration = self::billingDurationFromTask($task);
                            $unit = self::withTaskBillingTeam($task, static function () use ($task, $duration) {
                                return TokenLogService::checkToken($task->user_id, 'human_video_shanjian', $duration);
                            });
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_VIRTUALMAN;
                            // 无包装请求: 仅数字人 + 音色/文案(或音频) + 标题 + 介绍卡片, 不传包装模板/素材混剪参数
                            $requestdata = [
                                'virtualmanId' => $task->anchor_id,
                                'processRules' => [
                                    "watermarkShow" => false,
                                ],
                                'duration' => $duration,
                            ];
                            
                            if ($task->audio_url != '') {
                                $requestdata['audioUrl'] = $task->audio_url;
                                // minimax：提交 ASR 对齐后的 subtitle
                                self::appendMinimaxSubtitle($requestdata, $extra);
                            } else {
                                $requestdata['speakerId'] = $task->voice_id;
                                $requestdata['text'] = $task->msg;
                                self::appendSpeakerSpeedRatio($requestdata, $extra);
                            }
                            if ($task->card_name != '') {
                                $requestdata['introduceCard']['name'] = $task->card_name;
                            }
                            if ($task->card_introduced != '') {
                                $requestdata['introduceCard']['description'] = $task->card_introduced;
                            }
                            if (trim($task->pic) != '' && $task->thumb_status == 2) {
                                $requestdata['processRules']['firstFrameCover'] = [
                                    'coverSwitch' => true,
                                    'resultImageUrl' => FileService::getFileUrl($task->pic),
                                ];
                            }
                            $response = self::requestUrl($requestdata, $scene, $task->user_id, $task->task_id);
                            if ($task->persona_id == 0) {
                                Log::channel('shanjiannotice')->write('合成视频(无包装)' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            } else {
                                $logmsg = '设备号' . $task->device_code . '-人设id' . $task->persona_id . '-视频类型' . $task->shanjian_type . '-合成视频' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                Log::channel('ipVideoSynthesis')->write($logmsg);
                            }

                            if (!self::acceptQueueSubmission(
                                $task,
                                $response,
                                (float)$unit,
                                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
                                '数字人口播视频(无包装)生成'
                            )) {
                                self::markCompositeSubmitFailure($task, self::extractShanjianResponseMessage($response), true);
                                continue 2;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    if ($task->persona_id == 0) {
                        Log::channel('shanjiannotice')->write('合成视频错误' . $e->getMessage());
                    } else {
                        $logmsg = '设备号' . $task->device_code . '-人设' . AiPersona::formatLabel(null, (int)$task->persona_id) . '-视频类型' . $task->shanjian_type . '-合成视频错误' . $e->getMessage();
                        Log::channel('ipVideoSynthesis')->write($logmsg);
                    }

                    if (self::isClipStyleNotFoundError($e->getMessage())) {
                        self::markCompositeSubmitFailure($task, $e->getMessage(), true);
                        continue;
                    }

                    // 算力不足(4059):立即失败,不占用10次重试(2026-08-05 产品确认);充值后需重新提交任务
                    if ((int)$e->getCode() === 4059) {
                        $task->tries = 10;
                        $task->status = 2;
                        $task->remark = self::formatCompositeVideoErrorMessage($e->getMessage());
                        $setPublish = self::updateVideoSettingStatus($task->video_setting_id, false);
                        self::handleMaterialUseFailure($task);
                        $task->save();
                        continue;
                    }

                    $task->tries = $task->tries + 1;
                    $task->remark = self::formatCompositeVideoErrorMessage($e->getMessage());
                    if ($task->tries == 10) {
                        $task->status = 2;
                        // 更新视频设置表的错误计数和状态
                        $setPublish = self::updateVideoSettingStatus($task->video_setting_id, false);
                        self::handleMaterialUseFailure($task);
                    }
                    $task->save();
                }
            }
        } catch (\Exception $e) {
            Log::channel('shanjiannotice')->info('批量处理视频任务失败' . $e->getMessage());
        }
    }


    /**
     * 手动下载/转存成片到本地（或站点存储）
     * download_status: 0待下载 1下载中 2成功 3失败
     */
    public static function downloadResult(int $id): bool
    {
        return self::transferVideoResult($id, true, '手动');
    }

    /**
     * 自动转存成片，不依赖当前登录用户。
     */
    public static function autoDownloadResult(int $id): bool
    {
        return self::transferVideoResult($id, false, '自动', true);
    }

    /**
     * 回收卡在「下载中」过久的任务（进程被杀/超时未更新状态）。
     */
    public static function recoverStuckDownloading(): int
    {
        $threshold = time() - self::DOWNLOAD_STUCK_TIMEOUT;
        $since = time() - self::AUTO_DOWNLOAD_WITHIN_SECONDS;
        try {
            $affected = ShanjianVideoTask::where('status', ShanjianVideoTask::STATUS_SUCCESS)
                ->where('download_status', ShanjianVideoTask::DOWNLOAD_DOWNLOADING)
                ->where('update_time', '<', $threshold)
                ->where('create_time', '>=', $since)
                ->update([
                    'download_status' => ShanjianVideoTask::DOWNLOAD_FAILED,
                    'update_time' => time(),
                ]);
            $count = (int)$affected;
            if ($count > 0) {
                Log::channel('shanjiannotice')->warning('回收超时下载中成片任务数: ' . $count);
            }
            return $count;
        } catch (\Throwable $e) {
            Log::channel('shanjiannotice')->error('回收超时下载中成片异常: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 定时补偿下载待转存或下载失败的闪剪成片（仅创建时间 24 小时内）。
     */
    public static function autoDownloadPendingResults(int $limit = 5): bool
    {
        try {
            self::recoverStuckDownloading();

            $since = time() - self::AUTO_DOWNLOAD_WITHIN_SECONDS;
            $tasks = ShanjianVideoTask::where('status', ShanjianVideoTask::STATUS_SUCCESS)
                ->whereIn('download_status', [
                    ShanjianVideoTask::DOWNLOAD_PENDING,
                    ShanjianVideoTask::DOWNLOAD_FAILED,
                ])
                ->where('download_tries', '<', self::AUTO_DOWNLOAD_MAX_TRIES)
                ->where('create_time', '>=', $since)
                ->where(function ($query) {
                    $query->where('video_source_url', '<>', '')
                        ->whereOr('video_result_url', '<>', '');
                })
                ->order('id', 'asc')
                ->limit(max(1, $limit))
                ->select();

            foreach ($tasks as $task) {
                if (!self::autoDownloadResult((int)$task->id)) {
                    Log::channel('shanjiannotice')->warning(
                        '定时自动下载成片失败, task_id: ' . $task->task_id . ', err: ' . self::getError()
                    );
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjiannotice')->error('定时自动下载成片异常: ' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 转存成片到本地（或站点存储）。
     */
    private static function transferVideoResult(int $id, bool $checkUser, string $mode, bool $isAuto = false): bool
    {
        try {
            $query = ShanjianVideoTask::where('id', $id);
            if ($checkUser) {
                $query->where('user_id', self::$uid);
            }
            $task = $query->find();
            if (!$task) {
                self::setError('视频任务不存在');
                return false;
            }
            if ((int)$task->status !== ShanjianVideoTask::STATUS_SUCCESS) {
                self::setError('仅合成成功的任务可下载');
                return false;
            }
            // 注意：create_time 有获取器会格式化成日期字符串，时效判断必须取原始时间戳
            if ($isAuto && (int)$task->getData('create_time') < time() - self::AUTO_DOWNLOAD_WITHIN_SECONDS) {
                self::setError('超过自动下载时效（24小时）');
                return false;
            }

            $sourceUrl = trim((string)$task->getData('video_source_url'));
            if ($sourceUrl === '') {
                $sourceUrl = trim((string)$task->getData('video_result_url'));
            }
            if ($sourceUrl === '') {
                self::setError('暂无可下载的成片地址');
                return false;
            }

            // 已是本地路径：直接标成功
            if (!FileService::isRemoteHttpUrl($sourceUrl)
                && !FileService::isRemoteHttpUrl((string)$task->getData('video_result_url'))
            ) {
                if ((int)$task->download_status !== ShanjianVideoTask::DOWNLOAD_SUCCESS) {
                    $task->download_status = ShanjianVideoTask::DOWNLOAD_SUCCESS;
                    if (trim((string)$task->getData('video_source_url')) === '') {
                        $task->video_source_url = $sourceUrl;
                    }
                    $task->update_time = time();
                    $task->save();
                }
                self::$returnData = [
                    'id' => (int)$task->id,
                    'download_status' => ShanjianVideoTask::DOWNLOAD_SUCCESS,
                    'download_status_text' => ShanjianVideoTask::getDownloadStatusText(ShanjianVideoTask::DOWNLOAD_SUCCESS),
                    'video_source_url' => FileService::getFileUrl((string)$task->getData('video_source_url') ?: $sourceUrl),
                    'video_result_url' => FileService::getFileUrl((string)$task->getData('video_result_url')),
                ];
                return true;
            }

            // 下载中：未超时则拒绝；已超时则回收为失败后继续抢占
            if ((int)$task->download_status === ShanjianVideoTask::DOWNLOAD_DOWNLOADING) {
                $stuckBefore = time() - self::DOWNLOAD_STUCK_TIMEOUT;
                // update_time 有获取器，比较必须用原始时间戳
                if ((int)$task->getData('update_time') >= $stuckBefore) {
                    self::setError('正在下载中，请稍后刷新');
                    return false;
                }
                ShanjianVideoTask::where('id', $task->id)
                    ->where('download_status', ShanjianVideoTask::DOWNLOAD_DOWNLOADING)
                    ->where('update_time', '<', $stuckBefore)
                    ->update([
                        'download_status' => ShanjianVideoTask::DOWNLOAD_FAILED,
                        'update_time' => time(),
                    ]);
                $task = $query->find();
                if (!$task) {
                    self::setError('视频任务不存在');
                    return false;
                }
            }

            if ((int)$task->download_status === ShanjianVideoTask::DOWNLOAD_SUCCESS
                && !FileService::isRemoteHttpUrl((string)$task->getData('video_result_url'))
            ) {
                self::$returnData = [
                    'id' => (int)$task->id,
                    'download_status' => ShanjianVideoTask::DOWNLOAD_SUCCESS,
                    'download_status_text' => ShanjianVideoTask::getDownloadStatusText(ShanjianVideoTask::DOWNLOAD_SUCCESS),
                    'video_source_url' => FileService::getFileUrl((string)$task->getData('video_source_url') ?: $sourceUrl),
                    'video_result_url' => FileService::getFileUrl((string)$task->getData('video_result_url')),
                ];
                return true;
            }

            $lockKey = 'shanjian_video_download_' . $task->id;
            if (!self::acquireRedisLock($lockKey, 600)) {
                self::setError('正在下载中，请稍后刷新');
                return false;
            }

            $claimQuery = ShanjianVideoTask::where('id', $task->id)
                ->whereIn('download_status', [
                    ShanjianVideoTask::DOWNLOAD_PENDING,
                    ShanjianVideoTask::DOWNLOAD_FAILED,
                ]);
            if ($isAuto) {
                $claimQuery->where('download_tries', '<', self::AUTO_DOWNLOAD_MAX_TRIES);
            }
            $claimData = [
                'download_status' => ShanjianVideoTask::DOWNLOAD_DOWNLOADING,
                'video_source_url' => $sourceUrl,
                'update_time' => time(),
            ];
            if ($isAuto) {
                // 用 inc 保证表达式写入，避免 Raw 在部分 update 路径被丢掉
                $claimed = $claimQuery->inc('download_tries')->update($claimData);
            } else {
                $claimed = $claimQuery->update($claimData);
            }
            if (!$claimed) {
                self::releaseRedisLock($lockKey);
                self::setError($isAuto ? '自动下载重试次数已达上限或任务状态已变更' : '正在下载中或已下载完成，请刷新后查看');
                return false;
            }

            try {
                Log::channel('shanjiannotice')->write($mode . '下载成片开始' . json_encode([
                    'id' => (int)$task->id,
                    'task_id' => (string)$task->task_id,
                    'source_url' => $sourceUrl,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $localPath = FileService::downloadFileBySource($sourceUrl, 'video', 600);
                if ($localPath === '' || FileService::isRemoteHttpUrl($localPath)) {
                    ShanjianVideoTask::where('id', $task->id)->update([
                        'download_status' => ShanjianVideoTask::DOWNLOAD_FAILED,
                        'update_time' => time(),
                    ]);
                    self::setError('下载失败，请稍后重试');
                    return false;
                }

                ShanjianVideoTask::where('id', $task->id)->update([
                    'video_source_url' => $sourceUrl,
                    'video_result_url' => $localPath,
                    'download_status' => ShanjianVideoTask::DOWNLOAD_SUCCESS,
                    'update_time' => time(),
                ]);

                $task->video_result_url = $localPath;
                self::syncPendingPublishDetailVideo($task, $localPath);

                Log::channel('shanjiannotice')->write($mode . '下载成片成功' . json_encode([
                    'task_id' => $task->task_id,
                    'id' => $task->id,
                    'old' => $sourceUrl,
                    'new' => $localPath,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                self::$returnData = [
                    'id' => (int)$task->id,
                    'download_status' => ShanjianVideoTask::DOWNLOAD_SUCCESS,
                    'download_status_text' => ShanjianVideoTask::getDownloadStatusText(ShanjianVideoTask::DOWNLOAD_SUCCESS),
                    'video_source_url' => FileService::getFileUrl($sourceUrl),
                    'video_result_url' => FileService::getFileUrl($localPath),
                ];
                return true;
            } finally {
                self::releaseRedisLock($lockKey);
            }
        } catch (\Throwable $e) {
            try {
                if (!empty($id)) {
                    ShanjianVideoTask::where('id', $id)
                        ->where('download_status', ShanjianVideoTask::DOWNLOAD_DOWNLOADING)
                        ->update([
                            'download_status' => ShanjianVideoTask::DOWNLOAD_FAILED,
                            'update_time' => time(),
                        ]);
                }
            } catch (\Throwable $ignore) {
            }
            Log::channel('shanjiannotice')->error($mode . '下载成片异常 id=' . $id . ' err=' . $e->getMessage());
            self::setError('下载异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 定时主动查询闪剪 status 接口，补偿回调失败/锁超时未落库的任务（非队列）
     */
    public static function check()
    {
        try {
            $tasks = ShanjianVideoTask::where('status', ShanjianVideoTask::STATUS_PROCESSING)
                ->where('result_id', '<>', '')
                ->whereNotNull('result_id')
                ->where('create_time', '<=', strtotime('-120 minutes'))
                ->order('id', 'asc')
                ->limit(3)
                ->select();

            foreach ($tasks as $task) {
                $lockKey = 'shanjian_video_task_notify_' . $task->task_id;
                try {
                    if (!self::acquireRedisLock($lockKey, 180)) {
                        continue;
                    }

                    $params = ['taskId' => $task->result_id, 'task_id' => $task->task_id];
                    $response = \app\common\service\ToolsService::Shanjian()->status($params);
                    Log::channel('shanjiannotice')->write(
                        'check查询状态, task_id: ' . $task->task_id
                        . ', result_id: ' . $task->result_id
                        . ', 响应: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );

                    if (
                        !isset($response['code'])
                        || (isset($response['data']['status']) && $response['data']['status'] === 'processing')
                    ) {
                        self::releaseRedisLock($lockKey);
                        continue;
                    }

                    if (isset($response['code']) && in_array((int)$response['code'], [22901, 22902], true)) {
                        $notifyData = [
                            'task_id' => $task->task_id,
                            'status' => 'failed',
                            'errorMessage' => $response['message'] ?? '处理失败',
                        ];
                    } elseif (!empty($response['data']) && is_array($response['data'])) {
                        $notifyData = $response['data'];
                        $notifyData['task_id'] = $task->task_id;
                    } else {
                        self::releaseRedisLock($lockKey);
                        continue;
                    }

                    $ok = self::notify($notifyData);
                    if ($ok) {
                        self::keepRedisLock($lockKey, 20);
                    } else {
                        self::releaseRedisLock($lockKey);
                        Log::channel('shanjiannotice')->error(
                            'check补偿notify失败, task_id: ' . $task->task_id . ', err: ' . self::getError()
                        );
                    }
                } catch (\Exception $e) {
                    self::releaseRedisLock($lockKey);
                    $errorMsg = $e->getMessage();
                    if (strpos($errorMsg, 'Lock wait timeout exceeded') !== false) {
                        Log::channel('shanjiannotice')->warning('Check 任务锁等待超时，跳过: ' . ($task->task_id ?? ''));
                        continue;
                    }
                    Log::channel('shanjiannotice')->error(
                        'Check 方法处理任务失败, task_id: ' . ($task->task_id ?? '') . ', Error: ' . $errorMsg
                    );
                }
            }

            return true;
        } catch (\Exception | \think\db\exception\DataNotFoundException | \think\db\exception\ModelNotFoundException $e) {
            self::setError($e->getMessage());
            Log::channel('shanjiannotice')->error('Check 方法整体异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 处理闪剪封面回调
     */
    public static function covernotify($data)
    {
        try {
            if (!isset($data['task_id']) || empty($data['task_id'])) {
                self::setError('缺少任务ID');
                return false;
            }

            $thumbStatus = ShanjianVideoTask::where('task_id', $data['task_id'])->value('thumb_status');
            if ($thumbStatus !== null && in_array((int)$thumbStatus, [2, 3], true)) {
                return true;
            }

            $cover_url = '';
            if (($data['status'] ?? '') === 'succeed' && !empty($data['result']['imageUrl'])) {
                $old = $data['result']['imageUrl'];
                $cover_url = FileService::downloadFileBySource($old, 'image');
                Log::channel('shanjiannotice')->write('获取封面链接' . json_encode([
                    'old' => $old,
                    'new' => $cover_url,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $maxAttempts = 3;
            $lastError = '';
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                Db::startTrans();
                try {
                    $task = ShanjianVideoTask::where('task_id', $data['task_id'])
                        ->where('cover_result_id', $data['taskId'] ?? '')
                        ->where('thumb_status', 1)
                        ->lock(true)
                        ->find();
                    if (!$task) {
                        Db::commit();
                        return true;
                    }

                    $typeID = AccountLogEnum::TOKENS_DEC_SHANJIAN_AI_COVER;
                    if (isset($data['status'])) {
                        switch ($data['status']) {
                            case 'failed':
                                $task->thumb_status = 3;
                                $task->remark = $data['errorMessage'] ?? '处理失败';
                                $userId = $task->user_id;
                                $taskId = $task->task_id;
                                $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                                if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                                    $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                                    AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                                }
                                break;
                            case 'succeed':
                                $task->thumb_status = 2;
                                if ($cover_url !== '') {
                                    $task->pic = $cover_url;
                                }
                                break;
                        }
                    }
                    $task->update_time = time();
                    $task->save();
                    Db::commit();
                    return true;
                } catch (\Exception $e) {
                    self::safeRollback('covernotify');
                    $lastError = $e->getMessage();
                    if (self::isLockWaitTimeout($e) && $attempt < $maxAttempts) {
                        Log::channel('shanjiannotice')->warning(
                            'covernotify 1205锁等待超时，第' . $attempt . '/' . $maxAttempts . '次重试, task_id: ' . ($data['task_id'] ?? '')
                        );
                        $thumbStatus = ShanjianVideoTask::where('task_id', $data['task_id'])->value('thumb_status');
                        if ($thumbStatus !== null && in_array((int)$thumbStatus, [2, 3], true)) {
                            return true;
                        }
                        usleep(400000 * $attempt);
                        continue;
                    }
                    Log::channel('shanjiannotice')->write('covernotify异常: ' . $lastError);
                    self::setError($lastError);
                    return false;
                }
            }

            Log::channel('shanjiannotice')->write('covernotify 1205重试耗尽: ' . $lastError);
            self::setError($lastError);
            return false;
        } catch (\Exception $e) {
            self::safeRollback('covernotify');
            Log::channel('shanjiannotice')->write('covernotify异常: ' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 定时查询封面任务状态并补偿（非队列）
     */
    public static function checkCover()
    {
        try {
            $tasks = ShanjianVideoTask::where('thumb_status', 1)
                ->where('cover_result_id', '<>', '')
                ->whereNotNull('cover_result_id')
                ->where('create_time', '<=', strtotime('-30 minutes'))
                ->order('id', 'asc')
                ->limit(3)
                ->select();

            foreach ($tasks as $task) {
                $lockKey = 'shanjian_video_task_cover_notify_' . $task->task_id;
                try {
                    if (!self::acquireRedisLock($lockKey, 180)) {
                        continue;
                    }

                    $params = ['taskId' => $task->cover_result_id, 'task_id' => $task->task_id];
                    $response = \app\common\service\ToolsService::Shanjian()->status($params);
                    Log::channel('shanjiannotice')->write(
                        'checkCover查询状态, task_id: ' . $task->task_id
                        . ', cover_result_id: ' . $task->cover_result_id
                        . ', 响应: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );

                    if (
                        !isset($response['code'])
                        || (isset($response['data']['status']) && $response['data']['status'] === 'processing')
                    ) {
                        self::releaseRedisLock($lockKey);
                        continue;
                    }

                    if (isset($response['code']) && in_array((int)$response['code'], [22901, 22902], true)) {
                        $notifyData = [
                            'task_id' => $task->task_id,
                            'taskId' => $task->cover_result_id,
                            'status' => 'failed',
                            'errorMessage' => $response['message'] ?? '处理失败',
                        ];
                    } elseif (!empty($response['data']) && is_array($response['data'])) {
                        $notifyData = $response['data'];
                        $notifyData['task_id'] = $task->task_id;
                        $notifyData['taskId'] = $task->cover_result_id;
                    } else {
                        self::releaseRedisLock($lockKey);
                        continue;
                    }

                    $ok = self::covernotify($notifyData);
                    if ($ok) {
                        self::keepRedisLock($lockKey, 20);
                    } else {
                        self::releaseRedisLock($lockKey);
                        Log::channel('shanjiannotice')->error(
                            'checkCover补偿失败, task_id: ' . $task->task_id . ', err: ' . self::getError()
                        );
                    }
                } catch (\Exception $e) {
                    self::releaseRedisLock($lockKey);
                    $errorMsg = $e->getMessage();
                    if (strpos($errorMsg, 'Lock wait timeout exceeded') !== false) {
                        Log::channel('shanjiannotice')->warning('checkCover 锁等待超时，跳过: ' . ($task->task_id ?? ''));
                        continue;
                    }
                    Log::channel('shanjiannotice')->error(
                        'checkCover 处理失败, task_id: ' . ($task->task_id ?? '') . ', Error: ' . $errorMsg
                    );
                }
            }

            return true;
        } catch (\Exception | \think\db\exception\DataNotFoundException | \think\db\exception\ModelNotFoundException $e) {
            self::setError($e->getMessage());
            Log::channel('shanjiannotice')->error('checkCover 整体异常: ' . $e->getMessage());
            return false;
        }
    }
}
