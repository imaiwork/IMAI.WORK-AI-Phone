<?php

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\adminapi\validate\setting\DistributionAgentConfigValidate;

/**
 * 分销代理配置控制器
 * Class DistributionAgentConfigController
 * @package app\adminapi\controller\setting
 */
class DistributionAgentConfigController extends BaseAdminController
{
    /**
     * @notes 获取代理等级配置
     * @return \think\response\Json
     */
    public function getConfig()
    {
        $result = DistributionAgentConfigLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置代理等级配置
     * @return \think\response\Json
     */
    public function setConfig()
    {
        $params = (new DistributionAgentConfigValidate())->post()->goCheck('setConfig');
        DistributionAgentConfigLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 添加代理等级
     * @return \think\response\Json
     */
    public function addLevel()
    {
        $params = (new DistributionAgentConfigValidate())->post()->goCheck('addLevel');
        try {
            $result = DistributionAgentConfigLogic::addLevel($params);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('添加成功', $result);
    }

    /**
     * @notes 删除代理等级
     * @return \think\response\Json
     */
    public function delLevel()
    {
        $params = (new DistributionAgentConfigValidate())->post()->goCheck('delLevel');
        try {
            DistributionAgentConfigLogic::delLevel($params);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 获取每级代理可发展的下级人数上限
     * @return \think\response\Json
     */
    public function getSubLimits()
    {
        return $this->data(DistributionAgentConfigLogic::getSubLimits());
    }

    /**
     * @notes 设置每级代理可发展的下级人数上限
     * @return \think\response\Json
     */
    public function setSubLimits()
    {
        $params = request()->post();
        DistributionAgentConfigLogic::setSubLimits($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
