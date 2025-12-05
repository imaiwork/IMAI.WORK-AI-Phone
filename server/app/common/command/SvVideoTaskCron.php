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
        SvVideoSettingLogic::check();
        SvVideoTaskLogic::compositeAnchorCron();
        SvVideoTaskLogic::compositeVoiceCron();
        SvVideoTaskLogic::compositeAudioCron();
        SvVideoTaskLogic::compositeVideoCron();
        return true;
    }
}
