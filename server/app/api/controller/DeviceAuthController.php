<?php

namespace app\api\controller;

use app\api\logic\DeviceAuthLogic;
use app\api\validate\DeviceAuthValidate;

class DeviceAuthController extends BaseApiController
{
    public array $notNeedLogin = ['notice'];

    public function phoneList()
    {
        $params = (new DeviceAuthValidate())->goCheck('phoneList');
        $tab = $params['tab'] ?? 'all';
        $lists = DeviceAuthLogic::phoneList($this->userId, $tab);
        return $this->data(['lists' => $lists]);
    }

    public function myCodes()
    {
        $lists = DeviceAuthLogic::myCodes($this->userId);
        return $this->data(['lists' => $lists]);
    }

    public function planList()
    {
        $lists = DeviceAuthLogic::planList();
        return $this->data(['lists' => $lists]);
    }

    public function purchaseCode()
    {
        $params = (new DeviceAuthValidate())->post()->goCheck('purchase', [
            'user_id'  => $this->userId,
            'terminal' => $this->terminal,
        ]);
        $result = DeviceAuthLogic::purchaseCode($params);
        if ($result === false) {
            return $this->fail(DeviceAuthLogic::getError());
        }
        return $this->data(DeviceAuthLogic::getReturnData());
    }

    public function renewDevice()
    {
        $params = (new DeviceAuthValidate())->post()->goCheck('renew', [
            'user_id'  => $this->userId,
            'terminal' => $this->terminal,
        ]);
        $result = DeviceAuthLogic::renewDevice($params);
        if ($result === false) {
            return $this->fail(DeviceAuthLogic::getError());
        }
        return $this->data(DeviceAuthLogic::getReturnData());
    }

    public function activate()
    {
        $params = (new DeviceAuthValidate())->post()->goCheck('activate', [
            'user_id' => $this->userId,
        ]);

        $result = DeviceAuthLogic::activate($params);
        if ($result === false) {
            return $this->fail(DeviceAuthLogic::getError());
        }
        return $this->success('激活成功', DeviceAuthLogic::getReturnData());
    }

    public function notice()
    {
        $params = (new DeviceAuthValidate())->post()->goCheck('notice');

        $result = DeviceAuthLogic::notice($params);
        if ($result === false) {
            //通知不返回错误
            return $this->fail(DeviceAuthLogic::getError());
        }
        return $this->success('通知成功', DeviceAuthLogic::getReturnData());
    }

    public function addPhone()
    {
        $params = (new DeviceAuthValidate())->post()->goCheck('addPhone', [
            'user_id' => $this->userId,
        ]);
        $result = DeviceAuthLogic::addPhone($params);
        if ($result === false) {
            return $this->fail(DeviceAuthLogic::getError());
        }
        return $this->success('添加成功', DeviceAuthLogic::getReturnData());
    }
}
