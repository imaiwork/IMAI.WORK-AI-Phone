<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;

/**
 * 视频设置列表
 * Class SvVideoSettingLists
 * @package app\api\lists\sv
 */
class SvVideoSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'type', 'status', 'ai_type'],
            '%like%' => ['name'],
            // 其他搜索条件
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvVideoSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换6个特定字段为数组
            $jsonFields = ['anchor', 'voice', 'title', 'subtitle', 'copywriting', 'topic','extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }

            $item['latest_submission_time'] = SvVideoTask::where('video_setting_id',$item['id'])->order('update_time', 'desc') // 按 update_time 降序排序
            ->value('update_time') ?? '暂无';
            if ( $item['latest_submission_time'] != '暂无'){
                $item['latest_submission_time'] = date('Y-m-d H:i:s', $item['latest_submission_time']);
            }
        }
        
        return $list;
    }

    public function count(): int
    {
        return SvVideoSetting::where($this->searchWhere)->count();
    }
}