<?php
namespace app\adminapi\controller\sv;
use app\adminapi\lists\sv\DeviceLists;
use app\adminapi\logic\sv\DeviceLogic;
use app\adminapi\validate\sv\DeviceValidate;
use app\adminapi\controller\BaseAdminController;
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
}