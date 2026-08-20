<?php

namespace app\adminapi\logic\user;

use app\api\logic\LoginLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\logic\RechargeStatsLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\model\member\MemberUser;
use app\common\model\survey\Surveys;
use app\common\cache\UserTokenCache;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use app\common\model\user\UserSession;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\MemberService;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Config;
use think\facade\Db;

/**
 * 用户逻辑层
 * Class UserLogic
 * @package app\adminapi\logic\user
 */
class UserLogic extends BaseLogic
{

    /**
     * @notes 用户详情
     * @param int $userId
     * @return array
     * @author 段誉
     * @date 2022/9/22 16:32
     */
    public static function detail(int $userId): array
    {
        $field = [
            'id',
            'sn',
            'account',
            'nickname',
            'avatar',
            'real_name',
            'sex',
            'mobile',
            'create_time',
            'login_time',
            'channel',
            'source',
            'user_money',
            'tokens',
            'user_type',
            'level_id',
            'is_disable',
            'team_id',
            'team_role',
            'recharge_stats_reset_time',
        ];

        $user = User::where(['id' => $userId])->field($field)
            ->findOrEmpty();

        // 前端详情页沿用 is_blacklist 字段名展示拉黑状态
        $user['is_blacklist'] = (int)$user['is_disable'];
        $user['channel'] = UserTerminalEnum::getTermInalDesc($user['channel']);

        $user->sex = $user->getData('sex');
        $user['level_name'] = intval($user['level_id'] ?? -1) > 0
            ? (UserLevel::where('id', intval($user['level_id']))->value('level_name') ?? '')
            : '';

        //加载企业信息
        $user['company_name'] = Surveys::where('user_id', $user['id'])->value('company_name') ?? '';

        // 当前团队名称(team_role>0 才算在团队中;散客 team_id 仅是 OEM 站点归属,不展示)
        $user['team_name'] = '';
        if ((int)$user['team_id'] > 0 && (int)$user['team_role'] > 0) {
            $user['team_name'] = (string)(\app\common\model\team\Team::where('id', (int)$user['team_id'])->value('name') ?? '');
        }

        // 用户自己的累计充值金额，不受下级业绩清零影响
        $user['sum_price'] = RechargeStatsLogic::getUserAmount($userId);

        // 下级累计充值金额：名下所有层级下级的已支付充值订单合计，即可清零的「充值业绩」，
        // 按该用户的清零水位线统计，与代理端主页的「团队累计充值」同口径
        $resetTime = (int)$user['recharge_stats_reset_time'];
        $subUserIds = DistributionAgent::getDescendantIds($userId);
        $subRecharge = RechargeStatsLogic::getTotal($subUserIds, $resetTime);
        $user['sub_recharge_amount'] = $subRecharge['amount'];
        $user['sub_recharge_count'] = $subRecharge['order_count'];
        $user['sub_user_count'] = count($subUserIds);

        // 下级历史充值总额：不带水位线，含历次清零掉的金额，只做留档展示，不参与业绩考核
        // 从未清零过时两个口径本就相同，不必多查一次
        $subHistory = $resetTime > 0 ? RechargeStatsLogic::getTotal($subUserIds) : $subRecharge;
        $user['sub_recharge_history_amount'] = $subHistory['amount'];
        $user['sub_recharge_history_count'] = $subHistory['order_count'];
        $user['recharge_stats_reset_time'] = $resetTime;
        $user['recharge_stats_reset_time_text'] = $resetTime > 0 ? date('Y-m-d H:i:s', $resetTime) : '';

        $user['orders'] = [];

        // 累计算力使用次数
        $user['tokens_times'] = UserTokensLog::where('user_id', $userId)->where('task_id', '<>', '')->count('DISTINCT task_id');

        // 分销代理信息
        $defaultInviteSource = LoginLogic::getDefaultInviteSource();
        $distribution = DistributionAgent::where('user_id', $userId)->findOrEmpty();
        if (!$distribution->isEmpty()) {
            $user['distribution_level'] = $distribution->level;
            $user['distribution_parent_id'] = $distribution->parent_id;
            $user['distribution_become_time'] = $distribution->become_time;
            $user['distribution_status'] = $distribution->status;

            $user['distribution_downline_count'] = DistributionAgent::where('parent_id', $userId)->count();
        } else {
            $user['distribution_level'] = 0;
            $user['distribution_parent_id'] = 0;
            $user['distribution_become_time'] = 0;
            $user['distribution_status'] = 1;
            $user['distribution_downline_count'] = 0;
        }

        // 上级邀请人：按 distribution_agent.parent_id 查真实昵称（勿用 user.source，那是列表「邀请来源」默认文案）
        $parentId = (int)($user['distribution_parent_id'] ?? 0);
        $user['distribution_parent_name'] = $defaultInviteSource;
        if ($parentId > 0) {
            $parent = User::where('id', $parentId)->field('sn,nickname')->findOrEmpty();
            if (!$parent->isEmpty()) {
                $parentSn = (string)($parent['sn'] ?? '');
                $parentName = (string)($parent['nickname'] ?? '');
                $user['distribution_parent_name'] = $parentName !== ''
                    ? $parentName
                    : ($parentSn !== '' ? $parentSn : '未知用户');
            } else {
                $user['distribution_parent_name'] = '未知用户';
            }
        }

        // 统计用户累计消耗算力
        $user['used_tokens'] = AccountLogLogic::getUserUsedTokens($userId);

        // 会员订阅信息
        $member = \app\common\model\member\MemberUser::where('user_id', $userId)->findOrEmpty();
        if (!$member->isEmpty()) {
            $memberLevel = UserLevel::findOrEmpty($member->level_id);
            $user['member_level_id']   = $member->level_id;
            $user['member_level_name'] = $memberLevel->isEmpty() ? '' : $memberLevel->level_name;
            $user['member_start_time'] = self::formatMemberTime((int)$member->start_time);
            $user['member_end_time']   = self::formatMemberTime((int)$member->end_time);
            $user['member_status']     = $member->status;
            $user['is_member'] = $member->status == \app\common\model\member\MemberUser::STATUS_ACTIVE
                && $member->end_time > time();
        } else {
            $default = \app\common\service\MemberService::getDefaultLevel();
            $user['member_level_id']   = $default ? $default->id : 0;
            $user['member_level_name'] = $default ? $default->level_name : '普通用户';
            $user['member_start_time'] = '';
            $user['member_end_time']   = '';
            $user['member_status']     = 0;
            $user['is_member']         = false;
        }

        return $user->toArray();
    }

