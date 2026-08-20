<?php


namespace app\common\logic;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\model\user\UserTokensLog;

/**
 * 账户流水记录逻辑层
 * Class AccountLogLogic
 * @package app\common\logic
 */
class AccountLogLogic extends BaseLogic
{

    /**
     * @notes 账户流水记录
     * @param $userId
     * @param $changeType
     * @param $action
     * @param $changeAmount
     * @param string $sourceSn
     * @param string $remark
     * @param array $extra
     * @return UserAccountLog|false|\think\Model
     * @author 段誉
     * @date 2023/2/23 12:03
     */
    /**
     * @param int|null $teamId 强制归属企业空间;团队划拨/移出退回等须传业务所属 team_id,
     *                         避免团队主当前切在个人空间时流水 team_id=0 导致累计消耗对不上
     */
    public static function add($userId, $changeType, $action, $changeAmount, $status, string $sourceSn = '', string $remark = '', array $extra = [], ?int $teamId = null)
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            return false;
        }
        $changeObject = AccountLogEnum::getChangeObject($changeType);
        if (!$changeObject) {
            return false;
        }

        switch ($changeObject) {
                // 用户余额
            case AccountLogEnum::UM:
                $model       = new UserAccountLog();
                $left_amount = $user->user_money;
                break;
            case AccountLogEnum::TOKENS:
                $model = new UserTokensLog();
                $left_tokens = $user->tokens;
                break;
                // 其他
        }

        $data = [
            'sn'            => generate_sn(UserAccountLog::class, 'sn', 20),
            'user_id'       => $userId,
            'change_object' => $changeObject,
            'change_type'   => $changeType,
            'action'        => $action,
            'change_amount' => $changeAmount,
            'source_sn'     => $sourceSn,
            'remark'        => $remark,
            'extra'         => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : '',
        ];
        

        //如果属于系统功能算力扣费，需要记录task_id，且设置source_sn为空
        if (AccountLogEnum::checkCode($changeType)) {
            $data['source_sn'] = '';
            $data['task_id'] = $sourceSn;
        }

        if ($model instanceof UserTokensLog) {
            $data['left_tokens'] = $left_tokens ?? 0;
            $data['status'] = $status ?? 1;
            $spender = \app\common\service\TeamBillingService::resolveSpender((int)$userId);
            // 归属企业空间:调用方指定 > 企业钱包扣费主体 > TeamContext(仅团队主)
            // 成员未识别为 spender 时勿挂 team_id，否则会出现「团队明细有流水、企业钱包没扣」的假象
            if ($teamId !== null) {
                $data['team_id'] = (int)$teamId;
            } elseif ($spender !== null) {
                $data['team_id'] = (int)$spender['team_id'];
            } else {
                // 计费团队覆盖(异步任务按任务创建时的团队记账):
                // 团队主的任务消耗挂原团队(团队算力=团队主个人tokens,扣费主体本就正确),
                // 否则用户执行中切换团队后,流水会变成个人消耗、原团队明细无记录
                $override = \app\common\service\TeamBillingService::billingTeamOverride((int)$userId);
                if ($override !== null) {
                    $isOverrideOwner = $override > 0
                        && (int)(\think\facade\Db::name('team')
                            ->where('id', $override)
                            ->where(function ($q) {
                                $q->whereNull('delete_time')->whereOr('delete_time', 0);
                            })
                            ->value('owner_id') ?? 0) === (int)$userId;
                    $data['team_id'] = $isOverrideOwner ? $override : 0;
                } else {
                    $ctxTeamId = \app\common\service\TeamContextService::currentTeamId((int)$userId);
                    $isOwner = (int)($user->team_role ?? 0) === \app\common\service\TeamBillingService::ROLE_OWNER;
                    $data['team_id'] = ($isOwner && $ctxTeamId > 0) ? $ctxTeamId : 0;
                }
            }
            // 剩余算力口径:
            //  - 流水挂企业且用户为企业成员/管理员:该企业钱包余额
            //  - 团队主/个人流水:个人 tokens(上方已赋值;扣费在写流水前完成)
            // 勿因「流水带 team_id」就对团队主用 team_member.team_tokens——团队主钱包常为0,
            // 会导致企业空间通用聊天等显示「团队 0.00」,实际扣的是个人算力。
            // 显式 team_id=0 的个人流水也不要覆盖成当前企业钱包。
            $logTeamId = (int)($data['team_id'] ?? 0);
            if ($logTeamId > 0) {
                $membership = \app\common\service\TeamBillingService::findActiveMembership($logTeamId, (int)$userId);
                if ($membership !== null
                    && in_array(
                        (int)$membership['role'],
                        [
                            \app\common\service\TeamBillingService::ROLE_MEMBER,
                            \app\common\service\TeamBillingService::ROLE_ADMIN,
                        ],
                        true
                    )) {
                    $data['left_tokens'] = (float)($membership['team_tokens'] ?? 0);
                }
            }
            // 注意:team_id<=0 的个人流水(如企业空间内购买加油包,显式 teamId=0)一律保留个人 tokens,
            // 不能因用户当前是团队成员就覆盖成企业钱包余额,否则充值流水「剩余算力」显示 0
        }
        if ($model instanceof UserAccountLog) {
            $data['left_amount'] = $left_amount ?? 0;
        }
        return $model->create($data);
    }

    /**
     * 进行用户token操作 并记录
     * @param bool $success
     * @param int $userId
     * @param int $changeType
     * @param float $tokens
     * @return void
     * @author L
     * @data 2024/8/2 9:43
     */
    public static function recordUserTokensLog(bool $success, int $userId, int $changeType, float $tokens, $source_sn = '', $extra = [], ?int $teamId = null): void
    {
        $remark = AccountLogEnum::getChangeTypeDesc($changeType);
        //运行失败。token恢复
        if (!$success) {
            $billItem = (string)($extra['扣费项目'] ?? '');
            if ($billItem !== '' && str_contains($billItem, '结余预估退费')) {
                $remark = str_replace('减少算力', '超额恢复算力', $remark);
            } else {
                $remark = str_replace('减少算力', '请求失败恢复算力', $remark);
            }
            // 退费主体以原始扣费记录的企业归属为准:当初扣的是团队长算力→退回团队长;
            // 扣的是个人→退回个人。避免跨进程/切换团队后按当前状态退错主体。
            $origTeamId = null;
            if ($source_sn !== '' && $source_sn !== null) {
                $origTeamId = UserTokensLog::where('user_id', $userId)
                    ->where('task_id', $source_sn)
                    ->where('change_type', $changeType)
                    ->where('action', AccountLogEnum::DEC)
                    ->order('id desc')
                    ->value('team_id');
            }
            if ($origTeamId !== null) {
                \app\common\service\TeamBillingService::refundToTeam($userId, $tokens, (int)$origTeamId);
                // 流水归属与退费主体一致:钱退到原团队,流水也挂原团队(勿按当前空间挂)
                if ($teamId === null) {
                    $teamId = (int)$origTeamId;
                }
            } else {
                User::userTokensChange($userId, $tokens, 'inc');
            }
        }

        AccountLogLogic::add(
            $userId,
            $changeType,
            $success ? AccountLogEnum::DEC : AccountLogEnum::INC,
            $tokens,
            $success ? 1 : 2,
            $source_sn,
            $remark,
            $extra,
            $teamId
        );
    }

    /**
     * 统计用户累计业务消耗算力（净消耗）
     * 仅统计业务扣费类型，排除管理员扣减、充值退款、过期等非业务调整
     * @param int $userId
     * @return float
     */
    public static function getUserUsedTokens(int $userId): float
    {
        $excludeTypes = [
            AccountLogEnum::TOKENS_DEC_ADMIN,
            AccountLogEnum::TOKENS_DEC_RECHARGE_REFUND,
            AccountLogEnum::TOKENS_DEC_EXPIRE,
        ];
        $changeTypes = array_values(array_diff(AccountLogEnum::TOKENS_DEC, $excludeTypes));
        if (empty($changeTypes)) {
            return 0;
        }

        $totalDec = UserTokensLog::where('user_id', $userId)
            ->whereIn('change_type', $changeTypes)
            ->where('action', AccountLogEnum::DEC)
            ->sum('change_amount');
        $totalInc = UserTokensLog::where('user_id', $userId)
            ->whereIn('change_type', $changeTypes)
            ->where('action', AccountLogEnum::INC)
            ->sum('change_amount');

        return (float)($totalDec - $totalInc);
    }

    /**
     * 企业空间内按用户累计「业务净消耗」
     * = 业务 DEC(不含划拨/回收/OEM/制卡等转账类) - 同 change_type 失败退回 INC
     * 与消耗明细页合计(TeamConsumptionLists::extend)完全同口径,两处数字一致。
     * 划拨/回收属于资金转移,不计入任何人的「消耗」。
     *
     * @param int[] $userIds
     * @return array<int,float> user_id => net consumed(>=0)
     */
    public static function getTeamConsumedMap(array $userIds, int $teamId): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds || $teamId <= 0) {
            return [];
        }
        $transfer = AccountLogEnum::teamTransferTypes();
        $incTypes = AccountLogEnum::teamConsumeIncTypes();
        // status 1/2 与消耗明细 baseQuery 同口径(2=失败/超额退回,已实退钱包)
        $decMap = UserTokensLog::whereIn('user_id', $userIds)
            ->where('team_id', $teamId)
            ->whereIn('status', [1, 2])
            ->where('action', AccountLogEnum::DEC)
            ->whereNotIn('change_type', $transfer)
            ->group('user_id')
            ->column('sum(change_amount) as c', 'user_id');
        // 业务退回 INC:同 DEC 码失败退回 + 专用退费类型(9100/915x 等)
        $incSameMap = UserTokensLog::whereIn('user_id', $userIds)
            ->where('team_id', $teamId)
            ->whereIn('status', [1, 2])
            ->where('action', AccountLogEnum::INC)
            ->whereIn('change_type', $incTypes)
            ->group('user_id')
            ->column('sum(change_amount) as c', 'user_id');

        $out = [];
        foreach ($userIds as $uid) {
            $net = (float)($decMap[$uid] ?? 0) - (float)($incSameMap[$uid] ?? 0);
            $out[$uid] = $net > 0 ? round($net, 2) : 0.0;
        }
        return $out;
    }
}
