<?php

declare(strict_types=1);

namespace app\common\workerman\rpa;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use think\cache\driver\Redis;
use think\facade\Log;
use Workerman\Timer;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\workerman\rpa\WorkerEnum;
use Channel\Client as ChannelClient;

class RpaSocketService
{
    protected $worker;
    protected $HEARTBEAT_TIME = '3600'; //心跳时间，后端主动关闭客户端

    protected $whitelist = array(
        1,
        11,
        'bindSocket',
        'ping',
        21,
        25,
        26,
        91
    );
    protected $redis = null;
    protected $error_msg = '';
    protected $isWriteLog = true; //是否写入日志;
    public $uidConnections = array(); // 存储连接信息
    //protected $log = array();
    public function __construct(Worker $object)
    {
        $this->worker = $object;
        date_default_timezone_set('PRC');
        // 只做基础初始化，进程级别的初始化移至onWorkerStart
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->_connRedis();
    }


    public function onMessage(TcpConnection $connection, $data)
    {
        // 更新消息时间避免断开
        $connection->lastMessageTime = time();
        //客户端与后端消息链接唯一标识
        $this->setLog('新消息:' . $data);

        try {
            $uid = $connection->uid;
            $this->setLog('onMessage uid:' . $uid);
            $message = json_decode($data, true);
            // 验证请求
            [$type, $payload] = $this->verifyClientRequest($connection, $message);
            if ($type === false) { //验证失败，关闭此连接
                $this->setLog('验证失败:' . $data, 'error');
                return;
            }
            if ($uid) {
                $handler = match ($type) {
                    'ping' => new \app\common\workerman\rpa\handlers\HeartBeatHandler($this), #心跳

                    WorkerEnum::RPA_DEVICE_INFO => new \app\common\workerman\rpa\handlers\DeviceHandler($this), #获取设备信息、状态
                    WorkerEnum::RPA_USER_INFO => new \app\common\workerman\rpa\handlers\xhs\UserHandler($this), #小红书用户信息
                    WorkerEnum::RPA_PRIVATE_MESSAGE => new \app\common\workerman\rpa\handlers\xhs\PrivateMessageHandler($this), #私信列表信息
                    WorkerEnum::RPA_PUBLISHED_POST_STATUS => new \app\common\workerman\rpa\handlers\xhs\InteractiveMessageHandler($this), #已发布内容状态、收藏、点赞
                    WorkerEnum::RPA_SEND_CARD_STATUS => new \app\common\workerman\rpa\handlers\xhs\CardHandler($this), #发送私信消息
                    WorkerEnum::RPA_CARD_INFO => new \app\common\workerman\rpa\handlers\xhs\CardHandler($this), #发送卡片
                    WorkerEnum::RPA_NEW_PRIVATE_MESSAGE => new \app\common\workerman\rpa\handlers\xhs\MessageHandler($this), #最新私信消息
                    WorkerEnum::RPA_TASK_EXEC_STATUS => new \app\common\workerman\rpa\handlers\TaskHandler($this), #任务执行状态回复
                    WorkerEnum::RPA_DEVICE_INIT => new \app\common\workerman\rpa\handlers\CompletedHandler($this), #设备初始化完成
                    WorkerEnum::RPA_MSG_REPLY_STATUS => new \app\common\workerman\rpa\handlers\MsgReplyHandler($this), #正在回复消息状态
                    WorkerEnum::RPA_MSG_REPLY_COMPLETED => new \app\common\workerman\rpa\handlers\MsgReplyHandler($this), #设备回复消息完成
                    WorkerEnum::RPA_MEDIA_STATUS => new \app\common\workerman\rpa\handlers\MediaStatusHandler($this), #图文视频发布状态更新

                    WorkerEnum::WEB_SOCKET_STATUS_TEXT => new \app\common\workerman\rpa\handlers\WebWorkerHandler($this), #web端绑定socket
                    WorkerEnum::WEB_BIND_DEVICE_TEXT => new \app\common\workerman\rpa\handlers\DeviceHandler($this), #web端添加设备
                    WorkerEnum::WEB_GET_USER_INFO_TEXT => new \app\common\workerman\rpa\handlers\xhs\UserHandler($this), #web端获取设备账号信息
                    WorkerEnum::WEB_PRIVATE_MESSAGE_LIST_TEXT => new \app\common\workerman\rpa\handlers\xhs\PrivateMessageHandler($this), #web端获取私信列表
                    WorkerEnum::WEB_SEND_PRIVATE_MESSAGE_TEXT => new \app\common\workerman\rpa\handlers\xhs\MessageHandler($this), #最新私信消息
                    WorkerEnum::WEB_CARDS_TEXT => new \app\common\workerman\rpa\handlers\xhs\CardHandler($this), #web端获取名片列表
                    WorkerEnum::WEB_POST_STATUS_LIST_TEXT => new \app\common\workerman\rpa\handlers\xhs\InteractiveMessageHandler($this), #已发布内容状态、收藏、点赞
                    WorkerEnum::WEB_INIT_CHECK_TEXT => new \app\common\workerman\rpa\handlers\CheckInitHandler($this), #设备初始化检测
                    WorkerEnum::WEB_SEND_CARD_TEXT => new \app\common\workerman\rpa\handlers\xhs\CardHandler($this), #web端获取名片列表

                    WorkerEnum::RPA_SPH_TASK_SEND => new \app\common\workerman\rpa\handlers\sph\TaskSendHandler($this), #任务发送
                    WorkerEnum::RPA_SPH_TASK_RECORD_SAVE => new \app\common\workerman\rpa\handlers\sph\TaskRecordSaveHandler($this), #任务记录保存
                    WorkerEnum::RPA_SPH_TASK_PAUSE => new \app\common\workerman\rpa\handlers\sph\TaskPauseHandler($this), #任务暂停
                    WorkerEnum::RPA_SPH_TASK_RESUME => new \app\common\workerman\rpa\handlers\sph\TaskRecoveryHandler($this), #任务恢复
                    WorkerEnum::RPA_SPH_TASK_CANCEL => new \app\common\workerman\rpa\handlers\sph\TaskDeleteHandler($this), #任务删除
                    WorkerEnum::RPA_SPH_TASK_COMPLETED => new \app\common\workerman\rpa\handlers\sph\TaskCompletedHandler($this), #任务完成
                    WorkerEnum::RPA_SPH_TASK_RECEIVEW => new \app\common\workerman\rpa\handlers\sph\TaskReceivedHandler($this), #任务接收

                    WorkerEnum::RPA_GET_ACCOUNT_APP_SEND => new \app\common\workerman\rpa\handlers\device\AppSendHandler($this), #正在发送指令
                    WorkerEnum::RPA_GET_ACCOUNT_APP_EXEC => new \app\common\workerman\rpa\handlers\device\AppExecHandler($this), #手机正在处理指令
                    WorkerEnum::RPA_GET_ACCOUNT_APP_OPEN => new \app\common\workerman\rpa\handlers\device\AppOpenHandler($this), #打开app
                    WorkerEnum::RPA_GET_ACCOUNT_APP_PERSONAL_CENTER => new \app\common\workerman\rpa\handlers\device\AppPersonalCenterHandler($this), #打开个人中心
                    WorkerEnum::RPA_GET_ACCOUNT_APP_INFO => new \app\common\workerman\rpa\handlers\device\AppInfoHandler($this), #获取账号信息
                    WorkerEnum::RPA_GET_ACCOUNT_APP_DATA_SEND => new \app\common\workerman\rpa\handlers\device\AppDataSendHandler($this), #正在等待数据返回
                    WorkerEnum::RPA_GET_ACCOUNT_APP_COMPLETED => new \app\common\workerman\rpa\handlers\device\AppCompletedHandler($this), #应用执行完成



                    WorkerEnum::RPA_TAKE_OVER_TASK_RESULT_SAVE => new \app\common\workerman\rpa\handlers\TakeOverTaskResultSaveHandler($this), #接管任务结果保存

                    WorkerEnum::RPA_ACTIVE_TASK_RESULT_SAVE => new \app\common\workerman\rpa\handlers\ActiveTaskResultSaveHandler($this), #活动任务结果保存

                    WorkerEnum::RPA_DEVICE_APP_EXEC => new \app\common\workerman\rpa\handlers\DeviceAppStartExecHandler($this), #设备应用执行
                    WorkerEnum::RPA_GET_WECHAT_DEVICE_CODE => new \app\common\workerman\rpa\handlers\GetWechatDeviceCodeHandler($this), #获取微信设备验证码

                    // ...其他case对应的handler...
                    default => new \app\common\workerman\rpa\handlers\DefaultHandler($this)
                };
                $handler->handle($connection, $uid, $payload);
            } else { //查询不到用户信息，关闭此连接
                $this->onClose($connection);
            }
        } catch (\Exception $e) {
            $this->setLog('onMessage:' . $e, 'error');
            $message['code'] = $e->getCode();
            $message['reply'] = $e->getMessage();
            $this->sendError($uid, $message);
            return;
        }
    }