    /** 会员时间戳转 Y-m-d H:i:s,无效则空串 */
    private static function formatMemberTime(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }


    /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserInfo(array $params)
    {
        return User::update([
            'id' => $params['id'],
            $params['field'] => $params['value']
        ]);
    }

    /**
     * @notes 拉黑/解除拉黑用户（写入 is_disable，拉黑后踢下线）
     * @param array $params id, type(1拉黑 2解除，缺省则取反)
     * @return bool
     */
    public static function blacklist(array $params): bool
    {
        try {
            $user = User::findOrEmpty($params['id']);
            if ($user->isEmpty()) {
                throw new Exception('用户不存在');
            }

            if (isset($params['type']) && in_array((int)$params['type'], [1, 2], true)) {
                $isDisable = (int)$params['type'] === 1 ? 1 : 0;
            } else {
                $isDisable = (int)$user->is_disable === 1 ? 0 : 1;
            }

            $user->is_disable = $isDisable;
            $user->update_time = time();
            $user->save();

            if ($isDisable === 1) {
                self::expireUserTokens((int)$user->id);
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 使指定用户全部登录态失效
     */
    private static function expireUserTokens(int $userId): void
    {
        $sessions = UserSession::where('user_id', $userId)->select();
        if ($sessions->isEmpty()) {
            return;
        }

        $cache = new UserTokenCache();
        $now = time();
        foreach ($sessions as $session) {
            $cache->deleteUserInfo((string)$session->token);
            $session->expire_time = $now;
            $session->update_time = $now;
            $session->save();
        }
    }

    /**
     * @notes 变更用户会员等级
     * 同步 User.level_id + MemberUser，使 userLevel/edit 配置的配额立即生效
     * （grant_tokens / grant_cycle / max_* / allowed_models 等）
     * @param array $params id, level_id, days|day(可选，开通/覆盖有效天数)
     * @return bool
     */
    public static function changeLevel(array $params): bool
    {
        Db::startTrans();
        try {
            $levelId = intval($params['level_id']);
            // 兼容前端传 day / days
            $days = intval($params['days'] ?? $params['day'] ?? 0);
            $user = User::findOrEmpty($params['id']);
            if ($user->isEmpty()) {
                throw new Exception('用户不存在');
            }

            $level = null;
            $levelName = '';
            if ($levelId > 0) {
                $level = UserLevel::findOrEmpty($levelId);
                if ($level->isEmpty()) {
                    throw new Exception('会员等级不存在');
                }
                if ((int)$level->status !== 1) {
                    throw new Exception('会员等级已禁用');
                }
                $levelName = (string)$level->level_name;
            }

            $user->level_id = $levelId;
            $user->update_time = time();
            $user->save();

            // 同步订阅记录，否则配额仍按旧 MemberUser.level_id / 默认等级计算
            if ($levelId <= 0 || ($level && (int)$level->is_default === 1)) {
                $member = MemberUser::where('user_id', $user->id)->findOrEmpty();
                if (!$member->isEmpty() && (int)$member->status === MemberUser::STATUS_ACTIVE) {
                    MemberService::expireAndFreeze($member);
                }
            } else {
                self::syncMembershipOnLevelChange((int)$user->id, $levelId, $days, $level);
                MemberService::thawWithinQuota((int)$user->id);
            }

            $quota = MemberService::getQuota((int)$user->id);
            $member = MemberUser::where('user_id', $user->id)->findOrEmpty();

            self::$returnData = [
                'id' => (int)$user->id,
                'level_id' => $levelId,
                'level_name' => $levelName,
                // 与 userLevel/edit 同套配额字段，便于前端核对
                'grant_tokens' => (float)($quota['grant_tokens'] ?? 0),
                'grant_cycle' => (int)($quota['grant_cycle'] ?? 0),
                'max_robots' => (int)($quota['max_robots'] ?? 0),
                'max_knowledges' => (int)($quota['max_knowledges'] ?? 0),
                'max_personas' => (int)($quota['max_personas'] ?? 0),
                'max_mobiles' => (int)($quota['max_mobiles'] ?? 0),
                'max_digital_humans' => (int)($quota['max_digital_humans'] ?? 0),
                'max_voices' => (int)($quota['max_voices'] ?? 0),
                'allowed_models' => $quota['allowed_models'] ?? [],
                'member_end_time' => $member->isEmpty() ? 0 : (int)$member->end_time,
                'member_status' => $member->isEmpty() ? 0 : (int)$member->status,
                'is_member' => !empty($quota['is_member']),
            ];

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 变更等级时同步 MemberUser，并按新等级发放周期算力
     *
     * 指定天数时（有效会员）：
     * - 从原激活时间 start_time 起算新到期 = start_time + days
     * - 新到期晚于原到期：覆盖 end_time（激活时间不变）
     * - 新到期不晚于原到期：保留原 end_time / start_time，仅换等级
     * 无有效会员：按当前时间开通
     */
    private static function syncMembershipOnLevelChange(int $userId, int $levelId, int $days, UserLevel $level): void
    {
        $member = MemberUser::where('user_id', $userId)->findOrEmpty();
        $now = time();
        $isActive = !$member->isEmpty()
            && (int)$member->status === MemberUser::STATUS_ACTIVE
            && (int)$member->end_time > $now;

        if ($days > 0) {
            if ($isActive && (int)$member->start_time > 0) {
                $oldEnd = (int)$member->end_time;
                $newEnd = (int)$member->start_time + $days * 86400;
                $member->level_id = $levelId;
                // 仅当新时长覆盖后更晚才改到期时间；否则保留原到期
                if ($newEnd > $oldEnd) {
                    $member->end_time = $newEnd;
                }
                $member->status = MemberUser::STATUS_ACTIVE;
                $member->last_grant_time = 0;
                $member->source = MemberUser::SOURCE_ADMIN;
                $member->source_remark = '后台变更会员等级';
                $member->update_time = $now;
                $member->save();
                MemberService::grantTokensIfDue($member);
                return;
            }

            MemberService::grant($userId, $levelId, $days, MemberUser::SOURCE_ADMIN, '后台变更会员等级');
            return;
        }

        if ($isActive) {
            // 未传天数：保留原到期时间，仅切换等级配额
            $member->level_id = $levelId;
            $member->last_grant_time = 0;
            $member->source = MemberUser::SOURCE_ADMIN;
            $member->source_remark = '后台变更会员等级';
            $member->update_time = $now;
            $member->save();
            MemberService::grantTokensIfDue($member);
            return;
        }

        // 无有效订阅时按发放周期给默认天数，使新等级配额立刻生效
        $defaultDays = match ((int)$level->grant_cycle) {
            UserLevel::CYCLE_DAY => 1,
            UserLevel::CYCLE_MONTH => 30,
            UserLevel::CYCLE_YEAR => 365,
            default => 365,
        };
        MemberService::grant($userId, $levelId, $defaultDays, MemberUser::SOURCE_ADMIN, '后台变更会员等级');
    }

    /**
     * @notes 更新用户分销信息
     * @param array $params
     * @author MonitorAllen
     * @date 
     */
    public static function setDistributionInfo(array $params)
    {
        $agent = DistributionAgent::where('user_id', $params['id'])->findOrEmpty();
        if ($agent->isEmpty()) {
            $agent->user_id = $params['id'];
            $agent->create_time = time();
        }
        $agent->{$params['field']} = $params['value'];
        $agent->update_time = time();

        if ($params['field'] == 'level' && $params['value'] > 0) {
            $agent->status = 1;
        }

        // 后台可以分别调整 level / parent_id / status，任意一次操作让用户成为有效代理后
        // 都要补上加入时间，否则代理端下级列表的「加入时间」拿不到值
        if ((int)$agent->level > 0 && empty($agent->become_time)) {
            $agent->become_time = time();
        }

        return $agent->save();
    }


    /**
     * @notes 调整用户余额
     * @param array $params
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 14:25
     */
    public static function adjustUserMoney(array $params)
    {
        Db::startTrans();
        try {
            $user = User::find($params['user_id']);
            if (AccountLogEnum::INC == $params['action']) {
                //调整可用余额
                $user->user_money += $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_INC_ADMIN,
                    AccountLogEnum::INC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );
            } else {
                $user->user_money -= $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }


    /**
     * @notes 调整用户算力
     * @param array $params
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 14:25
     */
    public static function adjustUserTokens(array $params)
    {
        Db::startTrans();
        try {
            $user = User::find($params['user_id']);
            if (AccountLogEnum::INC == $params['action']) {
                //调整可用余额
                $user->tokens += $params['num'];
                $user->save();
                //记录日志
                // 后台调整的是用户个人 tokens,流水显式 teamId=0;
                // 否则用户当前在团队空间时会被自动挂上 team_id,误入团队消耗明细
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::TOKENS_INC_ADMIN,
                    AccountLogEnum::INC,
                    $params['num'],
                    '1',
                    '',
                    !empty($params['remark']) ? $params['remark'] : '管理员增加算力',
                    [],
                    0
                );
            } else {
                $user->tokens -= $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::TOKENS_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $params['num'],
                    '1',
                    '',
                    !empty($params['remark']) ? $params['remark'] : '管理员扣除算力',
                    [],
                    0
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }


    /**
     * @notes 下级充值业绩清零
     *
     * 只推进该用户的统计水位线 recharge_stats_reset_time，不动任何系统账单：
     * 充值订单（la_gift_package_order）、算力流水（la_user_tokens_log）、
     * 余额流水（la_user_account_log）全部原样保留，用户算力与余额也不变。
     * 水位线属于该用户自己的下级业绩视图，所以清零后：
     * 该用户的「下级累计充值金额」与代理端「团队累计充值 / 下级充值业绩」只统计清零之后的订单，
     * 而每个下级自己的「累计充值金额」保持不变。
     * 被清零的金额可由水位线之前的订单反推，无需额外快照。
     *
     * @param array $params
     * @return bool
     */
    public static function resetRechargeStats(array $params): bool
    {
        try {
            $userId = (int)$params['id'];
            $cleared = RechargeStatsLogic::resetSubStats($userId, DistributionAgent::getDescendantIds($userId));
            self::$returnData = [
                'cleared_amount' => $cleared['amount'],
                'cleared_count' => $cleared['order_count'],
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserPas(array $params)
    {


        $user = User::findOrEmpty($params['id']);
        if (empty($user)) {
            return false;
        }
        if (!empty($params['password'])) {
            $passwordSalt = Config::get('project.unique_identification');
            $params['password'] = create_password($params['password'], $passwordSalt);
        }

        return User::update([
            'id' => $params['id'],
            'password' => $params['password']
        ]);
    }


    public static function createUser(array $params): bool
    {
        try {
            $userSn = User::createUserSn();
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($params['password'], $passwordSalt);

            if ($params['password'] != $params['password_confirm']) {
                throw new Exception('两次密码不一致');
            }

            $mobile = trim((string)($params['mobile'] ?? ''));
            if ($mobile !== '' && preg_match('/^\+?86(\d{11})$/', $mobile, $matches)) {
                $mobile = $matches[1];
            }
            $params['mobile'] = $mobile;

            $modelUser = new User();
            $isMobile = $modelUser->where(['mobile' => $mobile])->findOrEmpty();
            if (!$isMobile->isEmpty()) {
                throw new Exception('手机已被占用,换一个吧！');
            }

            if (empty($params['nickname'])) {
                $params['nickname'] = '用户' . $userSn;
            }
            if (empty($params['avatar'])) {
                $params['avatar'] = ConfigService::get('default_image', 'user_avatar');
            }

            $user = User::create([
                'sn' => $userSn,
                'avatar' => FileService::setFileUrl($params['avatar']),
                'real_name' => $params['real_name'] ?? '',
                'nickname' => $params['nickname'] ?? '',
                'mobile' => $mobile,
                'account' => $mobile,
                'password' => $password,
                'channel' => UserTerminalEnum::ADMIN,
                'level_id' => -1,
            ]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 批量导入用户（仅手机号+算力）—— 真 · 一条 SQL
     */
    public static function import($file)
    {
        $fileinfo = $file->getRealPath();
        $spreadsheet = IOFactory::load($fileinfo);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // 二维数组
        $salt = Config::get('project.unique_identification');
        $defaultAvatar = ConfigService::get('default_image', 'user_avatar');
        /* 1. 预生成所有数据 */
        $insertData = [];
        $now = time();
        $modelUser = new User();

        foreach ($rows as $k => $row) {
            if ($k == 1)
                continue; // 跳过表头
            if ($row['A'] == '' || $row['B'] == '')
                continue;
            $mobile = trim($row['A']);
            $token = trim($row['B']);

            if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                throw new \Exception("第" . ($k + 1) . "行手机格式错误");
            }
            if ($token < 0) {
                throw new \Exception("第" . ($k + 1) . "行算力不能小于0");
            }
            $isMobile = $modelUser->where(['mobile' => $mobile])->findOrEmpty();
            if (!$isMobile->isEmpty()) {
                throw new Exception("第" . ($k + 1) . "行手机已被占用,换一个吧！");
            }
            $token = round($token, 2);
            $sn = User::createUserSn();          // 还是原规则
            $pwd = create_password($mobile, $salt);

            $insertData[] = [
                'sn' => $sn,
                'account' => $mobile,
                'mobile' => $mobile,
                'password' => $pwd,
                'nickname' => '用户' . $sn,
                'tokens' => $token,
                'avatar' => $defaultAvatar,
                'channel' => UserTerminalEnum::ADMIN,
                'level_id' => -1,
                'create_time' => $now,
                'update_time' => $now,
            ];

        }
        Db::startTrans();          // 事务

        /* 2. 一条 SQL 插完 */
        try {
            (new User)->insertAll($insertData);   // TP 自带批量插入
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }


    }

}
