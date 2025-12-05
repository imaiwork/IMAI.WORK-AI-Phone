<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\device\ActiveValidate;
use app\api\logic\device\ActiveLogic;
use app\api\lists\device\ActiveLists;

/**
 * ActiveController
 * @desc 设备养号任务
 * @author Qasim
 */
class ActiveController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new ActiveLists());
    }

    public function add()
    {
        try {
            $params = (new ActiveValidate())->post()->goCheck('add');
            $result = ActiveLogic::add($params);
            if ($result) {
                return $this->success(data: ActiveLogic::getReturnData());
            }
            return $this->fail(ActiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update() {}

    /**
     * @desc 删除机器人
     */
    public function delete()
    {
        try {
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 详情
     */
    public function subtasks()
    {
        try {
            $params = $this->request->post();
            $result = ActiveLogic::subtasks($params);
            if ($result) {
                return $this->success(data: ActiveLogic::getReturnData());
            }
            return $this->fail(ActiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            //DeviceActiveLogic::execDeviceActiveCron();

        } catch (\Throwable $th) {
            //throw $th;
            print_r($th->__toString());
            die;
        }
    }
}
