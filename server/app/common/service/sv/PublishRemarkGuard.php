<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 发布任务备注判定：算力锁定、离线失败、RPA 是否允许回写。
 */
class PublishRemarkGuard
{
    public const WAIT_OFFLINE_REMARK = '设备不在线，等待重试';
    public const RUNNING_REMARK = '任务执行中';
    public const SUCCESS_REMARK = 'RPA执行：发布成功';

    public static function isBillingLocked(string $remark): bool
    {
        return trim($remark) !== '' && str_contains($remark, '算力不足');
    }

    public static function isDeviceOfflineFail(string $remark): bool
    {
        $remark = trim($remark);
        if ($remark === '' || !str_starts_with($remark, '任务执行失败')) {
            return false;
        }

        return str_contains($remark, '不在线') || str_contains($remark, '设备离线');
    }

    public static function isTransientOfflineWait(string $remark): bool
    {
        return trim($remark) === self::WAIT_OFFLINE_REMARK;
    }

    /**
     * 仅算力不足禁止被 RPA 进度/终态覆盖。
     */
    public static function shouldBlockRpaWrite(string $remark, int $rpaStatus): bool
    {
        unset($rpaStatus);
        return self::isBillingLocked($remark);
    }

    public static function isPublishSuccessText(string $remark): bool
    {
        $remark = trim($remark);
        if ($remark === '') {
            return false;
        }

        return $remark === '发布成功' || $remark === self::SUCCESS_REMARK;
    }

    /**
     * RPA 是否允许改写发布明细。进度不得回退已成功/已失败。
     */
    public static function shouldApplyRpaDetailStatus(int $incoming, int $currentDetailStatus, string $currentRemark): bool
    {
        if (self::shouldBlockRpaWrite($currentRemark, $incoming)) {
            return false;
        }
        if (!in_array($incoming, [1, 2, 3], true)) {
            return false;
        }
        if ($incoming === 3 && in_array($currentDetailStatus, [1, 2], true)) {
            return false;
        }
        return true;
    }

    /**
     * RPA 是否允许改写设备任务。已完成不被进度打回；失败仅离线/等待可被进度拉回执行中。
     */
    public static function shouldApplyRpaTaskStatus(int $incoming, int $currentTaskStatus, string $currentRemark): bool
    {
        if (self::shouldBlockRpaWrite($currentRemark, $incoming)) {
            return false;
        }
        if (!in_array($incoming, [1, 2, 3], true)) {
            return false;
        }
        if ($incoming === 3 && $currentTaskStatus === 2) {
            return false;
        }
        if ($incoming === 3 && $currentTaskStatus === 3) {
            return self::isDeviceOfflineFail($currentRemark) || self::isTransientOfflineWait($currentRemark);
        }
        return true;
    }
}
