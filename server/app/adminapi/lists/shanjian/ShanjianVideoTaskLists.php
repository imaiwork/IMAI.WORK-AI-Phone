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
            '=' => ['sj.video_setting_id', 'sj.shanjian_type'],
            'in' => ['sj.status'],
            '%like%' => ['sj.name', 'u.nickname', 'sa.authorized_url', 'sa.anchor_url', 'sj.video_result_url', 'sj.card_introduced', 'sj.card_name']
        ];
    }

    public function lists(): array
    {

        $shanjian_type = $this->request->get('shanjian_type', 1);
        $list = ShanjianVideoTask::alias('sj')
            ->field('sj.*,u.nickname')
            ->join('user u', 'u.id = sj.user_id');
        switch ($shanjian_type) {
            case 1:
                $list = $list->field('sa.authorized_url,sa.anchor_url')->join('shanjian_anchor sa', 'sa.anchor_id = sj.anchor_id');
                break;
            case 2:
            case 3:
            case 4:
                break;
            default:
                break;
        }

        $list = $list->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })
            ->where($this->searchWhere)
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
               if ($item->status == 2){
                   $item->video_token = 0;
               }

            })->toArray();
        return $list;
    }

    public function count(): int
    {

        $shanjian_type = $this->request->get('shanjian_type', 1);
        $count = ShanjianVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id');
        switch ($shanjian_type) {
            case 1:
                $count = $count->join('shanjian_anchor sa', 'sa.anchor_id = sj.anchor_id');
                break;
            case 2:
            case 3:
            case 4:
                break;
            default:
                break;
        }

        return $count->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
