<?php

namespace app\common\service\aiPersona;

/**
 * 按 id 升序轮询：以上一次使用的 id 为游标，取下一条/下一批，到末尾后从头继续。
 *
 * 例：库内 id 为 1~6，上次用到 3，本次要 3 条 → 4、5、6；
 * 上次用到 5，本次要 3 条 → 6、1、2。
 */
class IdRoundRobinPicker
{
    /**
     * @param array $items 待选取列表（关联数组或对象，需含 id）
     * @param int $lastUsedId 上一次使用的 id，0 表示从未使用过（从最小 id 开始）
     * @param int $needCount 需要条数
     * @param string $idKey id 字段名
     * @return array 选取结果（保持原元素结构）
     */
    public static function pick(array $items, int $lastUsedId, int $needCount, string $idKey = 'id'): array
    {
        $items = array_values($items);
        $total = count($items);
        if ($total <= 0 || $needCount <= 0) {
            return [];
        }

        usort($items, static function ($a, $b) use ($idKey) {
            return self::itemId($a, $idKey) <=> self::itemId($b, $idKey);
        });

        $startIndex = self::resolveStartIndex($items, $lastUsedId, $idKey);
        $take = min($needCount, $total);
        $result = [];
        for ($i = 0; $i < $take; $i++) {
            $result[] = $items[($startIndex + $i) % $total];
        }

        return $result;
    }

    /**
     * 取下一条（needCount=1 的快捷方法）
     */
    public static function pickNext(array $items, int $lastUsedId, string $idKey = 'id')
    {
        $picked = self::pick($items, $lastUsedId, 1, $idKey);
        return $picked[0] ?? null;
    }

    private static function resolveStartIndex(array $items, int $lastUsedId, string $idKey): int
    {
        $total = count($items);
        if ($lastUsedId <= 0 || $total <= 0) {
            return 0;
        }

        $exactIndex = null;
        $nextGreaterIndex = null;
        foreach ($items as $index => $item) {
            $id = self::itemId($item, $idKey);
            if ($id === $lastUsedId) {
                $exactIndex = $index;
                break;
            }
            if ($nextGreaterIndex === null && $id > $lastUsedId) {
                $nextGreaterIndex = $index;
            }
        }

        if ($exactIndex !== null) {
            return ($exactIndex + 1) % $total;
        }
        if ($nextGreaterIndex !== null) {
            return $nextGreaterIndex;
        }

        // 上次 id 已不在列表且大于当前最大 id → 从头开始
        return 0;
    }

    private static function itemId($item, string $idKey): int
    {
        if (is_array($item)) {
            return (int)($item[$idKey] ?? 0);
        }
        if (is_object($item)) {
            return (int)($item->{$idKey} ?? 0);
        }
        return 0;
    }
}
