<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\DeviceValidate;
use app\api\logic\auto\DeviceLogic;
use app\api\lists\auto\DeviceLists;

/**
 * DeviceController
 * @desc 设备自动任务
 * @author Qasim
 */
class DeviceController extends BaseApiController
{

    public array $notNeedLogin = [];

    public function add(){
        try {
            $params = (new DeviceValidate())->post()->goCheck('add');
            $result = DeviceLogic::add($params);
            if ($result) {
                return $this->success(data: DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail(){
        try {
            $params = (new DeviceValidate())->get()->goCheck('detail');
            $result = DeviceLogic::detail($params);
            if ($result) {
                return $this->success(data: DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}