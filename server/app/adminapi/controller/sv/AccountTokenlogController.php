<?php

namespace app\adminapi\controller\sv;


use app\adminapi\lists\sv\AccountTokenlogLists;
use app\adminapi\controller\BaseAdminController;



/**
 * tokens消耗情况
 * Class AccounttokenlogController
 * @package app\adminapi\controller\sv
 */
class AccountTokenlogController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists()
    {  
        return $this->dataLists(new AccountTokenlogLists());
    }
}