<?php

namespace app\api\lists\coze;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\CozeConfig;
use app\common\model\coze\CozeLog;

class CozeLogLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type','conversation_id','bot_id'],
        ];
    }

    public function lists(): array
    {
        $where = [
            ['user_id', '=', $this->userId],
        ];
        $searchWhere = $this->searchWhere;
        $group = true;
        $order = ['create_time' => 'desc'];
        foreach ($searchWhere as $val){
           foreach ($val as $v){
               if ($v == 'conversation_id'){
                   $group = false;
                   $order = ['create_time' => 'asc'];
               }
           }
        }
        $list = CozeLog::where($where)
            ->where($this->searchWhere)
            ->order($order)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength);
        if ($group){
            $list = $list->group('conversation_id') ;
        }
        $list = $list->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $where = [
            ['user_id', '=', $this->userId],
        ];
        $searchWhere = $this->searchWhere;
        $group = true;
        foreach ($searchWhere as $val){
            foreach ($val as $v){
                if ($v == 'conversation_id'){
                    $group = false;
                }
            }
        }
        $count = CozeLog::where($where)  ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            });
            if ($group){
                $count = $count->group('conversation_id') ;
            };
        $count = $count->count();
        return $count;
    }
}


