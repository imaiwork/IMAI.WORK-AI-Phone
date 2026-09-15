<?php

declare(strict_types=1);

namespace app\common\service\log;

use RuntimeException;

/**
 * 对齐 ThinkPHP File 驱动（single=false 且 max_files=0）的月目录。
 */
class ThinkFileLogDir
{
    public static function monthPath(string $channel, ?string $root = null): string
    {
        $channel = trim($channel, "/\\ \t\n\r\0\x0B");
        if ($channel === '') {
            throw new RuntimeException('日志通道不能为空');
        }

        $base = $root ?? rtrim((string)root_path(), '/\\');

        return $base
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'log'
            . DIRECTORY_SEPARATOR . $channel
            . DIRECTORY_SEPARATOR . date('Ym');
    }

    public static function ensureChannel(string $channel, ?string $root = null): void
    {
        self::ensure(self::monthPath($channel, $root));
    }

    public static function ensure(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (file_exists($path) && !is_dir($path)) {
            throw new RuntimeException('日志路径已被普通文件占用: ' . $path);
        }

        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('无法创建日志目录: ' . $path);
        }
    }
}
