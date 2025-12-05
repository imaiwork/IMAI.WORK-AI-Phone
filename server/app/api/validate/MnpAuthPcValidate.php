<?php


namespace app\api\validate;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\user\UserSession;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 账号密码登录校验
 * Class LoginValidate
 * @package app\api\validate
 */
class MnpAuthPcValidate extends BaseValidate
{

    protected $rule = [
        'terminal' => 'require|in:' . UserTerminalEnum::WECHAT_MMP . ',' . UserTerminalEnum::WECHAT_OA . ','
                      . UserTerminalEnum::H5 . ',' . UserTerminalEnum::PC . ',' . UserTerminalEnum::IOS .
                      ',' . UserTerminalEnum::ANDROID,
//        'scene' => 'require|in:' . LoginEnum::MNP_AUTH_LOGIN . '|checkConfig', TODO 暂时删除无效验证，待完善
        'account' => 'require',
        'token' =>'require|checkToken',
        'auth_key' => 'require'
    ];


    protected $message = [
        'terminal.require' => '终端参数缺失',
        'terminal.in' => '终端参数状态值不正确',
        'scene.require' => '场景不能为空',
        'scene.in' => '场景值错误',
        'account.require' => '请输入账号',
        'token.require' => 'token不能为空',
        'auth_key.require' => 'auth_key不能为空'
    ];


    /**
     * @notes 登录场景相关校验
     * @param $scene
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2022/9/15 14:37
     */
    public function checkConfig($scene, $rule, $data)
    {
        $config = ConfigService::get('login', 'login_way');
        if (!in_array($scene, $config)) {
            return '不支持的登录方式';
        }

        return true;
    }

    public function checkToken($token)
    {
        $where = [
            'token' => $token,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ];
        $userSession = UserSession::where($where)->findOrEmpty();
        if ($userSession->isEmpty()) {
            return 'token验证失败';
        }
        return true;
    }



}
