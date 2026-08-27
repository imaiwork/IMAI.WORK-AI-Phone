<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hotspot\CreationLists;
use app\adminapi\logic\hotspot\CreationLogic;
use app\adminapi\validate\hotspot\HotspotAdminValidate;

class CreationController extends BaseAdminController
{
    public function lists()
    {
        (new HotspotAdminValidate())->get()->goCheck('lists');
        return $this->dataLists(new CreationLists());
    }

    public function detail()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('id');
        $result = CreationLogic::detail((int)$params['id']);
        if (false === $result) {
            return $this->fail(CreationLogic::getError());
        }
        return $this->data($result);
    }

    public function delete()
    {
        $params = (new HotspotAdminValidate())->post()->goCheck('id');
        if (true === CreationLogic::delete($params['id'])) {
            return $this->success('操作成功');
        }
        return $this->fail(CreationLogic::getError());
    }
}
