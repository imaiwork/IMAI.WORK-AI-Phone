<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDySearchRecord;
use app\common\model\sv\SvDySearchTask;
use app\common\service\display\DySearchIntentionMapper;
use think\facade\Log;

/**
 * 抖音获客微信号异步校验：只决定能不能进加微队列，不改客资、不退费。
 */
class DySearchWechatVerifyService
{
    public const ADD_STATUS_INVALID = 0;
    public const ADD_STATUS_PENDING = 4;
    public const ADD_STATUS_VERIFYING = 6;
    public const DISPATCH_STATUSES = [3, 4, 5];

    public const INVALID_RESULT = '该线索经过校验为无效线索';
    public const VERIFYING_RESULT = '微信号校验中';
    public const VALID_RESULT = '微信号校验通过';

    private const CRON_LIMIT = 5;
    private const TASK_VERIFY_LIMIT = 20;
    private const STUCK_SECONDS = 1800;
    private const CRON_BUDGET_SECONDS = 25;
    private const CRON_RETRY_SECONDS = 300;
    private const HTTP_CONNECT_TIMEOUT = 5;
    private const HTTP_REQUEST_TIMEOUT = 8;

    public static function shouldDispatchStatus(int $status): bool
    {
        return in_array($status, self::DISPATCH_STATUSES, true);
    }

    public static function shouldReclassifyPending(string $regWechat, string $result): bool
    {
        if (!WechatExistVerifyService::isWechatFormat($regWechat)) {
            return false;
        }
        return trim($result) !== self::VALID_RESULT;
    }

    /**
     * 校验结果落到加微 status：有效待加、无效失败、其余继续待校验。
     */
    public static function nextAddStatus(string $outcome): int
    {
        return match ($outcome) {
            WechatExistVerifyService::OUTCOME_VALID => self::ADD_STATUS_PENDING,
            WechatExistVerifyService::OUTCOME_INVALID => self::ADD_STATUS_INVALID,
            default => self::ADD_STATUS_VERIFYING,
        };
    }

    public static function cron(): void
    {
        $deadline = time() + self::CRON_BUDGET_SECONDS;
        self::reclassifyLegacyPending($deadline);
        if (time() >= $deadline) {
            return;
        }
        self::recycleLegacyExecuting();
        if (time() >= $deadline) {
            return;
        }
        self::recycleStuckDispatchAll($deadline);
        if (time() >= $deadline) {
            return;
        }
        self::verifyStoredRecords($deadline);
    }

    public static function reclassifyLegacyPending(int $deadline = 0): int
    {
        $changed = 0;
        $rows = SvAddWechatRecord::where('channel', DySearchIntentionMapper::ADD_CHANNEL)
            ->where('dy_search_task_id', '>', 0)
            ->where('status', self::ADD_STATUS_PENDING)
            ->order('id', 'asc')
            ->limit(50)
            ->select();
        foreach ($rows as $row) {
            if ($deadline > 0 && time() >= $deadline) {
                break;
            }
            if (!self::shouldReclassifyPending((string)$row->reg_wechat, (string)($row->result ?? ''))) {
                continue;
            }
            $row->status = self::ADD_STATUS_VERIFYING;
            $row->result = self::VERIFYING_RESULT;
            $row->update_time = time();
            $row->save();
            $changed++;
        }
        if ($changed > 0) {
            self::log('存量待加微微信号已改待校验：条数=' . $changed);
        }
        return $changed;
    }

    private static function verifyStoredRecords(int $deadline): void
    {
        $retryBefore = time() - self::CRON_RETRY_SECONDS;
        $records = SvDySearchRecord::where('result', DySearchIngestService::RESULT_STORED)
            ->where('is_verify', 0)
            ->whereRaw('(update_time IS NULL OR update_time = 0 OR update_time <= ? OR update_time = create_time)', [$retryBefore])
            ->order('id', 'asc')
            ->limit(self::CRON_LIMIT)
            ->select();

        self::log('待校验客资：共' . count($records) . '条');
        $processed = 0;
        foreach ($records as $record) {
            if (time() >= $deadline) {
                self::log('校验达到单轮耗时预算已中止：已处理' . $processed . '条');
                break;
            }
            try {
                self::verifyRecord($record, $deadline);
            } catch (\Throwable $e) {
                self::log('record_id=' . $record->id . ' ' . $e->getMessage());
                $record->update_time = time();
                $record->save();
            }
            $processed++;
        }
    }

