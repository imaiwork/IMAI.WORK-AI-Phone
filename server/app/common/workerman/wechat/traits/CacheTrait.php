<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;

use Predis\Client as RedisClient;
use think\cache\driver\Redis;
/**
 * 缓存操作能力
 * 
 * @author Qasim
 * @package app\traits
 */
trait CacheTrait
{
    /**
     * 缓存键前缀
     */
    private static  $PREFIX = [
        'DEVICE' => 'device',      // 设备相关
        'WECHAT' => 'wechat',      // 微信相关
        'CLIENT' => 'client',      // 客户端相关
        'CONNECTION' => 'connection', // 连接相关
    ];

    /**
     * Redis连接实例（单例）
     */
    private static $redisInstance = null;
    /**
     * 获取Redis实例
     * 
     * @return Redis|Connection
     */
    protected function redis()
    {
        if (self::$redisInstance === null) {
            self::$redisInstance = new Redis([
                'host'        => env('redis.HOST', '127.0.0.1'),
                'port'        => env('redis.PORT', 6379),
                'password'    => env('redis.PASSWORD', '123456'),
                'select'      => env('redis.WX_SELECT', 9),
                'timeout'     => 0,
                'persistent'  => true,
            ]);
        }

        return self::$redisInstance;
    }

    /**
     * 获取设备缓存键
     * 
     * @param string $deviceId 设备ID
     * @param string $type 类型
     * @return string
     */
    protected function getDeviceKey(string $deviceId, string $type): string
    {
        return sprintf('%s:%s:%s', self::$PREFIX['DEVICE'], $deviceId, $type);
    }

    /**
     * 获取客户端缓存键
     * 
     * @param string $deviceId 设备ID
     * @param string $type 类型
     * @return string
     */
    protected function getClientKey(string $deviceId, string $type): string
    {
        return sprintf('%s:%s:%s', self::$PREFIX['CLIENT'], $deviceId, $type);
    }

    /**
     * 获取微信缓存键
     * 
     * @param string $deviceId 设备ID
     * @param string $type 类型
     * @return string
     */
    protected function getWechatKey(string $deviceId, string $type): string
    {
        return sprintf('%s:%s:%s', self::$PREFIX['WECHAT'], $deviceId, $type);
    }

    /**
     * 获取连接缓存键
     * 
     * @param string $type 类型
     * @return string
     */
    protected function getConnectionKey(string $type): string
    {
        return sprintf('%s:%s', self::$PREFIX['CONNECTION'], $type);
    }
}
