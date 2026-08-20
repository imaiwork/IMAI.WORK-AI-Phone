<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvGroupBuyRecord;
use app\common\model\sv\SvGroupBuyTaskAccount;

/**
 * 团购截流任务列表
 * Class GroupBuyTaskLists
 * @package app\api\lists\sv
 */
class GroupBuyTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['ps.status', 'ps.task_type'],
            '%like%' => ['ps.name', 'a.account'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];

        $list = SvGroupBuyTaskAccount::alias('ps')
                                 ->field('ps.*, a.nickname, a.avatar as account_avatar')
                                 ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
                                 ->where($this->searchWhere)
                                 ->when(
                                     $this->request->get('start_time') && $this->request->get('end_time'),
                                     function ($query) {
                                         $query->whereBetween('ps.create_time', [
                                             strtotime($this->request->get('start_time')),
                                             strtotime($this->request->get('end_time')),
                                         ]);
                                     }
                                 )
                                 ->order('ps.id', 'desc')
                                 ->limit($this->limitOffset, $this->limitLength)
                                 ->select()
                                 ->each(function ($item) {
                                     // 统计已触达数
                                     $item['record_count'] = SvGroupBuyRecord::where('group_buy_account_id', $item['id'])
                                                                             ->where('status', 1)
                                                                             ->count();
                                     return $item;
                                 })
                                 ->toArray();

        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];

        return SvGroupBuyTaskAccount::alias('ps')
                                ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
                                ->where($this->searchWhere)
                                ->count();
    }
}