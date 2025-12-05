<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\sv\SvVideoTask;
use app\common\service\FileService;

/**
 * 视频任务列表
 * Class SvVideoTaskLists
 * @package app\api\lists\sv
 */
class SvVideoTaskLists extends BaseApiDataLists implements ListsSearchInterface,  ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            '=' => [ 'type', 'status', 'video_setting_id', 'audio_type', 'model_version', 'ai_type'],
            '%like%' => ['name'],
            'between' => ['create_time'],
            // 其他搜索条件
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time'];
    }


    public function setDefaultOrder(): array
    {
        return ['create_time' => 'desc'];
    }
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvVideoTask::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            if (!empty($item['extra'])) {
                $item['extra'] = json_decode($item['extra'], true);
            } else {
                $item['extra'] = [];
            }
            $item['audio_url']            = trim($item['audio_url']) ?  FileService::getFileUrl($item['audio_url']) : "";
            $item['upload_audio_url']     = trim($item['upload_audio_url']) ? FileService::getFileUrl($item['upload_audio_url']): "";
            $item['upload_video_url']         = trim($item['upload_video_url']) ? FileService::getFileUrl($item['upload_video_url']): "";
            $item['video_result_url']         = trim($item['video_result_url']) ? FileService::getFileUrl($item['video_result_url']): "";
        }
        
        return $list;
    }

    public function count(): int
    {
        return SvVideoTask::where($this->searchWhere)->count();
    }
}