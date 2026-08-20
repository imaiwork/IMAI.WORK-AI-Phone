<?php

namespace app\common\service\geo;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\chat\ModelsCost;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\GeoService;
use think\facade\Log;

/**
 * GEO 算力计费 —— 直接按【模型】计费,与对话(ChatBillingService)同一定价体系:
 *  - 单价来自 models_cost 表(后台「AI 模型」页,可从中台同步),按中台响应里的
 *    实际 model 别名取行:quota_type=1 固定 model_price/次,否则
 *    (输入tokens+输出tokens) × price ÷ 1000(统一按后台「AI模型」编辑页出售价,输入输出同价)
 *  - GEO 不再叠加 model_config 的 geo_* 场景价("算力/次"),场景常量仅保留
 *    账变编号与名称,供流水渲染与幂等键使用
 *  - models_cost 无该模型行时不扣费并告警(宁可漏收不误扣)
 *  - 余额 = 个人算力 + 企业算力钱包(TeamBillingService::spendableTokens 预检 / userTokensChange 扣减)
 *  - GEO 中台未启用(GeoService::enabled()=false)一律不计费
 *  - 先 precheck(余额预检,按 estimate 典型算力阈值,不扣),业务成功后按 usage 结算;失败不扣。
 *
 * 官网自有站点/授权直发/AI 手机发布免费;历史媒体代发退费见 GeoPublishService。
 */
class GeoChargeService
{
    /**
     * scene => [记账类型code, 名称, 单位, 默认单价(未配置 model_config 行时兜底)]
     * 生成类 8 场景(见 TOKEN_SCENES):与系统既有按量计费场景(chat/common_chat 等)同口径,
     *   score 语义为 tokens/算力(多少 token 兑 1 算力,除数),estimate=预检用典型单次消耗;
     * 10 个场景均按实际 token 结算;失败不扣。
     */
    public const SCENES = [
        'geo_content'     => ['code' => AccountLogEnum::TOKENS_DEC_GEO_CONTENT, 'name' => 'AI生成文章', 'unit' => 'tokens/算力', 'default' => 200, 'estimate' => 20],
        'geo_monitor'     => ['code' => AccountLogEnum::TOKENS_DEC_GEO_MONITOR, 'name' => 'AI搜索监测', 'unit' => 'tokens/算力', 'default' => 200, 'estimate' => 5],
        'geo_topic_ai'    => ['code' => AccountLogEnum::TOKENS_DEC_GEO_TOPIC_AI, 'name' => 'AI推荐话题', 'unit' => 'tokens/算力', 'default' => 400, 'estimate' => 5],
        'geo_question_ai' => ['code' => AccountLogEnum::TOKENS_DEC_GEO_QUESTION_AI, 'name' => 'AI生成场景问题', 'unit' => 'tokens/算力', 'default' => 200, 'estimate' => 10],
        'geo_knowledge'   => ['code' => AccountLogEnum::TOKENS_DEC_GEO_KNOWLEDGE, 'name' => '知识解析导入', 'unit' => 'tokens/算力', 'default' => 500, 'estimate' => 10],
        'geo_analyze'     => ['code' => AccountLogEnum::TOKENS_DEC_GEO_ANALYZE, 'name' => '品牌分析', 'unit' => 'tokens/算力', 'default' => 300, 'estimate' => 10],
        'geo_suggestion'  => ['code' => AccountLogEnum::TOKENS_DEC_GEO_SUGGESTION, 'name' => '优化建议', 'unit' => 'tokens/算力', 'default' => 600, 'estimate' => 5],
        'geo_video'       => ['code' => AccountLogEnum::TOKENS_DEC_GEO_VIDEO, 'name' => '文章转短视频', 'unit' => 'tokens/算力', 'default' => 50, 'estimate' => 50],
        'geo_match_brand' => ['code' => AccountLogEnum::TOKENS_DEC_GEO_MATCH_BRAND, 'name' => 'AI匹配品牌信息', 'unit' => 'tokens/算力', 'default' => 150, 'estimate' => 5],
        'geo_report'      => ['code' => AccountLogEnum::TOKENS_DEC_GEO_REPORT, 'name' => 'GEO诊断报告', 'unit' => 'tokens/算力', 'default' => 250, 'estimate' => 30],
    ];

    /** 按实际 token 计费的场景(中台响应返回 data.usage;监测自中台 v2.1 起透出 usage) */
    public const TOKEN_SCENES = [
        'geo_content', 'geo_knowledge', 'geo_analyze', 'geo_suggestion',
        'geo_report', 'geo_topic_ai', 'geo_question_ai', 'geo_match_brand',
        'geo_monitor', 'geo_video',
    ];

