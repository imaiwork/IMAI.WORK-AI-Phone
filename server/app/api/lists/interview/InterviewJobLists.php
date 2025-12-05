<?php


namespace app\api\lists\interview;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewConfig;
use app\common\model\interview\InterviewJob;


/**
 * 列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class InterviewJobLists extends  BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
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
        $lists = InterviewJob::where($this->searchWhere)
            ->where('user_id', '=', $this->userId)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['tips'] = json_decode($item['tips']);
            })
            ->toArray();
        foreach ($lists as &$item){
            $item['interview_user_num'] = Interview::where(['job_id' => $item['id']])->count();
            $config = InterviewConfig::where('job_id', $item['id'])->findOrEmpty()->toArray();
            $item['interview_config'] = $config;
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
        return InterviewJob::where($this->searchWhere)->where('user_id', '=', $this->userId)->count();
    }
}
