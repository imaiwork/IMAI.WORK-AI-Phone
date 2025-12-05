<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\DeviceLogic;
use app\api\validate\wechat\DeviceValidate;
use app\api\lists\wechat\DeviceLists;
use think\exception\HttpResponseException;

/**
 * DeviceController
 * @desc 设备
 * @author Qasim
 */
class DeviceController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取设备列表
     */
    public function lists()
    {
        return $this->dataLists(new DeviceLists());
    }

    /**
     * @desc 添加设备
     */
    public function add()
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('add');
            $result = DeviceLogic::addDevice($params);
            if ($result) {
                return $this->success(data: DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('update');
            $result = DeviceLogic::updateDevice($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除设备
     */
    public function remove()
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('remove');
            $result = DeviceLogic::removeDevice($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
