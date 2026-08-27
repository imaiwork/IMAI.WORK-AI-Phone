<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\hotspot\HotLogic;
use app\adminapi\validate\hotspot\HotspotAdminValidate;

class HotController extends BaseAdminController
{
    public function lists()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('hot');
        $result = HotLogic::lists($params);
        if (false === $result) {
            return $this->fail(HotLogic::getError());
        }
        return $this->data($result);
    }

    public function historyDates()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('historyDates');
        return $this->data(HotLogic::historyDates($params));
    }
}
