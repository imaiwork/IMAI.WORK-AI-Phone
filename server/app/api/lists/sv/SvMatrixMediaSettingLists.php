<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvMatrixMediaSetting;

/**
 * 矩阵媒体设置列表
 * Class SvMatrixMediaSettingLists
 * @package app\api\lists\sv
 */
class SvMatrixMediaSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['media_type'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMatrixMediaSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换JSON字段为数组
            $jsonFields = ['media_url', 'copywriting', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }
        }
        
        return $list;
    }

    public function count(): int
    {
        return SvMatrixMediaSetting::where($this->searchWhere)->count();
    }
}
