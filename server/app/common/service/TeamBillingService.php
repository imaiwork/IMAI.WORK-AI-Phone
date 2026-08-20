<?php

namespace app\common\service;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use think\facade\Db;
use think\facade\Log;

/**
 * 团队计费服务
 * —— 统一实现「企业空间内，团队成员消费 = 消耗团队长算力」。
 *
 * 规则:
 *  - 团队成员/管理员(team_role ∈ {1,3} 且 team_id>0):在企业空间内消费时,
 *      优先扣其「企业钱包」(team_member.team_tokens,团队长预先划拨,已从主个人算力扣走),
 *      不足部分实时回落扣「团队长个人算力」(owner.tokens);成员本人的个人算力永不被动用。
 *  - 团队主(2)/散客(0)/个人用户(team_id=0):各自消耗自己的个人算力(与原逻辑一致)。
 *
 * 退费对称:失败退费(inc)时,按进程内登记(TeamWalletRefundRegistry)把当初从企业钱包扣的份额
 *  原路退回钱包,其余退回团队长个人算力;取不到登记(跨进程/队列)时全额退回团队长个人算力
 *  —— 钱始终回到「团队长生态」,不会误退到成员个人。
 *
 * 接入点:全局唯一的 User::userTokensChange() 已改为委托本服务,
 *  因此所有 AI 扣费/退费链路自动生效,无需逐处改造。
 */
class TeamBillingService
{
    const ROLE_NONE   = 0; // 散客(站点归属)
    const ROLE_MEMBER = 1; // 团队成员
    const ROLE_OWNER  = 2; // 团队主(超管)
    const ROLE_ADMIN  = 3; // 团队管理员

    /**
     * @var array<int, int> 计费团队覆盖(userId => teamId)。
     * 异步任务(私信自动回复等)执行时,用户可能已切到其他团队,
     * resolveSpender 按 user.team_id 会扣错主体;设置覆盖后按「任务创建时的团队」结算。
     * 0=强制个人空间;workerman 常驻进程务必 finally 清除,避免污染后续消息。
     */
    private static array $billingTeamOverrides = [];

    /** 设置计费团队覆盖(teamId=0 表示强制按个人结算) */
    public static function setBillingTeamOverride(int $userId, int $teamId): void
    {
        if ($userId > 0) {
            self::$billingTeamOverrides[$userId] = max(0, $teamId);
        }
    }

    public static function clearBillingTeamOverride(int $userId): void
    {
        unset(self::$billingTeamOverrides[$userId]);
    }

    /** 当前生效的计费团队覆盖;null=无覆盖(按 user.team_id) */
    public static function billingTeamOverride(int $userId): ?int
    {
        return self::$billingTeamOverrides[$userId] ?? null;
    }

    /** 以指定计费团队执行回调,结束后自动恢复(嵌套安全) */
    public static function runWithBillingTeam(int $userId, int $teamId, callable $fn)
    {
        $prev = self::$billingTeamOverrides[$userId] ?? null;
        self::setBillingTeamOverride($userId, $teamId);
        try {
            return $fn();
        } finally {
            if ($prev === null) {
                self::clearBillingTeamOverride($userId);
            } else {
                self::$billingTeamOverrides[$userId] = $prev;
            }
        }
    }

