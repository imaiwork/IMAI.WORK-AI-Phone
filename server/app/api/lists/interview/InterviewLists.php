<?php


namespace app\api\lists\interview;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewDialog;
use app\common\model\interview\InterviewJob;
use app\common\model\user\User;


/**
 * 列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class InterviewLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['comment'],
            '=' => ['status', 'id']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        $jobIds = InterviewJob::where('user_id', $this->userId)->column('id');
        $where = [];
        if (!empty($this->params['username']))
        {
            $userIds = User::where('nickname|mobile', 'like', '%'.$this->params['username'].'%')->column('id');
            if (!empty($userIds))
            {
                $where[] = ['user_id', 'in', $userIds];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }
        $groups = Interview::where($this->searchWhere)
            ->where('job_id', 'in', $jobIds)
            ->where($where)
            ->field(['user_id', 'job_id'])
            ->order('id', 'desc')
            ->group(['user_id', 'job_id'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($groups as &$group)
        {
            $user = User::where('id', $group['user_id'])->findOrEmpty()->toArray();
            $group['user'] = $user;

            $success_interview = Interview::where(['user_id'=>$group['user_id'], 'job_id' => $group['job_id'], 'status' => 1])->findOrEmpty()->toArray();
            if (!empty($success_interview))
            {
                $success_interview['duration'] = ($success_interview['end_time'] - $success_interview['start_time']);
                $success_interview['start_time'] = date('Y-m-d H:i', $success_interview['start_time']);
            }
            $group['success_interview'] = $success_interview;


            $job = InterviewJob::where('id', $group['job_id'])->findOrEmpty()->toArray();
            $group['job'] = $job;

            $cv = InterviewCv::where('user_id', $group['user_id'])->findOrEmpty()->toArray();
            $group['cv'] = $cv;

            $interviews = Interview::where(['user_id'=>$group['user_id'], 'job_id' => $group['job_id']])->select()->toArray();
            foreach ($interviews as &$interview)
            {
                $dialogs = InterviewDialog::where('interview_id', $interview['id'])->order('id ASC')->select()->toArray();
                $interview['dialogs'] = $dialogs;
            }
            $group['interviews'] = $interviews;


        }

        return $groups;
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $where = [];
        if (!empty($this->params['username']))
        {
            $userIds = User::where('nickname|mobile', 'like', '%'.$this->params['username'].'%')->column('id');
            if (!empty($userIds))
            {
                $where[] = ['user_id', 'in', $userIds];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }
        $jobIds = InterviewJob::where('user_id', $this->userId)->where($where)->column('id');
        return  Interview::where($this->searchWhere)
            ->where('job_id', 'in', $jobIds)
            ->where($where)
            ->count();
    }
}
