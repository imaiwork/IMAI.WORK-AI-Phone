<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;

use app\common\workerman\wechat\constants\ResponseCode;
use Google\Protobuf\Internal\Message;

/**
 * 响应特性
 * 
 * @author Qasim
 * @package app\trait
 */
trait ResponseTrait
{

    /**
     * 消息类型
     */
    protected string|int $msgType = '';

    /**
     * 消息内容
     */
    protected string $message = 'Success';

    /**
     * 内容
     */
    protected array|Message $content = [];

    /**
     * 状态码
     */
    protected int $code = ResponseCode::SUCCESS;

    /**
     * 设置消息类型
     * 
     * @param string|int $msgType 消息类型
     * @return static
     */
    protected function withMsgType(string|int $msgType): static
    {
        $this->msgType = $msgType;
        return $this;
    }

    /**
     * 设置内容
     * 
     * @param array|Message $content 内容
     * @return static
     */
    protected function withContent($content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置消息
     * 
     * @param string $message 消息
     * @return static
     */
    protected function withMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置状态码
     * 
     * @param int $code 状态码
     * @return static
     */
    protected function withCode(int $code): static
    {
        $this->code = $code;
        return $this;
    }


    /**
     * 构建protobuf响应
     * 
     * @return array
     */
    protected function build(): array
    {
        return [
            'MsgType' => $this->msgType,
            'Content' => $this->content
        ];
    }

    /**
     * 构建正常响应
     * 
     * @return array
     */
    protected function response(): array
    {
        return [
            'Code' => $this->code,
            'Message' => $this->message ?: ResponseCode::getMessage($this->code),
            'Data' => [
                'MsgType' => $this->msgType,
                'Content' => $this->content
            ]
        ];
    }
}
