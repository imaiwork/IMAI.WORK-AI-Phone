<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\traits\{CacheTrait, LoggerTrait, DeviceTrait, ResponseTrait};
use Google\Protobuf\Internal\Message;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 基础处理器
 * 
 * 
 * @author Qasim
 * @package app\handlers\client
 */
class BaseHandler
{
    use LoggerTrait, DeviceTrait, ResponseTrait, CacheTrait;

    protected const TOKEN_TTL = 3600;

    /**
     * 构建JSON响应
     * 
     * @param string $msgType 消息类型
     * @param array $content 内容
     * @return array
     */
    protected function buildJsonResponse(string $msgType, array $content): array
    {
        return $this->withMsgType($msgType)->withContent($content)->response();
    }

    /**
     * 构建Protobuf响应
     * 
     * @param int $msgType 消息类型
     * @param Message $content 内容
     * @return array
     */
    protected function buildProtobufResponse(int $msgType, Message $content): array
    {
        return $this->withMsgType($msgType)->withContent($content)->build();
    }

    /**
     * 静态调用转实例调用
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = new static();
        return $instance->$name(...$arguments);
    }

    /**
     * 实例方法调用
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        throw new ResponseException(ResponseCode::HANDLER_METHOD_NOT_FOUND);
    }
}
