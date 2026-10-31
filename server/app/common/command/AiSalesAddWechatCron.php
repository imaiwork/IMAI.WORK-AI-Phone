<?php

namespace app\common\command;

use app\api\logic\wechat\AiWechatAddLeadsLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AiSalesAddWechatCron
 * @desc AI销售加微-线索加微信定时任务
 */
class AiSalesAddWechatCron extends Command
{
    protected function configure()
    {
        $this->setName('ai_sales_add_wechat_cron')
            ->setDescription('AI销售加微-线索加微信定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        AiWechatAddLeadsLogic::runCron();
        return true;
    }
}