    /**
     * 监测计价必备模型:四个引擎的上游模型(联网检索与模型直答同模型),
     * 均随中台对话模型同步下发,计价行由同步维护,无本地种子。
     * models_cost 缺行时监测免费放行,故模型同步后会据此清单检查并提示;
     * 中台监测契约换模型时需同步更新此清单,否则检查会误报/漏报。
     */
    public const MONITOR_PRICE_MODELS = [
        'deepseek-v4-pro'             => 'DeepSeek监测',
        'doubao-seed-2-0-lite-260428' => '豆包监测',
        'qwen-flash'                  => '通义监测',
        'hy3'                         => '元宝监测',
    ];

    /**
     * 监测计价行缺失检查:返回缺行(或已停用)的 [alias => 名称],全齐返回空数组。
     */
    public static function missingMonitorPriceRows(): array
    {
        $aliases = array_keys(self::MONITOR_PRICE_MODELS);
        $existing = ModelsCost::whereIn('alias', $aliases)
            ->where('status', 1)
            ->column('alias');
        $missing = [];
        foreach (self::MONITOR_PRICE_MODELS as $alias => $name) {
            if (!in_array($alias, $existing, true)) {
                $missing[$alias] = $name;
            }
        }
        return $missing;
    }

    /**
     * 是否启用计费:GEO 中台未配置(PROJECT_KEY.API_KEY 缺失)时不收费。
     */
    public static function enabled(): bool
    {
        return GeoService::enabled();
    }

    /** 请求内缓存的 models_cost 行,避免批量监测逐 cell 查表 */
    protected static array $costCache = [];

    /**
     * 按模型别名取 models_cost 计价行;停用或不存在返回 null。
     * 中台 usage 可能回带日期版本的上游模型名(如 gpt-4o-2024-11-20),
     * 与同步目录里的裸别名(gpt-4o)对不上会被误判缺行免费放行,
     * 故精确匹配不中时剥日期后缀再查。
     */
    public static function modelCost(string $alias): ?array
    {
        $alias = trim($alias);
        if ($alias === '') {
            return null;
        }
        if (array_key_exists($alias, self::$costCache)) {
            return self::$costCache[$alias];
        }
        $row = null;
        foreach (self::aliasCandidates($alias) as $cand) {
            $found = ModelsCost::where('alias', $cand)->where('status', 1)->findOrEmpty();
            if (!$found->isEmpty()) {
                $row = $found->toArray();
                break;
            }
        }
        return self::$costCache[$alias] = $row;
    }

