<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvMediaSetting;

/**
 * 视频设置列表
 * Class SvMediaSettingLists
 * @package app\api\lists\sv
 */
class SvMediaSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'type', 'media_type'],
            '%like%' => ['name'],
            // 其他搜索条件
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMediaSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换6个特定字段为数组
            $jsonFields = ['media_url', 'title', 'subtitle','extra'];
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
        return SvMediaSetting::where($this->searchWhere)->count();
    }
}