<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\tools\ToolsLists;
use app\api\lists\tools\ToolsLogLists;
use app\api\logic\ToolsLogic;
use app\api\validate\ToolsValidate;
use think\response\Json;

class ToolsController extends BaseApiController
{
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


    public function getSearchTerms(){
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        return ToolsLogic::getSearchTerms($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }

    public function getMatrixCopywriting(){
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        return ToolsLogic::getMatrixCopywriting($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }

    public function transcoding(){
        $params = $this->request->post();
        return ToolsLogic::transcoding($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }

    public function searchTranscoding(){
        $params = $this->request->post();
        return ToolsLogic::searchTranscoding($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }
}