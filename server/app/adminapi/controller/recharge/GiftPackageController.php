<?php
namespace app\adminapi\controller\recharge;


use app\adminapi\lists\recharge\GiftPackageLists;
use app\adminapi\logic\recharge\GiftPackageLogic;
use app\adminapi\validate\recharge\GiftPackageValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\adminapi\controller
 */
class GiftPackageController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists()
    {
        return $this->dataLists(new GiftPackageLists());
    }


    /**
     * @notes 添加
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function add():Json
    {
        $params = (new GiftPackageValidate())->post()->goCheck('add');
        return GiftPackageLogic::add($params) ? $this->success('操作成功', GiftPackageLogic::getReturnData()) : $this->fail(GiftPackageLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function delete():Json
    {
        $getData = (new GiftPackageValidate())->get()->goCheck('delete');
        $result = GiftPackageLogic::delete($getData);
        return $result ? $this->success() : $this->fail(GiftPackageLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function detail():Json
    {
        $getData = (new GiftPackageValidate())->get()->goCheck('detail');
        $result = GiftPackageLogic::detail($getData);
        return $result ? $this->success(data: GiftPackageLogic::getReturnData()) : $this->fail(GiftPackageLogic::getError());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function edit():Json
    {
        $postData = (new GiftPackageValidate())->post()->goCheck('edit');
        $edit      = GiftPackageLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(GiftPackageLogic::getError());
    }

    /**
     * 更换状态
     * @return Json
     * @author L
     * @data 2024/7/5 10:25
     */
    public function changeStatus():Json
    {
        $params = (new GiftPackageValidate())->post()->goCheck('changeStatus');
        $changeStatus      = GiftPackageLogic::changeStatus($params);
        return $changeStatus ? $this->success() : $this->fail(GiftPackageLogic::getError());
    }
}
                        