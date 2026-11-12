<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\MaterialUseLog;

/**
 * 素材使用次数统计：为「最少使用优先」选材提供次数口径。
 *
 * 目的：混剪素材每轮独立随机抽样会让部分素材反复用 4~5 次、部分一次不用；
 * 改为按使用次数升序取用后，同一人设下的素材会自然轮转，无需设置使用上限。
 */
class MaterialUsageService
{
    /** 自动任务标记（与素材列表「已用N次」口径一致） */
    private const AUTO_TASK = 1;

    /**
     * 统计指定人设下一批素材的使用次数。
     *
     * @param int $personaId 人设 ID
     * @param array<int, int|string> $materialIds 素材 ID 列表
     * @param bool $includeManual 是否统计热点追踪等手动任务（auto_type=0）
     * @return array<int, int> material_id => 使用次数
     */
    public static function countsForPersona(int $personaId, array $materialIds, bool $includeManual = false): array
    {
        if ($personaId <= 0 || $materialIds === []) {
            return [];
        }
        $materialIds = array_values(array_unique(array_filter(array_map('intval', $materialIds))));
        if ($materialIds === []) {
            return [];
        }

        $query = MaterialUseLog::alias('log')
            ->join('shanjian_video_task task', 'task.id = log.task_id')
            ->where('log.persona_id', $personaId)
            ->where('log.use_scene', MaterialUseLog::USE_SCENE_AI_GENERATE)
            // 生成中也算已用：同一批建单时前一条刚提交、还没回调，不能重复选同一条
            ->whereIn('log.use_status', [MaterialUseLog::USE_STATUS_USING, MaterialUseLog::USE_STATUS_SUCCESS])
            ->whereIn('log.material_id', $materialIds);
        if (!$includeManual) {
            $query->where('task.auto_type', self::AUTO_TASK);
        }

        $rows = $query
            ->field('log.material_id, COUNT(*) AS use_count')
            ->group('log.material_id')
            ->select()
            ->toArray();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['material_id']] = (int)$row['use_count'];
        }
        return $counts;
    }

    /**
     * 从候选素材中提取自有素材 ID：广场素材 id 与自有素材不同空间，不能混算次数。
     *
     * @param array<int, mixed> $items
     * @return array<int, int>
     */
    public static function ownMaterialIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (!is_array($item) || ($item['source'] ?? '') === 'square') {
                continue;
            }
            $id = (int)($item['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * 按使用次数升序选取素材（次数少的先取，同档随机）。
     *
     * @param array<int, array<string, mixed>> $items
     * @param int $need 需要条数
     * @param int $personaId 人设 ID
     * @param bool $includeManual 是否统计手动任务
     * @return array<int, array<string, mixed>>
     */
    public static function pickLeastUsed(array $items, int $need, int $personaId, bool $includeManual = false): array
    {
        if ($need <= 0 || $items === []) {
            return [];
        }
        $usage = self::countsForPersona($personaId, self::ownMaterialIds($items), $includeManual);
        return LeastUsedPicker::pick($items, $need, $usage);
    }
}
