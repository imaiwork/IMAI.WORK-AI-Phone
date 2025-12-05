<?php

namespace app\api\controller\interview;

use app\api\controller\BaseApiController;
use app\api\lists\interview\InterviewJobLists;
use app\api\logic\interview\InterviewJobLogic;
use think\response\Json;

class InterviewJobController extends BaseApiController
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
        return $this->dataLists(new InterviewJobLists());
    }

    /**
     * 提交问卷
     */
    public function add()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;

        $data = InterviewJobLogic::add($params);
        return $data ? $this->success('提交成功') : $this->fail(InterviewJobLogic::getError());
    }

    public function edit()
    {
        $params = $this->request->post();

        $result = InterviewJobLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(InterviewJobLogic::getError());
    }

    public function detail()
    {
        $params = $this->request->post();

        $result = InterviewJobLogic::detail($params['id']);
        if (true === $result) {
            return $this->success('ok', InterviewJobLogic::getReturnData());
        }
        return $this->fail(InterviewJobLogic::getError());
    }

    public function delete()
    {
        $params = $this->request->get();
        InterviewJobLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }
}
