<?php

namespace app\api\controller;

use app\api\lists\tools\ToolsLists;
use app\api\lists\tools\ToolsLogLists;
use app\api\logic\ToolsLogic;
use app\api\validate\ToolsValidate;
use think\response\Json;

class ToolsLogController extends BaseApiController
{
    /**
     * 工具对话记录
     * @return Json
     * @author L
     * @data 2024/6/17 15:42
     */
    public function lists():Json
    {
        (new ToolsValidate())->get()->goCheck("getToolsLog");
        return $this->dataLists(new ToolsLogLists());
    }

    /**
     * 删除
     * @return Json
     * @author L
     * @data 2024/6/17 15:46
     */
    public function delete():Json
    {
        $params = (new ToolsValidate())->post()->goCheck("delete");
        return ToolsLogic::delete($params, $this->userId) ? $this->success() : $this->fail(ToolsLogic::getError());
    }
}