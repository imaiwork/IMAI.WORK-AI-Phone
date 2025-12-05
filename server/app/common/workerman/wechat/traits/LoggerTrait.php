<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;

use think\facade\Log;

/**
 * 日志记录能力
 * 
 * @author Qasim
 * @package app\traits
 */
trait LoggerTrait
{

    /**
     * 通道
     */
    protected string $channel = 'message';

    /**
     * 日志级别
     */
    protected string $level = 'info';

    /**
     * 标题
     */
    protected string $title = '';

    /**
     * 上下文
     */
    protected array $context = [];

    /**
     * 设置通道
     * 
     * @param string $channel 通道
     * @return static
     */
    protected function withChannel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * 设置通道
     * 
     * @param string $level 日志级别
     * @return static
     */
    protected function withLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    /**
     * 设置标题
     * 
     * @param string $title 标题
     * @return static
     */
    protected function withTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /** 
     * 设置上下文
     * 
     * @param array $context 上下文
     * @return static
     */
    protected function withContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }

    /**
     * 记录日志
     * 
     * @return void
     */
    protected function log(): void
    {
        try {
            $content = '';
            if (is_array($this->context)) {
                $content = json_encode($this->context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            Log::channel($this->channel)->{$this->level}($this->title . "\n" . $content);
        } catch (\Exception $e) {
        }
    }
}
