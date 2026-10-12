<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 发布任务状态机决策（无 IO），供调度器与测试共用。
 */
class PublishTaskReconcile
{
    public const ACTION_NOOP = 'noop';
    public const ACTION_WRITE = 'write';
    public const ACTION_WAIT_OFFLINE = 'wait_offline';

    public const WINDOW_FINISH = 'finish';
    public const WINDOW_FAIL = 'fail';
    public const WINDOW_WAIT = 'wait';
    public const WINDOW_DISPATCH = 'dispatch';
    public const WINDOW_KEEP = 'keep';

    public const CLOSE_BILLING = 'billing';
    public const CLOSE_FINISH = 'finish';
    public const CLOSE_FAIL_DETAIL = 'fail_detail';
    public const CLOSE_SUCCESS_FROM_LOG = 'success_from_log';
    public const CLOSE_FAIL_INFLIGHT = 'fail_inflight';
    public const CLOSE_TIMEOUT = 'timeout';

    /**
     * @return array{action:string,status?:int,remark?:string,notify?:bool,working?:bool}
     */
    public static function decideDispatchResult(
        int $currentTaskStatus,
        int $endTime,
        int $now,
        int $resultStatus,
        string $resultRemark
    ): array {
        if ($resultStatus === -1) {
            return ['action' => self::ACTION_NOOP];
        }

        $offlineInWindow = $resultStatus === 3
            && PublishRemarkGuard::isDeviceOfflineFail($resultRemark)
            && $now <= $endTime;
        if ($offlineInWindow) {
            $keepStatus = in_array($currentTaskStatus, [0, 1], true) ? $currentTaskStatus : 0;
            return [
                'action' => self::ACTION_WAIT_OFFLINE,
                'status' => $keepStatus,
                'remark' => PublishRemarkGuard::WAIT_OFFLINE_REMARK,
                'notify' => false,
                'working' => false,
            ];
        }

        return [
            'action' => self::ACTION_WRITE,
            'status' => $resultStatus,
            'remark' => $resultRemark,
            'notify' => true,
            'working' => $resultStatus === 1,
        ];
    }

    /**
     * @return array{action:string}
     */
    public static function decideRunningWindow(?int $detailStatus): array
    {
        if ($detailStatus === null) {
            return ['action' => self::WINDOW_KEEP];
        }

        return match ($detailStatus) {
            1 => ['action' => self::WINDOW_FINISH],
            2 => ['action' => self::WINDOW_FAIL],
            3 => ['action' => self::WINDOW_WAIT],
            0, 5 => ['action' => self::WINDOW_DISPATCH],
            default => ['action' => self::WINDOW_KEEP],
        };
    }

    /**
     * @return array{action:string,fail_inflight_detail?:bool}
     */
    public static function decideClose(
        string $taskRemark,
        ?int $detailStatus,
        string $lastLogMessage,
        ?int $taskStatus = null
    ): array {
        if (PublishRemarkGuard::isBillingLocked($taskRemark)) {
            return [
                'action' => self::CLOSE_BILLING,
                'fail_inflight_detail' => $detailStatus === 3,
            ];
        }

        // 任务已被写成完成：到期收口不得再打成断网失败。
        if ($taskStatus === 2) {
            if ($detailStatus === 1) {
                return ['action' => self::CLOSE_FINISH];
            }
            if ($detailStatus === 2 && !PublishRemarkGuard::isPublishSuccessText($lastLogMessage)) {
                return ['action' => self::CLOSE_FAIL_DETAIL];
            }
            return ['action' => self::CLOSE_SUCCESS_FROM_LOG];
        }

        if ($detailStatus === 1) {
            return ['action' => self::CLOSE_FINISH];
        }
        if ($detailStatus === 2) {
            return ['action' => self::CLOSE_FAIL_DETAIL];
        }
        if ($detailStatus === 3) {
            if (PublishRemarkGuard::isPublishSuccessText($lastLogMessage)) {
                return ['action' => self::CLOSE_SUCCESS_FROM_LOG];
            }
            return ['action' => self::CLOSE_FAIL_INFLIGHT];
        }

        return ['action' => self::CLOSE_TIMEOUT];
    }

    /**
     * 读日志生成失败备注之后再判一次：成功文案必须升为成功，不能落成断网失败。
     */
    public static function resolveAfterInflightRemark(string $action, string $inflightRemark): string
    {
        if ($action === self::CLOSE_FAIL_INFLIGHT && PublishRemarkGuard::isPublishSuccessText($inflightRemark)) {
            return self::CLOSE_SUCCESS_FROM_LOG;
        }

        return $action;
    }

    /**
     * 人设发布列表对外状态：明细终态优先。
     */
    public static function decideListStatus(int $detailStatus, int $taskStatus, string $taskRemark): int
    {
        if ($detailStatus === 1) {
            return 2;
        }
        if ($detailStatus === 2) {
            return 3;
        }
        if ($detailStatus === 3) {
            if (PublishRemarkGuard::isBillingLocked($taskRemark) && $taskStatus === 3) {
                return 3;
            }
            return 1;
        }
        return $taskStatus;
    }

    /**
     * 人设发布列表对外备注：算力优先，明细成功压过离线失败。
     *
     * @param list<string> $noise
     */
    public static function decideListRemark(string $detailRemark, string $taskRemark, array $noise = []): string
    {
        $detailRemark = trim($detailRemark);
        $taskRemark = trim($taskRemark);
        foreach ([$taskRemark, $detailRemark] as $remark) {
            if (PublishRemarkGuard::isBillingLocked($remark)) {
                return $remark;
            }
        }
        if (PublishRemarkGuard::isPublishSuccessText($detailRemark)) {
            return $detailRemark;
        }
        if ($detailRemark !== '' && !in_array($detailRemark, $noise, true)) {
            return $detailRemark;
        }
        if ($taskRemark !== '' && !in_array($taskRemark, $noise, true)) {
            return $taskRemark;
        }
        return $detailRemark !== '' ? $detailRemark : $taskRemark;
    }

    /**
     * 待执行超时失败文案：发布任务若已因离线在等，保留原备注。
     */
    public static function decideTimeoutRemark(string $currentRemark, bool $isPublishTask): string
    {
        $currentRemark = trim($currentRemark);
        if ($isPublishTask && (
            PublishRemarkGuard::isTransientOfflineWait($currentRemark)
            || PublishRemarkGuard::isDeviceOfflineFail($currentRemark)
        )) {
            return $currentRemark;
        }
        return '执行任务的设备没有提前开启';
    }
}
