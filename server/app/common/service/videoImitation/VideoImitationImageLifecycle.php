<?php

namespace app\common\service\videoImitation;

use app\common\model\videoImitation\VideoImitationTask;

/**
 * 图文 Cron 生命周期：超时阈值、锁 TTL、扫描/入口守卫。
 * 只收敛生命周期，不承担计费。
 */
class VideoImitationImageLifecycle
{
    /** PROCESSING 硬超时秒数 */
    public const PROCESSING_TIMEOUT_SECONDS = 1800;

    /** 软孤儿：无心跳超过该秒数可立即续跑 */
    public const ORPHAN_STALE_SECONDS = 240;

    /** 图文解析无进展视为超时的秒数 */
    public const PARSE_STALE_SECONDS = 1800;

    /** 选图超时自动确认秒数 */
    public const AUTO_CONFIRM_STALE_SECONDS = 1800;

    /** 图文改写 Cron 运行锁 TTL */
    public const REWRITE_CRON_LOCK_TTL = 1800;

    /** 解析回收 Cron 运行锁 TTL */
    public const PARSE_CRON_LOCK_TTL = 120;

    /** 单任务解析回收锁 TTL */
    public const PARSE_TASK_LOCK_TTL = 60;

    /**
     * 用户是否已删除该任务
     *
     * @param VideoImitationTask|array $task
     */
    public static function isUserDeleted($task): bool
    {
        return self::intField($task, 'task_delete') === 1;
    }

    /**
     * 图文改写 Cron 是否可扫描该任务
     *
     * @param VideoImitationTask|array $task
     */
    public static function canScanRewrite($task): bool
    {
        return self::intField($task, 'media_type') === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
            && !self::isUserDeleted($task);
    }

    /**
     * 解析超时回收是否可扫描该任务。
     * WAIT/PROCESSING/SUCCESS 已进入改写流程，禁止打回选图。
     *
     * @param VideoImitationTask|array $task
     */
    public static function canScanParseRecover($task): bool
    {
        $rewriteStatus = self::intField($task, 'image_rewrite_status');
        if ($rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT
            || $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
            || $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS
        ) {
            return false;
        }

        return self::intField($task, 'media_type') === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
            && !self::isUserDeleted($task)
            && self::intField($task, 'status') === VideoImitationTask::STATUS_PARSING;
    }

    /**
     * PROCESSING 是否已硬超时。startedAt<=0 视为已过期。
     */
    public static function isProcessingExpired(int $startedAt, ?int $now = null): bool
    {
        if ($startedAt <= 0) {
            return true;
        }
        $now = $now ?? time();
        return $startedAt <= $now - self::PROCESSING_TIMEOUT_SECONDS;
    }

    /**
     * 是否达到软孤儿阈值。startedAt<=0 视为已过期。
     */
    public static function isOrphanStale(int $startedAt, ?int $now = null): bool
    {
        if ($startedAt <= 0) {
            return true;
        }
        $now = $now ?? time();
        return $startedAt <= $now - self::ORPHAN_STALE_SECONDS;
    }

    /**
     * @param VideoImitationTask|array $task
     */
    private static function intField($task, string $field): int
    {
        if (is_array($task)) {
            return (int)($task[$field] ?? 0);
        }

        return (int)($task->{$field} ?? 0);
    }
}
