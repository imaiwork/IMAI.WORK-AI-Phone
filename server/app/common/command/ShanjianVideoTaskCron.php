<?php


namespace app\common\command;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\logic\shanjian\VoiceLogic;
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

        // 上一步只按父设置状态 1/2 捞:父设置被 notify 推成终态(3/4/5)后就再也选不到,
        // 底下没跑完的子任务永远挂"生成中"、不退费,还占死闪剪任务2 的 limit 名额。
        // 单独一步按子任务反查已终态的父设置扫尾,异常也不拖累其他步骤。
        $this->runStep($output, '闪剪孤儿子任务收口', static function () {
            ShanjianVideoSettingLogic::checkOrphanTasks();
        });

        // 回调是主链路,这里是唯一能取回真实结果的兜底:回调一旦丢失(含鉴权拒绝),
        // 24 小时收口只会判失败退费,已渲染好的成片就白丢了。中台 shanjian_status
        // 场景为 free 计费,轮询不产生算力消耗。
        $this->runStep($output, '闪剪任务2', static function () {
            ShanjianVideoTaskLogic::check();
        });

        $this->runStep($output, '闪剪任务4', static function () {
            ShanjianVideoTaskLogic::checkCover();
        });

        $this->runStep($output, '闪剪任务3', static function () {
            ShanjianVideoTaskLogic::compositeVideoCron();
        });

        // 音色回调丢失的唯一兜底：补偿 notify + 24 小时收口退费。没有这一步就不能给音色回调开鉴权。
        $this->runStep($output, '闪剪音色兜底', static function () {
            VoiceLogic::check();
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
