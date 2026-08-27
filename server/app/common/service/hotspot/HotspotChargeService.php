<?php

namespace app\common\service\hotspot;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\chat\ChatBillingService;
use app\common\service\TeamBillingService;
use app\common\service\TeamMemberService;
use think\facade\Cache;

/**
 * 热点追踪扣费：TikHub 按次，方舟按模型 token。
 * 个人/团队钱包走 User::userTokensChange。
 */
class HotspotChargeService
{
    public const SCENE_TIKHUB_WORDS = 'hotspot_hot_words';
    public const SCENE_TIKHUB_DETAIL = 'hotspot_insight';
    public const TASK_ID_MAX_LEN = 50;

    /** @var array<string, array{code:int,name:string,unit:string,default:float}> */
    public const SCENES = [
        self::SCENE_TIKHUB_WORDS => [
            'code' => AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_WORDS,
            'name' => '热点热搜词拉取',
            'unit' => '算力/次',
            // 产品定义热榜拉取免费;后台 la_model_config 无该行时也不得回退收费
            'default' => 0,
        ],
        self::SCENE_TIKHUB_DETAIL => [
            'code' => AccountLogEnum::TOKENS_DEC_HOTSPOT_INSIGHT,
            'name' => '热点话题洞察',
            'unit' => '算力/次',
            'default' => 50,
        ],
    ];

    public static function requireUser(int $userId): void
    {
        if ($userId <= 0) {
            throw new \Exception('请先登录', 4059);
        }
    }

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array{
     *   price?: float,
     *   skipSettled?: bool,
     *   logError?: \Throwable,
     *   interceptTokens?: bool,
     *   silentLog?: bool,
     *   refundError?: \Throwable,
     *   tokenOps?: list<array{0:int,1:float,2:string}>,
     *   logs?: list<string>
     * }
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    public static function price(string $scene): float
    {
        if (array_key_exists('price', self::$testHooks)) {
            return (float)self::$testHooks['price'];
        }
        $conf = self::SCENES[$scene] ?? null;
        if ($conf === null) {
            return 0.0;
        }
        $row = ModelConfig::where('scene', $scene)->findOrEmpty();
        if ($row->isEmpty()) {
            // 无配置行视为"后台未开启收费":仅当代码默认值>0(历史兼容)才回退
            return (float)$conf['default'];
        }
        return (int)$row->status === 1 ? (float)$row->score : 0.0;
    }

    /**
     * @return float 预计消耗；单价为 0 时返回 0
     * @throws \Exception
     */
    public static function precheckTikhub(int $userId, string $scene): float
    {
        self::requireUser($userId);
        $need = self::price($scene);
        if ($need <= 0) {
            HotspotLog::write(sprintf('热点按次预检跳过：场景=%s 用户=%d 单价为0或已停用', $scene, $userId));
            return 0.0;
        }
        TeamMemberService::assertActive($userId);
        $available = TeamBillingService::spendableTokens($userId);
        if ($available < $need) {
            $name = self::SCENES[$scene]['name'] ?? $scene;
            $msg = TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主'
                : '用户算力不足';
            HotspotLog::write(sprintf(
                '热点按次预检失败：场景=%s 用户=%d 需=%s 可用=%s',
                $scene,
                $userId,
                (string)$need,
                (string)$available
            ));
            throw new \Exception($msg . '：' . $name . '预计需 ' . $need . ' 算力', 4059);
        }
        return $need;
    }

