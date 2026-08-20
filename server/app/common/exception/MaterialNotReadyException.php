<?php

namespace app\common\exception;

/**
 * 素材尚未完成转码就绪的专用异常
 * 上游(cron / Job)捕获后应当走"延迟重试"分支,而不是按一般失败处理
 */
class MaterialNotReadyException extends \RuntimeException
{
    /** @var array<int, string> */
    public array $pendingUris = [];

    /** @var array<int, string> */
    public array $failedUris = [];

    public static function fromCheck(array $check, string $context = ''): self
    {
        $msg = '素材转码尚未完成,' . $context;
        $e = new self($msg);
        $e->pendingUris = $check['pending_uris'] ?? [];
        $e->failedUris = $check['failed_uris'] ?? [];
        return $e;
    }
}
