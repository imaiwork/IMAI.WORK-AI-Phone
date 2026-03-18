<?php

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 分销代理配置验证
 * Class DistributionAgentConfigValidate
 * @package app\adminapi\validate\setting
 */
class DistributionAgentConfigValidate extends BaseValidate
{
    protected $rule = [
        'config' => 'require|array',
    ];

    protected $message = [
        'config.require' => '代理配置不能为空',
        'config.array' => '数据格式必须是数组',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['config']);
    }
}
