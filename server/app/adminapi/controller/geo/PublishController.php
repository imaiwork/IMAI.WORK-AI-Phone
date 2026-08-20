<?php

namespace app\adminapi\controller\geo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\geo\GeoPublishLists;
use app\adminapi\logic\geo\GeoPublishAdminLogic;
use app\adminapi\validate\geo\GeoPublishValidate;
use think\response\Json;

/**
 * GEO 管理 · 发布记录(投递台账)
 */
class PublishController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new GeoPublishLists());
    }

    public function delete(): Json
    {
        $params = (new GeoPublishValidate())->post()->goCheck('delete');
        $result = GeoPublishAdminLogic::delete($params);
        if ($result === false) {
            return $this->fail(GeoPublishAdminLogic::getError());
        }
        return $this->success('删除成功');
    }
}
