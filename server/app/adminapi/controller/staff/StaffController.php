<?php

namespace app\adminapi\controller\staff;


use app\adminapi\lists\staff\StaffLists;
use app\adminapi\logic\staff\StaffLogic;
use app\adminapi\validate\staff\StaffValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\adminapi\controller
 */
class StaffController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function lists()
    {
        return $this->dataLists(new StaffLists());
    }



    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function detail(): Json
    {
        $getData = (new StaffValidate())->get()->goCheck('detail');
        $result = StaffLogic::detail($getData);
        return $result ? $this->data(StaffLogic::getReturnData()) : $this->fail(StaffLogic::getError());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function edit(): Json
    {
        $postData = (new StaffValidate())->post()->goCheck('edit');
        $edit      = StaffLogic::edit($postData);
        return $edit ? $this->success('编辑成功', StaffLogic::getReturnData(), 1, 1) : $this->fail(StaffLogic::getError());
    }

    /**
     * 更换状态
     * @return Json
     * @author L
     * @data 2024/7/5 10:25
     */
    public function changeStatus(): Json
    {
        $params = (new StaffValidate())->post()->goCheck('changeStatus');
        $changeStatus      = StaffLogic::changeStatus($params);
        return $changeStatus ? $this->success('编辑成功', [], 1, 1) : $this->fail(StaffLogic::getError());
    }
}
