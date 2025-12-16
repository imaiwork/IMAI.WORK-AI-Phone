<?php

namespace app\adminapi\validate\hd;

use app\common\validate\BaseValidate;

class HdPuzzleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'name' => 'max:200',
        'task_id' => 'max:50',
        'status' => 'in:0,1,2',
        'type' => 'in:1,2,3,4,5',
        'user_id' => 'number',
        'puzzle_setting_id' => 'number',
        'title' => '',
        'material' => '',
        'puzzle_url' => '',
        'img_token' => 'max:10',
        'extra' => '',
        'tries' => 'number',
        'remark' => 'max:255',
    ];

    protected $message = [
        'name.max' => '名称长度不能超过200',
        'task_id.max' => '任务ID长度不能超过50',
        'img_token.max' => '图片扣费长度不能超过10',
        'remark.max' => '失败原因长度不能超过255',
    ];

    protected $scene = [
        'add' => ['name', 'user_id', 'puzzle_setting_id', 'title', 'material', 'puzzle_url', 'img_token', 'extra'],
        'edit' => ['id', 'name', 'title', 'material', 'puzzle_url', 'img_token', 'extra'],
        'delete' => ['id'],
        'detail' => ['id'],
        'updateStatus' => ['id', 'status'],
    ];
}