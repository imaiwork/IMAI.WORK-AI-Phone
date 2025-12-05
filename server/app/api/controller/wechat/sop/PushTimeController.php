<?php
declare (strict_types = 1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\lists\sop\PushTimeLists;
use app\api\logic\wechat\sop\PushTimeLogic;
use app\api\validate\wechat\sop\PushTimeValidate;
use think\exception\HttpResponseException;

/**
 * PushTimeController
 * @desc 推送时间管理
 */
class PushTimeController extends BaseApiController
{
    /**
     * @notes 创建推送时间
     */
    public function add()
    {
        try {
            $params = (new PushTimeValidate())->scene('createPushTime')->post()->goCheck();
            $result = PushTimeLogic::createPushTime($params);
            if ($result) {
                return $this->success(data: PushTimeLogic::getReturnData());
            }
            return $this->fail(PushTimeLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新推送时间
     */
    public function update()
    {
        try {
            $params = (new PushTimeValidate())->post()->goCheck('updatePushTime');
            $result = PushTimeLogic::updatePushTime($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushTimeLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除推送时间
     */
    public function delete()
    {
        try {
            $params = (new PushTimeValidate())->post()->goCheck('deletePushTime');
            $result = PushTimeLogic::deletePushTime($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PushTimeLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
    
    /**
     * @notes 获取推送时间列表
     */
    public function lists()
    {
        $pushTimeLists = new PushTimeLists(); // 实例化 PushTimeLists
        $pushTimeLists->setSearch(); // 设置搜索条件
        $lists = $pushTimeLists->lists(); // 调用 lists 方法
        return $this->success(data: $lists);
    }

    /**
     * @notes 获取推送时间列表
     */
    public function pushTimeLists()
    {
        $push_id = $this->request->param('push_id');
        $pushTimeLogic = new PushTimeLogic(); // 实例化 PushTimeLogic
        $lists = $pushTimeLogic->getPushTimeList($push_id); // 调用逻辑方法并传递 push_id
        return $this->success(data: $lists);
    }
} 