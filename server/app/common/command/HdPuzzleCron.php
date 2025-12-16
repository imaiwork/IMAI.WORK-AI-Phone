<?php


namespace app\common\command;

use app\api\logic\hd\HdPuzzleLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * @author dagouzi
 */
class HdPuzzleCron extends Command
{
    protected function configure()
    {
        $this->setName('hd_puzzle_cron')
            ->setDescription('小红书拼图任务');
    }

    protected function execute(Input $input, Output $output)
    {

        HdPuzzleLogic::taskCron();
        return true;
    }
}
