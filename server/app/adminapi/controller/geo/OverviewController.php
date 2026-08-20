<?php

namespace app\adminapi\controller\geo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\geo\GeoOverviewLogic;
use think\response\Json;

/**
 * GEO 管理 · 概览看板
 * 路由 geo.overview/*,菜单挂营销管理 → GEO管理
 */
class OverviewController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function index(): Json
    {
        $result = GeoOverviewLogic::index();
        if ($result === false) {
            return $this->fail(GeoOverviewLogic::getError());
        }
        return $this->data(GeoOverviewLogic::getReturnData());
    }
}
