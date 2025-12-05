<?php

namespace app\api\controller;

use app\api\lists\mindMap\MindMapLists;
use app\api\logic\MindMapLogic;
use think\response\Json;

class MindMapController extends BaseApiController
{
    /**
     * 列表
     */
    public function lists()
    {
        return $this->dataLists(new MindMapLists());
    }

    /**
     * 删除记录
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        return MindMapLogic::delete($params) ? $this->success() : $this->fail(MindMapLogic::getError());
    }


    /**
     * 详情
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        return MindMapLogic::detail($params) ? $this->success(data: MindMapLogic::getReturnData()) : $this->fail(MindMapLogic::getError());
    }

    /**
     * 编辑
     */
    public function edit(): Json
    {
        $params = $this->request->post();
        return MindMapLogic::edit($params) ? $this->success(data: MindMapLogic::getReturnData()) : $this->fail(MindMapLogic::getError());
    }
}
