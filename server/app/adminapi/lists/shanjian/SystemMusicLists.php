<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\SystemMusic;
use app\common\model\shanjian\SystemMusicGroup;
use app\common\service\FileService;

/**
 * 公共音乐库列表（后台管理页）。
 * 两个来源：center=模板中心同步（内容只读）、local=站长后台上传（可改名/删除）。
 * 本站只维护 status（显隐）、sort_override（本站排序）与 group_id（分组）。
 */
class SystemMusicLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'enabled', 'source', 'group_id'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $rows = SystemMusic::where($this->searchWhere)
            // 与前台同一套生效排序：本站排序优先，否则跟随模板中心（小的在前）
            ->orderRaw(SystemMusic::SORT_EXPRESSION . ' ASC, update_time DESC')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item->audio_url = FileService::getFileUrl((string)$item->audio_url, '', true);
                $sort = (int)$item->getData('sort');
                $sortOverride = $item->getData('sort_override');
                $item->sort = $sort;
                $item->sort_override = $sortOverride === null ? null : (int)$sortOverride;
                $item->effective_sort = $sortOverride === null ? $sort : (int)$sortOverride;
                // 类型统一成整型：前端用 === 1 判断开关，别依赖数据库驱动的返回类型
                $item->status = (int)$item->getData('status');
                $item->enabled = (int)$item->getData('enabled');
                $item->duration_sec = (int)$item->getData('duration_sec');
                // source 决定前端给不给「编辑名称 / 删除」：center 的行为同步维护，改了也会被覆盖
                $item->source = (string)$item->getData('source') === SystemMusic::SOURCE_LOCAL
                    ? SystemMusic::SOURCE_LOCAL
                    : SystemMusic::SOURCE_CENTER;
                $item->update_time = $item->getData('update_time')
                    ? date('Y-m-d H:i:s', (int)$item->getData('update_time'))
                    : null;
                $item->create_time = $item->getData('create_time')
                    ? date('Y-m-d H:i:s', (int)$item->getData('create_time'))
                    : null;
            })
            ->toArray();

        // 分组名单独取一次，避免 join 让 SORT_EXPRESSION 里的裸列名撞车；
        // group_name 为空表示无分组，前端显示「无」
        $groupMap = $this->groupNameMap();
        foreach ($rows as &$row) {
            $groupId = (int)($row['group_id'] ?? SystemMusicGroup::NO_GROUP);
            $row['group_id'] = $groupId;
            $row['group_name'] = $groupMap[$groupId] ?? '';
        }
        unset($row);

        return $rows;
    }

    public function count(): int
    {
        return SystemMusic::where($this->searchWhere)->count();
    }

    /** @return array<int, string> group_id => 分组名 */
    private function groupNameMap(): array
    {
        $rows = SystemMusicGroup::field('id,name')->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = (string)$row['name'];
        }
        return $map;
    }
}
