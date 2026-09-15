<?php

namespace app\api\lists\distributionAgent;

use app\api\lists\BaseApiDataLists;
use app\common\enum\CardCodeEnum;
use app\common\model\cardcode\CardCode;

/**
 * 分销代理卡密列表
 * Class DistributionAgentCardLists
 * @package app\api\lists\distributionAgent
 */
class DistributionAgentCardLists extends BaseApiDataLists
{
    /**
     * @notes 获取分销代理卡密列表
     * @return array
     */
    public function lists(): array
    {
        $userId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);

        $query = CardCode::alias('c')
            ->join('card_package p', 'c.package_id = p.id', 'left')
            ->where('c.user_id', $userId)
            ->where('c.type', CardCodeEnum::TYPE_DISTRIBUTION_TOKENS)
            ->where('c.delete_time', null)
            ->field('c.id, c.sn as card_code, c.card_num, c.used_num, c.balance as tokens, p.name as package_name, c.create_time');

        // 状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] == 1) {
                // 已使用完整
                $query->whereColumn('c.used_num', '>=', 'c.card_num');
            } else {
                // 未使用或部分使用
                $query->whereColumn('c.used_num', '<', 'c.card_num');
            }
        }

        // 根据卡密编码搜索
        if (!empty($this->params['sn']) && $this->params['sn'] !== '') {
            $query->where('c.sn', 'like', '%' . $this->params['sn'] . '%');
        }

        $lists = $query->order('c.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status'] = $item['used_num'] >= $item['card_num'] ? 1 : 0;
            $item['remaining_uses'] = $item['card_num'] - $item['used_num'];
        }

        return $lists;
    }

    /**
     * @notes 获取卡密总数
     * @return int
     */
    public function count(): int
    {
        $userId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);

        $query = CardCode::alias('c')
            ->where('c.user_id', $userId)
            ->where('c.type', CardCodeEnum::TYPE_DISTRIBUTION_TOKENS)
            ->where('c.delete_time', null);

        // 状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] == 1) {
                $query->whereColumn('c.used_num', '>=', 'c.card_num');
            } else {
                $query->whereColumn('c.used_num', '<', 'c.card_num');
            }
        }

        // 根据卡密编码搜索
        if (!empty($this->params['sn']) && $this->params['sn'] !== '') {
            $query->where('c.sn', 'like', '%' . $this->params['sn'] . '%');
        }

        return $query->count();
    }
}
