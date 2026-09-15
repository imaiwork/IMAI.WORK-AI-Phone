<?php

namespace app\common\queue;

use Exception;
use RedisException;
use think\queue\connector\Redis as BaseRedis;

/**
 * 队列 Redis 连接器（加固版）
 *
 * 线上事故：queue:work 常驻进程与 Redis 的连接断开后被 phpredis 静默重建，
 * 重建后的连接停在 db 0，而生产端按 redis.SELECT 往 db 9 投递，
 * worker 从此 lpop 到空、任务只进不出，且没有任何报错。
 *
 * 处理：
 * 1. 任何 RedisException 都重建客户端（原实现只认 "went away"），重建时会重新 SELECT。
 * 2. 每次 pop 前按固定间隔重新 SELECT 一次（O(1) 命令），静默重连也能在一个周期内纠正。
 */
class RedisConnector extends BaseRedis
{
    /** 重新 SELECT 的最小间隔（秒），匿名类中引用，需 public */
    public const RESELECT_INTERVAL = 60;

    public static function __make($config)
    {
        if (!extension_loaded('redis')) {
            throw new Exception('redis扩展未安装');
        }

        $redis = new class($config) {
            protected $config;
            protected $client;
            protected $lastSelectAt = 0;

            public function __construct($config)
            {
                $this->config = $config;
                $this->client = $this->createClient();
            }

            protected function createClient()
            {
                $config = $this->config;
                $func   = $config['persistent'] ? 'pconnect' : 'connect';

                $client = new \Redis;
                $client->$func($config['host'], $config['port'], $config['timeout']);

                if ('' != $config['password']) {
                    $client->auth($config['password']);
                }

                if (0 != $config['select']) {
                    $client->select($config['select']);
                }
                $this->lastSelectAt = time();
                return $client;
            }

            /**
             * 确保当前连接选中的库正确；连接已坏则重建。
             */
            public function ensureSelected(): void
            {
                if (0 == $this->config['select']) {
                    return;
                }
                if (time() - $this->lastSelectAt < RedisConnector::RESELECT_INTERVAL) {
                    return;
                }
                try {
                    $this->client->select($this->config['select']);
                    $this->lastSelectAt = time();
                } catch (RedisException $e) {
                    $this->client = $this->createClient();
                }
            }

            public function __call($name, $arguments)
            {
                try {
                    return call_user_func_array([$this->client, $name], $arguments);
                } catch (RedisException $e) {
                    // 连接层异常一律重建客户端，重建过程会重新 auth/select
                    $this->client = $this->createClient();
                    throw $e;
                }
            }
        };

        return new self($redis, $config['queue'], $config['retry_after'] ?? 60, $config['block_for'] ?? null);
    }

    public function pop($queue = null)
    {
        $this->redis->ensureSelected();
        return parent::pop($queue);
    }
}
