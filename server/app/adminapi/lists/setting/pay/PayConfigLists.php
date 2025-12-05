<?php


namespace app\adminapi\lists\setting\pay;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\pay\PayConfig;

/**
 * 支付配置列表
 * Class PayConfigLists
 * @package app\adminapi\lists\setting\pay
 */
class PayConfigLists extends BaseAdminDataLists
{

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 16:15
     */
    public function lists(): array
    {
        $lists = PayConfig::field('id,name,pay_way,icon,sort')
            ->append(['pay_way_name'])
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        return $lists;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 16:15
     */
    public function count(): int
    {
        return PayConfig::count();
    }
}
