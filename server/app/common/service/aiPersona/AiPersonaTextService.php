<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersona;

class AiPersonaTextService
{
    public static function join($value, string $separator = ','): string
    {
        if (!is_array($value)) {
            return trim((string)$value);
        }

        $items = [];
        self::flatten($value, $items);

        return implode($separator, array_values(array_filter($items, function ($item) {
            return $item !== '';
        })));
    }

    public static function buildPersonaMainData(array $params, ?AiPersona $oldPersona = null): array
    {
        $data = [
            'main_business'      => self::pickText($params, 'main_business', $oldPersona),
            'target_pain_points' => self::pickText($params, 'target_pain_points', $oldPersona),
            'conversion_hook'    => self::pickText($params, 'conversion_hook', $oldPersona),
            'is_shopping_cart'   => self::pickInt($params, 'is_shopping_cart', $oldPersona),
            'goods_name'         => self::pickText($params, 'goods_name', $oldPersona),
            'is_store_position'  => self::pickInt($params, 'is_store_position', $oldPersona),
            'store_position'     => self::pickText($params, 'store_position', $oldPersona),
        ];

        return $data;
    }

    private static function pickText(array $params, string $key, ?AiPersona $oldPersona): string
    {
        if (array_key_exists($key, $params)) {
            return self::join($params[$key]);
        }

        return $oldPersona ? (string)($oldPersona[$key] ?? '') : '';
    }

    private static function pickInt(array $params, string $key, ?AiPersona $oldPersona): int
    {
        if (array_key_exists($key, $params)) {
            return (int)$params[$key];
        }

        return $oldPersona ? (int)($oldPersona[$key] ?? 0) : 0;
    }

    private static function flatten(array $value, array &$items): void
    {
        foreach ($value as $item) {
            if (is_array($item)) {
                self::flatten($item, $items);
                continue;
            }

            $items[] = trim((string)$item);
        }
    }
}
