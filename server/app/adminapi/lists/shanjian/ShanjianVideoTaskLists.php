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
            ->when($this->excludeHotspot(), function ($query) {
                $query->whereRaw(ShanjianVideoTask::notHotspotSourceCond('sj.'));
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
        })->when($this->excludeHotspot(), function ($query) {
            $query->whereRaw(ShanjianVideoTask::notHotspotSourceCond('sj.'));
        })->where($this->searchWhere)->count();
    }

    /**
     * 是否剔除热点追踪来源的任务。
     * 人设详情「IP 创作记录」只看人设自己的创作，热点追踪有独立的后台模块，
     * 混在一起会把人设记录淹掉（实测人设 271 是 29/48、276 是 10/12 都来自热点追踪）。
     * 默认不剔除，保持混剪创作详情等既有调用方行为不变。
     */
    private function excludeHotspot(): bool
    {
        return !empty($this->params['exclude_hotspot']);
    }
}
