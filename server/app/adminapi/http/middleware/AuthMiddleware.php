<?php

declare(strict_types=1);

namespace app\adminapi\http\middleware;

use app\common\{
    cache\AdminAuthCache,
    service\JsonService
};
use think\helper\Str;

/**
 * 权限验证中间件
 * Class AuthMiddleware
 * @package app\adminapi\http\middleware
 */
class AuthMiddleware
{
    /**
     * @notes 权限验证
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @author 令狐冲
     * @date 2021/7/2 19:29
     */
    public function handle($request, \Closure $next)
    {
        //不登录访问，无需权限验证
        if ($request->controllerObject->isNotNeedLogin()) {
            return $next($request);
        }

        //系统默认超级管理员，无需权限验证
        if (1 === $request->adminInfo['root']) {
            return $next($request);
        }

        $adminAuthCache = new AdminAuthCache($request->adminInfo['admin_id']);

        // 当前访问路径
        $accessUri = strtolower($request->controller() . '/' . $request->action());
        // 全部路由
        $allUri = $this->formatUrl($adminAuthCache->getAllUri());

        // 判断该当前访问的uri是否存在，不存在无需验证
        if (!in_array($accessUri, $allUri)) {
            return $next($request);
        }

        // 当前管理员拥有的路由权限
        $AdminUris = $adminAuthCache->getAdminUri() ?? [];
        $AdminUris = $this->formatUrl($AdminUris);

        if (in_array($accessUri, $AdminUris)) {
            return $next($request);
        }
        return JsonService::fail('权限不足，无法访问或操作');
    }


    /**
     * @notes 格式化URL
     * @param array $data
     * @return array|string[]
     * @author 段誉
     * @date 2022/7/7 15:39
     */
    public function formatUrl(array $data)
    {
        return array_map(function ($item) {
            return strtolower(Str::camel($item));
        }, $data);
    }
}
