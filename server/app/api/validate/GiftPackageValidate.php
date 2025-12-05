<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\validate
 */
class GiftPackageValidate extends BaseValidate
{
    protected $rule = [
        'id'         => 'require',
        'package_id' => 'require',
    ];


    protected $message = [
        'id.require'         => '主键参数缺失',
        'package_id.require' => '礼包参数缺失',
    ];
    protected $scene = [
        'recharge'          => [
            'package_id'
        ],
        'delete'       => [
            "id",
        ],
        'detail'       => [
            "id",
        ],
        'edit'         => [
            "id",
        ],
        'changeStatus' => [
            "id",
        ],
    ];
}
            