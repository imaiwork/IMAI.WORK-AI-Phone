<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\AccountKeywordValidate;
use app\api\logic\sv\AccountKeywordLogic;
use app\api\lists\sv\AccountKeywordLists;

/**
 * AccountKeywordController
 * @desc 账号关键词
 * @author Qasim
 */
class AccountKeywordController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取关键词列表
     */
    public function lists()
    {
        if (!$this->request->get('account', '')) {
            return $this->fail('账号不能为空');
        }
        if (!$this->request->get('type', '')) {
            return $this->fail('账号类型不能为空');
        }

        return $this->dataLists(new AccountKeywordLists());
    }

    /**
     * @desc 添加账号关键词
     */
    public function add()
    {
        try {
            $params = (new AccountKeywordValidate())->post()->goCheck('add');
            $result = AccountKeywordLogic::addAccountKeyword($params);
            if ($result) {
                return $this->success(data: AccountKeywordLogic::getReturnData());
            }
            return $this->fail(AccountKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新账号关键词
     */
    public function update()
    {
        try {
            $params = (new AccountKeywordValidate())->post()->goCheck('update');
            $result = AccountKeywordLogic::updateAccountKeyword($params);
            if ($result) {
                return $this->success(data: AccountKeywordLogic::getReturnData());
            }
            return $this->fail(AccountKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除账号关键词
     */
    public function delete()
    {
        try {
            $params = (new AccountKeywordValidate())->post()->goCheck('delete');
            $result = AccountKeywordLogic::deleteAccountKeyword($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(AccountKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 导入账号关键词
     */
    public function import()
    {
        try {
            $params = (new AccountKeywordValidate())->post()->goCheck('import');
            $result = AccountKeywordLogic::importAccountKeyword($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(AccountKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
