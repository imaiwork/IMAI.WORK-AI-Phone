<?php

namespace app\common\workerman\rpa\handlers\xhs;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\wechat\AiWechat;
use think\facade\Db;

class UserHandler extends BaseMessageHandler
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


            if ($this->msgType == WorkerEnum::RPA_USER_INFO) {
                $this->_updateUserInfoByDevice($content);
            } else if ($this->msgType == WorkerEnum::WEB_GET_USER_INFO) {
                $this->_getUserInfoByRpa($content);
            }
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'user');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::USER_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function _getUserInfoByRpa($content)
    {

        //判断设备在不在线
        //不在线 返回不在线信息
        //在线 则发送指令到rap,
        //等待rpa回复 webws存在则 生成推送指令,不存在则不生产推送指令
        try {

            $device = $content['deviceId'];
            $worker = $this->service->getWorker();
            if (!isset($worker->devices[$device])) {

                $this->payload['reply'] = "设备{$device}不在线,无法获取账号信息";
                $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);

                $this->setLog($this->payload, 'user');
            } else {
                $uid = $worker->devices[$device] ?? '';
                if ($uid == '') {
                    $this->payload['reply'] = "设备{$device}不在线,无法获取账号信息";
                    $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
                // if (!$this->checkDeviceStatus($device)) {
                //     $this->payload['reply'] = "设备正在回复消息中, 请稍后再试";
                //     $this->payload['code'] = WorkerEnum::DEVICE_RUNNING_REPLY_MSG;
                //     //$this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                //     $this->sendError($this->connection,  $this->payload);
                //     return;
                // }

                // $wechatCode = $this->service->getRedis()->get("xhs:device:{$device}:wechat_code");
                // if(empty($wechatCode)){
                //     $message = array(
                //         'messageId' => $uid,
                //         'deviceId' => $device,
                //         'type' => WorkerEnum::TO_RAP_USER_INFO,
                //         'appVersion' => WorkerEnum::APP_VERSION,
                //         'appType' => 1,
                //         'code' => WorkerEnum::SUCCESS_CODE,
                //         'reply' => [
                //             'type' => WorkerEnum::TO_RAP_USER_INFO,
                //             'appType' => 1,
                //             'msg' => sprintf("获取设备%s用户信息", WorkerEnum::getAccountTypeDesc(1)),
                //             'deviceId' => $device
                //         ]
                //     );
                //     $this->sendResponse($uid, $message, $message['reply']);
                //     sleep(5);
                // }


                $message = array(
                    'messageId' => $uid,
                    'deviceId' => $device,
                    'type' => WorkerEnum::TO_RAP_USER_INFO,
                    'appVersion' => WorkerEnum::APP_VERSION,
                    'appType' => $this->payload['appType'] ?? 3,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => [
                        'type' => WorkerEnum::TO_RAP_USER_INFO,
                        'appType' => $this->payload['appType'] ?? 3,
                        'msg' => sprintf("获取设备%s用户信息", WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3)),
                        'deviceId' => $device
                    ]
                );

                //$this->sendAppExec($device, $uid, $this->payload['appType']);

                $this->service->getRedis()->set("xhs:getUser:{$device}", $content['userId']);
                $this->sendResponse($uid, $message, $message['reply']);
                $this->setLog($message, 'user');
            }
        } catch (\Exception $e) {
            $this->setLog('_getUserInfoByRpa' . $e, 'error');
        }
    }

    private function sendAppExec($deviceid, $uid, $appType)
    {
        try {
            $deviceTaskStatus = $this->service->getRedis()->get("xhs:device:{$deviceid}:taskStatus");
            //print_r($deviceTaskStatus);die;
            if (!empty($deviceTaskStatus)) {
                $deviceTaskStatus = json_decode(($deviceTaskStatus), true);
                if ($deviceTaskStatus['taskStatus'] == 'running' && $deviceTaskStatus['scene'] == 'xhs') {
                    return;
                }
            }

            $app = SvDeviceRpa::where('device_code', $deviceid)->where('app_type', $appType)->findOrEmpty();
            if ($app->isEmpty()) {
                throw new \Exception('当前设备未绑定app:' . Db::getLastSql());
            }
            $payload = [
                "messageId" => 2,
                "type" => 90, //执行那个app指令
                "appType" => $appType,
                "content" => json_encode([
                    "deviceId" => $deviceid,
                    "appType" => $appType,
                    'msg' => WorkerEnum::getAccountTypeDesc($appType),
                    'task_id' => $app->id
                ], JSON_UNESCAPED_UNICODE),
                'reply' => [
                    "deviceId" => $deviceid,
                    "appType" => $appType,
                    'msg' => WorkerEnum::getAccountTypeDesc($appType),
                    'task_id' => $app->id
                ],
                "deviceId" => $deviceid,
                "appVersion" => WorkerEnum::APP_VERSION
            ];

            $this->sendResponse($uid, $payload, $payload['reply']);

            SvDeviceRpa::where('device_code', $deviceid)->where('app_type', '<>', $appType)->update(['status' => 0, 'update_time' => time()]);
            SvDeviceRpa::where('device_code', $deviceid)->where('app_type', $appType)->update([
                'status' => 1,
                'update_time' => time(),
                'start_time' => date('Y-m-d H:i:s', time()),
            ]);

            $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":taskStatus", json_encode([
                'taskStatus' => 'running',
                'taskType' => 'getUserInfo',
                'msg' => WorkerEnum::getAccountTypeDesc($appType) . '正在获取用户信息',
                'duration' => 10,
                'scene' => 'xhs',
                'time' => date('Y-m-d H:i:s', time()),
            ], JSON_UNESCAPED_UNICODE));

            sleep(20);
        } catch (\Throwable $e) {
            $this->setLog('sendAppExec' . $e, 'error');
        }
    }



    private function _updateUserInfoByDevice($content)
    {

        try {
            if (isset($this->payload['appType']) && (int)$this->payload['appType'] === 1) {
                $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":wechat_code", $content['wechatDeviceCode']);
                $find = \app\common\model\sv\SvDevice::where('device_code', $this->payload['deviceId'])->findOrEmpty();
                if (!$find->isEmpty()) {
                    $find->wechat_device_code = $content['wechatDeviceCode'] ?? '';
                    $find->update_time = time();
                    $find->save();
                }

                $wechat = AiWechat::where('device_code', $content['wechatDeviceCode'])->where('user_id', $find->user_id)->findOrEmpty();
                if (!$wechat->isEmpty()) {
                    $postData = array(
                        'type' => $this->payload['appType'],
                        'device_code' => $this->payload['deviceId'],
                        'wechat_device_code' => $content['wechatDeviceCode'] ?? '',
                        'avatar' => $wechat->wechat_avatar ?? '',
                        'nickname' => $wechat->wechat_nickname ?? WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . rand(0, 99999),
                        'status' => $wechat->wechat_status ?? 1,
                        'extra' => array(
                            'gender' => $content['gender'] ?? ''
                        ),
                        'account' => $wechat->wechat_id ?? '',
                        'account_no' => $wechat->wechat_no ?? '',
                        'create_time' => time()
                    );
                    $this->payload['reply'] = '设备用户新增成功, ';
                    $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
                } else {
                    $this->payload['reply'] = "获取账号信息失败,请先绑定个微,再重新获取";
                    $this->payload['code'] = WorkerEnum::WEB_GET_WECHAT_USER_INFO_FAIL;
                    $postData = [
                        'code' => WorkerEnum::WEB_GET_WECHAT_USER_INFO_FAIL,
                        'msg' => '获取账号信息失败,请先绑定个微,再重新获取'
                    ];
                }
                //判断是不是有web的ws,用则推送一条数据
                $this->_sendWeb($postData);
            } else {
                if (!isset($content['xhsId'])) {
                    //return;
                    $content['xhsId'] = time();
                    $this->payload['reply'] = "获取账号信息失败,请重新获取";
                    $this->payload['code'] = WorkerEnum::WEB_GET_USER_INFO_FAIL;
                    $postData = [
                        'code' => WorkerEnum::WEB_GET_USER_INFO_FAIL,
                        'msg' => '获取账号信息失败,请重新获取'
                    ];
                    $this->_sendWeb($postData);
                    return;
                }

                $content['xhsId'] = str_replace(WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . '号：', '', $content['xhsId']);
                $this->payload['reply'] = '';

                $postData = array(
                    'avatar' => $this->base64ToImage($content),
                    'nickname' => $content['nickName'] ?? WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . rand(0, 99999),
                    'status' => 1,
                    'type' => $this->payload['appType'] ?? 3,
                    'extra' => json_encode(array(
                        'gender' => $content['gender'] ?? '',
                        'introduction' => $content['introduction'] ?? '',
                        'constellation' => $content['constellation'] ?? '',
                        'area' => $content['area'] ?? '',
                        'followers' => $content['numberFollowers'] ?? 0,
                        'fans' => $content['numberFans'] ?? 0,
                        'thumbup_collect' => $content['thumbsUpAndCollect'] ?? 0,
                        'business_card' => 0, //$this->_getCardByAccount($content, $device['user_id'])
                        'account_type' => $content['accountType'] ?? 0, //0 个人 1企业
                    ), JSON_UNESCAPED_UNICODE),
                );

                $postData['device_code'] =  $this->payload['deviceId'];
                $postData['account'] = $content['xhsId'];
                $postData['account_no'] = $content['xhsId'];
                $postData['create_time'] = time();
                $postData['extra'] = json_decode($postData['extra'], true);

                $this->payload['reply'] = '设备用户新增成功, ';
                $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
                $platformType = $this->PlatformTypeEn[$this->payload['appType'] ?? 3] ?? 'xhs';
                $this->service->getRedis()->set("xhs:{$this->payload['deviceId']}:{$platformType}:accountNo", $content['xhsId']);
                $this->service->getRedis()->set("xhs:{$this->payload['deviceId']}:{$platformType}:accountInfo:{$content['xhsId']}", json_encode($postData, JSON_UNESCAPED_UNICODE));

                $this->_sendWeb($postData);
            }
        } catch (\Exception $e) {
            $this->setLog('_updateUserInfoByDevice' . $e, 'error');
        }
    }

    private function _sendWeb($content)
    {

        try {

            $userId = $this->service->getRedis()->get("xhs:getUser:" . $this->payload['deviceId']);
            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $content['source_type'] = $source;
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => WorkerEnum::WEB_GET_USER_INFO_TEXT,
                        'appType' => $this->payload['appType'] ?? 3,
                        'deviceId' => $this->payload['deviceId'],
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => $this->payload['code'],
                        'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    );
                    $this->sendResponse($uid,  $message,  $message['reply']);
                }
            }
            // $uid = $this->service->getRedis()->get("xhs:user:pc:{$userId}") ?? $this->service->getRedis()->get("xhs:user:wmprog:{$userId}");
            // if ($uid) {
            //     $message = array(
            //         'messageId' => $uid,
            //         'type' => WorkerEnum::WEB_GET_USER_INFO_TEXT,
            //         'appType' => $this->payload['appType'] ?? 3,
            //         'deviceId' => $this->payload['deviceId'],
            //         'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            //         'code' => $this->payload['code'],
            //         'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
            //     );
            //     $this->sendResponse($uid,  $message,  $message['reply']);
            // } else {
            //     $this->setLog('web客户端不存在:' .  $userId, 'user');
            // }
        } catch (\Exception $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }
}
