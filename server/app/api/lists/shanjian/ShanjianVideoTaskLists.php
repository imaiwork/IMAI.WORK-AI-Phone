<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;

class ShanjianVideoTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['video_setting_id', 'shanjian_type','persona_id'],
            'in' => ['status'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })
            ->where($this->searchWhere)
            ->order(['id' => 'desc'])
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
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
