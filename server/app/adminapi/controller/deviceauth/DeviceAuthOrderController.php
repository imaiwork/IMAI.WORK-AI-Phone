<?php

namespace app\adminapi\controller\deviceauth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\deviceauth\DeviceAuthOrderLogic;
use app\adminapi\validate\deviceauth\DeviceAuthCodeValidate;

class DeviceAuthOrderController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists();
    }

    public function detail()
    {
        (new DeviceAuthCodeValidate())->goCheck('id');
        $detail = DeviceAuthOrderLogic::detail((int)$this->request->get('id'));
        return $this->success('', $detail);
    }
}
