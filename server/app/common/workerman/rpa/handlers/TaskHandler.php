<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

class TaskHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        try {

            $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];

            $payload['reply'] = '任务执行状态';


            $this->sendResponse($uid, $payload, $payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'error');
        }
    }
}
