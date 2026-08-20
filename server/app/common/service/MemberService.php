<?php

namespace app\common\service;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use think\facade\Log;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\member\MemberUser;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use app\common\service\chat\ChatModelsService;
use think\facade\Db;

/**
 * 会员业务核心服务
 *
 * 负责:
 *  - 取用户当前的"有效配额"(会员 → 等级配额;非会员 → 免费用户配额)
 *  - 创建实体前的限额检查(智能体/知识库/人设/手机/数字人/音色/模型)
 *  - 兑换 / 赠送会员
 *  - 周期算力发放
 *  - 到期检查 + 软降级冻结超出实体
 *
 * 各业务点调用方式:
 *   if (!MemberService::canCreate($userId, 'robot', $existingCount)) {
 *       throw new \Exception('已达智能体上限,请升级会员');
 *   }
 */
class MemberService
{
    // 实体类型 → DB 表 + 关键列 配置(冻结时使用)
    // user_field: 用户外键列;status_field: 冻结标记列;active_value/frozen_value: 状态值
    public const ENTITIES = [
        'robot' => [
            'table' => 'kb_robot',
            'user_field' => 'user_id',
            'status_field' => 'is_enable',
            'active_value' => 1,
            'frozen_value' => 0,
            'quota_field' => 'max_robots',
            'label' => '智能体',
            'extra_active_where' => ['delete_time' => null],
        ],
        // 向量知识库（/api/kb.know/lists，表 kb_know）
        'knowledge' => [
            'table' => 'kb_know',
            'user_field' => 'user_id',
            'status_field' => 'is_enable',
            'active_value' => 1,
            'frozen_value' => 0,
            'quota_field' => 'max_knowledges',
            'label' => '知识库',
            'extra_active_where' => ['delete_time' => null],
        ],
        'persona' => [
            'table' => 'ai_persona',
            'user_field' => 'user_id',
            'status_field' => 'status',
            'active_value' => 1,
            'frozen_value' => 0,
            'quota_field' => 'max_personas',
            'label' => '人设',
            'extra_active_where' => ['delete_time' => null],
        ],
        // 蝉镜形象(/human/createVideo 用)
        'digital_human' => [
            'table' => 'human_anchor',
            'user_field' => 'user_id',
            'status_field' => 'status',
            'active_value' => 1,
            'frozen_value' => 9,    // 业务侧自定义 freeze 标记,避开厂商任务状态 0-5
            'quota_field' => 'max_digital_humans',
            'label' => '数字人',
        ],
        // 闪剪形象(/shanjian.shanjianVideoSetting/add 用)— 与 digital_human 共享同一个配额池
        'digital_human_shanjian' => [
            'table' => 'shanjian_anchor',
            'user_field' => 'user_id',
            'status_field' => 'status',
            'active_value' => 6,    // 闪剪侧 6 = success
            'frozen_value' => 9,
            'quota_field' => 'max_digital_humans',
            'label' => '数字人(闪剪)',
        ],
        'voice' => [
            'table' => 'human_voice',
            'user_field' => 'user_id',
            'status_field' => 'status',
            'active_value' => 1,
            'frozen_value' => 9,
            'quota_field' => 'max_voices',
            'label' => '音色',
        ],
    ];

    /**
     * 取用户当前的完整配额(merged: 等级配额 OR 免费用户默认配额)
     * @return array {grant_tokens, grant_cycle, max_robots, ..., allowed_models: {id:name}}
     */
    public static function getQuota(int $userId): array
    {
        $member = self::getActiveMembership($userId);
        if ($member && $member->level_id > 0) {
            $level = UserLevel::findOrEmpty($member->level_id);
            if (!$level->isEmpty()) {
                return self::levelToQuota($level);
            }
        }
        // 无会员 → 走系统默认等级(user_level.is_default=1)
        $default = UserLevel::where('is_default', 1)->findOrEmpty();
        if (!$default->isEmpty()) {
            $quota = self::levelToQuota($default);
            $quota['is_member'] = false; // 默认等级不算"已订阅"
            return $quota;
        }
        // 兜底:DB 里没默认行时退回硬编码默认值
        return self::defaultFreeQuota();
    }

