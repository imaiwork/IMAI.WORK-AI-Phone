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

class InterviewJobLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name']
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
        if (!empty($this->params['founder'])) {
            $userlist = User::where('mobile|nickname', 'like', $this->params['founder'] . '%')->column('nickname', 'id');
            if (!empty($userlist)) {
                $userids = array_keys($userlist);
                $where[] = ['user_id', 'in', $userids];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }

        // 获取列表数据
        $lists = InterviewJob::where($this->searchWhere)
            ->field('id,user_id,name,avatar,company,type,create_time,status')
            ->where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        
        // 处理用户信息
        if (count($lists) > 0) {
            $userIds = array_column($lists, 'user_id');
            $userlist = User::where('id', 'in', $userIds)->column('nickname', 'id');
            $scene = ModelConfig::whereIn('scene', ['interview_mark','interview_cv','interview_chat'])->column('score','scene');
            $mark_score = $scene['interview_mark'] ?? 0;
            $cv_score = $scene['interview_cv'] ?? 0;
            $chat_score = $scene['interview_chat'] ?? 0;
            foreach ($lists as &$item) {
                $item['interview_user_num'] = InterviewRecord::where(['job_id' => $item['id']])->count();
                $item['type_text'] = $item['type'] == 1 ? '文字' : '语音';
                $item['user'] = $userlist[$item['user_id']] ?? '';

                $mark = InterviewRecord::where(['job_id' => $item['id'],'status' => 1])->count('id');
                $cv = InterviewCv::where(['interview_job_id' => $item['id'],'type' => 1])->count('id');
                $chat = InterviewRecord::where(['job_id' => $item['id']])->count('id');
                $item['mark_score'] = $mark_score * $mark;
                $item['cv_score'] = $cv_score * $cv;
                $item['chat_score'] = $chat_score * $chat;
                $item['all_score'] = $item['mark_score'] + $item['cv_score'] + $item['chat_score'];
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
        return InterviewJob::where($this->searchWhere)->where($where)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }

  
}