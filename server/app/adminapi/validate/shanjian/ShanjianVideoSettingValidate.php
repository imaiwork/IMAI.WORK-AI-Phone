<?php

namespace app\adminapi\validate\shanjian;

use app\common\validate\BaseValidate;

class ShanjianVideoSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'user_id' => 'number',
        'name' => 'max:50',
        'pic' => 'max:255',
        'task_id' => 'max:50',
        'status' => 'in:1,2,3',
        'speed' => 'in:0,1,2',
        'video_count' => 'number',
        'anchor' => '',
        'voice' => '',
        'copywriting' => '',
        'title' => '',
        'character_design' => '',
        'material' => '',
        'clip' => '',
        'music' => '',
        'extra' => '',
        'success_num' => 'number',
        'error_num' => 'number',
    ];

    protected $message = [
        'name.max' => '名称长度不能超过50',
        'pic.max' => '封面长度不能超过255',
    ];

}
