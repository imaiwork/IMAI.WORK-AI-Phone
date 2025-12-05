<?php

namespace app\common\workerman\rpa;

use think\facade\Log;
use think\facade\Config;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;
use Channel\Client as ChannelClient;

abstract class BaseMessageHandler
{
    protected RpaSocketService $service;
    protected $worker;
    protected int $msgType;
    protected string $uid;
    protected array $payload;
    protected int $userId;
    protected TcpConnection $connection;

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

    public function __construct(RpaSocketService $service)
    {
        $this->service = $service;
    }

    abstract public function handle(TcpConnection $connection, string $uid, array $payload): void;

    // 通用发送方法
    protected function sendResponse(string $uid, array $payload, $message)
    {
        try {
            $payload['reply'] = $message;
            return $this->service->send($uid, $payload);
        } catch (\Exception $e) {
            $this->setLog('sendResponse' . $e, 'error');
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
    
    public function base64ToImage($item)
    {
        if (!trim($item['avatar'])) {
            return '';
        }
        // 分离Base64头和数据
        $data = explode(',', $item['avatar']);
        // 解码Base64数据
        $decoded = base64_decode($data[1] ?? $data[0]);
        $code = $item['xhsId'] ?? $item['authorName'];
        $output = 'uploads/images/xhs/xhs_' . $code . '.png';
        $root_path = public_path();
        // 创建目录（如果不存在）
        if (!is_dir(dirname($root_path . $output))) {
            mkdir(dirname($root_path . $output), 0777, true);
        }

        // 保存文件
        if (file_put_contents($root_path . $output, $decoded)) {
            return Config::get('app.app_host') . '/' . $output;
        }
        return '';
    }


    public function setLog($content, $level = 'info')
    {
        if ($this->service->isWriteLog() === true) {
            try {
                if (is_array($content)) {
                    $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
                Log::channel('socket')->write($content, $level);
            } catch (\Exception  $e) {
                Log::channel('socket')->write($e, $level);
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
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        // 注册进程消息监听
        ChannelClient::on("{$type}.{$deviceId}.message", function ($data) use ($connection, $type) {

            $message = $data['data'];
            $this->setLog(json_decode($message, true), 'channel');

            $connection->send($message);
        });
        $this->setLog('Channel listener registered: ' . $deviceId, 'device');
    }
}
