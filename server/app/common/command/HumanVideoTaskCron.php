<?php


namespace app\common\command;

use app\api\logic\HumanLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * HumanVoiceTaskCron
 * @desc 视频任务
 * @author dagouzi
 */
class HumanVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('human_video_task_cron')
            ->setDescription('视频任务');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 数字人任务...'\n");
        HumanLogic::videoTaskCron();
        return true;
    }
}
