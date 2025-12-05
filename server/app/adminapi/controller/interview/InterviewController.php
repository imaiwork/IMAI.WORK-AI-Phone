<?php

namespace app\adminapi\controller\interview;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\interview\InterviewLists;
use app\adminapi\logic\interview\InterviewLogic;
use app\api\logic\interview\InterviewLogic as ApiInterviewLogic;
use think\response\Json;

class InterviewController extends BaseAdminController
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
        return $this->dataLists(new InterviewLists());
    }

    public function detail()
    {
        $params = $this->request->get();

        $result = ApiInterviewLogic::detail($params['id']);
        if (true == $result) {
            return $this->success('ok', ApiInterviewLogic::getReturnData(),1,0);
        }
        return $this->fail(ApiInterviewLogic::getError());
    }


    /**
     * @desc 岗位链接
     * @return Json
     * @date 2025/2/13 15:40
     * @author dagouzi
     */
    public function jobLink()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;

        $data = InterviewLogic::jobLink($params);
        return $data ? $this->success('ok', $data) : $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 我的岗位链接
     * @return Json
     * @date 2025/2/17 9:32
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public function myJobLink()
    {
        $params['user_id'] = $this->userId;

        $data = InterviewLogic::myJobLink($params);
        return $data ? $this->success('ok', $data) : $this->fail(InterviewLogic::getError());
    }

    public function delete()
    {
        $params = $this->request->get();
        InterviewLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }
}
