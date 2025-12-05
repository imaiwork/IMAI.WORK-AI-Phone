<?php
declare (strict_types=1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\lists\sop\FlowLists;
use app\api\logic\wechat\sop\FlowLogic;
use app\api\validate\wechat\sop\FlowValidate;
use think\exception\HttpResponseException;

/**
 * FlowController
 * @desc SOP流程管理
 */
class FlowController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 获取流程列表
     */
    public function lists()
    {
        return $this->dataLists(new FlowLists());
    }

    /**
     * @notes 创建流程
     */
    public function add()
    {
        try {
            $params = (new FlowValidate())->post()->goCheck('createFlow');
            $result = FlowLogic::createFlow($params);
            if ($result) {
                return $this->success(data: FlowLogic::getReturnData());
            }
            return $this->fail(FlowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新流程
     */
    public function update()
    {
        try {
            $params = (new FlowValidate())->post()->goCheck('updateFlow');
            $result = FlowLogic::updateFlow($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(FlowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除流程
     */
    public function delete()
    {
        try {
            $params = (new FlowValidate())->post()->goCheck('deleteFlow');
            $result = FlowLogic::deleteFlow($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(FlowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 获取流程详情，包括子阶段及其触发条件和跟进提醒个数
     */
    public function detail()
    {
        try {
            $params = (new FlowValidate())->get()->goCheck('deleteFlow');
            $result = FlowLogic::getFlowDetail($params['id']);
            if ($result) {
                return $this->success(data: $result);
            }
            return $this->fail(FlowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function dashboard()
    {
        try {
            $params = (new FlowValidate())->post()->goCheck('dashboardFlow');
            $result = FlowLogic::getDashboardDetail($params);
            if ($result) {
                return $this->success(data: FlowLogic::getReturnData());
            }
            return $this->fail(FlowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
} 