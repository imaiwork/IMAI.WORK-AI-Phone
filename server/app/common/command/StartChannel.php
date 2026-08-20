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
        $logRoot = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'log';
        if (!is_dir($logRoot) && !mkdir($logRoot, 0755, true) && !is_dir($logRoot)) {
            throw new \RuntimeException('Cannot create log directory: ' . $logRoot);
        }
        $workermanLog = $logRoot . DIRECTORY_SEPARATOR . 'workerman.log';
        Worker::$stdoutFile = $workermanLog;
        Worker::$logFile = $workermanLog;
        $channel_server = new \Channel\Server('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        $output->writeln('channel服务启动成功');
        if (!defined('GLOBAL_START')) {
            Worker::runAll();
        }
    }
}
