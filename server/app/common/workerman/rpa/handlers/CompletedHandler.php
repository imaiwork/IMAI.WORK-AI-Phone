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
            // $worker->uidConnections[$uid]->crontabId = Timer::add(90, function () use ($uid, $payload, $worker) {
            //     $uid = $this->service->getRedis()->get("xhs:device:{$payload['deviceId']}") ?? $uid;
            //     if (!isset($worker->uidConnections[$uid])) {
            //         $msg = '设备不在线';
            //         $this->setLog('设备绑定定时器, 设备号:' . $payload['deviceId'] . ', uid:' . $uid . '  msg:' . $msg, 'device');
            //         return;
            //     }
            //     if ($worker->uidConnections[$uid]->isMsgRunning == 0) {
            //         try {
            //             $handler = new CrontabHandler($this->service);
            //             //$this->setLog('设备绑定定时器, 设备号:'. $payload['deviceId']. ', uid:'. $uid. ', name:'. $worker->uidConnections[$uid]->name, 'device');
            //             return $handler->runing($worker->uidConnections[$uid]);
            //         } catch (\Exception $e) {
            //             $this->setLog($e, 'error');
            //         }
            //     } else {
            //         $msg = '设备正在回复消息中, 请稍后再试';
            //         $this->setLog('设备绑定定时器, 设备号:' . $payload['deviceId'] . ', uid:' . $uid . ', name:' . $worker->uidConnections[$uid]->name . '  msg:' . $msg, 'device');
            //     }
            // });

            //示例数据定时任务
            // $worker->uidConnections[$uid]->testCrontabId = Timer::add(3, function () use ($uid, $payload, $worker) {
            //     $find = SvPublishSettingDetail::where('data_type', 1)->where('status', 0)->limit(1)->findOrEmpty();
            //     if (!$find->isEmpty()) {
            //         $uid = $this->service->getRedis()->get("xhs:device:{$payload['deviceId']}") ?? $uid;
            //         if (!isset($worker->uidConnections[$uid])) {
            //             $msg = '设备不在线';
            //             // $this->setLog('设备绑定定时器, 设备号:'. $payload['deviceId']. ', uid:'. $uid . '  msg:' . $msg, 'device');
            //             return;
            //         }

            //         if ($worker->uidConnections[$uid]->isMsgRunning == 0) {
            //             try {
            //                 $handler = new CrontabHandler($this->service);
            //                 return $handler->runing($worker->uidConnections[$uid], 1);
            //             } catch (\Exception $e) {
            //                 $this->setLog($e, 'error');
            //             }
            //         } else {
            //             $msg = '设备正在回复消息中, 请稍后再试1';
            //             $this->setLog('设备绑定定时器, 设备号:' . $payload['deviceId'] . ', uid:' . $uid . ', name:' . $worker->uidConnections[$uid]->name . ' msg:' . $msg, 'device');
            //         }
            //     }
            // });

            $this->service->getRedis()->set("xhs:init:{$payload['deviceId']}", date('Y-m-d H:i:s', time()));
            $this->service->getRedis()->set("xhs:device:{$payload['deviceId']}:status", 'online');
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
                'code' => WorkerEnum::DEVICE_INIT_OK,
                'msg' => '设备初始化完成'
            ]);

            $this->setLog($payload, 'init');
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'init');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_INIT_COMPLETED_ERROR;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
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
