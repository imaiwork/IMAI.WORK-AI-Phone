<?php


namespace app\adminapi\controller\interview;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\interview\InterviewRecordLists;
use app\adminapi\logic\interview\InterviewRecordLogic;
use app\common\model\interview\InterviewRecord;

class InterviewRecordController extends BaseAdminController
{
    public array $notNeedLogin = [];

    /**
     * @desc 获取面试记录列表
     * @return \think\response\Json
     */
  
    public function lists()
    {
        return $this->dataLists(new InterviewRecordLists());
    }

    /**
     * @desc 查看面试记录详情
     * @param int $id
     * @return \think\response\Json
     */
    public function detail(int $id)
    {
        try {
            return InterviewRecordLogic::detail($id);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @desc 批量删除面试记录
     * @return \think\response\Json
     */
    public function delete()
    {
        $ids = $this->request->post('id'); // 从请求中获取要删除的 ID 数组

        try {
            $deletedCount = InterviewRecordLogic::deleteRecords($ids);
            if ($deletedCount == false) {
                return $this->fail(InterviewRecordLogic::getError());
            }   
            return $this->success("操作成功");
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}