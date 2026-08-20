<?php

namespace app\common\service;

use app\common\model\user\User;
use think\facade\Db;

/**
 * 团队空间上下文服务
 * —— 统一读取用户「当前所在企业空间」,供智能体/知识库等业务资源做归属写入与列表隔离。
 *
 * 归属规则(P3 团队共享):
 *  - 用户在某企业空间内(team_id>0)创建的资源,写入 team_id=当前企业,团队全员可见可用;
 *  - 个人空间(team_id=0)创建的资源 team_id=0,仅本人可见;
 *  - 退团/移出:该用户在该企业创建的智能体/知识库标记为 team_id=-原企业ID(个人可见,团队不可见);
 *  - 重新入团:把 team_id=-该企业 的资源挂回正数 team_id,IP人设绑定自动恢复;
 *  - 列表:企业空间按 team_id + 创建者仍为有效成员过滤;个人空间按 user_id + team_id<=0。
 */
class TeamContextService
{
    /** @var array<int, bool> 请求内去重,避免列表多次触发孤儿回收 */
    private static array $orphanReclaimed = [];

    /** @var array<int, bool> 请求内去重:企业空间全员资源挂回 */
    private static array $teamRestored = [];

    /** 资源表:退团回收 / 入团恢复 */
    private static function resourceTables(): array
    {
        return ['kb_robot', 'kb_know', 'knowledge'];
    }

