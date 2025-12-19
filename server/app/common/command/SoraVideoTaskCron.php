<?php


namespace app\common\command;

use app\api\logic\sora\SoraVideoSettingLogic;
use app\api\logic\sora\SoraVideoTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SoraStatus
 * @desc 视频生成状态
 * @author dagouzi
 */
class SoraVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('sora_video_task')
            ->setDescription('Sora视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        SoraVideoSettingLogic::checkStatus();
        SoraVideoTaskLogic::checkStatus();
        return true;
    }
}
