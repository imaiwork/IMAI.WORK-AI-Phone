<?php

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\adminapi\validate\setting\DistributionAgentConfigValidate;

/**
 * 分销代理配置
 * Class DistributionAgentConfigController
 * @package app\adminapi\controller\setting
 */
class DistributionAgentConfigController extends BaseAdminController
{
    /**
     * @notes 获取分销代理配置
     * @return \think\response\Json
     */
    public function getConfig()
    {
        $result = DistributionAgentConfigLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置分销代理配置
     * @return \think\response\Json
     */
    public function setConfig()
    {
        $params = request()->post();
        if (empty($params)) {
            return $this->fail('参数不能为空');
        }
        DistributionAgentConfigLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