    public static function verifyRecord(SvDySearchRecord $record, int $deadline = 0): void
    {
        $task = SvDySearchTask::where('id', (int)$record->task_id)->findOrEmpty();
        if ($task->isEmpty() || (int)$task->add_type !== 1) {
            $record->is_verify = 1;
            $record->update_time = time();
            $record->save();
            return;
        }

        $wechats = self::pendingWechats($record, $task);
        if ($wechats === []) {
            $record->is_verify = 1;
            $record->update_time = time();
            $record->save();
            return;
        }

        $pending = false;
        $logs = [];
        foreach ($wechats as $wechat) {
            if ($deadline > 0 && time() >= $deadline) {
                $pending = true;
                $logs[] = '微信号校验达到单轮耗时预算，本条剩余号码稍后重试';
                break;
            }
            $handled = self::handleOneWechat($record, $task, $wechat, $deadline);
            $logs[] = $handled['log'];
            if ($handled['pending']) {
                $pending = true;
            }
        }

        if ($logs !== []) {
            self::log(json_encode($logs, JSON_UNESCAPED_UNICODE));
        }

        $record->update_time = time();
        if (!$pending) {
            $record->is_verify = 1;
        }
        $record->save();
    }

    /**
     * @return array{pending: bool, log: string}
     */
    private static function handleOneWechat(
        SvDySearchRecord $record,
        SvDySearchTask $task,
        string $wechat,
        int $deadline
    ): array {
        $existing = SvAddWechatRecord::where('user_id', (int)$record->user_id)
            ->where('reg_wechat', $wechat)
            ->findOrEmpty();

        if (!$existing->isEmpty()) {
            $status = (int)$existing->status;
            if (
                $status === self::ADD_STATUS_PENDING
                || $status === 1
                || $status === 2
                || ($status !== self::ADD_STATUS_VERIFYING && $status !== self::ADD_STATUS_INVALID && self::shouldDispatchStatus($status))
            ) {
                return ['pending' => false, 'log' => $wechat . '加微记录已处理，跳过'];
            }
            $checked = WechatExistVerifyService::check(
                $wechat,
                self::HTTP_CONNECT_TIMEOUT,
                self::HTTP_REQUEST_TIMEOUT,
                true,
                $deadline
            );
            if ($checked['outcome'] === WechatExistVerifyService::OUTCOME_VALID) {
                $existing->status = self::ADD_STATUS_PENDING;
                $existing->result = self::VALID_RESULT;
                $existing->update_time = time();
                $existing->save();
                return ['pending' => false, 'log' => $checked['log']];
            }
            if ($checked['outcome'] === WechatExistVerifyService::OUTCOME_INVALID) {
                $existing->status = self::ADD_STATUS_INVALID;
                $existing->result = self::INVALID_RESULT;
                $existing->update_time = time();
                $existing->save();
                return ['pending' => false, 'log' => $checked['log']];
            }
            if ((int)$existing->status !== self::ADD_STATUS_VERIFYING) {
                $existing->status = self::ADD_STATUS_VERIFYING;
                $existing->result = self::VERIFYING_RESULT;
                $existing->update_time = time();
                $existing->save();
            }
            return ['pending' => true, 'log' => $checked['log']];
        }

        $checked = WechatExistVerifyService::check(
            $wechat,
            self::HTTP_CONNECT_TIMEOUT,
            self::HTTP_REQUEST_TIMEOUT,
            true,
            $deadline
        );
        if ($checked['outcome'] === WechatExistVerifyService::OUTCOME_VALID) {
            self::enqueueFromRecord($task, $record, $wechat, self::ADD_STATUS_PENDING, self::VALID_RESULT);
            return ['pending' => false, 'log' => $checked['log']];
        }
        if ($checked['outcome'] === WechatExistVerifyService::OUTCOME_INVALID) {
            self::enqueueFromRecord($task, $record, $wechat, self::ADD_STATUS_INVALID, self::INVALID_RESULT);
            return ['pending' => false, 'log' => $checked['log']];
        }
        return ['pending' => true, 'log' => $checked['log']];
    }

