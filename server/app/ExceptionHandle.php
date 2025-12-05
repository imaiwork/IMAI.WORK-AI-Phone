<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Log;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        if ($e instanceof HttpResponseException) {
            // 记录message方法的参数
            $data = $e->getResponse()->getData();
            $is_log = $data['is_log'] ?? 0;
            if ($is_log == 1)
            {
                Log::write($request->request(), 'system');
                Log::write($data['msg'], 'system');
                Log::write($e->getLine(), 'system');
            }
        } else {
            // 语法错误
            Log::write($e->__toString(), 'system');
            $data['code'] = 0;
            $data['msg'] = $e->getMessage();
            $data['show'] = 0;
            return json($data);
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
