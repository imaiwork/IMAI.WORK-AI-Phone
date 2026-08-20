<?php

namespace app\api\lists\team;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\team\TeamMember;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;

/**
 * 团队成员列表(本团队成员可查看;支持按角色/成员名/手机号筛选,分页)
 * Class TeamMemberLists
 * @package app\api\lists\team
 */
class TeamMemberLists extends BaseApiDataLists implements ListsExtendInterface
{
    /** 当前操作者所在企业id(在团成员均可查看) */
    private function teamId(): int
    {
        $uid = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);
        $user = User::where('id', $uid)->field('id,team_id,team_role')->findOrEmpty();
        if ($user->isEmpty() || (int)$user->team_id <= 0 || !in_array((int)$user->team_role, [1, 2, 3], true)) {
            return 0;
        }
        return (int)$user->team_id;
    }

    /** 按关键词收窄成员(昵称/手机号) */
    private function applyKeyword($q)
    {
        $kw = trim((string)($this->params['keyword'] ?? $this->params['name'] ?? ''));
        if ($kw !== '') {
            $uids = User::where(function ($query) use ($kw) {
                $query->whereLike('nickname', '%' . $kw . '%')
                    ->whereOr('mobile', 'like', '%' . $kw . '%');
            })->column('id');
            $q->whereIn('user_id', $uids ?: [0]);
        }
        return $q;
    }

    /** 成员查询(支持按角色/成员名/手机号搜索) */
    private function memberQuery(int $teamId)
    {
        $q = TeamMember::where('team_id', $teamId);
        $role = (int)($this->params['team_role'] ?? 0);
        if (in_array($role, [1, 2, 3], true)) {
            $q->where('role', $role);
        }
        return $this->applyKeyword($q);
    }

    /**
     * 角色人数统计(筛选条展示)
     * 跟随 keyword,不跟随当前 team_role——切换标签时各角色数字仍对应当前搜索结果
     */
    public function extend()
    {
        $teamId = $this->teamId();
        if ($teamId <= 0) {
            return ['role_counts' => ['all' => 0, 'owner' => 0, 'admin' => 0, 'member' => 0]];
        }
        $q = TeamMember::where('team_id', $teamId);
        $this->applyKeyword($q);
        $rows = $q->group('role')->column('count(*) as c', 'role');
        $owner = (int)($rows[2] ?? 0);
        $admin = (int)($rows[3] ?? 0);
        $member = (int)($rows[1] ?? 0);
        return [
            'role_counts' => [
                'all' => $owner + $admin + $member,
                'owner' => $owner,
                'admin' => $admin,
                'member' => $member,
            ],
        ];
    }

    public function lists(): array
    {
        $teamId = $this->teamId();
        if ($teamId <= 0) {
            return [];
        }
        $rows = $this->memberQuery($teamId)
            ->field('user_id,role,team_tokens,expire_time')
            ->orderRaw('FIELD(role,2,3,1), id asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();
        if (!$rows) {
            return [];
        }
        $uids = array_column($rows, 'user_id');
        $userMap = array_column(
            User::whereIn('id', $uids)->field('id,sn,nickname,avatar,mobile,tokens,create_time')->select()->toArray(),
            null, 'id'
        );
        // 累计净消耗(划拨-回收等) + 最近使用,均限定本企业空间
        $consumedMap = \app\common\logic\AccountLogLogic::getTeamConsumedMap($uids, $teamId);
        $lastMap = UserTokensLog::whereIn('user_id', $uids)
            ->where('team_id', $teamId)
            ->group('user_id')->column('max(create_time) as t', 'user_id');

        $now = time();
        $list = [];
        foreach ($rows as $r) {
            $u = $userMap[$r['user_id']] ?? null;
            if (!$u) {
                continue;
            }
            $lastLog = (int)($lastMap[$r['user_id']] ?? 0);
            $expireTs = (int)$r['expire_time'];
            // 创始人消费扣个人算力 → 剩余展示个人 tokens;成员/管理员展示企业钱包
            $role = (int)$r['role'];
            $tokens = $role === 2 ? (float)($u['tokens'] ?? 0) : (float)$r['team_tokens'];
            $list[] = [
                'id'               => $u['id'],
                'sn'               => $u['sn'],
                'nickname'         => $u['nickname'],
                'avatar'           => $u['avatar'] ? \app\common\service\FileService::getFileUrl($u['avatar']) : '',
                'mobile'           => $u['mobile'],
                'tokens'           => $tokens,
                'team_role'        => $role,
                'role_desc'        => [1 => '成员', 2 => '创始人', 3 => '管理员'][$role] ?? '成员',
                // 到期时间已格式化;原始时间戳放 expire_time_ts 供前端编辑(日期选择器)用
                'team_expire_time' => $expireTs > 0 ? date('Y-m-d H:i', $expireTs) : '永久',
                'team_expire_time_desc' => $expireTs > 0 ? date('Y-m-d', $expireTs) : '永久',
                'expire_time_ts'   => $expireTs,
                'expired'          => ($expireTs > 0 && $expireTs < $now) ? 1 : 0,
                'create_time'      => date('Y-m-d H:i:s', (int)$u['create_time']),
                'total_consumed'   => (float)($consumedMap[$r['user_id']] ?? 0),
                // 最近使用时间已格式化
                'last_used_time'   => $lastLog ? date('Y-m-d H:i', $lastLog) : '-',
                'last_used_time_desc' => $lastLog ? date('m-d H:i', $lastLog) : '—',
            ];
        }
        return $list;
    }

    public function count(): int
    {
        $teamId = $this->teamId();
        if ($teamId <= 0) {
            return 0;
        }
        return $this->memberQuery($teamId)->count();
    }
}
