<?php


namespace app\common\command;

use app\api\logic\sv\SvVideoTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 *
 * @desc 查询音频生成情况
 * @author dagouzi
 */
class QuerySvAudioTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('query_sv_audio_task')
            ->setDescription('音频查询');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 矩阵任务查询1...'\n");
        SvVideoTaskLogic::queryVoiceCron();
        print_r("\n 矩阵任务查询2...'\n");
        SvVideoTaskLogic::queryAudioCron();
        return true;
    }
}