    public static function settleTikhub(int $userId, string $scene, string $ref = '', array $extra = []): float
    {
        self::requireUser($userId);
        $conf = self::SCENES[$scene] ?? null;
        if ($conf === null) {
            return 0.0;
        }
        $unit = self::price($scene);
        $points = round($unit, 2);
        if ($points <= 0) {
            self::chargeLog(sprintf('热点按次结算跳过：场景=%s 用户=%d 单价为0或已停用', $scene, $userId));
            return 0.0;
        }
        // 幂等保护:hasSettled 是普通 SELECT,与扣费之间无原子性;同一用户同一单号并发结算时
        // 用 Redis 互斥锁串行化,持锁后再复查一次流水,避免缓存击穿场景重复扣费
        $lockKey = '';
        $lockHandler = null;
        if ($ref !== '') {
            $lockKey = 'hotspot:charge:lock:' . md5($userId . '|' . $conf['code'] . '|' . $ref);
            $lockHandler = Cache::store('redis')->handler();
            $deadline = microtime(true) + 5;
            while (!(bool)$lockHandler->set($lockKey, 1, ['nx', 'ex' => 30])) {
                if (microtime(true) >= $deadline) {
                    throw new \Exception('热点扣费处理中，请稍后重试');
                }
                usleep(100000);
            }
        }
        try {
            if ($ref !== '' && self::hasSettled($userId, $conf['code'], $ref)) {
                self::chargeLog(sprintf('热点按次结算跳过：场景=%s 用户=%d 单号=%s 已扣过', $scene, $userId, $ref));
                return 0.0;
            }
            return self::doSettleTikhub($userId, $scene, $conf, $unit, $points, $ref, $extra);
        } finally {
            if ($lockKey !== '' && $lockHandler !== null) {
                $lockHandler->del($lockKey);
            }
        }
    }

    /**
     * @param array<string, mixed> $conf
     * @param array<string, mixed> $extra
     */
    private static function doSettleTikhub(int $userId, string $scene, array $conf, float $unit, float $points, string $ref, array $extra): float
    {
        self::changeTokens($userId, $points, 'dec');
        try {
            self::writeTokensLog(true, $userId, $conf['code'], $points, $ref, array_merge([
                '扣费项目' => '热点追踪·' . $conf['name'],
                '单价' => $unit . ' ' . $conf['unit'],
                '数量' => 1,
            ], $extra));
        } catch (\Throwable $e) {
            try {
                self::changeTokens($userId, $points, 'inc');
                self::chargeLog(sprintf(
                    '热点按次流水失败已退费：场景=%s 用户=%d 单号=%s 退费=%s 原因=%s',
                    $scene,
                    $userId,
                    $ref,
                    (string)$points,
                    $e->getMessage()
                ));
            } catch (\Throwable $refundEx) {
                self::chargeLog(sprintf(
                    '热点按次流水失败且退费失败：场景=%s 用户=%d 单号=%s 流水原因=%s 退费原因=%s',
                    $scene,
                    $userId,
                    $ref,
                    $e->getMessage(),
                    $refundEx->getMessage()
                ));
            }
            throw $e;
        }
        self::chargeLog(sprintf(
            '热点按次扣费成功：场景=%s 用户=%d 单号=%s 扣费=%s',
            $scene,
            $userId,
            $ref,
            (string)$points
        ));
        return $points;
    }

    /**
     * @throws \Exception
     */
    public static function precheckArk(int $userId, string $modelKey): void
    {
        self::requireUser($userId);
        ChatBillingService::checkBalance($userId, $modelKey);
    }

    /**
     * 从方舟完整返回体抽出用量。禁止从截断日志反推。
     *
     * @param array<string, mixed> $payload
     * @return array{prompt_tokens:int,completion_tokens:int,total_tokens:int}
     */
    public static function extractArkUsage(array $payload): array
    {
        $usage = $payload['usage'] ?? [];
        return self::normalizeArkUsage(is_array($usage) ? $usage : []);
    }

    /**
     * @param array<string, mixed> $usage
     * @return array{prompt_tokens:int,completion_tokens:int,total_tokens:int}
     */
    public static function normalizeArkUsage(array $usage): array
    {
        $prompt = (int)($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0);
        $completion = (int)($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0);
        $total = (int)($usage['total_tokens'] ?? 0);
        if ($total <= 0) {
            $total = $prompt + $completion;
        }
        return [
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $total,
        ];
    }

