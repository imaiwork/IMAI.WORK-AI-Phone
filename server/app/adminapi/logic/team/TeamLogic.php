<?php

namespace app\adminapi\logic\team;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\team\Team;
use app\common\model\team\TeamMember;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\facade\Db;

/**
 * 团队(企业OEM)管理逻辑 —— 站长后台
 * 对齐 company-web/admin 的 team.team 契约:
 *   getInfo/create/detail/setSeat/changeStatus/openOem/oemReview/tenant/setTenant/members/oemPricing/saveOemPricing/cancelOem/wallet
 * 复用 app\api\logic\TeamLogic 的 create/tenantData/setTenantByTeam/oemUsedQuota/oemAuthNum。
 *
 * Class TeamLogic
 * @package app\adminapi\logic\team
 */
class TeamLogic extends BaseLogic
{
    const OEM_DESC = [0 => '免费版', 1 => '待审核', 2 => '已开通'];

    /**
     * @notes OEM剩余名额(与旧版OEM共用站长全局授权名额)
     */
    public static function getInfo(): array
    {
        $authnum = \app\api\logic\TeamLogic::oemAuthNum();
        $used    = \app\api\logic\TeamLogic::oemUsedQuota();
        return [
            'authnum'    => $authnum,
            'useauthnum' => $used,
            'balance'    => $authnum > 0 ? max(0, $authnum - $used) : -1, // -1=不限/未授权
        ];
    }

    /**
     * @notes 后台创建团队(指定归属用户为团队主)
     */
    public static function create(array $params): bool
    {
        $ownerId = (int)($params['owner_id'] ?? 0);
        $name    = trim((string)($params['name'] ?? ''));
        if ($ownerId <= 0) {
            self::setError('请选择团队归属用户');
            return false;
        }
        if ($name === '') {
            self::setError('请输入团队名称');
            return false;
        }
        // 委托 api 逻辑创建(owner_id 即团队主)
        $res = \app\api\logic\TeamLogic::create($ownerId, ['name' => $name, 'remark' => (string)($params['remark'] ?? '')]);
        if ($res === false) {
            self::setError(\app\api\logic\TeamLogic::getError() ?: '创建失败');
            return false;
        }
        // 备注(api create 未落库,补写)
        if (!empty($params['remark']) && !empty($res['team_id'])) {
            Team::where('id', (int)$res['team_id'])->update(['remark' => (string)$params['remark']]);
        }
        return true;
    }

