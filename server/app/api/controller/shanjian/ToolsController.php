<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\lists\tools\ToolsLists;
use app\api\lists\tools\ToolsLogLists;
use app\api\logic\shanjian\ToolsLogic;
use app\api\validate\ToolsValidate;
use think\response\Json;

class ToolsController extends BaseApiController
{


    public function getNewsMixcutTittle(){
        $params = $this->request->post();
        $params['user_id'] =  $this->userId ;
        return ToolsLogic::getCopywriting($params) ? $this->success(data: ToolsLogic::getReturnData()) : $this->fail(ToolsLogic::getError());
    }
}