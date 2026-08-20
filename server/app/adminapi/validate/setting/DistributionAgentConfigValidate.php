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
        'name' => 'require|max:10',
        'remark' => 'max:255',
        'level' => 'require|integer|gt:0',
    ];

    protected $message = [
        'config.require' => '代理配置不能为空',
        'config.array' => '数据格式必须是数组',
        'name.require' => '请输入等级名称',
        'name.max' => '等级名称最多 10 个字',
        'remark.max' => '备注最多 255 个字',
        'level.require' => '请选择代理等级',
        'level.integer' => '代理等级错误',
        'level.gt' => '代理等级错误',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['config']);
    }

    public function sceneAddLevel()
    {
        return $this->only(['name', 'remark']);
    }

    public function sceneDelLevel()
    {
        return $this->only(['level']);
    }
}
