<?php

namespace app\common\model\distribution;

use app\common\model\BaseModel;
use app\common\model\user\User;

/**
 * 分销代理模型
 * Class DistributionAgent
 * @package app\common\model\distribution
 */
class DistributionAgent extends BaseModel
{
    /** 下级关系树最大遍历层数，防止脏数据成环 */
    const MAX_TREE_DEPTH = 10;

    protected $name = 'distribution_agent';
    protected $type = [
        'become_time' => 'timestamp',
    ];

    /**
     * @notes 全部层级下线的用户 ID（不含自己）
     *   表里只有 parent_id，没有 path/ancestors，只能逐层查
     * @param int $userId
     * @return array
     */
    public static function getDescendantIds(int $userId): array
    {
        $all = [];
        $frontier = [$userId];

        for ($depth = 0; $depth < self::MAX_TREE_DEPTH; $depth++) {
            $children = self::whereIn('parent_id', $frontier)->column('user_id');
            $children = array_values(array_diff(
                array_unique(array_map('intval', $children)),
                array_merge($all, [$userId])
            ));
            if ($children === []) {
                break;
            }
            $all = array_merge($all, $children);
            $frontier = $children;
        }

        return $all;
    }

    /**
     * @notes 批量统计每个用户的全部层级下线人数（不含自己）
     *   按树逐层往下查，同一层一次 IN 查询，避免列表页对每人单独扫树
     * @param array $userIds
     * @return array<int, int> user_id => 子孙人数
     */
    public static function getDescendantCountMap(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $counts = array_fill_keys($userIds, 0);
        if ($userIds === []) {
            return $counts;
        }

        $rootOf = array_combine($userIds, $userIds);
        $frontier = $userIds;
        $seen = array_fill_keys($userIds, true);

        for ($depth = 0; $depth < self::MAX_TREE_DEPTH; $depth++) {
            $rows = self::whereIn('parent_id', $frontier)
                ->field('user_id, parent_id')
                ->select()
                ->toArray();
            if ($rows === []) {
                break;
            }

            $next = [];
            foreach ($rows as $row) {
                $childId = (int)$row['user_id'];
                $parentId = (int)$row['parent_id'];
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;

                $rootId = (int)($rootOf[$parentId] ?? 0);
                if ($rootId === 0 || !isset($counts[$rootId])) {
                    continue;
                }
                $rootOf[$childId] = $rootId;
                $counts[$rootId]++;
                $next[] = $childId;
            }

            $frontier = $next;
            if ($frontier === []) {
                break;
            }
        }

        return $counts;
    }

    // 关联上级用户
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    // 关联本用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
