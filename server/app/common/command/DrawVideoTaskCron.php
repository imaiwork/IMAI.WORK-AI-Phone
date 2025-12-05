<?php


namespace app\common\command;

use app\api\logic\VolcLogic;
use app\api\logic\hd\DoubaoLogic;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class DrawVideoTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('draw_video_task')
            ->setDescription('AI设计视频生成');
    }

    protected function execute(Input $input, Output $output)
    {
        VolcLogic::videoQueue();
        VolcLogic::videoQueueStatus();
        DoubaoLogic::videoQueue();
        DoubaoLogic::videoQueueStatus();
        return true;
    }
}
