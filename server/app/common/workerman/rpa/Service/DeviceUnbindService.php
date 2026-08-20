<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Service;

use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\Support\ConnectionRepository;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use app\common\workerman\rpa\WorkerEnum;
use app\common\workerman\rpa\Tool\ToolUtil;
class DeviceUnbindService
{
    /**
     * @var ConnectionRepository
     */
    private $repository;

    /**
     * @var callable
     */
    private $sendWebCallback;

    /**
     * @var ToolUtil
     */
    private $toolUtil;



    /**
     * @var array
     */
    private $uidConnections;


    public function __construct(
        ConnectionRepository $repository,
        callable $sendWebCallback,
    ) {
        $this->repository = $repository;
        $this->sendWebCallback = $sendWebCallback;
        $this->toolUtil = new ToolUtil();
    }


    /**
     * 执行设备解绑
     * @param string $uid
     * @param string $deviceId
     * @param int $userId
     * @param string $sourceType
     * @return array
     */
    public function execute(
        string $uid,
        string $deviceId = '',
        int $userId = 0,
        string $sourceType = '',
        array $uidConnections = []
    ): array {
        try {
            if (!isset($uidConnections[$uid])) {
                $this->repository->forgetConnection($uid);
                return $uidConnections;
            }

            $connection = $uidConnections[$uid];
            $deviceId = $deviceId !== '' ? trim($deviceId) : trim((string)($connection->deviceid ?? ''));

            if ($deviceId !== '') {
                $this->handleDeviceUnbind($connection, $deviceId);
            }

            $userId = $userId > 0 ? $userId : (int)($connection->userid ?? 0);
            $sourceType = $sourceType !== '' ? $sourceType : (string)($connection->sourceType ?? '');
            $this->handleUserUnbind($uid, $userId, $sourceType);

            $this->cleanupConnection($uid, $uidConnections);
            return $uidConnections;
        } catch (\Throwable $e) {
            \think\facade\Log::channel('socket')->write('_unBind:' . $e, 'error');
            return $uidConnections;
        }
    }

    /**
     * 处理设备解绑逻辑
     * @param TcpConnection $connection
     * @param string $deviceId
     */
    private function handleDeviceUnbind(TcpConnection $connection, string $deviceId): void
    {
        // 标记设备离线
        if (!$this->repository->markDeviceOffline($deviceId, (string)($connection->uid ?? ''))) {
            return;
        }

        // 清理定时器
        $this->cleanupTimers($connection, $deviceId);

        // 更新设备和账号状态
        $this->updateDeviceAndAccountStatus($deviceId);

        // 发送离线通知
        $this->sendOfflineNotification($deviceId);

        // 发送Web通知
        ($this->sendWebCallback)([
            'type' => 'deviceOffline',
            'deviceId' => $deviceId,
            'code' => WorkerEnum::DEVICE_OFFLINE,
            'msg' => '设备已断开连接',
        ]);
    }

    /**
     * 清理连接上的定时器
     * @param TcpConnection $connection
     */
    private function cleanupTimers(TcpConnection $connection, string $deviceId): void
    {
        foreach (['timerId', 'crontabId', 'testCrontabId'] as $timerProperty) {
            if (!empty($connection->{$timerProperty})) {
                Timer::del($connection->{$timerProperty});
                unset($connection->{$timerProperty});
                
            }
        }
        \think\facade\Log::channel('socket')->write('设备:' . $deviceId . ' 已断开socket连接, 删除相关定时器', 'device');
    }

    /**
     * 更新设备和账号状态
     * @param string $deviceId
     */
    private function updateDeviceAndAccountStatus(string $deviceId): void
    {
        try {
            $find = SvDevice::where('device_code', $deviceId)->limit(1)->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->status = 0;
                $find->update_time = time();
                $find->save();

                $account = SvAccount::where('user_id', $find['user_id'])
                    ->where('device_code', $deviceId)
                    ->limit(1)
                    ->findOrEmpty();
                if (!$account->isEmpty()) {
                    $account->status = 0;
                    $account->update_time = time();
                    $account->save();
                }
                \think\facade\Log::channel('socket')->write('设备:' . $deviceId . ' 已断开连接，账号状态已更新为离线，发送离线通知', 'device');
                // \app\api\logic\ApiLogic::sendNotice([
                //     'userId' => $find->user_id,
                //     'content' => $find->device_name,
                //     'time' => date('Y-m-d H:i:s', time()),
                //     'status' => '离线',
                // ], 403);
                $this->toolUtil->sendNotification((int)$find->user_id, $find->device_name, '离线');
            }
        } catch (\Throwable $e) {
            \think\facade\Log::channel('socket')->write(json_encode([
                'msg' => '设备和账号离线状态同步失败',
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'error');
        }
    }

    /**
     * 发送离线通知（预留扩展）
     * @param string $deviceId
     */
    private function sendOfflineNotification(string $deviceId): void
    {
        // 可以在此添加更多通知逻辑
    }

    /**
     * 处理用户解绑逻辑
     * @param int $userId
     * @param string $sourceType
     */
    private function handleUserUnbind(string $uid, int $userId, string $sourceType): void
    {
        if ($userId <= 0) {
            return;
        }

        if ($sourceType !== '') {
            $this->repository->forgetWebUserIfUid($sourceType, $userId, $uid);
            return;
        }

        foreach (\app\common\workerman\rpa\WorkerEnum::WS_SOURCES as $source) {
            $this->repository->forgetWebUserIfUid($source, $userId, $uid);
        }
    }

    /**
     * 清理连接
     * @param string $uid
     */
    private function cleanupConnection(string $uid, array &$uidConnections): void
    {
        $this->repository->forgetConnection($uid);
        unset($uidConnections[$uid]);
    }

}
