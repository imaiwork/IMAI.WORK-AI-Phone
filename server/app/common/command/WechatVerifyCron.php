<?php


namespace app\common\command;

use app\api\logic\sv\CrawlingTaskLogic;
use app\common\service\sv\DySearchWechatVerifyService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * WechatVerifyCron
 * @desc 验证微信用户
 * @author dagouzi
 */
class WechatVerifyCron extends Command
{
    protected function configure()
    {
        $this->setName('wechat_verify')
            ->setDescription('验证微信用户')
            ->addOption('task', null, Option::VALUE_OPTIONAL, '回补指定抖音获客任务id');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('验证微信用户任务开始...');
        $start = microtime(true);
        $taskId = (int)$input->getOption('task');
        if ($taskId > 0) {
            $processed = DySearchWechatVerifyService::backfillTask($taskId);
            $output->writeln('已回补抖音获客任务' . $taskId . '，处理' . $processed . '条');
            $elapsed = round(microtime(true) - $start, 2);
            $output->writeln('验证微信用户任务结束，耗时' . $elapsed . '秒');
            return true;
        }
        CrawlingTaskLogic::verifyWeChatCron();
        DySearchWechatVerifyService::cron();
        $elapsed = round(microtime(true) - $start, 2);
        $output->writeln('验证微信用户任务结束，耗时' . $elapsed . '秒');
        return true;
    }
}
