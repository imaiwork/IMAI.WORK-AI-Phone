<?php

namespace app\common\service\videoImitation;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\UserTokensLog;

/**
 * 图文仿写流水：task_id 写数字主键，轮次/旧复合键放 extra，幂等同时认新旧键
 */
class VideoImitationImageBilling
{
    public const MODULE = 'video_imitation_image_text';

    /**
     * 新图文流水写入的 task_id（数字任务主键）
     */
    public static function writeTaskId(int $taskId): string
    {
        return (string)$taskId;
    }

    /**
     * 信息抓取旧复合键
     */
    public static function extractBillingKey(int $taskId, int $billingRound): string
    {
        return 'video_imitation_info_extract_' . $taskId . '_r' . max(1, $billingRound);
    }

    /**
     * 图片改写旧复合键
     */
    public static function rewriteBillingKey(int $taskId, int $billingRound): string
    {
        return 'video_imitation_img_' . $taskId . '_r' . max(1, $billingRound);
    }

    /**
     * 历史复合键枚举上界：至少 1，不设魔法上限
     */
    public static function maxLegacyRound(int $billingRound): int
    {
        return max(1, $billingRound);
    }

    /**
     * 枚举历史复合 task_id（抓取键 + 改写键），轮次 1..maxLegacyRound
     *
     * @return list<string>
     */
    public static function legacyTaskIds(int $taskId, int $maxRound): array
    {
        $keys = [];
        $end = self::maxLegacyRound($maxRound);
        for ($round = 1; $round <= $end; $round++) {
            $keys[] = self::extractBillingKey($taskId, $round);
            $keys[] = self::rewriteBillingKey($taskId, $round);
        }
        return array_values(array_unique($keys));
    }

    /**
     * 2002 曾把改写复合键写入 source_sn，仅枚举改写键
     *
     * @return list<string>
     */
    public static function legacySourceSns(int $taskId, int $maxRound): array
    {
        $keys = [];
        $end = self::maxLegacyRound($maxRound);
        for ($round = 1; $round <= $end; $round++) {
            $keys[] = self::rewriteBillingKey($taskId, $round);
        }
        return array_values(array_unique($keys));
    }

    /**
     * 合并 extra，并覆盖写入口径字段
     */
    public static function mergeExtra(int $taskId, int $billingRound, string $billingKey, array $extra): array
    {
        $extra['task_id'] = $taskId;
        $extra['billing_round'] = max(1, $billingRound);
        $extra['billing_key'] = $billingKey;
        $extra['module'] = self::MODULE;
        return $extra;
    }

    /**
     * extra 可能是 JSON 字符串或数组
     */
    public static function parseExtra(mixed $extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * 精确归集用：是否图文仿写流水。禁止仅凭数字 task_id + change_type=2002 判定。
     */
    public static function isImageTextLog(array $log): bool
    {
        $extra = self::parseExtra($log['extra'] ?? null);

        if (($extra['module'] ?? '') === self::MODULE) {
            return true;
        }

        $billingKey = (string)($extra['billing_key'] ?? '');
        if ($billingKey !== '' && (
            str_starts_with($billingKey, 'video_imitation_info_extract_')
            || str_starts_with($billingKey, 'video_imitation_img_')
        )) {
            return true;
        }

        if (($extra['场景'] ?? '') === '手动-小红书图文仿写') {
            return true;
        }

        $billItem = (string)($extra['扣费项目'] ?? '');
        if ($billItem !== '' && str_contains($billItem, '图文爆款仿写')) {
            return true;
        }

        return false;
    }

    /**
     * 同时按数字 id 与旧复合键统计该轮 DEC/INC
     *
     * @return array{dec:int,inc:int}
     */
    public static function countDecInc(
        int $userId,
        int $taskId,
        int $changeType,
        int $billingRound,
        string $oldBillingKey
    ): array {
        $dec = 0;
        $inc = 0;
        foreach (self::fetchMatchedLogs($userId, $taskId, $changeType, $billingRound, $oldBillingKey) as $log) {
            $action = (int)$log->action;
            if ($action === AccountLogEnum::DEC) {
                $dec++;
            } elseif ($action === AccountLogEnum::INC) {
                $inc++;
            }
        }
        return ['dec' => $dec, 'inc' => $inc];
    }

    /**
     * 该轮是否已有 DEC（含旧复合键行）
     */
    public static function hasDec(
        int $userId,
        int $taskId,
        int $changeType,
        int $billingRound,
        string $oldBillingKey
    ): bool {
        $counts = self::countDecInc($userId, $taskId, $changeType, $billingRound, $oldBillingKey);
        return $counts['dec'] > 0;
    }

    /**
     * 该轮最新一条 DEC（给退费用），找不到返回空模型
     */
    public static function findLatestDec(
        int $userId,
        int $taskId,
        int $changeType,
        int $billingRound,
        string $oldBillingKey
    ): UserTokensLog {
        $latest = null;
        foreach (self::fetchMatchedLogs($userId, $taskId, $changeType, $billingRound, $oldBillingKey) as $log) {
            if ((int)$log->action !== AccountLogEnum::DEC) {
                continue;
            }
            if ($latest === null || (int)$log->id > (int)$latest->id) {
                $latest = $log;
            }
        }
        return $latest ?? UserTokensLog::findOrEmpty(0);
    }

    /**
     * 拉取 user_id + change_type + task_id IN (数字id, 旧复合键) 后按轮次过滤
     *
     * @return array<int, UserTokensLog>
     */
    private static function fetchMatchedLogs(
        int $userId,
        int $taskId,
        int $changeType,
        int $billingRound,
        string $oldBillingKey
    ): array {
        $numericId = self::writeTaskId($taskId);
        $round = max(1, $billingRound);
        $logs = UserTokensLog::where('user_id', $userId)
            ->where('change_type', $changeType)
            ->whereIn('task_id', [$numericId, $oldBillingKey])
            ->select();

        $matched = [];
        foreach ($logs as $log) {
            if (self::rowMatchesRound($log, $numericId, $oldBillingKey, $round)) {
                $matched[] = $log;
            }
        }
        return $matched;
    }

    /**
     * 旧键行全部计入；数字 id 行仅 extra.billing_round 等于该轮次才计入
     */
    private static function rowMatchesRound(
        UserTokensLog $log,
        string $numericId,
        string $oldBillingKey,
        int $billingRound
    ): bool {
        $rowTaskId = (string)$log->task_id;
        if ($rowTaskId === $oldBillingKey) {
            return true;
        }
        if ($rowTaskId === $numericId) {
            $extra = self::parseExtra($log->extra);
            return (int)($extra['billing_round'] ?? 0) === $billingRound;
        }
        return false;
    }
}
