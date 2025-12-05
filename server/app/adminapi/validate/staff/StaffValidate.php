<?php

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\adminapi\staff\validate
 */
class StaffValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
        'pic'    => 'require',
        'name'    => 'require',
        'tips'    => 'require',
        'brief'    => 'require',
        'content'    => 'require',
        'sort'    => 'require',
        'is_new'    => 'require',
        'show_status'    => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
    ];
    protected $scene = [
        'detail' => [
            'id',
        ],
        'edit' => [
            "pic",
            "name",
            "tips",
            "brief",
            "content",
            "sort",
            "is_new",
            "show_status"
        ],
        'changeStatus' => [
            "id",
        ],
    ];
}
            