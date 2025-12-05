<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;

/**
 * 闪剪视频设置列表
 * Class ShanjianVideoSettingLists
 * @package app\api\lists\shanjian
 */
class ShanjianVideoSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = ShanjianVideoSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换JSON字段为数组
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }

            $item['task'] = ShanjianVideoTask::where('video_setting_id', $item['id'])
                ->order('update_time', 'desc')
                ->select();
        }
        
        return $list;
    }

    public function count(): int
    {
        return ShanjianVideoSetting::where($this->searchWhere)->count();
    }
}
