<?php

namespace app\adminapi\validate\recharge;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\adminapi\recharge\validate
 */
class GiftPackageValidate extends BaseValidate
{
    protected $rule = [
        'id'            => 'require',
        'name'          => 'requireIf:type,2',
        'desc'          => 'requireIf:type,2',
        'selling_price' => 'require',
        'type'          => 'require|in:1,2',
        'price'         => 'require',
        'package_info'  => 'require',
    ];


    protected $message = [
        'id.require'            => '主键参数缺失',
        'name.requireIf'        => '礼包名称参数缺失',
        'desc.requireIf'        => '礼包备注参数缺失',
        'selling_price.require' => '售卖金额参数缺失',
        'price.require'         => '实际金额参数缺失',
        'package_info.require'  => '礼包信息参数缺失',
        'type.require'          => '礼包类型参数缺失',
        'type.in'               => '礼包类型参数错误',
    ];
    protected $scene = [
        'add'          => [
//            "name",
//            "desc",
//            "selling_price",
"type",
"price",
"package_info",
        ],
        'delete'       => [
            "id",
        ],
        'detail'       => [
            "id",
        ],
        'edit'         => [
            "id",
            //            "name",
            //            "desc",
            //            "selling_price",
            "type",
            "price",
            "package_info",
        ],
        'changeStatus' => [
            "id",
        ],
    ];
}
            