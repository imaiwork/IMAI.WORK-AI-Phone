<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianVideoTask;

class ShanjianVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['sj.video_setting_id', 'sj.shanjian_type','sj.persona_id','sj.is_final'],
            'in' => ['sj.status'],
            '%like%' => ['sj.name', 'u.nickname', 'sa.authorized_url', 'sa.anchor_url', 'sj.video_result_url', 'sj.card_introduced', 'sj.card_name']
        ];
    }

    public function lists(): array
    {

        $shanjian_type = $this->request->get('shanjian_type', 1);
        $list = ShanjianVideoTask::alias('sj')
            ->field('sj.*,u.nickname')
            ->leftJoin('user u', 'u.id = sj.user_id')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })
            ->where($this->searchWhere)
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
               $item->append(['queue_status_text']);
               if ($item->status == 2){
                   $item->video_token = 0;
               }
                $item->authorized_url = '';
                $item->anchor_url = '';
               if ($item->shanjian_type == 1){
                   $shanjian_anchor = ShanjianAnchor::where('anchor_id', $item->anchor_id)->find();
                   $item->authorized_url = $shanjian_anchor->authorized_url ?? '';
                   $item->anchor_url = $shanjian_anchor->anchor_url ?? '';
               }
               

            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {

        $shanjian_type = $this->request->get('shanjian_type', 1);
        $count = ShanjianVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id');
        return $count->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
