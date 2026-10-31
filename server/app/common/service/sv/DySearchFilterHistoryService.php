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
     * 从列表中剔除指定项（用于同步用户在表单里删掉的线索词）。
     *
     * @param mixed $existing
     * @param mixed $removed
     * @return array<int, string>
     */
    public static function excludeList($existing, $removed): array
    {
        $existing = self::normalizeList($existing);
        $removed = self::normalizeList($removed);
        if ($removed === []) {
            return $existing;
        }
        $removedMap = array_fill_keys($removed, true);
        return array_values(array_filter($existing, static function (string $item) use ($removedMap): bool {
            return !isset($removedMap[$item]);
        }));
    }

    /**
     * 任务创建成功后合并写入；空内容且无旧行则跳过。
     *
     * @param mixed $keywords
     * @param mixed $remarks
     * @param mixed $missScripts
     * @param mixed $removedKeywords 前端随单上报的「本次在表单里删掉的线索词」，从历史同步移除
     */
    public static function saveAfterAdd(int $userId, string $searchMode, $keywords, $remarks, $missScripts, $removedKeywords = []): void
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

        // 关键词合并去重：旧词不丢、新词插到最前；本次提交里被用户从表单删掉的词从历史移除。
        // account 模式不产出关键词历史（incoming 为空），mergeList 保持旧词不重排。
        $row->keywords = self::mergeList(self::excludeList($row->keywords, $removedKeywords), $incoming['keywords']);
        // 话术/备注：非空传入全量替换（覆盖删除场景），空传入保持历史不变（防止「直接跳过」或关备注把历史清空）。
        $row->remarks = self::replaceList($row->remarks, $incoming['remarks']);
        $row->miss_scripts = self::replaceList($row->miss_scripts, $incoming['miss_scripts']);
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
