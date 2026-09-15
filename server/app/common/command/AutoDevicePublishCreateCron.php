<?php

namespace app\common\command;

use app\common\service\sv\AutoDeviceDailyCreateRunner;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AutoDevicePublishCreateCron
 * @desc 自动创建设备素材发布/朋友圈素材发布任务
 */
class AutoDevicePublishCreateCron extends Command
{
    protected function configure()
    {
        $this->setName('auto_device_publish_create_cron')
            ->setDescription('自动创建设备素材发布与朋友圈素材发布任务');
    }

    protected function execute(Input $input, Output $output)
    {
        return (new AutoDeviceDailyCreateRunner())->run(AutoDeviceDailyCreateRunner::SCENE_PUBLISH) ? 0 : 1;
    }
}
