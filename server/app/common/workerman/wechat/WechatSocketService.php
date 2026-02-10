<?php

declare(strict_types=1);

namespace app\common\workerman\wechat;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use app\common\workerman\wechat\services\Service;
use app\common\workerman\wechat\constants\ResponseCode;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\SocketType;
use app\common\workerman\wechat\traits\{LoggerTrait, CacheTrait};

/**
 * WebSocket连接处理器
 * 
 * 处理WebSocket连接生命周期:
 * - 连接建立
 * - 消息处理
 * - 连接关闭
 * - 错误处理
 * 
 * @author Qasim
 * @package app\process
 */
class WechatSocketService
{
    use LoggerTrait, CacheTrait;
    /** @var Service 服务容器 */
    private Service $service;

    /**
     * 处理Worker启动
     * 
     * @param Worker $worker Worker实例
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 初始化服务
        $this->service = Service::getInstance();

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Client WebSocket worker started')->withContext([
            //'pid' => posix_getpid()
            'pid' => getmypid()
        ])->log();
    }

    /**
     * 处理连接建立
     * 
     * @param TcpConnection $connection 连接实例
     * @return void
     */
    public function onConnect(TcpConnection $connection): void
    {
        // 设置连接属性
        $connection->maxSendBufferSize = 1024 * 1024;
        $connection->maxPackageSize = 1024 * 1024;
        $connection->lastActiveTime = time();

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Client connected')->withContext([
            'connectionId' => $connection->id,
            'remoteAddress' => $connection->getRemoteAddress()
        ])->log();
    }

    /**
     * 处理消息
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $data 数据
     * @return void
     */
    public function onMessage(TcpConnection $connection, string $data): void
    {
        // 更新活动时间
        $connection->lastActiveTime = time();

        $message = [];
            
        try {
            
            // 解析JSON消息
            $message = json_decode($data, true);
            $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Client WebSocket msg')->withContext([
                'data' => $data,
                'Message' => $message
            ])->log();
            // 处理消息
            $this->service->messageService->handleClientMessage($connection, $message);
        } catch (ResponseException $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Client WebSocket error')->withContext([
                'connectionId' => $connection->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'info' => $e->__toString()
            ])->log();
            $connection->send(json_encode([
                'Code' => $e->getCode(),
                'Message' => $e->getMessage(),
                'Data' => [
                    'MsgType' => $message['MsgType'] ?? '',
                    'Content' => $message['Content'] ?? []
                ]
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Client WebSocket error')->withContext([
                'connectionId' => $connection->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'info' => $e->__toString()
            ])->log();

            $connection->send(json_encode([
                'Code' => ResponseCode::SYSTEM_ERROR,
                'Message' => '系统错误',
                'Data' => [
                    'MsgType' => $message['MsgType'] ?? '',
                    'Content' => $message['Content'] ?? []
                ]
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 处理连接关闭
     * 
     * @param TcpConnection $connection 连接实例
     * @return void
     */
    public function onClose(TcpConnection $connection): void
    {
        // 移除客户端连接
        $this->service->connectionService->removeConnection($connection, SocketType::WEBSOCKET);

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Client disconnected')->withContext([
            'connectionId' => $connection->id,
        ])->log();
    }

    /**
     * 处理错误
     * 
     * @param TcpConnection $connection 连接实例
     * @param \Throwable $e 异常实例
     * @return void
     */
    public function onError(TcpConnection $connection, int $code, string $msg): void
    {
        $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Client WebSocket error')->withContext([
            'connectionId' => $connection->id,
            'error' => $msg,
            'code' => $code,
        ])->log();

        // 关闭异常连接
        if ($connection->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
            $connection->close();
        }
    }
}
