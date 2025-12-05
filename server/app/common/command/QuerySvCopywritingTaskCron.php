<?php


namespace app\common\command;

use app\api\logic\sv\SvCopywritingTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 *
 * @desc 查询文案生成情况
 * @author dagouzi
 */
class QuerySvCopywritingTaskCron extends Command
{
    protected function configure()
    {
        $this->setName('query_sv_copywriting_task_cron')
            ->setDescription('文案查询');
    }

    protected function execute(Input $input, Output $output)
    {
        SvCopywritingTaskLogic::queryCopywritingCron();
        return true;
    }
}
