<?php

namespace app\api\controller\interview;


use app\api\controller\BaseApiController;
use app\api\lists\interview\InterviewLists;
use app\api\logic\interview\InterviewLogic;
use think\response\Json;

class InterviewController extends BaseApiController
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

        $result = InterviewLogic::detail($params['id']);
        if (true === $result) {
            return $this->success('ok', InterviewLogic::getReturnData());
        }
        return $this->fail(InterviewLogic::getError());
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



    public function stat()
    {
        $params['user_id'] = $this->userId;

        $res = InterviewLogic::stat($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }

}
