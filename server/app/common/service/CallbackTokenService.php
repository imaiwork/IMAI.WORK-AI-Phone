<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Log;

/**
 * 全站共用·回调鉴权令牌（2026-09-02，起源于爆款玩法回调，不限于它）。
 *
 * 背景：异步生成类功能的回调接口必须免登录（上游服务器直调，无用户会话），
 * 等于公网敞开的门——不设防则任何人可伪造终态报文改任务状态、触发退款。
 * 统一方案：提交任务时把令牌拼进回调地址交给上游（appendTo），回调打回时
 * 严格比对（verify，hash_equals 防时序攻击）。令牌只在本站闭环、上游仅透传，
 * 故各站自定随机长串即可（openssl rand -hex 24），无需与任何一方协调。
 *
 * 配置：.env 的 [NOTIFY] 段 TOKEN 键（段可放文件任意位置）。
 * 新增免登录回调接口一律接入本服务校验（verify），不要各自造轮子/裸奔。
 *
 * 存量接口迁移（2026-09-03 起，闪剪视频/封面/音色先行）用 guard 而不是 verify：
 * 这些接口上线前就靠回调推进业务，不能因为站点还没配令牌就把回调通道关掉。
 * 三档判定见 judge()：未配令牌照旧放行；配了则带令牌的严格比对；不带令牌的
 * 只在 LEGACY_UNTIL 宽限期内放行（在途任务提交时回调地址还没带令牌）。
 * 发出方对应传 ToolsService::setRequestAndNotifyUrl(..., withToken: true)。
 */
class CallbackTokenService
{
    /** 迁移期判定：令牌比对通过 */
    public const VERDICT_OK = 'ok';
    /** 迁移期判定：本站未配置令牌，未鉴权放行（站点应尽快配置） */
    public const VERDICT_UNCONFIGURED = 'unconfigured';
    /** 迁移期判定：请求未带令牌，宽限期内放行 */
    public const VERDICT_LEGACY = 'legacy';
    /** 迁移期判定：拒绝 */
    public const VERDICT_REJECT = 'reject';

    /** 读取令牌；空串=未配置（新接口的回调通道应保持关闭，纯靠定时任务兜底同步） */
    public static function token(): string
    {
        $token = (string)env('NOTIFY.TOKEN', '');
        if ($token !== '') {
            return $token;
        }
        // 兼容爆款玩法早期的两种写法，迁移完成后可清理
        $token = (string)env('PLAY.NOTIFY_TOKEN', '');
        return $token !== '' ? $token : (string)env('PLAY_NOTIFY_TOKEN', '');
    }

    /** 本站是否已配置令牌 */
    public static function isConfigured(): bool
    {
        return self::token() !== '';
    }

    /** 校验回调携带的令牌。未配置令牌时恒拒——正常流程此时根本不会发出回调地址 */
    public static function verify(?string $given): bool
    {
        $token = self::token();
        if ($token === '' || $given === null || $given === '') {
            return false;
        }
        return hash_equals($token, (string)$given);
    }

    /**
     * 存量接口迁移宽限期截止时间戳，0=不设宽限期。
     * .env 的 [NOTIFY] 段 LEGACY_UNTIL，可写时间戳，也可写 2026-09-10 00:00:00 这类日期串；
     * 到点自动失效，不用回头再改配置。
     */
    public static function legacyUntil(): int
    {
        $raw = trim((string)env('NOTIFY.LEGACY_UNTIL', ''));
        if ($raw === '') {
            return 0;
        }
        if (ctype_digit($raw)) {
            return (int)$raw;
        }
        $ts = strtotime($raw);
        return $ts === false ? 0 : $ts;
    }

    /**
     * 存量免登录回调接口的迁移期判定（新接口一律用 verify）：
     * - 本站未配令牌：VERDICT_UNCONFIGURED，放行，行为与接入前一致，回调通道不因升级静默关闭；
     * - 已配令牌且请求带令牌：比对，通过 VERDICT_OK，不通过 VERDICT_REJECT（带错令牌的不享受宽限）；
     * - 已配令牌但请求没带：宽限期内 VERDICT_LEGACY 放行，过期 VERDICT_REJECT。
     */
    public static function judge(?string $given): string
    {
        if (!self::isConfigured()) {
            return self::VERDICT_UNCONFIGURED;
        }
        if ($given !== null && $given !== '') {
            return self::verify($given) ? self::VERDICT_OK : self::VERDICT_REJECT;
        }
        return time() < self::legacyUntil() ? self::VERDICT_LEGACY : self::VERDICT_REJECT;
    }

    /**
     * 存量回调接口的守卫：按 judge 放行/拒绝，并把需要站长知道的两种情况写日志。
     * 放行返回 true；拒绝返回 false，调用方直接 fail 掉即可。
     *
     * @param string $channel 该模块自己的日志通道，如 shanjiannotice
     * @param string $scene   出现在日志里的接口名，如 shanjian_video_notify
     */
    public static function guard(string $channel, string $scene, ?string $given): bool
    {
        $verdict = self::judge($given);
        switch ($verdict) {
            case self::VERDICT_REJECT:
                Log::channel($channel)->warning(
                    $scene . ' 回调令牌校验失败，已丢弃该回调（' . (($given === null || $given === '') ? '请求未带令牌且宽限期已过' : '令牌不匹配') . '）'
                );
                return false;
            case self::VERDICT_LEGACY:
                Log::channel($channel)->warning(
                    $scene . ' 回调未带令牌，宽限期内放行（在途任务），宽限期至 ' . date('Y-m-d H:i:s', self::legacyUntil())
                );
                return true;
            case self::VERDICT_UNCONFIGURED:
                Log::channel($channel)->info($scene . ' 未配置回调令牌，回调未鉴权放行');
                return true;
            default:
                return true;
        }
    }

    /** 把令牌拼进回调地址；令牌未配置返回空串（表示不开启回调） */
    public static function appendTo(string $url): string
    {
        $token = self::token();
        if ($token === '' || $url === '') {
            return '';
        }
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'token=' . urlencode($token);
    }

    /** 日志脱敏：把回调报文里的 token 换成 ***，避免令牌落进日志文件 */
    public static function maskForLog(array $payload): array
    {
        if (array_key_exists('token', $payload)) {
            $payload['token'] = '***';
        }
        return $payload;
    }
}
