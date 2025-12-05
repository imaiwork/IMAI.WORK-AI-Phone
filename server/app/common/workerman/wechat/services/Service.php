<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

/**
 * 服务容器
 * 
 * 管理所有服务实例:
 * - 单例模式
 * - 懒加载
 * - 服务管理
 * 
 * @author Qasim
 * @package app\service
 * @property-read ConnectionService $connectionService
 * @property-read MessageService $messageService
 * @property-read OSSService $ossService
 * 
 */
class Service
{
    /**
     * 服务实例映射
     * @var array<string, object>
     */
    private array $services = [];

    /**
     * 服务实例
     * @var ?self
     */
    private static ?self $instance = null;

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct() {}

    /**
     * 获取服务管理实例
     * 
     * @return self 服务管理实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取服务
     */
    public function __get(string $name)
    {
        return $this->services[$name] ??= $this->createService($name);
    }

    /**
     * 创建服务实例
     */
    private function createService(string $name)
    {
        // 构建完整的类名
        $className = __NAMESPACE__ . '\\' . ucfirst($name);

        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Service {$name} not found");
        }

        // 使用反射创建实例
        $reflection = new \ReflectionClass($className);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Service {$name} is not instantiable");
        }

        return $reflection->newInstance();
    }

    /**
     * 私有克隆函数，防止对象克隆
     */
    private function __clone() {}
}
