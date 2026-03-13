<?php


namespace app\common\command;

use app\api\logic\sv\SvVideoSettingLogic;
use app\api\logic\sv\SvVideoTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class SvVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('sv_video_task')
            ->setDescription('短视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 矩阵任务1...'\n");
        SvVideoSettingLogic::check();
        print_r("\n 矩阵任务2...'\n");
        SvVideoTaskLogic::compositeAnchorCron();
        print_r("\n 矩阵任务3...'\n");
        SvVideoTaskLogic::compositeVoiceCron();
        print_r("\n 矩阵任务4...'\n");
        SvVideoTaskLogic::compositeAudioCron();
        print_r("\n 矩阵任务5...'\n");
        SvVideoTaskLogic::compositeVideoCron();
        return true;
    }
}
