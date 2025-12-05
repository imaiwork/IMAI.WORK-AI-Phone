<?php

namespace app\api\lists\coze;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auth\Admin;
use app\common\model\coze\CozeAgent;
use app\common\model\user\User;

class CozeAgentLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'agent_cate_id', 'source'],
            'like' => ['name'],
        ];
    }

    public function lists(): array
    {
        $where = [
            ['source_id', '=', $this->userId],
            ['source', '=', CozeAgent::SOURCE_USER],
        ];

        $list = CozeAgent::where(function ($q) use ($where) {
                $q->where(function ($q2)use ($where) {
                    $q2->where($where);   
                })->whereOr('source',CozeAgent::SOURCE_ADMIN);
            })
            ->where($this->searchWhere)
            ->order(['create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

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
        }

        return $list;
    }

    public function count(): int
    {
        $where = [
            ['source_id', '=', $this->userId],
            ['source', '=', CozeAgent::SOURCE_USER],
        ];

        return CozeAgent::where(function ($q) use ($where) {
            $q->where(function ($q2)use ($where) {
                $q2->where($where);
            })->whereOr('source',CozeAgent::SOURCE_ADMIN);
        })
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}
