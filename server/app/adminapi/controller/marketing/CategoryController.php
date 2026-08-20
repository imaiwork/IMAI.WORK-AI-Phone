<?php

namespace app\adminapi\controller\marketing;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\marketing\MarketingCategoryLogic;
use app\adminapi\validate\marketing\MarketingCategoryValidate;
use app\adminapi\lists\marketing\MarketingCategoryLists;

use Exception;
use think\response\Json;

class CategoryController extends BaseAdminController
{
    // 无需登录的接口
    public array $notNeedLogin = [];


    public function lists(): Json
    {
        return $this->dataLists(new MarketingCategoryLists());
    }

    public function add(): Json
    {
        $params = (new MarketingCategoryValidate())->post()->goCheck('add');
        $result = MarketingCategoryLogic::add($params);
        if ($result === false) {
            return $this->fail(MarketingCategoryLogic::getError());
        }
        return $this->success('添加成功', MarketingCategoryLogic::getReturnData());
    }

    /**
     * 编辑AI人设智能体设置
     * @return Json
     */
    public function edit(): Json
    {
        $params = (new MarketingCategoryValidate())->post()->goCheck('edit');
        $result = MarketingCategoryLogic::edit($params);
        if ($result === false) {
            return $this->fail(MarketingCategoryLogic::getError());
        }
        return $this->success('编辑成功', MarketingCategoryLogic::getReturnData());
    }

    /**
     * 获取AI人设智能体设置详情
     * @return Json
     */
    public function detail(): Json
    {
        $params = (new MarketingCategoryValidate())->get()->goCheck('detail');
        try {
            $result = MarketingCategoryLogic::detail($params);
            if ($result === false) {
                return $this->fail(MarketingCategoryLogic::getError() ?: '分类不存在');
            }
            return $this->data(MarketingCategoryLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete(): Json
    {
        $params = (new MarketingCategoryValidate())->post()->goCheck('delete');
        $result = MarketingCategoryLogic::delete($params);
        if ($result === false) {
            return $this->fail(MarketingCategoryLogic::getError());
        }
        return $this->success('删除成功', MarketingCategoryLogic::getReturnData());
    }
}
