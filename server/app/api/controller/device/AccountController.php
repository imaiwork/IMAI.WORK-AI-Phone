<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\AccountLists;
use app\api\logic\device\AccountLogic;
use app\api\validate\device\AccountValidate;
use think\exception\HttpResponseException;

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

    /**
     * @desc 触发 RPA 602 拉取平台账号（异步落库）
     */
    public function fetch()
    {
        try {
            $params = (new AccountValidate())->post()->goCheck('fetch');
            $result = AccountLogic::fetch($params);
            if ($result) {
                return $this->success('已下发获取账号指令', AccountLogic::getReturnData());
            }
            return $this->fail(AccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}