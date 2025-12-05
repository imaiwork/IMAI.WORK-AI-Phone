<?php
declare (strict_types = 1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\lists\sop\PushLists;
use app\api\logic\wechat\sop\PushLogic;
use app\api\validate\wechat\sop\PushValidate;
use think\exception\HttpResponseException;

/**
 * PushController
 * @desc 推送管理
 */
class PushController extends BaseApiController
{

      /**
     * @notes 获取流程列表
     */
    public function lists()
    {
        return $this->dataLists(new PushLists());
    }

    /**
     * @notes 创建推送
     */
    public function add()
    {
        try {
            $params = (new PushValidate())->sceneCreatePush()->post()->goCheck(); // 确保使用 sceneCreatePush()
            $result = PushLogic::createPush($params);
            if ($result) {
                return $this->success(data: PushLogic::getReturnData());
            }
            return $this->fail(PushLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
    /**
     * @notes 更新推送
     */
    public function update()
    {
        try {
            $params = (new PushValidate())->post()->goCheck('updatePush');
            $result = PushLogic::updatePush($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


     /**
     * @notes 群发任务更新
     */
    public function updateSequence()
    {
        try {
            $params = (new PushValidate())->post()->goCheck('updateSequencePush');
            $result = PushLogic::updateSequencePush($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
    

    /**
     * @notes 删除推送
     */
    public function delete()
    {
        try {
            $params = (new PushValidate())->post()->goCheck('deletePush');
            $result = PushLogic::deletePush($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 推送详情
     */
    public function detail()
    {
        $params = (new PushValidate())->get()->goCheck('detail');
        $result = PushLogic::detail($params);
        if ($result) {
            return $this->success(data: PushLogic::getReturnData());
        }
        return $this->fail(PushLogic::getError());
    }

    /**
     * @notes 选择推送流程人员
     */
    public function choiceFlow()
    {
        $params = (new PushValidate())->post()->goCheck('choiceFlow');
        $result = PushLogic::choiceFlow($params);
        if ($result) {
            return $this->success(data: PushLogic::getReturnData());
        }
        return $this->fail(PushLogic::getError());
    }
} 