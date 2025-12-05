<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\logic\device\TaskLogic;
use app\api\lists\device\TaskLists;

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

    public function cron()
    {
        try {
            TaskLogic::execDeviceTaskCron();

        } catch (\Throwable $th) {
            //throw $th;
            print_r($th->__toString());die;
        }
    }
}