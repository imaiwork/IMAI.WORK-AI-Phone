<?php

namespace app\api\lists\interview;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\InterviewJob;
use app\common\model\interview\InterviewRecord;
use app\common\model\user\User;

/**
 * 面试记录列表
 * Class InterviewRecordLists
 * @package app\api\lists\interview
 */
class InterviewRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            "like%" => ['interview_name'],
            'time'=>['first_start_time','create_time'],
            '=' => ['r.status', 'id','r.job_id']
        ];
    }
    
    /**
     * 获取面试记录列表
     * @return array
     */
    public function lists(): array
    {
       
        $jobIds = InterviewJob::where('user_id', $this->userId)->column('id');
        $lists = InterviewRecord::alias('r')
            ->where('r.job_id', 'in', $jobIds)
            ->where($this->searchWhere)
            ->append(['status_text'])
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
            
        foreach ($lists as &$item) {
            // 格式化时间
            $item['first_start_time_text'] = !empty($item['first_start_time']) ? 
                date('Y-m-d H:i:s', $item['first_start_time']) : '';
                
            $item['last_end_time_text'] = !empty($item['last_end_time']) ? 
                date('Y-m-d H:i:s', $item['last_end_time']) : '';
            $duration = $item['duration'];
                // 计算面试时长
            $hours = floor($duration / 3600); // 计算小时
            $minutes = floor(($duration % 3600) / 60); // 计算分钟
            $seconds = $duration % 60; // 计算秒数
                // 格式化为 "时:分:秒"
            $formattedTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            $item['duration'] = $formattedTime;
        }
        
        return $lists;
    }
    
    /**
     * 获取数量
     * @return int
     */
    public function count(): int
    {
        $jobIds = InterviewJob::where('user_id', $this->userId)->column('id');
        
        $query = InterviewRecord::alias('r')
            ->where('r.job_id', 'in', $jobIds)
            ->where($this->searchWhere)->count();
    
        return $query;
    }
} 