    /**
     * 有效成员关系(兼容 delete_time 为 NULL 或 0;绕过 SoftDelete 误伤)。
     * @return array{id?:int,team_id:int,user_id:int,role:int,team_tokens:string|float,expire_time:int}|null
     */
    public static function findActiveMembership(int $teamId, int $userId): ?array
    {
        if ($teamId <= 0 || $userId <= 0) {
            return null;
        }
        $row = Db::name('team_member')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('delete_time')->whereOr('delete_time', 0);
            })
            ->field('id,team_id,user_id,role,team_tokens,expire_time')
            ->find();
        return $row ?: null;
    }

    /**
     * 某企业当前有效成员 user_id 列表(退团/踢出/成员资格到期后不再包含)。
     * 调用方均为「资源共享名单」场景:到期成员的智能体/知识库不再共享给团队,续期后自动恢复。
     * @return int[]
     */
    public static function activeMemberUserIds(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }
        $ids = Db::name('team_member')
            ->where('team_id', $teamId)
            ->where(function ($q) {
                $q->whereNull('delete_time')->whereOr('delete_time', 0);
            })
            ->where(function ($q) {
                // expire_time=0 永久(含创始人);到期时间已过的成员/管理员剔除
                $q->where('expire_time', 0)->whereOr('expire_time', '>', time());
            })
            ->column('user_id');
        return array_values(array_unique(array_map('intval', $ids ?: [])));
    }

    /**
     * 某用户当前仍有效的企业 ID 列表(成员资格到期的企业不算)。
     * —— 供个人空间孤儿资源回收判断:到期成员的资源标记回个人可见,续期后由列表入口自动挂回团队。
     * @return int[]
     */
    public static function activeMemberTeamIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $ids = Db::name('team_member')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('delete_time')->whereOr('delete_time', 0);
            })
            ->where(function ($q) {
                // expire_time=0 永久(含创始人);到期时间已过的成员/管理员剔除
                $q->where('expire_time', 0)->whereOr('expire_time', '>', time());
            })
            ->column('team_id');
        return array_values(array_unique(array_map('intval', $ids ?: [])));
    }

    /**
     * 解析消费者:是否为「在企业空间内消耗团队长算力」的成员。
     * @param int $userId
     * @return array{team_id:int, owner_id:int}|null  null 表示按个人算力结算
     */
    public static function resolveSpender(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $user = User::where('id', $userId)->field('id,team_id,team_role')->findOrEmpty();
        if ($user->isEmpty()) {
            return null;
        }
        // 计费团队覆盖优先(异步任务按任务创建时的团队结算);0=强制个人
        $override = self::billingTeamOverride($userId);
        $teamId = $override !== null ? $override : (int)$user->team_id;
        if ($teamId <= 0) {
            return null;
        }
        // 以 team_member 为准:入团后若 user.team_role 未同步(仍为散客0),
        // 旧逻辑会走个人扣费,但流水仍可能带 team_id → 团队明细看得到、企业钱包没扣。
        // SoftDelete 默认 whereNull(delete_time),历史数据 delete_time=0 会被误判为已删,
        // 导致成员预检走个人算力 →「用户算力不足」,而页面展示的企业钱包仍有余额。
        $membership = self::findActiveMembership($teamId, $userId);
        // 覆盖团队 ≠ 用户当前团队时:role 只认该团队的成员关系,不回落 user.team_role(那是当前团队的快照)
        $isOverridden = $override !== null && $override !== (int)$user->team_id;
        $role = $membership !== null
            ? (int)$membership['role']
            : ($isOverridden ? self::ROLE_NONE : (int)$user->team_role);
        // team_role 快照同步仅在「结算团队=当前团队」时进行,勿用其他团队的角色污染当前快照
        if (!$isOverridden
            && $membership !== null
            && in_array((int)$membership['role'], [self::ROLE_MEMBER, self::ROLE_ADMIN], true)
            && (int)$user->team_role !== (int)$membership['role']) {
            User::where('id', $userId)->update(['team_role' => (int)$membership['role']]);
        }
        // 仅「团队成员/管理员」在企业空间内消耗企业钱包
        if (!in_array($role, [self::ROLE_MEMBER, self::ROLE_ADMIN], true)) {
            return null;
        }
        // 团队主查询同样兼容 delete_time=0
        $ownerId = (int)(Db::name('team')
            ->where('id', $teamId)
            ->where(function ($q) {
                $q->whereNull('delete_time')->whereOr('delete_time', 0);
            })
            ->value('owner_id') ?? 0);
        if ($ownerId <= 0 || $ownerId === $userId) {
            return null;
        }
        return ['team_id' => $teamId, 'owner_id' => $ownerId];
    }

    /**
     * 企业空间内可用算力(成员=企业钱包;其他=个人算力)。
     * 供扣费前的余额校验使用,避免成员个人算力为 0 却被误拦。
     */
    public static function spendableTokens(int $userId): float
    {
        $spender = self::resolveSpender($userId);
        if ($spender === null) {
            return (float)User::where('id', $userId)->value('tokens');
        }
        $membership = self::findActiveMembership((int)$spender['team_id'], $userId);
        return (float)($membership['team_tokens'] ?? 0);
    }

    /**
     * 扣费(dec)。成员:企业钱包优先 → 回落团队长个人算力;其他:扣自己个人算力(与原逻辑一致)。
     * @throws \Exception 团队算力不足时抛异常(code 4059),防止透支。
     */
    public static function deduct(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }
        // 团队被平台停用 / 团队成员已到期 → 一律拦截消费(运行时硬校验)
        TeamMemberService::assertActive($userId);
        $spender = self::resolveSpender($userId);
        if ($spender === null) {
            // 仍挂着 team_id 却走个人:多为 team_member 缺失/role 不对/未部署或未重启常驻进程
            $probeTeamId = (int)(User::where('id', $userId)->value('team_id') ?? 0);
            if ($probeTeamId > 0) {
                $probe = self::findActiveMembership($probeTeamId, $userId);
                Log::warning(sprintf(
                    'TeamBilling personal-fallback user=%d team_id=%d membership=%s role=%s amount=%s',
                    $userId,
                    $probeTeamId,
                    $probe ? 'yes' : 'no',
                    $probe['role'] ?? (User::where('id', $userId)->value('team_role') ?? ''),
                    $amount
                ));
            }
            // 团队主/散客/个人:扣个人算力(不足拦截,禁止透支到负数)
            $amt = (string)$amount;
            Db::startTrans();
            try {
                $row = User::where('id', $userId)->lock(true)->findOrEmpty();
                if ($row->isEmpty() || bccomp((string)$row->tokens, $amt, 2) < 0) {
                    throw new \Exception('用户算力不足', 4059);
                }
                $row->tokens = bcsub((string)$row->tokens, $amt, 2);
                $row->save();
                Db::commit();
                return true;
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }
        }

        $teamId = $spender['team_id'];
        $amt    = (string)$amount;

        Db::startTrans();
        try {
            // 行锁重取,防并发下钱包双花(兼容 delete_time NULL/0)
            $membership = Db::name('team_member')
                ->where('team_id', $teamId)
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                })
                ->lock(true)
                ->find();
            $wallet = (string)($membership['team_tokens'] ?? '0');
            // 成员只能用团队长划拨的企业钱包,不足直接拦截(不再回落团队长个人算力)
            if (!$membership || bccomp($wallet, $amt, 2) < 0) {
                throw new \Exception('团队算力不足，请联系团队主分配', 4059);
            }
            Db::name('team_member')->where('id', (int)$membership['id'])->update([
                'team_tokens' => bcsub($wallet, $amt, 2),
                'update_time' => time(),
            ]);
            Db::commit();
            // 登记企业钱包扣款,供失败退费按原路退回钱包
            TeamWalletRefundRegistry::push($userId, $teamId, (float)$amt);
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 退费(inc)——按「用户当前所在企业」推断退费主体(供无原始记录上下文的直连退费兜底)。
     * 成员:按登记退回企业钱包,其余退团队长个人算力;其他:退回自己个人算力。
     */
    public static function refund(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }
        $spender = self::resolveSpender($userId);
        if ($spender === null) {
            User::where('id', $userId)->inc('tokens', $amount)->update();
            return true;
        }
        return self::refundToTeam($userId, $amount, $spender['team_id']);
    }

    /**
     * 按原始扣费流水的企业归属退费,返回应写入退费流水的 team_id。
     * —— 数字人/闪剪等「结余退费」勿直接改 user.tokens,否则团队模式只加个人余额、剩余不涨。
     */
    public static function refundByOriginalLog(int $userId, float $amount, int $decChangeType, string $taskId): int
    {
        if ($amount <= 0) {
            return TeamContextService::currentTeamId($userId);
        }
        $origTeamId = UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('change_type', $decChangeType)
            ->where('action', AccountLogEnum::DEC)
            ->order('id desc')
            ->value('team_id');
        if ($origTeamId !== null) {
            self::refundToTeam($userId, $amount, (int)$origTeamId);
            return (int)$origTeamId;
        }
        User::userTokensChange($userId, $amount, 'inc');
        return TeamContextService::currentTeamId($userId);
    }

    /**
     * 企业钱包入账(成员/管理员)。由调用方负责事务,不另开事务。
     * 非成员/团队主返回 false,调用方应改入个人算力。
     */
    public static function creditTeamWallet(int $userId, float $amount, int $teamId): bool
    {
        if ($amount <= 0 || $teamId <= 0 || $userId <= 0) {
            return false;
        }
        $membership = self::findActiveMembership($teamId, $userId);
        if ($membership === null
            || !in_array((int)$membership['role'], [self::ROLE_MEMBER, self::ROLE_ADMIN], true)) {
            return false;
        }
        $m = Db::name('team_member')
            ->where('id', (int)$membership['id'])
            ->lock(true)
            ->find();
        if (!$m) {
            return false;
        }
        Db::name('team_member')->where('id', (int)$m['id'])->update([
            'team_tokens' => bcadd((string)$m['team_tokens'], (string)$amount, 2),
            'update_time' => time(),
        ]);
        return true;
    }

    /**
     * 结算补扣——补扣主体以「原始预扣流水的企业归属」为准,与 refundByOriginalLog 对称:
     * 预扣后用户切换空间,多退少补仍算在预扣那一次的空间上。
     * 返回应写入补扣流水的 team_id;查不到原始流水时维持旧口径(按当前空间扣)。
     */
    public static function deductByOriginalLog(int $userId, float $amount, int $decChangeType, string $taskId): int
    {
        if ($amount <= 0) {
            return TeamContextService::currentTeamId($userId);
        }
        $origTeamId = UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('change_type', $decChangeType)
            ->where('action', AccountLogEnum::DEC)
            ->order('id desc')
            ->value('team_id');
        if ($origTeamId === null) {
            User::userTokensChange($userId, $amount);
            return TeamContextService::currentTeamId($userId);
        }
        self::deductFromTeam($userId, $amount, (int)$origTeamId);
        return (int)$origTeamId;
    }

    /**
     * 从「指定企业」补扣(refundToTeam 的反向操作)。
     *  - $teamId<=0:扣用户个人算力;
     *  - $teamId>0 且用户是该团队成员/管理员:扣该企业钱包;
     *  - 成员关系已不存在(退团/到期移除):回退扣个人。
     * 补扣是既成事实的消耗结算,不做余额拦截(允许扣至负数),避免任务结算被算力不足卡死。
     */
    public static function deductFromTeam(int $userId, float $amount, int $teamId): bool
    {
        if ($amount <= 0) {
            return true;
        }
        if ($teamId > 0) {
            $membership = self::findActiveMembership($teamId, $userId);
            $isTeamStaff = $membership !== null
                && in_array((int)$membership['role'], [self::ROLE_MEMBER, self::ROLE_ADMIN], true);
            if ($isTeamStaff) {
                Db::startTrans();
                try {
                    $m = Db::name('team_member')
                        ->where('team_id', $teamId)
                        ->where('user_id', $userId)
                        ->where(function ($q) {
                            $q->whereNull('delete_time')->whereOr('delete_time', 0);
                        })
                        ->lock(true)
                        ->find();
                    if ($m) {
                        Db::name('team_member')->where('id', (int)$m['id'])->update([
                            'team_tokens' => bcsub((string)$m['team_tokens'], (string)$amount, 2),
                            'update_time' => time(),
                        ]);
                        Db::commit();
                        return true;
                    }
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    throw $e;
                }
            }
        }
        User::where('id', $userId)->dec('tokens', $amount)->update();
        return true;
    }

    /**
     * 退费到「指定企业」——退费主体以原始扣费的企业归属为准(权威),跨进程/切换团队后仍退对人。
     *  - $teamId<=0:退回用户个人算力;
     *  - $teamId>0:先按登记退回企业钱包,其余退回该团队长个人算力(当初扣团队长的,原路退团队长);
     *  - 团队已解散/团队主不存在:回退到个人,避免算力凭空消失。
     */
    public static function refundToTeam(int $userId, float $amount, int $teamId): bool
    {
        if ($amount <= 0) {
            return true;
        }
        if ($teamId <= 0) {
            User::where('id', $userId)->inc('tokens', $amount)->update();
            return true;
        }
        // 仅「团队成员/管理员」的消费才是消耗团队钱包→退回钱包;
        // 散客(无成员关系)/非成员消费的是自己个人算力→退回自己。
        $membership = self::findActiveMembership($teamId, $userId);
        $isTeamStaff = $membership !== null
            && in_array((int)$membership['role'], [self::ROLE_MEMBER, self::ROLE_ADMIN], true);
        if (!$isTeamStaff) {
            User::where('id', $userId)->inc('tokens', $amount)->update();
            return true;
        }

        Db::startTrans();
        try {
            // 成员消费只扣了企业钱包 → 全额退回该企业钱包
            $m = Db::name('team_member')
                ->where('team_id', $teamId)
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                })
                ->lock(true)
                ->find();
            if ($m) {
                Db::name('team_member')->where('id', (int)$m['id'])->update([
                    'team_tokens' => bcadd((string)$m['team_tokens'], (string)$amount, 2),
                    'update_time' => time(),
                ]);
            } else {
                // 成员关系已不存在(如已退团):回退个人,保证不丢算力
                User::where('id', $userId)->inc('tokens', $amount)->update();
            }
            // 核销登记
            TeamWalletRefundRegistry::take($userId, $amount);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}
