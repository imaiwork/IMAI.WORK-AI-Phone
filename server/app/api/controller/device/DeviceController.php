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

    public array $notNeedLogin = ['bind', 'accountLists'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new DeviceLists());
    }

    public function accountLists()
    {
        return $this->dataLists(new \app\api\lists\sv\AllAccountLists());
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

    public function qrcode()
    {
        try {
            $params = $this->request->param();
            $result = DeviceLogic::qrcode($params);
            if ($result) {
                return $this->data(DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function scanOldQrcode()
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('scanOldQrcode');
            $result = DeviceLogic::scanOldQrcode($params);
            if ($result) {
                return $this->success('添加成功', DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function used()
    {
        try {
            $params = $this->request->post();
            $result = DeviceLogic::used($params);
            if ($result) {
                return $this->success('操作成功', DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function redeem()
    {
        try {
            $params = (new DeviceValidate())->post()->goCheck('redeem');
            $result = DeviceLogic::redeemCdk($params);
            if ($result) {
                return $this->success('兑换成功', DeviceLogic::getReturnData());
            }
            return $this->fail(DeviceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function active()
    {
        return $this->redeem();
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

    public function removePersona()
    {
        try {
            $params = $this->request->post();
            $result = DeviceLogic::removePersona($params);
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