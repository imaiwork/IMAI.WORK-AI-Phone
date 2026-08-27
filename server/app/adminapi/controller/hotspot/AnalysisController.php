<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hotspot\AnalysisLists;
use app\adminapi\logic\hotspot\AnalysisLogic;
use app\adminapi\validate\hotspot\HotspotAdminValidate;

class AnalysisController extends BaseAdminController
{
    public function lists()
    {
        (new HotspotAdminValidate())->get()->goCheck('lists');
        return $this->dataLists(new AnalysisLists());
    }

    public function detail()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('id');
        $result = AnalysisLogic::detail((int)$params['id']);
        if (false === $result) {
            return $this->fail(AnalysisLogic::getError());
        }
        return $this->data($result);
    }

    public function delete()
    {
        $params = (new HotspotAdminValidate())->post()->goCheck('id');
        if (true === AnalysisLogic::delete($params['id'])) {
            return $this->success('操作成功');
        }
        return $this->fail(AnalysisLogic::getError());
    }
}
