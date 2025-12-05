<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvPublishSettingAccount;

/**
 * 发布列表
 * Class AccountLists
 * @package app\adminapi\lists\shanjian
 * @author Lee
 */
class PublishLists extends BaseAdminDataLists implements ListsSearchInterface
{

    public function setSearch(): array
    {
        return [
            '=' => ['ps.status', 'ps.media_type'],
            '%like%' => ['ps.name', 'u.nickname'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 2)];
        // print_r(SvPublishSetting::alias('ps')
        //     ->field('ps.*, u.nickname as user_nickname')
        //     ->join('user u', 'u.id = ps.user_id')
        //     ->where($this->searchWhere)
        //     ->order('id', 'desc')
        //     ->limit($this->limitOffset, $this->limitLength)
        //     ->fetchSql(true)
        //     ->select());die;
        return SvPublishSetting::alias('ps')
            ->field('ps.*, u.nickname as user_nickname')
            ->join('user u', 'u.id = ps.user_id')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $times = SvPublishSettingDetail::where('publish_id', $item['id'])->column('publish_time');
                $item['publish_end'] = !empty($times) ? date('Y-m-d', strtotime(max($times))) : date('Y-m-d', time());

                $item['publish_cycle'] = ceil((strtotime($item['publish_end'] . ' 23:59:59') - strtotime($item['publish_start'] . ' 00:00:00')) / 86400);

                $item['count'] = SvPublishSettingAccount::where('publish_id', $item['id'])->sum('count');
                $item['published_count'] = SvPublishSettingDetail::where('publish_id', $item['id'])->where('status', 'in', [1, 2])->count();

                if ((int)$item['published_count'] > (int)$item['count']) {
                    $item['count'] = $item['published_count'];
                    if ($item->status === 2) {
                        $item->status = 3;
                    }
                }
                $item->save();
                ///$times = SvPublishSettingDetail::where('publish_id', $item['id'])->where('status', 'in', [0, 3])->column('publish_time');
                $item['times'] = array_map(function ($time) {
                    return date('h:i A', strtotime($time));
                }, $times);
                $item['accounts'] = json_decode($item['accounts'], true);
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        //$this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 2)];
        return  SvPublishSetting::alias('ps')
            ->field('ps.*')
            ->join('user u', 'u.id = ps.user_id')
            ->where($this->searchWhere)
            ->count();
    }
}
