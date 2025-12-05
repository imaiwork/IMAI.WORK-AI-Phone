<?php


namespace app\common\command;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
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
class ShanjianVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('shanjian_video_task')
            ->setDescription('闪剪视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        ShanjianVideoSettingLogic::check();
        ShanjianVideoTaskLogic::check();
        ShanjianVideoTaskLogic::compositeVideoCron();
        return true;
    }
}
