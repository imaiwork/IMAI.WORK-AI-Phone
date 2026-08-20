<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\DeviceAvailableCDKLists;
use app\adminapi\lists\sv\DeviceLists;
use app\adminapi\logic\sv\DeviceLogic;
use app\adminapi\validate\sv\DeviceValidate;
use think\exception\HttpResponseException;
use think\response\Json;

/**
 *
 * Class WechatController
 * @package app\adminapi\controller
 */
class DeviceController extends BaseAdminController
{
  /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    {
        return $this->dataLists(new DeviceLists());
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

    /**
     * @notes 可选兑换码列表
     */
    public function availableCDKLists()
    {
        (new DeviceValidate())->goCheck('availableCDKLists');
        return $this->dataLists(new DeviceAvailableCDKLists());
    }

    /**
     * @notes 站长代兑兑换码
     */
    public function redeem(): Json
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('redeem');
            $result = DeviceLogic::redeemCdk($params);
            if ($result) {
                return $this->success('兑换成功', DeviceLogic::getReturnData(), 1, 1);
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 设备转移用户
     */
    public function deviceTransfer(): Json
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('deviceTransfer');
            $result = DeviceLogic::deviceTransfer($params);
            if ($result) {
                return $this->success('转移成功', DeviceLogic::getReturnData(), 1, 1);
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}