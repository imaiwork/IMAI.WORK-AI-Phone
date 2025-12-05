<?php

namespace app\api\controller;


use app\api\lists\workWeChat\FileLists;
use app\api\lists\workWeChat\importLists;
use app\api\lists\workWeChat\PhoneLists;
use app\api\lists\workWeChat\WorkWeChatLists;
use app\api\logic\WorkWeChatLogic;
use app\api\validate\WorkWeChatValidate;
use think\response\Json;


/**
 *
 * Class WechatController
 * @package app\api\controller
 */
class WorkWeChatController extends BaseApiController
{
    public array $notNeedLogin = [
        'getData'
    ];
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function lists()
    {
        return $this->dataLists(new WorkWeChatLists());
    }


    /**
     * @notes 列表
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function phoneLists()
    {
        return $this->dataLists(new PhoneLists());
    }


    /**
     * @notes 导入列表
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function importLists()
    {
        return $this->dataLists(new importLists());
    }

    /**
     * @notes 添加
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function add(): Json
    {
        $params = (new WorkWeChatValidate())->post()->goCheck('add');
        return WorkWeChatLogic::add($params, $this->userId) ? $this->success('操作成功', WorkWeChatLogic::getReturnData()) : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * @notes 修改
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function checkUserLogin(): Json
    {
        return WorkWeChatLogic::checkUserLogin($this->request->get(), $this->userId)
            ? $this->success('操作成功')
            : $this->fail(WorkWeChatLogic::getError());
    }

    /**
     * @notes 修改
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function updateUser(): Json
    {
        $params = (new WorkWeChatValidate())->post()->goCheck('updateUser');
        return WorkWeChatLogic::updateUser($params, $this->userId) ? $this->success('操作成功', WorkWeChatLogic::getReturnData()) : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * @notes 修改
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function edit(): Json
    {
        $params = (new WorkWeChatValidate())->post()->goCheck('edit');
        return WorkWeChatLogic::edit($params) ? $this->success('操作成功') : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * 导入
     * @return Json
     * @author L
     * @data 2024/8/19 15:01
     */
    public function importData(): Json
    {
        $params = (new WorkWeChatValidate())->post()->goCheck('importList');
        return WorkWeChatLogic::importData($params, $this->userId) ? $this->success('操作成功') : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function delete(): Json
    {
        $getData = (new WorkWeChatValidate())->get()->goCheck('delete');
        $result  = WorkWeChatLogic::delete($getData, $this->userId);
        return $result ? $this->success() : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function detail(): Json
    {
        $getData = (new WorkWeChatValidate())->get()->goCheck('detail');
        $result  = WorkWeChatLogic::detail($getData, $this->userId);
        return $result ? $this->success(data: WorkWeChatLogic::getReturnData()) : $this->fail(WorkWeChatLogic::getError());
    }


    /**
     * 更换状态
     * @return Json
     * @author L
     * @data 2024/7/5 10:25
     */
    public function changeStatus(): Json
    {
        $params       = (new WorkWeChatValidate())->post()->goCheck('changeStatus');
        $changeStatus = WorkWeChatLogic::changeStatus($params, $this->userId);
        return $changeStatus ? $this->success() : $this->fail(WorkWeChatLogic::getError());
    }

    /**
     * 添加好友
     * @return void
     * @author L
     * @data 2024/8/19 18:42
     */
    public function apply()
    {
        echo WorkWeChatLogic::apply();
        exit();
    }

    /**
     * 接收请求
     * @return Json
     * @throws \Exception
     * @author L
     * @data 2024/8/20 11:22
     */
    public function getData():Json
    {
        $data = $this->request->post();
        if (empty($data)) {
            $this->fail();
        }
        $data = WorkWeChatLogic::getData($data);
        return $this->data(data: $data);
    }
}
            