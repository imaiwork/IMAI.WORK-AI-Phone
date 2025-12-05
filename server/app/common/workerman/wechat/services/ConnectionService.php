<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

use Workerman\Connection\TcpConnection;
use Channel\Client;
use app\common\workerman\wechat\traits\{LoggerTrait, CacheTrait, DeviceTrait};
use app\common\workerman\wechat\constants\SocketType;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 连接管理服务
 * 
 * 管理连接和消息转发:
 * - 绑定设备连接
 * - 移除设备连接
 * - 进程间通信
 * 
 * @author Qasim
 * @package app\service
 */
class ConnectionService
{
    use LoggerTrait, CacheTrait, DeviceTrait;
    /** @var string 进程类型 */
    private string $processType = '';
    /**
     * 连接到设备的映射
     * @var array ['connectionId' => ['deviceId1', 'deviceId2']]
     */
    private array $connectionDevices = [];

    /**
     * 进程类型
     */
    const TYPE_SOCKET = 'socket';
    const TYPE_WEBSOCKET = 'websocket';

    public function __construct() {}

    /**
     * 添加连接
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $deviceId 设备ID
     * @return void
     */
    public function bindConnection(TcpConnection $connection, string $deviceId, string $type = SocketType::SOCKET): void
    {

        if (!isset($this->connectionDevices[$connection->id])) {
            $this->connectionDevices[$connection->id] = [];
        }

        // 添加设备ID到连接的映射列表
        if (!$this->hasDevice($connection, $deviceId)) {
            $this->connectionDevices[$connection->id][] = $deviceId;
        }

        // 注册Channel监听
        $this->registerChannelListener($connection, $deviceId, $type);

        // 如果是Socket进程，则更新设备状态
        if ($type === SocketType::SOCKET) {
            $this->updateDeviceStatus($deviceId, true);
        }

        // 添加到连接缓存
        $this->redis()->sAdd($this->getConnectionKey($type), $deviceId);

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Connection bound')->withContext([
            'device_id' => $deviceId,
            'connection_id' => $connection->id,
        ])->log();
    }

    /**
     * 获取连接绑定的所有设备ID
     * 
     * @param TcpConnection $connection 连接实例    
     * @return array
     */
    public function getDeviceIds(TcpConnection $connection): array
    {
        return $this->connectionDevices[$connection->id] ?? [];
    }

    /**
     * 获取连接绑定的所有设备ID
     * 
     * @param TcpConnection $connection 连接实例    
     * @return string
     */
    public function getDeviceId(TcpConnection $connection): string
    {
        return $this->connectionDevices[$connection->id][0] ?? '';
    }

    /**
     * 检查连接是否绑定了指定设备
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $deviceId 设备ID
     * @return bool
     */
    public function hasDevice(TcpConnection $connection, string $deviceId): bool
    {
        return isset($this->connectionDevices[$connection->id]) &&
            in_array($deviceId, $this->connectionDevices[$connection->id]);
    }

    /**
     * 解绑连接
     * 
     * @param TcpConnection $connection 连接实例
     * @return void
     */
    public function unbindConnection(TcpConnection $connection): void
    {
        if (isset($this->connectionDevices[$connection->id])) {
            unset($this->connectionDevices[$connection->id]);
        }
    }


    /**
     * 移除连接
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $type 连接类型
     * @return void
     */
    public function removeConnection(TcpConnection $connection, string $type = SocketType::SOCKET): void
    {
        // 获取当前连接所有设备
        foreach ($this->getDeviceIds($connection) as $deviceId) {

            // 清理Token缓存
            if ($type === SocketType::SOCKET) {
                $this->redis()->del($this->getDeviceKey($deviceId, 'token'));

                // 更新设备状态 - 离线
                $this->updateDeviceStatus($deviceId, false);
            } else {
                $this->redis()->del($this->getClientKey($deviceId, 'token'));
            }
        }

        // 解绑连接
        $this->unbindConnection($connection);

        // 删除连接缓存
        $this->redis()->del($this->getConnectionKey($type));

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Device connection removed')->withContext([
            'connection_id' => $connection->id
        ])->log();
    }

        /**
     * 检查设备是否在线
     * 
     * @param string $deviceId 设备ID
     * @return bool
     */
    public function isDeviceOnline(string $deviceId): bool
    {
        // 先检查本地连接
        if (isset($this->deviceConns[$deviceId])) {
            return true;
        }

        // 再检查Redis中的状态
        $statusKey = $this->redis()->getDeviceKey($deviceId, 'status');
        $status = $this->redis()->get($statusKey);

        return $status === 'online';
    }

    /**
     * 注册Channel监听
     * 
     * @param TcpConnection $connection 连接实例
     * @return void
     */
    private function registerChannelListener(TcpConnection $connection, string $deviceId, string $type = SocketType::SOCKET): void
    {
        Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));

        // 注册进程消息监听
        Client::on("{$type}.{$deviceId}.message", function ($data) use ($connection, $type) {
            $message = $data['data'];

            if ($type === SocketType::SOCKET) {
                $message = pack('N', strlen($message)) . $message;
            }
            $connection->send($message);
        });

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Channel listener registered')->withContext([
            'device_id' => $deviceId,
        ])->log();
    }

    

    /**
     * 通过Channel发送跨进程消息
     * 
     * @param string $deviceId 设备ID
     * @param mixed $data 消息数据
     * @return void
     */
    public function sendChannelMessage(string $deviceId, mixed $data): void
    {
        if (empty($deviceId) || empty($data)) {
            return;
        }

        // 确定目标进程类型
        $targetProcess = $this->processType === self::TYPE_SOCKET ? self::TYPE_WEBSOCKET : self::TYPE_SOCKET;

        // 通过Channel转发
        try {
            $channel = "{$targetProcess}.{$deviceId}.message";

            Client::publish($channel, [
                'data' => is_array($data) ? json_encode($data) : $data
            ]);

            $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Message published')->withContext([
                'deviceId' => $deviceId,
                'channel' => $channel,
                'type' => $targetProcess
            ])->log();
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Publish error')->withContext([
                'error' => $e->getMessage(),
                'deviceId' => $deviceId,
                'type' => $targetProcess
            ])->log();
        }
    }
}
