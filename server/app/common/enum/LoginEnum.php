<?php

namespace app\common\enum;

/**
 * 登录枚举
 * Class LoginEnum
 * @package app\common\enum
 */
class LoginEnum
{
    /**
     * 支持的登录方式
     * ACCOUNT_PASSWORD 账号/手机号密码登录
     * MOBILE_CAPTCHA 手机验证码登录
     * THIRD_LOGIN 第三方登录
     * MNP_AUTH_LOGIN 小程序授权PC登录
     */
    const ACCOUNT_PASSWORD = 1;
    const MOBILE_CAPTCHA = 2;
    const THIRD_LOGIN = 3;
    const MNP_AUTH_LOGIN = 4;
}
