<?php

namespace app\api\validate\lianlian;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\lianlian\validate
 */
class LianlianValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
    ];
    protected $scene = [
        'add' => [
            "id",
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit' => [
            "id",
        ],
    ];
}
            