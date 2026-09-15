<?php


namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auto\AutoDeviceTouchConfig;

/**
 * 截流词自动任务列表
 * Class TouchLists
 * @package app\api\lists\auto
 * @author Qasim
 */
class TouchLists extends BaseApiDataLists implements ListsSearchInterface
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

        return AutoDeviceTouchConfig::alias('dt')
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
        return AutoDeviceTouchConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

