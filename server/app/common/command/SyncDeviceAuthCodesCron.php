<?php

namespace app\common\command;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceUsed;
use app\common\service\ConfigService;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class SyncDeviceAuthCodesCron extends Command
{
    protected function configure()
    {
        $this->setName('sync_device_auth_codes')->setDescription('从中台拉取设备CDK同步到码池');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $fixed = $this->fixLegacyDeviceAuthStatus();
            echo '修复旧设备auth_status成功: ' . $fixed;

            $result = $this->syncDeviceUsedRecords(500);
            echo '同步设备使用记录成功: ' . json_encode($result, JSON_UNESCAPED_UNICODE);

            $lastSync = (int)ConfigService::get('device_auth', 'last_sync_time', 0);
            $params = [];
            if ($lastSync > 0) {
                $params['updated_since'] = $lastSync;
            }
            $result = DeviceAuthCodeSyncService::pullFromPlatform($params);
            echo '同步中台设备CDK成功: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
            return true;
        } catch (\Exception $e) {
            Log::channel('crontab')->error('同步中台设备CDK失败: ' . $e->getMessage());
            echo '同步中台设备CDK失败: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * 修复站长端旧设备：status=1/2 且 auth_status=0 的记录改为已激活
     */
    private function fixLegacyDeviceAuthStatus(): int
    {
        return (int)SvDevice::whereIn('status', [1, 2])
            ->where('auth_status', DeviceAuthCodeEnum::DEVICE_AUTH_INACTIVE)
            ->update([
                'auth_status' => DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE,
            ]);
    }

    private function syncDeviceUsedRecords(int $batchSize): array
    {
        $batchSize = $batchSize > 0 ? $batchSize : 100;
        $deadline = strtotime('2026-07-03 11:30:00');
        $lastId = 0;
        $total = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'devices' => 0];

        while (true) {
            $devices = SvDevice::where('id', '>', $lastId)
                ->where('device_code', '<>', '')
                ->where('user_id', '>', 0)
                ->where(function ($query) use ($deadline) {
                    $query->where('create_time', '<', $deadline)
                        ->whereOr('create_time', 0);
                })
                ->order('id asc')
                ->limit($batchSize)
                ->select();
            if ($devices->isEmpty()) {
                break;
            }

            foreach ($devices as $device) {
                $lastId = max($lastId, (int)$device->id);
                $deviceCode = trim((string)$device->device_code);
                $userId = (int)$device->user_id;
                if ($deviceCode === '' || $userId <= 0) {
                    $total['skipped']++;
                    continue;
                }

                $exists = SvDeviceUsed::where([
                    'user_id'     => $userId,
                    'device_code' => $deviceCode,
                ])->findOrEmpty();
                SvDeviceUsed::saveRecord($userId, $deviceCode, (int)$device->id, 1);
                if ($exists->isEmpty()) {
                    $total['created']++;
                } else {
                    $total['updated']++;
                }
                $total['devices']++;
            }
        }

        return $total;
    }
}
