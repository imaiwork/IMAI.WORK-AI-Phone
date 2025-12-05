<?php

namespace app\adminapi\controller\recharge;


use app\adminapi\lists\recharge\GiftPackageOrderLists;
use app\adminapi\controller\BaseAdminController;



/**
 * 礼物包订单
 * Class GiftPackageOrderController
 * @package app\adminapi\controller\recharge
 */
class GiftPackageOrderController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists()
    {
        return $this->dataLists(new GiftPackageOrderLists());
    }
}
