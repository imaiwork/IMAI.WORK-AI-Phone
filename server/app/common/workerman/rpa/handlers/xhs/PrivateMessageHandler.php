<?php

namespace app\common\workerman\rpa\handlers\xhs;


use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPrivateMessage;
use app\common\workerman\rpa\WorkerEnum;

class PrivateMessageHandler extends BaseMessageHandler
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
                'taskType' => 'setPrivateMessage',
                'msg' => '小红书正在获取私列表',
                'duration' => 10,
                'scene' => 'xhs',
                'time' => date('Y-m-d H:i:s', time()),
            ], JSON_UNESCAPED_UNICODE));

            if ($this->msgType == WorkerEnum::RPA_PRIVATE_MESSAGE) {
                $this->_updatePrivateMessage($content);
            } else if ($this->msgType == WorkerEnum::WEB_PRIVATE_MESSAGE_LIST) {
                $this->_getPrivateMesssage($content);
            }
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'error');
        }
    }

    private function _getPrivateMesssage($content)
    {
        try {

            $device = $content['deviceId'];
            $worker = $this->service->getWorker();
            if (!isset($worker->devices[$device])) {

                $this->payload['reply'] = "设备{$device}不在线,无法获取私信列表信";
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                $this->setLog($this->payload, 'msg_list');
            } else {
                $uid = $worker->devices[$device] ?? '';
                if ($uid == '') {
                    $this->payload['reply'] = "设备{$device}不在线,无法获取账号信息";
                    $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }

                if (!$this->checkDeviceStatus($device)) {
                    $this->payload['reply'] = "设备正在回复消息中, 请稍后再试";
                    $this->payload['code'] = WorkerEnum::DEVICE_RUNNING_REPLY_MSG;
                    //$this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
                $message = array(
                    'messageId' => $uid,
                    'deviceId' => $device,
                    'type' => WorkerEnum::TO_RAP_PRIVATE_MESSAGE_LIST,
                    'appVersion' => WorkerEnum::APP_VERSION,
                    'appType' => 3,
                    'reply' => [
                        'type' => WorkerEnum::TO_RAP_PRIVATE_MESSAGE_LIST,
                        'msg' => '获取账号私信列表',
                        'deviceId' => $device
                    ]

                );

                $this->sendResponse($uid, $message, $message['reply']);
                $this->setLog($message, 'msg_list');
            }
        } catch (\Exception $e) {
            $this->setLog('_getPrivateMesssage' . $e, 'error');
        }
    }

    private function _updatePrivateMessage($content)
    {
        try {

            $this->payload['reply'] = '';
            $user = SvAccount::where('device_code', $this->payload['deviceId'])->limit(1)->find();
            if (!empty($user)) {
                $this->userId = $user['user_id'];

                $insertData = array();
                foreach ($content as $_item) {
                    $nickname =  $_item['authorName'] ?? '';
                    $friendId = md5($user['user_id'] . $user['account'] . $this->payload['deviceId'] . $nickname);
                    array_push($insertData, array(
                        'user_id' => $user['user_id'],
                        'device_code' => $this->payload['deviceId'],
                        'account' => $user['account'],
                        'type' => 3,
                        'friend_id' => $friendId,
                        'replay_type' => $_item['replyObject'] ?? '',
                        'avatar' => $this->base64ToImage($_item),
                        'author_name' => $_item['authorName'] ?? '',
                        'message_content' => $_item['messageContent'] ?? '',
                        'message_timer' => $_item['messagetTimer'] ?? time(),
                        'new_message_count' => $_item['newMessagetCount'] ?? 0,
                        'customer_type' => $_item['customerType'] ?? 1,
                        'create_time' => time()
                    ));
                }

                if (!empty($insertData)) {
                    //SvPrivateMessage::where('device_code' , $this->payload['deviceId'])->where('user_id', $user['user_id'])->delete();

                    $model = new SvPrivateMessage();
                    $result = $model->saveAll($insertData);
                }
                $this->payload['reply'] = '私信列表更新成功';
                $this->payload['userId'] = $this->userId;

                $this->_sendWeb($insertData);
            } else {
                $this->payload['reply'] = '该设备缺少用户信息';
            }

            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);

            $this->setLog($this->payload, 'msg_list');
        } catch (\Exception $e) {
            $this->setLog('_updatePrivateMessage' . $e, 'error');
        }
    }

    private function _sendWeb($content)
    {
        try {

            $userId = $this->userId;
            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => WorkerEnum::WEB_PRIVATE_MESSAGE_LIST_TEXT,
                        'appType' => 3,
                        'deviceId' => $this->payload['deviceId'],
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'reply' => $content
                    );
                    $this->sendResponse($uid,  $message,  $message['reply']);

                    $this->setLog($message, 'msg_list');
                }
            }
            // $uid = $this->service->getRedis()->get("xhs:user:pc:{$userId}") ?? $this->service->getRedis()->get("xhs:user:wmprog:{$userId}");
            // if ($uid) {
            //     $message = array(
            //         'messageId' => $uid,
            //         'type' => WorkerEnum::WEB_PRIVATE_MESSAGE_LIST_TEXT,
            //         'appType' => 3,
            //         'deviceId' => $this->payload['deviceId'],
            //         'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            //         'reply' => $content
            //     );
            //     $this->sendResponse($uid,  $message,  $message['reply']);

            //     $this->setLog($message, 'msg_list');
            // }
        } catch (\Exception $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }
}
