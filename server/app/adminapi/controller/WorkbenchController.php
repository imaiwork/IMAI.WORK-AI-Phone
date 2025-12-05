<?php

namespace app\adminapi\controller;

use app\adminapi\logic\WorkbenchLogic;

/**
 * 工作台
 * Class WorkbenchCotroller
 * @package app\adminapi\controller
 */
class WorkbenchController extends BaseAdminController
{

    /**
     * @notes 工作台
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 17:01
     */
    public function index()
    {
        $result = WorkbenchLogic::index();
        return $this->data($result);
    }
}
