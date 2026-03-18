<?php

namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\auto\AutoDeviceWechatCircleConfig;

/**
 * 微信朋友圈自动化配置列表
 * Class AutoDeviceWechatCircleConfigLists
 * @package app\api\lists\auto
 */
class AutoDeviceWechatCircleConfigLists extends BaseApiDataLists implements ListsSearchInterface, ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'device_config_id', 'is_ai'],
            '%like%' => ['device_code', 'industry_type'],
            'between' => ['create_time', 'exec_date'],
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
        $list = AutoDeviceWechatCircleConfig::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AutoDeviceWechatCircleConfig::where($this->searchWhere)->count();
    }
}
