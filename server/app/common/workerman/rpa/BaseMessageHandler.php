<?php

namespace app\common\workerman\rpa;

use think\facade\Log;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;
use Workerman\Worker;
use app\common\workerman\rpa\Tool\ToolUtil;

abstract class BaseMessageHandler
{
    protected RpaSocketService $service;
    protected Worker $worker;
    protected int $msgType;
    protected string $uid;
    protected array $payload;
    protected int $userId;
    protected TcpConnection $connection;
    protected ToolUtil $toolUtil;

    protected int $publishPlatform = 0;

    protected array $platform = array(
        1 => '个微',
        3 => '小红书',
        4 => '抖音',
        5 => '快手',
    );

    protected array $PlatformTypeEn = array(
        1 => 'sph',
        3 => 'xhs',
        4 => 'dy',
        5 => 'ks',
    );

    protected array $accountSource = array(
        3 => 60,
        4 => 70,
        5 => 80
    );

    public function __construct(RpaSocketService $service)
    {
        $this->service = $service;
        $this->toolUtil = new ToolUtil();
    }

    abstract public function handle(TcpConnection $connection, string $uid, array $payload): void;

    // 通用发送方法
    protected function sendResponse(string $uid, array $payload, array|string|null $message)
    {
        try {
            $payload['reply'] = $message;
            return $this->service->send($uid, $payload);
        } catch (\Exception $e) {
            $this->setLog('sendResponse' . $e, 'error');
        } finally{
            unset($payload);
        }
    }

    /**
     * 发送错误信息到web端
     *
     * @param TcpConnection $connection
     * @param array $payload
     * @return void
     */
    public function sendError(TcpConnection $connection, array $payload)
    {
        try {
            $code = $payload['code'] ?? WorkerEnum::ERROR_CODE;
            $reply = array(
                'code' => $code,
                'msg' => $payload['reply'] ?? (WorkerEnum::getMessage($code) ??  '指令有误'),
                'deviceId' => $payload['deviceId'] ?? '',
            );
            $payload = array(
                'code' =>  WorkerEnum::ERROR_CODE,
                'reply' => $reply,
                'appType' => $payload['appType'] ?? 3,
                'type' =>  $payload['type'] ?? 'error',
                'messageId' => $payload['messageId'] ?? 0,
                'deviceId' => $payload['deviceId'] ?? '',
                'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            );
            $this->setLog($payload, 'send');
            $this->setLog($connection->clientType, 'send');
            $this->setLog($connection->uid, 'send');

            $uid = '';
            if ($connection->clientType == WorkerEnum::WS_CLIENT_TYPE) {
                $uid = $connection->uid;
                $this->setLog('uid ' . $uid, 'send');
                $this->service->send($uid, $payload);
            } else if ($connection->clientType == WorkerEnum::WS_DEVICE_TYPE) {
                $find = SvDevice::where('device_code', $payload['deviceId'])->limit(1)->find();
                if (empty($find)) {
                    $this->setLog('设备不存在:' .  $payload['deviceId'], 'error');
                    return;
                }
                $sources = WorkerEnum::WS_SOURCES;
                foreach ($sources as $source) {
                    $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$find['user_id']}");
                    if ($uid) {
                        $this->service->send($uid, $payload);
                    }
                }
            } else {
                $this->service->send($connection->uid, $payload);
            }
        } catch (\Exception $e) {
            $this->setLog('sendError' . $e, 'error');
        } finally{
            unset($payload);
        }
    }

    public function checkDeviceStatus(string $deviceId)
    {
        try {
            $this->worker = $this->service->getWorker();

            $device_uid = $this->worker->devices[$deviceId] ?? null;
            if (empty($device_uid)) {
                return false;
            }
            $connection = $this->worker->uidConnections[$device_uid] ?? null;
            if (empty($connection)) {
                return false;
            }
            return true;

            if ($connection->isMsgRunning == 1) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            $this->setLog('checkDeviceStatus' . $e, 'error');
        }
    }


    public function setLog(array|string $content, $level = 'info')
    {
        if ($this->service->isWriteLog() === true) {
            try {
                if (is_array($content)) {
                    $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
                Log::channel('socket')->write($content, $level);
            } catch (\Exception  $e) {
                Log::channel('socket')->write($e, $level);
            } finally{
                unset($content);
            }
        }
    }


    /**
     * 注册Channel监听
     * 
     * @param TcpConnection $connection 连接实例
     * @return void
     */
    public function registerChannelListener(TcpConnection $connection, string $deviceId, string $type = 'device'): void
    {
        $this->service->registerChannelListener($connection, $deviceId, $type);
    }
}
