<?php

namespace app\adminapi\validate\hd;

use app\common\validate\BaseValidate;

class HdPuzzleSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'user_id' => 'number',
        'name' => 'max:50',
        'task_id' => 'max:50',
        'status' => 'in:1,2,3,4,5',
        'result_count' => 'number',
        'task_count' => 'number',
        'success_puzzle_count' => 'number',
        'copywriting' => '',
        'material' => '',
        'extra' => '',
        'success_num' => 'number',
        'error_num' => 'number',
    ];

    protected $message = [
        'name.max' => '名称长度不能超过50',
        'task_id.max' => '任务ID长度不能超过50',
    ];


}