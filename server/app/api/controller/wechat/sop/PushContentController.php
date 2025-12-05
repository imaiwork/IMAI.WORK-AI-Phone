<?php
declare (strict_types=1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\sop\PushContentLogic;
use app\api\logic\wechat\sop\StageLogic;
use app\api\validate\wechat\sop\PushContentValidate;
use think\exception\HttpResponseException;

/**
 * PushContentController
 * @desc 推送内容管理
 */
class PushContentController extends BaseApiController
{
    /**
     * @notes 创建推送内容
     */
    public function add()
    {
        try {
            // 验证参数
            $params = (new PushContentValidate())->scene('createPushContent')->post()->goCheck();

            // 调用逻辑层处理推送内容和推送时间的插入
            $result = PushContentLogic::createPushContent($params);
            if ($result) {
                return $this->success(data: PushContentLogic::getReturnData());
            }
            return $this->fail(PushContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新推送内容
     */
    public function update()
    {
        try {
            $params = (new PushContentValidate())->post()->goCheck('updatePushContent');
            $result = PushContentLogic::updatePushContent($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除推送内容
     */
    public function delete()
    {
        try {
            $params = (new PushContentValidate())->post()->goCheck('deletePushContent');
            $result = PushContentLogic::deletePushContent($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 获取推送内容列表
     */
    public function detail()
    {
        try {
            $params = (new PushContentValidate())->get()->goCheck('detail');
            $result = PushContentLogic::detail($params);
            if ($result) {
                return $this->success(data: PushContentLogic::getReturnData());
            }
            return $this->fail(PushContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 获取推送内容列表
     */
    public function test()
    {
        //sop被动触发消息推送
        PushContentLogic::sopStagetriggerPushJob();
        echo 111;
    }
} 