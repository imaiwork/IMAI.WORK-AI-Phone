<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\team\Team;
use app\common\model\team\TeamInvite;
use app\common\model\team\TeamMember;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\model\Config;
use app\common\logic\AccountLogLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\notice\NoticeEnum;
use app\common\model\user\UserTokensLog;
use app\common\service\sms\SmsDriver;
use think\facade\Db;

/**
 * 团队逻辑
 * Class TeamLogic
 * @package app\api\logic
 */
class TeamLogic extends BaseLogic
{
    const ROLE_NONE = 0;   // 非团队用户/散客归属
    const ROLE_MEMBER = 1; // 成员
    const ROLE_OWNER = 2;  // 团队主(超级管理员)
    const ROLE_ADMIN = 3;  // 管理员(可管理成员,权限低于超管)

    /**
     * @notes 开通团队：当前用户成为团队主
     * @return array|false
     */
    public static function create(int $userId, array $params)
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            self::setError('用户不存在');
            return false;
        }
        // 每人只能创建(拥有)一个企业;加入他人企业不受影响
        $owned = TeamMember::where('user_id', $userId)->where('role', self::ROLE_OWNER)->findOrEmpty();
        if (!$owned->isEmpty()) {
            self::setError('您已创建过企业，每个账号仅可创建一个');
            return false;
        }
        Db::startTrans();
        try {
            $seat = (int)ConfigService::get('team', 'default_seat_limit', 5);
            $team = Team::create([
                'name' => $params['name'],
                'owner_id' => $userId,
                'seat_limit' => $seat > 0 ? $seat : 1,
                'member_count' => 1,
                'status' => 1,
            ]);
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $userId,
                'role' => self::ROLE_OWNER,
                'team_tokens' => 0,
                'expire_time' => 0,
                'delete_time' => 0, // SoftDelete 口径未删除=0，禁止落 NULL
            ]);
            // 创建后自动切换为当前企业;若是 OEM 站点散客,先锁定 origin_team_id,
            // 解散/退团后可恢复到主站「站点用户」,避免被清成 team_id=0 后从列表消失
            $update = [
                'id' => $userId,
                'team_id' => $team->id,
                'team_role' => self::ROLE_OWNER,
                'team_expire_time' => 0,
            ];
            $origin = self::resolveSiteOriginTeamId($user);
            if ($origin > 0 && (int)$user->origin_team_id <= 0) {
                $update['origin_team_id'] = $origin;
            }
            User::update($update);
            Db::commit();
            return ['team_id' => $team->id, 'name' => $team->name, 'seat_limit' => $team->seat_limit];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 当前用户团队信息
     */
    public static function info(int $userId): array
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_id == 0) {
            return ['in_team' => 0, 'team_role' => self::ROLE_NONE];
        }
        // OEM 站点散客(team_role=0):team_id 仅是站点归属,不算"在团队中",
        // 否则右上角会显示 OEM 团队名、而"我的企业"列表又是空的(口径矛盾)
        if (!in_array((int)$user->team_role, [self::ROLE_MEMBER, self::ROLE_OWNER, self::ROLE_ADMIN], true)) {
            // 自愈:历史账号 team_role 未同步但确有有效成员关系时,同步后继续
            $m = TeamMember::where('team_id', (int)$user->team_id)
                ->where('user_id', $userId)
                ->findOrEmpty();
            if ($m->isEmpty() || !in_array((int)$m->role, [self::ROLE_MEMBER, self::ROLE_OWNER, self::ROLE_ADMIN], true)) {
                return ['in_team' => 0, 'team_role' => self::ROLE_NONE];
            }
            User::update(['id' => $userId, 'team_role' => (int)$m->role]);
            $user->team_role = (int)$m->role;
        }
        $team = Team::findOrEmpty($user->team_id);
        // 团队已解散但用户 team_id 未清干净:纠正为个人空间
        if ($team->isEmpty()) {
            User::update([
                'id' => $userId,
                'team_id' => 0,
                'team_role' => self::ROLE_NONE,
                'team_expire_time' => 0,
            ]);
            return ['in_team' => 0, 'team_role' => self::ROLE_NONE];
        }
        $ownerUser = User::findOrEmpty((int)$team->owner_id);
        $seatLimit = (int)$team->seat_limit;
        $memberCount = (int)TeamMember::where('team_id', $team->id)->count();
        // 纠偏历史虚高/偏低的 seat 计数字段(展示与入团校验都依赖实算)
        if ((int)$team->member_count !== $memberCount) {
            Team::where('id', $team->id)->update(['member_count' => $memberCount]);
        }
        $data = [
            'in_team' => 1,
            'team_role' => $user->team_role,
            'team_id' => $team->id,
            'name' => $team->name,
            'status' => $team->status,
            'create_time' => $team->create_time,
            // OEM 状态: 0=免费版 1=已缴费待站长审核 2=已开通
            'oem_status' => (int)($team->oem_status ?? 0),
            'oem_price' => (float)ConfigService::get('team', 'oem_upgrade_price', 5000),
            // 席位/成员统计：全员可见(移动端团队管理页)
            'seat_limit' => $seatLimit,
            'member_count' => $memberCount,
            'seat_left' => max(0, $seatLimit - $memberCount),
            'admin_count' => (int)TeamMember::where('team_id', $team->id)->where('role', self::ROLE_ADMIN)->count(),
            'owner_nickname' => $ownerUser->isEmpty() ? '' : (string)$ownerUser->nickname,
        ];
        // 当前用户个人算力(管理员勿被覆盖成创始人余额)
        $data['tokens'] = (float)$user->tokens;
        // 团队长个人算力:创始人「剩余算力/充值」;管理员仅划拨上限用
        if (in_array((int)$user->team_role, [self::ROLE_OWNER, self::ROLE_ADMIN], true)) {
            $data['owner_tokens'] = $ownerUser->isEmpty() ? 0.0 : (float)$ownerUser->tokens;
            if ((int)$user->team_role === self::ROLE_OWNER) {
                $data['tokens'] = $data['owner_tokens'];
            }
            // 今日企业空间「业务消耗」(仅今日 00:00 起；不含划拨/回收/OEM/制卡等转账类)
            $memberIds = TeamMember::where('team_id', $user->team_id)->column('user_id');
            $attrIds = User::where('team_id', $user->team_id)->where('team_role', 0)->column('id');
            $ids = array_values(array_unique(array_merge($memberIds ?: [], $attrIds ?: [])));
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayCost = 0;
            if ($ids) {
                // 与消耗明细合计完全同口径:业务 DEC - 业务退回 INC(含 status=2 与 9100/915x)
                $excludeTypes = AccountLogEnum::teamTransferTypes();
                $incTypes = AccountLogEnum::teamConsumeIncTypes();
                $todayDec = UserTokensLog::whereIn('user_id', $ids)
                    ->where('team_id', (int)$user->team_id)
                    ->where('action', AccountLogEnum::DEC)
                    ->whereIn('status', [1, 2])
                    ->where('create_time', '>=', $todayStart)
                    ->whereNotIn('change_type', $excludeTypes)
                    ->sum('change_amount');
                $todayInc = UserTokensLog::whereIn('user_id', $ids)
                    ->where('team_id', (int)$user->team_id)
                    ->where('action', AccountLogEnum::INC)
                    ->whereIn('status', [1, 2])
                    ->where('create_time', '>=', $todayStart)
                    ->whereIn('change_type', $incTypes)
                    ->sum('change_amount');
                $todayCost = (float)$todayDec - (float)$todayInc;
            }
            $data['today_cost'] = round(max(0, $todayCost), 2);
        }
        // 管理员联系二维码(OEM站点用户"立即充值"弹窗用)
        $adminQr = (string)ConfigService::get('website', 'admin_qr', '', (int)$user->team_id);
        $data['admin_qr'] = $adminQr !== '' ? \app\common\service\FileService::getFileUrl($adminQr) : '';
        // 我在当前企业的企业算力钱包
        $myMembership = TeamMember::where('team_id', $user->team_id)->where('user_id', $userId)->findOrEmpty();
        $data['team_tokens'] = $myMembership->isEmpty() ? 0 : $myMembership->team_tokens;
        // 成员与管理员都有到期时间(团队主可给管理员设置到期);以成员关系表为准,user 字段仅是快照
        if (in_array((int)$user->team_role, [self::ROLE_MEMBER, self::ROLE_ADMIN], true)) {
            $expire = $myMembership->isEmpty() ? (int)$user->team_expire_time : (int)$myMembership->expire_time;
            $data['team_expire_time'] = $expire;
            $data['team_expire_time_desc'] = $expire > 0 ? date('Y-m-d H:i', $expire) : '永久';
            $data['expired'] = ($expire > 0 && $expire < time()) ? 1 : 0;
        }
        // 授权功能：已启用的功能key列表(团队维度存 la_config team.enabled_features；未配置=全部启用)
        $allFeatures = ['digital_human', 'video_mix', 'gaode_lead', 'ai_phone', 'ai_draw', 'ai_ppt', 'sph_lead', 'ai_agent', 'llm_chat'];
        $enabled = ConfigService::get('team', 'enabled_features', null, (int)$user->team_id);
        if (!is_array($enabled)) {
            $enabled = $allFeatures;
        }
        $data['features'] = array_values(array_intersect($allFeatures, $enabled));
        // 已提交的功能开通申请(key 列表)
        $requests = ConfigService::get('team', 'feature_requests', [], (int)$user->team_id);
        $data['feature_requests'] = is_array($requests) ? array_values(array_column($requests, 'key')) : [];
        return $data;
    }

    /**
     * @notes 团队主申请开通某个授权功能(记录申请,由平台处理)
     * @return bool
     */
    public static function requestFeature(int $ownerId, string $key): bool
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以申请开通');
            return false;
        }
        $allFeatures = ['digital_human', 'video_mix', 'gaode_lead', 'ai_phone', 'ai_draw', 'ai_ppt', 'sph_lead', 'ai_agent', 'llm_chat'];
        if (!in_array($key, $allFeatures)) {
            self::setError('无效的功能标识');
            return false;
        }
        $teamId = (int)$owner->team_id;
        $enabled = ConfigService::get('team', 'enabled_features', null, $teamId);
        if (!is_array($enabled) || in_array($key, $enabled)) {
            self::setError('该功能已开通，无需申请');
            return false;
        }
        $requests = ConfigService::get('team', 'feature_requests', [], $teamId);
        $requests = is_array($requests) ? $requests : [];
        if (in_array($key, array_column($requests, 'key'))) {
            self::setError('已提交过申请，请耐心等待');
            return false;
        }
        $requests[] = ['key' => $key, 'time' => time()];
        ConfigService::set('team', 'feature_requests', $requests, $teamId);
        return true;
    }

    /**
     * @notes 生成邀请码(团队内任意成员均可分享邀请)
     * @return array|false
     */
    public static function invite(int $userId, array $params)
    {
        $user = User::findOrEmpty($userId);
        $teamId = (int)($user->team_id ?? 0);
        if ($user->isEmpty() || $teamId <= 0) {
            self::setError('请先加入团队后再邀请');
            return false;
        }
        // 须为本企业有效成员(创始人/管理员/成员);散客归属不可发邀请
        $inTeam = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        if ($inTeam->isEmpty()) {
            self::setError('只有团队成员可以生成邀请码');
            return false;
        }
        $code = strtoupper(substr(md5($userId . uniqid('', true)), 0, 8));
        $invite = TeamInvite::create([
            'team_id' => $teamId,
            'code' => $code,
            'inviter_id' => $userId,
            'max_uses' => (int)($params['max_uses'] ?? 0),
            'used_count' => 0,
            'expire_time' => (int)($params['expire_time'] ?? 0),
            'status' => 1,
        ]);
        return ['code' => $invite->code, 'id' => $invite->id];
    }

    /**
     * @notes 通过邀请码加入团队(已登录用户)
     * @return bool
     */
    public static function join(int $userId, string $code): bool
    {
        Db::startTrans();
        try {
            self::applyJoin($userId, $code);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 注册时携带邀请码自动入团(失败静默，不阻断注册)
     */
    public static function bindInviteOnRegister(int $userId, string $code): void
    {
        Db::startTrans();
        try {
            self::applyJoin($userId, $code);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
        }
    }

    /**
     * @notes 入团核心：校验邀请码/坐席并绑定，失败抛异常
     */
    private static function applyJoin(int $userId, string $code): void
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            throw new \Exception('用户不存在');
        }
        $invite = TeamInvite::where('code', $code)->where('status', 1)->lock(true)->findOrEmpty();
        if ($invite->isEmpty()) {
            throw new \Exception('邀请码无效');
        }
        // 站点隔离:已开通 OEM 的企业邀请码仅对应 OEM 站可用;
        // 免费团邀请码主站始终可用(勿因团长 origin_team_id 误拦主站加团)
        $requestSiteId = self::currentRequestSiteTeamId();
        if (!self::inviteAllowedOnRequestSite((int)$invite->team_id, $requestSiteId)) {
            throw new \Exception('当前站点不给用');
        }
        // C 硬隔离:OEM 站点原生账号不能加入「其它 OEM 站点企业」
        // (防止在 A 站操作 B 站空间);加入同站用户自建的免费团/非 OEM 团允许
        $originId = (int)$user->origin_team_id;
        if ($originId <= 0) {
            $originId = self::resolveSiteOriginTeamId($user);
        }
        if ($originId > 0 && (int)$invite->team_id !== $originId) {
            $inviteOemStatus = (int)Team::where('id', (int)$invite->team_id)->value('oem_status');
            if ($inviteOemStatus === 2) {
                throw new \Exception('该账号归属其它站点，无法加入此企业');
            }
        }
        if ($invite->expire_time > 0 && $invite->expire_time < time()) {
            throw new \Exception('邀请码已过期');
        }
        if ($invite->max_uses > 0 && $invite->used_count >= $invite->max_uses) {
            throw new \Exception('邀请码已用完');
        }
        $team = Team::where('id', $invite->team_id)->lock(true)->findOrEmpty();
        if ($team->isEmpty() || $team->status != 1) {
            throw new \Exception('团队不存在或已禁用');
        }
        // 席位以有效成员实算为准(team.member_count 历史可能因软删/重复入团虚高)
        $liveCount = (int)TeamMember::where('team_id', $team->id)->count();
        if ($liveCount >= (int)$team->seat_limit) {
            throw new \Exception('团队坐席已满');
        }
        // 含软删:移出后记录仍占 uk_team_user,需恢复而非再 insert
        $exist = TeamMember::withTrashed()
            ->where('team_id', $team->id)
            ->where('user_id', $userId)
            ->findOrEmpty();
        // delete_time=0 在 PHP empty() 为 true,不能当「已删除」;与 withNoTrashed 口径对齐
        $existDeleted = !$exist->isEmpty()
            && $exist->delete_time !== null
            && (int)$exist->delete_time !== 0;
        if (!$exist->isEmpty() && !$existDeleted) {
            throw new \Exception('您已在该企业中');
        }
        if (!$exist->isEmpty() && $existDeleted) {
            $exist->restore();
            $exist->role = self::ROLE_MEMBER;
            $exist->team_tokens = 0;
            $exist->expire_time = 0;
            $exist->save();
        } elseif ($exist->isEmpty()) {
            try {
                TeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $userId,
                    'role' => self::ROLE_MEMBER,
                    'team_tokens' => 0,
                    'expire_time' => 0,
                    'delete_time' => 0, // SoftDelete 口径未删除=0，禁止落 NULL
                ]);
            } catch (\Throwable $e) {
                // uk_team_user 冲突兜底:并发入团/历史软删残留时转恢复,避免直接把 1062 抛给用户
                $dup = TeamMember::withTrashed()
                    ->where('team_id', $team->id)
                    ->where('user_id', $userId)
                    ->findOrEmpty();
                if ($dup->isEmpty()) {
                    throw $e;
                }
                $dup->restore();
                $dup->role = self::ROLE_MEMBER;
                $dup->team_tokens = 0;
                $dup->expire_time = 0;
                $dup->save();
            }
        }
        // 入团前锁定 OEM 站点归属(散客 team_id),避免随后 switchTeam/覆盖 team_id
        // 后从主站「站点用户」列表消失
        $originToLock = self::resolveSiteOriginTeamId($user);
        if ($originToLock > 0 && (int)$user->origin_team_id <= 0) {
            User::where('id', $userId)->update(['origin_team_id' => $originToLock]);
            $user->origin_team_id = $originToLock;
        }
        // 入团后进入该企业空间(写 team_role=成员),否则列表按 currentTeamId 共享不到智能体/知识库。
        // 已在别站散客空间(team_id 为其他企业):只记成员关系,不覆盖归属,用户可自行 switchTeam。
        $currentTeamId = (int)$user->team_id;
        if ($currentTeamId === 0 || $currentTeamId === (int)$team->id) {
            User::update([
                'id' => $userId,
                'team_id' => $team->id,
                'team_role' => self::ROLE_MEMBER,
                'team_expire_time' => 0,
            ]);
        }
        // 挂回退团回收的智能体/知识库(含历史 team_id=0),供团队共享与 IP 人设绑定恢复
        \app\common\service\TeamContextService::restoreUserTeamResources(
            $userId,
            (int)$team->id,
            true
        );
        $team->member_count = (int)TeamMember::where('team_id', $team->id)->count();
        $team->save();
        $invite->used_count += 1;
        $invite->save();
    }

    /**
     * @notes 某条消耗记录的产出结果(聊天记录/图片/数字人视频)
     */
    public static function consumptionOutput(int $operatorId, int $logId): array
    {
        $op = User::findOrEmpty($operatorId);
        if ($op->isEmpty() || !in_array((int)$op->team_role, [self::ROLE_OWNER, self::ROLE_ADMIN])) {
            self::setError('无权限');
            return [];
        }
        $log = \app\common\model\user\UserTokensLog::findOrEmpty($logId);
        if ($log->isEmpty()) {
            self::setError('记录不存在');
            return [];
        }
        // 该条消耗必须发生在当前企业空间内(防跨企业查看成员在别处的产出)
        if ((int)$log->team_id !== (int)$op->team_id) {
            self::setError('无权查看');
            return [];
        }
        // 目标用户须属于当前企业
        $inTeam = TeamMember::where('team_id', $op->team_id)->where('user_id', $log->user_id)->count()
            || User::where('id', $log->user_id)->where('team_id', $op->team_id)->count();
        if (!$inTeam) {
            self::setError('无权查看');
            return [];
        }
        [$bizKey, $bizName, $output] = \app\api\lists\team\TeamConsumptionLists::bizType((int)$log->change_type);
        $taskId = (string)($log->task_id ?: $log->source_sn);
        $uid = (int)$log->user_id;
        $result = ['biz_name' => $bizName, 'output_type' => $output, 'task_id' => $taskId, 'items' => []];
        if ($taskId === '' || $output === 'none') {
            return $result; // 无任务号无法关联产出 / 该业务无可视产出
        }
        $url = fn($u) => $u ? \app\common\service\FileService::getFileUrl((string)$u) : '';
        // 新版 draw 统一链路(生图/生视频/PPT逐页):draw_task(task_no) → draw_asset(task_id)
        $fillDraw = function () use (&$result, $taskId, $url) {
            $id = (int)\think\facade\Db::name('draw_task')->where('task_no', $taskId)->value('id');
            if ($id <= 0) {
                return;
            }
            $assets = \think\facade\Db::name('draw_asset')->where('task_id', $id)
                ->order('sort asc, id asc')->field('asset_type,file_url,source_url')->select()->toArray();
            foreach ($assets as $a) {
                $u = $a['file_url'] ?: $a['source_url'];
                if ($u) {
                    $result['items'][] = ['type' => ($a['asset_type'] === 'video' ? 'video' : 'image'), 'url' => $url($u)];
                }
            }
        };

        try {
            switch ($output) {
                case 'chat':
                    // AI对话 → chat_log;取不到再兜底知识库问答
                    $rows = \think\facade\Db::name('chat_log')->where('task_id', $taskId)->where('user_id', $uid)
                        ->field('message,reply,create_time')->limit(30)->select()->toArray();
                    foreach ($rows as $r) {
                        $result['items'][] = ['type' => 'chat', 'ask' => $r['message'], 'answer' => $r['reply'], 'time' => $r['create_time']];
                    }
                    if (!$result['items']) {
                        try {
                            $rows = \think\facade\Db::name('knowledge_use_scene_record')->where('task_id', $taskId)
                                ->field('prompt,content,create_time')->limit(30)->select()->toArray();
                            foreach ($rows as $r) {
                                $result['items'][] = ['type' => 'chat', 'ask' => $r['prompt'], 'answer' => $r['content'], 'time' => $r['create_time']];
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                    break;

                case 'image':
                    $fillDraw(); // 新版 draw
                    if (!$result['items']) { // 旧版绘画
                        try {
                            $imgs = \think\facade\Db::name('hd_image')->alias('i')
                                ->leftJoin('hd_cue_image c', 'c.id = i.log_id')
                                ->where('i.sub_task_id', $taskId)->whereOr('c.task_id', $taskId)->column('i.image');
                            foreach ($imgs as $u) {
                                if ($u) {
                                    $result['items'][] = ['type' => 'image', 'url' => $url($u)];
                                }
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                    break;

                case 'video':
                    $fillDraw(); // 新版 draw 生视频
                    if (!$result['items']) {
                        // 依次探测各视频产出表(task_id / 主键 id 关联),命中即止
                        $probes = [
                            ['human_video', 'task_id', 'video_id'],
                            ['shanjian_video_task', 'task_id', 'video_result_url'],
                            ['sora_video_task', 'task_id', 'video_result_url'],
                            ['storyboard_video_task', 'task_id', 'video_result_url'],
                            ['video_imitation_task', 'id', 'video_url'],
                        ];
                        foreach ($probes as $p) {
                            if ($result['items']) {
                                break;
                            }
                            try {
                                $urls = \think\facade\Db::name($p[0])->where($p[1], $taskId)->column($p[2]);
                                foreach ($urls as $u) {
                                    if ($u) {
                                        $result['items'][] = ['type' => 'video', 'url' => $url($u)];
                                    }
                                }
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                    break;

                case 'audio':
                    // 音色/语音 → human_voice.voice_urls(可能是 JSON 数组或逗号分隔)
                    $raws = \think\facade\Db::name('human_voice')->where('task_id', $taskId)->column('voice_urls');
                    foreach ($raws as $raw) {
                        if (!$raw) {
                            continue;
                        }
                        $arr = json_decode((string)$raw, true);
                        $listUrls = is_array($arr) ? $arr : preg_split('/[,\s]+/', (string)$raw);
                        foreach ((array)$listUrls as $u) {
                            if ($u) {
                                $result['items'][] = ['type' => 'audio', 'url' => $url($u)];
                            }
                        }
                    }
                    break;

                case 'text':
                    if ($bizKey === 'meeting') {
                        $t = (string)\think\facade\Db::name('audio_info')->where('task_id', $taskId)->value('text');
                        if ($t !== '') {
                            $result['items'][] = ['type' => 'text', 'text' => $t];
                        }
                    } elseif ($bizKey === 'mind') {
                        $r = \think\facade\Db::name('mind_map')->where('task_id', $taskId)->field('ask,reply')->find();
                        if ($r && ($r['reply'] ?? '') !== '') {
                            $result['items'][] = ['type' => 'text', 'title' => $r['ask'] ?? '', 'text' => $r['reply']];
                        }
                    } elseif ($bizKey === 'kb_retrieve') {
                        $c = (string)\think\facade\Db::name('knowledge_use_scene_record')->where('task_id', $taskId)->value('content');
                        if ($c !== '') {
                            $result['items'][] = ['type' => 'text', 'text' => $c];
                        }
                    } elseif ($bizKey === 'map_lead') {
                        $c = (string)\think\facade\Db::name('map_lead_message')->where('id', $taskId)->value('content');
                        if ($c !== '') {
                            $result['items'][] = ['type' => 'text', 'text' => $c];
                        }
                    } elseif ($bizKey === 'imitation_text') {
                        $r = \think\facade\Db::name('video_imitation_task')->where('id', $taskId)->field('rewritten_text,original_text')->find();
                        if ($r) {
                            $t = ($r['rewritten_text'] ?? '') ?: ($r['original_text'] ?? '');
                            if ($t !== '') {
                                $result['items'][] = ['type' => 'text', 'text' => $t];
                            }
                        }
                    }
                    break;
            }
        } catch (\Throwable $e) {
            // 产出表查询失败不阻断,返回空 items
        }
        return $result;
    }

    /**
     * @notes 团队主查看成员列表(仅本团队成员)
     */
    /**
     * @notes 成员下拉选项(全量精简:id/nickname/mobile)
     * 含团队成员 + OEM 站点用户(origin 归属/历史散客),供消耗筛选与卡密转移
     */
    public static function memberOptions(int $userId): array
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || !in_array((int)$user->team_role, [self::ROLE_OWNER, self::ROLE_ADMIN], true)) {
            return [];
        }
        $teamId = (int)$user->team_id;
        $memberUids = TeamMember::where('team_id', $teamId)
            ->orderRaw('FIELD(role,2,3,1), id asc')->column('user_id');
        // 站点用户:与 attributedUsers 同口径,转移卡密需能选到 OEM 站点注册用户
        $siteUids = User::where(function ($q) use ($teamId) {
                $q->where('origin_team_id', $teamId)
                    ->whereOr(function ($w) use ($teamId) {
                        $w->where('origin_team_id', 0)
                            ->where('team_id', $teamId)
                            ->where('team_role', self::ROLE_NONE);
                    });
            })
            ->order('id asc')
            ->column('id');
        $uids = [];
        $seen = [];
        foreach (array_merge($memberUids ?: [], $siteUids ?: []) as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0 || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $uids[] = $uid;
        }
        if (!$uids) {
            return [];
        }
        $userMap = array_column(
            User::whereIn('id', $uids)->field('id,nickname,mobile')->select()->toArray(),
            null, 'id'
        );
        $list = [];
        foreach ($uids as $uid) {
            $u = $userMap[$uid] ?? null;
            if ($u) {
                $list[] = ['id' => $u['id'], 'nickname' => $u['nickname'], 'mobile' => $u['mobile']];
            }
        }
        return $list;
    }

    /**
     * 是否本企业可接收卡密的目标:正式成员,或 OEM 站点归属用户
     */
    public static function isCardTransferTarget(int $teamId, int $targetUserId): bool
    {
        if ($teamId <= 0 || $targetUserId <= 0) {
            return false;
        }
        $member = TeamMember::where('team_id', $teamId)->where('user_id', $targetUserId)->findOrEmpty();
        if (!$member->isEmpty()) {
            return true;
        }
        $siteUser = User::where('id', $targetUserId)
            ->where(function ($q) use ($teamId) {
                $q->where('origin_team_id', $teamId)
                    ->whereOr(function ($w) use ($teamId) {
                        $w->where('origin_team_id', 0)
                            ->where('team_id', $teamId)
                            ->where('team_role', self::ROLE_NONE);
                    });
            })
            ->findOrEmpty();
        return !$siteUser->isEmpty();
    }

    public static function members(int $userId): array
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || !in_array((int)$user->team_role, [self::ROLE_OWNER, self::ROLE_ADMIN])) {
            return [];
        }
        $rows = TeamMember::where('team_id', $user->team_id)
            ->field('user_id,role,team_tokens,expire_time')
            ->orderRaw('FIELD(role,2,3,1), id asc')
            ->select()
            ->toArray();
        $userMap = [];
        $consumedMap = [];
        $lastMap = [];
        $uids = array_column($rows, 'user_id');
        if ($uids) {
            $users = User::whereIn('id', $uids)->field('id,sn,nickname,avatar,mobile,tokens,create_time')->select()->toArray();
            $userMap = array_column($users, null, 'id');
            // 批量取累计净消耗(划拨-回收) + 最近使用时间,均限定本企业空间(team_id),避免 N+1
            $consumedMap = AccountLogLogic::getTeamConsumedMap($uids, (int)$user->team_id);
            $lastMap = \app\common\model\user\UserTokensLog::whereIn('user_id', $uids)
                ->where('team_id', (int)$user->team_id)
                ->group('user_id')->column('max(create_time) as t', 'user_id');
        }
        $now = time();
        $list = [];
        foreach ($rows as $r) {
            $u = $userMap[$r['user_id']] ?? null;
            if (!$u) {
                continue;
            }
            $consumed = $consumedMap[$r['user_id']] ?? 0;
            $lastLog = $lastMap[$r['user_id']] ?? 0;
            $role = (int)$r['role'];
            // 创始人消费扣个人算力 → 剩余展示个人 tokens;成员/管理员展示企业钱包
            $tokens = $role === self::ROLE_OWNER ? (float)($u['tokens'] ?? 0) : (float)$r['team_tokens'];
            $list[] = [
                'id' => $u['id'],
                'sn' => $u['sn'],
                'nickname' => $u['nickname'],
                'avatar' => $u['avatar'],
                'mobile' => $u['mobile'],
                'tokens' => $tokens,
                'team_role' => $role,
                'role_desc' => [1 => '成员', 2 => '超级管理员', 3 => '管理员'][$role] ?? '成员',
                // 秒级时间戳(设置到期用) + 格式化文案(列表展示用)
                'team_expire_time' => (int)$r['expire_time'],
                'create_time' => $u['create_time'],
                'expired' => ($r['expire_time'] > 0 && (int)$r['expire_time'] < $now) ? 1 : 0,
                'team_expire_time_desc' => $r['expire_time'] > 0 ? date('Y-m-d H:i:s', (int)$r['expire_time']) : '永久',
                'total_consumed' => (float)$consumed,
                'last_used_time' => $lastLog ? (int)$lastLog : 0,
                'last_used_time_desc' => $lastLog ? date('Y-m-d H:i:s', (int)$lastLog) : '-',
            ];
        }
        return $list;
    }

    /**
     * @notes 成员主动退团(role=1 释放坐席；role=0 归属散客解除归属；团队主不可退)
     * @return bool
     */
    public static function leave(int $userId): bool
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_id == 0) {
            self::setError('您不在任何团队中');
            return false;
        }
        if ($user->team_role == self::ROLE_OWNER) {
            self::setError('团队主不能退出自己的团队');
            return false;
        }
        Db::startTrans();
        try {
            self::detachFromTeam($user, (int)$user->team_id);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 团队主移除成员/解除散客归属(不能移除自己)
     * @return bool
     */
    public static function removeMember(int $operatorId, int $userId): bool
    {
        $op = User::findOrEmpty($operatorId);
        $teamId = (int)$op->team_id;
        // 管理员及以上(超管/管理员)可移除
        $opRole = self::teamRoleOf($operatorId, $teamId);
        if ($op->isEmpty() || !in_array($opRole, [self::ROLE_OWNER, self::ROLE_ADMIN])) {
            self::setError('无权操作');
            return false;
        }
        $target = User::findOrEmpty($userId);
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        // 与 attributedUsers / 品牌管理「站点用户」同口径:含 origin 锁定(可已切个人空间)
        $isAttributed = self::isSiteAttributedUser($target, $teamId);
        if ($target->isEmpty() || ($membership->isEmpty() && !$isAttributed)) {
            self::setError('该用户不属于你的团队');
            return false;
        }
        if ($userId == $operatorId) {
            self::setError('不能移除自己');
            return false;
        }
        // 管理员只能移除普通成员/散客,不能移除其他管理员或超管
        $targetRole = $membership->isEmpty() ? self::ROLE_NONE : (int)$membership->role;
        if ($targetRole === self::ROLE_OWNER || ($targetRole === self::ROLE_ADMIN && $opRole !== self::ROLE_OWNER)) {
            self::setError('无权移除该成员');
            return false;
        }
        Db::startTrans();
        try {
            self::detachFromTeam($target, $teamId);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取用户在某团队的角色(读成员关系表)
     */
    public static function teamRoleOf(int $userId, int $teamId): int
    {
        $r = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->value('role');
        return $r === null ? self::ROLE_NONE : (int)$r;
    }

    /**
     * @notes 超管修改成员角色(成员 <-> 管理员;不能改超管自己)
     * @return bool
     */
    public static function setMemberRole(int $ownerId, int $userId, int $role): bool
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有超级管理员可以修改角色');
            return false;
        }
        if (!in_array($role, [self::ROLE_MEMBER, self::ROLE_ADMIN])) {
            self::setError('无效的角色');
            return false;
        }
        $teamId = (int)$owner->team_id;
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        if ($membership->isEmpty() || (int)$membership->role === self::ROLE_OWNER) {
            self::setError('该成员不可修改角色');
            return false;
        }
        $membership->role = $role;
        $membership->save();
        // 目标当前正选中此企业 → 同步 la_user.team_role
        $target = User::findOrEmpty($userId);
        if (!$target->isEmpty() && (int)$target->team_id === $teamId) {
            User::update(['id' => $userId, 'team_role' => $role]);
        }
        return true;
    }

    /**
     * @notes 管理员及以上修改成员的企业算力(设为目标值,差额与超管个人算力结算)
     * @return bool
     */
    public static function setMemberTokens(int $operatorId, int $userId, float $tokens): bool
    {
        if ($tokens < 0) {
            self::setError('算力不能为负');
            return false;
        }
        $op = User::findOrEmpty($operatorId);
        $teamId = (int)$op->team_id;
        $opRole = self::teamRoleOf($operatorId, $teamId);
        if ($op->isEmpty() || !in_array($opRole, [self::ROLE_OWNER, self::ROLE_ADMIN])) {
            self::setError('无权操作');
            return false;
        }
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        if ($membership->isEmpty() || (int)$membership->role === self::ROLE_OWNER) {
            self::setError('该成员不可修改算力');
            return false;
        }
        // 管理员不能修改其他管理员的算力(仅超管可以)
        if ((int)$membership->role === self::ROLE_ADMIN && $opRole !== self::ROLE_OWNER) {
            self::setError('无权修改该成员算力');
            return false;
        }
        // 结算对象:超管(团队主)个人算力
        $team = Team::findOrEmpty($teamId);
        $owner = $team->isEmpty() ? null : User::findOrEmpty((int)$team->owner_id);
        if (!$owner || $owner->isEmpty()) {
            self::setError('团队主不存在');
            return false;
        }
        Db::startTrans();
        try {
            // 行锁重取,防并发下余额校验失真/钱包双花
            $owner = User::where('id', $owner->id)->lock(true)->findOrEmpty();
            $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->lock(true)->findOrEmpty();
            if ($membership->isEmpty()) {
                throw new \Exception('该成员不存在');
            }
            $delta = bcsub((string)$tokens, (string)$membership->team_tokens, 2); // >0 需从超管扣, <0 退给超管
            $memberLabel = self::memberDisplayName($userId);
            // 扣/退仍走创始人钱包;流水 extra 记真实操作人,消耗明细按操作人展示(区分创始人/管理员)
            // target_user_id=划拨/回收的目标成员,算力流转按成员筛选靠它命中(2026-08-05)
            $opExtra = [
                'operator_id' => $operatorId,
                'operator_role' => $opRole,
                'target_user_id' => $userId,
            ];
            $opRoleLabel = $opRole === self::ROLE_OWNER ? '创始人' : '管理员';
            $opLabel = self::memberDisplayName($operatorId, $op);
            if (bccomp($delta, '0', 2) > 0) {
                if (bccomp((string)$owner->tokens, $delta, 2) < 0) {
                    throw new \Exception('超管算力不足,无法增加');
                }
                $owner->tokens = bcsub((string)$owner->tokens, $delta, 2);
                $owner->save();
                AccountLogLogic::add(
                    $owner->id,
                    AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
                    AccountLogEnum::DEC,
                    (float)$delta,
                    1,
                    '',
                    $opRoleLabel . '「' . $opLabel . '」划拨企业算力给成员「' . $memberLabel . '」: +' . $delta,
                    $opExtra,
                    $teamId
                );
            } elseif (bccomp($delta, '0', 2) < 0) {
                $back = bcsub('0', $delta, 2);
                $owner->tokens = bcadd((string)$owner->tokens, $back, 2);
                $owner->save();
                AccountLogLogic::add(
                    $owner->id,
                    AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
                    AccountLogEnum::INC,
                    (float)$back,
                    1,
                    '',
                    $opRoleLabel . '「' . $opLabel . '」回收成员「' . $memberLabel . '」企业算力退回: +' . $back,
                    $opExtra,
                    $teamId
                );
            }
            $membership->team_tokens = $tokens;
            $membership->save();
            // 成员侧入账流水(12015):算力流转按成员筛选可直接命中;须在 membership 保存后写,
            // AccountLogLogic::add 会按该企业钱包最新余额记 left_tokens
            if (bccomp($delta, '0', 2) > 0) {
                AccountLogLogic::add(
                    $userId,
                    AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE,
                    AccountLogEnum::INC,
                    (float)$delta,
                    1,
                    '',
                    $opRoleLabel . '「' . $opLabel . '」划拨企业算力给「' . $memberLabel . '」: +' . $delta,
                    $opExtra,
                    $teamId
                );
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
     * @notes 把用户从指定团队摘除：有成员关系则删除关系并释放坐席(企业算力随之作废)；
     *        散客(仅归属无关系)则解除归属。若摘除的是其当前选中企业，一律切回个人空间。
     *        个人算力不受影响。须在事务内调用
     */
    private static function detachFromTeam(User $user, int $teamId): void
    {
        // 行锁,防与计费扣钱包并发(调用方均在事务内)
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $user->id)->lock(true)->findOrEmpty();
        $wasMember = !$membership->isEmpty();
        if ($wasMember) {
            // 该成员在本企业剩余的企业算力,退回给团队主(超管)个人算力
            $refund = (float)$membership->team_tokens;
            if ($refund > 0) {
                $team0 = Team::findOrEmpty($teamId);
                $ownerUser = $team0->isEmpty() ? null : User::where('id', (int)$team0->owner_id)->lock(true)->findOrEmpty();
                if ($ownerUser && !$ownerUser->isEmpty()) {
                    $ownerUser->tokens = bcadd((string)$ownerUser->tokens, (string)$refund, 2);
                    $ownerUser->save();
                    // 与「修改算力回收」同类型;强制写入业务所属 team_id,避免创始人在个人空间时流水 team_id=0
                    AccountLogLogic::add(
                        $ownerUser->id,
                        AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
                        AccountLogEnum::INC,
                        $refund,
                        1,
                        '',
                        '成员移出退回企业算力: ' . self::memberDisplayName((int)$user->id, $user),
                        ['target_user_id' => (int)$user->id],
                        $teamId
                    );
                }
            }
            // 名下未使用完的团队卡密(算力卡/会员兑换码)收回给团队主,避免留在已移除成员手里
            $ownerId = (int)Team::where('id', $teamId)->value('owner_id');
            if ($ownerId > 0 && $ownerId !== (int)$user->id) {
                \app\common\model\cardcode\CardCode::where('team_id', $teamId)
                    ->where('user_id', (int)$user->id)
                    ->whereIn('type', [
                        \app\common\enum\CardCodeEnum::TYPE_DISTRIBUTION_TOKENS,
                        \app\common\enum\CardCodeEnum::TYPE_MEMBER,
                    ])
                    ->whereRaw('used_num < card_num')
                    ->update(['user_id' => $ownerId, 'update_time' => time()]);
            }
            // 软删:再次加入走 withTrashed + restore,避免 uk_team_user 冲突,并能识别重入团挂回资源
            $membership->delete();
            $team = Team::where('id', $teamId)->lock(true)->findOrEmpty();
            if (!$team->isEmpty()) {
                // 以有效成员实算回写,避免反复进出后 member_count 与列表不一致
                $team->member_count = (int)TeamMember::where('team_id', $teamId)->count();
                $team->save();
            }
        }
        // 该用户在本企业创建的智能体/知识库标记回收(team_id=-企业),个人空间仍可见,团队不再共享
        \app\common\service\TeamContextService::reclaimUserTeamResources((int)$user->id, $teamId);
        // 当前选中企业被摘除:优先恢复 OEM 站点归属,否则回个人空间
        if ((int)$user->team_id === $teamId) {
            $restore = self::pickRestorableOriginTeamId((int)$user->origin_team_id, $teamId);
            User::update([
                'id' => $user->id,
                'team_id' => $restore,
                'team_role' => self::ROLE_NONE,
                'team_expire_time' => 0,
            ]);
        }
        // 解除对本企业的 OEM 站点归属锁;否则用户仍出现在「站点用户」却无法再移除
        if ((int)$user->origin_team_id === $teamId) {
            User::update([
                'id' => (int)$user->id,
                'origin_team_id' => 0,
            ]);
        }
    }

    /**
     * @notes 是否为本企业 OEM 站点归属用户(与 attributedUsers 列表口径一致)
     */
    private static function isSiteAttributedUser(User $user, int $teamId): bool
    {
        if ($user->isEmpty() || $teamId <= 0) {
            return false;
        }
        if ((int)$user->origin_team_id === $teamId) {
            return true;
        }
        return (int)$user->origin_team_id === 0
            && (int)$user->team_id === $teamId
            && (int)$user->team_role === self::ROLE_NONE;
    }

    /**
     * 解析用户应锁定的 OEM 站点归属团队:
     * 已有 origin_team_id 优先;否则若当前是散客(role=0)且挂着 team_id,视为站点归属。
     */
    private static function resolveSiteOriginTeamId(User $user): int
    {
        $origin = (int)($user->origin_team_id ?? 0);
        if ($origin > 0) {
            return $origin;
        }
        if ((int)$user->team_role === self::ROLE_NONE && (int)$user->team_id > 0) {
            return (int)$user->team_id;
        }
        return 0;
    }

    /**
     * 离开/解散某企业后,若 origin 指向其它仍存在的 OEM 团队,则恢复为该站点散客归属。
     * @param int $originTeamId 用户 origin_team_id
     * @param int $leavingTeamId 正在离开/解散的团队
     */
    private static function pickRestorableOriginTeamId(int $originTeamId, int $leavingTeamId): int
    {
        if ($originTeamId <= 0 || $originTeamId === $leavingTeamId) {
            return 0;
        }
        $team = Team::where('id', $originTeamId)->findOrEmpty();
        return $team->isEmpty() ? 0 : $originTeamId;
    }

    /**
     * @notes 升级企业OEM：短信验证→校验算力→扣除预缴费→进入待站长审核状态
     * @return array|false
     */
    public static function upgradeOem(int $ownerId, string $mobile = '', string $code = '')
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以升级OEM');
            return false;
        }
        $mobile = trim($mobile);
        $code = trim($code);
        if ($mobile === '') {
            self::setError('请输入手机号');
            return false;
        }
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            self::setError('手机号格式不正确');
            return false;
        }
        if ($code === '') {
            self::setError('请输入验证码');
            return false;
        }
        $ownerMobile = trim((string)$owner->mobile);
        if ($ownerMobile !== '' && $mobile !== $ownerMobile) {
            self::setError('请使用账号绑定的手机号获取验证码');
            return false;
        }
        $smsDriver = new SmsDriver();
        if (!$smsDriver->verify($mobile, $code, NoticeEnum::LOGIN_CAPTCHA)) {
            self::setError($smsDriver->getError() ?: '验证码错误');
            return false;
        }
        $team = Team::findOrEmpty($owner->team_id);
        if ($team->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        if ((int)$team->oem_status === 1) {
            self::setError('已提交申请，等待站长审核');
            return false;
        }
        if ((int)$team->oem_status === 2) {
            self::setError('已是企业OEM，无需重复升级');
            return false;
        }
        // 名额预检:与旧版OEM共用站长全局授权名额,用尽则不允许申请(避免付了预缴又被拒)
        $authNum = self::oemAuthNum();
        if ($authNum > 0 && self::oemUsedQuota() >= $authNum) {
            self::setError('平台OEM授权名额已用尽，暂无法开通，请联系站长');
            return false;
        }
        $price = (float)ConfigService::get('team', 'oem_upgrade_price', 5000);
        if (bccomp((string)$owner->tokens, (string)$price, 2) < 0) {
            self::setError('算力余额不足，请先充值');
            return false;
        }
        Db::startTrans();
        try {
            $owner->tokens = bcsub((string)$owner->tokens, (string)$price, 2);
            $owner->save();
            AccountLogLogic::add(
                $owner->id,
                AccountLogEnum::TOKENS_DEC_OEM_UPGRADE,
                AccountLogEnum::DEC,
                $price,
                1,
                '',
                '升级企业OEM预缴算力'
            );
            Team::where('id', (int)$team->id)->update([
                'oem_status' => 1,
                'oem_apply_time' => time(),
                'oem_pay_tokens' => $price,
            ]);
            Db::commit();
            return ['oem_status' => 1, 'paid' => $price];
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes OEM 站点归属用户列表(区别于成员)
     * 口径:origin_team_id=本企业(含已自建团/切到个人空间的站点用户)
     *      + 历史散客(origin=0 且 team_id=本企业且 role=0)
     */
    public static function attributedUsers(int $ownerId): array
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            return [];
        }
        $teamId = (int)$owner->team_id;
        $list = User::where(function ($q) use ($teamId) {
                $q->where('origin_team_id', $teamId)
                    ->whereOr(function ($w) use ($teamId) {
                        $w->where('origin_team_id', 0)
                            ->where('team_id', $teamId)
                            ->where('team_role', self::ROLE_NONE);
                    });
            })
            ->field('id,sn,nickname,avatar,mobile,tokens,channel,create_time,team_id,team_role,origin_team_id')
            ->order('id desc')
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $item['channel_desc'] = \app\common\enum\user\UserTerminalEnum::getTermInalDesc($item['channel']);
        }
        return $list;
    }

    /**
     * @notes OEM 站长调整站点用户算力(设为目标值,差额与站长个人算力结算)
     * @return bool
     */
    public static function setSiteUserTokens(int $ownerId, int $userId, float $tokens): bool
    {
        if ($tokens < 0) {
            self::setError('算力不能为负');
            return false;
        }
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有企业主可以调整');
            return false;
        }
        // 目标须为本站点归属用户(origin 锁定,或历史散客 team_id+role=0)
        $teamId = (int)$owner->team_id;
        $target = User::where('id', $userId)
            ->where(function ($q) use ($teamId) {
                $q->where('origin_team_id', $teamId)
                    ->whereOr(function ($w) use ($teamId) {
                        $w->where('origin_team_id', 0)
                            ->where('team_id', $teamId)
                            ->where('team_role', self::ROLE_NONE);
                    });
            })
            ->findOrEmpty();
        if ($target->isEmpty()) {
            self::setError('该用户不属于你的站点');
            return false;
        }
        Db::startTrans();
        try {
            // 行锁重取双方,防并发下余额校验失真
            $owner = User::where('id', $owner->id)->lock(true)->findOrEmpty();
            $target = User::where('id', $target->id)->lock(true)->findOrEmpty();
            $delta = bcsub((string)$tokens, (string)$target->tokens, 2); // >0 从站长扣, <0 退站长
            if (bccomp($delta, '0', 2) > 0) {
                if (bccomp((string)$owner->tokens, $delta, 2) < 0) {
                    throw new \Exception('你的算力不足');
                }
                $owner->tokens = bcsub((string)$owner->tokens, $delta, 2);
                $owner->save();
                AccountLogLogic::add($owner->id, AccountLogEnum::TOKENS_DEC_DISTRIBUTION_TRANSFER, AccountLogEnum::DEC, (float)$delta, 1, '', '调增站点用户算力: ' . $target->sn);
                $target->tokens = bcadd((string)$target->tokens, $delta, 2);
                $target->save();
                AccountLogLogic::add($target->id, AccountLogEnum::TOKENS_INC_DISTRIBUTION_TRANSFER, AccountLogEnum::INC, (float)$delta, 1, '', '企业发放算力');
            } elseif (bccomp($delta, '0', 2) < 0) {
                $back = bcsub('0', $delta, 2);
                $target->tokens = bcsub((string)$target->tokens, $back, 2);
                $target->save();
                AccountLogLogic::add($target->id, AccountLogEnum::TOKENS_DEC_DISTRIBUTION_TRANSFER, AccountLogEnum::DEC, (float)$back, 1, '', '企业调减算力');
                $owner->tokens = bcadd((string)$owner->tokens, $back, 2);
                $owner->save();
                AccountLogLogic::add($owner->id, AccountLogEnum::TOKENS_INC_DISTRIBUTION_TRANSFER, AccountLogEnum::INC, (float)$back, 1, '', '站点用户算力调减退回');
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
     * @notes 校验团队已开通OEM(品牌/小程序/卡密等能力需开通后使用)
     */
    public static function assertOemActive(int $teamId): void
    {
        $status = (int)Team::where('id', $teamId)->value('oem_status');
        if ($status !== 2) {
            throw new \Exception($status === 1 ? '企业OEM申请审核中，暂不可用' : '请先升级企业OEM后使用该功能');
        }
    }

    /**
     * @notes 团队主修改团队名称
     * @return bool
     */
    public static function setName(int $ownerId, string $name): bool
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以修改名称');
            return false;
        }
        $name = trim($name);
        if ($name === '') {
            self::setError('请输入名称');
            return false;
        }
        Team::where('id', (int)$owner->team_id)->update(['name' => $name]);
        return true;
    }

    /**
     * @notes 团队主解散团队：全员(含散客)解除归属并清到期，删除团队/邀请码/团队配置。
     *        用户已到手的算力保留；团队卡密与流水作为历史记录保留(team_id 仍指向已删团队)
     * @return bool
     */
    public static function disband(int $ownerId): bool
    {
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以解散团队');
            return false;
        }
        return self::disbandByTeam((int)$owner->team_id);
    }

    /**
     * @notes 按团队ID解散(站长后台强制删除复用)：退回成员企业钱包+未使用卡密算力+审核中预缴给团队主，
     *        清成员/资源/配置/域名，软删团队本体。团队主从 team.owner_id 解析。
     */
    public static function disbandByTeam(int $teamId): bool
    {
        if ($teamId <= 0) {
            self::setError('团队不存在');
            return false;
        }
        $teamChk = Team::findOrEmpty($teamId);
        if ($teamChk->isEmpty()) {
            self::setError('团队不存在');
            return false;
        }
        $owner = User::findOrEmpty((int)$teamChk->owner_id);
        Db::startTrans();
        try {
            // 1) 成员未消耗的企业算力汇总退回团队主(与踢人/退团的退回规则一致)
            $walletTotal = (string)TeamMember::where('team_id', $teamId)->sum('team_tokens');
            if (bccomp($walletTotal, '0', 2) > 0 && !$owner->isEmpty()) {
                $owner->tokens = bcadd((string)$owner->tokens, $walletTotal, 2);
                $owner->save();
                AccountLogLogic::add(
                    $owner->id,
                    AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
                    AccountLogEnum::INC,
                    (float)$walletTotal,
                    1,
                    '',
                    '解散企业退回未使用的企业算力',
                    [],
                    $teamId
                );
            }
            // 2) OEM 申请审核中:预缴算力退回团队主(避免解散后无处退)
            $teamRow = Team::findOrEmpty($teamId);
            if (!$teamRow->isEmpty() && (int)$teamRow->oem_status === 1 && (float)$teamRow->oem_pay_tokens > 0 && !$owner->isEmpty()) {
                $paid = (float)$teamRow->oem_pay_tokens;
                $owner->tokens = bcadd((string)$owner->tokens, (string)$paid, 2);
                $owner->save();
                AccountLogLogic::add(
                    $owner->id,
                    AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND,
                    AccountLogEnum::INC,
                    $paid,
                    1,
                    '',
                    '解散企业退回OEM预缴算力'
                );
                Team::where('id', $teamId)->update(['oem_status' => 0, 'oem_pay_tokens' => 0]);
            }
            // 2.5) 未使用企业卡密算力退回团队主(含站长/成员/站点用户名下未兑卡)
            if (!$owner->isEmpty()) {
                \app\api\logic\team\TeamCardLogic::refundUnusedOnDisband($teamId, $owner);
            }
            // 3) 成员关系硬删除(软删仍占 uk_team_user,影响再建团/再加入)
            Db::name('team_member')->where('team_id', $teamId)->delete();
            // 当前选中此企业的用户:优先恢复到 OEM 站点归属(origin_team_id),
            // 否则回个人空间。避免 OEM 成员自建团再解散后从主站「站点用户」消失
            $affected = User::where('team_id', $teamId)->field('id,origin_team_id')->select();
            foreach ($affected as $u) {
                $restore = self::pickRestorableOriginTeamId((int)$u->origin_team_id, $teamId);
                User::where('id', (int)$u->id)->update([
                    'team_id' => $restore,
                    'team_role' => self::ROLE_NONE,
                    'team_expire_time' => 0,
                ]);
            }
            // 解除 C 硬隔离锁:该站点原生账号的 origin 指向已删团队,不清会被永久锁在门外(登录恒被拒)
            User::where('origin_team_id', $teamId)->update(['origin_team_id' => 0]);
            // 邀请码作废(软删)
            Db::name('team_invite')->where('team_id', $teamId)
                ->whereNull('delete_time')->update(['delete_time' => time()]);
            // 企业空间业务资源直接删除(软删),避免解散后 team_id 指向已删团队成孤儿
            foreach (['kb_robot', 'knowledge', 'kb_know',] as $tbl) {
                Db::name($tbl)->where('team_id', $teamId)
                    ->whereNull('delete_time')->update(['delete_time' => time()]);
            }
            // 关站落痕:软删前保留域名、记下小程序 appid + 域名标记,供 resolveTenant 识别「站点已关闭」
            // (config 会被清掉;软删行 domain 也可能被后续接管清掉,故额外写 oem_site 落痕)
            $mnpAppId = (string)Config::where([
                'type' => 'mnp_setting',
                'name' => 'app_id',
                'team_id' => $teamId,
            ])->value('value');
            $closedDomain = self::normalizeDomain((string)($teamChk->domain ?? ''));
            Team::where('id', $teamId)->update([
                'oem_status' => 0,
                'status' => 0,
                // domain 故意保留在软删行,用于按 Host 识别关站
            ]);
            // 团队品牌/小程序等隔离配置清除
            Db::name('config')->where('team_id', $teamId)->delete();
            // 团队本体软删(域名仍留在行上)
            $team = Team::findOrEmpty($teamId);
            if (!$team->isEmpty()) {
                $team->delete();
            }
            if ($mnpAppId !== '') {
                ConfigService::set('oem_site', 'closed_appid:' . $mnpAppId, (string)$teamId, 0);
            }
            if ($closedDomain !== '') {
                self::markClosedDomain($closedDomain, $teamId);
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
     * @notes 团队主设置成员到期时间(0=永久)。到期后该成员失去团队权益
     * @return bool
     */
    public static function setMemberExpire(int $operatorId, int $userId, int $expireTime): bool
    {
        $op = User::findOrEmpty($operatorId);
        $teamId = (int)$op->team_id;
        $opRole = self::teamRoleOf($operatorId, $teamId);
        if ($op->isEmpty() || !in_array($opRole, [self::ROLE_OWNER, self::ROLE_ADMIN], true)) {
            self::setError('无权设置到期时间');
            return false;
        }
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        if ($membership->isEmpty()) {
            self::setError('该用户不属于你的团队');
            return false;
        }
        if ((int)$membership->role === self::ROLE_OWNER) {
            self::setError('不能给团队主设置到期时间');
            return false;
        }
        // 管理员只能设置普通成员到期
        if ($opRole === self::ROLE_ADMIN && (int)$membership->role !== self::ROLE_MEMBER) {
            self::setError('无权设置该成员到期时间');
            return false;
        }
        $expire = $expireTime > 0 ? $expireTime : 0;
        $membership->expire_time = $expire;
        $membership->save();
        // 目标当前选中的正是该企业 → 同步镜像(计费拦截读 la_user)
        $target = User::findOrEmpty($userId);
        if (!$target->isEmpty() && (int)$target->team_id === $teamId) {
            User::update(['id' => $userId, 'team_expire_time' => $expire]);
        }
        return true;
    }

    /**
     * @notes 成员是否已过期(团队权益失效)
     */
    public static function isMemberExpired(User $user): bool
    {
        // 与 TeamMemberService 同口径:优先读成员关系表 expire_time
        return \app\common\service\TeamMemberService::isExpiredUser($user);
    }

    /**
     * @notes 团队自有小程序：建议的下一个版本号 + 是否已上传代码包
     */
    public static function mnpVersion(int $userId): array
    {
        $user = User::findOrEmpty($userId);
        $tid = (int)$user->team_id;
        $hasCode = self::teamHasMnpCode($tid) ? 1 : 0;
        $last = (string)ConfigService::get('mnp_setting', 'app_version', '2.0.0', $tid);
        $parts = explode('.', $last);
        if (count($parts) !== 3) {
            return ['version' => '2.0.0', 'has_mnp_code' => $hasCode];
        }
        [$major, $minor, $patch] = array_map('intval', $parts);
        $patch++;
        if ($patch >= 10) {
            $patch = 0;
            $minor++;
        }
        if ($minor >= 10) {
            $minor = 0;
            $major++;
        }
        return ['version' => "$major.$minor.$patch", 'has_mnp_code' => $hasCode];
    }

    /**
     * @notes miniprogram-ci 根目录(绝对路径,避免 cwd 差异导致写不到文件)
     */
    private static function mnpCiDir(): string
    {
        return rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'miniprogram-ci' . DIRECTORY_SEPARATOR;
    }

    /**
     * @notes 查找已安装的 miniprogram-ci 包目录(本目录 / 项目根 node_modules)
     */
    private static function mnpCiPackageDir(): string
    {
        $root = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $candidates = [
            self::mnpCiDir() . 'node_modules' . DIRECTORY_SEPARATOR . 'miniprogram-ci',
            $root . 'node_modules' . DIRECTORY_SEPARATOR . 'miniprogram-ci',
            $root . 'extend' . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'miniprogram-ci',
        ];
        foreach ($candidates as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . 'package.json')) {
                return $dir;
            }
        }
        return '';
    }

    /**
     * @notes 规范化微信小程序上传私钥 PEM
     * 常见损坏：前端/库把真实换行存成字面量 \n，OpenSSL 会报 DECODER unsupported
     */
    private static function normalizeMnpPrivateKey(string $raw): string
    {
        $key = trim($raw);
        // 去掉 BOM
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?? $key;
        // 字面量 \r\n / \n → 真实换行
        if (str_contains($key, '\\n')) {
            $key = str_replace(["\\r\\n", "\\n"], ["\n", "\n"], $key);
        }
        $key = str_replace(["\r\n", "\r"], "\n", $key);
        $key = trim($key) . "\n";
        if (!preg_match('/-----BEGIN [A-Z0-9 ]+PRIVATE KEY-----/', $key)
            || !preg_match('/-----END [A-Z0-9 ]+PRIVATE KEY-----/', $key)
        ) {
            throw new \Exception('私钥格式不正确，请粘贴微信公众平台下载的完整私钥文件内容（含 BEGIN/END 行）');
        }
        return $key;
    }

    /**
     * @notes 写入团队私钥文件(规范化后同时落到 teams/{tid} 与 ci 根目录)
     */
    private static function writeMnpPrivateKeyFile(int $tid, string $appid, string $rawKey): string
    {
        $key = self::normalizeMnpPrivateKey($rawKey);
        self::ensureMnpTeamWorkspace($tid, $appid);
        $teamKey = self::mnpTeamPrivateKeyPath($tid, $appid);
        $rootKey = self::mnpCiDir() . 'private.' . $appid . '.key';
        if (file_put_contents($teamKey, $key) === false) {
            throw new \Exception('私钥文件写入失败：' . $teamKey);
        }
        @file_put_contents($rootKey, $key);
        return $teamKey;
    }

    /**
     * @notes OEM 团队小程序工作区: extend/miniprogram-ci/teams/{tid}/
     * 与主站 mp-weixin / private.*.key 隔离
     */
    private static function mnpTeamWorkDir(int $tid): string
    {
        return self::mnpCiDir() . 'teams/' . $tid . '/';
    }

    /**
     * @notes 团队小程序代码目录
     */
    private static function mnpTeamProjectDir(int $tid): string
    {
        return self::mnpTeamWorkDir($tid) . 'mp-weixin';
    }

    /**
     * @notes 团队小程序上传私钥路径
     */
    private static function mnpTeamPrivateKeyPath(int $tid, string $appid): string
    {
        return self::mnpTeamWorkDir($tid) . 'private.' . $appid . '.key';
    }

    /**
     * @notes 团队是否已上传可提交的小程序代码包(以关键配置文件为准)
     */
    private static function teamHasMnpCode(int $tid): bool
    {
        if ($tid <= 0) {
            return false;
        }
        // 兼容旧目录 mp-weixin.{tid}
        self::ensureMnpTeamWorkspace($tid);
        $projectDir = self::mnpTeamProjectDir($tid);
        return is_file($projectDir . DIRECTORY_SEPARATOR . 'app.json')
            || is_file($projectDir . DIRECTORY_SEPARATOR . 'project.config.json');
    }

    /**
     * @notes 幂等建目录:并发下 is_dir+mkdir 会触发 mkdir(): File exists(被转成异常弹到前端)
     */
    private static function ensureDir(string $dir, int $mode = 0775): void
    {
        if ($dir === '' || is_dir($dir)) {
            return;
        }
        if (!@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new \RuntimeException('无法创建目录：' . $dir);
        }
    }

    /**
     * @notes 确保团队工作区存在；并把旧版 mp-weixin.{tid} / 根目录私钥迁入新路径
     */
    private static function ensureMnpTeamWorkspace(int $tid, string $appid = ''): void
    {
        $workDir = self::mnpTeamWorkDir($tid);
        $projectDir = self::mnpTeamProjectDir($tid);
        self::ensureDir($workDir, 0775);
        self::ensureDir($workDir . '.upload', 0775);

        // 旧代码目录 mp-weixin.{tid} → teams/{tid}/mp-weixin
        $legacyProject = self::mnpCiDir() . 'mp-weixin.' . $tid;
        if (!is_dir($projectDir) && is_dir($legacyProject)) {
            @rename($legacyProject, $projectDir);
        }

        // 旧私钥(ci 根目录) → teams/{tid}/
        if ($appid !== '') {
            $newKey = self::mnpTeamPrivateKeyPath($tid, $appid);
            $legacyKey = self::mnpCiDir() . 'private.' . $appid . '.key';
            if (!is_file($newKey) && is_file($legacyKey)) {
                @rename($legacyKey, $newKey);
            }
            // 库里有私钥明文时，按规范化重写到团队目录 + ci 根目录(修复历史 \n 字面量损坏)
            $fromDb = (string)Config::where([
                'type' => 'mnp_setting',
                'name' => 'private_key',
                'team_id' => $tid,
            ])->value('value');
            if ($fromDb !== '') {
                try {
                    $normalized = self::normalizeMnpPrivateKey($fromDb);
                    $needRewrite = !is_file($newKey)
                        || (string)file_get_contents($newKey) !== $normalized
                        || !is_file($legacyKey)
                        || (string)@file_get_contents($legacyKey) !== $normalized;
                    if ($needRewrite) {
                        file_put_contents($newKey, $normalized);
                        @file_put_contents($legacyKey, $normalized);
                    }
                } catch (\Throwable $e) {
                    // 库内内容已损坏时不阻断工作区创建，提交时再报错
                }
            } elseif (is_file($newKey) && !is_file($legacyKey)) {
                @copy($newKey, $legacyKey);
            }
        }
    }

    /**
     * @notes 团队主上传自有小程序代码到微信(团队工作区 + 团队 appid)
     * @return array|false
     */
    public static function uploadMnp(int $userId, array $params)
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以上传小程序');
            return false;
        }
        $tid = (int)$user->team_id;
        try {
            $ciDir = self::mnpCiDir();
            $pkgDir = self::mnpCiPackageDir();
            if ($pkgDir === '') {
                throw new \Exception(
                    '未找到 miniprogram-ci 依赖。请确认已安装到以下任一目录：'
                    . $ciDir . 'node_modules/miniprogram-ci 或 '
                    . rtrim(root_path(), '/\\') . '/node_modules/miniprogram-ci'
                );
            }
            $appid = (string)ConfigService::get('mnp_setting', 'app_id', '', $tid);
            if ($appid === '') {
                throw new \Exception('请先在品牌管理中填写小程序 AppID');
            }
            self::ensureMnpTeamWorkspace($tid, $appid);
            $projectDir = self::mnpTeamProjectDir($tid);
            if (!is_dir($projectDir)) {
                throw new \Exception('请先上传本团队的小程序代码文件');
            }
            $privateKeyPath = self::mnpTeamPrivateKeyPath($tid, $appid);
            if (!is_file($privateKeyPath)) {
                throw new \Exception('请先设置小程序上传私钥（品牌管理 → 保存小程序凭证）');
            }
            // 上传前再校验磁盘私钥，避免无效内容跑完编译才失败
            try {
                self::normalizeMnpPrivateKey((string)file_get_contents($privateKeyPath));
            } catch (\Throwable $e) {
                throw new \Exception('当前上传私钥无效：' . $e->getMessage());
            }
            // 兼容旧版 upload.js：默认读 ci 根目录 private.{appid}.key，同步一份过去
            $legacyKeyPath = $ciDir . 'private.' . $appid . '.key';
            if (!is_file($legacyKeyPath) || md5_file($legacyKeyPath) !== md5_file($privateKeyPath)) {
                if (@copy($privateKeyPath, $legacyKeyPath) === false) {
                    // copy 失败仍继续，新版脚本会走 privateKeyPath
                    @file_put_contents($legacyKeyPath, (string)file_get_contents($privateKeyPath));
                }
            }
            if (!is_file($legacyKeyPath)) {
                throw new \Exception('私钥同步失败，无法写入：' . $legacyKeyPath);
            }
            // 用团队自有域名(无则用当前请求域名)重写 baseUrl
            // 兼容两种产物：baseUrl:变量名 / baseUrl:"https://..."
            $domain = (string)Team::where('id', $tid)->value('domain');
            $domain = $domain !== '' ? (preg_match('#^https?://#', $domain) ? $domain : 'https://' . $domain) : request()->domain(true);
            $domain = rtrim($domain, '/') . '/';
            $indexJs = $projectDir . '/config/index.js';
            if (file_exists($indexJs)) {
                $content = (string)file_get_contents($indexJs);
                $replacement = 'baseUrl:"' . $domain . '"';
                $newContent = null;
                if (preg_match('/baseUrl:\s*[a-zA-Z_$]/', $content)) {
                    // uni 构建常见：baseUrl:e / baseUrl:baseUrl
                    $newContent = preg_replace('/baseUrl:\s*[a-zA-Z_$][\w$]*/', $replacement, $content, 1);
                } elseif (preg_match('/baseUrl:\s*"(https?:\/\/[^"]*\/?)"/', $content)) {
                    $newContent = preg_replace('/baseUrl:\s*"(https?:\/\/[^"]*\/?)"/', $replacement, $content, 1);
                } elseif (preg_match("/baseUrl:\s*'(https?:\/\/[^']*\/?)'/", $content)) {
                    $newContent = preg_replace("/baseUrl:\s*'(https?:\/\/[^']*\/?)'/", $replacement, $content, 1);
                }
                if ($newContent !== null && $newContent !== $content) {
                    file_put_contents($indexJs, $newContent);
                } elseif ($newContent === null) {
                    throw new \Exception(
                        '未能在小程序包 config/index.js 中定位 baseUrl，域名替换失败。请确认上传的是 mp-weixin 构建产物。'
                    );
                }
            } else {
                throw new \Exception('小程序包缺少 config/index.js，无法替换接口域名');
            }
            // 同步 appid 到小程序项目配置（换 AppID 后必须一致）
            $projectConfigPath = $projectDir . DIRECTORY_SEPARATOR . 'project.config.json';
            if (is_file($projectConfigPath)) {
                $projectConfig = json_decode((string)file_get_contents($projectConfigPath), true);
                if (is_array($projectConfig) && ($projectConfig['appid'] ?? '') !== $appid) {
                    $projectConfig['appid'] = $appid;
                    $encoded = json_encode($projectConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    if ($encoded !== false) {
                        file_put_contents($projectConfigPath, $encoded . PHP_EOL);
                    }
                }
            }
            $resolvedProject = realpath($projectDir) ?: $projectDir;
            $resolvedKey = realpath($privateKeyPath) ?: $privateKeyPath;
            $data = [
                'appid'          => $appid,
                'version'        => $params['upload_version'] ?? ConfigService::get('mnp_setting', 'app_version', '2.0.0', $tid),
                'desc'           => $params['upload_desc'] ?? '',
                'projectPath'    => $resolvedProject,
                // 优先团队工作区；同时已同步到 ci 根目录，兼容旧脚本
                'privateKeyPath' => $resolvedKey,
            ];
            // 在 ci 目录执行；NODE_PATH 兼容依赖装在项目根的情况
            $nodePath = implode(PATH_SEPARATOR, array_filter([
                dirname($pkgDir), // .../node_modules
                $ciDir . 'node_modules',
                rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'node_modules',
            ]));
            $command = 'cd ' . escapeshellarg(rtrim($ciDir, DIRECTORY_SEPARATOR))
                . ' && NODE_PATH=' . escapeshellarg($nodePath)
                . ' node upload.js ' . escapeshellarg(json_encode($data, JSON_UNESCAPED_SLASHES)) . ' 2>&1';
            $output = null;
            $retval = null;
            exec($command, $output, $retval);
            if ($retval) {
                $err = is_array($output) ? implode("\n", $output) : (string)$output;
                if (str_contains($err, "Cannot find module 'miniprogram-ci'")) {
                    $err = '未找到 miniprogram-ci 模块(已查: ' . $ciDir . 'node_modules 与项目根 node_modules)。请在服务器执行: cd '
                        . $ciDir . ' && npm install';
                } elseif (
                    str_contains($err, 'less/lib/less')
                    || (str_contains($err, 'Cannot find module') && str_contains($err, 'less'))
                ) {
                    $err = '缺少兼容的 less 依赖（需 4.5.1，勿用 4.6+）。请在服务器执行: cd '
                        . rtrim($ciDir, '/\\') . ' && npm install less@4.5.1 --save-exact';
                } elseif (str_contains($err, '41001') || str_contains($err, 'access_token missing')) {
                    $err = '微信鉴权失败(41001 access_token missing)：代码已编译完成，但上传私钥无效或与 AppID 不匹配。'
                        . '请到微信公众平台 → 开发管理 → 开发设置 → 小程序代码上传，重新生成并下载私钥，'
                        . '在品牌管理中重新粘贴保存（须含 BEGIN/END），确认 AppID 为 ' . $appid . ' 后再提交。';
                } elseif (str_contains($err, '-10008') || str_contains($err, 'invalid ip')) {
                    $ip = '';
                    if (preg_match('/invalid ip:\s*([0-9.]+)/i', $err, $m)) {
                        $ip = $m[1];
                    }
                    $err = '微信拒绝上传：服务器出口 IP 未加入小程序代码上传白名单'
                        . ($ip !== '' ? '（当前 IP：' . $ip . '）' : '')
                        . '。请到微信公众平台 → 开发管理 → 开发设置 → 小程序代码上传 → IP 白名单，'
                        . '添加该 IP 后重试。文档：https://developers.weixin.qq.com/miniprogram/dev/devtools/ci.html';
                }
                self::setError($err);
                return false;
            }
            if (!empty($params['upload_version'])) {
                ConfigService::set('mnp_setting', 'app_version', $params['upload_version'], $tid);
            }
            return ['msg' => '上传成功'];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 团队主上传自有小程序代码包(zip)，解压到 teams/{tid}/mp-weixin
     * @return array|false
     */
    public static function uploadMnpCode(int $userId)
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以上传小程序代码');
            return false;
        }
        $tid = (int)$user->team_id;
        try {
            self::ensureMnpTeamWorkspace($tid);
            $workDir = self::mnpTeamWorkDir($tid);
            $tmpDir = $workDir . '.upload';
            $result = \app\common\service\UploadService::zipfile(0, 0, \app\common\enum\FileEnum::SOURCE_USER, rtrim($tmpDir, '/'));
            if (empty($result['url'])) {
                throw new \Exception('压缩包上传失败');
            }
            $zip = new \ZipArchive();
            if ($zip->open($result['url']) !== true) {
                throw new \Exception('无法打开ZIP文件');
            }
            $teamRoot = self::mnpTeamProjectDir($tid) . '/';
            // 重新上传时清空旧代码，避免残留文件干扰
            if (is_dir(rtrim($teamRoot, '/'))) {
                self::rmDirRecursive(rtrim($teamRoot, '/'));
            }
            self::ensureDir(rtrim($teamRoot, '/'), 0775);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                // 去掉可能的顶层 mp-weixin/ 前缀，统一落到团队 mp-weixin/
                $rel = preg_replace('#^mp-weixin/#', '', $entry);
                if ($rel === '' || $rel === false) {
                    continue;
                }
                $dest = $teamRoot . $rel;
                if (substr($entry, -1) === '/') {
                    self::ensureDir($dest, 0755);
                    continue;
                }
                self::ensureDir(dirname($dest), 0755);
                file_put_contents($dest, $zip->getFromIndex($i));
            }
            $zip->close();
            @unlink($result['url']);
            return ['msg' => '小程序代码已上传'];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 递归删除目录(仅用于团队小程序工作区清理)
     */
    private static function rmDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::rmDirRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * @notes 团队主查看本团队租户配置(域名/品牌/小程序)
     */
    public static function getTenant(int $userId): array
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_role != self::ROLE_OWNER) {
            self::setError('无权限');
            return [];
        }
        return self::tenantData((int)$user->team_id);
    }

    /**
     * @notes 图片相对路径补全访问链接(空值返回空,避免拼出无效域名)
     */
    private static function fileUrl($path): string
    {
        $path = (string)$path;
        return $path === '' ? '' : \app\common\service\FileService::getFileUrl($path);
    }

    /**
     * @notes 读团队品牌配置:优先团队自有,未配置则回落平台默认(避免未配置的OEM站点品牌空白)
     */
    private static function brandGet(string $name, int $tid): string
    {
        $v = (string)ConfigService::get('website', $name, '', $tid);
        if ($v === '' && $tid > 0) {
            $v = (string)ConfigService::get('website', $name, '', 0);
        }
        return $v;
    }

    /**
     * @notes OEM 站点页脚备案信息(备案号/企业名称)，结构对齐平台 copyright.config
     *        团队 OEM 站点始终用团队配置覆盖平台，避免露出主站主体信息；未填则对应行不展示
     * @return array|null null=非团队OEM,保持平台 copyright；array=覆盖(可为空)
     */
    public static function siteCopyright(?int $teamId = null): ?array
    {
        $tid = $teamId === null ? self::currentRequestSiteTeamId() : (int)$teamId;
        if ($tid <= 0) {
            return null;
        }
        $list = [];
        $icp = trim((string)ConfigService::get('website', 'icp_number', '', $tid));
        $company = trim((string)ConfigService::get('website', 'company_name', '', $tid));
        if ($icp !== '') {
            $list[] = ['key' => $icp, 'value' => ''];
        }
        if ($company !== '') {
            $list[] = ['key' => $company, 'value' => ''];
        }
        return $list;
    }

    /**
     * @notes 组装团队租户配置(域名/品牌/小程序)——供 api 与 adminapi 复用
     */
    public static function tenantData(int $tid): array
    {
        // 小程序仅取团队自有(不回退平台)
        $mnpOwn = function (string $name) use ($tid) {
            return (string)Config::where(['type' => 'mnp_setting', 'name' => $name, 'team_id' => $tid])->value('value');
        };
        $qrCode = $mnpOwn('qr_code');
        $domain = (string)Team::where('id', $tid)->value('domain');
        return [
            'team_id' => $tid,
            'domain' => $domain,
            'brand' => [
                // name=站点标题  web_logo=站点icon  pc_logo=站点logo  admin_qr=管理员联系二维码
                // icp_number=备案号  company_name=企业名称(页脚展示,不回落平台)
                // 图片统一补全访问链接(存相对路径,读回带域名),前端素材选择器/预览才能显示
                // 品牌未配置时回落平台默认,避免OEM站点空白
                'name' => self::brandGet('name', $tid),
                'web_logo' => self::fileUrl(self::brandGet('web_logo', $tid)),
                'pc_logo' => self::fileUrl(self::brandGet('pc_logo', $tid)),
                'admin_qr' => self::fileUrl(self::brandGet('admin_qr', $tid)),
                'icp_number' => (string)ConfigService::get('website', 'icp_number', '', $tid),
                'company_name' => (string)ConfigService::get('website', 'company_name', '', $tid),
            ],
            'mnp' => [
                'app_id' => $mnpOwn('app_id'),
                // app_secret 脱敏:只回传是否已配置,不回传明文(与 private_key 一致)
                'has_app_secret' => $mnpOwn('app_secret') !== '' ? 1 : 0,
                'original_id' => $mnpOwn('original_id'),
                'name' => $mnpOwn('name'),
                'qr_code' => $qrCode === '' ? '' : \app\common\service\FileService::getFileUrl($qrCode),
                // 只回传是否已配置私钥，不回传明文
                'has_private_key' => $mnpOwn('private_key') !== '' ? 1 : 0,
                // 磁盘上是否已有代码包(刷新页面可回显，无需重传)
                'has_mnp_code' => self::teamHasMnpCode($tid) ? 1 : 0,
                'app_version' => ConfigService::get('mnp_setting', 'app_version', '2.0.0', $tid),
                // 是否开启审核(默认关闭)
                'audit' => (int)$mnpOwn('audit'),
            ],
            // 微信公众平台需填写的服务器/业务/OSS 域名（只读，供 OEM 站长照抄）
            'mnp_domains' => self::buildMnpDomains($domain),
        ];
    }

    /**
     * @notes 组装小程序需配置的域名
     * OEM 小程序须同时配置：站点域名 + 主站域名 + 当前存储引擎空间域名
     */
    private static function buildMnpDomains(string $teamDomain): array
    {
        $siteHost = self::normalizeDomain($teamDomain);
        $mainHost = self::resolveMainSiteHost();
        // 站点未配时不拿主站顶替，避免和主站组重复；前端提示先配站点外观
        $site = self::formatMnpHostDomains($siteHost);
        $main = self::formatMnpHostDomains($mainHost);

        $engine = (string)ConfigService::get('storage', 'default', 'local');
        $engineNames = [
            'local' => '本地存储',
            'qiniu' => '七牛云',
            'aliyun' => '阿里云OSS',
            'qcloud' => '腾讯云COS',
        ];
        $ossDomain = '';
        if ($engine === 'local') {
            $ossDomain = rtrim((string)config('app.app_host'), '/');
        } else {
            $storage = ConfigService::get('storage', $engine, []);
            $ossDomain = rtrim((string)($storage['domain'] ?? ''), '/');
        }

        return [
            // 兼容旧扁平字段：优先站点，空则主站
            'request_domain' => $site['request_domain'] ?: $main['request_domain'],
            'socket_domain' => $site['socket_domain'] ?: $main['socket_domain'],
            'upload_file_domain' => $site['upload_file_domain'] ?: $main['upload_file_domain'],
            'download_file_domain' => $site['download_file_domain'] ?: $main['download_file_domain'],
            'udp_domain' => $site['udp_domain'] ?: $main['udp_domain'],
            'business_domain' => $site['business_domain'] ?: $main['business_domain'],
            // OEM 需同时配置站点 + 主站
            'site' => $site,
            'main' => $main,
            'oss_domain' => $ossDomain,
            'oss_engine' => $engine,
            'oss_engine_name' => $engineNames[$engine] ?? $engine,
        ];
    }

    /**
     * @notes 解析主站 host（project.domain / app_host，不依赖当前 OEM 请求 Host）
     */
    private static function resolveMainSiteHost(): string
    {
        $configured = self::normalizeDomain((string)config('project.domain', ''));
        if ($configured !== '') {
            return $configured;
        }
        $appHost = self::normalizeDomain((string)parse_url((string)config('app.app_host'), PHP_URL_HOST));
        if ($appHost !== '') {
            return $appHost;
        }
        // 仅非 OEM 请求时，当前 Host 可作为主站补充
        foreach (self::mainSiteDomains() as $host) {
            if ($host !== '') {
                return $host;
            }
        }
        return '';
    }

    /**
     * @notes 按 host 拼出微信各协议域名
     */
    private static function formatMnpHostDomains(string $host): array
    {
        $host = self::normalizeDomain($host);
        return [
            'host' => $host,
            'request_domain' => $host !== '' ? 'https://' . $host : '',
            'socket_domain' => $host !== '' ? 'wss://' . $host : '',
            'upload_file_domain' => $host !== '' ? 'https://' . $host : '',
            'download_file_domain' => $host !== '' ? 'https://' . $host : '',
            'udp_domain' => $host !== '' ? 'udp://' . $host : '',
            'business_domain' => $host,
        ];
    }

    /**
     * @notes 团队主设置本团队租户配置(域名唯一校验 + 品牌 + 小程序)
     */
    public static function setTenant(int $userId, array $params): bool
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty() || $user->team_role != self::ROLE_OWNER) {
            self::setError('无权限');
            return false;
        }
        try {
            self::assertOemActive((int)$user->team_id);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
        return self::setTenantByTeam((int)$user->team_id, $params);
    }

    /**
     * @notes 按团队id设置租户配置(域名唯一校验 + 品牌 + 小程序)——供 api 与 adminapi 复用
     */
    public static function setTenantByTeam(int $tid, array $params): bool
    {
        Db::startTrans();
        try {
            if (isset($params['domain'])) {
                $domain = self::normalizeDomain((string)$params['domain']);
                if ($domain !== '') {
                    // OEM 站点域名不可占用主站域名(否则会把主站解析成该团队 OEM)
                    self::assertDomainNotMainSite($domain);
                    $exists = Team::where('domain', $domain)->where('id', '<>', $tid)->find();
                    if ($exists) {
                        throw new \Exception('该域名已被其他团队绑定');
                    }
                    // 新团队接管域名时，清掉软删团队上的同名域名 + 关站落痕
                    Team::onlyTrashed()->where('domain', $domain)->update(['domain' => '']);
                    self::clearClosedDomainMarker($domain);
                }
                Team::where('id', $tid)->update(['domain' => $domain]);
            }
            if (!empty($params['brand']) && is_array($params['brand'])) {
                foreach (['name', 'web_logo', 'pc_logo', 'admin_qr', 'icp_number', 'company_name'] as $k) {
                    if (array_key_exists($k, $params['brand'])) {
                        $v = $params['brand'][$k];
                        // logo/icon/二维码 存相对路径
                        if (in_array($k, ['web_logo', 'pc_logo', 'admin_qr']) && $v) {
                            $v = \app\common\service\FileService::setFileUrl($v);
                        }
                        if (in_array($k, ['icp_number', 'company_name'], true)) {
                            $v = trim((string)$v);
                        }
                        ConfigService::set('website', $k, $v, $tid);
                    }
                }
            }
            if (!empty($params['mnp']) && is_array($params['mnp'])) {
                // OEM 小程序 AppID 不可占用主站渠道配置(否则 check/resolve 会把主站小程序解析成 OEM)
                if (array_key_exists('app_id', $params['mnp'])) {
                    self::assertMnpAppIdNotMainSite((string)$params['mnp']['app_id']);
                }
                // app_secret 已脱敏不回传:仅在前端明确填了新值时才更新,空值不覆盖(同 private_key)
                if (array_key_exists('app_secret', $params['mnp']) && (string)$params['mnp']['app_secret'] !== '') {
                    ConfigService::set('mnp_setting', 'app_secret', (string)$params['mnp']['app_secret'], $tid);
                }
                foreach (['app_id', 'original_id', 'name', 'audit'] as $k) {
                    if (array_key_exists($k, $params['mnp'])) {
                        ConfigService::set('mnp_setting', $k, (string)$params['mnp'][$k], $tid);
                    }
                }
                if (array_key_exists('qr_code', $params['mnp'])) {
                    $qr = $params['mnp']['qr_code'] ? \app\common\service\FileService::setFileUrl($params['mnp']['qr_code']) : '';
                    ConfigService::set('mnp_setting', 'qr_code', $qr, $tid);
                }
                // 小程序上传私钥：规范化后写入团队工作区 + ci 根目录
                $appid = (string)($params['mnp']['app_id'] ?? ConfigService::get('mnp_setting', 'app_id', '', $tid));
                if (!empty($params['mnp']['private_key'])) {
                    if ($appid === '') {
                        throw new \Exception('请先填写小程序 AppID，再保存上传私钥');
                    }
                    $normalized = self::normalizeMnpPrivateKey((string)$params['mnp']['private_key']);
                    ConfigService::set('mnp_setting', 'private_key', $normalized, $tid);
                    self::writeMnpPrivateKeyFile($tid, $appid, $normalized);
                } elseif ($appid !== '') {
                    // 本次未改私钥：仍同步/修复一次磁盘文件
                    self::ensureMnpTeamWorkspace($tid, $appid);
                }
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
     * @notes 域名规范化:去协议/去www/去端口/去尾斜杠/转小写。存与查两端统一,避免隔离静默失效。
     */
    public static function normalizeDomain(string $domain): string
    {
        $d = strtolower(trim($domain));
        $d = preg_replace('#^https?://#', '', $d);   // 去协议
        $d = preg_replace('#/.*$#', '', $d);          // 去路径/尾斜杠
        $d = preg_replace('#:\d+$#', '', $d);         // 去端口
        $d = preg_replace('#^www\.#', '', $d);        // 去 www.
        return $d;
    }

    /**
     * @notes 主站域名列表(规范化后):环境配置 + 当前落在主站时的请求 Host
     * @return string[]
     */
    public static function mainSiteDomains(): array
    {
        $list = [];
        $configured = self::normalizeDomain((string)config('project.domain', ''));
        if ($configured !== '') {
            $list[] = $configured;
        }
        // 当前请求未命中团队 OEM 时,Host 即主站(或旧版换肤站)访问域名,同样禁止绑给 OEM
        if (self::currentRequestSiteTeamId() <= 0) {
            try {
                $host = self::normalizeDomain((string)\think\facade\Request::host());
                if ($host === '') {
                    $host = self::normalizeDomain((string)\think\facade\Request::domain());
                }
                if ($host !== '') {
                    $list[] = $host;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return array_values(array_unique($list));
    }

    /**
     * @notes OEM 域名不得使用主站域名
     * @throws \Exception
     */
    public static function assertDomainNotMainSite(string $domain): void
    {
        $domain = self::normalizeDomain($domain);
        if ($domain === '') {
            return;
        }
        if (in_array($domain, self::mainSiteDomains(), true)) {
            throw new \Exception('不能使用主站域名作为 OEM 站点域名');
        }
    }

    /**
     * @notes 主站渠道「微信小程序」AppID(team_id=0)
     */
    public static function mainSiteMnpAppId(): string
    {
        return trim((string)ConfigService::get('mnp_setting', 'app_id', '', 0));
    }

    /**
     * @notes 是否为主站渠道小程序 AppID
     */
    public static function isMainSiteMnpAppId(string $appid): bool
    {
        $appid = trim($appid);
        if ($appid === '') {
            return false;
        }
        $main = self::mainSiteMnpAppId();
        return $main !== '' && $appid === $main;
    }

    /**
     * @notes OEM 小程序不得使用主站渠道 AppID
     * @throws \Exception
     */
    public static function assertMnpAppIdNotMainSite(string $appid): void
    {
        $appid = trim($appid);
        if ($appid === '') {
            return;
        }
        if (self::isMainSiteMnpAppId($appid)) {
            throw new \Exception('不能使用主站小程序 AppID 作为 OEM 小程序配置');
        }
    }

    /**
     * @notes 已解散 OEM 站点的统一载荷(前端全屏关站,不回落主站)
     */
    public static function closedTenantPayload(string $reason = 'team_disbanded'): array
    {
        return [
            'team_id' => 0,
            'is_team' => 0,
            'is_oem' => 0,
            'site_closed' => 1,
            'close_reason' => $reason,
            'message' => '该站点已关闭',
            'name' => '',
            'web_logo' => '',
            'pc_logo' => '',
            'admin_qr' => '',
            'icp_number' => '',
            'company_name' => '',
        ];
    }

    /** @notes 解散/取消 OEM 后写入域名关站落痕(team_id=0 的全局 config) */
    private static function markClosedDomain(string $domain, int $teamId): void
    {
        $domain = self::normalizeDomain($domain);
        if ($domain === '' || $teamId <= 0) {
            return;
        }
        ConfigService::set('oem_site', 'closed_domain:' . $domain, (string)$teamId, 0);
    }

    /** @notes 存活 OEM 重新绑定域名时清除关站落痕 */
    private static function clearClosedDomainMarker(string $domain): void
    {
        $domain = self::normalizeDomain($domain);
        if ($domain === '') {
            return;
        }
        ConfigService::set('oem_site', 'closed_domain:' . $domain, '', 0);
    }

    /** @notes 该域名是否已被存活 OEM 占用 */
    private static function hasAliveOemDomain(string $domain): bool
    {
        $domain = self::normalizeDomain($domain);
        if ($domain === '') {
            return false;
        }
        return Team::where('domain', $domain)->where('status', 1)->where('oem_status', 2)->count() > 0;
    }

    /**
     * @notes 域名/appid 是否对应已解散的团队 OEM 站点
     */
    private static function resolveClosedTenant(string $domain, string $appid): ?array
    {
        // 1) 域名关站落痕(优先:不依赖软删行是否仍保留 domain)
        if ($domain !== '') {
            $marker = trim((string)ConfigService::get('oem_site', 'closed_domain:' . $domain, '', 0));
            if ($marker !== '' && !self::hasAliveOemDomain($domain)) {
                return self::closedTenantPayload('team_disbanded');
            }
            // 2) 软删/停用团队仍挂该域名(绕过 SoftDelete onlyTrashed 在 delete_time=0 口径下的歧义)
            $closed = Db::name('team')
                ->where('domain', $domain)
                ->where(function ($q) {
                    $q->where('delete_time', '>', 0)->whereOr('status', 0);
                })
                ->order('id', 'desc')
                ->find();
            if ($closed && !self::hasAliveOemDomain($domain)) {
                return self::closedTenantPayload('team_disbanded');
            }
        }
        // 3) 解散时写入的 appid 关站标记(config 已清,靠 oem_site 找回)
        if ($appid !== '') {
            $marker = trim((string)ConfigService::get('oem_site', 'closed_appid:' . $appid, '', 0));
            if ($marker !== '') {
                // 若该 appid 已重新绑到存活 OEM,则关站标记失效
                $cfg = Config::where(['type' => 'mnp_setting', 'name' => 'app_id'])
                    ->where('value', $appid)
                    ->where('team_id', '>', 0)
                    ->find();
                if ($cfg) {
                    $alive = Team::where('id', (int)$cfg->team_id)
                        ->where('status', 1)
                        ->where('oem_status', 2)
                        ->count() > 0;
                    if ($alive) {
                        return null;
                    }
                }
                return self::closedTenantPayload('team_disbanded');
            }
        }
        return null;
    }

    /**
     * @notes 按域名/小程序appid解析租户，返回品牌配置(无需登录)
     */
    public static function resolveTenant(string $domain = '', string $appid = ''): array
    {
        $teamId = 0;
        $domain = self::normalizeDomain($domain);
        $appid = trim($appid);
        // 主站渠道 AppID：即使 Host 是 OEM 域 / 库里被 OEM 误填，也按主站小程序解析
        $isMainMnpAppId = self::isMainSiteMnpAppId($appid);
        // 仅已开通 OEM(oem_status=2)且启用的团队才能被解析为独立站点,
        // 防非 OEM/审核中/已拒团队(哪怕被 admin 绑了域名)白嫖独立站点能力
        if ($domain !== '' && !$isMainMnpAppId) {
            $team = Team::where('domain', $domain)->where('status', 1)->where('oem_status', 2)->find();
            if ($team) {
                $teamId = (int)$team->id;
            }
        }
        if ($teamId === 0 && $appid !== '' && !$isMainMnpAppId) {
            $cfg = Config::where(['type' => 'mnp_setting', 'name' => 'app_id'])
                ->where('value', $appid)
                ->where('team_id', '>', 0)
                ->find();
            if ($cfg) {
                $cid = (int)$cfg->team_id;
                // appid 同样要求团队已开通 OEM 且启用
                $ok = Team::where('id', $cid)->where('status', 1)->where('oem_status', 2)->count() > 0;
                $teamId = $ok ? $cid : 0;
            }
        }
        // 存活 OEM 未命中 → 识别已解散站点,避免 Host 回落主站
        // 主站小程序 appid 不参与关站 appid 命中；带主站 appid 时也不吃 OEM 域名关站
        if ($teamId === 0 && !$isMainMnpAppId) {
            $closed = self::resolveClosedTenant($domain, $appid);
            if ($closed !== null) {
                return $closed;
            }
        }
        // 团队 OEM 未命中 → 回落到旧版 OEM(iw_oem,按域名的站点级换肤),统一从本接口解析,
        // 前端无需再单独调 /oem/check。
        if ($teamId === 0 && $domain !== '' && !$isMainMnpAppId) {
            $oem = \app\common\model\oem\Oem::where('domain', $domain)->where('status', 1)->findOrEmpty();
            if (!$oem->isEmpty()) {
                $logo = \app\common\service\FileService::getFileUrl($oem->logo_url);
                $siteLogo = is_null($oem->site_logo) ? '' : \app\common\service\FileService::getFileUrl($oem->site_logo);
                return [
                    'team_id'  => 0,
                    'is_team'  => 0,
                    'is_oem'   => 1, // 旧版OEM站点(用户级换肤)
                    'site_closed' => 0,
                    'name'     => (string)$oem->name,
                    'web_logo' => $logo,
                    'pc_logo'  => $siteLogo ?: $logo,
                    'admin_qr' => '',
                    'icp_number' => '',
                    'company_name' => '',
                ];
            }
        }
        return [
            'team_id' => $teamId,
            'is_team' => $teamId > 0 ? 1 : 0,
            'is_oem' => 0,
            'site_closed' => 0,
            'name' => self::brandGet('name', $teamId),
            'web_logo' => self::fileUrl(self::brandGet('web_logo', $teamId)),
            'pc_logo' => self::fileUrl(self::brandGet('pc_logo', $teamId)),
            // 管理员联系二维码：OEM 站点用户「充值」弹窗展示(无需登录)
            'admin_qr' => $teamId > 0 ? self::fileUrl(self::brandGet('admin_qr', $teamId)) : '',
            // 页脚备案：仅团队自有，不回落平台
            'icp_number' => $teamId > 0 ? trim((string)ConfigService::get('website', 'icp_number', '', $teamId)) : '',
            'company_name' => $teamId > 0 ? trim((string)ConfigService::get('website', 'company_name', '', $teamId)) : '',
        ];
    }

    /**
     * @notes 注册归属:当前请求域名若为团队 OEM 站点,返回团队 id(新用户以散客 role=0 归属该站点,
     *        供品牌管理「站点用户」列表统计);非 OEM 域名归平台(0)。
     */
    public static function registerAttributionTeamId(): int
    {
        return self::currentRequestSiteTeamId();
    }

    /**
     * 当前请求所属站点团队 id:团队 OEM 站点>0,主站/旧版 OEM 换肤站=0
     */
    public static function currentRequestSiteTeamId(): int
    {
        try {
            $tenant = self::currentRequestTenant();
            return (int)($tenant['team_id'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @notes 当前请求是否命中已解散 OEM 关站
     */
    public static function currentRequestSiteClosed(): bool
    {
        try {
            $tenant = self::currentRequestTenant();
            return !empty($tenant['site_closed']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @notes 解析当前请求租户(域名 + appid)
     */
    public static function currentRequestTenant(): array
    {
        $domain = (string)\think\facade\Request::domain();
        $appid = (string)\think\facade\Request::param('appid', '');
        if ($appid === '') {
            $appid = (string)\think\facade\Request::header('appid', '');
        }
        return self::resolveTenant($domain, $appid);
    }

    /**
     * 邀请码是否允许在当前请求站点使用:
     * - 已开通 OEM(oem_status=2)的企业 → 仅对应 OEM 站点(requestSiteId=该团队id)
     * - 免费团 → 主站(requestSiteId=0)始终可用;若在 OEM 站请求,仅当团长归属该 OEM 站时放行
     */
    private static function inviteAllowedOnRequestSite(int $inviteTeamId, int $requestSiteId): bool
    {
        $team = Team::where('id', $inviteTeamId)->field('id,owner_id,oem_status')->findOrEmpty();
        if ($team->isEmpty()) {
            return false;
        }
        if ((int)$team->oem_status === 2) {
            return $requestSiteId === (int)$team->id;
        }
        // 免费团:主站可加入
        if ($requestSiteId <= 0) {
            return true;
        }
        // 免费团在 OEM 站:团长须归属该 OEM 站(同站自建免费团)
        $owner = User::where('id', (int)$team->owner_id)
            ->field('id,team_id,team_role,origin_team_id')
            ->findOrEmpty();
        if ($owner->isEmpty()) {
            return false;
        }
        $ownerSite = (int)$owner->origin_team_id;
        if ($ownerSite <= 0
            && (int)$owner->team_role === self::ROLE_NONE
            && (int)$owner->team_id > 0) {
            $ownerSite = (int)$owner->team_id;
        }
        return $ownerSite > 0 && $requestSiteId === $ownerSite;
    }

    /**
     * @notes 企业OEM「已用授权名额」= 旧版OEM绑定数 + 已开通(oem_status=2)团队数
     *        —— 与旧版 OEM 共用站长的全局授权名额(ToolsService::Auth)。
     */
    public static function oemUsedQuota(): int
    {
        $oemCount  = (int)\app\common\model\oem\Oem::count();
        $teamCount = (int)Team::where('oem_status', 2)->count();
        return $oemCount + $teamCount;
    }

    /**
     * @notes 站长全局授权名额上限(0=未授权/不限,交由外部授权服务返回)
     */
    public static function oemAuthNum(): int
    {
        try {
            $result = \app\common\service\ToolsService::Auth()->checkby();
            return (int)($result['authnum'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @notes 团队主给本团队用户划拨算力(从团队主名下划出，不增发)
     * @return bool
     */
    public static function allocateTokens(int $ownerId, int $userId, float $tokens): bool
    {
        if ($tokens <= 0) {
            self::setError('划拨算力需大于0');
            return false;
        }
        $owner = User::findOrEmpty($ownerId);
        if ($owner->isEmpty() || $owner->team_role != self::ROLE_OWNER) {
            self::setError('只有团队主可以划拨算力');
            return false;
        }
        $target = User::findOrEmpty($userId);
        $membership = TeamMember::where('team_id', $owner->team_id)->where('user_id', $userId)->findOrEmpty();
        $isAttributed = !$target->isEmpty() && $target->team_id == $owner->team_id && $target->team_role == self::ROLE_NONE;
        if ($target->isEmpty() || ($membership->isEmpty() && !$isAttributed)) {
            self::setError('该用户不属于你的团队');
            return false;
        }
        if ($target->id == $owner->id) {
            self::setError('不能给自己划拨');
            return false;
        }
        Db::startTrans();
        try {
            // 行锁重取团队主,防并发下余额校验失真
            $owner = User::where('id', $owner->id)->lock(true)->findOrEmpty();
            // 团队主余额是否充足(只划分不增发)
            if (bccomp((string)$owner->tokens, (string)$tokens, 2) < 0) {
                throw new \Exception('你的算力不足');
            }
            // 从团队主个人算力扣除
            $owner->tokens = bcsub((string)$owner->tokens, (string)$tokens, 2);
            $owner->save();
            $bizTeamId = (int)$owner->team_id;
            $opExtra = [
                'operator_id' => (int)$owner->id,
                'operator_role' => self::ROLE_OWNER,
                'target_user_id' => (int)$target->id,
            ];
            $ownerLabel = self::memberDisplayName((int)$owner->id, $owner);
            AccountLogLogic::add(
                $owner->id,
                AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
                AccountLogEnum::DEC,
                $tokens,
                1,
                '',
                '创始人「' . $ownerLabel . '」划拨企业算力给「' . self::memberDisplayName((int)$target->id, $target) . '」: +' . $tokens,
                $opExtra,
                $bizTeamId
            );
            if (!$membership->isEmpty()) {
                // 划入目标在本企业的企业算力钱包(切换到该企业时可用)
                $membership->team_tokens = bcadd((string)$membership->team_tokens, (string)$tokens, 2);
                $membership->save();
            } else {
                // 散客(无成员关系)：划入个人算力
                $target->tokens = bcadd((string)$target->tokens, (string)$tokens, 2);
                $target->save();
            }
            AccountLogLogic::add(
                $target->id,
                AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE,
                AccountLogEnum::INC,
                $tokens,
                1,
                '',
                $membership->isEmpty() ? '创始人划拨算力' : '创始人划拨企业算力',
                $opExtra,
                $bizTeamId
            );
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 我加入/创建的全部企业列表(自己创建的排第一)
     */
    public static function myTeams(int $userId): array
    {
        $rows = TeamMember::where('user_id', $userId)
            ->field('team_id,role,team_tokens,expire_time')
            ->order('role desc, id asc')
            ->select()
            ->toArray();
        if (!$rows) {
            return [];
        }
        $teams = Team::whereIn('id', array_column($rows, 'team_id'))
            ->field('id,name,owner_id,status,oem_status')
            ->select()
            ->toArray();
        $teamMap = array_column($teams, null, 'id');
        $current = (int)User::where('id', $userId)->value('team_id');
        // 批量取各团队「团队长个人算力」(企业空间共用池,展示用),避免 N+1
        $ownerIds = array_values(array_unique(array_filter(array_column($teams, 'owner_id'))));
        $ownerTokenMap = $ownerIds
            ? User::whereIn('id', $ownerIds)->column('tokens', 'id')
            : [];
        $now = time();
        $list = [];
        foreach ($rows as $r) {
            $t = $teamMap[$r['team_id']] ?? null;
            if (!$t) {
                continue; // 团队已解散
            }
            $role = (int)$r['role'];
            $expireTs = (int)$r['expire_time'];
            // 成员/管理员受到期限制;创始人永久
            $expired = in_array($role, [self::ROLE_MEMBER, self::ROLE_ADMIN], true)
                && $expireTs > 0
                && $expireTs < $now
                ? 1 : 0;
            $ownerTokens = (float)($ownerTokenMap[$t['owner_id']] ?? 0);
            $list[] = [
                'team_id' => (int)$t['id'],
                'name' => $t['name'],
                'role' => $role,
                'is_owner' => $role === self::ROLE_OWNER ? 1 : 0,
                'team_tokens' => $r['team_tokens'],
                // 企业算力口径 = 团队长个人算力(全员在企业空间共用团队长算力)
                'owner_tokens' => $ownerTokens,
                'oem_status' => (int)($t['oem_status'] ?? 0),
                'status' => (int)$t['status'],
                'is_current' => (int)$t['id'] === $current ? 1 : 0,
                'expire_time' => $expireTs,
                'expire_time_desc' => $expireTs > 0 ? date('Y-m-d', $expireTs) : '永久',
                'expired' => $expired,
            ];
        }
        return $list;
    }

    /**
     * @notes 切换当前企业(须为该企业成员)
     * @return bool
     */
    public static function switchTeam(int $userId, int $teamId): bool
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            self::setError('用户不存在');
            return false;
        }
        // 切换前若仍是 OEM 散客归属,先锁 origin,保证切走后仍在主站站点用户列表
        $originToLock = self::resolveSiteOriginTeamId($user);
        if ($originToLock > 0 && (int)$user->origin_team_id <= 0) {
            User::where('id', $userId)->update(['origin_team_id' => $originToLock]);
            $user->origin_team_id = $originToLock;
        }
        // 切回个人空间(team_id=0):任何登录用户都可切换
        if ($teamId <= 0) {
            User::update([
                'id' => $userId,
                'team_id' => 0,
                'team_role' => self::ROLE_NONE,
                'team_expire_time' => 0,
            ]);
            return true;
        }
        $membership = TeamMember::where('team_id', $teamId)->where('user_id', $userId)->findOrEmpty();
        if ($membership->isEmpty()) {
            self::setError('您不是该企业的成员');
            return false;
        }
        $team = Team::findOrEmpty($teamId);
        if ($team->isEmpty()) {
            self::setError('企业不存在');
            return false;
        }
        if ((int)$team->status !== 1) {
            self::setError('该企业已被停用，无法切换');
            return false;
        }
        // 成员/管理员到期不可再进入企业空间(算力仍挂在该企业钱包,续期后可进)
        $role = (int)$membership->role;
        $expireTs = (int)$membership->expire_time;
        if (in_array($role, [self::ROLE_MEMBER, self::ROLE_ADMIN], true)
            && $expireTs > 0
            && $expireTs < time()) {
            self::setError('你在该企业的成员资格已过期，请联系管理员续期');
            return false;
        }
        User::update([
            'id' => $userId,
            'team_id' => $teamId,
            'team_role' => $role,
            'team_expire_time' => $expireTs,
        ]);
        // 切回企业:挂回回收标记 + 历史 team_id=0(已入团但共享未恢复的成员,切一次即可修好)
        \app\common\service\TeamContextService::restoreUserTeamResources($userId, $teamId, true);
        return true;
    }

    /**
     * 账单备注用成员展示名:优先昵称,否则 SN
     * @param User|null $user 已加载的用户模型(可选,避免重复查询)
     */
    public static function memberDisplayName(int $userId, ?User $user = null): string
    {
        if (!$user || $user->isEmpty()) {
            $user = User::where('id', $userId)->field('id,nickname,sn')->findOrEmpty();
        }
        if ($user->isEmpty()) {
            return '用户' . $userId;
        }
        $name = trim((string)$user->nickname);
        if ($name !== '') {
            return $name;
        }
        $sn = trim((string)$user->sn);
        return $sn !== '' ? $sn : ('用户' . $userId);
    }
}
