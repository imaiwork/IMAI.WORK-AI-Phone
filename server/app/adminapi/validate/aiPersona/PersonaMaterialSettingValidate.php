<?php

namespace app\adminapi\validate\aiPersona;

use app\common\validate\BaseValidate;

/**
 * IP人设设置验证
 * Class PersonaMaterialSettingValidate
 * @package app\adminapi\validate\aiPersona
 */
class PersonaMaterialSettingValidate extends BaseValidate
{
    protected $rule = [
        'material_clean_switch' => 'require|in:0,1',
        'material_clean_threshold' => 'require|integer|egt:1|elt:999',
    ];

    protected $message = [
        'material_clean_switch.require' => '请选择一键清理按钮显示状态',
        'material_clean_switch.in' => '一键清理按钮显示状态值有误',
        'material_clean_threshold.require' => '请填写素材清理次数阈值',
        'material_clean_threshold.integer' => '素材清理次数阈值须为整数',
        'material_clean_threshold.egt' => '素材清理次数阈值不能小于1',
        'material_clean_threshold.elt' => '素材清理次数阈值不能大于999',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['material_clean_switch', 'material_clean_threshold']);
    }
}
