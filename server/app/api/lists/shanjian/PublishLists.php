<?php


namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvPublishSettingAccount;

/**
 * 发布设置列表
 * Class PublishLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class PublishLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['ps.status', 'ps.media_type'],
            '%like%' => ['ps.name', 'a.account']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.media_type', '=', $this->request->get('media_type', 1)];
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 2)];

        return SvPublishSetting::alias('ps')
            ->field('ps.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $times = SvPublishSettingDetail::where('publish_id', $item['id'])->column('publish_time');
                $item['publish_end'] = !empty($times) ? date('Y-m-d', strtotime(max($times))) : date('Y-m-d', time());
                
                $item['publish_cycle'] = $item['publish_end'] == '' ? 0 : ceil((strtotime($item['publish_end'].' 23:59:59') - strtotime($item['publish_start'].' 00:00:00')) / 86400);

                $item['count'] = SvPublishSettingAccount::where('publish_id', $item['id'])->sum('count');
                $item['published_count'] = SvPublishSettingDetail::where('publish_id', $item['id'])->where('status', 'in', [1, 2])->count();

                $publishCount= SvPublishSettingDetail::where('publish_id', $item['id'])->where('status', 'in', [0, 1])->count();
                if($item['status'] == 3 && $publishCount > 0){
                    $item['status'] = 2;
                    $item->save();
                }

                if($item['status'] == 2 && $item['published_count'] == $item['count']){
                    $item['status'] = 3;
                    if($item['publish_end'] == ''){
                        unset($item['publish_end']);
                    }
                    $item->save();
                }

                $times = SvPublishSettingDetail::where('publish_id', $item['id'])->where('status', 'in', [0, 3])->column('publish_time');
                $item['times'] = array_map(function($time){
                    return date('h:i A', strtotime($time));
                }, $times);
            })
            ->toArray();

    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.media_type', '=', $this->request->get('media_type', 1)];
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 2)];
        return SvPublishSetting::alias('ps')
            ->field('ps.*')
            ->where($this->searchWhere)
            ->count();
    }
}
