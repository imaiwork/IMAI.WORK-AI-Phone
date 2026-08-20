<?php

namespace app\api\validate;


use app\common\model\user\User;
use app\common\enum\notice\NoticeEnum;
use app\common\service\sms\SmsDriver;
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

    public function sceneRegister()
    {
        $this->rule = [
            'account' => 'require|checkMobile',
            'code' => 'require|checkCode',
        ];
        $this->message = [
            'account.require' => '请输入手机号',
            'code.require' => '请输入手机验证码',
        ];
        return $this->only(['account', 'code']);
    }

    public function checkMobile($mobile)
    {
        if (!preg_match('/^1[3-9]\d{9}$/', (string)$mobile)) {
            return '请输入正确的手机号';
        }
        return true;
    }

    public function checkCode($code, $rule, $data)
    {
        $smsDriver = new SmsDriver();
        if ($smsDriver->verify($data['account'], $code, NoticeEnum::LOGIN_CAPTCHA)) {
            return true;
        }
        return '验证码错误';
    }
}
