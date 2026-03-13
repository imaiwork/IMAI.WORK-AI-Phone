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
        print_r("\n公共数字人形象任务1...'\n");
        DigitalHumanLogic::createDigitalHumanAnchorCron();
        print_r("\n公共数字人形象任务2...'\n");
        DigitalHumanLogic::getDigitalHumanAnchorStatusCron();
        print_r("\n公共数字人形象任务3...'\n");
        DigitalHumanLogic::getDigitalHumanAnchorFailedStatusCron(); //1小时以上的失败任务处理
        print_r("\n公共数字人形象任务4...'\n");
        DigitalHumanLogic::digitalHumanAnchorReturnCron(); //公共数字人创建失败退费
        print_r("\n公共数字人形象任务5...'\n");
        DigitalHumanLogic::supplement(); //公共数字人旧数据宽高补充 TODO 下个版本需删除
        return true;
    }
}
