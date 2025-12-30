<?php


namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auto\AutoDeviceActiveConfig;

/**
 * 自动任务列表
 * Class ActiveLists
 * @package app\api\lists\auto
 * @author Qasim
 */
class ActiveLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'device_code'],
            '%like%' => ['device_name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return AutoDeviceActiveConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
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
        return AutoDeviceActiveConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

