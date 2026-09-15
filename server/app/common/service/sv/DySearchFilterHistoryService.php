<?php

namespace app\common\service\sv;

use app\common\model\sv\SvDySearchFilterHistory;

/**
 * 抖音获客线索词与话术历史：一人一行，新内容在前、去重、每列最多100条。
 */
class DySearchFilterHistoryService
{
    public const MAX_ITEMS = 100;

    /**
     * 用 incoming 全量替换 existing。incoming 为空时保持 existing 不变。
     *
     * @param mixed $existing
     * @param mixed $incoming
     * @return array<int, string>
     */
    public static function replaceList($existing, $incoming): array
    {
        $incoming = self::normalizeList($incoming);
        if ($incoming === []) {
            return array_slice(self::normalizeList($existing), 0, self::MAX_ITEMS);
        }
        return array_slice($incoming, 0, self::MAX_ITEMS);
    }

    /**
     * 仅将 incoming 中尚未存在的项插到 existing 首位，旧项不重排，再截断。
     *
     * @param mixed $existing
     * @param mixed $incoming
     * @return array<int, string>
     */
    public static function mergeList($existing, $incoming): array
    {
        $incoming = self::normalizeList($incoming);
        $existing = self::normalizeList($existing);
        if ($incoming === []) {
            return array_slice($existing, 0, self::MAX_ITEMS);
        }
        $seen = [];
        foreach ($existing as $item) {
            $seen[$item] = true;
        }
        $newItems = [];
        foreach ($incoming as $item) {
            if (!isset($seen[$item])) {
                $newItems[] = $item;
                $seen[$item] = true;
            }
        }
        return array_slice(array_merge($newItems, $existing), 0, self::MAX_ITEMS);
    }

    /**
     * 指定账号名单不当线索词历史。
     * keyword 模式线索词全量反转，提交尾部优先写入历史；话术不反转。
     *
     * @param mixed $keywords
     * @param mixed $remarks
     * @param mixed $missScripts
     * @return array{keywords: array<int, string>, remarks: array<int, string>, miss_scripts: array<int, string>}
     */
    public static function incomingForSave(string $searchMode, $keywords, $remarks, $missScripts): array
    {
        $keywordList = $searchMode === 'keyword'
            ? array_values(array_reverse(self::normalizeList($keywords)))
            : [];

        return [
            'keywords'     => $keywordList,
            'remarks'      => self::normalizeList($remarks),
            'miss_scripts' => self::normalizeList($missScripts),
        ];
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    public static function normalizeList($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : ($value !== '' ? [$value] : []);
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $one = trim((string)$item);
            if ($one !== '') {
                $out[] = $one;
            }
        }
        return array_values($out);
    }

    /**
     * 任务创建成功后合并写入；空内容且无旧行则跳过。
     *
     * @param mixed $keywords
     * @param mixed $remarks
     * @param mixed $missScripts
     */
    public static function saveAfterAdd(int $userId, string $searchMode, $keywords, $remarks, $missScripts): void
    {
        if ($userId < 1) {
            return;
        }
        $incoming = self::incomingForSave($searchMode, $keywords, $remarks, $missScripts);
        $row = SvDySearchFilterHistory::withTrashed()->where('user_id', $userId)->findOrEmpty();
        if (!$row->isEmpty() && (int)($row->delete_time ?? 0) > 0) {
            $row->delete_time = null;
        }
        if ($row->isEmpty()) {
            if ($incoming['keywords'] === [] && $incoming['remarks'] === [] && $incoming['miss_scripts'] === []) {
                return;
            }
            SvDySearchFilterHistory::create([
                'user_id'      => $userId,
                'keywords'     => $incoming['keywords'],
                'remarks'      => $incoming['remarks'],
                'miss_scripts' => $incoming['miss_scripts'],
            ]);
            return;
        }

        // keyword 模式下，本次提交的关键词清单视为用户的最新选择，需将被删除的词从历史中移除；
        // account 模式不产出关键词历史，保持原有合并逻辑即可。
        if ($searchMode === 'keyword') {
            $row->keywords = self::replaceList($row->keywords, $incoming['keywords']);
        } else {
            $row->keywords = self::mergeList($row->keywords, $incoming['keywords']);
        }
        $row->remarks = $incoming['remarks'];
        $row->miss_scripts = $incoming['miss_scripts'];
        $row->save();
    }

    /**
     * @return array{keywords: array<int, string>, remarks: array<int, string>, miss_scripts: array<int, string>}
     */
    public static function getHistory(int $userId): array
    {
        $empty = [
            'keywords'     => [],
            'remarks'      => [],
            'miss_scripts' => [],
        ];
        if ($userId < 1) {
            return $empty;
        }
        $row = SvDySearchFilterHistory::where('user_id', $userId)->findOrEmpty();
        if ($row->isEmpty()) {
            return $empty;
        }
        return [
            'keywords'     => self::normalizeList($row->keywords),
            'remarks'      => self::normalizeList($row->remarks),
            'miss_scripts' => self::normalizeList($row->miss_scripts),
        ];
    }
}
