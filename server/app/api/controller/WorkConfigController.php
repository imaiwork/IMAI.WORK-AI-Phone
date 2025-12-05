<?php
namespace app\api\controller;


use app\api\lists\WorkConfigLists;
use app\api\logic\WorkConfigLogic;
use app\api\validate\WorkConfigValidate;
use app\api\controller\BaseApiController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\api\controller
 */
class WorkConfigController extends BaseApiController
{

    /**
     * @notes 添加
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function add():Json
    {
        $params = (new WorkConfigValidate())->post()->goCheck('add');
        return WorkConfigLogic::add($params, $this->userId) ? $this->success('操作成功', WorkConfigLogic::getReturnData()) : $this->fail(WorkConfigLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function delete():Json
    {
        $getData = (new WorkConfigValidate())->get()->goCheck('delete');
        $result = WorkConfigLogic::delete($getData, $this->userId);
        return $result ? $this->success() : $this->fail(WorkConfigLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function detail():Json
    {
        $result = WorkConfigLogic::detail($this->userId);
        return $result ? $this->success(data: WorkConfigLogic::getReturnData()) : $this->fail(WorkConfigLogic::getError());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function edit():Json
    {
        $postData = (new WorkConfigValidate())->post()->goCheck('edit');
        $edit      = WorkConfigLogic::edit($postData, $this->userId);
        return $edit ? $this->success() : $this->fail(WorkConfigLogic::getError());
    }
}
            