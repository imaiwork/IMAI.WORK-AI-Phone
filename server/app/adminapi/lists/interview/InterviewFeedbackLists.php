<?php


namespace app\adminapi\lists\interview;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewFeedback;
use app\common\model\interview\InterviewJob;
use app\common\model\user\User;


/**
 * 列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class InterviewFeedbackLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['content'],
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
        $lists = InterviewFeedback::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($lists as &$item){
            $user = User::where('id', $item['user_id'])->findOrEmpty()->toArray();
            $item['user'] = $user;

            $job = InterviewJob::where('id', $item['job_id'])->findOrEmpty()->toArray();
            $item['job'] = $job;

            $create_user = User::where('id', $job['user_id'])->findOrEmpty()->toArray();
            $item['create_user'] = $create_user;
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
        return InterviewFeedback::where($this->searchWhere)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }
}
