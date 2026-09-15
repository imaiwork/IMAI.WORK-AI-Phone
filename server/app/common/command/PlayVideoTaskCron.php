<?php

declare(strict_types=1);

namespace app\common\command;

use app\api\logic\playVideo\PlayVideoLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 爆款玩法任务兜底（每分钟）：
 * 1) 批量同步生成中的任务（回调丢失/迟到的收敛路径）
 * 2) 超时判死退款（35min，以中台终态为准）+ 提交中断件清理
 */
class PlayVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('play_video_task')
            ->setDescription('爆款玩法任务状态兜底同步与超时退款');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->runStep($output, '爆款玩法状态同步', static function () {
            PlayVideoLogic::syncBatch();
        });
        $this->runStep($output, '爆款玩法超时清理', static function () {
            PlayVideoLogic::sweepTimeout();
        });
        return true;
    }

    private function runStep(Output $output, string $name, callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $msg = "{$name}异常: " . $e->getMessage();
            $output->writeln($msg);
            Log::channel('playvideo')->error($msg . ' @' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
