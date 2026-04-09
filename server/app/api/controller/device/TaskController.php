<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\logic\device\TaskLogic;
use app\api\lists\device\TaskLists;
use app\api\validate\device\TaskValidate;

/**
 * TaskController
 * @desc 设备任务
 * @author Qasim
 */
class TaskController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new TaskLists());
    }

    public function check()
    {
        try {
            $params = (new TaskValidate())->post()->goCheck('check');
            $result = TaskLogic::check($params);
            if ($result) {
                return $this->success(data: TaskLogic::getReturnData());
            }
            return $this->fail(TaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            //TaskLogic::execDeviceTaskCron();

        } catch (\Throwable $th) {
            //throw $th;
            print_r($th->__toString());
            die;
        }
    }
}
