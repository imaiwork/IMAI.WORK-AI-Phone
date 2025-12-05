<?php

declare(strict_types=1);

namespace app\api\http\middleware;


use app\api\logic\ApiLogic;
use app\api\logic\service\UserTokenService;
use app\common\cache\UserTokenCache;
use app\common\service\JsonService;
use think\facade\Config;

class LoginMiddleware
{
    /**
     * @notes 登录验证
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Json
     * @author 令狐冲
     * @date 2021/7/1 17:33
     */
    public function handle($request, \Closure $next)
    {
        $token = $request->header('token');
        //判断接口是否免登录
        $isNotNeedLogin = $request->controllerObject->isNotNeedLogin();

        //不直接判断$isNotNeedLogin结果，使不需要登录的接口通过，为了兼容某些接口可以登录或不登录访问
        if (empty($token) && !$isNotNeedLogin) {
            //没有token并且该地址需要登录才能访问, 指定show为0，前端不弹出此报错
            return JsonService::fail('请求参数缺token', [], 0, 0);
        }

        $userInfo = (new UserTokenCache())->getUserInfo($token);

        if (empty($userInfo) && !$isNotNeedLogin) {
            //token过期无效并且该地址需要登录才能访问
            return JsonService::fail('登录超时，请重新登录', [], -1, 0);
        }

        //token临近过期，自动续期
        if ($userInfo) {
            //获取临近过期自动续期时长
            $beExpireDuration = Config::get('project.user_token.be_expire_duration');
            //token续期
            if (time() > ($userInfo['expire_time'] - $beExpireDuration)) {
                $result = UserTokenService::overtimeToken($token);
                //续期失败（数据表被删除导致）
                if (empty($result)) {
                    return JsonService::fail('登录过期', [], -1);
                }
            }
        }

        //给request赋值，用于控制器
        $request->userInfo = $userInfo;
        $request->userId = $userInfo['user_id'] ?? 0;
        ApiLogic::$uid = $request->userId;

        // 记录用户活跃
        if (in_array($request->pathinfo(), ['user/center'])) {
            // 当日已存在的不记录
            $userActiveLog = \app\common\model\user\UserActiveLog::where('user_id', $userInfo['user_id'])->whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])->findOrEmpty();
            if ($userActiveLog->isEmpty()) {
                \app\common\model\user\UserActiveLog::create([
                    'user_id' => $userInfo['user_id'],
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            } else {
                $userActiveLog->update_time = time();
                $userActiveLog->save();
            }
        }
        return $next($request);
    }
}
