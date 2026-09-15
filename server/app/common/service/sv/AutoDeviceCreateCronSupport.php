<?php

namespace app\common\service\sv;

/**
 * AutoDeviceCreateCron 的进度/时间盒纯函数，便于单测。
 */
class AutoDeviceCreateCronSupport
{
    public static function parseDoneMap(mixed $raw): array
    {
        $codes = [];
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            $codes = is_array($data['done'] ?? null) ? $data['done'] : [];
        } elseif (is_array($raw)) {
            $codes = is_array($raw['done'] ?? null) ? $raw['done'] : $raw;
        }

        $map = [];
        foreach ($codes as $code) {
            $code = trim((string)$code);
            if ($code !== '') {
                $map[$code] = 1;
            }
        }

        return $map;
    }

    public static function encodeProgress(array $doneMap, string $updatedAt = ''): string
    {
        return json_encode([
            'done' => array_values(array_keys($doneMap)),
            'updated_at' => $updatedAt !== '' ? $updatedAt : date('c'),
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function shouldStopForBudget(int $startedAt, int $budgetSeconds, ?int $now = null): bool
    {
        if ($budgetSeconds <= 0) {
            return false;
        }

        return ($now ?? time()) >= ($startedAt + $budgetSeconds);
    }

    public static function inProcessTimeBudget(string $sapi): int
    {
        return in_array($sapi, ['cli', 'cli-server'], true) ? 0 : 180;
    }

    /**
     * @param string[] $deviceCodes
     * @return string[]
     */
    public static function remainingCodes(array $deviceCodes, array $doneMap): array
    {
        $remaining = [];
        foreach ($deviceCodes as $code) {
            $code = (string)$code;
            if ($code !== '' && !isset($doneMap[$code])) {
                $remaining[] = $code;
            }
        }

        return $remaining;
    }

    public static function canSpawnWorker(string $osFamily, bool $execEnabled): bool
    {
        return $osFamily !== 'Windows' && $execEnabled;
    }

    public static function isPhpFpmBinary(string $path): bool
    {
        return (bool)preg_match('/php-fpm/i', basename(str_replace('\\', '/', $path)));
    }

    /**
     * HTTP(php-fpm) 下 PHP_BINARY 常是 sbin/php-fpm，CLI 在上一级 bin/php（宝塔/LNMP）。
     *
     * @return string[]
     */
    public static function phpCliCandidates(string $phpBinary, string $sapi): array
    {
        $out = [];
        $add = static function (string $path) use (&$out) {
            $path = trim($path);
            if ($path !== '' && !in_array($path, $out, true)) {
                $out[] = $path;
            }
        };

        if (in_array($sapi, ['cli', 'cli-server'], true) && $phpBinary !== '' && !self::isPhpFpmBinary($phpBinary)) {
            $add($phpBinary);
            return $out;
        }

        $dir = $phpBinary !== '' ? dirname($phpBinary) : '';
        $parent = $dir !== '' ? dirname($dir) : '';
        $names = ['php', 'php8.3', 'php8.2', 'php8.1', 'php8.0', 'php83', 'php82', 'php81', 'php80'];
        foreach ($names as $name) {
            if ($dir !== '') {
                $add($dir . DIRECTORY_SEPARATOR . $name);
            }
            if ($parent !== '' && $parent !== '.' && $parent !== DIRECTORY_SEPARATOR) {
                $add($parent . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $name);
            }
        }

        if ($phpBinary !== '' && !self::isPhpFpmBinary($phpBinary)) {
            $add($phpBinary);
        }

        return $out;
    }

    /**
     * @param string[] $candidates
     * @param callable(string):bool $isUsable
     */
    public static function pickPhpCliBinary(array $candidates, callable $isUsable, string $fallback = 'php'): string
    {
        foreach ($candidates as $candidate) {
            if ($isUsable($candidate)) {
                return $candidate;
            }
        }

        return $fallback;
    }
}
