<?php
declare (strict_types=1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\sop\IndexLogic;
use think\exception\HttpResponseException;

/**
 * FlowController
 * @desc SOP流程管理
 */
class IndexController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 获取好友所处流程
     */
    public function getFriendFlow()
    {
        try {
            $params = $this->request->get();
            $result = IndexLogic::getFriendFlow($params);
            if ($result) {
                return $this->success(data: IndexLogic::getReturnData());
            }
            return $this->fail(IndexLogic::getError());
        } catch (HttpResponseException $e) {

            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 获取好友sop记录
     */
    public function getFriendPushLog()
    {
        try {
            $params = $this->request->get();
            $result = IndexLogic::getFriendPushLog($params);
            if ($result) {
                return $this->success(data: IndexLogic::getReturnData());
            }
            return $this->fail(IndexLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除好友sop记录
     */
    public function deleteFriendPushLog()
    {
        try {
            $params = $this->request->post();
            $result = IndexLogic::deleteFriendPushLog($params);
            if ($result) {
                return $this->success(data: IndexLogic::getReturnData());
            }
            return $this->fail(IndexLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 记录列表
     */
    public function getPushLogList()
    {
        try {
            $params = $this->request->get();
            $result = IndexLogic::getPushLogList($params);
            if ($result) {
                return $this->success(data: IndexLogic::getReturnData());
            }
            return $this->fail(IndexLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}