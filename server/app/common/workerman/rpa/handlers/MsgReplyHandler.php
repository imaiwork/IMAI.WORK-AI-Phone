<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\WorkerEnum;

class MsgReplyHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":taskStatus", json_encode([
                'taskStatus' => 'running',
                'taskType' => 'MsgReply',
                'msg' => '小红书正在变更通知状态',
                'duration' => 10,
                'time' => date('Y-m-d H:i:s', time()),
                'scene' => 'xhs'
            ], JSON_UNESCAPED_UNICODE));

            if ($this->msgType == 12) {
                $this->msgReplyStart($content);
            } else if ($this->msgType == 13) {

                $this->msgReplyCompleted($content);
            }
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'device');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }


    private function msgReplyStart($content)
    {
        try {

            $this->setLog($content, 'msgReplyStart');

            $this->worker = $this->service->getWorker();

            $device_uid = $this->worker->devices[$this->payload['deviceId']] ?? '';
            if ($device_uid == '') {
                $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
                $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                $this->sendError($this->connection,  $this->payload);
                return;
            }

            $this->connection = $this->worker->uidConnections[$device_uid] ?? null;
            if ($this->connection === null) {
                $this->payload['reply'] = '设备未连接';
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                return;
            }
            $this->connection->isMsgRunning = 0;
            $this->worker->uidConnections[$device_uid]->isMsgRunning = 0;
            $this->service->setWorker($this->worker);

            $this->payload['reply'] = '已被设置为回复消息状态';
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);

            $this->sendWeb([
                'type' => "replyMsgRunning",
                'deviceId' => $this->payload['deviceId'],
                'code' => WorkerEnum::SUCCESS_CODE,
                'msg' => '设备正在回复消息中'
            ]);
        } catch (\Exception $e) {
            $this->setLog('msgReplyStart' . $e, 'error');
        }
    }

    private function msgReplyCompleted($content)
    {
        try {

            $this->setLog($content, 'msgReplyCompleted');
            $this->worker = $this->service->getWorker();

            $device_uid = $this->worker->devices[$this->payload['deviceId']] ?? '';
            if ($device_uid == '') {
                $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
                $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                $this->sendError($this->connection,  $this->payload);
                return;
            }
            $this->connection = $this->worker->uidConnections[$device_uid] ?? null;
            if ($this->connection === null) {
                $this->payload['reply'] = '设备未连接';
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                return;
            }
            $this->connection->isMsgRunning = 0;
            $this->worker->uidConnections[$device_uid]->isMsgRunning = 0;
            $this->service->setWorker($this->worker);

            $this->payload['reply'] = '设备清除消息回复状态';
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);

            $this->sendWeb([
                'type' => 'replyMsgCompleted',
                'deviceId' => $this->payload['deviceId'],
                'code' => WorkerEnum::SUCCESS_CODE,
                'msg' => '设备回复消息完成'
            ]);
        } catch (\Exception $e) {
            $this->setLog('msgReplyCompleted' . $e, 'error');
        }
    }


    private function sendWeb($content)
    {

        try {

            $find = SvDevice::where('device_code', $content['deviceId'])->limit(1)->find();
            if (empty($find)) {
                $this->setLog('设备不存在:' .  $content['deviceId'], 'init');
                return;
            }
            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$find['user_id']}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => $content['type'],
                        'appType' => $content['appType'] ?? 3,
                        'deviceId' => $content['deviceId'],
                        'appVersion' => $content['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => WorkerEnum::SUCCESS_CODE,
                        'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    );
                    $this->setLog($message, 'init');
                    $this->sendResponse($uid, $message, $message['reply']);
                }
            }
            // $uid = $this->service->getRedis()->get("xhs:user:pc:{$find['user_id']}") ?? $this->service->getRedis()->get("xhs:user:wmprog:{$find['user_id']}");
            // if ($uid) {
            //     $message = array(
            //         'messageId' => $uid,
            //         'type' => $content['type'],
            //         'appType' => $content['appType'] ?? 3,
            //         'deviceId' => $content['deviceId'],
            //         'appVersion' => $content['appVersion'] ?? WorkerEnum::APP_VERSION,
            //         'code' => WorkerEnum::SUCCESS_CODE,
            //         'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
            //     );
            //     $this->setLog($message, 'init');
            //     $this->sendResponse($uid, $message, $message['reply']);
            // } else {
            //     $this->setLog('web客户端不存在:' . $find['user_id'], 'init');
            // }
        } catch (\Exception $e) {
            $this->setLog('sendWeb' . $e, 'error');
        }
    }
}
