<?php

namespace app\api\controller;

use app\api\lists\tools\ToolsLists;
use app\api\lists\tools\ToolsLogLists;
use app\api\logic\SdLogic;
use app\api\logic\ToolsLogic;
use app\api\validate\ToolsValidate;
use think\response\Json;

class SdController extends BaseApiController
{
    /**
     * @desc
     * @return Json
     * @date 2024/6/26 19:09
     * @throws \Exception
     * @author dagouzi
     */
    public function generate():Json
    {
        $params = $this->request->post();
        $result = SdLogic::generate($params);
        if ($result)
        {
            return $this->data(SdLogic::getReturnData());
        }
        return $this->data(SdLogic::getError());
    }
}