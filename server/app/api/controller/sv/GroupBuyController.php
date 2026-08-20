<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\GroupBuyRecordLists;
use app\api\lists\sv\GroupBuyTaskLists;
use app\api\logic\sv\GroupBuyLogic;
use app\api\validate\sv\GroupBuyTaskValidate;
use think\exception\HttpResponseException;

/**
 * GroupBuyController
 * @desc 团购截流任务
 */
class GroupBuyController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 获取任务列表
     */
    public function lists()
    {
        return $this->dataLists(new GroupBuyTaskLists());
    }

    /**
     * @desc 获取记录列表
     */
    public function recordLists()
    {
        return $this->dataLists(new GroupBuyRecordLists());
    }

    /**
     * @desc 添加团购截流任务
     */
    public function add()
    {
        try {
            $params = (new GroupBuyTaskValidate())->post()->goCheck('add');
            $result = GroupBuyLogic::add($params);
            if ($result) {
                return $this->success(data: GroupBuyLogic::getReturnData());
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 编辑团购截流任务
     */
    public function edit()
    {
        try {
            $params = (new GroupBuyTaskValidate())->post()->goCheck('edit');
            $result = GroupBuyLogic::edit($params);
            if ($result) {
                return $this->success(data: GroupBuyLogic::getReturnData());
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除任务
     */
    public function delete()
    {
        try {
            $params = (new GroupBuyTaskValidate())->post()->goCheck('delete');
            $result = GroupBuyLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新任务状态（暂停/恢复）
     */
    public function updateStatus()
    {
        try {
            $params = (new GroupBuyTaskValidate())->post()->goCheck('updateStatus');
            $result = GroupBuyLogic::updateStatus($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 获取筛选词历史
     */
     public function getFilterHistory()
    {
        try {
            $result = GroupBuyLogic::getFilterHistory([]);
            if ($result) {
                return $this->data(GroupBuyLogic::getReturnData());
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 获取任务详情
     */
    public function detail()
    {
        try {
            $params = (new GroupBuyTaskValidate())->get()->goCheck('detail');
            $result = GroupBuyLogic::detail($params);
            if ($result !== false) {
                return $this->success(data: $result);
            }
            return $this->fail(GroupBuyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}