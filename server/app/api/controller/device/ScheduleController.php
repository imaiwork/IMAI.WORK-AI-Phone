<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\device\ScheduleValidate;
use app\api\logic\device\ScheduleLogic;
use app\api\lists\device\ScheduleLists;

/**
 * ScheduleController
 * @desc 设备执行计划任务
 * @author Qasim
 */
class ScheduleController extends BaseApiController
{

    public array $notNeedLogin = [];

    public function lists()
    {
        try {
            $params = $this->request->get();
            $result = ScheduleLogic::lists($params);
            if ($result) {
                return $this->data(ScheduleLogic::getReturnData());
            }
            return $this->fail(ScheduleLogic::getError());
        } catch (HttpResponseException $e) {
            print_r($e->__toString());die;
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = $this->request->post();
            $result = ScheduleLogic::update($params);
            if ($result) {
                return $this->data(ScheduleLogic::getReturnData());
            }
            return $this->fail(ScheduleLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}