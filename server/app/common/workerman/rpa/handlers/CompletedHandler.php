<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Timer;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvReplyStrategy;

class CompletedHandler extends BaseMessageHandler
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


            $worker = $this->service->getWorker();
            if (!isset($worker->uidConnections[$uid])) {
                throw new \Exception('设备未连接');
            }
            $worker->uidConnections[$uid]->initial = 1;
            
            $deviceId = trim((string)$payload['deviceId']);
            $payload['deviceId'] = $deviceId;
            $this->service->getRedis()->set("xhs:init:{$deviceId}", date('Y-m-d H:i:s', time()));
            $this->service->getRepository()->markDeviceOnline(
                $deviceId,
                $uid,
                (int)$this->service->getWorker()->id,
                (string)($connection->appversion ?? '')
            );
            $payload['reply'] = '初始化完成';
            //获取设备对应用户的回复策略
            $device = SvDevice::where('device_code', $payload['deviceId'])->limit(1)->findOrEmpty();
            $defaultReplyStrategy =  [
                "multiple_type" => 0,
                "voice_enable" => 0,
                "image_enable" => 0,
                "image_reply" => "",
                "stop_enable" => 0,
                "stop_keywords" => '',
                "number_chat_rounds" => 0,
            ];
            if (!$device->isEmpty()) {
                $replyFind = SvReplyStrategy::where('user_id', $device['user_id'])->limit(1)->findOrEmpty();
                $defaultReplyStrategy = $replyFind->isEmpty() ? $defaultReplyStrategy : $replyFind->toArray();
            }

            $payload['reply'] = $defaultReplyStrategy;
            $this->sendResponse($uid, $payload, $payload['reply']);

            $this->sendWeb([
                'type' => WorkerEnum::WEB_DEVICE_INIT_OK_TEXT,
                'deviceId' => $payload['deviceId'],
                'code' => WorkerEnum::SUCCESS_CODE,
                'msg' => '设备初始化完成'
            ]);

            $this->setLog($payload, 'init');
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'init');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_INIT_COMPLETED_ERROR;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }

    private function sendWeb(array $content)
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
