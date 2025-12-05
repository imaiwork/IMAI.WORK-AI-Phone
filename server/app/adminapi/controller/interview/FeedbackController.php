<?php

namespace app\adminapi\controller\interview;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\interview\InterviewFeedbackLists;
use app\adminapi\lists\interview\InterviewLists;
use app\adminapi\logic\interview\InterviewLogic;
use think\response\Json;

class FeedbackController extends BaseAdminController
{
    public array $notNeedLogin = [];

    /**
     * @desc 岗位列表
     * @return Json
     * @date 2025/2/13 15:40
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new InterviewFeedbackLists());
    }

    public function delete()
    {
        $params = $this->request->get();
        InterviewLogic::deleteFeedback($params);
        return $this->success('删除成功', [], 1, 1);
    }
}
