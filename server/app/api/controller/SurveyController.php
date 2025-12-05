<?php

namespace app\api\controller;

use app\api\logic\SurveyLogic;


/**
 * 调查问卷控制器
 * Class SurveyController
 * @package app\api\controller
 */
class SurveyController extends BaseApiController
{
    /**
     * 检查用户是否填写过调查问卷
     */
    public function check()
    {
        $data = SurveyLogic::check($this->userId);
        return $this->data($data ? ['remind' => 1] : ['remind' => 0]);
    }


    /**
     * 提交问卷
     */
    public function add()
    {
        $params = $this->request->post();

        $data = SurveyLogic::add($params);
        return $data ? $this->success('提交成功') : $this->fail(SurveyLogic::getError());
    }
}
