<?php

namespace app\adminapi\controller\geo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\geo\GeoProjectLists;
use app\adminapi\logic\geo\GeoProjectLogic;
use app\adminapi\validate\geo\GeoProjectValidate;
use think\response\Json;

/**
 * GEO 管理 · 项目管理(全租户视角)
 */
class ProjectController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new GeoProjectLists());
    }

    public function detail(): Json
    {
        $params = (new GeoProjectValidate())->get()->goCheck('detail');
        $result = GeoProjectLogic::detail($params);
        if ($result === false) {
            return $this->fail(GeoProjectLogic::getError() ?: '项目不存在');
        }
        return $this->data(GeoProjectLogic::getReturnData());
    }

    public function setAutoMonitor(): Json
    {
        $params = (new GeoProjectValidate())->post()->goCheck('autoMonitor');
        $result = GeoProjectLogic::setAutoMonitor($params);
        if ($result === false) {
            return $this->fail(GeoProjectLogic::getError());
        }
        return $this->success('设置成功', GeoProjectLogic::getReturnData());
    }

    public function delete(): Json
    {
        $params = (new GeoProjectValidate())->post()->goCheck('delete');
        $result = GeoProjectLogic::delete($params);
        if ($result === false) {
            return $this->fail(GeoProjectLogic::getError());
        }
        return $this->success('删除成功');
    }
}
