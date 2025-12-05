<?php


namespace app\adminapi\lists\crontab;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\Crontab;

/**
 * 定时任务列表
 * Class CrontabLists
 * @package app\adminapi\lists\crontab
 */
class CrontabLists extends BaseAdminDataLists
{
    /**
     * @notes 定时任务列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/3/29 14:30
     */
    public function lists(): array
    {
        $field = 'id,name,type,type as type_desc,command,params,expression,
        status,status as status_desc,error,last_time,time,max_time';

        $lists = Crontab::field($field)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return $lists;
    }


    /**
     * @notes 定时任务数量
     * @return int
     * @author 段誉
     * @date 2022/3/29 14:38
     */
    public function count(): int
    {
        return Crontab::count();
    }
}
