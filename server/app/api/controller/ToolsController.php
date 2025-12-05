<?php

namespace app\api\controller;

use app\api\lists\tools\ToolsLists;
use app\api\logic\ToolsLogic;
use app\api\validate\ToolsValidate;
use think\facade\Log;
use think\response\Json;

class ToolsController extends BaseApiController
{
    public array $notNeedLogin = ['clip', 'getPrompt'];

    /**
     * 工具列表
     * @return Json
     * @author L
     * @data 2024/6/17 15:34
     */
    public function lists()
    {
        return $this->dataLists(new ToolsLists());
    }

    /**
     * 对话
     * @return Json
     * @author L
     * @data 2024/6/17 15:49
     */
    public function chat():Json
    {
        $params = (new ToolsValidate())->post()->goCheck("chat");
        return ToolsLogic::chat($params, $this->userId) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }

    public function getConfig():Json
    {
        $param = 'sd.' . $this->request->get('model');
        $result = config($param) ?: [];
        return $this->data($result);
    }


    public function getSearchTerms(){
        $params = $this->request->post();;
        return ToolsLogic::getSearchTerms($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }

    public function getPrompt(){
        $params = $this->request->post();
        return ToolsLogic::getPrompt($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }


    public function clip(){
        try {
            $result = ToolsLogic::clip();
            if ($result) {
                return $this->data(ToolsLogic::getReturnData());
            }
            return $this->fail(ToolsLogic::getError());

        } catch (\Exception $e) {
            Log::channel('clip')->write('剪辑失败'.$e->getMessage());
            return $this->fail($e->getMessage());
        }

    }
}