    /**
     * @notes 团队详情(含团队主、租户品牌配置)
     */
    public static function detail(int $teamId): array
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            return [];
        }
        $owner = User::where('id', (int)$team->owner_id)->field('id,nickname,mobile,sn,tokens')->findOrEmpty();
        $data = $team->toArray();
        $data['owner'] = $owner->isEmpty() ? null : $owner->toArray();
        $data['oem_status_desc'] = self::OEM_DESC[(int)$team->oem_status] ?? '未知';
        $data['tenant'] = \app\api\logic\TeamLogic::tenantData($teamId);
        return $data;
    }

    /**
     * @notes 设置坐席上限(不能小于当前成员数)
     */
    public static function setSeat(int $teamId, int $seatLimit): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        if ($seatLimit < 1) {
            self::setError('坐席上限至少为1');
            return false;
        }
        $memberCount = (int)TeamMember::where('team_id', $teamId)->count();
        if ($seatLimit < $memberCount) {
            self::setError('坐席上限不能小于当前成员数(' . $memberCount . ')');
            return false;
        }
        Team::where('id', $teamId)->update(['seat_limit' => $seatLimit]);
        return true;
    }

    /**
     * @notes 启用/停用团队(status 明确传入;停用后该企业空间一律不可消费/建资源)
     */
    public static function changeStatus(int $teamId, ?int $status = null): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        $new = $status === null ? ((int)$team->status === 1 ? 0 : 1) : ($status ? 1 : 0);
        $team->status = $new;
        $team->save();
        return true;
    }

    /**
     * @notes 站长后台直接开通企业OEM(免费版→已开通,不扣团队主算力)
     */
    public static function openOem(int $teamId): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        $status = (int)$team->oem_status;
        if ($status === 2) {
            self::setError('该团队已开通OEM');
            return false;
        }
        if ($status === 1) {
            self::setError('该团队已在待审核，请走审核通过');
            return false;
        }
        if ((int)$team->status !== 1) {
            self::setError('团队已停用，无法开通OEM');
            return false;
        }
        $authNum = \app\api\logic\TeamLogic::oemAuthNum();
        if ($authNum > 0 && \app\api\logic\TeamLogic::oemUsedQuota() >= $authNum) {
            self::setError('OEM授权名额已用尽(' . \app\api\logic\TeamLogic::oemUsedQuota() . '/' . $authNum . ')，无法再开通');
            return false;
        }
        Team::where('id', $teamId)->update([
            'oem_status' => 2,
            'oem_pay_tokens' => 0,
            'oem_audit_time' => time(),
            'oem_audit_remark' => '站长后台直接开通',
        ]);
        return true;
    }

    /**
     * @notes 审核企业OEM升级申请(approve: 1=通过并开通 0=拒绝并退回预缴)
     */
    public static function oemReview(int $teamId, int $approve): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        if ((int)$team->oem_status !== 1) {
            self::setError('该团队不在待审核状态');
            return false;
        }
        // 通过前校验全局授权名额(与旧版OEM共用;authnum=0 视为不限)
        if ($approve === 1) {
            $authNum = \app\api\logic\TeamLogic::oemAuthNum();
            if ($authNum > 0 && \app\api\logic\TeamLogic::oemUsedQuota() >= $authNum) {
                self::setError('OEM授权名额已用尽(' . \app\api\logic\TeamLogic::oemUsedQuota() . '/' . $authNum . ')，无法再开通');
                return false;
            }
        }

        Db::startTrans();
        try {
            if ($approve === 1) {
                Team::where('id', $teamId)->update([
                    'oem_status' => 2,
                    'oem_audit_time' => time(),
                    'oem_audit_remark' => '',
                ]);
            } else {
                $paid = (float)$team->oem_pay_tokens;
                if ($paid > 0) {
                    $owner = User::where('id', (int)$team->owner_id)->lock(true)->findOrEmpty();
                    if (!$owner->isEmpty()) {
                        $owner->tokens = bcadd((string)$owner->tokens, (string)$paid, 2);
                        $owner->save();
                        AccountLogLogic::add(
                            (int)$owner->id,
                            AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND,
                            AccountLogEnum::INC,
                            $paid,
                            1,
                            '',
                            '企业OEM审核未通过退回预缴算力',
                            [],
                            $teamId
                        );
                    }
                }
                Team::where('id', $teamId)->update([
                    'oem_status' => 0,
                    'oem_pay_tokens' => 0,
                    'oem_audit_time' => time(),
                ]);
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 强制取消团队OEM(清状态+清域名;refund=1 时退回预缴算力给团队主)
     */
    public static function cancelOem(int $teamId, int $refund = 0): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        if ((int)$team->oem_status === 0) {
            self::setError('该团队未开通OEM');
            return false;
        }
        Db::startTrans();
        try {
            if ($refund === 1) {
                $paid = (float)$team->oem_pay_tokens;
                if ($paid > 0) {
                    $owner = User::where('id', (int)$team->owner_id)->lock(true)->findOrEmpty();
                    if (!$owner->isEmpty()) {
                        $owner->tokens = bcadd((string)$owner->tokens, (string)$paid, 2);
                        $owner->save();
                        AccountLogLogic::add(
                            (int)$owner->id,
                            AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND,
                            AccountLogEnum::INC,
                            $paid,
                            1,
                            '',
                            '站长取消企业OEM退回预缴算力',
                            [],
                            $teamId
                        );
                    }
                }
            }
            $oldDomain = \app\api\logic\TeamLogic::normalizeDomain((string)($team->domain ?? ''));
            Team::where('id', $teamId)->update([
                'oem_status' => 0,
                'oem_pay_tokens' => 0,
                'domain' => '', // 清域名,站点立即失效
                'oem_audit_time' => time(),
                'oem_audit_remark' => '站长强制取消OEM',
            ]);
            // 清域名后仍写关站落痕,避免 Host 回落主站
            if ($oldDomain !== '') {
                \app\common\service\ConfigService::set('oem_site', 'closed_domain:' . $oldDomain, (string)$teamId, 0);
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除(解散)团队 —— 退回成员企业钱包/审核中预缴给团队主,清成员/资源/配置/域名,软删团队
     */
    public static function delete(int $teamId): bool
    {
        $ok = \app\api\logic\TeamLogic::disbandByTeam($teamId);
        if (!$ok) {
            self::setError(\app\api\logic\TeamLogic::getError() ?: '删除失败');
            return false;
        }
        return true;
    }

    /**
     * @notes 团队租户配置(域名/品牌/小程序) —— 委托 api 组装
     */
    public static function tenant(int $teamId): array
    {
        return \app\api\logic\TeamLogic::tenantData($teamId);
    }

    /**
     * @notes 保存团队租户配置 —— 委托 api(按团队id,不依赖登录态)
     */
    public static function setTenant(int $teamId, array $params): bool
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        $ok = \app\api\logic\TeamLogic::setTenantByTeam($teamId, $params);
        if (!$ok) {
            self::setError(\app\api\logic\TeamLogic::getError() ?: '保存失败');
            return false;
        }
        return true;
    }

    /**
     * @notes 指定团队的成员列表
     */
    public static function members(int $teamId): array
    {
        $rows = TeamMember::where('team_id', $teamId)
            ->field('user_id,role,team_tokens,expire_time')
            ->orderRaw('FIELD(role,2,3,1), id asc')
            ->select()->toArray();
        if (!$rows) {
            return [];
        }
        $uids = array_column($rows, 'user_id');
        $userMap = array_column(
            User::whereIn('id', $uids)->field('id,nickname,avatar,mobile,tokens')->select()->toArray(),
            null, 'id'
        );
        $now = time();
        $list = [];
        foreach ($rows as $r) {
            $u = $userMap[$r['user_id']] ?? null;
            if (!$u) {
                continue;
            }
            $list[] = [
                'id'                    => $u['id'],
                'nickname'              => $u['nickname'],
                'avatar'                => $u['avatar'] ? FileService::getFileUrl($u['avatar']) : '',
                'mobile'                => $u['mobile'],
                'team_role'             => (int)$r['role'],
                'role_desc'             => [1 => '成员', 2 => '超级管理员', 3 => '管理员'][(int)$r['role']] ?? '成员',
                'tokens'                => $r['team_tokens'],        // 本企业钱包
                'personal_tokens'       => $u['tokens'],            // 个人算力
                // 原始秒级时间戳(编辑用) + 格式化展示
                'expire_timestamp'      => (int)$r['expire_time'],
                'team_expire_time'      => $r['expire_time'] > 0 ? date('Y-m-d H:i:s', (int)$r['expire_time']) : '',
                'team_expire_time_desc' => $r['expire_time'] > 0 ? date('Y-m-d H:i:s', (int)$r['expire_time']) : '永久',
                'expired'               => ($r['expire_time'] > 0 && (int)$r['expire_time'] < $now) ? 1 : 0,
            ];
        }
        return $list;
    }

    /**
     * @notes 团队算力钱包(团队主个人算力 + 全体成员企业钱包合计 + 明细)
     */
    public static function wallet(int $teamId): array
    {
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            return ['owner_tokens' => 0, 'wallet_total' => 0, 'members' => []];
        }
        $ownerId = (int)$team->owner_id;
        $ownerTokens = (float)(User::where('id', $ownerId)->value('tokens') ?? 0);
        $walletTotal = (float)(TeamMember::where('team_id', $teamId)->sum('team_tokens'));

        $rows = TeamMember::where('team_id', $teamId)
            ->field('user_id,role,team_tokens')
            ->orderRaw('FIELD(role,2,3,1), id asc')
            ->select()->toArray();
        $uids = array_column($rows, 'user_id');
        $userMap = $uids ? array_column(
            User::whereIn('id', $uids)->field('id,nickname,mobile,tokens')->select()->toArray(), null, 'id'
        ) : [];
        $members = [];
        foreach ($rows as $r) {
            $u = $userMap[$r['user_id']] ?? null;
            if (!$u) {
                continue;
            }
            $members[] = [
                'nickname'        => $u['nickname'],
                'mobile'          => $u['mobile'],
                'is_owner'        => (int)$r['user_id'] === $ownerId ? 1 : 0,
                'personal_tokens' => $u['tokens'],
                'team_tokens'     => $r['team_tokens'],
            ];
        }
        return [
            'owner_tokens' => $ownerTokens,
            'wallet_total' => $walletTotal,
            'members'      => $members,
        ];
    }

    /**
     * @notes 读取OEM收费配置
     */
    public static function oemPricing(): array
    {
        return [
            'oem_cost_price'    => (float)ConfigService::get('team', 'oem_cost_price', 0),
            'oem_upgrade_price' => (float)ConfigService::get('team', 'oem_upgrade_price', 5000),
            'oem_charge_cost'   => (float)ConfigService::get('team', 'oem_charge_cost', 0),
        ];
    }

    /**
     * @notes 保存OEM收费配置
     */
    public static function saveOemPricing(array $params): bool
    {
        foreach (['oem_cost_price', 'oem_upgrade_price', 'oem_charge_cost'] as $k) {
            if (array_key_exists($k, $params)) {
                ConfigService::set('team', $k, (float)$params[$k]);
            }
        }
        return true;
    }
}
