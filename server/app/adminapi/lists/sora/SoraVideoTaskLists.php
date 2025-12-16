<?php

namespace app\adminapi\lists\sora;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraVideoTask;

class SoraVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['sj.video_setting_id'],
            'in' => ['sj.status'],
            '%like%' => ['sj.name', 'u.nickname', 'sa.authorized_url', 'sa.anchor_url', 'sj.video_result_url', 'sj.card_introduced', 'sj.card_name']
        ];
    }

    public function lists(): array
    {
        $list = SoraVideoTask::alias('sj')
            ->field('sj.*,u.nickname')
            ->join('user u', 'u.id = sj.user_id');

        $list = $list->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
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
        $count = SoraVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id');

        return $count->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
