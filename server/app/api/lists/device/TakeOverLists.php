<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceTakeOverTask;

/**
 * 设备接管任务列表
 * Class TakeOverLists
 * @package app\api\lists\device
 * @author Qasim
 */
class TakeOverLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['task_type', 'device_code'],
            '%like%' => ['task_name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return SvDeviceTakeOverTask::alias('dt') 
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['account'] = json_decode($item['account'], true);
                $item['time_config'] = json_decode($item['time_config'], true);

                return $item;
            })
            ->toArray();

    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvDeviceTakeOverTask::alias('dt') 
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

