<?php


namespace app\adminapi\lists\interview;

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
            "%like%" => ['name|mobile']
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
        $where = [];
        $params = $this->params;
        if (!empty($params['job_name']))
        {
            $jobIds = InterviewJob::where('name', 'like', '%'.$params['job_name'].'%')->column('id');
            if (!empty($jobIds))
            {
                $where[] = ['job_id', 'in', $jobIds];
            } else {
                $where[] = ['job_id', '=', -1];
            }
        }
        if (!empty($params['user_name']))
        {
            $userIds = User::where('nickname', 'like', '%'.$params['user_name'].'%')->column('id');
            if (!empty($userIds))
            {
                $where[] = ['user_id', 'in', $userIds];
            } else {
                $where[] = ['user_id', '=', -1];
            }
        }
        $lists = Interview::where($this->searchWhere)
            ->where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($lists as &$item){
            $cv = InterviewCv::where('user_id', $item['user_id'])->findOrEmpty()->toArray();
            $item['cv'] = $cv;

            $job = InterviewJob::where('id', $item['job_id'])->findOrEmpty()->toArray();
            $item['job'] = $job;

            $dialogs = InterviewDialog::where('interview_id', $item['id'])->json(['dialog'])->select()->toArray();
            $item['dialogs'] = $dialogs;

            $create_user = User::where('id', $job['user_id'])->findOrEmpty()->toArray();
            $item['create_user'] = $create_user;

            $user = User::where('id', $item['user_id'])->findOrEmpty()->toArray();
            $item['user'] = $user;
        }
        return $lists;
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return Interview::where($this->searchWhere)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }
}
