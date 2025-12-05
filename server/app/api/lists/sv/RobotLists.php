<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvRobot;
use app\common\model\sv\SvAccount;

/**
 * 微信机器人列表
 * Class RobotLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class RobotLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvRobot::field('id,user_id,name,logo,description,company_background,profile,create_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {

                // 绑定数量
                $item['account_count'] = SvAccount::alias('w')
                    ->leftJoin('sv_setting s', 's.account = w.account')
                    ->where('w.user_id', $this->userId)
                    ->where('s.robot_id', $item['id'])
                    ->count() ?? 0;
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvRobot::field('id,name,logo,description,company_background,profile,create_time')
            ->where($this->searchWhere)
            ->count();
    }
}
