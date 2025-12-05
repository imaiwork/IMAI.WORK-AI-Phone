<?php
declare (strict_types = 1);

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\model\sop\SopSubFlowRemind;

/**
 * 跟进提醒列表
 * Class RemindLists
 * @package app\api\lists\sop
 */
class RemindLists extends BaseApiDataLists
{
    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['delete_time', 'null', null];

        return SopSubFlowRemind::where($this->searchWhere)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['delete_time', 'null', null];
        return SopSubFlowRemind::where($this->searchWhere)->count();
    }
} 