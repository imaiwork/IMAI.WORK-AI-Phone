<?php

namespace app\adminapi\lists\coze;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\CozeLog;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;

class CozeLogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['cl.type','cl.conversation_id','cl.bot_id'],
            "%like%" => ['u.nickname', 'cl.content'],
        ];
    }

    public function lists(): array
    {

        $searchWhere = $this->searchWhere;

        $order = ['cl.create_time' => 'desc'];
        foreach ($searchWhere as $val){
           foreach ($val as $v){
               if ($v == 'cl.conversation_id'){
                   $order = ['cl.create_time' => 'asc'];
               }
           }
            if ($val[0] == 'cl.type'  && $val[2] == 1){
                $where['cl.role'] = 'user';
            }
            if ($val[0] == 'cl.type'  && $val[2] == 2){
                $where['cl.role'] = 'workflow';
            }
        }
        $list = CozeLog::alias('cl')
            ->leftJoin('user u', 'u.id = cl.user_id')
            ->field('cl.*, u.nickname, u.avatar')
            ->where($this->searchWhere)
            ->where($where)
            ->order($order)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('cl.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)->select()
            ->each(function ($item) {
                $item['nickname']   = $item['nickname'] ?? '';
                $item['avatar']     = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
                switch ($item['type']) {
                    case 1:
                        $change_type = AccountLogEnum::TOKENS_DEC_COZE_AGENT_CHAT;
                        break;
                    case 2:
                        $change_type = AccountLogEnum::TOKENS_DEC_COZE_WORKFLOW;
                        break;
                }
                $points1 = UserTokensLog::where('user_id', $item['user_id'])->where('action',1)
                    ->where('task_id', $item['message_id'])->where('change_type', $change_type)->sum('change_amount') ?? 0;

                $points2 = UserTokensLog::where('user_id', $item['user_id'])->where('action',2)
                    ->where('task_id', $item['message_id'])->where('change_type', $change_type)->sum('change_amount') ?? 0;
                $item['points'] = $points1 + $points2 ;
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $searchWhere = $this->searchWhere;
        foreach ($searchWhere as $val){
            if ($val[0] == 'cl.type'  && $val[2] == 1){
                $where['cl.role'] = 'user';
            }
            if ($val[0] == 'cl.type'  && $val[2] == 2){
                $where['cl.role'] = 'workflow';
            }
        }
        $count = CozeLog::alias('cl') ->leftJoin('user u', 'u.id = cl.user_id') ->where($this->searchWhere)
            ->where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('cl.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->count();
        return $count;
    }
}


