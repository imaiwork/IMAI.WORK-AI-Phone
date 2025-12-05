<?php


declare(strict_types=1);

namespace app\common\http\middleware;

/**
 * 基础中间件
 * Class Middleware
 * @package app\common\http\middleware
 */
class BaseMiddleware
{
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }
}
