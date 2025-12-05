<?php
namespace app\adminapi\controller\lianlian;

use app\adminapi\lists\lianlian\LlAnalysisLists;
use app\adminapi\lists\lianlian\LlChatLogLists;
use app\adminapi\logic\lianlian\LlAnalysisLogic;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;

/**
 * 分析报告
 * Class LlAnalysisController
 * @package app\adminapi\controller
 */
class LlAnalysisController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists()
    {
        return $this->dataLists(new LlAnalysisLists());
    }

     /**
     * @notes 列表
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function chatLog()
    {
        $id = $this->request->get('analysis_id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return $this->dataLists(new LlChatLogLists());
    }

    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function delete():Json
    {
        $data = $this->request->post();
        return LlAnalysisLogic::delete($data) ? $this->success() : $this->fail(LlAnalysisLogic::getError());
    }

    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function detail():Json
    {
        $id = $this->request->get('id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return LlAnalysisLogic::detail($id) ? $this->data(LlAnalysisLogic::getReturnData()) : $this->fail(LlAnalysisLogic::getError());
    }

    /**
     * 重试
     * @return Json
     * @author L
     * @data 2024/7/5 10:25
     */
    public function retry():Json
    {
        $id = $this->request->post('id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return LlAnalysisLogic::retry($id) ? $this->success(data:LlAnalysisLogic::getReturnData()) : $this->fail(LlAnalysisLogic::getError());
    }
}
                        