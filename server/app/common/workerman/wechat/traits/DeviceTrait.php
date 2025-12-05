<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatDevice;
use think\facade\Cache;
/**
 * 设备能力
 * - 检查设备合法性
 * - 获取设备信息
 * - 更新设备状态
 * - 设备在线状态
 * - 更新心跳时间
 * 
 * @author Qasim
 * @package app\traits
 */
trait DeviceTrait
{
    use LoggerTrait, CacheTrait;

    protected static $CLIENT_TOKEN_TTL = 3600;
    protected static $DEVICE_TOKEN_TTL = 86400;

    /**
     * 检查设备
     * 
     * @param string $deviceId 设备ID
     * @return bool
     */
    protected function checkDevice(string $deviceId): bool
    {
        // 获取设备信息    
        $cache = Cache::get('DeviceInfoCache_'.$deviceId, false);
        if($cache) {
            return (bool)$cache;
        }

        $deviceInfo = $this->getDeviceInfo($deviceId);
        if($deviceInfo) {
            Cache::set('DeviceInfoCache_'.$deviceId, ($deviceInfo ? true : false), 86400);
        }
        return $deviceInfo ? true : false;
    }

    /**
     * 获取设备信息
     * 
     * @param string $deviceId 设备ID
     * @return array
     */
    protected function getDeviceInfo(string $deviceId): array
    {
        try {
            $result = \app\common\service\ToolsService::Auth()->checkDevice($deviceId);
            if ((int)$result['code'] === 10000) {
                return $result['data'];
            } else {
                return [];
            }
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('updateDevices')->withContext([
                'deviceId' => $deviceId,
                'trace' => $e->getTraceAsString(),
            ])->log();
            return [];
        }
    }

    /**
     * 更新设备池
     * 
     * @param array $device 设备池
     * @return void
     */
    protected function updateDevices(array $device): void
    {
        try {
            $body = \app\common\service\ToolsService::Auth()->deviceUpdate($device);
            $this->withChannel('wechat_socket')->withLevel('info')->withTitle('updateDevices')->withContext([
                'deviceInfo' => $body
            ])->log();
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('updateDevices')->withContext([
                'deviceInfo' => $device,
                'trace' => $e->getTraceAsString(),
            ])->log();
        }
    }

    /**
     * 检查设备是否在线
     * 
     * @param string $deviceId 设备ID
     * @return bool
     */
    protected function isDeviceOnline(string $deviceId): bool
    {
        $statusKey = $this->getDeviceKey($deviceId, 'status');
        $status = $this->redis()->get($statusKey);
        return $status === 'online';
    }

    /**
     * 更新设备状态
     * 
     * @param string $deviceId 设备ID
     * @param bool $online 是否在线
     * @return void
     */
    protected function updateDeviceStatus(string $deviceId, bool $online): void
    {
        $this->redis()->set(
            $this->getDeviceKey($deviceId, 'status'),
            $online ? 'online' : 'offline'
        );
        $status = 0;
        if(!$online){
            $status = 0;
            $this->withChannel('wechat_socket')->withLevel('info')->withTitle('updateDeviceStatus offile')->withContext([
                'device' => $deviceId
            ])->log();
        }
        if ($online) {
            $status = 1;
            $this->updateDeviceHeartbeat($deviceId);
        }

        AiWechatDevice::where('device_code', $deviceId)->update(['device_status' => $status, 'update_time' => time()]);
        AiWechat::where('device_code', $deviceId)->update(['wechat_status' => $status, 'update_time' => time()]);
    }

    /**
     * 更新设备心跳时间
     * 
     * @param string $deviceId 设备ID
     * @return void
     */
    protected function updateDeviceHeartbeat(string $deviceId): void
    {
        $this->redis()->set($this->getDeviceKey($deviceId, 'heartbeat'),time());
        
    }

    /**
     * 验证Token
     * 
     * @param string $deviceId 设备ID
     * @param string $token Token
     * @param string $type 类型 device: 设备 token: 客户端
     * @return bool
     */
    protected function verifyToken(string $deviceId, string $token, string $type = 'device'): bool
    {
        if ($type === 'device') {
            $tokenKey = $this->getDeviceKey($deviceId, 'token');
            $tokenValue = $this->redis()->get($tokenKey);
        } else {
            $tokenKey = $this->getClientKey($deviceId, 'token');
            $tokenValue = $this->redis()->get(trim($tokenKey));
        }

        $this->withChannel('wechat_socket')->withLevel('info')->withTitle('verifyToken')->withContext([
            'type' => $type,
            'device' => $deviceId,
            'exists' => $this->redis()->exists($tokenKey),
            'tokenKey' => $tokenKey,
            'Token' => $token,
            'tokenValue' => $tokenValue
        ])->log();

        return $token === $tokenValue;
    }

    /**
     * 设置Token
     * 
     * @param string $deviceId 设备ID
     * @param string $token Token
     * @param string $type 类型 device: 设备 token: 客户端
     * @return string
     */
    protected function setToken(string $deviceId, string $type = 'device'): string
    {
        if ($type === 'device') {
            $tokenKey = $this->getDeviceKey($deviceId, 'token');
        } else {
            $tokenKey = $this->getClientKey($deviceId, 'token');
        }

        // 生成并缓存Token
        $token = hash('sha256', $deviceId . $type . time() . uniqid());
        $this->redis()->setex($tokenKey, $type === 'device' ? self::$DEVICE_TOKEN_TTL : self::$CLIENT_TOKEN_TTL, $token);

        return $token;
    }
}