    /** 系统默认等级(无则回落硬编码) */
    public static function getDefaultLevel(): ?UserLevel
    {
        $level = UserLevel::where('is_default', 1)->findOrEmpty();
        return $level->isEmpty() ? null : $level;
    }

    private static function levelToQuota(UserLevel $level): array
    {
        // 读原始字段,避免走模型 getter 的展示格式
        $rawAllowed = $level->getData('allowed_models');
        if (is_string($rawAllowed)) {
            $decoded = json_decode($rawAllowed, true);
            $rawAllowed = is_array($decoded) ? $decoded : [];
        }

        return [
            'name' => $level->level_name,
            'is_member' => true,
            'grant_tokens' => (float)$level->grant_tokens,
            'grant_cycle' => (int)$level->grant_cycle,
            'max_robots' => (int)$level->max_robots,
            'max_knowledges' => (int)$level->max_knowledges,
            'max_personas' => (int)$level->max_personas,
            'max_mobiles' => (int)$level->max_mobiles,
            'max_digital_humans' => (int)$level->max_digital_humans,
            'max_voices' => (int)$level->max_voices,
            // 接口展示: 按本地 models 表查 name
            'allowed_models' => self::formatAllowedModels(is_array($rawAllowed) ? $rawAllowed : []),
        ];
    }

    public static function defaultFreeQuota(): array
    {
        return [
            'name' => '免费用户',
            'is_member' => false,
            'grant_tokens' => 0,
            'grant_cycle' => 0,
            'max_robots' => 1,
            'max_knowledges' => 1,
            'max_personas' => 0,    // 0 = 禁止创建
            'max_mobiles' => 0,
            'max_digital_humans' => 0,
            'max_voices' => 0,
            // 空数组 = 不限制,可用全部大模型
            'allowed_models' => [],
        ];
    }

