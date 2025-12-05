<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ErrorMessage};

/**
 * 错误处理
 * 
 * @method static array handle(int $code, string $message) 业务处理
 * @author Qasim
 * @package app\handler\device
 */
class ErrorHandler extends BaseHandler
{

    /**
     * 错误处理
     * 
     * @param int $code 错误码
     * @param string $message 错误消息
     * @return array
     */
    protected function handle(int $code, string $message): array
    {
        $errorMessage = new ErrorMessage();
        $errorMessage->setErrorCode($code);
        $errorMessage->setErrorMsg($message);

        // 返回响应体
        return $this->buildProtobufResponse(EnumMsgType::Error, $errorMessage);
    }
}
