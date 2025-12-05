<?php

namespace app\api\controller;

use app\api\logic\interview\InterviewDialogLogic;
use app\api\logic\InterviewLogic;
use think\exception\HttpResponseException;

class InterviewController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * 面试列表
     */
    public function jobs()
    {
        $params = $this->request->post();
        $params['user_id'] = $params['user_id'] ?? $this->userId;

        $data = InterviewLogic::jobs($params);
        return $data ? $this->success('ok', $data) : $this->fail(InterviewLogic::getError());
    }

    public function jobDetail()
    {
        $params = $this->request->post();

        $data = InterviewLogic::jobDetail($params);
        return $data ? $this->success('ok', $data) : $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 简历识别
     * @return \think\response\Json
     * @date 2025/2/13 17:28
     * @throws \think\Exception
     * @author dagouzi
     */
    public function extractCv()
    {
        set_time_limit(0);
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = InterviewLogic::extractCv($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()): $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 保存简历
     * @return \think\response\Json
     * @date 2025/2/14 11:40
     * @author dagouzi
     */
    public function saveCv()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;

        $data = InterviewLogic::saveCv($params);
        return $data ? $this->success('ok') : $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 开始面试
     * @return \think\response\Json
     * @date 2025/2/17 9:58
     * @author dagouzi
     */
    public function start()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;

        $res = InterviewLogic::start($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 聊天对话
     * @return \think\response\Json
     * @date 2025/2/17 18:07
     * @throws \think\Exception
     * @author dagouzi
     */
    public function chat()
    {
        set_time_limit(0);
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = InterviewLogic::chat($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }

    /**
     * @desc 用户回答
     * @return \think\response\Json
     * @date 2025/2/17 18:08
     * @author dagouzi
     */
    public function answer()
    {
        dd('没用到');
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = InterviewLogic::answer($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }

    public function feedback()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = InterviewLogic::feedback($params);
        return $res ? $this->success('ok') : $this->fail(InterviewLogic::getError());
    }

    public function stt()
    {
        $params = $this->request->post();
        $res = InterviewLogic::getStt($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }

    public function checkInterview()
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = InterviewLogic::checkInterview($params);
        return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());
    }


    public function getDialog()
    {
        try {
            $params = $this->request->get();
            
            // 检查参数
            if (empty($params['interview_id'])) {
                return $this->fail('请提供面试ID', [],0);
            }
            
            // 调用逻辑层查询对话记录
            $res = InterviewDialogLogic::getDialogByInterviewId($params['interview_id'], $this->userId);
            
            return $res ? $this->success('ok', InterviewLogic::getReturnData()) : $this->fail(InterviewLogic::getError());

        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
}