    /**
     * 解析为模型 id 列表(仅 id,用于落库/校验)
     * 兼容入参:
     *  - [2,4]
     *  - {"2":"ChatCPT4o"} / {"2":""}
     *  - [{"id":2,"name":"ChatCPT4o"}]
     *  - ["ChatCPT4o","DeepSeek"] / ["GPT","DeepSeek"] (旧格式)
     *
     * @return int[]
     */
    public static function parseAllowedModelIds($raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        $keys = array_keys($raw);
        $isList = $keys === range(0, count($raw) - 1);

        if ($isList) {
            foreach ($raw as $item) {
                if (is_array($item)) {
                    $id = (int)($item['id'] ?? $item['model_id'] ?? 0);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                    continue;
                }
                if (is_numeric($item)) {
                    $id = (int)$item;
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                    continue;
                }
                if (is_string($item) && $item !== '') {
                    foreach (self::resolveLegacyModelNames($item) as $id) {
                        $ids[] = $id;
                    }
                }
            }
        } else {
            foreach ($raw as $id => $_) {
                $id = (int)$id;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids, static fn($id) => (int)$id > 0)));
        sort($ids);
        return $ids;
    }

    /**
     * 接口展示用: id => 当前环境 models.name
     *
     * @return array<string,string>
     */
    public static function formatAllowedModels($raw): array
    {
        $ids = self::parseAllowedModelIds($raw);
        if (empty($ids)) {
            return [];
        }

        $names = Models::whereIn('id', $ids)->column('name', 'id');
        $result = [];
        foreach ($ids as $id) {
            $result[(string)$id] = $names[$id] ?? ('模型#' . $id);
        }
        return $result;
    }

    /** @deprecated 使用 formatAllowedModels / parseAllowedModelIds */
    public static function normalizeAllowedModels($raw): array
    {
        return self::formatAllowedModels($raw);
    }

    /** 旧名称 / 家族名 → 模型 id 列表 */
    private static function resolveLegacyModelNames(string $name): array
    {
        $ids = Models::where('name', $name)->column('id');
        if (!empty($ids)) {
            return array_map('intval', $ids);
        }

        $models = Models::field('id,name,channel')->select()->toArray();
        foreach ($models as $model) {
            if (strcasecmp((string)$model['name'], $name) === 0) {
                $ids[] = (int)$model['id'];
            }
        }
        if (!empty($ids)) {
            return array_values(array_unique($ids));
        }

        $family = self::matchModelFamily($name);
        $matched = [];
        $costs = ModelsCost::field('model_id,alias,name')->select()->toArray();
        foreach ($costs as $cost) {
            $alias = (string)($cost['alias'] ?: $cost['name']);
            if (strcasecmp(self::matchModelFamily($alias), $family) === 0) {
                $matched[] = (int)$cost['model_id'];
            }
        }
        foreach ($models as $model) {
            if (
                strcasecmp(self::matchModelFamily((string)$model['name']), $family) === 0
                || strcasecmp(self::matchModelFamily((string)$model['channel']), $family) === 0
            ) {
                $matched[] = (int)$model['id'];
            }
        }

        return array_values(array_unique(array_filter($matched)));
    }

    /** 模型别名/名称归并到家族名(仅用于兼容旧 allowed_models) */
    public static function matchModelFamily(string $model): string
    {
        $m = strtolower($model);
        if (str_contains($m, 'gpt') || str_contains($m, 'chatgpt') || str_contains($m, 'o1') || str_contains($m, 'o3')) {
            return 'GPT';
        }
        if (str_contains($m, 'deepseek')) {
            return 'DeepSeek';
        }
        if (str_contains($m, 'claude') || str_contains($m, '克洛德')) {
            return 'Claude';
        }
        if (str_contains($m, 'doubao') || str_contains($m, 'volc') || str_contains($m, 'seedream') || str_contains($m, 'seedance')) {
            return 'Doubao';
        }
        if (str_contains($m, 'kimi') || str_contains($m, 'moonshot')) {
            return 'Kimi';
        }
        if (str_contains($m, 'qwen') || str_contains($m, '通义')) {
            return 'Qwen';
        }
        if (str_contains($m, 'glm') || str_contains($m, 'zhipu') || str_contains($m, 'autoglm')) {
            return 'GLM';
        }
        if (str_contains($m, 'gemini') || str_contains($m, '谷歌智元')) {
            return 'Gemini';
        }
        return $model;
    }

    /** 取用户当前有效会员记录,若 end_time 已到则自动置过期 */
    public static function getActiveMembership(int $userId): ?MemberUser
    {
        $m = MemberUser::where('user_id', $userId)->findOrEmpty();
        if ($m->isEmpty()) return null;
        if ($m->status == MemberUser::STATUS_ACTIVE && $m->end_time > 0 && $m->end_time < time()) {
            // 懒过期:被读到就自动降级 + 冻结超出
            self::expireAndFreeze($m);
            return null;
        }
        return $m->status == MemberUser::STATUS_ACTIVE ? $m : null;
    }

    /**
     * 创建前校验(同时支持"-1=禁止" 0=不限 N=最多 N 个)
     */
    public static function canCreate(int $userId, string $type, int $existingCount, string &$reason = ''): bool
    {
        if (!isset(self::ENTITIES[$type])) {
            return true;
        }
        $quota = self::getQuota($userId);
        $field = self::ENTITIES[$type]['quota_field'];
        $limit = (int)($quota[$field] ?? 0);
        $label = self::ENTITIES[$type]['label'];
        if ($limit < 0) {
            $reason = "当前等级({$quota['name']})不允许创建{$label}";
            return false;
        }
        if ($limit > 0 && $existingCount >= $limit) {
            $reason = "已达{$label}上限({$limit})";
            return false;
        }
        return true;
    }

    /**
     * 设备绑定前校验（按会员等级 max_mobiles；不入 ENTITIES，避免到期冻结误改设备 status）
     * -1=禁止 0=不限 N=最多 N 台
     */
    public static function canBindDevice(int $userId, int $existingCount, string &$reason = ''): bool
    {
        $quota = self::getQuota($userId);
        $limit = (int)($quota['max_mobiles'] ?? 0);
        if ($limit < 0) {
            $reason = '已达到绑定上限';
            return false;
        }
        if ($limit > 0 && $existingCount >= $limit) {
            $reason = '已达到绑定上限';
            return false;
        }
        return true;
    }

    /**
     * 计入会员智能体配额的数量（排除人设自动创建等 quota_exempt=1）
     */
    public static function countQuotaRobots(int $userId): int
    {
        return (int)Db::name('kb_robot')
            ->where('user_id', $userId)
            ->where('is_enable', 1)
            ->where('quota_exempt', 0)
            ->whereNull('delete_time')
            ->count();
    }

    /**
     * 计入会员 Coze 智能体配额的数量
     * - source=用户
     * - type=智能体(1) + 工作流(2)
     */
    public static function countQuotaCozeAgents(int $userId): int
    {
        return (int)Db::name('coze_agent')
            ->where('source', 1)
            ->where('source_id', $userId)
            ->whereIn('type', [\app\common\model\coze\CozeAgent::TYPE_AGENT, \app\common\model\coze\CozeAgent::TYPE_WORKFLOW])
            ->count();
    }

    /**
     * 计入会员“智能体”配额的数量（普通智能体 + Coze智能体 + Coze工作流）
     */
    public static function countQuotaSmartAgents(int $userId): int
    {
        return self::countQuotaRobots($userId) + self::countQuotaCozeAgents($userId);
    }

    /**
     * 计入会员知识库配额的数量（kb_know，排除系统“模型大管家”）
     */
    public static function countQuotaKnowledges(int $userId): int
    {
        return (int)Db::name('kb_know')
            ->where('user_id', $userId)
            ->where('is_enable', 1)
            ->where('name', '<>', '模型大管家')
            ->whereNull('delete_time')
            ->count();
    }

    /**
     * 计入会员数字人配额的数量（仅统计 digital_human_anchor 创建成功项；系统默认形象不计入）
     */
    public static function countQuotaDigitalHumans(int $userId): int
    {
        return (int)Db::name('digital_human_anchor')
            ->where('user_id', $userId)
            ->where('status', 2)
            ->whereNull('delete_time')
            ->where(function ($q) {
                $q->whereNull('remark')
                    ->whereOr('remark', '<>', 'system_default');
            })
            ->count();
    }

    /**
     * 计入会员音色配额的数量
     * 统计生成中(0)+成功(1)，含蝉镜/闪剪（同表 human_voice）；失败(2)、系统默认音色不计入
     */
    public static function countQuotaVoices(int $userId): int
    {
        return (int)Db::name('human_voice')
            ->where('user_id', $userId)
            ->whereIn('status', [0, 1])
            ->whereNull('delete_time')
            ->where(function ($q) {
                $q->whereNull('remark')
                    ->whereOr('remark', '<>', 'system_default_voice');
            })
            ->count();
    }

    /**
     * 模型是否可用(按 models.id 校验)
     * allowed_models 为空表示不限制
     */
    public static function canUseModel(int $userId, int $modelId): bool
    {
        if ($modelId <= 0) {
            return true;
        }
        $allowed = self::parseAllowedModelIds(self::getQuota($userId)['allowed_models'] ?? []);
        if (empty($allowed)) {
            return true; // 等级没限制就放
        }
        return in_array($modelId, $allowed, true);
    }


    /**
     * 为用户随机选一对可用对话模型（主模型 + 匹配的启用子模型）
     * 候选：Models.type=CHAT 且 is_enable=1，且存在 status=1 的 ModelsCost，且通过等级 allowed_models
     *
     * @return array{model_id:int, model_sub_id:int, model:string}|null
     */
    public static function pickRandomChatModelPair(int $userId): ?array
    {
        $mains = Models::where('type', \app\common\enum\ChatEnum::MODEL_TYPE_CHAT)
            ->where('is_enable', 1)
            ->field('id,name')
            ->select()
            ->toArray();
        if (empty($mains)) {
            return null;
        }

        $candidates = [];
        foreach ($mains as $main) {
            $modelId = (int)($main['id'] ?? 0);
            if ($modelId <= 0 || !self::canUseModel($userId, $modelId)) {
                continue;
            }
            $costs = ModelsCost::where('model_id', $modelId)
                ->where('status', 1)
                ->where('alias', 'not in', ChatModelsService::getAgentExcludedModelAliases())
                ->field('id,name,alias')
                ->select()
                ->toArray();
            if (empty($costs)) {
                continue;
            }
            $cost = $costs[array_rand($costs)];
            $candidates[] = [
                'model_id' => $modelId,
                'model_sub_id' => (int)$cost['id'],
                'model' => (string)($cost['name'] ?? $cost['alias'] ?? $main['name'] ?? ''),
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        return $candidates[array_rand($candidates)];
    }


    /**
     * 赠送 / 兑换会员
     * - 等级不变且当前仍有效：续期，end_time 在原到期时间上累加 days（不覆盖 start_time）
     * - 新开 / 换等级 / 已过期：覆盖模式
     *     level_id 替换, start_time=now, end_time=now+days,
     *     last_grant_time 清零并立刻发一次新等级算力
     */
    public static function grant(int $userId, int $levelId, int $days, int $source = MemberUser::SOURCE_ADMIN, string $remark = ''): MemberUser
    {
        $level = UserLevel::findOrEmpty($levelId);
        if ($level->isEmpty() || $level->status != 1) {
            throw new \Exception('会员等级无效');
        }

        $now = time();
        $m = MemberUser::where('user_id', $userId)->findOrEmpty();
        $addSeconds = max(1, $days) * 86400;

        // 同等级且仍在有效期内 → 续期累加，不重置起止时间基准
        $isSameLevelRenew = !$m->isEmpty()
            && (int)$m->level_id === $levelId
            && (int)$m->status === MemberUser::STATUS_ACTIVE
            && (int)$m->end_time > $now;

        if ($isSameLevelRenew) {
            $m->end_time = (int)$m->end_time + $addSeconds;
            $m->status = MemberUser::STATUS_ACTIVE;
            $m->source = $source;
            $m->source_remark = $remark;
            $m->update_time = $now;
            $m->save();

            self::syncUserLevelId($userId, $levelId, $now);
            return $m;
        }

        $payload = [
            'user_id' => $userId,
            'level_id' => $levelId,
            'start_time' => $now,
            'end_time' => $now + $addSeconds,
            'status' => MemberUser::STATUS_ACTIVE,
            'last_grant_time' => 0,
            'source' => $source,
            'source_remark' => $remark,
            'update_time' => $now,
        ];

        if ($m->isEmpty()) {
            $payload['create_time'] = $now;
            $m = MemberUser::create($payload);
        } else {
            foreach ($payload as $k => $v) {
                $m->$k = $v;
            }
            $m->save();
        }

        // 同步 user.level_id，供个人中心 / AgentPermissionService 等读取
        self::syncUserLevelId($userId, $levelId, $now);

        // 立即发一次新等级的算力(换等级/新开)
        self::grantTokensIfDue($m);
        return $m;
    }

    /**
     * 周期算力发放(供 cron / grant 调用)
     * 通过行锁 + 事务保证并发安全，用自然月/年计算避免漂移
     */
    public static function grantTokensIfDue(MemberUser $m): void
    {
        $userId = (int)$m->user_id;
        $now    = time();

        Db::startTrans();
        try {
            // 行锁重取最新状态，防止 cron 并发重复发放
            $m = MemberUser::where('user_id', $userId)
                ->lock(true)
                ->findOrEmpty();
            if ($m->isEmpty()) {
                Db::commit();
                return;
            }
            if ($m->status != MemberUser::STATUS_ACTIVE) {
                Db::commit();
                return;
            }
            if ($m->end_time > 0 && $m->end_time < $now) {
                Db::commit();
                return;
            }

            $level = UserLevel::findOrEmpty($m->level_id);
            if ($level->isEmpty() || $level->grant_tokens <= 0 || $level->grant_cycle == 0) {
                Db::commit();
                return;
            }

            // create_time 经模型读出可能是日期字符串，取原始值并规范为 ?int
            $createTimeRaw = $m->getData('create_time');
            if ($createTimeRaw === null || $createTimeRaw === '') {
                $createTime = null;
            } elseif (is_numeric($createTimeRaw)) {
                $createTime = (int)$createTimeRaw;
            } else {
                $ts = is_string($createTimeRaw) ? strtotime($createTimeRaw) : false;
                $createTime = $ts !== false ? $ts : null;
            }
            $nextGrantAt = self::calcNextGrantTime((int)$m->last_grant_time, (int)$level->grant_cycle, $createTime);
            if ($nextGrantAt === null || $now < $nextGrantAt) {
                Db::commit();
                return;
            }

            User::where('id', $userId)->inc('tokens', $level->grant_tokens)->update();

            $sourceSn = 'member_grant_' . $userId . '_' . $level->level_id . '_' . date('YmdHis');
            $remark   = '会员周期赠送:' . $level->level_name . '(' . $level->grant_tokens . ')';
            $log = AccountLogLogic::add(
                $userId,
                AccountLogEnum::TOKENS_INC_MEMBER_GRANT,
                AccountLogEnum::INC,
                $level->grant_tokens,
                1,
                $sourceSn,
                $remark
            );
            if ($log === false) {
                throw new \RuntimeException('会员周期赠送流水写入失败');
            }

            MemberUser::where('user_id', $userId)->update([
                'last_grant_time' => $now,
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 计算下一次发放时间戳（基于自然月/年）
     * @return int|null null 表示无需发放
     */
    private static function calcNextGrantTime(int $lastGrantTime, int $grantCycle, ?int $createTime): ?int
    {
        if ($lastGrantTime > 0) {
            return match ($grantCycle) {
                UserLevel::CYCLE_DAY   => $lastGrantTime + 86400,
                UserLevel::CYCLE_MONTH => strtotime('+1 month', $lastGrantTime),
                UserLevel::CYCLE_YEAR  => strtotime('+1 year', $lastGrantTime),
                default                => null,
            };
        }
        // 首次发放：用 create_time 作为基准，此时 now >= create_time，立即发放
        return $createTime ?? 0;
    }

    /**
     * 到期处理:把状态置过期 + 软降级冻结超出实体
     * 冻结策略:按 create_time DESC 保留前 N 个(N=免费配额),其余置为 frozen_value
     */
    public static function expireAndFreeze(MemberUser $m): void
    {
        $m->status = MemberUser::STATUS_EXPIRED;
        $m->save();
        self::syncUserLevelId((int)$m->user_id, self::defaultUserLevelId());
        $free = self::getQuota($m->user_id);   // 此时已无有效会员,getQuota 会落到 free 配额
        foreach (self::ENTITIES as $type => $cfg) {
            $limit = (int)($free[$cfg['quota_field']] ?? 0);
            self::freezeExcess($m->user_id, $type, max(0, $limit));
        }
    }

    /**
     * 把用户某类实体超出 keep 个的"最近创建之外"冻结
     * keep=0 时全部冻结(免费禁止)
     * keep=-1 在 canCreate 已挡,无需处理
     */
    public static function freezeExcess(int $userId, string $type, int $keep): void
    {
        if (!isset(self::ENTITIES[$type])) return;
        if ($keep < 0) return;
        $cfg = self::ENTITIES[$type];
        $table = $cfg['table'];

        // 取当前 active 列表,按创建时间倒序,前 keep 个保留,其余冻结
        // robot：排除人设自动创建等不计配额智能体，避免误冻
        $query = Db::name($table)
            ->where($cfg['user_field'], $userId)
            ->where($cfg['status_field'], $cfg['active_value']);
        if ($type === 'robot') {
            $query->where('quota_exempt', 0);
        }
        if ($type === 'knowledge') {
            $query->where('name', '<>', '模型大管家')->whereNull('delete_time');
        }
        // voice：系统默认音色不计入配额，禁止冻结；并恢复历史误冻
        if ($type === 'voice') {
            self::restoreFrozenSystemDefaultVoices($userId);
            self::scopeQuotaVoice($query);
        }
        // 数字人形象：系统默认（digital_human_anchor.remark=system_default）禁止冻结
        if ($type === 'digital_human' || $type === 'digital_human_shanjian') {
            self::restoreFrozenSystemDefaultAnchors($userId);
            self::scopeQuotaDigitalHuman($query, $userId);
        }
        $rows = $query->order('create_time desc, id desc')->column('id');
        if (empty($rows)) return;
        $toFreeze = array_slice($rows, $keep);
        if (empty($toFreeze)) return;
        try {
            Db::name($table)
                ->whereIn('id', $toFreeze)
                ->update([$cfg['status_field'] => $cfg['frozen_value'], 'update_time' => time()]);
            Log::channel('member')->write('冻结实体:' . json_encode([
                'user_id'  => $userId,
                'type'     => $type,
                'label'    => $cfg['label'],
                'keep'     => $keep,
                'active'   => count($rows),
                'frozen'   => count($toFreeze),
                'ids'      => $toFreeze,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::channel('member')->write('冻结实体失败:' . json_encode([
                'user_id' => $userId,
                'type'    => $type,
                'error'   => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * 音色配额范围（与 countQuotaVoices 一致）：排除系统默认音色
     */
    private static function scopeQuotaVoice($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('remark')
                ->whereOr('remark', '<>', 'system_default_voice');
        });
    }

    /**
     * 数字人形象配额范围：排除关联 digital_human_anchor.remark=system_default 的记录
     *（human_anchor / shanjian_anchor 自身 remark 为空，靠 dh_id 关联）
     */
    private static function scopeQuotaDigitalHuman($query, int $userId = 0)
    {
        $sub = Db::name('digital_human_anchor')->where('remark', 'system_default');
        if ($userId > 0) {
            $sub->where('user_id', $userId);
        }
        $ids = $sub->column('id');
        if (empty($ids)) {
            return $query;
        }
        return $query->where(function ($q) use ($ids) {
            $q->whereNotIn('dh_id', $ids)
                ->whereOr('dh_id', 0)
                ->whereOr('dh_id', null);
        });
    }

    /**
     * 恢复被误冻的系统默认音色（status=9 → 1，仅看 remark）
     * @param int $userId 0=全量修复
     * @return int 恢复条数
     */
    public static function restoreFrozenSystemDefaultVoices(int $userId = 0): int
    {
        $cfg = self::ENTITIES['voice'];
        try {
            $query = Db::name($cfg['table'])
                ->where($cfg['status_field'], $cfg['frozen_value'])
                ->where('remark', 'system_default_voice');
            if ($userId > 0) {
                $query->where($cfg['user_field'], $userId);
            }
            $count = (int)$query->update([
                $cfg['status_field'] => $cfg['active_value'],
                'update_time' => time(),
            ]);
            if ($count > 0) {
                Log::channel('member')->write('恢复系统默认音色:' . json_encode([
                    'user_id' => $userId,
                    'restored' => $count,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            return $count;
        } catch (\Throwable $e) {
            Log::channel('member')->write('恢复系统默认音色失败:' . json_encode([
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return 0;
        }
    }

    /**
     * 恢复被误冻的系统默认形象（human_anchor / shanjian_anchor，靠 dh_id 关联 system_default）
     * @param int $userId 0=全量修复
     * @return int 恢复条数
     */
    public static function restoreFrozenSystemDefaultAnchors(int $userId = 0): int
    {
        try {
            $sub = Db::name('digital_human_anchor')->where('remark', 'system_default');
            if ($userId > 0) {
                $sub->where('user_id', $userId);
            }
            $ids = $sub->column('id');
            if (empty($ids)) {
                return 0;
            }
            $now = time();
            $restored = 0;
            foreach (['digital_human', 'digital_human_shanjian'] as $type) {
                $cfg = self::ENTITIES[$type];
                $q = Db::name($cfg['table'])
                    ->where($cfg['status_field'], $cfg['frozen_value'])
                    ->whereIn('dh_id', $ids);
                if ($userId > 0) {
                    $q->where($cfg['user_field'], $userId);
                }
                $restored += (int)$q->update([
                    $cfg['status_field'] => $cfg['active_value'],
                    'update_time' => $now,
                ]);
            }
            if ($restored > 0) {
                Log::channel('member')->write('恢复系统默认形象:' . json_encode([
                    'user_id'  => $userId,
                    'restored' => $restored,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            return $restored;
        } catch (\Throwable $e) {
            Log::channel('member')->write('恢复系统默认形象失败:' . json_encode([
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return 0;
        }
    }

    /** 会员过期后回退的 user.level_id（默认等级 id，无则 -1） */
    private static function defaultUserLevelId(): int
    {
        $default = self::getDefaultLevel();
        return $default ? (int)$default->id : -1;
    }

    /** 同步 user.level_id，与 member_user 订阅等级对齐 */
    private static function syncUserLevelId(int $userId, int $levelId, ?int $updateTime = null): void
    {
        User::where('id', $userId)->update([
            'level_id' => $levelId,
            'update_time' => $updateTime ?? time(),
        ]);
    }

    /** 用户续费后:解冻可允许范围内的项 */
    public static function thawWithinQuota(int $userId): void
    {
        // 先恢复历史误冻的系统默认音色/形象（不占配额）
        self::restoreFrozenSystemDefaultVoices($userId);
        self::restoreFrozenSystemDefaultAnchors($userId);

        $quota = self::getQuota($userId);
        foreach (self::ENTITIES as $type => $cfg) {
            $limit = (int)($quota[$cfg['quota_field']] ?? 0);
            if ($limit < 0) continue;
            $table = $cfg['table'];
            try {
                // 现有 active 数
                $activeQuery = Db::name($table)->where($cfg['user_field'], $userId)
                    ->where($cfg['status_field'], $cfg['active_value']);
                $frozenQuery = Db::name($table)->where($cfg['user_field'], $userId)
                    ->where($cfg['status_field'], $cfg['frozen_value']);
                if ($type === 'robot') {
                    $activeQuery->where('quota_exempt', 0);
                    $frozenQuery->where('quota_exempt', 0);
                }
                if ($type === 'knowledge') {
                    $activeQuery->where('name', '<>', '模型大管家')->whereNull('delete_time');
                    $frozenQuery->where('name', '<>', '模型大管家')->whereNull('delete_time');
                }
                if ($type === 'voice') {
                    self::scopeQuotaVoice($activeQuery);
                    self::scopeQuotaVoice($frozenQuery);
                }
                if ($type === 'digital_human' || $type === 'digital_human_shanjian') {
                    self::scopeQuotaDigitalHuman($activeQuery, $userId);
                    self::scopeQuotaDigitalHuman($frozenQuery, $userId);
                }
                $active = (int)$activeQuery->count();
                $slots = $limit == 0 ? PHP_INT_MAX : max(0, $limit - $active);
                if ($slots <= 0) continue;
                // 按 create_time desc 解冻最近冻结的若干个
                $frozen = $frozenQuery
                    ->order('create_time desc, id desc')
                    ->limit($slots)
                    ->column('id');
                if ($frozen) {
                    Db::name($table)->whereIn('id', $frozen)
                        ->update([$cfg['status_field'] => $cfg['active_value'], 'update_time' => time()]);
                    Log::channel('member')->write('解冻实体:' . json_encode([
                        'user_id'  => $userId,
                        'type'     => $type,
                        'label'    => $cfg['label'],
                        'limit'    => $limit,
                        'active'   => $active,
                        'slots'    => $slots,
                        'thawed'   => count($frozen),
                        'ids'      => $frozen,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            } catch (\Throwable $e) {
                Log::channel('member')->write('解冻实体失败:' . json_encode([
                    'user_id' => $userId,
                    'type'    => $type,
                    'error'   => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
    }
}
