<?php

namespace app\adminapi\controller\marketing;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\marketing\MarketingTemplateLogic;
use app\adminapi\validate\marketing\MarketingTemplateValidate;
use app\adminapi\lists\marketing\MarketingTemplateLists;

use Exception;
use think\response\Json;

class TemplateController extends BaseAdminController
{
    // 无需登录的接口
    public array $notNeedLogin = [];


    public function lists(): Json
    {
        return $this->dataLists(new MarketingTemplateLists());
    }

    public function add(): Json
    {
        $params = (new MarketingTemplateValidate())->post()->goCheck('add');
        $result = MarketingTemplateLogic::add($params);
        if ($result === false) {
            return $this->fail(MarketingTemplateLogic::getError());
        }
        return $this->success('添加成功', MarketingTemplateLogic::getReturnData());
    }

    /**
     * 编辑AI人设智能体设置
     * @return Json
     */
    public function edit(): Json
    {
        $params = (new MarketingTemplateValidate())->post()->goCheck('edit');
        $result = MarketingTemplateLogic::edit($params);
        if ($result === false) {
            return $this->fail(MarketingTemplateLogic::getError());
        }
        return $this->success('编辑成功', MarketingTemplateLogic::getReturnData());
    }

    

    /**
     * 获取AI人设智能体设置详情
     * @return Json
     */
    public function detail(): Json
    {
        $params = (new MarketingTemplateValidate())->get()->goCheck('detail');
        try {
            $result = MarketingTemplateLogic::detail($params);
            if ($result === false) {
                return $this->fail(MarketingTemplateLogic::getError() ?: '模板不存在');
            }
            return $this->data(MarketingTemplateLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete(): Json
    {
        $params = (new MarketingTemplateValidate())->post()->goCheck('delete');
        $result = MarketingTemplateLogic::delete($params);
        if ($result === false) {
            return $this->fail(MarketingTemplateLogic::getError());
        }
        return $this->success('删除成功', MarketingTemplateLogic::getReturnData());
    }

    /**
     * 修改模板状态
     * @return Json
     */
    public function updateStatus(): Json
    {
        $params = (new MarketingTemplateValidate())->post()->goCheck('updateStatus');
        $result = MarketingTemplateLogic::updateStatus($params);
        if ($result === false) {
            return $this->fail(MarketingTemplateLogic::getError());
        }
        return $this->success('状态修改成功', MarketingTemplateLogic::getReturnData());
    }
}
