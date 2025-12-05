<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;

class ShanjianVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['sj.video_setting_id'],
            'in' => ['sj.status'],
            '%like%' => ['sj.name','u.nickname','sa.authorized_url','sa.anchor_url','sj.video_result_url','sj.card_introduced','sj.card_name']
        ];
    }

    public function lists(): array
    {
        $list = ShanjianVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id')
            ->join('shanjian_anchor sa', 'sa.anchor_id = sj.anchor_id')
            ->field('sj.*,u.nickname,sa.authorized_url,sa.anchor_url')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return ShanjianVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id')
            ->join('shanjian_anchor sa', 'sa.anchor_id = sj.anchor_id')->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->where($this->searchWhere)->count();
    }
}
