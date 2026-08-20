<?php

namespace app\common\workerman\rpa\handlers\xhs;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\wechat\AiWechat;
use app\common\service\sv\SvAccountPersistService;
use think\facade\Db;

class UserHandler extends BaseMessageHandler
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


            if ($this->msgType == WorkerEnum::RPA_USER_INFO) {
                $this->_updateUserInfoByDevice($content);
            } else if ($this->msgType == WorkerEnum::WEB_GET_USER_INFO) {
                $this->_getUserInfoByRpa($content);
            }
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e, 'user');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::USER_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function _getUserInfoByRpa(array $content)
    {

        //判断设备在不在线
        //不在线 返回不在线信息
        //在线 则发送指令到rap,
        //等待rpa回复 webws存在则 生成推送指令,不存在则不生产推送指令
        try {

            $device = $content['deviceId'] ?? ($this->payload['deviceId'] ?? '');
            if ($device === '') {
                $this->payload['reply'] = '设备码无效,无法获取账号信息';
                $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
                $this->sendError($this->connection, $this->payload);
                return;
            }
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
                //         'type' => WorkerEnum::TO_RPA_USER_INFO,
                //         'appVersion' => WorkerEnum::APP_VERSION,
                //         'appType' => 1,
                //         'code' => WorkerEnum::SUCCESS_CODE,
                //         'reply' => [
                //             'type' => WorkerEnum::TO_RPA_USER_INFO,
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
                    'type' => WorkerEnum::TO_RPA_USER_INFO,
                    'appVersion' => WorkerEnum::APP_VERSION,
                    'appType' => $this->payload['appType'] ?? 3,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => [
                        'type' => WorkerEnum::TO_RPA_USER_INFO,
                        'appType' => $this->payload['appType'] ?? 3,
                        'msg' => sprintf("获取设备%s用户信息", WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3)),
                        'deviceId' => $device
                    ]
                );

                //$this->sendAppExec($device, $uid, $this->payload['appType']);
                if (!isset($content['userId'])) {
                    $userId = \app\common\model\sv\SvDevice::where('device_code', $this->payload['deviceId'])->limit(1)->value('user_id') ?? 0;
                } else {
                    $userId = $content['userId'];
                }

                $this->service->getRedis()->set("xhs:getUser:{$device}", $userId);
                $this->sendResponse($uid, $message, $message['reply']);
                $this->setLog($message, 'user');
            }
        } catch (\Exception $e) {
            $this->setLog('_getUserInfoByRpa' . $e, 'error');
        }
    }
    private function _updateUserInfoByDevice(array $content)
    {

        try {
            $find = \app\common\model\sv\SvDevice::where('device_code', $this->payload['deviceId'])->findOrEmpty();
            $postData = [];
            if (isset($content['wechatDeviceCode']) && $content['wechatDeviceCode'] != '') {
                $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":wechat_code", $content['wechatDeviceCode']);

                if (!$find->isEmpty()) {
                    $find->wechat_device_code = $content['wechatDeviceCode'] ?? '';
                    $find->mode = 'root';
                    $find->update_time = time();
                    $find->save();
                }

                $wechat = AiWechat::where('device_code', $content['wechatDeviceCode'])
                    ->where('user_id', (int)($find->user_id ?? 0))
                    ->findOrEmpty();
                if (!$wechat->isEmpty() && trim((string)($wechat->wechat_id ?? '')) !== '') {
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
                    $this->_sendWeb([
                        'code' => WorkerEnum::WEB_GET_WECHAT_USER_INFO_FAIL,
                        'msg' => '获取账号信息失败,请先绑定个微,再重新获取'
                    ]);
                    return;
                }
            } else {
                if (!isset($content['xhsId']) || trim((string)$content['xhsId']) === '') {
                    $this->payload['reply'] = "获取账号信息失败,请重新获取";
                    $this->payload['code'] = WorkerEnum::WEB_GET_USER_INFO_FAIL;
                    $this->_sendWeb([
                        'code' => WorkerEnum::WEB_GET_USER_INFO_FAIL,
                        'msg' => '获取账号信息失败,请重新获取'
                    ]);
                    return;
                }
                if (!$find->isEmpty()) {
                    $find->mode = 'rpa';
                    $find->update_time = time();
                    $find->save();
                }

                $content['xhsId'] = str_replace(WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . '号：', '', $content['xhsId']);
                $this->payload['reply'] = '';

                $postData = array(
                    'avatar' => $this->toolUtil->base64ToImage($content),
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
                        'business_card' => 0,
                        'account_type' => $this->payload['appType'] ?? 3,
                        'is_verified' => $content['isVerified'] ?? 0,
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
            }

            $result = $this->_persistAccount($postData);
            if ($result['ok']) {
                $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
                $this->_sendWeb([
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'msg' => '获取账号信息成功'
                ]);
            } else {
                $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
                $this->_sendWeb([
                    'code' => WorkerEnum::DEVICE_ERROR_CODE,
                    'msg' => $result['msg'] !== '' ? $result['msg'] : '账号落库失败',
                ]);
            }
        } catch (\Throwable $e) {
            $this->setLog('_updateUserInfoByDevice' . $e, 'error');
            $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
            $this->_sendWeb([
                'code' => WorkerEnum::DEVICE_ERROR_CODE,
                'msg' => '账号更新异常:' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 服务端落库平台账号（HTTP/WS 共用）
     * @return array{ok: bool, msg: string}
     */
    private function _persistAccount(array $postData): array
    {
        try {
            if (($this->payload['code'] ?? 0) != WorkerEnum::SUCCESS_CODE) {
                return ['ok' => false, 'msg' => '账号解析未成功,跳过落库'];
            }
            if (empty($postData['account']) || isset($postData['msg'])) {
                return ['ok' => false, 'msg' => '账号数据不完整,无法落库'];
            }

            $userId = (int)($this->service->getRedis()->get("xhs:getUser:" . $this->payload['deviceId']) ?: 0);
            if ($userId <= 0) {
                $userId = (int)(\app\common\model\sv\SvDevice::where('device_code', $this->payload['deviceId'])->value('user_id') ?? 0);
            }
            if ($userId <= 0) {
                $this->setLog('账号落库跳过:未找到归属用户', 'user');
                return ['ok' => false, 'msg' => '账号落库跳过:未找到归属用户'];
            }

            $ok = SvAccountPersistService::upsertFromRpa($userId, $postData);
            if ($ok) {
                return ['ok' => true, 'msg' => '获取账号信息成功'];
            }
            $err = SvAccountPersistService::getLastError();
            return ['ok' => false, 'msg' => $err !== '' ? $err : '账号落库失败'];
        } catch (\Throwable $e) {
            $msg = '账号落库异常:' . $e->getMessage();
            $this->setLog($msg, 'error');
            return ['ok' => false, 'msg' => $msg];
        }
    }

    /**
     * 通知 PC/小程序（对齐 AppCompletedHandler：type=appCompleted）
     */
    private function _sendWeb(array $content): void
    {
        try {
            $deviceId = trim((string)($this->payload['deviceId'] ?? ''));
            if ($deviceId === '') {
                $this->setLog('web推送跳过:设备码为空', 'user');
                return;
            }

            $code = (int)($content['code'] ?? $this->payload['code'] ?? WorkerEnum::SUCCESS_CODE);
            $msg = (string)($content['msg'] ?? '');
            $this->_rememberPersistResult($deviceId, $code, $msg);
            $reply = [
                'code' => $code,
                'msg' => $msg,
            ];

            $userId = (int)($this->service->getRedis()->get("xhs:getUser:" . $deviceId) ?: 0);
            if ($userId <= 0) {
                $userId = (int)(\app\common\model\sv\SvDevice::where('device_code', $deviceId)->value('user_id') ?? 0);
            }
            if ($userId <= 0) {
                $this->setLog('web推送跳过:未找到归属用户', 'user');
                return;
            }

            $this->payload['code'] = $code;
            $sources = WorkerEnum::WS_SOURCES;
            $sent = false;
            foreach ($sources as $source) {
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                if ($uid) {
                    $message = [
                        'messageId' => 0,
                        'type' => 'appCompleted',
                        'appType' => $this->payload['appType'] ?? 3,
                        'deviceId' => $deviceId,
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => $code,
                        'reply' => json_encode($reply, JSON_UNESCAPED_UNICODE),
                    ];
                    $this->setLog($message, 'user');
                    $this->sendResponse((string)$uid, $message, $message['reply']);
                    $sent = true;
                }
            }
            if (!$sent) {
                $this->setLog("web推送跳过:用户{$userId}的PC/小程序均未在线", 'user');
            }
        } catch (\Throwable $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }

    /**
     * 记录落库结果，供 707 appCompleted 判断是否再推成功
     */
    private function _rememberPersistResult(string $deviceId, int $code, string $msg): void
    {
        try {
            $appType = (int)($this->payload['appType'] ?? 3);
            $ok = $code === WorkerEnum::SUCCESS_CODE ? 1 : 0;
            $this->service->getRedis()->set(
                SvAccountPersistService::persistResultKey($deviceId, $appType),
                json_encode(['ok' => $ok, 'code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE),
                SvAccountPersistService::PERSIST_RESULT_TTL
            );
        } catch (\Throwable $e) {
            $this->setLog('记录落库结果失败:' . $e->getMessage(), 'error');
        }
    }
}
