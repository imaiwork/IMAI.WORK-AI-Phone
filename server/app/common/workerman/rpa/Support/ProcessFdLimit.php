<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

/**
 * 进程 fd 上限相关阈值。
 *
 * socket 日志的自动关闭/恢复阈值必须跟着进程真实的 soft 上限走：
 * supervisor 默认只给子进程 1024，写死 3000/2500 会让"接近上限提前关闭"永不触发、
 * "回落恢复"永远成立，保护逻辑形同虚设。
 */
final class ProcessFdLimit
{
    /**
     * @return array{int, int} [关闭日志阈值, 恢复日志阈值]，取 soft 上限的 90% / 80%；
     *                          上限为 unlimited 或取不到时退回传入的兜底值
     */
    public static function logThresholds(int $fallbackDisable, int $fallbackRecover): array
    {
        $soft = self::softLimit();
        if ($soft <= 0) {
            return [$fallbackDisable, $fallbackRecover];
        }

        return [(int)floor($soft * 0.9), (int)floor($soft * 0.8)];
    }

    /**
     * 进程打开文件数 soft 上限；unlimited 或 posix 扩展不可用时返回 0。
     */
    public static function softLimit(): int
    {
        if (!function_exists('posix_getrlimit')) {
            return 0;
        }
        $soft = posix_getrlimit()['soft openfiles'] ?? 0;

        return is_int($soft) && $soft > 0 ? $soft : 0;
    }
}
