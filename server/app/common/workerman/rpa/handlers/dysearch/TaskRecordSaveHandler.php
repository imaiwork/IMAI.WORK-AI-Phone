<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\handlers\dysearch;

use Workerman\Connection\TcpConnection;
use app\common\service\sv\DySearchIngestService;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;

class TaskRecordSaveHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content'] ?? null) ? json_decode((string)($payload['content'] ?? ''), true) : $payload['content'];
        if (!is_array($content)) {
            $content = [];
        }
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = (int)($content['userId'] ?? 0);
            $this->connection = $connection;

            $deviceCode = trim((string)($this->payload['deviceId'] ?? $content['device_code'] ?? ''));
            $ack = DySearchIngestService::ingest($content, $deviceCode);
            $ackCode = (int)($ack['code'] ?? 0);
            $ackOk = !empty($ack['ok']);
            $discard = !empty($ack['discard']);

            $this->setLog(sprintf(
                '抖音获客走访上报 task_id=%s device=%s bind_id=%s dy_uid=%s result=%s ok=%s discard=%s code=%s msg=%s',
                (string)($content['task_id'] ?? 0),
                $deviceCode,
                (string)($content['bind_id'] ?? 0),
                trim((string)($content['dy_uid'] ?? '')),
                (string)((int)($ack['result'] ?? 0)),
                $ackOk ? '1' : '0',
                $discard ? '1' : '0',
                (string)$ackCode,
                (string)($ack['msg'] ?? '')
            ), 'dy_search');

            $reply = [
                'deviceId' => $deviceCode,
                'task_id' => $content['task_id'] ?? 0,
                'bind_id' => $content['bind_id'] ?? 0,
                'status' => ($ackOk && !$discard && $ackCode !== 4059) ? 1 : 0,
                'result' => (int)($ack['result'] ?? 0),
                'reg_content' => (string)($ack['reg_content'] ?? ''),
                'clue_type' => (int)($ack['clue_type'] ?? 0),
                'time' => date('Y-m-d H:i:s'),
                'msg' => (string)($ack['msg'] ?? ''),
            ];
            if ($ackCode === 4059) {
                $reply['code'] = WorkerEnum::TASK_TOKEN_ERROR;
                $this->payload['code'] = WorkerEnum::TASK_TOKEN_ERROR;
            }

            $this->payload['reply'] = $reply;
            $this->sendResponse($uid, $this->payload, $reply);
        } catch (\Throwable $e) {
            $this->setLog('抖音获客走访上报回执异常：' . $e->getMessage(), 'dy_search');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_DY_SEARCH_TASK_FAIL;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }
}
