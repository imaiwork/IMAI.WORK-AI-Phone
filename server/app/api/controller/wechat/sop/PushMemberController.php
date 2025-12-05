<?php

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\lists\sop\PushMemberLists;
use app\api\logic\wechat\sop\PushMemberLogic;
use app\api\validate\wechat\sop\PushMemberValidate;
use think\exception\HttpResponseException;

class PushMemberController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new PushMemberLists());
    }

    /**
     * @notes 创建推送人员
     */
    public function add()
    {
        try {
            // 验证参数
            $params = (new PushMemberValidate())->post()->goCheck();

            // 调用逻辑层处理推送内容和推送时间的插入
            $result = PushMemberLogic::createPushMember($params);
            if ($result) {
                return $this->success(data: PushMemberLogic::getReturnData());
            }
            return $this->fail(PushMemberLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除推送人员
     */
    public function delete()
    {
        try {
            $params = (new PushMemberValidate())->post()->goCheck();
            $result = PushMemberLogic::deletePushMember($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushMemberLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 转移推送人员
     */
    public function transfer()
    {
        try {
            $params = (new PushMemberValidate())->post()->goCheck('transfer');
            $result = PushMemberLogic::transferPushMember($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushMemberLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}