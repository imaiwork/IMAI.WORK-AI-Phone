<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\logic\device\ClawLogic;
use think\exception\HttpResponseException;

/**
 * ClawController
 * @desc 抓取任务
 * @author Qasim
 */
class ClawController extends BaseApiController
{

    public array $notNeedLogin = ['getInfo', 'getTask', 'setAccount', 'unbindReport'];

    public function getInfo()
    {
        try {
            $params = $this->request->get();
            $result = ClawLogic::getInfo($params);
            if ($result) {
                return $this->data(ClawLogic::getReturnData());
            }
            return $this->fail(ClawLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function getTask()
    {
        try {
            $params = $this->request->get();
            $result = ClawLogic::getTask($params);
            if ($result) {
                return $this->data(ClawLogic::getReturnData());
            }
            return $this->fail(ClawLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function setAccount()
    {
        try {
            $params = $this->request->post();
            $result = ClawLogic::setAccount($params);
            if ($result) {
                return $this->data(ClawLogic::getReturnData());
            }
            return $this->fail(ClawLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 手机收到 1212 清本地码后的解绑上报（替代原 WS 1213）
     */
    public function unbindReport()
    {
        try {
            $params = $this->request->post();
            $result = ClawLogic::unbindReport($params);
            if ($result) {
                return $this->data(ClawLogic::getReturnData());
            }
            return $this->fail(ClawLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}