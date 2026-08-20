<?php

namespace app\api\controller;

use app\api\lists\aiModels\AiModelsLists;

/**
 * AI模型管理
 * Class AiModelsController
 * @package app\api\controller
 */
class AiModelsController extends BaseApiController
{
    public array $notNeedLogin = ['lists'];
    public function lists(): \think\response\Json
    {
        $result = (new AiModelsLists())->lists();
        return $this->success('获取成功', $result);
    }
}
