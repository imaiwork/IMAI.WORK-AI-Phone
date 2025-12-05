<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\logic\device\TaskLogic;
use app\api\lists\device\CalendarTaskLists;

/**
 * TaskController
 * @desc 设备任务
 * @author Qasim
 */
class CalendarTaskController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new CalendarTaskLists());
    }


    public function  statistics()
    {
        try {
            $day = $this->request->get('day',date('Y-m-d'));
            $device_code = $this->request->get('device_code');
            $result = TaskLogic::statistics($day,$device_code);
            if ($result) {
                return $this->data(TaskLogic::getReturnData());
            }
            return $this->fail(TaskLogic::getError());
        } catch (\Throwable $th) {
            return $this->fail($th->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete(){
        try {
            $params = $this->request->param();
            $result = TaskLogic::deleteTask($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(TaskLogic::getError());
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage() ?? '');

        }
    }


    public function subtasks()
    {
        try {
            $params = $this->request->post();
            $result = TaskLogic::subtasks($params);
            if ($result) {
                return $this->success(data: TaskLogic::getReturnData());
            }
            return $this->fail(TaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function updateName()
    {
        try {
            $params = $this->request->post();
            $result = TaskLogic::updateName($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(TaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}