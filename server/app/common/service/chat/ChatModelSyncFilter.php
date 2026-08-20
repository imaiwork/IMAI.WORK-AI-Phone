<?php

namespace app\common\service\chat;

class ChatModelSyncFilter
{
    /** @var string[]|null */
    private static ?array $excludedAliases = null;

    /** @var string[] */
    private const EXCLUDED_PATTERNS = [
        '/^gpt-image-/i',
        '/^doubao-seedance-/i',
        '/^doubao-seedream-/i',
        '/^seedream-/i',
        '/^doubao-embedding-/i',
        '/^text-embedding-/i',
        '/-embedding-/i',
    ];

    public static function shouldSync(string $alias): bool
    {
        $alias = trim($alias);
        if ($alias === '') {
            return false;
        }

        if (self::isExcludedAlias($alias)) {
            return false;
        }

        foreach (self::EXCLUDED_PATTERNS as $pattern) {
            if (preg_match($pattern, $alias)) {
                return false;
            }
        }

        return true;
    }

    private static function isExcludedAlias(string $alias): bool
    {
        $lowerAlias = strtolower($alias);
        foreach (self::getExcludedAliases() as $excluded) {
            if (strtolower($excluded) === $lowerAlias) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private static function getExcludedAliases(): array
    {
        if (is_array(self::$excludedAliases)) {
            return self::$excludedAliases;
        }

        try {
            $configured = config('ai.chat_model_sync_exclude');
            if (is_array($configured) && $configured !== []) {
                self::$excludedAliases = array_values(array_filter(array_map('strval', $configured)));
                return self::$excludedAliases;
            }
        } catch (\Throwable) {
        }

        $path = dirname(__DIR__, 4) . '/config/ai/chat_model_sync_exclude.php';
        $configured = is_file($path) ? (require $path) : [];
        self::$excludedAliases = is_array($configured)
            ? array_values(array_filter(array_map('strval', $configured)))
            : [];

        return self::$excludedAliases;
    }
}
