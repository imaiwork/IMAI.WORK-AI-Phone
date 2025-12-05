<?php

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\sop\AiWechatSopPushMember;

/**
 * 推送人员列表
 * Class FlowLists
 * @package app\api\lists\sop
 */
class PushMemberLists extends BaseApiDataLists implements ListsSearchInterface
{
    
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status','flow_id','wechat_id','friend_id'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        // 添加用户ID条件
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['status', '=', 1];

        $baseQuery = AiWechatSopPushMember::field('id,wechat_id,friend_id,status,user_id,flow_id,stage_id,create_time,update_time')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('id', 'desc');

        $result = $baseQuery->select()->toArray();

        return $result;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['status', '=', 1];
        return AiWechatSopPushMember::where($this->searchWhere)->count();
    }
} 