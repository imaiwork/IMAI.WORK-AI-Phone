<?php

namespace app\common\workerman\rpa\handlers\device;

use app\common\service\sv\SvAccountPersistService;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class AppCompletedHandler extends AppActionHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        if (!is_array($content)) {
            $content = [];
        }
        try {

            $this->msgType = $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            if ((int)($content['status'] ?? 0) === 1) {
                $code = WorkerEnum::RPA_APP_COMPLETED_FAIL;
                $msg = WorkerEnum::getMessage($code);
                $this->payload['reply'] = $msg;
                $this->payload['code'] = $code;
                $this->sendActionToWeb([
                    'code' => $code,
                    'msg' => $msg
                ], 'appCompleted');
                return;
            }

            $persist = $this->readPersistResult();
            if (is_array($persist) && (int)($persist['ok'] ?? 1) === 0) {
                $code = (int)($persist['code'] ?? WorkerEnum::DEVICE_ERROR_CODE);
                $msg = (string)($persist['msg'] ?? '账号落库失败');
                $this->payload['reply'] = $msg;
                $this->payload['code'] = $code;
                $this->sendActionToWeb([
                    'code' => $code,
                    'msg' => $msg
                ], 'appCompleted');
                return;
            }

            $this->setLog('appCompleted跳过成功推送:完成态由落库结果通知', 'user');
        } catch (\Throwable $th) {
            $this->setLog('异常信息' . $th->getMessage(), 'cron');

            $this->payload['reply'] = $th->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function readPersistResult(): ?array
    {
        $deviceId = trim((string)($this->payload['deviceId'] ?? ''));
        if ($deviceId === '') {
            return null;
        }
        $appType = (int)($this->payload['appType'] ?? 3);
        $raw = $this->service->getRedis()->get(SvAccountPersistService::persistResultKey($deviceId, $appType));
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
