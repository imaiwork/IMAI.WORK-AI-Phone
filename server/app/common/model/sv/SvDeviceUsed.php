<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceUsed extends BaseModel
{
    protected $name = 'sv_device_used';

    public static function saveRecord(int $userId, string $deviceCode, int $deviceId, int $isUsed): self
    {
        $deviceCode = trim($deviceCode);
        if ($userId <= 0 || $deviceCode === '') {
            throw new \Exception('设备使用记录参数错误');
        }

        $data = [
            'device_id'   => $deviceId,
            'user_id'     => $userId,
            'device_code' => $deviceCode,
            'is_used'     => $isUsed,
        ];
        $record = self::where([
            'user_id'     => $userId,
            'device_code' => $deviceCode,
        ])->findOrEmpty();
        if ($record->isEmpty()) {
            return self::create($data);
        }

        $record->save($data);
        return $record;
    }

    public static function deleteByDevice(int $userId, string $deviceCode, int $deviceId = 0): void
    {
        if ($deviceId > 0) {
            self::where('device_id', $deviceId)->delete();
        }
        self::where([
            'user_id'     => $userId,
            'device_code' => trim($deviceCode),
        ])->delete();
    }
}
