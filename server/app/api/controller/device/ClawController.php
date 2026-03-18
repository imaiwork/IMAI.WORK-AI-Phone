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

    public array $notNeedLogin = ['getInfo', 'getTask'];

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
}