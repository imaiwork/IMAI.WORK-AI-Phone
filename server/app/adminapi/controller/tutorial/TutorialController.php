<?php

namespace app\adminapi\controller\tutorial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\tutorial\TutorialLists;
use app\adminapi\logic\tutorial\TutorialLogic;
use app\adminapi\validate\tutorial\TutorialValidate;

/**
 * 教程卡片管理控制器
 * Class TutorialController
 * @package app\adminapi\controller\tutorial
 */
class TutorialController extends BaseAdminController
{


    public function lists()
    {
        return $this->dataLists(new TutorialLists());
    }


    public function add()
    {
        $params = (new TutorialValidate())->post()->goCheck('add');
        $result = TutorialLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', TutorialLogic::getReturnData());
        }
        return $this->fail(TutorialLogic::getError());
    }


    public function edit()
    {
        $params = (new TutorialValidate())->post()->goCheck('edit');
        $result = TutorialLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(TutorialLogic::getError());
    }


    public function delete()
    {
        $params = (new TutorialValidate())->post()->goCheck('delete');
        $result = TutorialLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(TutorialLogic::getError());
    }


    public function detail()
    {
        $params = (new TutorialValidate())->goCheck('detail');
        $result = TutorialLogic::detail($params);
        if (true === $result) {
            return $this->data(TutorialLogic::getReturnData());
        }
        return $this->fail(TutorialLogic::getError());
    }
}
