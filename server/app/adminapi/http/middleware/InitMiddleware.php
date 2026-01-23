<?php

declare(strict_types=1);

namespace app\adminapi\http\middleware;

use app\adminapi\controller\BaseAdminController;
use app\common\cache\AdminTokenCache;
use app\common\exception\ControllerExtendException;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;

/**
 * 初始化验证中间件
 * Class InitMiddleware
 * @package app\adminapi\http\middleware
 */
class InitMiddleware
{
    /**
     * @notes 初始化
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws ControllerExtendException
     * @author 令狐冲
     * @date 2021/7/2 19:29
     */
    public function handle($request, \Closure $next)
    {
        //获取控制器
        try {
            $controller = str_replace('.', '\\', $request->controller());
            $controller = '\\app\\adminapi\\controller\\' . $controller . 'Controller';
            $controllerClass = invoke($controller);
            if (($controllerClass instanceof BaseAdminController) === false) {
                throw new ControllerExtendException($controller, '404');
            }
        } catch (ClassNotFoundException $e) {

            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        //创建控制器对象
        $request->controllerObject = invoke($controller);

        $response =  $next($request);

        //追加内容
        $currentContent = $response->getContent();

        $currentContent = json_decode($currentContent, true);

        //获取当前请求
        if (in_array($request->pathinfo(), ['config/getConfig', 'login/account'])) {

            if ($currentContent['code'] == 1) {

                //检查授权
                try {

                    $msg = \app\common\service\ToolsService::Auth()->check();
                } catch (\Exception $e) {

                    $msg = "请求异常";
                }

                $currentContent['data']['is_auth'] = $msg;

                //检查授权
                try {

                    $msg = \app\common\service\ToolsService::Auth()->checkby();
                } catch (\Exception $e) {

                    $msg = "请求异常";
                }

                $currentContent['data']['is_auth_by'] = $msg['bystatus'] ?? 0;
            }
        }

        $token = $request->header('token');

        $adminInfo = (new AdminTokenCache())->getAdminInfo($token);

        if (in_array($request->pathinfo(), ['config/getConfig', 'login/account']) && !$adminInfo) {

            $currentContent['data']['model_key']['api_key'] = "";
        }

        // 设置新的内容
        $response->content(json_encode($currentContent));

        return $response;
    }
}
