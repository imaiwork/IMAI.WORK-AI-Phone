<?php


namespace app\common\command;


use app\common\service\sv\AutoDeviceDailyCreateRunner;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AutoDeviceCreateCron
 * @desc 自动创建设备运营/爆款任务
 * @author dagouzi
 */
class AutoDeviceCreateCron extends Command
{
    protected function configure()
    {
        $this->setName('auto_device_create_cron')
            ->setDescription('自动创建设备运营与爆款任务');
    }

    protected function execute(Input $input, Output $output)
    {
        return (new AutoDeviceDailyCreateRunner())->run(AutoDeviceDailyCreateRunner::SCENE_OPS) ? 0 : 1;
    }
}
