<?php


namespace app\common\command;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 闪剪视频定时任务
 */
class ShanjianVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('shanjian_video_task')
            ->setDescription('闪剪视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        // 成片转存单独先行：避免被合成/封面步骤卡住或异常拖死，导致永远跑不到
        $this->runStep($output, '闪剪成片转存', static function () {
            ShanjianVideoTaskLogic::autoDownloadPendingResults();
        });

        $this->runStep($output, '闪剪任务1', static function () {
            ShanjianVideoSettingLogic::check();
        });

        $this->runStep($output, '闪剪任务4', static function () {
            ShanjianVideoTaskLogic::checkCover();
        });

        $this->runStep($output, '闪剪任务3', static function () {
            ShanjianVideoTaskLogic::compositeVideoCron();
        });

        return true;
    }

    private function runStep(Output $output, string $name, callable $fn): void
    {
        $output->writeln("\n {$name}...'");
        try {
            $fn();
        } catch (\Throwable $e) {
            $msg = "{$name}异常: " . $e->getMessage();
            $output->writeln($msg);
            Log::channel('shanjiannotice')->error($msg . ' @' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
