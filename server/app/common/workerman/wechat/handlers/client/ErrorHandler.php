<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;


/**
 * 错误处理
 * 
 * @method static array handle(int $code, string $message, string $msgType) 业务处理
 * @author Qasim
 * @package app\handler\client
 */
class ErrorHandler extends BaseHandler
{

    /**
     * 错误处理
     * 
     * @param int $code 错误码
     * @param string $message 错误消息
     * @param string $msgType 消息类型
     * @param string $content 消息内容
     * @return array
     */
    protected function handle(int $code, string $message, string $msgType, array $content = []): array
    {
        // 返回响应体
        return $this->withMsgType($msgType)
            ->withCode($code)
            ->withContent($content)
            ->withMessage($message)
            ->response();
    }
}
