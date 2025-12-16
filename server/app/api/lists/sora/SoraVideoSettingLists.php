<?php

namespace app\api\lists\sora;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;

/**
 * 闪剪视频设置列表
 * Class SoraVideoSettingLists
 * @package app\api\lists\sora
 */
class SoraVideoSettingLists extends BaseApiDataLists implements ListsSearchInterface
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
        $list = SoraVideoSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换JSON字段为数组
            $jsonFields = ['copywriting', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }

            $item['task'] = SoraVideoTask::where('video_setting_id', $item['id'])
                ->order('update_time', 'desc')
                ->select();
        }
        
        return $list;
    }

    public function count(): int
    {
        return SoraVideoSetting::where($this->searchWhere)->count();
    }
}
