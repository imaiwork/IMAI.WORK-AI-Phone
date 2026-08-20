<?php

namespace app\common\service;

use app\common\model\user\User;

/**
 * 团队成员权益(到期)服务
 * —— 团队成员(team_role=1)到期后，硬拦截其算力消耗；团队主/非成员不受限。
 * Class TeamMemberService
 * @package app\common\service
 */
class TeamMemberService
{
    const ROLE_MEMBER = 1;
    const ROLE_ADMIN = 3;

    /**
     * @notes 判断某用户是否为「已到期的团队成员」
     */
    public static function isExpired(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $user = User::where('id', $userId)->field('id,team_id,team_role,team_expire_time')->findOrEmpty();
        if ($user->isEmpty()) {
            return false;
        }
        return self::isExpiredUser($user);
    }

    /**
     * @notes 已加载的 User 对象判断
     * 到期以团队成员关系表 expire_time 为准(与 getTeamInfo/切换拦截一致);
     * user.team_expire_time 仅为镜像,可能未同步导致误放行。
     */
    public static function isExpiredUser(User $user): bool
    {
        // 仅团队成员/管理员受成员到期限制;团队主(2)/散客(0)不受限
        if (!in_array((int)$user->team_role, [self::ROLE_MEMBER, self::ROLE_ADMIN], true)) {
            return false;
        }
        $teamId = (int)($user->team_id ?? 0);
        if ($teamId > 0) {
            $membership = TeamBillingService::findActiveMembership($teamId, (int)$user->id);
            if ($membership !== null) {
                $expire = (int)($membership['expire_time'] ?? 0);
                return $expire > 0 && $expire < time();
            }
        }
        $expire = (int)$user->team_expire_time;
        return $expire > 0 && $expire < time();
    }

    /**
     * @notes 到期/团队被停用则抛异常(硬拦截，接入计费与创建链路)
     * @throws \Exception
     */
    public static function assertActive(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        $user = User::where('id', $userId)->field('id,team_id,team_role,team_expire_time')->findOrEmpty();
        if ($user->isEmpty()) {
            return;
        }
        // 计费团队覆盖(异步任务按任务归属团队结算):按结算团队校验,而非用户当前所在团队
        $override = TeamBillingService::billingTeamOverride($userId);
        if ($override !== null) {
            if ($override <= 0) {
                return; // 个人空间任务:不受团队状态/成员到期限制
            }
            $membership = TeamBillingService::findActiveMembership($override, $userId);
            if ($membership !== null
                && in_array((int)$membership['role'], [self::ROLE_MEMBER, self::ROLE_ADMIN], true)) {
                $expire = (int)($membership['expire_time'] ?? 0);
                if ($expire > 0 && $expire < time()) {
                    throw new \Exception('您的团队权益已到期，请联系团队管理员续期');
                }
            }
            $status = (int)\app\common\model\team\Team::where('id', $override)->value('status');
            if ($status !== 1) {
                throw new \Exception('所在企业已被停用，暂不可使用企业空间功能');
            }
            return;
        }
        // 团队成员到期
        if (self::isExpiredUser($user)) {
            throw new \Exception('您的团队权益已到期，请联系团队管理员续期');
        }
        // 当前所在企业被平台停用 → 该企业空间内一律不可消费/建资源(运行时复查,不止登录/切换时)
        if ((int)$user->team_id > 0) {
            $status = (int)\app\common\model\team\Team::where('id', (int)$user->team_id)->value('status');
            if ($status !== 1) {
                throw new \Exception('所在企业已被停用，暂不可使用企业空间功能');
            }
        }
    }
}