    /**
     * @return list<string>
     */
    public static function pendingWechats(SvDySearchRecord $record, SvDySearchTask $task): array
    {
        $scanConfig = is_array($task->scan_config) ? $task->scan_config : [];
        $contactOpts = [
            'contact_phone' => (bool)($scanConfig['contact_phone'] ?? true),
            'contact_wechat' => (bool)($scanConfig['contact_wechat'] ?? true),
            'contact_qq' => (bool)($scanConfig['contact_qq'] ?? false),
        ];
        $extracted = DySearchIngestService::buildExtracted(
            '',
            (string)$record->dy_id,
            (string)$record->ocr_text,
            $contactOpts,
            (string)($task->add_rule ?? 'all'),
            (bool)($scanConfig['scan_dyid'] ?? true)
        );
        return self::mergePendingWechats(
            $extracted,
            (string)$record->reg_content,
            (bool)($scanConfig['phone_as_wechat'] ?? true),
            (string)$record->dy_id,
            (string)($task->add_rule ?? 'all')
        );
    }

    /**
     * 待校验微信号，按 add_rule 收口。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @return list<string>
     */
    public static function mergePendingWechats(
        array $extracted,
        string $regContent,
        bool $phoneAsWechat = true,
        string $dyId = '',
        string $addRule = 'all'
    ): array {
        $fromReg = self::extractedFromRegContent($regContent);
        $mergedExtracted = [
            'phones' => array_values(array_unique(array_merge($extracted['phones'] ?? [], $fromReg['phones']))),
            'wechats' => array_values(array_unique(array_merge($extracted['wechats'] ?? [], $fromReg['wechats']))),
            'qqs' => array_values(array_unique(array_merge($extracted['qqs'] ?? [], $fromReg['qqs']))),
            'raw' => array_values(array_unique(array_merge($extracted['raw'] ?? [], $fromReg['raw']))),
        ];
        $contacts = DySearchIngestService::pickAddWechatContacts($mergedExtracted, $addRule, $phoneAsWechat);
        $merged = DySearchIngestService::splitAddWechatContacts($contacts)['wechats'];
        $hasPhones = ($mergedExtracted['phones'] ?? []) !== [];
        $dyId = trim($dyId);
        if ($hasPhones && $dyId !== '') {
            $merged = array_values(array_filter($merged, static fn(string $wechat): bool => $wechat !== $dyId));
        }
        return $merged;
    }

    /**
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    public static function extractedFromRegContent(string $regContent): array
    {
        $phones = [];
        $wechats = [];
        $raw = [];
        foreach (explode(',', $regContent) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $raw[] = $item;
            if (preg_match('/^1[3-9]\d{9}$/', $item) === 1) {
                $phones[] = $item;
            } elseif (WechatExistVerifyService::isWechatFormat($item)) {
                $wechats[] = $item;
            }
        }
        return [
            'phones' => $phones,
            'wechats' => $wechats,
            'qqs' => [],
            'raw' => $raw,
        ];
    }

    private static function enqueueFromRecord(
        SvDySearchTask $task,
        SvDySearchRecord $record,
        string $contact,
        int $status,
        string $result = ''
    ): void {
        $deviceCode = (string)$record->device_code;
        $userId = (int)$record->user_id;
        DySearchIngestService::writeAddWechatRecord(
            $task,
            $contact,
            $deviceCode,
            DySearchIngestService::localDyAccount($deviceCode, $userId),
            (string)($record->dy_id !== '' ? $record->dy_id : $record->nickname),
            (string)$record->ocr_text,
            $userId,
            $status,
            $result
        );
    }

    /**
     * 把已入库且已标处理完的行重新打开，留给校验入队。
     */
    public static function reopenStoredForVerify(int $taskId): int
    {
        if ($taskId < 1) {
            return 0;
        }
        return (int)SvDySearchRecord::where('task_id', $taskId)
            ->where('result', DySearchIngestService::RESULT_STORED)
            ->where('is_verify', 1)
            ->update([
                'is_verify' => 0,
                'update_time' => time(),
            ]);
    }

