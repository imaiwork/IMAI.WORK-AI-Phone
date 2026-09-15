<?php


namespace app\common\command;

use app\api\logic\material\FfmpegFileLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * @author dagouzi
 */
class FFmpegFileCron extends Command
{
    protected function configure()
    {
        $this->setName('ffmpeg_cron')
            ->setDescription('文件处理任务');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 转码任务1...'\n");
        FfmpegFileLogic::taskCron();
        return true;
    }
}
