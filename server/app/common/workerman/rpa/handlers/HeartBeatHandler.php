<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

class HeartBeatHandler extends BaseMessageHandler
{
    protected $HEARTBEAT_TIME = '3600';
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        return;
        try {

            $time_now = time();
            $worker = $this->service->getWorker();
            if (isset($worker->uidConnections[$uid])) {
                if (empty($worker->uidConnections[$uid]->lastMessageTime)) {
                    $worker->uidConnections[$uid]->lastMessageTime = $time_now;
                    return;
                }
                $diff_time = $time_now - $worker->uidConnections[$uid]->lastMessageTime;

                $message = array(
                    'type' => 'pong'
                );
                //$worker->uidConnections[$uid]->send(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $worker->uidConnections[$uid]->lastMessageTime > $this->HEARTBEAT_TIME) {
                    $worker->uidConnections[$uid]->close();
                }
            }
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'error');
        }
    }
}
