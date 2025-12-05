<?php

namespace app\adminapi\controller\survey;

use app\adminapi\logic\survey\SurveyLogic;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\survey\SurveyLists;



/**
 * 调查问卷控制器
 * Class SurveyController
 * @package app\adminapi\controller
 */
class SurveyController extends BaseAdminController
{
    /**
     * 列表
     */
    public function lists()
    {
        return $this->dataLists(new SurveyLists());
    }


    /**
     * 删除
     */
    public function delete()
    {
        $params = $this->request->post();
        return SurveyLogic::delete($params) ? $this->success() : $this->fail(SurveyLogic::getError());
    }
}