    /**
     * 本任务是否还有已入库但未校验的行。不重开已处理行。
     */
    public static function hasPendingVerify(int $taskId): bool
    {
        if ($taskId < 1) {
            return false;
        }
        return SvDySearchRecord::where('task_id', $taskId)
            ->where('result', DySearchIngestService::RESULT_STORED)
            ->where('is_verify', 0)
            ->count() > 0;
    }

    /**
     * 只校验本任务未处理行，不重开已校验客资。
     */
    public static function verifyPendingForTask(int $taskId, int $deadline = 0, string $scene = '加微前校验'): int
    {
        if ($taskId < 1) {
            return 0;
        }
        $records = SvDySearchRecord::where('task_id', $taskId)
            ->where('result', DySearchIngestService::RESULT_STORED)
            ->where('is_verify', 0)
            ->order('id', 'asc')
            ->limit(self::TASK_VERIFY_LIMIT)
            ->select();
        $processed = 0;
        foreach ($records as $record) {
            if ($deadline > 0 && time() >= $deadline) {
                self::log($scene . '达到单轮耗时预算已中止：task_id=' . $taskId . ' 已处理' . $processed . '条');
                break;
            }
            try {
                self::verifyRecord($record, $deadline);
            } catch (\Throwable $e) {
                self::log($scene . ' record_id=' . $record->id . ' ' . $e->getMessage());
                $record->update_time = time();
                $record->save();
            }
            $processed++;
        }
        self::log($scene . '完成 task_id=' . $taskId . ' 处理' . $processed . '条');
        return $processed;
    }

    /**
     * 执行中超过 7 天未回写的加微记录标失败（原列表 GET 副作用）。
     */
    public static function recycleLegacyExecuting(): int
    {
        $before = time() - (7 * 86400);
        $changed = SvAddWechatRecord::where('status', 2)
            ->where('create_time', '<', $before)
            ->limit(200)
            ->update([
                'status' => self::ADD_STATUS_INVALID,
                'result' => '加微执行失败',
                'update_time' => time(),
            ]);
        $count = (int)$changed;
        if ($count > 0) {
            self::log('执行中超过7天已标失败：条数=' . $count);
        }
        return $count;
    }

    /**
     * 执行中超过 STUCK_SECONDS 未回写的加微记录收回待加微。
     */
    public static function recycleStuckDispatch(int $taskId, int $beforeTs = 0): int
    {
        if ($taskId < 1) {
            return 0;
        }
        $beforeTs = $beforeTs > 0 ? $beforeTs : (time() - self::STUCK_SECONDS);
        $query = SvAddWechatRecord::where('dy_search_task_id', $taskId)
            ->where('channel', DySearchIntentionMapper::ADD_CHANNEL)
            ->where('status', 2)
            ->where('update_time', '<', $beforeTs);
        $changed = $query->update([
            'status' => self::ADD_STATUS_PENDING,
            'result' => '加微超时已回收',
            'update_time' => time(),
        ]);
        $count = (int)$changed;
        if ($count > 0) {
            self::log('执行中超时已回收：task_id=' . $taskId . ' 条数=' . $count);
        }
        return $count;
    }

    private static function recycleStuckDispatchAll(int $deadline): void
    {
        if ($deadline > 0 && time() >= $deadline) {
            return;
        }
        $beforeTs = time() - self::STUCK_SECONDS;
        $changed = SvAddWechatRecord::where('channel', DySearchIntentionMapper::ADD_CHANNEL)
            ->where('dy_search_task_id', '>', 0)
            ->where('status', 2)
            ->where('update_time', '<', $beforeTs)
            ->limit(50)
            ->update([
                'status' => self::ADD_STATUS_PENDING,
                'result' => '加微超时已回收',
                'update_time' => time(),
            ]);
        if ((int)$changed > 0) {
            self::log('执行中超时已回收：全局条数=' . (int)$changed);
        }
    }

    /**
     * 回补指定任务：重开已入库行后走现有校验。
     */
    public static function backfillTask(int $taskId, int $deadline = 0): int
    {
        if ($taskId < 1) {
            return 0;
        }
        self::reopenStoredForVerify($taskId);
        return self::verifyPendingForTask($taskId, $deadline, '回补');
    }

    private static function log(string $msg): void
    {
        try {
            Log::channel('device')->write('抖音获客微信号校验 ' . $msg, 'verify_wechat');
        } catch (\Throwable $e) {
        }
    }
}
