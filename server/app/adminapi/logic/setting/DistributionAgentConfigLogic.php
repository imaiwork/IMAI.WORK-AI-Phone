<?php

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * 分销代理配置逻辑
 * Class DistributionAgentConfigLogic
 * @package app\adminapi\logic\setting
 */
class DistributionAgentConfigLogic extends BaseLogic
{
    /**
     * @notes 获取分销代理配置
     * @author MonitorAllen
     * @return array
     */
    public static function getConfig()
    {
        $config = ConfigService::get('distribution_agent', 'distribution_agent_level_names', [
            ['level' => 1, 'name' => '高级代理'],
            ['level' => 2, 'name' => '中级代理'],
            ['level' => 3, 'name' => '初级代理'],
        ]);

        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        return $config;
    }

    /**
     * @notes 设置分销代理配置
     * @param $params
     */
    public static function setConfig($params)
    {
        // $params expected to be the json array `[{level: 1, name: '...'}, ...]`
        ConfigService::set('distribution_agent', 'distribution_agent_level_names', json_encode($params['config'], JSON_UNESCAPED_UNICODE));
    }
}
