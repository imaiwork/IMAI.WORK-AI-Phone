<?php

namespace app\common\workerman\rpa\handlers\xhs;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvMaterial;

class CardHandler extends BaseMessageHandler
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
                'scene' => 'xhs',
                'duration' => 10,
                'taskType' => 'getCardInfo',
                'msg' => '正在获取小红书名片信息',
                'time' => date('Y-m-d H:i:s', time()),
            ]));


            if ($this->msgType == WorkerEnum::RPA_CARD_INFO) { //获取名片信息
                $this->_updateCards($content);
            } else if ($this->msgType == WorkerEnum::WEB_CARDS) { //web端主动获取名片列表
                $this->_getCardsByRpa($content);
            } else if ($this->msgType == WorkerEnum::WEB_SEND_CARD) { //web端发送名片信息
                $this->_sendCardToRpa($content);
            } else if ($this->msgType == WorkerEnum::RPA_SEND_CARD_STATUS) { //rpa回复名片发送状态
                $this->_receiveCardToWeb($content);
            }
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'card');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::CARD_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }
    private function _receiveCardToWeb($content) {}
    private function _sendCardToRpa($content)
    {
        try {
            $device = $content['deviceId'] ?? $this->payload['deviceId'];
            $worker = $this->service->getWorker();
            if (!isset($worker->devices[$device])) {
                $this->payload['reply'] = "设备{$device}不在线,无法发送名片";
                $this->payload['code'] = WorkerEnum::CARD_DEVICE_OFFLINE;
                $this->sendError($this->connection,  $this->payload);
                return;
            } else {
                $card = SvMaterial::where('id', $content['material_id'])->limit(1)->find();
                if (empty($card)) {
                    $this->payload['reply'] = "名片不存在";
                    $this->payload['code'] = WorkerEnum::CARD_NOT_FOUND;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
                $uid = $worker->devices[$device] ?? '';
                if ($uid == '') {
                    $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
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
                    'type' => WorkerEnum::TO_RPA_SEND_CARD,
                    'appVersion' => WorkerEnum::APP_VERSION,
                    'appType' => $this->payload['appType'] ?? 3,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => [
                        'targetRecipient' => $content['targetRecipient'],
                        'cardInfo'  => $card['content'],
                        'deviceId' => $device,
                        'msg' => '发送名片信息',
                    ]
                );
                $this->setLog($message, 'card');
                $this->sendResponse($uid, $message, $message['reply']);
                $this->_sendWeb([
                    'type' => WorkerEnum::WEB_SEND_CARD_TEXT,
                    'appType' => $this->payload['appType'] ?? 3,
                    'deviceId' => $this->payload['deviceId'],
                    'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => [
                        'type' => WorkerEnum::WEB_SEND_CARD_TEXT,
                        'deviceId' => $device,
                        'code' => WorkerEnum::SEND_CARD_OK,
                        'msg' => '发送名片成功'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $this->setLog('_sendCardToRpa' . $e, 'error');
        }
    }

    private function _getCardsByRpa($content)
    {
        try {
            $device = $content['deviceId'];
            $worker = $this->service->getWorker();
            if (!isset($worker->devices[$device])) {

                $this->payload['reply'] = "设备{$device}不在线,无法获取名片列表";
                $this->payload['code'] = WorkerEnum::CARD_DEVICE_OFFLINE;
                $this->setLog($this->payload['reply'], 'card');
                $this->sendError($this->connection,  $this->payload);
            } else {
                $account = SvAccount::where('device_code', $device)->limit(1)->find();
                if (empty($account)) {
                    $this->payload['reply'] = "设备缺少用户信息";
                    $this->payload['code'] = WorkerEnum::DEVICE_INVALID_ACCOUNT;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
                if ($account['type'] == 3) {
                    $this->setLog($account['extra'], 'card');
                    $extra = $account['extra'] ? json_decode($account['extra'], true) : [];
                    $account_type = $extra['account_type'] ?? 0;
                    $this->setLog('account_type:' . $account_type, 'card');
                    if ((int)$account_type === 0) {
                        $this->payload['reply'] = "小红书个人账号不支持获取名片列表";
                        $this->payload['code'] = WorkerEnum::NOT_SUPPORT_PERSONAL;
                        $this->sendError($this->connection,  $this->payload);
                        return;
                    }

                    $uid = $worker->devices[$device] ?? '';
                    if ($uid == '') {
                        $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
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
                        'type' => WorkerEnum::TO_RPA_CARDS,
                        'appVersion' => WorkerEnum::APP_VERSION,
                        'appType' => $this->payload['appType'] ?? 3,
                        'code' => WorkerEnum::SUCCESS_CODE,
                        'reply' => [
                            'type' => WorkerEnum::TO_RPA_CARDS,
                            'msg' => '获取账号名片信息',
                            'deviceId' => $device
                        ]
                    );
                    $this->setLog($message, 'card');
                    $this->sendResponse($uid, $message, $message['reply']);
                } else {
                    $this->payload['reply'] = "其他平台不支持获取名片列表";
                    $this->payload['code'] = WorkerEnum::NOT_SUPPORT;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->setLog('_getCardsByRpa' . $e, 'error');
        }
    }

    private function _updateCards($content)
    {
        try {

            $this->payload['reply'] = '';
            $user = SvAccount::where('device_code', $this->payload['deviceId'])->limit(1)->find();

            if (!empty($user)) {
                $this->userId = $user['user_id'];
                $insertData = array();
                $postData = array();

                $this->payload['reply'] = '名片列表更新成功';
                $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
                $this->setLog('名片列表更新成功', 'card');

                $message = array(
                    'type' => WorkerEnum::WEB_CARDS_TEXT,
                    'appType' => $this->payload['appType'] ?? 3,
                    'deviceId' => $this->payload['deviceId'],
                    'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => $content
                );

                $this->_sendWeb($message);
            } else {
                $this->payload['reply'] = '该设备缺少用户信息';
                $this->payload['code'] = WorkerEnum::DEVICE_INVALID_ACCOUNT;
                $this->setLog('该设备缺少用户信息', 'card');

                $this->sendError($this->connection,  $this->payload);
            }
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('_updateCards' . $e, 'error');
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
                        'type' => $content['type'],
                        'appType' => $this->payload['appType'] ?? 3,
                        'deviceId' => $this->payload['deviceId'],
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => WorkerEnum::SUCCESS_CODE,
                        'reply' => $content['reply']
                    );

                    $this->sendResponse($uid,  $message,  $message['reply']);
                    $message['sendTo'] = 'web';
                    $this->setLog($message, 'card');
                }
            }
            // $uid = $this->service->getRedis()->get("xhs:user:pc:{$userId}") ?? $this->service->getRedis()->get("xhs:user:wmprog:{$userId}");
            // if ($uid) {
            //     $message = array(
            //         'messageId' => $uid,
            //         'type' => $content['type'],
            //         'appType' => $this->payload['appType'] ?? 3,
            //         'deviceId' => $this->payload['deviceId'],
            //         'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            //         'code' => WorkerEnum::SUCCESS_CODE,
            //         'reply' => $content['reply']
            //     );

            //     $this->sendResponse($uid,  $message,  $message['reply']);
            //     $message['sendTo'] = 'web';
            //     $this->setLog($message, 'card');
            // } else {
            //     $this->setLog('web客户端连接未找到:' . $userId, 'card');
            // }
        } catch (\Exception $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }
}
