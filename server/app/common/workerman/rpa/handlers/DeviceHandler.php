<?php

namespace app\common\workerman\rpa\handlers;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\service\device\RpaDeviceDispatchService;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\Support\DeviceAuthClient;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;

class DeviceHandler extends BaseMessageHandler
{
    protected array $deviceInfo = [];
    protected array $content = [];
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->content = $content;
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->_checkDevice();


            if ($this->msgType == WorkerEnum::RPA_DEVICE_INFO) {
                $this->_updateDeviceInfo($content);
            } else if ($this->msgType == WorkerEnum::WEB_BIND_DEVICE) {

                $this->_getDeviceInfo($content);
            }
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'device');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }
    private function _checkDevice()
    {
        try {
            $payload = array(
                'device_code' => $this->payload['deviceId'],
                'platform' => 3,
                'code' => $this->content['code'] ?? '',
            );

            $response = DeviceAuthClient::checkSvDevice($payload);
            $this->setLog($response, 'device');
            if ((int)$response['code'] === 10000) {
                $this->deviceInfo = $response['data'] ?? [];
                return;
            }

            if (DeviceAuthClient::isTimeoutResponse($response)) {
                $this->setLog([
                    'msg' => '设备鉴权超时',
                    'deviceId' => $this->payload['deviceId'] ?? '',
                    'response' => $response,
                ], 'error');
                $this->payload['reply'] = '设备鉴权超时，请稍后重试';
                $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
                $this->sendError($this->connection, $this->payload);
                return;
            }

            $this->payload['reply'] = "设备未找到";
            $this->payload['code'] = WorkerEnum::DEVICE_NOT_FOUND;
            $this->sendError($this->connection, $this->payload);
        } catch (\Exception $e) {
            $this->setLog([
                'msg' => '设备鉴权失败',
                'deviceId' => $this->payload['deviceId'] ?? '',
                'error' => $e->getMessage(),
            ], 'error');
            $this->payload['reply'] = '设备鉴权失败，请稍后重试';
            $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
            $this->sendError($this->connection, $this->payload);
        }
    }

    private function _getDeviceInfo(array $content)
    {
        try {
            $device = $this->deviceInfo;

            $worker = $this->service->getWorker();
            if (isset($worker->devices[$this->payload['deviceId']])) {
                $find = SvDevice::where('device_code', $content['deviceId'])->limit(1)->find();
                if (empty($find)) {
                    $this->payload['reply'] = '新增设备';
                    $this->setLog($this->payload, 'device');
                    $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
                    $this->payload['reply'] = array(
                        'deviceId' => $device['DeviceId'],
                        "deviceModel" => $device['DeviceModel'],
                        'sdkVersion' => $device['SdkVersion'],
                        'online' => 1
                    );
                } else {

                    //更新设备状态
                    SvDevice::where('device_code', $content['deviceId'])->update([
                        'status' => 1,
                        'update_time' => time()
                    ]);
                    $this->payload['reply'] = '设备已存在';
                    $this->payload['code'] = WorkerEnum::DEVICE_HAS_BIND;
                }

                $uid = $worker->devices[$this->payload['deviceId']] ?? '';
                if ($uid == '') {
                    $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
                    $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                    $this->sendError($this->connection,  $this->payload);
                    return;
                }
                $message = array(
                    'messageId' => $uid,
                    'deviceId' => $device['DeviceId'],
                    'type' => WorkerEnum::TO_RPA_DEVICE_INFO,
                    'appVersion' => WorkerEnum::APP_VERSION,
                    'appType' => $this->payload['appType'] ?? 3,
                    'code' => WorkerEnum::SUCCESS_CODE,
                    'reply' => [
                        'type' => WorkerEnum::TO_RPA_DEVICE_INFO,
                        'msg' => '获取设备信息',
                        'deviceId' => $device['DeviceId'],
                    ]
                );
                //$this->sendResponse($uid, $message, $message['reply']);
                if ($this->payload['code'] !== WorkerEnum::SUCCESS_CODE) {
                    $this->sendError($this->connection,  $this->payload);
                } else {
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                }

                $this->setLog($this->payload, 'device');
                return;
            } else {
                $this->payload['reply'] = "设备不在线";
                $this->payload['code'] = WorkerEnum::DEVICE_OFFLINE;
                $this->sendError($this->connection,  $this->payload);
                $this->setLog($this->payload, 'device');
                return;
            }
        } catch (\Exception $e) {
            $this->setLog('_getDeviceInfo' . $e, 'error');
        }
    }

    private function _updateDeviceInfo(array $content)
    {
        try {
            $deviceId = trim((string)($content['deviceId'] ?? $this->payload['deviceId'] ?? ''));
            if ($deviceId === '') {
                throw new \InvalidArgumentException('设备ID不能为空');
            }

            $this->payload['deviceId'] = $deviceId;

            if (RpaDeviceDispatchService::shouldForceUnbind($deviceId)) {
                $traceId = RpaDeviceDispatchService::getTraceId($deviceId);
                if ($traceId === '') {
                    $traceId = RpaDeviceDispatchService::newTraceId();
                }
                $unbindPayload = RpaDeviceDispatchService::buildPayload(
                    $deviceId,
                    WorkerEnum::TO_RPA_DEVICE_UNBIND,
                    [
                        'reason' => 'reconnect_force',
                        'user_id' => 0,
                        'ts' => time(),
                    ]
                );
                $this->service->send($this->uid, $unbindPayload);
                RpaDeviceDispatchService::markPendingUnbind($deviceId, [
                    'reason' => 'reconnect_force',
                    'user_id' => 0,
                    'ts' => time(),
                    'trace_id' => $traceId,
                ]);
                $workerId = (int)($this->service->getWorker()->id ?? 0);
                RpaDeviceDispatchService::scheduleForceClose(
                    $deviceId,
                    RpaDeviceDispatchService::FORCE_CLOSE_DELAY_SEC,
                    '设备已解绑超时未断开',
                    $this->uid,
                    $workerId,
                    ['trace_id' => $traceId]
                );
                RpaDeviceDispatchService::logUnbind('unbind_reconnect_force', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceId,
                    'uid' => $this->uid,
                    'worker_id' => $workerId,
                    'delay_sec' => RpaDeviceDispatchService::FORCE_CLOSE_DELAY_SEC,
                    'reason' => 'reconnect_force',
                    'msg' => '重连命中强制解绑，已下发 1212 并调度延迟踢线',
                    'result' => 'ok',
                ]);
                return;
            }

            $this->payload['reply'] = '设备信息更新成功';
            $this->payload['code'] = WorkerEnum::SUCCESS_CODE;

            $this->bind($this->uid, $this->payload);

            $worker = $this->service->getWorker();
            if (!isset($worker->uidConnections[$this->uid])) {
                throw new \Exception('设备未连接');
            }
            if ($worker->uidConnections[$this->uid]->initial == 0) {
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                //发送当前执行的app指令
                //$this->sendCurrentApp($this->uid, $this->payload);

            }

            $device = $this->syncDeviceOnlineStatus($deviceId, !empty($content['serverStatus']));
            $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
            if (!empty($device['user_id'])) {
                $this->_sendWeb((int)$device['user_id'], [
                    'type' => WorkerEnum::WEB_DEVICE_ONLINE_TEXT,
                    'deviceId' => $deviceId,
                    'code' => WorkerEnum::DEVICE_ONLINE,
                    'msg' => '设备已连接'
                ]);
            }
            $this->setLog($this->payload, 'device');
        } catch (\Throwable $e) {
            $this->setLog('_updateDeviceInfo' . $e, 'error');
        }
    }

    private function syncDeviceOnlineStatus(string $deviceId, bool $online): ?array
    {
        try {
            $find = SvDevice::where('device_code', $deviceId)->limit(1)->findOrEmpty();
            if ($find->isEmpty()) {
                return null;
            }

            $find->status = $online ? 1 : 0;
            $find->update_time = time();
            if (!$find->save()) {
                $this->setLog([
                    'msg' => '设备在线状态保存失败',
                    'deviceId' => $deviceId,
                    'online' => $online,
                ], 'error');
            }

            return $find->toArray();
        } catch (\Throwable $e) {
            $this->setLog([
                'msg' => '设备在线状态同步失败',
                'deviceId' => $deviceId,
                'online' => $online,
                'error' => $e->getMessage(),
            ], 'error');
            return null;
        }
    }

    private function bind(string $uid, array $payload)
    {
        try {
            $worker = $this->service->getWorker();;

            if (isset($worker->uidConnections[$uid])) {

                // foreach ($worker->uidConnections as $connection) {
                //     if($connection->deviceid == $payload['deviceId'] && $connection->uid !== $uid){
                //         $this->setLog('删除设备旧的socket连接, 设备号:' . $connection->deviceid . ', uid:' . $connection->uid . ', name:' . $connection->name, 'ws');
                //         $connection->close();
                //     }
                // }

                $deviceId = trim((string)($payload['deviceId'] ?? ''));
                $appVersion = $payload['appVersion'] ?? WorkerEnum::APP_VERSION;
                $payload['deviceId'] = $deviceId;
                if ($deviceId !== '') {
                    $oldUid = $this->service->getRepository()->getDeviceUid($deviceId);
                    if ($oldUid !== null && $oldUid !== '' && $oldUid !== $uid) {
                        $this->service->closeConnection($oldUid, 'device socket replaced');
                    }
                }

                $worker->uidConnections[$uid]->deviceid = $deviceId;
                $worker->uidConnections[$uid]->apptype = $payload['appType'] ?? 3;
                $worker->uidConnections[$uid]->messageid = $payload['messageId'] ?? '';
                $worker->uidConnections[$uid]->appversion = $appVersion;
                $worker->uidConnections[$uid]->clientType = 'device';
                $worker->uidConnections[$uid]->name =  'device:' . $deviceId;
                $worker->uidConnections[$uid]->initial = 0;
                $worker->uidConnections[$uid]->isMsgRunning = 0;
                $worker->uidConnections[$uid]->lastHeartbeat = time();

                $worker->devices[$deviceId] = $uid;
                $worker->appType = $payload['appType'] ?? 3;

                $this->service->getRepository()->markDeviceOnline($deviceId, $uid, (int)$worker->id, $appVersion);

                $this->service->setWorker($worker);
                $this->registerChannelListener($this->connection, $deviceId);
                $this->setLog('设备绑定socket连接, workerId:' . $worker->id . ', 设备号:' . $payload['deviceId'] . ', uid:' . $uid . ', name:' . $worker->uidConnections[$uid]->name, 'device');
                $this->setLog('设备绑定socket连接, workerId:' . $worker->id . ', 设备号:' . $payload['deviceId'] . ', uid:' . $uid . ', name:' . $worker->uidConnections[$uid]->name, 'heart');

                $this->service->getRedis()->set("xhs:device:" . $payload['deviceId'] . ":taskStatus", json_encode([
                    'taskStatus' => 'standby',
                    'taskType' => 'addDevice',
                    'msg' => '添加设备',
                    'time' => date('Y-m-d H:i:s', time()),
                    'scene' => 'xhs',
                ], JSON_UNESCAPED_UNICODE));

                $this->sendReconnectNotification($deviceId);
            }
        } catch (\Throwable $e) {
            $this->setLog('bind' . $e, 'error');
        }
    }

    private function sendReconnectNotification(string $deviceId): void
    {
        try {
            $find = SvDevice::where('device_code', $deviceId)->findOrEmpty();
            if (!$find->isEmpty()) {
                $this->toolUtil->sendNotification((int)$find->user_id, $find->device_name, '重连');
            }
        } catch (\Throwable $e) {
            $this->setLog([
                'msg' => '设备重连通知发送失败',
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    private function _sendWeb(int $userId, array $content)
    {

        try {

            if ($userId) {
                $sources = WorkerEnum::WS_SOURCES;
                foreach ($sources as $source) {
                    $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                    if ($uid) {
                        $message = array(
                            'messageId' => $uid,
                            'type' => WorkerEnum::WEB_DEVICE_ONLINE_TEXT,
                            'appType' => 3,
                            'deviceId' => $this->payload['deviceId'],
                            'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                            'code' => $this->payload['code'] ?? WorkerEnum::SUCCESS_CODE,
                            'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                        );
                        $this->sendResponse($uid,  $message,  $message['reply']);
                    }
                }
                // $uid = $this->service->getRedis()->get("xhs:user:pc:{$userId}") ?? $this->service->getRedis()->get("xhs:user:wmprog:{$userId}");
                // if ($uid) {
                //     $message = array(
                //         'messageId' => $uid,
                //         'type' => WorkerEnum::WEB_DEVICE_ONLINE_TEXT,
                //         'appType' => 3,
                //         'deviceId' => $this->payload['deviceId'],
                //         'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                //         'code' => $this->payload['code'],
                //         'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                //     );
                //     $this->sendResponse($uid,  $message,  $message['reply']);
                // } else {
                //     $this->setLog('web客户端不存在:' .  $userId, 'error');
                // }
            }
        } catch (\Throwable $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }
}
