<?php



namespace app\api\logic\service;

use app\common\cache\UserTokenCache;
use app\common\model\user\UserSession;
use think\facade\Config;

class UserTokenService
{

    /**
     * @notes 设置或更新用户token
     * @param $userId
     * @param $terminal
     * @return array|false|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 10:10
     */
    public static function setToken($userId, $terminal, $authKey='')
    {
        $time = time();
        $userSession = UserSession::where([['user_id', '=', $userId], ['terminal', '=', $terminal]])->find();

        //获取token延长过期的时间
        $expireTime = $time + Config::get('project.user_token.expire_duration');
        $userTokenCache = new UserTokenCache();

        //token处理
        if ($userSession) {
            //清空缓存
            $userTokenCache->deleteUserInfo($userSession->token);
            //重新获取token
            $userSession->token = create_token($userId);
            $userSession->expire_time = $expireTime;
            $userSession->update_time = $time;
            $userSession->auth_key = $authKey;
            $userSession->save();
        } else {
            //找不到在该终端的token记录，创建token记录
            $userSession = UserSession::create([
                'user_id' => $userId,
                'terminal' => $terminal,
                'token' => create_token($userId),
                'auth_key' => $authKey,
                'expire_time' => $expireTime
            ]);
        }

        return $userTokenCache->setUserInfo($userSession->token);
    }


    /**
     * @notes 延长token过期时间
     * @param $token
     * @return array|false|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 10:10
     */
    public static function overtimeToken($token)
    {
        $time = time();
        $userSession = UserSession::where('token', '=', $token)->findOrEmpty();
        if ($userSession->isEmpty()) {
            return false;
        }
        //延长token过期时间
        $userSession->expire_time = $time + Config::get('project.user_token.expire_duration');
        $userSession->update_time = $time;
        $userSession->save();

        return (new UserTokenCache())->setUserInfo($userSession->token);
    }


    /**
     * @notes 设置token为过期
     * @param $token
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 10:10
     */
    public static function expireToken($token)
    {
        $userSession = UserSession::where('token', '=', $token)
            ->find();
        if (empty($userSession)) {
            return false;
        }

        $time = time();
        $userSession->expire_time = $time;
        $userSession->update_time = $time;
        $userSession->save();

        return (new  UserTokenCache())->deleteUserInfo($token);
    }
}