    public static function settleArk(
        int $userId,
        string $modelKey,
        array $usage,
        int $logType,
        string $taskId,
        string $path = '',
        array $extra = []
    ): float {
        self::requireUser($userId);
        $normalized = self::normalizeArkUsage($usage);
        if ($normalized['prompt_tokens'] <= 0 && $normalized['completion_tokens'] <= 0) {
            HotspotLog::write(sprintf(
                '方舟用量缺失或为0，跳过扣费：路径=%s 模型=%s 用户=%d 单号=%s',
                $path !== '' ? $path : '-',
                $modelKey,
                $userId,
                $taskId
            ));
            return 0.0;
        }
        if ($taskId !== '' && self::hasSettled($userId, $logType, $taskId)) {
            HotspotLog::write(sprintf(
                '方舟结算跳过：用户=%d 单号=%s 已扣过',
                $userId,
                $taskId
            ));
            return 0.0;
        }
        $points = ChatBillingService::charge($userId, $modelKey, $normalized, $logType, $taskId, $extra);
        HotspotLog::write(sprintf(
            '方舟用量：路径=%s 模型=%s 输入=%d 输出=%d 合计=%d 扣费=%s',
            $path !== '' ? $path : '-',
            $modelKey,
            $normalized['prompt_tokens'],
            $normalized['completion_tokens'],
            $normalized['total_tokens'],
            (string)$points
        ));
        return $points;
    }

    public static function hasSettled(int $userId, int $changeType, string $ref): bool
    {
        if (!empty(self::$testHooks['skipSettled'])) {
            return false;
        }
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

    public static function newRef(string $prefix): string
    {
        $suffix = bin2hex(random_bytes(8));
        $ref = $prefix . '-' . $suffix;
        if (strlen($ref) <= self::TASK_ID_MAX_LEN) {
            return $ref;
        }
        return self::compressTaskId($prefix, $suffix, $suffix);
    }

    /**
     * 确定性扣费单号：同一目标在同一缓存窗口内生成同一 ref，
     * 配合 hasSettled 幂等去重，缓存击穿导致的并发重复拉取只扣一次费。
     * 短单号保持原拼接；超长（超过 task_id varchar(50)）才压缩。
     */
    public static function windowRef(string $prefix, string $key, int $windowSeconds): string
    {
        $windowSeconds = max(60, $windowSeconds);
        $window = (string)intdiv(time(), $windowSeconds);
        $ref = $prefix . '-' . $key . '-' . $window;
        if (strlen($ref) <= self::TASK_ID_MAX_LEN) {
            return $ref;
        }
        return self::compressTaskId($prefix, $key, $window);
    }

    private static function compressTaskId(string $prefix, string $key, string $tail): string
    {
        $compressed = substr($prefix, 0, 8) . '-' . substr(hash('sha256', $prefix . '|' . $key), 0, 16) . '-' . $tail;
        if (strlen($compressed) <= self::TASK_ID_MAX_LEN) {
            return $compressed;
        }
        return substr(hash('sha256', $prefix . '|' . $key . '|' . $tail), 0, 32);
    }

    private static function changeTokens(int $userId, float $points, string $type): void
    {
        if (!empty(self::$testHooks['interceptTokens'])) {
            self::$testHooks['tokenOps'][] = [$userId, $points, $type];
            if ($type === 'inc'
                && isset(self::$testHooks['refundError'])
                && self::$testHooks['refundError'] instanceof \Throwable
            ) {
                throw self::$testHooks['refundError'];
            }
            return;
        }
        User::userTokensChange($userId, $points, $type);
    }

    private static function writeTokensLog(
        bool $success,
        int $userId,
        int $changeType,
        float $tokens,
        string $sourceSn,
        array $extra
    ): void {
        if (isset(self::$testHooks['logError']) && self::$testHooks['logError'] instanceof \Throwable) {
            throw self::$testHooks['logError'];
        }
        if (!empty(self::$testHooks['interceptTokens'])) {
            return;
        }
        AccountLogLogic::recordUserTokensLog($success, $userId, $changeType, $tokens, $sourceSn, $extra);
    }

    private static function chargeLog(string $msg): void
    {
        if (!empty(self::$testHooks['silentLog'])) {
            self::$testHooks['logs'][] = $msg;
            return;
        }
        HotspotLog::write($msg);
    }
}
