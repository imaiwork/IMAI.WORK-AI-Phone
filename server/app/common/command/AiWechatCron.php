<?php


namespace app\common\command;

use app\api\logic\wechat\CircleLogic;
use app\api\logic\wechat\TodoLogic;
use app\api\logic\shanjian\PublishLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class AiWechatCron extends Command
{
    protected function configure()
    {
        $this->setName('ai_wechat_cron')
            ->setDescription('AI微信消息推送');
    }

    protected function execute(Input $input, Output $output)
    {
        TodoLogic::pushMessageCron();
        CircleLogic::sendCircleCron();
        //PublishLogic::SphPublishCron();
        return true;
    }
}
