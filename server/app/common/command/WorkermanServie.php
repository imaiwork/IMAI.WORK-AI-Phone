<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;

class WorkermanServie extends Command
{

    protected function configure()
    {
        // 指令配置
        $this->setName('workerman:server')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('mode', 'm', Option::VALUE_OPTIONAL, 'Run the workerman server in daemon mode.')
            ->setDescription('Wechat server');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('convert start');

        $action = $input->getArgument('action');
        $mode = $input->getOption('mode');

        // 重新构造命令行参数,以便兼容workerman的命令
        global $argv;
        $argv = [];
        array_unshift($argv, 'think', $action);
        if ($mode == 'd') {
            $argv[] = '-d';
        } else if ($mode == 'g') {
            $argv[] = '-g';
        }

        try {

            // 初始化Channel服务（用于跨进程通信）
            $channel_server = new \Channel\Server('0.0.0.0', env('WORKERMAN.CHANNEL_PROT', 2206));
            Worker::$stdoutFile = root_path(). '/runtime/log/workerman.log';
            // 在这里放心的实例化worker,
            $rpaWorker = new Worker('websocket://0.0.0.0:'.env('WORKERMAN.RPA_PORT', 2345));
            $rpaWorker->count = 1;
            $rpaWorker->name = 'AiRpaService';
            $service = new \app\common\workerman\rpa\RpaSocketService($rpaWorker);
            $rpaWorker->onWorkerStart = array($service, 'onWorkerStart');
            $rpaWorker->onConnect     = array($service, 'onConnect');
            $rpaWorker->onMessage     = array($service, 'onMessage');
            $rpaWorker->onClose       = array($service, 'onClose');
            $rpaWorker->onError       = array($service, 'onError');
            $rpaWorker->onBufferFull  = array($service, 'onBufferFull');
            $rpaWorker->onBufferDrain = array($service, 'onBufferDrain');
             // 添加端口复用配置
            $rpaWorker->reusePort = true; // 启用端口复用，解决惊群效应

            $worker = new Worker('websocket://0.0.0.0:'.env('WORKERMAN.WECHAT_PORT', 2347));
            $worker->count = 4;
            $worker->name = 'AiWechatService';
            $service = new \app\common\workerman\wechat\WechatSocketService();
            $worker->onWorkerStart = array($service, 'onWorkerStart');
            $worker->onConnect     = array($service, 'onConnect');
            $worker->onMessage     = array($service, 'onMessage');
            $worker->onClose       = array($service, 'onClose');
            $worker->onError       = array($service, 'onError');

            // //设备socket
            $tcpWorker = new Worker('tcp://0.0.0.0:'.env('WORKERMAN.DEVICE_PORT', 6489));
            $tcpWorker->count = 4;
            $tcpWorker->name = 'AiDeviceService';
            $deviceService = new \app\common\workerman\wechat\DeviceSocketService();
            $tcpWorker->onWorkerStart = array($deviceService, 'onWorkerStart');
            $tcpWorker->onConnect     = array($deviceService, 'onConnect');
            $tcpWorker->onMessage     = array($deviceService, 'onMessage');
            $tcpWorker->onClose       = array($deviceService, 'onClose');
            $tcpWorker->onError       = array($deviceService, 'onError');

            Worker::runAll();
        } catch (\Exception $e) {
            $level = 'ws';
            \think\facade\Log::channel('socket')->{$level}($e->__toString());
        }
    }
}
