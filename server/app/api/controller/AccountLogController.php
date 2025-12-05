<?php


namespace app\api\controller;

use app\api\lists\AccountLogLists;
use app\api\logic\AccountTokenLogic;

/**
 * 账户流水
 * Class AccountLogController
 * @package app\api\controller
 */
class AccountLogController extends BaseApiController
{
    /**
     * @notes 账户流水
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 14:34
     */
    public function lists()
    {
        return $this->dataLists(new AccountLogLists());
    }


    /**
     * @notes 扣费信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/12/24 10:46
     */
    public function info()
    {
        return $this->data(AccountTokenLogic::info($this->request->get('task_id')));
    }
}
