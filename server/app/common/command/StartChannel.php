<?php


namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use Workerman\Worker;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class StartChannel extends Command
{
    protected function configure()
    {
        $this->setName('start_channel')
            ->setDescription('启动channel服务');
    }

    protected function execute(Input $input, Output $output)
    {
        $channel_server = new \Channel\Server('0.0.0.0', env('WORKERMAN.CHANNEL_PROT', 2206));
        $output->writeln('channel服务启动成功');
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }
}
