<?php

namespace app\adminapi\validate\deviceauth;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\validate\BaseValidate;

class DeviceAuthCodeValidate extends BaseValidate
{
    protected $rule = [
        'id'          => 'require|integer|gt:0',
        'user_id'     => 'require|integer|gt:0',
        'type'        => 'require|checkType',
        'num'         => 'require|integer|gt:0|elt:500',
        'rule_type'   => 'require|in:1,2',
        'is_open'     => 'require|in:0,1',
        'code_prefix' => 'max:16',
    ];

    protected $message = [
        'id.require'        => '请选择设备CDK',
        'user_id.require'   => '请选择用户',
        'user_id.integer'   => '用户ID必须为整数',
        'user_id.gt'        => '用户ID必须大于0',
        'type.require'      => '请选择CDK类型',
        'num.require'       => '请输入生成数量',
        'num.gt'            => '生成数量必须大于0',
        'num.elt'           => '单次最多生成500个',
        'rule_type.require' => '请选择生成规则',
        'rule_type.in'      => '生成规则错误',
        'is_open.require'   => '请选择开关状态',
        'is_open.in'        => '开关状态错误',
    ];

    public function sceneGenerate()
    {
        return $this->only(['type', 'num', 'rule_type']);
    }

    public function sceneId()
    {
        return $this->only(['id']);
    }

    public function sceneTransfer()
    {
        return $this->only(['id', 'user_id']);
    }

    public function sceneSetConfig()
    {
        return $this->only(['is_open', 'code_prefix']);
    }

    protected function checkType($value)
    {
        if (!array_key_exists((int)$value, DeviceAuthCodeEnum::getTypeDesc())) {
            return 'CDK类型错误';
        }
        return true;
    }
}
