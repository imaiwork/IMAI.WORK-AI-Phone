<?php

namespace app\adminapi\controller\catering;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\catering\CateringFranchiseLists;
use app\adminapi\logic\catering\CateringFranchiseLogic;
use app\adminapi\validate\catering\CateringFranchiseValidate;

/**
 * 招商项目管理控制器
 * Class CateringFranchiseController
 * @package app\adminapi\controller\catering
 */
class CateringFranchiseController extends BaseAdminController
{


    public function lists()
    {
        return $this->dataLists(new CateringFranchiseLists());
    }


    public function add()
    {
        $params = (new CateringFranchiseValidate())->post()->goCheck('add');
        $result = CateringFranchiseLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', CateringFranchiseLogic::getReturnData());
        }
        return $this->fail(CateringFranchiseLogic::getError());
    }


    public function edit()
    {
        $params = (new CateringFranchiseValidate())->post()->goCheck('edit');
        $result = CateringFranchiseLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(CateringFranchiseLogic::getError());
    }


    public function delete()
    {
        $params = (new CateringFranchiseValidate())->post()->goCheck('delete');
        $result = CateringFranchiseLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(CateringFranchiseLogic::getError());
    }


    public function detail()
    {
        $params = (new CateringFranchiseValidate())->goCheck('detail');
        $result = CateringFranchiseLogic::detail($params);
        if (true === $result) {
            return $this->data(CateringFranchiseLogic::getReturnData());
        }
        return $this->fail(CateringFranchiseLogic::getError());
    }
}
