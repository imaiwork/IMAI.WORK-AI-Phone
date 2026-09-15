<?php

namespace app\api\lists\distributionAgent;

use app\api\lists\BaseApiDataLists;
use app\common\model\cardcode\CardPackage;

/**
 * 分销制卡套餐列表
 * Class DistributionAgentCardPackagesLists
 * @package app\api\lists\distributionAgent
 */
class DistributionAgentCardPackagesLists extends BaseApiDataLists
{
    /**
     * @notes 获取分销制卡套餐列表
     * @return array
     */
    public function lists(): array
    {
        return CardPackage::where('status', 1)
            ->where('delete_time', null)
            ->order('sort desc, id desc')
            ->field('id, name, tokens')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取分销制卡套餐总数
     * @return int
     */
    public function count(): int
    {
        return CardPackage::where('status', 1)
            ->where('delete_time', null)
            ->count();
    }
}
