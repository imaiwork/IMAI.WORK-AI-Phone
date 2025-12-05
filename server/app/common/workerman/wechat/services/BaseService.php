<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

use Workerman\Connection\TcpConnection;
use think\facade\Log;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 基础服务类
 * 
 * 提供基础功能:
 * - 错误处理
 * - 日志记录
 * - 服务管理
 * 
 * @author Qasim
 * @package app\service
 */
class BaseService
{
    /**
     * 服务管理实例
     */
    protected Service $service;

    /**
     * 进程类型
     */
    const TYPE_SOCKET = 'socket';
    const TYPE_WEBSOCKET = 'websocket';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->service = Service::getInstance();
    }

    /**
     * 处理系统错误
     * 
     * @param TcpConnection $connection 连接实例    
     * @param \Throwable $e 异常实例
     * @param array $context 上下文
     * @return void
     */
    protected function handleSysError(TcpConnection $connection, \Throwable $e, array $context = []): void
    {
        $this->logError('System error', array_merge([
            'connectionId' => $connection->id,
            'deviceId' => $connection->deviceId ?? '',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], $context));
    }

    /**
     * 处理普通错误
     * 
     * @param \Throwable $e 异常实例
     * @param array $context 上下文
     * @return void
     */
    protected function handleNormalError(\Throwable $e, array $context = []): void
    {
        $this->logError('Normal error', array_merge([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], $context));
    }

    /**
     * 记录错误日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * 记录警告日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * 记录信息日志
     * 
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * 记录调试日志
     * 
     * @param string $message 消息
     * @param array $context 上下文 
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }


    /**
     * 组装响应
     * 
     * @param int $code 响应码
     * @param string $message 响应消息
     * @param string $msgType 消息类型
     * @param array $data 响应数据
     * @return array
     */
    protected function buildResponse(int $code = ResponseCode::SUCCESS, string $message = 'Success', string $msgType = '', array $data = []): array
    {

        // 组装响应
        $response = [
            "Code" => $code,
            "Message" => $message,
            "Data" => [
                "MsgType" => $msgType,
                "Content" => $data
            ]
        ];

        return $response;
    }
}
