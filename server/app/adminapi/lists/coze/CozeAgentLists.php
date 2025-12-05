<?php

namespace app\adminapi\lists\coze;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auth\Admin;
use app\common\model\coze\AgentCate;
use app\common\model\coze\CozeAgent;
use app\common\model\user\User;

class CozeAgentLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'agent_cate_id', 'source_id','source'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $nickname = $this->request->get('nickname','');
        $list = CozeAgent::alias('ca')->where($this->searchWhere)
            ->order(['ca.create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ca.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            });
            if ($nickname != ''){
                $list = $list->where(function ($q) use ($nickname) {
                    // admin 昵称字段
                    $q->where('source', 0)
                        ->whereExists(function ($query) use ($nickname) {
                            $query->name('admin')
                                ->whereColumn('id', 'ca.source_id')
                                ->whereLike('name', "%{$nickname}%");
                        });
                })
                    ->whereOr(function ($q) use ($nickname) {
                        // user 昵称字段
                        $q->where('source', 1)
                            ->whereExists(function ($query) use ($nickname) {
                                $query->name('user')
                                    ->whereColumn('id', 'ca.source_id')
                                    ->whereLike('nickname', "%{$nickname}%");
                            });
                    }) ;
            };
        $list = $list->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['source_text'] = CozeAgent::getSourceText((int)($item['source'] ?? 0));
            $item['type_text'] = CozeAgent::getTypeText((int)($item['type'] ?? 0));
            $item['permissions_text'] = CozeAgent::getPermissionsText((int)($item['permissions'] ?? 0));
            $item['stream_text'] = CozeAgent::getStreamText((int)($item['stream'] ?? 0));
            $item['deduction_text'] = CozeAgent::getDeductionText((int)($item['deduction'] ?? 0));
            if ($item['source'] == CozeAgent::SOURCE_USER) {
                $item['nickname'] = User::where('id', $item['source_id'])->value('nickname');
            }else{
                $item['nickname'] = Admin::where('id', $item['source_id'])->value('name');
            }
            $item['agent_cate_name'] = "";
            if ($item['agent_cate_id'] > 0){
                $item['agent_cate_name'] = AgentCate::where('id', $item['agent_cate_id'])->value('name');
            }
        }

        return $list;
    }

    public function count(): int
    {
        $nickname = $this->request->get('nickname','');
        $list = CozeAgent::alias('ca')->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            });

        if ($nickname != ''){
            $list = $list->where(function ($q) use ($nickname) {
                // admin 昵称字段
                $q->where('source', 0)
                    ->whereExists(function ($query) use ($nickname) {
                        $query->name('admin')
                            ->whereColumn('id', 'ca.source_id')
                            ->whereLike('name', "%{$nickname}%");
                    });
            })
                ->whereOr(function ($q) use ($nickname) {
                    // user 昵称字段
                    $q->where('source', 1)
                        ->whereExists(function ($query) use ($nickname) {
                            $query->name('user')
                                ->whereColumn('id', 'ca.source_id')
                                ->whereLike('nickname', "%{$nickname}%");
                        });
                }) ;
        };

        return $list->count();
    }
}
