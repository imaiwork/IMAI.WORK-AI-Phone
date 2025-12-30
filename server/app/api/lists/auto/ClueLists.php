<?php


namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auto\AutoDeviceClueConfig;

/**
 * 线索词自动任务列表
 * Class ClueLists
 * @package app\api\lists\auto
 * @author Qasim
 */
class ClueLists extends BaseApiDataLists implements ListsSearchInterface
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

        return AutoDeviceClueConfig::alias('dt')
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
        return AutoDeviceClueConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

