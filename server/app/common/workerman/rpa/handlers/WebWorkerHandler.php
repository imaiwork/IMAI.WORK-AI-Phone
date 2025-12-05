<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class WebWorkerHandler extends BaseMessageHandler
{
    protected int $msgType;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        $content['userId'] = $content['userId'] ?? 0;
        try {
            $this->uid = $uid;
            $this->payload = $payload;
            $this->connection = $connection;
            $this->userId = $content['userId'] ?? 0;
            if ($this->userId == 0) {
                $this->payload['reply'] = '用户参数无效';
                $this->payload['code'] =  WorkerEnum::WEB_USER_INVALID_REQUEST;
                $this->sendError($this->connection,  $this->payload);
                return;
            }
            $worker = $this->service->getWorker();
            if (isset($worker->uidConnections[$uid])) {
                $worker->uidConnections[$uid]->apptype = WorkerEnum::WS_WEB_TYPE;
                $worker->uidConnections[$uid]->userid = $content['userId'] ?? 0;
                $worker->uidConnections[$uid]->clientType = 'webUser';
                $worker->uidConnections[$uid]->sourceType = $content['sourceType'] ?? WorkerEnum::WS_SOURCE_PC_TYPE;
                $worker->uidConnections[$uid]->name = 'web_' . $content['userId'];

                $source = $worker->uidConnections[$uid]->sourceType;
                $this->service->getRedis()->set("xhs:user:{$source}:{$content['userId']}", $uid);
            }

            $message = array(
                'messageId' => $uid,
                'type' => 'bindSocket',
                'reply' => [
                    'type' => 'bindSocket',
                    'msg' => '绑定成功',
                    'userId' => $content['userId'],
                    'sourceType' => $source,
                    'wsId' => $uid
                ],
                'code' => WorkerEnum::SUCCESS_CODE
            );

            $this->sendResponse($uid, $message, $message['reply']);
            $this->registerChannelListener($this->connection, $content['userId'], 'webUser');
            $this->setLog($message, 'bind');
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'bind');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::WED_BIND_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }
}
