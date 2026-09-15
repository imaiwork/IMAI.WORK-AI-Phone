<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvMediaManualSetting;

/**
 * 媒体手动设置列表
 * Class SvMediaManualSettingLists
 * @package app\api\lists\sv
 */
class SvMediaManualSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['user_id'],
            '%like%' => ['name'],
            // 其他搜索条件
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMediaManualSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        
        return $list;
    }

    public function count(): int
    {
        return SvMediaManualSetting::where($this->searchWhere)->count();
    }
}