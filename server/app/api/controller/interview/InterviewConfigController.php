<?php

namespace app\api\controller\interview;

use app\api\controller\BaseApiController;
use app\api\lists\interview\InterviewJobLists;
use app\api\lists\interview\InterviewLists;
use app\api\logic\interview\InterviewConfigLogic;
use app\api\logic\interview\InterviewJobLogic;
use app\api\logic\InterviewLogic;
use think\response\Json;

class InterviewConfigController extends BaseApiController
{
    public function edit()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;

        $result = InterviewConfigLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 0);
        }
        return $this->fail(InterviewJobLogic::getError());
    }

    public function detail()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;

        $result = InterviewConfigLogic::detail($params);
        if (true === $result) {
            return $this->success('编辑成功', InterviewConfigLogic::getReturnData(), 1);
        }
        return $this->fail(InterviewJobLogic::getError());
    }
}
