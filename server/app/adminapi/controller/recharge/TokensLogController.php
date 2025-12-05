<?php

namespace app\adminapi\controller\recharge;


use app\adminapi\lists\recharge\TokensLogLists;
use app\adminapi\controller\BaseAdminController;



/**
 * tokens消耗情况
 * Class TokensLogController
 * @package app\adminapi\controller\recharge
 */
class TokensLogController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists()
    {
        return $this->dataLists(new TokensLogLists());
    }
}
