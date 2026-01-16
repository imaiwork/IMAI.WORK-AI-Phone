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
class DigitalHumanAnchorCron extends Command
{
    protected function configure()
    {
        $this->setName('digital_human_anchor_cron')
            ->setDescription('公共数字人形象任务');
    }

    protected function execute(Input $input, Output $output)
    {
        DigitalHumanLogic::createDigitalHumanAnchorCron();
        DigitalHumanLogic::getDigitalHumanAnchorStatusCron();
        return true;
    }
}
