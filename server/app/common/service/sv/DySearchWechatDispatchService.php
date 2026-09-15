<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 抖音获客加微调度判定。不连库、不发 Channel，便于单测。
 */
class DySearchWechatDispatchService
{
    public const ACTION_RUN = 'run';
    public const ACTION_WAIT = 'wait';
    public const ACTION_FINISH = 'finish';

    /**
     * 主任务 status=3 表示搜寻完成，加微继续；4 才是用户结束。
     *
     * @return array{action: string, status: int, remark: string}
     */
    public static function decide(int $taskStatus, int $addType): array
    {
        if ($taskStatus === 4) {
            return [
                'action' => self::ACTION_FINISH,
                'status' => 2,
                'remark' => '获客任务已结束',
            ];
        }
        if ($taskStatus === 2) {
            return [
                'action' => self::ACTION_WAIT,
                'status' => 1,
                'remark' => '获客任务已暂停，等待恢复',
            ];
        }
        if ($addType !== 1) {
            return [
                'action' => self::ACTION_FINISH,
                'status' => 2,
                'remark' => '未开启自动加好友',
            ];
        }
        return [
            'action' => self::ACTION_RUN,
            'status' => 1,
            'remark' => '任务执行中',
        ];
    }

    public static function shouldVerifyPending(bool $queueEmpty, bool $hasUnverifiedStored): bool
    {
        return $queueEmpty && $hasUnverifiedStored;
    }

    /**
     * 本任务本设备已占用的加微名额（已加或执行中）。
     */
    public static function isConsumedStatus(int $status): bool
    {
        return in_array($status, [1, 2], true);
    }

    /**
     * 本轮最多还能下发几条。addNumber 小于 1 视为不限，但单轮仍封顶 batchMax。
     */
    public static function dispatchQuota(int $addNumber, int $used, int $batchMax = 10): int
    {
        $batchMax = max(1, $batchMax);
        if ($addNumber < 1) {
            return $batchMax;
        }
        $remain = $addNumber - $used;
        if ($remain < 1) {
            return 0;
        }
        return min($batchMax, $remain);
    }
}
