<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatRobot;
use app\common\model\wechat\AiWechat;

/**
 * 微信机器人列表
 * Class RobotLists
 * @package app\api\lists\wechat
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
        return AiWechatRobot::field('id,user_id,name,logo,description,company_background,question,answer,create_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {

                // 绑定微信数量
                $item['wechat_count'] = AiWechat::alias('w')
                    ->leftJoin('ai_wechat_setting s', 's.wechat_id = w.wechat_id')
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
        return AiWechatRobot::field('id,name,logo,description,company_background,question,answer,create_time')
            ->where($this->searchWhere)
            ->count();
    }
}
