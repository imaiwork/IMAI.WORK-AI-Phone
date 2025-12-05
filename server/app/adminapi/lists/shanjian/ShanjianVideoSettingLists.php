<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;

class ShanjianVideoSettingLists extends BaseAdminDataLists implements ListsSearchInterface
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
        $list = ShanjianVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('sj.*,u.nickname')
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                $item['video_token'] = ShanjianVideoTask::where('video_setting_id', $item->id)->sum('video_token');
                if ($item['video_token'] > 0) {
                    $item['video_token'] = sprintf('%.2f',   $item['video_token']);
                }
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return ShanjianVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')  ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->where($this->searchWhere)->count();
    }
}
