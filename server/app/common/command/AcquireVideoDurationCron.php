<?php


namespace app\common\command;

use app\api\logic\sv\SvVideoSettingLogic;
use app\api\logic\sv\SvVideoTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * @desc 获取视频时长
 * @author dagouzi
 */
class AcquireVideoDurationCron extends Command
{
    protected function configure()
    {
        $this->setName('acquire_video_duration_task')
            ->setDescription('获取视频时长');
    }

    protected function execute(Input $input, Output $output)
    {
        //https://test.imai.work/api/sv.mediaMaterial/lists?page_no=1&page_size=20&name&m_type&field&order_by&group_id
        //https://test.imai.work/api/video/creationRecord?page_no=1&page_size=20&type
        return true;
    }
}
