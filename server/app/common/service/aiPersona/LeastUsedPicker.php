<?php

namespace app\common\service\aiPersona;

/**
 * 按使用次数升序取用：次数少的先取，同一次数内随机。
 *
 * 用于「随机 + 可重复」的内容发布选料：不再设使用上限，
 * 素材用得越多排得越靠后，最少的那批总会被先取走；
 * 次数最少的一档不够时继续取下一档，直到凑够或素材用尽。
 */
class LeastUsedPicker
{
    /**
     * @param array $items 候选列表（关联数组或对象，需含 id）
     * @param int $needCount 需要条数
     * @param array<int,int> $usageCounts id => 使用次数，缺省记 0
     * @param string $idKey id 字段名
     * @return array 选取结果（保持原元素结构），条数为 min(needCount, 候选数)
     */
    public static function pick(array $items, int $needCount, array $usageCounts = [], string $idKey = 'id'): array
    {
        $items = array_values($items);
        $total = count($items);
        if ($total <= 0 || $needCount <= 0) {
            return [];
        }
        if ($needCount >= $total) {
            return $items;
        }

        $buckets = [];
        foreach ($items as $item) {
            $buckets[(int)($usageCounts[self::itemId($item, $idKey)] ?? 0)][] = $item;
        }
        ksort($buckets);

        $result = [];
        foreach ($buckets as $bucket) {
            shuffle($bucket);
            foreach ($bucket as $item) {
                $result[] = $item;
                if (count($result) >= $needCount) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * 从候选里剔除本轮已选条目，供「一类素材不足、用另一类补位」时继续按次数取用。
     *
     * @param array $items 全量候选
     * @param array $selected 已选条目
     * @return array 剩余候选
     */
    public static function exclude(array $items, array $selected, string $idKey = 'id'): array
    {
        $selectedIds = [];
        foreach ($selected as $item) {
            $id = self::itemId($item, $idKey);
            if ($id > 0) {
                $selectedIds[$id] = true;
            }
        }

        $remaining = [];
        foreach ($items as $item) {
            $id = self::itemId($item, $idKey);
            if ($id > 0) {
                if (!isset($selectedIds[$id])) {
                    $remaining[] = $item;
                }
                continue;
            }
            // AI 抓取的素材 id 为 0，只能按值判断是否已选
            if (!in_array($item, $selected, true)) {
                $remaining[] = $item;
            }
        }
        return $remaining;
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
