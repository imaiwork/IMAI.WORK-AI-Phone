<?php

namespace app\common\workerman\rpa\handlers\device;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\WorkerEnum;

class AppDataSendHandler extends AppActionHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {

            $this->msgType = $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $code = WorkerEnum::SUCCESS_CODE;
            $msg = '数据发送成功';
            if ((int)$content['status'] === 1) {
                $code = WorkerEnum::RPA_APP_DATA_SEND_FAIL;
                $msg = WorkerEnum::getMessage($code);
            }

            $this->payload['reply'] = $msg;
            $this->payload['code'] = $code;
            $postData = [
                'code' => $code,
                'msg' => $msg
            ];
            $this->sendActionToWeb($postData, 'appDataSend');
        } catch (\Throwable $th) {
            $this->setLog('异常信息' . $th->getMessage(), 'cron');

            $this->payload['reply'] = $th->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    
}
