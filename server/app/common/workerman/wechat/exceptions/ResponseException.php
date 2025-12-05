<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\exceptions;

use app\common\workerman\wechat\constants\ResponseCode;
use RuntimeException;

/**
 * 响应异常类
 */
class ResponseException extends RuntimeException
{

    /**
     * @param int $code 错误码
     * @param string|null $message 错误信息(可选)
     */
    public function __construct(int $code, ?string $message = null)
    {
        $message = $message ?: ResponseCode::getMessage($code);

        parent::__construct($message, $code);
    }
}
