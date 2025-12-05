<?php


namespace app\api\validate;

use app\common\validate\BaseValidate;


/**
 * 微信登录验证
 * Class WechatLoginValidate
 * @package app\api\validate
 */
class WechatLoginValidate extends BaseValidate
{
    protected $rule = [
        'code' => 'require',
        'nickname' => 'require',
        'headimgurl' => 'require',
        'openid' => 'require',
        'access_token' => 'require',
        'terminal' => 'require',
        'avatar' => 'require',
    ];

    protected $message = [
        'code.require' => 'code缺少',
        'nickname.require' => '昵称缺少',
        'headimgurl.require' => '头像缺少',
        'openid.require' => 'opendid缺少',
        'access_token.require' => 'access_token缺少',
        'terminal.require' => '终端参数缺少',
        'avatar.require' => '头像缺少',
    ];


    /**
     * @notes 公众号登录场景
     * @return WechatLoginValidate
     * @author 段誉
     * @date 2022/9/16 10:57
     */
    public function sceneOa()
    {
        return $this->only(['code']);
    }


    /**
     * @notes 小程序-授权登录场景
     * @return WechatLoginValidate
     * @author 段誉
     * @date 2022/9/16 11:15
     */
    public function sceneMnpLogin()
    {
        return $this->only(['code']);
    }


    /**
     * @notes
     * @return WechatLoginValidate
     * @author 段誉
     * @date 2022/9/16 11:15
     */
    public function sceneWechatAuth()
    {
        return $this->only(['code']);
    }


    /**
     * @notes 更新用户信息场景
     * @return WechatLoginValidate
     * @author 段誉
     * @date 2023/2/22 11:14
     */
    public function sceneUpdateUser()
    {
        return $this->only(['nickname', 'avatar']);
    }
}
