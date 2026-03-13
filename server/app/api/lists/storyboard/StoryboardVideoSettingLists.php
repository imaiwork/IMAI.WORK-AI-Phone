<?php

namespace app\api\lists\storyboard;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\storyboard\StoryboardVideoSetting;
use app\common\model\storyboard\StoryboardVideoTask;

/**
 * 闪剪视频设置列表
 * Class StoryboardVideoSettingLists
 * @package app\api\lists\Storyboard
 */
class StoryboardVideoSettingLists extends BaseApiDataLists implements ListsSearchInterface
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
        $list = StoryboardVideoSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换JSON字段为数组
            $jsonFields = ['extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }

            $item['task'] = StoryboardVideoTask::where('video_setting_id', $item['id'])
                ->order('update_time', 'desc')
                ->select();
        }
        
        return $list;
    }

    public function count(): int
    {
        return StoryboardVideoSetting::where($this->searchWhere)->count();
    }
}
