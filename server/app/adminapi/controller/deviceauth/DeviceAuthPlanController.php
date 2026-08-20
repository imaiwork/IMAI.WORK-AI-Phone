<?php

namespace app\adminapi\controller\deviceauth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\deviceauth\DeviceAuthPlanLists;
use app\adminapi\logic\deviceauth\DeviceAuthPlanLogic;
use app\adminapi\validate\deviceauth\DeviceAuthPlanValidate;

class DeviceAuthPlanController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new DeviceAuthPlanLists());
    }

    public function add()
    {
        $params = (new DeviceAuthPlanValidate())->post()->goCheck('add');
        if (!DeviceAuthPlanLogic::add($params)) {
            return $this->fail(DeviceAuthPlanLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }

    public function edit()
    {
        $params = (new DeviceAuthPlanValidate())->post()->goCheck('edit');
        if (DeviceAuthPlanLogic::edit($params) === false) {
            return $this->fail(DeviceAuthPlanLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }

    public function detail()
    {
        $params = (new DeviceAuthPlanValidate())->goCheck('detail');
        $detail = DeviceAuthPlanLogic::detail((int)$params['id']);
        return $this->success('', $detail);
    }

    public function delete()
    {
        $params = (new DeviceAuthPlanValidate())->post()->goCheck('delete');
        DeviceAuthPlanLogic::delete((int)$params['id']);
        return $this->success('操作成功', [], 1, 1);
    }

    public function changeStatus()
    {
        $params = (new DeviceAuthPlanValidate())->post()->goCheck('status');
        DeviceAuthPlanLogic::status((int)$params['id'], (int)$params['status']);
        return $this->success('操作成功', [], 1, 1);
    }
}
