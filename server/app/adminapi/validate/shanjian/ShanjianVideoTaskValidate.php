<?php

namespace app\adminapi\validate\shanjian;

use app\common\validate\BaseValidate;

class ShanjianVideoTaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'name' => 'max:200',
        'pic' => 'max:255',
        'task_id' => 'max:50',
        'status' => 'in:0,1,2,3',
        'audio_type' => 'in:1,2',
        'user_id' => 'number',
        'video_setting_id' => 'number',
        'anchor_id' => 'max:50',
        'voice_id' => 'max:50',
        'card_name' => 'max:255',
        'card_introduced' => '',
        'title' => 'max:200',
        'msg' => '',
        'material' => '',
        'result_id' => 'max:255',
        'music_url' => '',
        'video_result_url' => '',
        'clip_id' => 'max:40',
        'video_token' => 'max:10',
        'extra' => '',
        'tries' => 'number',
        'remark' => 'max:255',
    ];

    protected $message = [
        'name.max' => '名称长度不能超过200',
        'title.max' => '标题长度不能超过200',
        'card_name.max' => '人设名字长度不能超过255',
    ];


}
