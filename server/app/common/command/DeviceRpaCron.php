<?php


namespace app\common\command;

use app\api\logic\sv\DeviceLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * DeviceRpaCron
 * @desc 设备rpa执行定时任务
 * @author dagouzi
 */
class DeviceRpaCron extends Command
{
    protected function configure()
    {
        $this->setName('device_rpa_cron')
            ->setDescription('设备rpa执行定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        DeviceLogic::execDeviceRpaCron();
        return true;
    }
}
