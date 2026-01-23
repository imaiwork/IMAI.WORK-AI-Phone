<?php

declare(strict_types=1);

namespace app\adminapi\http\middleware;


use app\common\service\JsonService;

/**
 * 校验演示环境
 * Class CheckDemoMiddleware
 * @package app\adminapi\http\middleware
 */
class CheckMiddleware
{

    // 允许post的接口
    protected $ablePost = [
        'login/account',
        'login/logout',
    ];

    public function handle($request, \Closure $next)
    {
        if ($request->method() != 'POST') {
            return $next($request);
        }

        $accessUri = strtolower($request->controller() . '/' . $request->action());
        if (!in_array($accessUri, $this->ablePost) && env('project.demo_env')) {
            return JsonService::fail('演示环境不支持修改数据，请下载源码本地部署体验');
        }

        return $next($request);
    }
}
