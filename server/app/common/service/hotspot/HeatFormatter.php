<?php

namespace app\common\service\hotspot;

class HeatFormatter
{
    public static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)round((float)$value);
        }
        $text = trim((string)$value);
        if ($text === '' || !is_numeric($text)) {
            return 0;
        }
        return (int)round((float)$text);
    }

    public static function text(int $heat): string
    {
        if ($heat >= 100000000) {
            return number_format($heat / 100000000, 1, '.', '') . '亿';
        }
        if ($heat >= 10000) {
            return number_format($heat / 10000, 1, '.', '') . '万';
        }
        return (string)$heat;
    }
}
