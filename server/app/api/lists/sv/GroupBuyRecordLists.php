<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvGroupBuyRecord;

/**
 * 团购截流记录列表
 * Class GroupBuyRecordLists
 * @package app\api\lists\sv
 */
class GroupBuyRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['ps.status', 'ps.task_type'],
            '%like%' => ['ps.account_name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.group_buy_account_id', '=', $this->request->get('id', '')];

        return SvGroupBuyRecord::alias('ps')
                               ->field('ps.*, a.nickname, a.avatar')
                               ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
                               ->where($this->searchWhere)
                               ->when(
                                   $this->request->get('start_time') && $this->request->get('end_time'),
                                   function ($query) {
                                       $query->whereBetween('ps.exec_time', [
                                           strtotime($this->request->get('start_time')),
                                           strtotime($this->request->get('end_time')),
                                       ]);
                                   }
                               )
                               ->order('ps.id', 'desc')
                               ->limit($this->limitOffset, $this->limitLength)
                               ->select()
                               ->each(function ($item) {
                                   // 超时未回报状态 -> 标记为失败
                                   if ((int)$item['status'] === 3 && (time() - $item['exec_time']) > 600) {
                                       $item['status'] = 2;
                                       $item['remark'] = '团购截流执行超时';
                                       $item->save();
                                   }
                                   if ((int)$item['status'] === 1) {
                                       $item['remark'] = '';
                                   }
                                   return $item;
                               })
                               ->toArray();
    }

    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.group_buy_account_id', '=', $this->request->get('id', '')];

        return SvGroupBuyRecord::alias('ps')
                               ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
                               ->where($this->searchWhere)
                               ->count();
    }
}