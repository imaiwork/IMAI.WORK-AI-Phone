<?php

declare(strict_types=1);

namespace app\common\workerman\wechat;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\services\Service;
use app\common\workerman\wechat\constants\SocketType;
use app\common\workerman\wechat\traits\{LoggerTrait};


/**
 * Socket连接处理器
 * 
 * 处理Socket连接生命周期:
 * - 连接建立
 * - 消息处理
 * - 连接关闭
 * - 错误处理
 * 
 * @author Qasim
 * @package app\process
 */
class DeviceSocketService
{
    use LoggerTrait;
    /** @var array 连接缓冲区 [connId => string] */
    private static $buffers = [];

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

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Device Socket worker started')->withContext([
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

        // 初始化缓冲区
        self::$buffers[$connection->id] = '';

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Device connected')->withContext([
            'connectionId' => $connection->id
        ])->log();
    }

    /**
     * 处理消息
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $data 数据
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data): void
    {
        try {
            $this->handleMessage($connection, $data);
        } catch (ResponseException $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Device Socket error')->withContext([
                'connectionId' => $connection->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ])->log();
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Device Socket error')->withContext([
                'connectionId' => $connection->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ])->log();
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
        // 清理缓冲区
        unset(self::$buffers[$connection->id]);

        $this->service->connectionService->removeConnection($connection, SocketType::SOCKET);

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Device disconnected')->withContext([
            'connectionId' => $connection->id
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
        unset(self::$buffers[$connection->id]);

        $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Device WebSocket error')->withContext([
            'connectionId' => $connection->id,
            'error' => $msg,
            'code' => $code,
        ])->log();

        // 关闭连接
        $connection->close();
    }

    /**
     * 处理消息
     * 
     * @param TcpConnection $connection 连接实例    
     * @param string $data 数据
     * @return void
     */
    private function handleMessage(TcpConnection $connection, string $data): void
    {
        // 累积数据到缓冲区
        self::$buffers[$connection->id] .= $data;
        // 处理缓冲区数据
        while (strlen(self::$buffers[$connection->id]) >= 4) {
            // 解析消息长度
            $unpackData = unpack('N', self::$buffers[$connection->id]);
            $length = $unpackData[1];

            // 消息不完整，等待更多数据
            if (strlen(self::$buffers[$connection->id]) < $length + 4) {
                break;
            }

            // 提取完整消息
            $message = substr(self::$buffers[$connection->id], 4, $length);
            // 更新缓冲区，移除已处理的数据
            self::$buffers[$connection->id] = substr(self::$buffers[$connection->id], $length + 4);

            // 处理消息
            $this->service->messageService->handleDeviceMessage($connection, $message);
        }
    }
}
