<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\aiPersona\AgentConfigLogic;
use Exception;
use think\response\Json;

class AgentConfigController extends BaseAdminController
{
    // 无需登录的接口
    public array $notNeedLogin = [];

    /**
     * 编辑AI人设智能体设置
     * @return Json
     */
    public function update(): Json
    {
        $params = $this->request->post();
        $result = AgentConfigLogic::update($params);
        if ($result === false) {
            return $this->fail(AgentConfigLogic::getError());
        }
        return $this->success('编辑成功', AgentConfigLogic::getReturnData());
    }

    /**
     * 获取AI人设智能体设置详情
     * @return Json
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        try {
            $result = AgentConfigLogic::detail(intval($params['persona_id']));
            if ($result === false) {
                return $this->fail(AgentConfigLogic::getError() ?: '人设智能体设置不存在');
            }
            return $this->data(AgentConfigLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}