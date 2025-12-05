<?php


namespace app\common\command;

use app\api\logic\HdLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class HdStatus extends Command
{
    protected function configure()
    {
        $this->setName('hd_status')
            ->setDescription('图片生成状态');
    }

    protected function execute(Input $input, Output $output)
    {
        HdLogic::cron();
        return true;
    }
}
