<?php


namespace app\common\command;

use app\api\logic\sora\SoraAnchorLogic;
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
        print_r("\nSora视频生成任务1...'\n");
        SoraVideoSettingLogic::checkStatus();
        print_r("\nSora视频生成任务2...'\n");
        SoraVideoTaskLogic::checkStatus();
        print_r("\nSora视频生成任务3...'\n");
        SoraAnchorLogic::checkVideoStatus();
        print_r("\nSora视频生成任务4...'\n");
        SoraAnchorLogic::checkStatus();

        return true;
    }
}
