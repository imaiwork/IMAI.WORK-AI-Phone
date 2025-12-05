<?php

namespace app\api\validate;


use app\common\model\user\User;
use app\common\validate\BaseValidate;

/**
 * 注册验证器
 * Class RegisterValidate
 * @package app\api\validate
 */
class RegisterValidate extends BaseValidate
{

    protected $regex = [
        'register' => '^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$',
        'password' => '/^(?![0-9]+$)(?![a-z]+$)(?![A-Z]+$)(?!([^(0-9a-zA-Z)]|[\(\)])+$)([^(0-9a-zA-Z)]|[\(\)]|[a-z]|[A-Z]|[0-9]){6,20}$/'
    ];

    protected $rule = [
        'channel'          => 'require',
        //        'account'          => 'require|length:3,12|unique:' . User::class . '|regex:register',
        'account'          => 'require|length:3,12|unique:' . User::class,
        'password'         => 'require|length:6,20|regex:password',
        'password_confirm' => 'require|confirm',
        //        'group'            => 'require',
    ];

    protected $message = [
        'channel.require'          => '注册来源参数缺失',
        'account.require'          => '请输入账号',
        'account.regex'            => '账号须为字母数字组合',
        'account.length'           => '账号须为3-12位之间',
        'account.unique'           => '账号已存在',
        'password.require'         => '请输入密码',
        'password.length'          => '密码须在6-25位之间',
        'password.regex'           => '密码须为数字,字母或符号组合',
        'password_confirm.require' => '请确认密码',
        'password_confirm.confirm' => '两次输入的密码不一致',
        //        'group.group'              => '部门未选择'
    ];
}
