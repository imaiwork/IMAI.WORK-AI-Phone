<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

class HeartBeatHandler extends BaseMessageHandler
{
    protected $HEARTBEAT_TIME = '3600';
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        try {

            $message = array(
                'type' => 'pong',
            );

            $connection->send(json_encode($message, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'error');
        }
    }
}
