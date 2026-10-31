<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\SystemMusic;
use app\common\model\shanjian\SystemMusicGroup;

/**
 * 公共音乐分组列表（后台管理页）。
 * 附带每个分组下的曲目数（含前台已隐藏的曲目，管理视角要看到真实归属）。
 */
class SystemMusicGroupLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $rows = SystemMusicGroup::where($this->searchWhere)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        if ($rows === []) {
            return [];
        }

        $countMap = $this->musicCountMap();
        $list = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $list[] = [
                'id' => $id,
                'name' => (string)$row['name'],
                'sort' => (int)$row['sort'],
                'music_count' => $countMap[$id] ?? 0,
                'create_time' => $row['create_time'] ? date('Y-m-d H:i:s', (int)$row['create_time']) : null,
                'update_time' => $row['update_time'] ? date('Y-m-d H:i:s', (int)$row['update_time']) : null,
            ];
        }
        return $list;
    }

    public function count(): int
    {
        return SystemMusicGroup::where($this->searchWhere)->count();
    }

    /** @return array<int, int> group_id => 曲目数 */
    private function musicCountMap(): array
    {
        $rows = SystemMusic::where('group_id', '>', SystemMusicGroup::NO_GROUP)
            ->field('group_id, COUNT(*) AS music_count')
            ->group('group_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['group_id']] = (int)$row['music_count'];
        }
        return $map;
    }
}
