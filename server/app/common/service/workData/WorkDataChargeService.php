<?php

namespace app\common\service\workData;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\TeamBillingService;
use app\common\service\TeamMemberService;
use think\facade\Cache;

/**
 * 作品数据扣费：缓存未命中 / 强制刷新成功后按次一口价。
 */
class WorkDataChargeService
{
    public const SCENE_FETCH = 'work_data_fetch';
    public const TASK_ID_MAX_LEN = 50;

    /** @var array<string, array{code:int,name:string,unit:string,default:float}> */
    public const SCENES = [
        self::SCENE_FETCH => [
            'code' => AccountLogEnum::TOKENS_DEC_WORK_DATA_FETCH,
            'name' => '作品数据报表',
            'unit' => '算力/次',
            'default' => 50,
        ],
    ];

    /**
     * @var array{
     *   price?: float,
     *   skipSettled?: bool,
     *   logError?: \Throwable,
     *   interceptTokens?: bool,
     *   available?: float,
     *   skipLock?: bool,
     *   silentLog?: bool,
     *   refundError?: \Throwable,
     *   tokenOps?: list<array{0:int,1:float,2:string}>,
     *   settledRefs?: list<string>,
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

    public static function requireUser(int $userId): void
    {
        if ($userId <= 0) {
            throw new WorkDataException('请先登录', 4059);
        }
    }

    public static function price(string $scene = self::SCENE_FETCH): float
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
            return (float)$conf['default'];
        }
        return (int)$row->status === 1 ? (float)$row->score : 0.0;
    }

    /**
     * 给前端展示的单价（与 price() 同源，后台停用场景时返回 0）
     *
     * @return array{scene:string,name:string,score:float,unit:string}
     */
    public static function costItem(string $scene = self::SCENE_FETCH): array
    {
        $conf = self::SCENES[$scene] ?? null;
        return [
            'scene' => $scene,
            'name' => $conf['name'] ?? $scene,
            'score' => self::price($scene),
            'unit' => $conf['unit'] ?? '算力/次',
        ];
    }

    /**
     * @return float 预计消耗
     */
    public static function precheck(int $userId, string $scene = self::SCENE_FETCH): float
    {
        self::requireUser($userId);
        $need = self::price($scene);
        if ($need <= 0) {
            WorkDataLog::write(sprintf('作品数据预检跳过：场景=%s 用户=%d 单价为0或已停用', $scene, $userId));
            return 0.0;
        }
        if (!empty(self::$testHooks['interceptTokens'])) {
            $available = (float)(self::$testHooks['available'] ?? 99999);
        } else {
            TeamMemberService::assertActive($userId);
            $available = TeamBillingService::spendableTokens($userId);
        }
        if ($available < $need) {
            $name = self::SCENES[$scene]['name'] ?? $scene;
            $msg = TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主'
                : '用户算力不足';
            WorkDataLog::write(sprintf(
                '作品数据预检失败：场景=%s 用户=%d 需=%s 可用=%s',
                $scene,
                $userId,
                (string)$need,
                (string)$available
            ));
            throw new WorkDataException($msg . '：' . $name . '预计需 ' . $need . ' 算力', 4059);
        }
        return $need;
    }

    public static function settle(int $userId, string $ref = '', array $extra = [], string $scene = self::SCENE_FETCH): float
    {
        self::requireUser($userId);
        $conf = self::SCENES[$scene] ?? null;
        if ($conf === null) {
            return 0.0;
        }
        $unit = self::price($scene);
        $points = round($unit, 2);
        if ($points <= 0) {
            self::chargeLog(sprintf('作品数据结算跳过：场景=%s 用户=%d 单价为0或已停用', $scene, $userId));
            return 0.0;
        }
        $lockKey = '';
        $lockHandler = null;
        if ($ref !== '' && empty(self::$testHooks['skipLock'])) {
            $lockKey = 'work_data:charge:lock:' . md5($userId . '|' . $conf['code'] . '|' . $ref);
            $lockHandler = Cache::store('redis')->handler();
            $deadline = microtime(true) + 5;
            while (!(bool)$lockHandler->set($lockKey, 1, ['nx', 'ex' => 30])) {
                if (microtime(true) >= $deadline) {
                    throw new WorkDataException('作品数据扣费处理中，请稍后重试');
                }
                usleep(100000);
            }
        }
        try {
            if ($ref !== '' && self::hasSettled($userId, $conf['code'], $ref)) {
                self::chargeLog(sprintf('作品数据结算跳过：场景=%s 用户=%d 单号=%s 已扣过', $scene, $userId, $ref));
                return 0.0;
            }
            return self::doSettle($userId, $scene, $conf, $unit, $points, $ref, $extra);
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
    private static function doSettle(int $userId, string $scene, array $conf, float $unit, float $points, string $ref, array $extra): float
    {
        self::changeTokens($userId, $points, 'dec');
        try {
            self::writeTokensLog(true, $userId, $conf['code'], $points, $ref, array_merge([
                '扣费项目' => '作品数据·' . $conf['name'],
                '单价' => $unit . ' ' . $conf['unit'],
                '数量' => 1,
            ], $extra));
        } catch (\Throwable $e) {
            try {
                self::changeTokens($userId, $points, 'inc');
                self::chargeLog(sprintf(
                    '作品数据流水失败已退费：场景=%s 用户=%d 单号=%s 退费=%s 原因=%s',
                    $scene,
                    $userId,
                    $ref,
                    (string)$points,
                    $e->getMessage()
                ));
            } catch (\Throwable $refundEx) {
                self::chargeLog(sprintf(
                    '作品数据流水失败且退费失败：场景=%s 用户=%d 单号=%s 流水原因=%s 退费原因=%s',
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
            '作品数据扣费成功：场景=%s 用户=%d 单号=%s 扣费=%s',
            $scene,
            $userId,
            $ref,
            (string)$points
        ));
        if (!empty(self::$testHooks['interceptTokens']) && $ref !== '') {
            self::$testHooks['settledRefs'][] = $ref;
        }
        return $points;
    }

    public static function hasSettled(int $userId, int $changeType, string $ref): bool
    {
        if (!empty(self::$testHooks['skipSettled'])) {
            return false;
        }
        if ($ref !== '' && in_array($ref, self::$testHooks['settledRefs'] ?? [], true)) {
            return true;
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

    /**
     * @param array<string, mixed> $extra
     */
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
        WorkDataLog::write($msg);
    }
}
