<?php


namespace app\common\command;

use app\api\logic\minimax\VoiceLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class MinimaxShanjianCron extends Command
{
    protected function configure()
    {
        $this->setName('minimax_shanjian_cron')
            ->setDescription('minimax音频合成闪剪任务');
    }

    protected function execute(Input $input, Output $output)
    {
        VoiceLogic::shanjianAudioCreate();
        return true;
    }
}
