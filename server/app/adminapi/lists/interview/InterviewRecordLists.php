<?php

namespace app\adminapi\lists\interview;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewJob;
use app\common\model\interview\InterviewRecord;
use app\common\model\ModelConfig;
use app\common\model\user\User;

class InterviewRecordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['job_name'],
            '=' => ['job_id']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $where = [];
        $userlist = [];

        // 根据创始人进行搜索
        if (!empty($this->params['interview'])) {
            $userlist = User::where('mobile|nickname', 'like', $this->params['interview'] . '%')->column('nickname', 'id');
            if (!empty($userlist)) {
                $userids = array_keys($userlist);
                $where[] = ['user_id', 'in', $userids];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }
    
        // 获取列表数据
        $lists = InterviewRecord::where($this->searchWhere)
            ->field('id,user_id,status,job_id,degree,work_years,best_score,duration,create_time,interview_name')
            ->where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->append(['status_text'])
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
         

        // 处理用户信息
        if (count($lists) > 0) {
            $userIds = array_column($lists, 'user_id');
            $userlist = User::where('id', 'in', $userIds)->column('nickname', 'id');
            $jobIds = array_column($lists, 'job_id');
            $joblist = InterviewJob::where('id', 'in', $jobIds)->column('name,company', 'id');
          
            $scene = ModelConfig::whereIn('scene', ['interview_mark','interview_cv','interview_chat'])->column('score','scene');
            $mark_score = $scene['interview_mark'] ?? 0;
            $cv_score = $scene['interview_cv'] ?? 0;
            $chat_score = $scene['interview_chat'] ?? 0;
            foreach ($lists as &$item) {
              
                $item['user'] = $userlist[$item['user_id']] ?? '';
                $item['job_name'] = $joblist[$item['job_id']]['name'] ?? '';    
                $item['company'] = $joblist[$item['job_id']]['company'] ?? '';
                $mark = InterviewRecord::where(['id' => $item['id'],'status' => 1])->count('id');
                $cv = InterviewCv::where(['interview_job_id' => $item['job_id'],'type' => 1])->count('id');
                $chat = InterviewRecord::where(['id' => $item['id']])->count('id');
                $item['mark_score'] = $mark_score * $mark;
                $item['cv_score'] = $cv_score * $cv;
                $item['chat_score'] = $chat_score * $chat;
                $item['all_score'] = $item['mark_score'] + $item['cv_score'] + $item['chat_score'];
                $duration = $item['duration'];
                    // 计算面试时长
                $hours = floor($duration / 3600); // 计算小时
                $minutes = floor(($duration % 3600) / 60); // 计算分钟
                $seconds = $duration % 60; // 计算秒数
                    // 格式化为 "时:分:秒"
                $formattedTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                $item['duration'] = $formattedTime;
            }
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $where = [];
        if (!empty($this->params['mobile'])) {
            $userids = User::where('mobile|nickname', 'like', $this->params['interview'] . '%')->column('id');
            if (!empty($userids)) {
                $where[] = ['user_id', 'in', $userids];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }
        return InterviewRecord::where($this->searchWhere)->where($where)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }

  
}