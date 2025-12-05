<?php
declare (strict_types = 1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\sop\StageLogic;
use app\api\validate\wechat\sop\StageValidate;
use app\api\validate\wechat\sop\TriggerValidate;
use think\exception\HttpResponseException;

/**
 * StageController
 * @desc SOP流程子阶段管理
 */
class StageController extends BaseApiController
{
    /**
     * @notes 创建子阶段
     */
    public function add()
    {
        try {
            $params = (new StageValidate())->post()->goCheck('createStage');
            $result = StageLogic::createStage($params);
            if ($result) {
                return $this->success(data: StageLogic::getReturnData());
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新子阶段
     */
    public function update()
    {
        try {
            $params = (new StageValidate())->post()->goCheck('updateStage');
            $result = StageLogic::updateStage($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除子阶段
     */
    public function delete()
    {
        try {
            $params = (new StageValidate())->post()->goCheck('deleteStage');
            $result = StageLogic::deleteStage($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 获取子阶段列表
     */
    public function lists()
    {
        try {
            $params = (new StageValidate())->get()->goCheck('stageLists');
            $result = StageLogic::getStageList($params);
            if ($result) {
                return $this->success(data: StageLogic::getReturnData());
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 添加阶段触发条件
     */
    public function addTrigger()
    {
        try {
            $params = (new TriggerValidate())->post()->goCheck('create');
            $result = StageLogic::createTrigger($params);
            if ($result) {
                return $this->success(data: StageLogic::getReturnData());
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新阶段触发条件
     */
    public function updateTrigger()
    {
        try {
            $params = (new TriggerValidate())->sceneUpdateTrigger()->post()->goCheck('update');
            $result = StageLogic::updateTrigger($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除阶段触发条件
     */
    public function deleteTrigger()
    {
        try {
            $params = (new TriggerValidate())->post()->goCheck('delete');
            $result = StageLogic::deleteTrigger($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(StageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
} 