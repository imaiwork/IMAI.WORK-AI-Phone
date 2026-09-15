<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 团购截流记录校验
 * Class GroupBuyRecordValidate
 * @package app\api\validate\sv
 */
class GroupBuyRecordValidate extends BaseValidate
{
    protected $rule = [
        'id'       => 'require|integer',
        'accounts' => 'require',
    ];

    protected $message = [
        'id.require'      => '请传入记录ID',
        'id.integer'      => '记录ID格式错误',
        'accounts.require'=> '请选择账号',
    ];

    protected $scene = [
        'detail' => ['id'],
        'delete' => ['id'],
    ];
}