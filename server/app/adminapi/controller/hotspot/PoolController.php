<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\hotspot\PoolLogic;
use app\adminapi\validate\hotspot\HotspotAdminValidate;

class PoolController extends BaseAdminController
{
    public function lists()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('stockLists');
        $result = PoolLogic::lists($params);
        if (false === $result) {
            return $this->fail(PoolLogic::getError());
        }
        return $this->data($result);
    }

    public function delete()
    {
        $params = (new HotspotAdminValidate())->post()->goCheck('stockDelete');
        if (true === PoolLogic::delete($params)) {
            return $this->success('操作成功');
        }
        return $this->fail(PoolLogic::getError());
    }
}
