<?php

namespace app\adminapi\validate\audio;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\adminapi\audio\validate
 */
class AudioValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
    ];

    protected $scene = [
        'delete' => [
            "id",
        ],
    ];
}
