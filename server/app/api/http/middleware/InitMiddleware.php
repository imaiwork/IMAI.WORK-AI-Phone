<?php

declare(strict_types=1);

namespace app\api\http\middleware;


use app\common\exception\ControllerExtendException;
use app\api\controller\BaseApiController;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;


class InitMiddleware
{

    /**
     * @notes 初始化
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws ControllerExtendException
     * @author 段誉
     * @date 2022/9/6 18:17
     */
    public function handle($request, \Closure $next)
    {
        //获取控制器
        try {
            $controller = str_replace('.', '\\', $request->controller());
            $controller = '\\app\\api\\controller\\' . $controller . 'Controller';
            $controllerClass = invoke($controller);
            if (($controllerClass instanceof BaseApiController) === false) {
                throw new ControllerExtendException($controller, '404');
            }
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }
        //创建控制器对象
        $request->controllerObject = invoke($controller);

        return $next($request);
    }
}
