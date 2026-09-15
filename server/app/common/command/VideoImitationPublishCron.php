<?php
namespace app\common\command;

use app\api\logic\videoImitation\ImitationPublishLogic;
use app\api\logic\videoImitation\TaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class VideoImitationPublishCron extends Command
{
    protected function configure()
    {
        $this->setName('video_imitation_publish')->setDescription('爆款仿写视频自动发布');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('video imitation auto publish start');

        ImitationPublishLogic::setImitationPublishDetail();
        TaskLogic::autoConfirmWashTasksCron();
        TaskLogic::autoGenerateTasksCron();

        $output->writeln('video imitation auto publish end');
    }
}