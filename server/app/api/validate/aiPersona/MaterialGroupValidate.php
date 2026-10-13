<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class MaterialGroupValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'persona_id' => 'require|number',
        'name' => 'require|max:30',
        'keep_original' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '分组ID是必填项',
        'id.number' => '分组ID必须是数字',
        'persona_id.require' => '人设ID是必填项',
        'persona_id.number' => '人设ID必须是数字',
        'name.require' => '请填写分组名称',
        'name.max' => '分组名称最多30个字',
    ];

    public function sceneAdd()
    {
        return $this->only(['persona_id', 'name', 'keep_original']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'name']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneLists()
    {
        return $this->only(['persona_id']);
    }
}
