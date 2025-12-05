<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

class DefaultHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];

        try {
            $message = array(
                'messageId' => $uid,
                'type' => -1,
                'code' => 400,
                'reply' => '指令有误'
            );

            $this->sendResponse($uid, $message, $message['reply']);
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'error');
        }
    }
}
