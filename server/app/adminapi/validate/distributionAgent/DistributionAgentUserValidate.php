<?php

namespace app\adminapi\validate\distributionAgent;

use app\common\validate\BaseValidate;

/**
 * 分销代理用户验证器
 * Class DistributionAgentUserValidate
 * @package app\adminapi\validate\distributionAgent
 */
class DistributionAgentUserValidate extends BaseValidate
{
    protected $rule = [
        'user_id' => 'require|number',
    ];

    protected $message = [
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID格式错误',
    ];

    public function sceneDetail()
    {
        return $this->only(['user_id']);
    }
}
