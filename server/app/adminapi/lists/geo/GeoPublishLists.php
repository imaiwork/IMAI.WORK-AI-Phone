<?php

namespace app\adminapi\lists\geo;

use app\adminapi\lists\BaseAdminDataLists;
use think\facade\Db;

/**
 * GEO 发布记录列表(投递台账,全租户)。
 * 联表 geo_project 取品牌名,软删条件手动加(alias 下不走模型 SoftDelete 自动条件)。
 */
class GeoPublishLists extends BaseAdminDataLists
{
    public function lists(): array
    {
        $list = $this->buildQuery()
            ->field('gp.id, gp.user_id, gp.project_id, gp.title, gp.media_name, gp.mode, gp.channel, gp.media_type, gp.status, gp.error_msg, gp.published_url, gp.account, gp.site_name, gp.cost, gp.publish_time, gp.create_time, gp.stat_views, gp.stat_likes, gp.stat_comments, p.brand_name')
            ->order('gp.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($list as &$row) {
            $row['create_time'] = $row['create_time'] ? date('Y-m-d H:i:s', (int)$row['create_time']) : '';
            $row['publish_time'] = $row['publish_time'] ? date('Y-m-d H:i:s', (int)$row['publish_time']) : '';
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
        return Db::name('geo_publish')->alias('gp')
            ->leftJoin('geo_project p', 'p.id = gp.project_id')
            ->whereNull('gp.delete_time')
            ->when(!empty($get['title']), function ($q) use ($get) {
                $q->whereLike('gp.title', '%' . $get['title'] . '%');
            })
            ->when(!empty($get['status']), function ($q) use ($get) {
                $q->where('gp.status', $get['status']);
            })
            ->when(!empty($get['mode']), function ($q) use ($get) {
                $q->where('gp.mode', $get['mode']);
            })
            ->when(!empty($get['brand_name']), function ($q) use ($get) {
                $q->whereLike('p.brand_name', '%' . $get['brand_name'] . '%');
            })
            ->when(!empty($get['start_time']) && !empty($get['end_time']), function ($q) use ($get) {
                $q->whereBetween('gp.create_time', [strtotime($get['start_time']), strtotime($get['end_time'])]);
            });
    }
}
