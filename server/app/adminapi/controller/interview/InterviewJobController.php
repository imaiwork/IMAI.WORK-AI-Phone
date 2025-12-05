<?php

namespace app\adminapi\controller\interview;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\interview\InterviewJobLists;
use app\adminapi\logic\interview\InterviewJobLogic;
use app\common\model\interview\InterviewJob;

class InterviewJobController extends BaseAdminController
{
    public array $notNeedLogin = [];

    /**
     * @desc 岗位列表
     * @return \think\response\Json
     * @date 2025/3/4 16:03
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new InterviewJobLists());
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
        $params = $this->request->get();

        $result = InterviewJobLogic::detail($params['id']);
        if (true === $result) {
            return $this->success('ok', InterviewJobLogic::getReturnData(), 1, 0);
        }
        return $this->fail(InterviewJobLogic::getError());
    }

  /**
     * @notes 批量删除
     * @param int ids
     * @return bool
     */

    public function delete()
    {
        $jobIds = $this->request->post('id'); // 从请求中获取要删除的 ID 数组

        try {
            $res = InterviewJobLogic::deleteJobs($jobIds);
            if (true == $res) {
                return $this->success('成功删除', [], 1, 1);
            }
            return $this->fail('删除失败');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


      /**
     * @notes 变更状态
     * @param int $jobId
     * @param int $status
     * @return bool
     */
    public function changeStatus()
    {
        $jobId = $this->request->post('id'); 
        $status = $this->request->post('status'); 
        try {
            $res = InterviewJobLogic::changeStatus($jobId,$status);
            if (true === $res) {
                return $this->success ('更新成功', [], 1, 1);
                }
            return $this->fail('更新失败');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

     
    }
}