    /** 别名候选:原名 → 剥 OpenAI 风格日期(-2024-11-20)→ 剥纯数字版本段(-20241022/-0125) */
    protected static function aliasCandidates(string $alias): array
    {
        $cands = [$alias];
        $noDate = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $alias);
        if ($noDate !== $alias) {
            $cands[] = $noDate;
        }
        $noVer = preg_replace('/-\d{4,8}$/', '', $alias);
        if (!in_array($noVer, $cands, true)) {
            $cands[] = $noVer;
        }
        return $cands;
    }

    /**
     * 按模型口径把 usage 折算成算力(与 ChatBillingService::calculatePoints 同口径)。
     * 无模型行时返回 0 并告警:宁可漏收不误扣。
     * @return array{points: float, alias: string, unit: string}
     */
    public static function pointsForUsage(array $usage): array
    {
        $alias = trim((string)($usage['model'] ?? '')) ?: (string)env('geo.gen_default', '');
        $cost = self::modelCost($alias);
        if ($cost === null) {
            Log::warning("GEO 模型计费:models_cost 无模型行,按免费放行(model={$alias})");
            return ['points' => 0.0, 'alias' => $alias, 'unit' => '未配置'];
        }
        if ((int)$cost['quota_type'] === 1) {
            return ['points' => round((float)$cost['model_price'], 2), 'alias' => $alias, 'unit' => $cost['model_price'] . ' 算力/次'];
        }
        $prompt = (int)($usage['prompt'] ?? 0);
        $completion = (int)($usage['completion'] ?? 0);
        // 中台只回 total 时并入统一单价折算,避免漏计
        if ($prompt + $completion <= 0) {
            $prompt = (int)($usage['total'] ?? 0);
        }
        // 2026-08-19 口径变更:统一按后台「AI模型」编辑页的出售价(models_cost.price)
        // 计费,输入输出同价 —— 该页面只展示/编辑这一个价,output_price 无编辑入口,
        // 双价计费会造成账单单价与后台页面对不上(用户明确要求以 detail 接口价格为准)
        $price = (float)$cost['price'];
        $points = round(($prompt + $completion) * $price / 1000, 2);
        return [
            'points' => $points,
            'alias' => $alias,
            'unit' => "{$cost['price']} 算力/千tokens",
        ];
    }

    /**
     * @deprecated GEO 已切换为模型计费,场景单价恒为 0。仅为兼容旧调用保留。
     */
    public static function price(string $scene): float
    {
        return 0.0;
    }

    /** 计费配置(供前端 chargeConfig 展示):模型计费模式 + 生成侧模型及其单价 */
    public static function priceList(): array
    {
        $genModel = (string)env('geo.gen_default', '');
        $genCost = self::modelCost($genModel);
        $out = [];
        foreach (self::SCENES as $scene => $conf) {
            $out[] = [
                'scene' => $scene,
                'name' => $conf['name'],
                'billing' => 'model',
                'model' => $scene === 'geo_monitor' ? '' : $genModel, // 监测按各引擎实际模型计费
                'score' => 0, // 兼容旧前端:场景加价已取消
                'unit' => $genCost ? "输入 {$genCost['price']} / 输出 {$genCost['output_price']} 算力/千tokens" : '按模型用量',
            ];
        }
        return $out;
    }

    /**
     * 预检用典型单次消耗(算力阈值):模型计费无法预知精确 token 数,
     * 按各场景经验值做余额门槛,只拦"明显不够",不作为扣费依据。
     */
    public static function estimate(string $scene): float
    {
        $conf = self::SCENES[$scene] ?? null;
        if (!$conf || !self::enabled()) {
            return 0.0;
        }
        return (float)($conf['estimate'] ?? 1);
    }

    /**
     * 预检:余额不足直接抛异常(不扣费)。
     * @param float $count 预计单位数(篇/次/条)
     * @return float 预计消耗算力
     * @throws \Exception 算力不足 / 团队成员到期
     */
    public static function precheck(int $userId, string $scene, float $count = 1): float
    {
        // 模型计费无法预知 token 数,按 estimate(典型算力阈值)预检
        $need = (float)(self::SCENES[$scene]['estimate'] ?? 1) * max(1, $count);
        if ($need <= 0 || $userId <= 0) {
            return 0.0;
        }
        // 防重复提交兜底:同用户同场景 5 秒内只放行一次(双击/连点即双份 AI 调用双份扣费;
        // 文件缓存非原子,竞态窗口极小,作为前端 loading 禁用之外的服务端保险)
        $lockKey = "geo_charge_lock:{$userId}:{$scene}";
        if (\think\facade\Cache::get($lockKey)) {
            throw new \Exception('操作太频繁,请稍候几秒再试', 4292);
        }
        \think\facade\Cache::set($lockKey, 1, 5);
        \app\common\service\TeamMemberService::assertActive($userId);
        $available = \app\common\service\TeamBillingService::spendableTokens($userId);
        if ($available < $need) {
            $name = self::SCENES[$scene]['name'] ?? $scene;
            throw new \Exception("算力不足:{$name}预计需 {$need} 算力,当前可用 {$available},请充值后再试", 4059);
        }
        return $need;
    }

    /**
     * 结算:业务成功后按实际量扣费并记账。
     * @param float $count 实际单位数;0 或单价为 0 时不产生任何扣费
     * @param string $ref  关联单号(GEO 任务 id 等;失败退费按它命中原 DEC 流水原路退回)
     */
    public static function settle(int $userId, string $scene, float $count, string $ref = '', array $extra = []): void
    {
        $unit = self::price($scene);
        $points = round($unit * $count, 2);
        if ($points <= 0 || $userId <= 0) {
            return;
        }
        $conf = self::SCENES[$scene];
        // 同 ref 幂等:Job 重投/接口重试时不重复扣费(流水落在 task_id 或 source_sn)
        if ($ref !== '' && self::hasSettled($userId, $conf['code'], $ref)) {
            return;
        }
        User::userTokensChange($userId, $points, 'dec');
        AccountLogLogic::recordUserTokensLog(true, $userId, $conf['code'], $points, $ref, array_merge([
            '扣费项目' => 'GEO·' . $conf['name'],
            '单价' => $unit . ' ' . $conf['unit'],
            '数量' => $count,
        ], $extra));
    }

    /** 是否按实际 token 计费的场景 */
    public static function isTokenScene(string $scene): bool
    {
        return in_array($scene, self::TOKEN_SCENES, true);
    }

    /**
     * 按实际 token 结算(仅 TOKEN_SCENES):points = 总token ÷ 单价(tokens/算力)。
     * 对齐系统既有按量场景(KnowledgeLogic/ChatBillingService)口径;
     * 中台成功必带 usage;无用量视为未成功,不扣费。
     * @param array $usage ['prompt'=>int,'completion'=>int,'total'=>int,'model'=>string]
     * @return float 实际扣除的算力(未扣返回 0)
     */
    public static function settleByUsage(int $userId, string $scene, array $usage, string $ref = '', array $extra = []): float
    {
        $conf = self::SCENES[$scene] ?? null;
        if (!$conf || $userId <= 0 || !self::enabled()) {
            return 0.0;
        }
        $prompt = (int)($usage['prompt'] ?? 0);
        $completion = (int)($usage['completion'] ?? 0);
        $total = (int)($usage['total'] ?? 0) ?: ($prompt + $completion);
        if ($total <= 0) {
            Log::warning("GEO 模型计费:无 usage,视为未成功不扣费(scene={$scene}, ref={$ref})");
            return 0.0;
        }
        // 直接按模型计费(models_cost),不再叠加场景加价
        ['points' => $points, 'alias' => $alias, 'unit' => $unitDesc] = self::pointsForUsage($usage);
        if ($points <= 0) {
            return 0.0;
        }
        // 同 ref 幂等:Job 重投/接口重试时不重复扣费(流水落在 task_id 或 source_sn)
        if ($ref !== '' && self::hasSettled($userId, $conf['code'], $ref)) {
            return 0.0;
        }
        User::userTokensChange($userId, $points, 'dec');
        AccountLogLogic::recordUserTokensLog(true, $userId, $conf['code'], $points, $ref, array_merge([
            '扣费项目' => 'GEO·' . $conf['name'],
            '计费模型' => $alias,
            '模型单价' => $unitDesc,
            '输入tokens' => $prompt,
            '输出tokens' => $completion,
            '总tokens' => $total,
            '实际消耗算力' => $points,
        ], $extra));
        return $points;
    }

    /**
     * 任务成功才按 usage 扣;失败/无用量不扣。
     * @param array $task GeoTaskService::dispatch 返回行(含 status/usage_tokens/id)
     */
    public static function settleIfSucceeded(int $userId, string $scene, array $task, string $ref = '', array $extra = []): float
    {
        if (($task['status'] ?? '') !== 'success') {
            return 0.0;
        }
        $ref = $ref !== '' ? $ref : (string)($task['id'] ?? '');
        return self::settleByUsage($userId, $scene, (array)($task['usage_tokens'] ?? []), $ref, $extra);
    }

    /**
     * 按原扣费流水原路退回(同 ref 幂等)。
     * @return float 退回算力,未退为 0
     */
    public static function refund(int $userId, string $scene, string $ref, array $extra = []): float
    {
        $conf = self::SCENES[$scene] ?? null;
        if (!$conf || $userId <= 0 || $ref === '') {
            return 0.0;
        }
        if (self::hasRefunded($userId, $conf['code'], $ref)) {
            return 0.0;
        }
        $points = self::settledAmount($userId, $conf['code'], $ref);
        if ($points <= 0) {
            return 0.0;
        }
        AccountLogLogic::recordUserTokensLog(false, $userId, $conf['code'], $points, $ref, array_merge([
            '扣费项目' => 'GEO·' . $conf['name'] . '失败退回',
        ], $extra));
        return $points;
    }

    public static function hasRefunded(int $userId, int $changeType, string $ref): bool
    {
        if ($ref === '' || $userId <= 0) {
            return false;
        }
        return UserTokensLog::where('user_id', $userId)
            ->where('change_type', $changeType)
            ->where('action', AccountLogEnum::INC)
            ->where(function ($q) use ($ref) {
                $q->where('task_id', $ref)->whereOr('source_sn', $ref);
            })
            ->value('id') ? true : false;
    }

    public static function settledAmount(int $userId, int $changeType, string $ref): float
    {
        if ($ref === '' || $userId <= 0) {
            return 0.0;
        }
        return (float)(UserTokensLog::where('user_id', $userId)
            ->where('change_type', $changeType)
            ->where('action', AccountLogEnum::DEC)
            ->where(function ($q) use ($ref) {
                $q->where('task_id', $ref)->whereOr('source_sn', $ref);
            })
            ->order('id', 'desc')
            ->value('change_amount') ?: 0);
    }

    /** 是否已对同一业务键扣过费 */
    public static function hasSettled(int $userId, int $changeType, string $ref): bool
    {
        if ($ref === '' || $userId <= 0) {
            return false;
        }
        return UserTokensLog::where('user_id', $userId)
            ->where('change_type', $changeType)
            ->where('action', AccountLogEnum::DEC)
            ->where(function ($q) use ($ref) {
                $q->where('task_id', $ref)->whereOr('source_sn', $ref);
            })
            ->value('id') ? true : false;
    }
}
