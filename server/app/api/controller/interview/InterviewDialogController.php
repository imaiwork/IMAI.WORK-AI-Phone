<?php

namespace app\api\controller\interview;

use app\api\controller\BaseApiController;
use app\api\lists\interview\InterviewDialogLists;
use app\api\logic\interview\InterviewDialogLogic;
use app\common\model\interview\Interview;
use think\response\Json;
use think\exception\HttpResponseException;


/**
 * 面试对话记录控制器
 * Class InterviewDialogController
 * @package app\api\controller\interview
 */
class InterviewDialogController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 对话记录列表
     * @return Json
     */
    public function lists()
    {
        $interviewId = (int)$this->request->param('interview_id', 0);
        
        $interviewId = $this->request->param('interview_id', 0);
        $jobId = $this->request->param('job_id', 0);
        $interview = Interview::where(['id' => $interviewId,'job_id'=>$jobId])->find();
        if (!$interview) {
            return $this->fail('面试邀请不存在');
        }
        
        // 验证job_id是否属于当前用户
        if ($interview->user_id != $this->userId) {
            return $this->fail('无权限查看该面试的对话记录');
        }
        
        // 验证通过，获取列表数据
        $lists = new InterviewDialogLists();
        $lists->setInterviewId($interviewId);  // 确保这里的值是正确的
        
        // 确保InterviewId已经设置
        if ($lists->getInterviewId() != $interviewId) {
            // 如果不一致，手动设置searchWhere
            $lists->addSearchWhere('interview_id', '=', $interviewId);
        }
        
        return $this->dataLists($lists);
    }

    /**
     * @desc 对话记录详情
     * @return Json
     */
    public function detail()
    {
        dd(1);
        try {
            $params = $this->request->get();
            $result = InterviewDialogLogic::detail($params['id']);
            if (true === $result) {
                return $this->success('获取成功', InterviewDialogLogic::getReturnData());
            }
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }

    /**
     * @desc 添加对话记录
     * @return Json
     */
    public function add()
    {
        dd(1);
        try {
            $params = $this->request->post();
            $result = InterviewDialogLogic::add($params);
            if (true === $result) {
                return $this->success('添加成功', InterviewDialogLogic::getReturnData());
            }
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }

    /**
     * @desc 更新对话记录
     * @return Json
     */
    public function update()
    {
        dd(1);
        try {
            $params = $this->request->post();
            $result = InterviewDialogLogic::update($params);
            if (true === $result) {
                return $this->success('更新成功');
            }
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }

    /**
     * @desc 删除对话记录
     * @return Json
     */
    public function delete()
    {
        dd(1);
        try {
            $params = $this->request->post();
            $result = InterviewDialogLogic::delete($params);
            if (true === $result) {
                return $this->success('删除成功');
            }
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }

    /**
     * @desc 主动退出面试 面试者
     * @return Json
     */
    public function exit()
    {
        try {
            $params = $this->request->post();
            $params['user_id'] = $this->userId; // 添加用户ID
            
            if (empty($params['interview_id']) || empty($params['reason'])) {
                // 注意参数顺序: $msg, $data, $code, $show
                return $this->fail('请提供面试ID和原因');
            }
            
            $result = false;
            if(in_array($params['type'],[1,2,3])){
                $result = InterviewDialogLogic::endInterview($params,$params['type']);
            }

            if (true === $result) {
                return $this->success('退出成功', InterviewDialogLogic::getReturnData());
            }
            
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {

            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败', []);

        }
    }
    
    
    /**
     * @desc 创建新面试
     * @return Json
     */
    public function createNew()
    {
        try {
            $params = $this->request->post();
            $params['user_id'] = $this->userId; // 添加用户ID
            
            if (empty($params['job_id'])) {
                return $this->fail('请提供岗位ID');
            }
            
            $result = InterviewDialogLogic::createNewInterview($params);
            if (true === $result) {
                return $this->success('创建成功', InterviewDialogLogic::getReturnData());
            }
            return $this->fail(InterviewDialogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
} 