    /**
     * 用户当前所在企业空间 ID(0=个人空间)
     */
    public static function currentTeamId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $user = User::where('id', $userId)->field('id,team_id,team_role,team_expire_time')->findOrEmpty();
        if ($user->isEmpty()) {
            return 0;
        }
        $teamId = (int)$user->team_id;
        if ($teamId <= 0) {
            return 0;
        }
        // OEM 站点散客(role=0)仅是站点归属(供站点用户统计/站长管理),不进入企业空间上下文,
        // 否则会越权看到团队成员共享的智能体/知识库
        if (!in_array((int)$user->team_role, [
            TeamBillingService::ROLE_MEMBER,
            TeamBillingService::ROLE_OWNER,
            TeamBillingService::ROLE_ADMIN,
        ], true)) {
            return 0;
        }
        // 成员/管理员到期 → 视为退出企业空间:不再共享/使用团队智能体、知识库(列表回落个人)
        if (TeamMemberService::isExpiredUser($user)) {
            return 0;
        }
        return $teamId;
    }

    /**
     * 当前用户是否可查看/使用某企业资源。
     * 资源跟人:创建者是「我当前企业的有效成员」即可共享使用,不再限定资源创建时的 team_id
     * (成员加入团队后,其名下全部智能体/知识库即共享给该团队;退团/移除/到期自动退出共享)。
     */
    public static function canViewTeamResource(int $userId, int $resourceTeamId, int $ownerUserId = 0): bool
    {
        $resourceTeamId = (int)$resourceTeamId;
        $ownerUserId = (int)$ownerUserId;
        if ($userId <= 0) {
            return false;
        }
        $teamId = self::currentTeamId($userId);
        if ($teamId <= 0) {
            return false;
        }
        if ($ownerUserId > 0) {
            return in_array($ownerUserId, TeamBillingService::activeMemberUserIds($teamId), true);
        }
        // 未传创建者:退回按资源 team_id 匹配
        return $resourceTeamId === $teamId;
    }

    /**
     * 解析「会员能力」校验主体(如模型白名单)
     * —— 企业空间内使用他人共享资源时,按资源创建者的会员能力校验;否则按操作者本人。
     */
    public static function resolveCapabilityUserId(int $operatorUserId, int $ownerUserId, int $resourceTeamId): int
    {
        $ownerUserId = (int)$ownerUserId;
        $operatorUserId = (int)$operatorUserId;
        $resourceTeamId = (int)$resourceTeamId;
        if ($ownerUserId > 0
            && $resourceTeamId > 0
            && self::canViewTeamResource($operatorUserId, $resourceTeamId, $ownerUserId)
        ) {
            return $ownerUserId;
        }
        return $operatorUserId > 0 ? $operatorUserId : $ownerUserId;
    }

    /**
     * 退团/移出时:将该用户在指定企业创建的智能体/知识库标记为个人回收态(team_id=-teamId)。
     * —— 本人在个人空间仍可见;团队侧因 team_id 不匹配不再共享;重新入团可精确挂回。
     */
    public static function reclaimUserTeamResources(int $userId, int $teamId): void
    {
        if ($userId <= 0 || $teamId <= 0) {
            return;
        }
        $now = time();
        $marked = -$teamId;
        foreach (self::resourceTables() as $table) {
            Db::name($table)
                ->where('user_id', $userId)
                ->where('team_id', $teamId)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                })
                ->update(['team_id' => $marked, 'update_time' => $now]);
        }
    }

    /**
     * 重新入团/切回企业:把本用户从该企业回收的资源挂回企业,供团队共享。
     *  - team_id=-teamId(新回收标记)始终挂回;
     *  - includeLegacyZero=true 时,历史回收写成 0 的也挂回(仅用于入团/切回企业修复共享)。
     */
    public static function restoreUserTeamResources(int $userId, int $teamId, bool $includeLegacyZero = false): void
    {
        if ($userId <= 0 || $teamId <= 0) {
            return;
        }
        $now = time();
        $marked = -$teamId;
        foreach (self::resourceTables() as $table) {
            $query = Db::name($table)
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                });
            if ($includeLegacyZero) {
                $query->where(function ($q) use ($marked) {
                    $q->where('team_id', $marked)->whereOr('team_id', 0);
                });
            } else {
                $query->where('team_id', $marked);
            }
            $query->update(['team_id' => $teamId, 'update_time' => $now]);
        }
    }

    /**
     * 企业空间列表入口:把当前企业全员「退团回收/历史 team_id=0」资源挂回,请求内去重。
     * —— 修复「成员已回团但 PC 看不到其智能体/知识库」而无需再退入团。
     */
    public static function ensureTeamMembersResourcesRestored(int $teamId): void
    {
        if ($teamId <= 0 || isset(self::$teamRestored[$teamId])) {
            return;
        }
        self::$teamRestored[$teamId] = true;
        foreach (TeamBillingService::activeMemberUserIds($teamId) as $uid) {
            self::restoreUserTeamResources((int)$uid, $teamId, true);
        }
    }

    /**
     * 修复历史孤儿:用户已不在某企业,但其智能体/知识库仍挂着该正数 team_id → 标记为回收态。
     * 个人空间列表入口调用一次即可(请求内去重)。
     */
    public static function reclaimOrphanedOwnedResources(int $userId): void
    {
        if ($userId <= 0 || isset(self::$orphanReclaimed[$userId])) {
            return;
        }
        self::$orphanReclaimed[$userId] = true;

        $memberTeamIds = TeamBillingService::activeMemberTeamIds($userId);
        $now = time();
        foreach (self::resourceTables() as $table) {
            $rows = Db::name($table)
                ->where('user_id', $userId)
                ->where('team_id', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                })
                ->field('id,team_id')
                ->select()
                ->toArray();
            foreach ($rows as $row) {
                $tid = (int)$row['team_id'];
                if ($tid <= 0 || in_array($tid, $memberTeamIds, true)) {
                    continue;
                }
                Db::name($table)->where('id', (int)$row['id'])->update([
                    'team_id' => -$tid,
                    'update_time' => $now,
                ]);
            }
        }
    }

    /**
     * 给查询施加「当前空间可读」范围(资源跟人):
     * 企业空间→本企业全体有效成员创建的资源(不限创建时空间) ∪ 本人全部;
     * 个人空间→本人创建的全部。
     *
     * @param mixed $query think\db\Query|\think\Model
     * @return mixed
     */
    public static function applyReadableScope($query, int $userId, string $teamField = 'team_id', string $userField = 'user_id')
    {
        $teamId = self::currentTeamId($userId);
        if ($teamId > 0) {
            $memberIds = TeamBillingService::activeMemberUserIds($teamId);
            return $query->where(function ($q) use ($userField, $memberIds, $userId) {
                $q->whereIn($userField, $memberIds ?: [-1])
                    ->whereOr($userField, '=', $userId);
            });
        }
        return $query->where($userField, '=', $userId);
    }
}
