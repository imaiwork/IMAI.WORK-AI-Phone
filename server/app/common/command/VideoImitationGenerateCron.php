<?php
namespace app\common\command;

use app\api\logic\videoImitation\TaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class VideoImitationGenerateCron extends Command
{
    protected function configure()
    {
        $this->setName('video_imitation_task')->setDescription('爆款仿写视频自动生成');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('video imitation auto genarate start');

        TaskLogic::autoConfirmWashTasksCron();
        TaskLogic::autoGenerateTasksCron();

        $output->writeln('video imitation auto genarate end');
    }
}