<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatCircleTask;

/**
 * 发圈任务列表
 * Class WechatCircleTaskLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class CircleTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['t.send_status', 't.wechat_id', 'w.wechat_nickname', 'w.wechat_no'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['t.user_id', '=', $this->userId];
        return AiWechatCircleTask::alias('t')
            ->field('w.wechat_no,w.wechat_nickname,w.wechat_id,t.id,t.send_status,t.send_time,t.task_type,t.finish_time,t.content, t.attachment_type,t.attachment_content,t.comment')
            ->join('ai_wechat w', 'w.wechat_id = t.wechat_id and w.user_id = t.user_id')
            ->where($this->searchWhere)
            ->order('t.send_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item)
            {
                $item->send_time = date('Y-m-d H:i', strtotime($item->send_time));
                $item->finish_time = $item->finish_time ? date('Y-m-d H:i', strtotime($item->finish_time)) : "";
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['t.user_id', '=', $this->userId];
        return AiWechatCircleTask::alias('t')
            ->field('w.wechat_no,w.wechat_nickname,w.wechat_id,t.status,t.send_time,t.finish_time')
            ->join('ai_wechat w', 'w.wechat_id = t.wechat_id and w.user_id = t.user_id')
            ->where($this->searchWhere)
            ->count();
    }
}
