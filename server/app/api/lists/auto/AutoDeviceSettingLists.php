<?php

namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\auto\AutoDeviceSetting;

/**
 * 自动设备设置列表
 * Class AutoDeviceSettingLists
 * @package app\api\lists\auto
 */
class AutoDeviceSettingLists extends BaseApiDataLists implements ListsSearchInterface, ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'status', 'device_config_id'],
            '%like%' => ['device_code', 'video_theme', 'text_theme'],
            'between' => ['create_time', 'execution_day'],
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'update_time' => 'update_time'];
    }

    public function setDefaultOrder(): array
    {
        return ['create_time' => 'desc'];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = AutoDeviceSetting::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AutoDeviceSetting::where($this->searchWhere)->count();
    }
}