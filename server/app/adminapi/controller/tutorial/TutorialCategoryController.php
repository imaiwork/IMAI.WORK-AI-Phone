<?php

namespace app\adminapi\controller\tutorial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\tutorial\TutorialCategoryLists;
use app\adminapi\logic\tutorial\TutorialCategoryLogic;
use app\adminapi\validate\tutorial\TutorialCategoryValidate;

/**
 * 教程分类管理控制器
 * Class TutorialCategoryController
 * @package app\adminapi\controller\tutorial
 */
class TutorialCategoryController extends BaseAdminController
{


    public function lists()
    {
        return $this->dataLists(new TutorialCategoryLists());
    }


    public function add()
    {
        $params = (new TutorialCategoryValidate())->post()->goCheck('add');
        TutorialCategoryLogic::add($params);
        return $this->success('添加成功', [], 1, 1);
    }


    public function edit()
    {
        $params = (new TutorialCategoryValidate())->post()->goCheck('edit');
        $result = TutorialCategoryLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(TutorialCategoryLogic::getError());
    }


    public function delete()
    {
        $params = (new TutorialCategoryValidate())->post()->goCheck('delete');
        TutorialCategoryLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    public function detail()
    {
        $params = (new TutorialCategoryValidate())->goCheck('detail');
        $result = TutorialCategoryLogic::detail($params);
        return $this->data($result);
    }


    public function all()
    {
        $result = TutorialCategoryLogic::getAllData();
        return $this->data($result);
    }
}
