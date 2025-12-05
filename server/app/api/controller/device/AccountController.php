<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\AccountLists;

/**
 * AccountController
 * @desc 设备账号任务
 * @author Qasim
 */
class AccountController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new AccountLists());
    }
}