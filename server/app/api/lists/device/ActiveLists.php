<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceActive;

/**
 * 设备养号任务列表
 * Class ActiveLists
 * @package app\api\lists\device
 * @author Qasim
 */
class ActiveLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['active_type', 'device_code'],
            '%like%' => ['active_name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return SvDeviceActive::alias('dt')
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
        return SvDeviceActive::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

