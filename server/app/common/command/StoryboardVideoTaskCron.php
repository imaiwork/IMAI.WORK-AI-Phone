<?php


namespace app\common\command;

use app\api\logic\storyboard\StoryboardVideoSettingLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SoraStatus
 * @desc 视频生成状态
 */
class StoryboardVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('storyboard_video_task')
            ->setDescription('分镜混剪视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n分镜混剪视频生成任务1...'\n");
        StoryboardVideoSettingLogic::checkStatus();
        print_r("\n分镜混剪视频生成任务2...'\n");
        StoryboardVideoSettingLogic::checkTaskStatus();
        return true;
    }
}
