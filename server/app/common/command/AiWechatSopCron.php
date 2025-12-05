<?php


namespace app\common\command;

use app\api\logic\wechat\sop\PushContentLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class AiWechatSopCron extends Command
{
    protected function configure()
    {
        $this->setName('ai_wechat_sop_cron')
             ->setDescription('AI微信sop消息推送');
    }

    protected function execute(Input $input, Output $output)
    {
        //sop被动触发消息推送
        PushContentLogic::sopStagetriggerPushJob();
        //群发消息推送
        PushContentLogic::sopPushJob();
        return true;
    }
}
