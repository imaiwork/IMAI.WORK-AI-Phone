<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\hotspot\OverviewLogic;

class OverviewController extends BaseAdminController
{
    public function index()
    {
        return $this->data(OverviewLogic::index());
    }
}
