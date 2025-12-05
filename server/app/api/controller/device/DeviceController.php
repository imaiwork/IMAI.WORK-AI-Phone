<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\DeviceLists;
use app\api\logic\device\DeviceLogic;
use app\api\validate\device\DeviceValidate;
use think\exception\HttpResponseException;

/**
 * DeviceController
 * @desc 设备任务
 * @author Qasim
 */
class DeviceController extends BaseApiController
{

    public array $notNeedLogin = ['bind'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new DeviceLists());
    }

    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = DeviceLogic::detail($params);
            if ($result) {
                return $this->data(DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = $this->request->post();
            $result = DeviceLogic::update($params);
            if ($result) {
                return $this->data(DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function remove()
    {
        try {
            $params = $this->request->post();
            $result = DeviceLogic::remove($params);
            if ($result) {
                return $this->data(DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function bind()
    {
        try {
            $params = $this->request->post();
            $result = DeviceLogic::bind($params);
            if ($result) {
                return $this->data(DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}