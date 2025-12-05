<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class CheckInitHandler extends BaseMessageHandler
{
    protected int $msgType;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->uid = $uid;
            $this->payload = $payload;
            $this->connection = $connection;

            $this->userId = $content['userId'] ?? 0;

            $worker = $this->service->getWorker();
            $uid = $worker->devices[$this->payload['deviceId']] ?? '';
            if ($uid == '') {
                $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
                $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                $this->sendError($this->connection,  $this->payload);
                return;
            }

            $isInit = $worker->uidConnections[$uid]->initial == 1 ? 1 : 0;

            $message = array(
                'messageId' => $uid,
                'type' => WorkerEnum::WEB_INIT_CHECK_TEXT,
                'deviceId' =>  $this->payload['deviceId'],
                'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'appType' => $this->payload['appType'] ?? 3,
                'reply' => [
                    'type' =>  WorkerEnum::WEB_INIT_CHECK_TEXT,
                    'msg' => '设备初始化检查',
                    'deviceId' => $this->payload['deviceId'],
                    'isInit' => $isInit,
                    'code' => WorkerEnum::INIT_CHECK
                ],
                'code' => WorkerEnum::SUCCESS_CODE,
            );
            $this->setLog($message, 'init');
            $this->sendResponse($this->uid, $message, $message['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'init');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::INIT_CHECK_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }
}
