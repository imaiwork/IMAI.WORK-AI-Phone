<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

use Channel\Client as ChannelClient;
use think\facade\Log;

class ChannelBus
{
    private bool $connected = false;
    private array $subscriptions = [];

    public function connect(): void
    {
        $this->installOrphanEventFallback();

        if ($this->connected) {
            return;
        }

        ChannelClient::connect(
            env('WORKERMAN.CHANNEL_HOST', '127.0.0.1'),
            (int)env('WORKERMAN.CHANNEL_PROT', 2206)
        );
        $this->connected = true;
    }

    private function installOrphanEventFallback(): void
    {
        if (is_callable(ChannelClient::$onMessage)) {
            return;
        }

        ChannelClient::$onMessage = static function ($event, $eventData): void {
            try {
                Log::channel('socket')->write(json_encode([
                    'msg' => 'orphan channel event skipped',
                    'event' => (string)$event,
                    'data_type' => gettype($eventData),
                    'time' => date('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'channel');
            } catch (\Throwable) {
            }
        };
    }

    public function subscribe(string $channel, callable $handler): void
    {
        $this->connect();
        ChannelClient::on($channel, $handler);
        $this->subscriptions[$channel] = true;
        \think\facade\Log::channel('socket')->write("subscribe: {$channel}", 'channel');
    }

    public function unsubscribe(string $channel): void
    {
        if (!isset($this->subscriptions[$channel])) {
            \think\facade\Log::channel('socket')->write("未订阅的通道: {$channel}", 'channel');
            return;
        }
        $this->connect();
        ChannelClient::unsubscribe($channel);
        unset($this->subscriptions[$channel]);
        \think\facade\Log::channel('socket')->write("unsubscribe: {$channel}", 'channel');
    }

    public function publish(string $channel, array $message): void
    {
        $this->connect();
        ChannelClient::publish($channel, $message);
    }

    public function workerChannel(int $workerId): string
    {
        return "rpa.worker.{$workerId}.send";
    }
}
