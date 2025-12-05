<?php

namespace app\api\controller;

use app\api\lists\lianlian\LlSceneLists;
use app\api\lists\lianlian\LlAnalysisLists;
use app\api\lists\lianlian\LlChatLogLists;
use app\api\logic\LianLianLogic;
use think\response\Json;

/**
 * AI陪练
 * Class LianlianController
 * @package app\api\controller
 */
class LianlianController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 重试
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisRetry()
    {
        $id = $this->request->post('id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return LianLianLogic::analysisRetry($id) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

     /**
     * @notes 详情
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisDetail()
    {
        $id = $this->request->get('id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return LianLianLogic::analysisDetail($id) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 删除
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisDelete()
    {
        $data = $this->request->post();
        return LianLianLogic::analysisDelete($data) ? $this->success() : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 列表
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisLists()
    {
        return $this->dataLists(new LlAnalysisLists());
    }

     /**
     * @notes 聊天记录
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisChatLog()
    {
        $id = $this->request->get('analysis_id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }
        return $this->dataLists(new LlChatLogLists());
    }


    /**
     * @notes 分析工作台
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function analysisWorkbench()
    {
        return LianLianLogic::analysisWorkbench() ? $this->data(LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 列表
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function sceneLists()
    {
        return $this->dataLists(new LlSceneLists());
    }

    /**
     * @notes 添加场景
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function sceneAdd()
    {
        $data = $this->request->post();
        return LianLianLogic::sceneAdd($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }


     /**
     * @notes 编辑场景
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function sceneEdit()
    {
        $data = $this->request->post();
        return LianLianLogic::sceneEdit($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 场景详情
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function sceneDetail()
    {
        $id = $this->request->get('id/d');
        if(!$id){
            return $this->fail('缺少参数');
        }

        return LianLianLogic::sceneDetail($id) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 场景删除
     * @author L
     * @date 2024-07-05 11:34:16
     */
    public function sceneDelete()
    {
        $data = $this->request->post();

        return LianLianLogic::sceneDelete($data) ? $this->success() : $this->fail(LianLianLogic::getError());
    }

    /**
     * 开始聊天
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function startChat(): Json
    {
        $data = $this->request->post();
        return LianLianLogic::startChat($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * 聊天STT 
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function chatSTT(): Json
    {
        $audio = $this->request->post('audio');
        if(!$audio){
            return $this->fail('缺少参数');
        }
        return LianLianLogic::chatSTT($audio) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * 继续聊天
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function continueChat(): Json
    {
        $data = $this->request->post();
        return LianLianLogic::continueChat($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

     /**
     * 結束聊天
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function endChat(): Json
    {
        $data = $this->request->post();
        return LianLianLogic::endChat($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }


    /**
     * 聊天
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function speechcraftChat(): Json
    {
        $data = $this->request->post();
        return LianLianLogic::speechcraftChat($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * 聊天
     * @return Json
     * @author L
     * @data 2024/7/5 14:17
     */
    public function performanceChat(): Json
    {
        $data = $this->request->post();
        return LianLianLogic::performanceChat($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }

    /**
     * @notes 存为草稿
     * @author Rick
     * @date 2024-06-09 11:34:16
     */
    public function addDraft()
    {
        $data = $this->request->post();
        return LianLianLogic::analysisAddDraft($data) ? $this->success(data: LianLianLogic::getReturnData()) : $this->fail(LianLianLogic::getError());
    }
}
            