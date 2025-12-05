<?php


namespace app\common\command;

use app\api\logic\LianLianLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * HumanVoiceTaskCron
 * @desc 视频任务
 * @author dagouzi
 */
class LianlianAnalysisCron extends Command
{
    protected function configure()
    {
        $this->setName('lianlian_analysis_cron')
            ->setDescription('AI陪练分析');
    }

    protected function execute(Input $input, Output $output)
    {
        LianLianLogic::analysisCron();
        return true;
    }
}
