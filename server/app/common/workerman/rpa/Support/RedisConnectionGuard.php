<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

/**
 * 常驻 Workerman 进程的 Redis 断线守卫。
 *
 * 背景：RpaSocketService 在 onWorkerStart 时只创建一次 think\cache\driver\Redis，
 * phpredis 不会自动重连——连接一旦被服务端断开（went away），后续所有命令都会抛
 * RedisException，异常冒进 Timer/Channel 回调会触发进程退出（status 64000）。
 *
 * 策略：异常触发式，不做前置 ping（常态零开销）。
 * 1. 第一次执行失败：ping 探活确认连接是否真的断了；
 * 2. 探活失败：调用宿主注入的 rebuild 闭包（initServices(true)）强制重建；
 * 3. 重建后重试一次；仍未成功则记录中文日志并返回调用方指定的默认值，
 *    绝不把异常抛回事件循环。
 */
class RedisConnectionGuard
{
    /** 重建失败后的冷却时间（秒），避免 Redis 长时间不可用时高频重建 */
    private const REBUILD_COOLDOWN_SECONDS = 5;

    /** 失败日志的节流间隔（秒），避免通道风暴刷爆日志 */
    private const LOG_THROTTLE_SECONDS = 10;

    /** 强制重建动作，由宿主进程注入（通常是 initServices(true)） */
    private \Closure $rebuild;

    /** 探活动作，宿主注入：ping 当前 Redis 连接，成功返回 true */
    private ?\Closure $probe = null;

    /** 日志闭包：宿主注入中文日志实现（参数：级别，内容） */
    private ?\Closure $logger = null;

    /** 是否正在重建中：防止重建过程再次触发重建导致递归 */
    private bool $rebuilding = false;

    /** 最近一次重建失败的时间戳，用于冷却窗口判断 */
    private int $lastRebuildFailedAt = 0;

    /** 最近一次失败日志的时间戳，用于日志节流 */
    private int $lastLoggedAt = 0;

    public function __construct(\Closure $rebuild)
    {
        $this->rebuild = $rebuild;
    }

    public function setProbe(?\Closure $probe): void
    {
        $this->probe = $probe;
    }

    public function setLogger(?\Closure $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 执行依赖 Redis 的操作：正常路径直接透传，断线时自动重建并重试一次。
     *
     * @param callable $fn      实际业务操作
     * @param mixed    $default Redis 持续不可用时返回的兜底值
     * @param string   $scene   场景标识，写入日志便于定位
     * @return mixed
     */
    public function run(callable $fn, mixed $default = null, string $scene = ''): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            if (!$this->isRecoverable($e)) {
                // 非 Redis 侧异常（业务逻辑缺陷等）原样抛出，交给外层通用捕获记录，不掩盖问题
                throw $e;
            }

            return $this->recoverAndRetry($fn, $default, $scene, $e);
        }
    }

    /**
     * 探活当前 Redis 连接是否可用。
     */
    public function isRedisAvailable(): bool
    {
        if ($this->probe === null) {
            return false;
        }

        try {
            return ($this->probe)();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 判断异常是否属于“可重连恢复”的 Redis 侧异常。
     */
    private function isRecoverable(\Throwable $e): bool
    {
        if ($e instanceof \RedisException) {
            return true;
        }

        // 兼容未安装 phpredis 扩展时 think\cache 退化为 Predis 的场景
        foreach (['\Predis\CommunicationException', '\Predis\Connection\ConnectionException'] as $class) {
            if (class_exists($class) && $e instanceof $class) {
                return true;
            }
        }

        // think\cache\driver\Redis 的 __call 直接转发到 handle 属性上的原生 \Redis，
        // 句柄未初始化时会抛 Error(Called ... on null)，此时应触发重建而非外溢
        $message = $e->getMessage();
        return $e instanceof \Error
            && str_contains($message, 'Call to a member function')
            && str_contains($message, 'on null');
    }

    /**
     * 重建并重试一次；仍然失败时记中文日志并返回默认值。
     */
    private function recoverAndRetry(callable $fn, mixed $default, string $scene, \Throwable $firstError): mixed
    {
        $this->log('error', sprintf(
            '%sRedis 操作失败: 场景=%s, 已尝试自动重建',
            $scene !== '' ? "【{$scene}】" : '',
            $firstError->getMessage()
        ));

        if ($this->rebuilding) {
            // 重建流程中被再次触发时，返回默认值即可，等待本次重建完成后的重试
            $this->log('error', 'Redis 重建进行中，跳过本次重复触发');
            return $default;
        }

        if (time() - $this->lastRebuildFailedAt < self::REBUILD_COOLDOWN_SECONDS) {
            $this->log('error', 'Redis 处于重建冷却期，跳过本次重试');
            return $default;
        }

        $this->rebuilding = true;
        try {
            if ($this->probe !== null && $this->isRedisAvailable()) {
                // 连接仍存活：属于瞬时读写超时，直接重试即可，不重建
                $this->log('info', 'Redis 连接仍存活，按瞬时超时处理，直接重试');
            } else {
                $this->log('error', '试图重建 Redis 连接');
                ($this->rebuild)();
            }
        } catch (\Throwable $e) {
            $this->lastRebuildFailedAt = time();
            $this->log('error', sprintf('Redis 重建失败: 场景=%s, error=%s', $scene, $e->getMessage()));
            return $default;
        } finally {
            $this->rebuilding = false;
        }

        try {
            return $fn();
        } catch (\Throwable $e) {
            if (!$this->isRecoverable($e)) {
                $this->log('error', "Redis 重建后的重试抛出非 Redis 异常, 场景={$scene}, error={$e->getMessage()}");
                return $default;
            }

            $this->lastRebuildFailedAt = time();
            $this->log('error', sprintf(
                'Redis 重试仍失败: 场景=%s, error=%s（等待下个周期自动恢复）',
                $scene,
                $e->getMessage()
            ));
            return $default;
        }
    }

    private function log(string $level, string $message): void
    {
        if ($this->logger === null) {
            return;
        }

        $now = time();
        if ($level === 'error' && $now - $this->lastLoggedAt < self::LOG_THROTTLE_SECONDS) {
            return;
        }
        $this->lastLoggedAt = $now;

        ($this->logger)($level, $message);
    }
}
