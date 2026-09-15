<?php


namespace app\common\command;

use app\api\logic\kb\KbKnowLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * KbCron
 * @desc 知识库任务
 */
class KbCron extends Command
{
    protected function configure()
    {
        $this->setName('kb_cron')
             ->setDescription('知识库任务');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 知识库任务1...'\n");
        KbKnowLogic::modelButlerCheck();
        print_r("\n 知识库任务2...'\n");
        KbKnowLogic::systemKbRobotCheck();
        print_r("\n 知识库任务3...'\n");
        KbKnowLogic::systemKbRobotInsertModelButlerKbKnow();
        return true;
    }
}
