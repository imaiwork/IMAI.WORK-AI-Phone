<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\validate
 */
class WorkConfigValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
        'count'    => 'require',
        'space_time'    => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
    ];
    protected $scene = [
        'add' => [
            "id",
            "count",
            "space_time",
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit' => [
            "count",
            "space_time",
        ],
    ];
}
            