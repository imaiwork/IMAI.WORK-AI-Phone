<?php


namespace app\common\command;

use app\api\logic\sv\CrawlingManualLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 手动加微任务
 */
class CrawlingManualCron extends Command
{
    protected function configure()
    {
        $this->setName('crawling_manual_cron')
            ->setDescription('手动加微任务');
    }

    protected function execute(Input $input, Output $output)
    {
        //CrawlingManualLogic::crawlingManualTaskCron();
        return true;
    }
}
