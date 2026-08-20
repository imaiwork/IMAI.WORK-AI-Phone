<?php

namespace app\adminapi\lists\geo;

use app\adminapi\lists\BaseAdminDataLists;
use think\facade\Db;

/**
 * GEO 项目列表(全租户)。
 * 联表 user 取归属用户,软删条件手动加(alias 下不走模型 SoftDelete 自动条件);
 * 话题/问题/内容/监测量用分组统计一次补齐,避免逐行子查询。
 */
class GeoProjectLists extends BaseAdminDataLists
{
    public function lists(): array
    {
        $list = $this->buildQuery()
            ->field('p.id, p.user_id, p.team_id, p.brand_name, p.website, p.industry, p.gen_model, p.auto_monitor, p.last_auto_date, p.create_time, u.nickname, u.sn AS user_sn')
            ->order('p.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        if (empty($list)) {
            return [];
        }
        $ids = array_column($list, 'id');
        $counts = [
            'topic_count' => $this->groupCount('geo_topic', $ids),
            'question_count' => $this->groupCount('geo_keyword', $ids),
            'content_count' => $this->groupCount('geo_content', $ids),
            'monitor_count' => $this->groupCount('geo_monitor', $ids, false),
        ];
        foreach ($list as &$row) {
            foreach ($counts as $key => $map) {
                $row[$key] = (int)($map[$row['id']] ?? 0);
            }
            $row['create_time'] = $row['create_time'] ? date('Y-m-d H:i:s', (int)$row['create_time']) : '';
        }
        return $list;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    protected function buildQuery()
    {
        $get = $this->request->get();
        return Db::name('geo_project')->alias('p')
            ->leftJoin('user u', 'u.id = p.user_id')
            ->whereNull('p.delete_time')
            ->when(!empty($get['brand_name']), function ($q) use ($get) {
                $q->whereLike('p.brand_name', '%' . $get['brand_name'] . '%');
            })
            ->when(isset($get['auto_monitor']) && $get['auto_monitor'] !== '', function ($q) use ($get) {
                $q->where('p.auto_monitor', (int)$get['auto_monitor']);
            })
            ->when(!empty($get['user_keyword']), function ($q) use ($get) {
                $q->where('u.sn|u.nickname|u.mobile|u.account', 'like', '%' . $get['user_keyword'] . '%');
            })
            ->when(!empty($get['start_time']) && !empty($get['end_time']), function ($q) use ($get) {
                $q->whereBetween('p.create_time', [strtotime($get['start_time']), strtotime($get['end_time'])]);
            });
    }

    /** 按 project_id 分组计数,返回 [project_id => count] */
    protected function groupCount(string $table, array $ids, bool $softDelete = true): array
    {
        $rows = Db::name($table)
            ->whereIn('project_id', $ids)
            ->when($softDelete, function ($q) {
                $q->whereNull('delete_time');
            })
            ->field('project_id, COUNT(*) AS cnt')
            ->group('project_id')
            ->select()
            ->toArray();
        return array_column($rows, 'cnt', 'project_id');
    }
}
