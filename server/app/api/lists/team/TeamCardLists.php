<?php

namespace app\api\lists\team;

use app\api\lists\BaseApiDataLists;
use app\common\enum\CardCodeEnum;
use app\common\enum\CardCodeRecordEnum;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;
use app\common\model\user\User;
use app\common\model\user\UserLevel;

/**
 * 团队卡密列表
 * - 团队主：本企业全部卡密(算力卡 + 会员兑换码)
 * - 成员/管理员：仅自己持有的卡密(含被转移过来的)
 * Class TeamCardLists
 * @package app\api\lists\team
 */
class TeamCardLists extends BaseApiDataLists
{
    private function baseQuery()
    {
        $userId = (int)($this->userId ?: ($this->request->userInfo['user_id'] ?? 0));
        $me = User::where('id', $userId)->field('id,team_id,team_role')->findOrEmpty();
        $teamId = (int)($me->team_id ?? 0);
        $role = (int)($me->team_role ?? 0);

        $query = CardCode::alias('c')
            ->join('card_package p', 'c.package_id = p.id', 'left')
            ->leftJoin('user ou', 'c.user_id = ou.id')
            ->where('c.team_id', $teamId)
            ->whereIn('c.type', [CardCodeEnum::TYPE_DISTRIBUTION_TOKENS, CardCodeEnum::TYPE_MEMBER])
            ->where('c.delete_time', null);

        if ($teamId <= 0 || !in_array($role, [1, 2, 3], true)) {
            $query->where('c.id', 0);
        } elseif ($role !== 2) {
            $query->where('c.user_id', $userId);
        }

        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] == 1) {
                $query->whereColumn('c.used_num', '>=', 'c.card_num');
            } else {
                $query->whereColumn('c.used_num', '<', 'c.card_num');
            }
        }
        if (isset($this->params['type']) && $this->params['type'] !== '') {
            $query->where('c.type', (int)$this->params['type']);
        }
        if (!empty($this->params['sn'])) {
            $query->where('c.sn', 'like', '%' . $this->params['sn'] . '%');
        }
        return $query;
    }

    public function lists(): array
    {
        $userId = (int)($this->userId ?: ($this->request->userInfo['user_id'] ?? 0));
        $role = (int)(User::where('id', $userId)->value('team_role') ?? 0);
        $canManage = $role === 2;

        $lists = $this->baseQuery()
            ->field([
                'c.id',
                'c.sn as card_code',
                'c.type',
                'c.card_num',
                'c.used_num',
                'c.balance as tokens',
                'c.member_level_id',
                'c.member_days',
                'c.user_id as owner_id',
                'ou.nickname as owner_nickname',
                'p.name as package_name',
                'c.valid_end_time',
                'c.create_time',
            ])
            ->order('c.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $levelIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_column($lists, 'member_level_id')
        ))));
        $levelMap = $levelIds
            ? UserLevel::whereIn('id', $levelIds)->column('level_name', 'id')
            : [];

        $usedMap = $this->usedByMap(array_column($lists, 'id'));

        foreach ($lists as &$item) {
            $type = (int)$item['type'];
            $item['type_desc'] = $type === CardCodeEnum::TYPE_MEMBER ? '会员兑换码' : '算力卡';
            $item['status'] = $item['used_num'] >= $item['card_num'] ? 1 : 0;
            $item['status_desc'] = $item['status'] === 1 ? '已使用' : '未使用';
            // 过期时间与过期标记(0=永久有效)
            $validEnd = (int)($item['valid_end_time'] ?? 0);
            $item['expired'] = $validEnd > 0 && $validEnd < time() ? 1 : 0;
            $item['valid_end_time'] = $validEnd > 0 ? date('Y-m-d H:i:s', $validEnd) : '';
            $item['remaining_uses'] = $item['card_num'] - $item['used_num'];
            $item['owner_nickname'] = $item['owner_nickname'] ?: '-';
            $levelName = $levelMap[(int)$item['member_level_id']] ?? '';
            if ($type === CardCodeEnum::TYPE_MEMBER) {
                $item['content'] = ($levelName ?: '会员') . ' ' . (int)$item['member_days'] . '天';
            } else {
                $item['content'] = ((float)$item['tokens']) . ' 算力';
            }
            $item['member_level_name'] = $levelName;
            $used = $usedMap[$item['id']] ?? null;
            $item['used_by'] = $used['user_id'] ?? 0;
            $item['used_by_nickname'] = $used['nickname'] ?? '';
            $item['use_time'] = $used['use_time'] ?? 0;
            if (!empty($item['use_time']) && is_numeric($item['use_time'])) {
                $item['use_time'] = date('Y-m-d H:i:s', (int)$item['use_time']);
            }
            $item['can_manage'] = $canManage;
        }
        return $lists;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function usedByMap(array $cardIds): array
    {
        $cardIds = array_values(array_filter(array_map('intval', $cardIds)));
        if (!$cardIds) {
            return [];
        }

        $rows = CardCodeRecord::alias('r')
            ->leftJoin('user u', 'r.user_id = u.id')
            ->whereIn('r.card_id', $cardIds)
            ->where('r.status', CardCodeRecordEnum::STATYS_YES)
            ->field('r.card_id, r.user_id, u.nickname, r.use_time, r.id')
            ->order('r.id desc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $cid = (int)$row['card_id'];
            if (!isset($map[$cid])) {
                $map[$cid] = $row;
            }
        }
        return $map;
    }
}