    private function verifyClientRequest(TcpConnection $connection, $message)
    {

        $type = ctype_digit((string)$message['type']) ? intval($message['type']) : $message['type'];
        $payload = $message;

        try {
            if (isset($payload['deviceId']) && !empty(trim($payload['deviceId']))) {
                $payload['deviceId'] = trim($payload['deviceId']);
            }
            if ($type === 'ping') { // 心跳消息不处理在线以及验证AccessToken
                return [$type, $payload];
            }

            // 验证参数
            if (!$message || !isset($message['type']) || !isset($message['content'])) {
                throw new \Exception('无效请求', WorkerEnum::INVALID_REQUEST);
            }

            // 验证设备
            if (!isset($payload['deviceId'])) {
                throw new \Exception('无效请求,设备参数不存在', WorkerEnum::INVALID_REQUEST_NOFUND_DEVICE);
            }
            if (in_array($type, [1, 'addDevice'])) {
                //验证设备授权是否存在
                if (!$this->checkDevice($connection, $payload)) {
                    throw new \Exception($this->error_msg, WorkerEnum::DEVICE_NOT_FOUND);
                }
            }

            //判断设备初始化是否完成,未完成禁止主动获取设备相关信息
            if (!in_array($type, $this->whitelist)) {
                if (empty(trim($payload['deviceId']))) {
                    throw new \Exception('设备参数无效',  WorkerEnum::DEVICE_INVALID_REQUEST);
                }
                // 检查设备是否在线
                if (!isset($this->worker->devices[$payload['deviceId']])) {
                    throw new \Exception('设备已离线,请重新连接', WorkerEnum::DEVICE_OFFLINE);
                }

                // if(!$this->checkDeviceStatus($payload)){
                //     throw new \Exception('设备正在连接中，稍后再试',  WorkerEnum::DEVICE_INIT_NOT_COMPLETE);
                // }
            }

            if (isset($payload['content'])) {
                $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
                if ($content) {
                    if (isset($content['deviceId'])) {
                        $content['deviceId'] = trim($content['deviceId']);
                    }
                    $payload['content'] = $content;
                    //当前设备执行的app
                    // $app = SvDeviceRpa::where('device_code', $payload['deviceId'])->where('status', 1)->findOrEmpty();
                    // if(!$app->isEmpty()){
                    //     if($type == 'getUserInfo' && (int)$app->app_type !== 3){
                    //         throw new \Exception('当前设备正在执行其他app,暂时无法获取小红书账号信息', WorkerEnum::DEVICE_EXEC_OTHER_APP_ERROR_CODE);
                    //     }
                    // }
                }
            }

            return [$type, $payload];
        } catch (\Exception $e) {
            $this->setLog('verifyClientRequest:' . $e, 'error');
            $message['code'] = $e->getCode();
            $message['reply'] = $e->getMessage();
            $this->sendError($connection->uid, $message);
            return [false, []];
        }
    }


    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect(TcpConnection $connection)
    {
        try {
            $connection->maxSendBufferSize = 1024 * 1024 * 10;
            $connection->maxPackageSize = 1024 * 1024 * 10;
            // 初始化连接的最后通信时间
            $connection->lastMessageTime = time();
            $connection->onWebSocketConnect = function ($connection, $http_header) {
                $this->setLog('新请求header:' . $http_header);
                // 存客户端与websocket的映射，唯一连接标识（！！！关键）
                if (!isset($connection->uid)) {
                    $connection->uid = 'xhs_' . generate_unique_task_id();
                    $connection->lastMessageTime = time(); //更新消息时间避免,断开
                    $connection->deviceid = '';
                    $connection->apptype = '';
                    $connection->appversion = WorkerEnum::APP_VERSION;
                    $connection->messageid = 0;
                    $connection->userid = 0;
                    $connection->messageCount = 0;
                    $connection->clientType = '';
                    $connection->initial = 0; //初始化标志 1初始完成
                    $connection->name = '';
                    $connection->timerId = ''; //定时器id
                    $connection->crontabId = '';
                    $connection->testCrontabId = '';
                    $connection->isMsgRunning = 0;
                    $this->worker->uidConnections[$connection->uid] = $connection;
                    //$this->redis->set("xhs:connection:" . $connection->uid, $this->worker->id);
                    $this->setLog('新socket链接:' . $connection->uid);
                    return;
                }
            };
        } catch (\Exception $e) {
            $this->setLog('onConnect:' . $e, 'error');
        }
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose(TcpConnection $connection)
    {
        try {
            // 收集连接信息用于日志
            $ip = $connection->getRemoteIp();
            $uid = isset($connection->uid) ? $connection->uid : 'unknown';
            $name = isset($connection->name) ? $connection->name : '';
            $reason = isset($connection->closeReason) ? $connection->closeReason : '正常关闭';

            // 详细的关闭日志
            $this->setLog('连接关闭 [IP:' . $ip . ', UID:' . $uid . ', 名称:' . $name . ', 原因:' . $reason . ']', 'info');

            //代表用户下线，清除用户信息
            if (isset($connection->uid)) {
                $this->_unBind($connection->uid);
            }

            // 清理连接相关的所有属性
            unset($connection->uid, $connection->lastMessageTime, $connection->deviceid, $connection->closeReason);
        } catch (\Exception $e) {
            $this->setLog('处理连接关闭时发生异常: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        try {
            // 获取客户端IP和其他连接信息
            $clientIp = $connection->getRemoteIp();
            $clientPort = $connection->getRemotePort();

            // 构建详细的错误信息
            $errorInfo = array(
                'code' => $code,
                'error_message' => $msg,
                'client_ip' => $clientIp,
                'client_port' => $clientPort,
                'uid' => $connection->uid ?? 'unknown',
                'connection_status' => $connection->getStatus(),
                'time' => date('Y-m-d H:i:s')
            );

            // 根据错误码提供更具体的错误描述
            $errorDesc = $this->getErrorDescription($code);
            $errorInfo['error_description'] = $errorDesc;

            $this->setLog('WebSocket连接错误: ' . json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'error');
        } catch (\Exception $e) {
            $this->setLog($e, 'error');
        }
    }

    /**
     * 根据错误码获取详细的错误描述
     * @param int $code 错误码
     * @return string 错误描述
     */
    private function getErrorDescription(int $code): string
    {
        $errorDescriptions = [
            1001 => '连接被对端关闭',
            1002 => '协议错误',
            1003 => '收到了不支持的数据类型',
            1005 => '连接关闭时没有状态码',
            1006 => '连接异常关闭',
            1007 => '数据格式错误',
            1008 => '消息内容违反策略',
            1009 => '消息过大',
            1010 => '客户端需要扩展',
            1011 => '服务端内部错误',
            1012 => '服务重启',
            1013 => '临时故障',
            1014 => 'TLS握手失败',
            1015 => 'TLS握手失败',
        ];

        return $errorDescriptions[$code] ?? '未知错误';
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        // 记录进程ID
        $this->worker = $worker;
        $this->setLog("Worker[{$worker->id}] 进程启动, PID: " . getmypid(), 'info');

        // 初始化进程级别的连接池
        $this->worker->uidConnections = array();
        $this->worker->devices = array();

        // 重新连接Redis
        $this->_connRedis();

        // 添加心跳检测定时器
        // Timer::add(10, function () use ($worker) {
        //     $timeNow = time();
        //     foreach ($worker->connections as $connection) {
        //         if (isset($connection->lastMessageTime) && $timeNow - $connection->lastMessageTime > $this->HEARTBEAT_TIME) {
        //             $connection->close();
        //         }
        //     }
        // });
    }

    public function onWorkerReload($worker)
    {

        $this->setLog('onWorkerReload', 'error');
    }

    public function onBufferFull(TcpConnection $connection)
    {
        $this->setLog('缓冲区已满，不能发送', 'error');
    }

    public function onBufferDrain(TcpConnection $connection)
    {
        // 缓冲区清空时的处理，可以在这里重置
        $connection->sendBufferSize = 0;
        $connection->maxSendBufferSize = 1024 * 1024 * 10; // 恢复默认值

        $this->setLog('缓冲区清空，并继续发送', 'error');
    }

    public function sendSuccess($uid, $payload)
    {
        try {
            $payload['code'] = WorkerEnum::SUCCESS_CODE;
            $this->send($uid, $payload);
        } catch (\Exception $e) {
            $this->setLog('sendSuccess:' . $e, 'error');
        }
    }
    public function sendError($uid, $payload)
    {
        try {
            $code = $payload['code'] ?? WorkerEnum::ERROR_CODE;
            $reply = array(
                'code' => $code,
                'msg' => $payload['reply'] ?? (WorkerEnum::getMessage($code) ??  '指令有误'),
                'deviceId' => $payload['deviceId'] ?? '',
            );

            $payload = array(
                'code' =>   WorkerEnum::ERROR_CODE,
                'reply' => $reply,
                'appType' => $payload['appType'] ?? '',
                'type' => $payload['type'] ?? 'error',
                'messageId' => $uid,
                'deviceId' => $payload['deviceId'] ?? '',
                'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            );
            $this->send($uid, $payload);
        } catch (\Exception $e) {
            $this->setLog('sendError:' . $e, 'error');
        }
    }

    private function sendWeb($content)
    {
        try {
            $find = SvDevice::where('device_code', $content['deviceId'])->limit(1)->find();
            if (empty($find)) {
                $this->setLog('设备不存在:' .  $content['deviceId'], 'user');
                return;
            }

            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $uid = $this->redis->get("xhs:user:{$source}:{$find['user_id']}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => $content['type'],
                        'appType' => $content['appType'] ?? 3,
                        'deviceId' => $content['deviceId'],
                        'appVersion' => $content['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => $content['code'],
                        'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    );
                    $this->setLog($message, 'user');
                    $this->send($uid,  $message);
                }
            }


            // $uid = $this->redis->get("xhs:user:pc:{$find['user_id']}") ?? $this->redis->get("xhs:user:wmprog:{$find['user_id']}");
            // if ($uid) {
            //     $message = array(
            //         'messageId' => $uid,
            //         'type' => $content['type'],
            //         'appType' => $content['appType'] ?? 3,
            //         'deviceId' => $content['deviceId'],
            //         'appVersion' => $content['appVersion'] ?? WorkerEnum::APP_VERSION,
            //         'code' => $content['code'],
            //         'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
            //     );
            //     $this->setLog($message, 'user');
            //     $this->send($uid,  $message);
            // } else {
            //     $this->setLog('web客户端不存在:' . $find['user_id'], 'user');
            // }
        } catch (\Exception $e) {
            $this->setLog('sendWeb:' . $e, 'error');
        }
    }

    public function send($uid, $payload)
    {
        try {
            $content = array(
                'appType' => $payload['appType'] ?? 3,
                'messageId' => 0,
                'type' => $payload['type'],
                'content' => !is_array($payload['reply']) ? $payload['reply'] : json_encode($payload['reply'],  JSON_UNESCAPED_UNICODE),
                'deviceId' => $payload['deviceId'] ?? '',
                'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'code' => $payload['code'] ?? WorkerEnum::SUCCESS_CODE,
                'action' => 'send'
            );
            if ($content['content'] == null) {
                $content['content'] = json_encode($payload['content'],  JSON_UNESCAPED_UNICODE);
            }
            $this->setLog('回复内容: ' . json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'send');

            // 已经处理请求数
            if (isset($this->worker->uidConnections[$uid])) {
                $connection = $this->worker->uidConnections[$uid];
                $connection->messageCount += 1;
                $connection->send(json_encode($content, JSON_UNESCAPED_UNICODE));

                $this->setLog("正在向: {$connection->clientType} 端发送消息", 'send');
                $this->setLog('name ' . $connection->name . ' uid:' . $connection->uid . '  init:' . $connection->initial, 'send');
                $this->setLog('发送完成', 'send');

                // if(isset($payload['deviceId']) && !empty($payload['deviceId'])){
                //     $this->getRedis()->set("xhs:device:" . $payload['deviceId'] . ":taskStatus", json_encode([
                //         'taskStatus' => 'standby',
                //         'taskType' => 'send',
                //         'msg' => '发送完成',
                //         'time' => date('Y-m-d H:i:s', time()),
                //     ], JSON_UNESCAPED_UNICODE));
                // }
            } else {
                $this->setLog('uid 未找到: ' . $uid, 'error');
                return false;
            }
        } catch (\Exception $e) {
            $this->setLog('send:' . $e, 'error');
            $this->setLog($payload, 'error');
            return false;
        }
        $this->setLog("\n\n---------------------------");
        return true;
    }
    public function sendChannelMessage(string $deviceId, array $data, string $targetProcess = 'device'): void
    {
        $channel = "{$targetProcess}.{$deviceId}.message";
        ChannelClient::publish($channel, [
            'data' => is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data
        ]);
    }
    private function checkDevice(TcpConnection $connection, $payload)
    {
        try {
            if ($payload['deviceId'] == '') {
                return true;
            }
            $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
            $this->error_msg = '';

            $payload = array(
                'device_code' => $payload['deviceId'],
                'platform' => 3,
                'code' => $content['code'] ?? '',
            );

            $response = \app\common\service\ToolsService::Auth()->checkSvDevice($payload);

            $this->setLog($response, 'device');
            if ((int)$response['code'] === 10000) {
                return true;
            } else {
                $this->error_msg = $response['message'] ?? '设备未找到';
                return false;
            }
        } catch (\Exception $e) {
            $this->setLog('checkDevice:' . $e, 'error');
            return false;
        }
    }

    private function _unBind($uid)
    {
        try {
            if (!isset($this->worker->uidConnections[$uid])) {
                $this->setLog('uid 未找到: ' . $uid, 'error');
                return false;
            }

            if (isset($this->worker->uidConnections[$uid]->deviceid)) {
                $deviceid = $this->worker->uidConnections[$uid]->deviceid;
                $this->redis->del("xhs:device:{$deviceid}");
                $this->redis->del("xhs:device:{$deviceid}:status");
                $this->redis->del("xhs:init:{$deviceid}");
                $this->redis->del("xhs:getUser:{$deviceid}");
                $this->redis->set("xhs:device:{$deviceid}:status", 'offline');
                // $account = $this->redis->get("xhs:{$deviceid}:accountNo");
                // $this->redis->delete("xhs:{$deviceid}:accountNo");
                // $this->redis->delete("xhs:{$deviceid}:accountInfo:{$account}");

                $find = SvDevice::where('device_code', $deviceid)->limit(1)->find();
                if (!empty($find)) {
                    $find->status = 0;
                    $find->update_time = time();
                    $find->save();

                    $account = SvAccount::where('user_id', $find['user_id'])->where('device_code', $deviceid)->limit(1)->find();
                    if (!empty($account)) {
                        $account->status = 0;
                        $account->update_time = time();
                        $account->save();
                    }

                    $this->setLog('设备:' . $deviceid . ' 已断开socket连接, uid:' . $uid, 'device');

                    if (isset($this->worker->uidConnections[$uid]->timerId)) {
                        $this->setLog('设备:' . $deviceid . ' 已断开socket连接, 删除延时回复定时器', 'device');
                        Timer::del($this->worker->uidConnections[$uid]->timerId);
                        unset($this->worker->uidConnections[$uid]->timerId);
                    }
                    if (isset($this->worker->uidConnections[$uid]->crontabId)) {
                        $this->setLog('设备:' . $deviceid . ' 已断开socket连接, 删除定时器', 'device');
                        Timer::del($this->worker->uidConnections[$uid]->crontabId);
                        unset($this->worker->uidConnections[$uid]->crontabId);
                    }

                    if (isset($this->worker->uidConnections[$uid]->testCrontabId)) {
                        $this->setLog('设备:' . $deviceid . ' 已断开socket连接, 删除示例定时器', 'device');
                        Timer::del($this->worker->uidConnections[$uid]->testCrontabId);
                        unset($this->worker->uidConnections[$uid]->testCrontabId);
                    }
                }

                //通知web端设备已断开
                $this->sendWeb([
                    'type' => 'deviceOffline',
                    'deviceId' => $deviceid,
                    'code' => WorkerEnum::DEVICE_OFFLINE,
                    'msg' => '设备已断开连接'
                ]);
                //unset($this->worker->devices[$deviceid]);
            }

            $userId = $this->worker->uidConnections[$uid] ? $this->worker->uidConnections[$uid]->userid : 0;
            $sources =  WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $this->redis->del("xhs:user:{$source}:{$userId}");
            }
            // 连接断开时删除映射
            unset($this->worker->uidConnections[$uid]);
        } catch (\Exception $e) {
            $this->setLog('_unBind:' . $e, 'error');
            return false;
        }
    }

    private  function _connRedis()
    {
        if ($this->redis == null) {
            $this->redis = new Redis([
                'host'        => env('redis.HOST', '127.0.0.1'),
                'port'        => env('redis.PORT', 6379),
                'password'    => env('redis.PASSWORD', '123456'),
                'select'      => env('redis.WS_SELECT', 9),
                'timeout'     => 0,
                'persistent'  => true,
            ]);
        }
    }

    public function setLog($content, $level = 'info')
    {
        if ($this->isWriteLog) {
            Log::channel('socket')->write($content, $level);
        }
    }

    public function getWorker()
    {
        return $this->worker;
    }
    public function setWorker($worker)
    {
        $this->worker = $worker;
    }

    public function getConnections()
    {
        return $this->uidConnections;
    }

    public function setConnections($connections)
    {
        $this->uidConnections = $connections;
    }

    public function isWriteLog()
    {
        return $this->isWriteLog;
    }
    public function getRedis()
    {
        return $this->redis;
    }
}
