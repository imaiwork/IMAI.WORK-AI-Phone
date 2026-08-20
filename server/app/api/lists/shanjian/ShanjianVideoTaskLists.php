<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoTask;

class ShanjianVideoTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['video_setting_id', 'shanjian_type','persona_id','is_final'],
            'in' => ['status'],
        ];
    }

    /**
     * 默认隐藏中间过渡任务(is_final=0, 如开启智剪的 type=5),
     * 列表/发布优先取最终可用视频(is_final=1); 显式传入 is_final 时按需过滤
     */
    private function applyFinalFilter(): void
    {
        if ($this->request->get('is_final', '') === '') {
            $this->searchWhere[] = ['is_final', '=', 1];
        }
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->applyFinalFilter();
        $list = ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })
            ->where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
               $item->append(['queue_status_text', 'download_status_text']);
               if ($item->status == 2){
                   $item->video_token = 0;
               }

            })->toArray();
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->applyFinalFilter();
        return ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }
}
