<?php


namespace app\common\command;

use app\api\logic\DigitalHumanLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * DigitalHumanAnchorCron
 * @desc 公共数字人形象任务
 */
class AiDigitalHumanAnchorCron extends Command
{
    protected function configure()
    {
        $this->setName('ai_digital_human_anchor_cron')
            ->setDescription('ai授权数字人形象任务');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n ai授权视频一克三...'\n");
        DigitalHumanLogic::createDigitalHumanAnchorAiCron();
        return true;
    }
}
