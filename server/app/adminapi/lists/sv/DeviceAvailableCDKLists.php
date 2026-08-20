<?php

namespace app\adminapi\lists\sv;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\sv\SvDevice;

/**
 * 设备可选兑换码列表（仅设备所属用户未使用的 CDK）
 */
class DeviceAvailableCDKLists extends BaseAdminDataLists implements ListsSearchInterface
{
    private int $userId = 0;

    public function setSearch(): array
    {
        return [
            '=' => [],
            '%like%' => ['code'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $userId = $this->resolveUserId();
        $lists = DeviceCdkCode::where('status', DeviceAuthCodeEnum::STATUS_UNUSED)
            ->where('owner_user_id', $userId)
            ->where($this->searchWhere)
            ->field('id,code,type,duration_days,owner_user_id,purchase_time,create_time')
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['type']);
            $item['belong_to_user'] = true;
            $item['purchase_time'] = !empty($item['purchase_time']) && is_numeric($item['purchase_time'])
                ? date('Y-m-d H:i:s', (int)$item['purchase_time'])
                : (string)($item['purchase_time'] ?? '');
            if (!empty($item['create_time']) && is_numeric($item['create_time'])) {
                $item['create_time'] = date('Y-m-d H:i:s', (int)$item['create_time']);
            }
        }
        unset($item);

        return $lists;
    }

    /**
     * @notes 统计
     */
    public function count(): int
    {
        $userId = $this->resolveUserId();
        return DeviceCdkCode::where('status', DeviceAuthCodeEnum::STATUS_UNUSED)
            ->where('owner_user_id', $userId)
            ->where($this->searchWhere)
            ->count();
    }

    private function resolveUserId(): int
    {
        if ($this->userId > 0) {
            return $this->userId;
        }

        $deviceId = (int)($this->params['device_id'] ?? 0);
        $device = SvDevice::where('id', $deviceId)->findOrEmpty();
        if ($device->isEmpty()) {
            throw new \Exception('设备不存在');
        }
        if ((int)$device->user_id <= 0) {
            throw new \Exception('设备未绑定用户');
        }

        $this->userId = (int)$device->user_id;
        return $this->userId;
    }
}
