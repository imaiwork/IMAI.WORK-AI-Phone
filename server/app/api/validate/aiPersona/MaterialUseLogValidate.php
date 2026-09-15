<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class MaterialUseLogValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'persona_id' => 'number',
        'material_id' => 'number',
        'task_id' => 'number',
        'use_scene' => 'in:1,2,3',
        'use_status' => 'in:0,1,2',
        'fail_reason' => 'max:500',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须是数字',
        'persona_id.number' => '人设ID必须是数字',
        'material_id.number' => '素材ID必须是数字',
        'task_id.number' => '任务ID必须是数字',
        'use_scene.in' => '使用场景值只能是1、2或3',
        'use_status.in' => '使用状态值只能是0、1或2',
        'fail_reason.max' => '失败原因最多500个字符',
    ];

    public function sceneUpdate()
    {
        return $this->only(['id', 'use_status', 'fail_reason']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}
