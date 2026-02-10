<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvWechatStrategy;

/**
 * 设备微信任务列表
 * Class WechatLists
 * @package app\api\lists\device
 * @author Qasim
 */
class WechatLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => [ 'device_code'],
            '%like%' => []
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return SvWechatStrategy::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvWechatStrategy::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}

