<?php

namespace app\api\lists\conversation;

use app\api\lists\BaseApiDataLists;
use app\common\model\gptThread\GptThread;

class ConversationLists extends BaseApiDataLists
{
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
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['is_debug', '=', 0];
        $list = GptThread::with([
                             'assistants' => function($query){
                                $query->field('id,name,type,assistants_id');
                             }
                         ])
                         ->where($this->searchWhere)
                         ->order('id', 'desc')
                         ->select();
        if ($list->isEmpty()) {
            return [];
        }
        $groupedEvents = [];
        foreach ($list as $v) {
            $date = date('Y-m-d', $v->getData('create_time'));
            if (!isset($groupedEvents[$date])) {
                $groupedEvents[$date] = [];
            }
            $groupedEvents[$date][] = $v->toArray();
        }
        return $groupedEvents;
    }

    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['is_debug', '=', 0];
        return GptThread::where($this->searchWhere)->count();
    }
}