<?php

namespace app\api\controller\interview;

use app\api\controller\BaseApiController;
use app\api\lists\interview\InterviewRecordLists;
use app\api\logic\interview\InterviewRecordLogic;
use think\response\Json;
use think\exception\HttpResponseException;

/**
 * 面试记录控制器
 * Class InterviewRecordController
 * @package app\api\controller\interview
 */
class InterviewRecordController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 面试记录列表
     * @return Json
     */
    public function lists(): Json
    {
        $lists = new InterviewRecordLists();
        return $this->dataLists($lists);
    }
    
    /**
     * @desc 面试记录详情
     * @return Json
     */
    public function detail(): Json
    {
        dd(1);
        try {
            $params = $this->request->get();
            $params['user_id'] = $this->userId;
            
            if (empty($params['id'])) {
                return $this->fail('请提供面试记录ID');
            }
            
            $result = InterviewRecordLogic::detail($params);
            if (true === $result) {
                return $this->success('获取成功', InterviewRecordLogic::getReturnData());
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
    
    /**
     * @desc 添加面试记录
     * @return Json
     */
    public function add(): Json
    {
        dd(1);
        try {
            $params = $this->request->post();
            $params['user_id'] = $this->userId;
            
            if (empty($params['job_id'])) {
                return $this->fail('请提供岗位ID');
            }
            
            $result = InterviewRecordLogic::add($params);
            if (true === $result) {
                return $this->success('添加成功', InterviewRecordLogic::getReturnData());
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
    
    /**
     * @desc 更新面试记录
     * @return Json
     */
    public function update(): Json
    {
        dd(1);
        try {
            $params = $this->request->post();
            $params['user_id'] = $this->userId;
            
            if (empty($params['id'])) {
                return $this->fail('请提供面试记录ID');
            }
            
            $result = InterviewRecordLogic::update($params);
            if (true === $result) {
                return $this->success('更新成功', InterviewRecordLogic::getReturnData());
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
    
    /**
     * @desc 删除面试记录
     * @return Json
     */
    public function delete(): Json
    {
        dd(1);
        try {
            $params = $this->request->post();
            $params['user_id'] = $this->userId;
            
            if (empty($params['id'])) {
                return $this->fail('请提供面试记录ID');
            }
            
            $result = InterviewRecordLogic::delete($params);
            if (true === $result) {
                return $this->success('删除成功');
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
    
    /**
     * @desc 更新面试记录状态
     * @return Json
     */
    public function updateStatus(): Json
    {
      
        try {
            $params = $this->request->get();
            $params['user_id'] = $this->userId;
            
            if (empty($params['id'])) {
                return $this->fail('请提供面试记录ID');
            }
            
            $result = InterviewRecordLogic::updateStatus($params);
            if (true === $result) {
                return $this->success('状态更新成功', InterviewRecordLogic::getReturnData());
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }

    /**
     * @desc 批量删除面试记录
     * @return Json
     */
    public function batchDelete(): Json
    {
        try {
            $params = $this->request->post();
            
            // 检查是否提供了要删除的记录ID
            if (empty($params['ids']) || !is_array($params['ids'])) {
                return $this->fail('请提供要删除的面试记录ID数组', []);
            }
            
            // 调用逻辑层进行批量删除
            $result = InterviewRecordLogic::batchDelete($params['ids'], $this->userId);
            if ($result) {
                return $this->success('删除成功');
            }
            
            return $this->fail(InterviewRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '操作失败');
        }
    }
} 