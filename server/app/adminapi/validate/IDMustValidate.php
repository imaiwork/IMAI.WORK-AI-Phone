<?php

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * ID参数验证器
 */
class IDMustValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
    ];

    protected $message = [
        'id' => 'id参数缺失',
    ];
}