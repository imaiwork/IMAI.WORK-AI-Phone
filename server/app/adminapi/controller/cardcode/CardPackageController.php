<?php
namespace app\adminapi\controller\cardcode;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\cardcode\CardPackageLogic;
use app\adminapi\validate\cardcode\CardPackageValidate;
use app\adminapi\lists\cardcode\CardPackageLists;

/**
 * 卡密套餐控制器
 * Class CardPackageController
 * @package app\adminapi\controller\cardcode
 */
class CardPackageController extends BaseAdminController
{
    /**
     * @notes 套餐列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CardPackageLists());
    }

    /**
     * @notes 添加套餐
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CardPackageValidate())->post()->goCheck('add');
        CardPackageLogic::add($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 编辑套餐
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CardPackageValidate())->post()->goCheck('edit');
        CardPackageLogic::edit($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 套餐详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CardPackageValidate())->goCheck('detail');
        $detail = CardPackageLogic::detail($params['id']);
        return $this->success('', $detail);
    }

    /**
     * @notes 删除套餐
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CardPackageValidate())->post()->goCheck('delete');
        CardPackageLogic::delete($params['id']);
        return $this->success('操作成功', [], 1, 1);
    }
}
