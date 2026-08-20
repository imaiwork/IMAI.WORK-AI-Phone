<?php

declare(strict_types=1);

namespace app\common\command;

use app\common\service\chat\ChatModelsSyncService;
use app\common\service\draw\MediaModelsSyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 同步中台模型（对话 + 生图/生视频，合并为一条定时任务）
 *
 * php think sync_models
 *
 * 两个同步各自隔离，一个失败不影响另一个。
 */
class SyncModelsCron extends Command
{
    protected function configure()
    {
        $this->setName('sync_models')->setDescription('同步中台模型(对话+生图/生视频)');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->runSync($output, '对话模型', fn() => ChatModelsSyncService::sync());
        $this->runSync($output, '生图/生视频模型', fn() => MediaModelsSyncService::sync());
        return true;
    }

    private function runSync(Output $output, string $label, callable $sync): void
    {
        try {
            $result = $sync();
            $output->writeln("同步中台{$label}成功: " . json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::channel('crontab')->error("同步中台{$label}失败: " . $e->getMessage());
            $output->writeln("同步中台{$label}失败: " . $e->getMessage());
        }
    }
}
