<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

use app\common\service\log\ThinkFileLogDir;
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
            self::writeSocketLog(json_encode([
                'msg' => 'orphan channel event skipped',
                'event' => (string)$event,
                'data_type' => gettype($eventData),
                'time' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };
    }

    public function subscribe(string $channel, callable $handler): void
    {
        $this->connect();
        ChannelClient::on($channel, $handler);
        $this->subscriptions[$channel] = true;
        self::writeSocketLog("subscribe: {$channel}");
    }

    public function unsubscribe(string $channel): void
    {
        if (!isset($this->subscriptions[$channel])) {
            self::writeSocketLog("未订阅的通道: {$channel}");
            return;
        }
        $this->connect();
        ChannelClient::unsubscribe($channel);
        unset($this->subscriptions[$channel]);
        self::writeSocketLog("unsubscribe: {$channel}");
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

    private static int $socketLogFailCount = 0;
    private static int $socketLogFailThreshold = 3;
    private static int $socketLogFdThreshold = 3000;
    private static int $socketLogFdRecoverThreshold = 2500;
    private static int $socketLogFdCheckInterval = 180;
    private static int $socketLogMinDisableDuration = 60;
    private static int $socketLogDisabledAt = 0;
    private static int $socketLogLastFdCheckAt = 0;
    private static bool $socketLogFdLimitReached = false;

    private static function getSocketFdCount(): ?int
    {
        $fdDir = '/proc/self/fd';
        if (!is_dir($fdDir)) {
            return null;
        }

        try {
            return iterator_count(new \FilesystemIterator($fdDir, \FilesystemIterator::SKIP_DOTS));
        } catch (\Throwable $ignored) {
            return null;
        }
    }

    private static function shouldSkipSocketLog(): bool
    {
        if (!self::$socketLogFdLimitReached && self::$socketLogFailCount < self::$socketLogFailThreshold) {
            $now = time();
            if ($now - self::$socketLogLastFdCheckAt >= self::$socketLogFdCheckInterval) {
                self::$socketLogLastFdCheckAt = $now;
                $count = self::getSocketFdCount();
                if ($count !== null && $count >= self::$socketLogFdThreshold) {
                    self::$socketLogFdLimitReached = true;
                    self::$socketLogDisabledAt = time();
                    fwrite(STDERR, "[channel] fd 已接近上限 ({$count})，自动关闭 channel 内部日志" . PHP_EOL);
                    return true;
                }
            }
            return false;
        }

        $now = time();
        if ($now - self::$socketLogLastFdCheckAt < self::$socketLogFdCheckInterval) {
            return true;
        }
        self::$socketLogLastFdCheckAt = $now;

        if (self::$socketLogDisabledAt > 0 && $now - self::$socketLogDisabledAt < self::$socketLogMinDisableDuration) {
            return true;
        }

        $count = self::getSocketFdCount();
        if ($count !== null && $count < self::$socketLogFdRecoverThreshold) {
            self::$socketLogFdLimitReached = false;
            self::$socketLogFailCount = 0;
            self::$socketLogDisabledAt = 0;
            fwrite(STDERR, "[channel] fd 已回落 ({$count})，自动恢复 channel 内部日志" . PHP_EOL);
            return false;
        }

        return true;
    }

    private static function writeSocketLog(string $content): void
    {
        if (self::shouldSkipSocketLog()) {
            return;
        }

        try {
            ThinkFileLogDir::ensureChannel('socket');
            Log::channel('socket')->write($content, 'channel');
            self::$socketLogFailCount = 0;
        } catch (\Throwable $th) {
            self::$socketLogFailCount++;
            try {
                fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] socket 日志写入失败: ' . $th->getMessage() . PHP_EOL);
            } catch (\Throwable $ignored) {
                // 静默丢弃，防止 fd 耗尽时二次崩溃
            }

            if (self::$socketLogFailCount >= self::$socketLogFailThreshold) {
                self::$socketLogDisabledAt = time();
                try {
                    fwrite(STDERR, '[channel] 连续日志失败 ' . self::$socketLogFailCount . ' 次，已自动关闭 channel 内部日志' . PHP_EOL);
                } catch (\Throwable $ignored) {
                }
            }
        }
    }
}
