<?php

namespace app\adminapi\lists\storyboard;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\storyboard\StoryboardVideoTask;

class StoryboardVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            'in' => ['sj.status'],
            '%like%' => ['sj.name', 'u.nickname', 'sj.video_result_url'],
            '=' => ['sj.video_setting_id'],
        ];
    }

    public function lists(): array
    {
        $list = StoryboardVideoTask::alias('sj')
            ->field('sj.*,u.nickname')
            ->join('user u', 'u.id = sj.user_id');

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
        $count = StoryboardVideoTask::alias('sj')
            ->join('user u', 'u.id = sj.user_id');

        return $count->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
