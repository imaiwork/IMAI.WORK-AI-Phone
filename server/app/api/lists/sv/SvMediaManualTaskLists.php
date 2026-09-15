<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvMediaManualTask;

/**
 * 媒体手动任务列表
 * Class SvMediaManualTaskLists
 * @package app\api\lists\sv
 */
class SvMediaManualTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'manual_setting_id', 'media_type', 'status'],
            '%like%' => ['name', 'title'],
            // 其他搜索条件
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        
        // 如果有手动设置ID参数，则添加到查询条件
        if (!empty($this->params['manual_setting_id'])) {
            $this->searchWhere[] = ['manual_setting_id', '=', $this->params['manual_setting_id']];
        }
        
        $list = SvMediaManualTask::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            // 转换2个特定字段为数组
            $jsonFields = ['topic', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }
            
            // 添加状态描述
            $statusMap = [
                0 => '未发布',
                1 => '已发布', 
                2 => '发布失败',
                3 => '发布中'
            ];
            $item['status_text'] = $statusMap[$item['status']] ?? '未知状态';
            
            // 添加媒体类型描述
            $mediaTypeMap = [
                1 => '视频',
                2 => '图片'
            ];
            $item['media_type_text'] = $mediaTypeMap[$item['media_type']] ?? '未知类型';
        }
        
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        
        // 如果有手动设置ID参数，则添加到查询条件
        if (!empty($this->params['manual_setting_id'])) {
            $this->searchWhere[] = ['manual_setting_id', '=', $this->params['manual_setting_id']];
        }
        
        return SvMediaManualTask::where($this->searchWhere)->count();
    }
}