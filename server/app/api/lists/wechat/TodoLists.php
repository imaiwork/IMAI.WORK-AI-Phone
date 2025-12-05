<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatTodo;

/**
 * 微信待办列表
 * Class TodoLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class TodoLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['t.wechat_id', 't.friend_id'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return AiWechatTodo::alias('t')
            ->join('ai_wechat w', 'w.wechat_id = t.wechat_id')
            ->field('t.id,t.wechat_id,t.friend_id,t.todo_type,t.todo_content,t.todo_time,t.create_time,t.todo_status,t.fail_reason')
            ->where($this->searchWhere)
            ->order('t.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return AiWechatTodo::alias('t')
            ->join('ai_wechat w', 'w.wechat_id = t.wechat_id')
            ->field('t.id,t.wechat_id,t.friend_id,t.todo_type,t.todo_content,t.todo_time,t.create_time,t.status,t.fail_reason')
            ->where($this->searchWhere)
            ->count();
    }
}
