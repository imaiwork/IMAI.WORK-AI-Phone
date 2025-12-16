<?php

namespace app\adminapi\lists\sora;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;

class SoraVideoSettingLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['sj.name','u.nickname'],
            'in' => ['sj.status'],
        ];
    }

    public function lists(): array
    {
        $list = SoraVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('sj.*,u.nickname')
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                $item['image_count'] = json_decode($item->extra,true) ? json_decode($item->extra,true)['image_counts'] : 0;
                $item['video_token'] = SoraVideoTask::where('video_setting_id', $item->id)->sum('video_token');
                if ($item['video_token'] > 0) {
                    $item['video_token'] = sprintf('%.2f',   $item['video_token']);
                }
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return SoraVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')  ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->where($this->searchWhere)->count();
    }
}
