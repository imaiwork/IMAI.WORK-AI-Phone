<?php

namespace app\api\controller;

use app\api\lists\staff\StaffLists;
use app\api\logic\StaffLogic;
use think\response\Json;

class StaffController extends BaseApiController
{
    public array $notNeedLogin = ['lists','detail'];


    /**
     * 列表
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function lists(): Json
    {
        return $this->dataLists(new StaffLists());
    }

    /**
     * 详情
     * @return Json
     * @throws \Exception
     * @author L
     * @data 2024/6/28 11:23
     */
    public function detail(): Json
    {
        $id = $this->request->get('id');
        $detail  = StaffLogic::detail($id);
        return $detail ? $this->success(data: StaffLogic::getReturnData()) : $this->fail(StaffLogic::getError());
    }
}
