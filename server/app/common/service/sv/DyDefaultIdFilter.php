<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 默认抖音号过滤：无联系意义的系统号不能当联系方式
 */
class DyDefaultIdFilter
{
    private const PHONE_PATTERN = '/^1[3-9]\d{9}$/';
    private const WECHAT_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
    private const DEFAULT_DOUYIN_PATTERN = '/^douyin\d+$/i';
    private const DEFAULT_DY_PREFIX_PATTERN = '/^dy[0-9a-z]{8,}$/i';

    /**
     * 默认抖音号（无联系意义）返回 true。空串视为无效。
     */
    public static function isDefaultId(string $dyId): bool
    {
        $dyId = trim($dyId);
        if ($dyId === '') {
            return true;
        }

        if (preg_match(self::DEFAULT_DOUYIN_PATTERN, $dyId) === 1) {
            return true;
        }

        if (preg_match(self::DEFAULT_DY_PREFIX_PATTERN, $dyId) === 1) {
            return true;
        }

        if (preg_match('/^\d+$/', $dyId) === 1 && preg_match(self::PHONE_PATTERN, $dyId) !== 1) {
            return true;
        }

        return false;
    }

    /**
     * 可作为联系方式：合法手机号或微信号形态，且不是默认号
     */
    public static function isAcceptableDyId(string $dyId): bool
    {
        $dyId = trim($dyId);
        if (self::isDefaultId($dyId)) {
            return false;
        }

        if (preg_match(self::PHONE_PATTERN, $dyId) === 1) {
            return true;
        }

        return preg_match(self::WECHAT_PATTERN, $dyId) === 1;
    }
}
