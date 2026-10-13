<?php
// 隔离数据库、上游和算力账户，执行真实取数/计费/设置逻辑：php checks/work_data_sync.php
namespace think\facade {
    class Db { public static function transaction($fn) { return $fn(); } }
}
namespace app\common\model\aiPersona {
    class AiPersona extends \ArrayObject {
        public const STATUS_ENABLED = 1;
        public static array $rows = [];
        public function __construct(array $data) { parent::__construct($data, self::ARRAY_AS_PROPS); }
        public static function where($key, $value) { return (new Query())->where($key, $value); }
        public function save($data) { foreach ($data as $key => $value) $this[$key] = $value; }
    }
    class Query {
        private array $filters = [];
        public function where($key, $value) { $this->filters[$key] = $value; return $this; }
        public function whereNull($key) { return $this->where($key, null); }
        public function lock($value) { return $this; }
        public function find() {
            foreach (AiPersona::$rows as $row) {
                foreach ($this->filters as $key => $value) {
                    if ($row[$key] !== $value) continue 2;
                }
                return $row;
            }
            return null;
        }
    }
}
namespace app\common\service {
    class ToolsService {
        public static int $calls = 0;
        public static $duringFetch = null;
        public static bool $fail = false;
        public static function TikHub() { return new self(); }
        public function fetchWorkDataAnalytics($platform, $account) {
            self::$calls++;
            if (self::$duringFetch) (self::$duringFetch)();
            // 与生产一致：中台取数失败抛 HotspotUpstreamException，由 WorkDataFetchService 转成业务异常
            if (self::$fail) throw new \app\common\service\hotspot\HotspotUpstreamException('上游接口限流');
            return ['platform' => $platform, 'account' => ['uniqueId' => $account], 'items' => [], 'windows' => []];
        }
    }
}
namespace {
    use app\common\model\aiPersona\AiPersona;
    use app\common\service\ToolsService;
    use app\common\service\workData\{PersonaBindService, WorkDataCache, WorkDataChargeService, WorkDataFetchService, WorkDataLog};
    use app\api\logic\workData\WorkDataLogic;
    function config($key, $default = null) { return $default; }
    spl_autoload_register(static function ($class) {
        if (str_starts_with($class, 'app\\')) require dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    });
    function check($condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
        echo "PASS $message\n";
    }
    function resetCase(int $enabled = 0): AiPersona {
        $persona = new AiPersona(['id' => 1, 'user_id' => 7, 'delete_time' => null, 'status' => 1, 'work_data_auto_sync' => $enabled]);
        AiPersona::$rows = [$persona];
        WorkDataCache::useMemoryForTest();
        WorkDataLog::setTestCapture(true);
        WorkDataChargeService::setTestHooks(['price' => 50, 'skipSettled' => true, 'skipLock' => true, 'interceptTokens' => true, 'silentLog' => true]);
        ToolsService::$calls = 0;
        ToolsService::$duringFetch = null;
        ToolsService::$fail = false;
        PersonaBindService::setTestResolver(static fn() => ['persona' => $persona, 'account' => 'demo']);
        return $persona;
    }
    function charges(): int { return count(WorkDataChargeService::testHookState()['tokenOps'] ?? []); }
    resetCase();
    $read = WorkDataFetchService::fetch(7, 1, 'douyin');
    check(($read['hasData'] ?? null) === false && ToolsService::$calls === 0 && charges() === 0, '关闭且无缓存时浏览不取数、不扣费');
    WorkDataFetchService::fetch(7, 1, 'douyin', false, null, true);
    check(ToolsService::$calls === 0 && charges() === 0, '关闭时后台调度不取数、不扣费');
    WorkDataFetchService::fetch(7, 1, 'douyin', true);
    check(ToolsService::$calls === 1 && charges() === 1, '关闭时手动刷新仍成功取数并扣费');
    $read = WorkDataFetchService::fetch(7, 1, 'douyin');
    check($read['cached'] && charges() === 1, '浏览已有数据不扣费');
    WorkDataFetchService::fetch(7, 1, 'douyin', true);
    check(ToolsService::$calls === 1 && charges() === 1, '刷新节流命中不重复取数和扣费');
    $memory = new \ReflectionProperty(WorkDataCache::class, 'memory');
    $memory->setAccessible(true);
    $cache = $memory->getValue();
    unset($cache[WorkDataCache::payloadKey('douyin', 'demo')], $cache[WorkDataCache::refreshKey('douyin', 'demo')]);
    $memory->setValue(null, $cache);
    check(WorkDataFetchService::fetch(7, 1, 'douyin')['cached'] && charges() === 1, '缓存过期后仍可查看上次快照');
    WorkDataFetchService::fetch(7, 1, 'douyin', true);
    $refs = WorkDataChargeService::testHookState()['settledRefs'];
    check(charges() === 2 && count(array_unique($refs)) === 2, '同一小时第二次实际刷新使用独立扣费单号');
    resetCase(1);
    WorkDataFetchService::fetch(7, 1, 'xhs');
    check(ToolsService::$calls === 0 && charges() === 0, '开启时普通浏览也不隐性扣费');
    WorkDataFetchService::fetch(7, 1, 'xhs', false, null, true);
    check(ToolsService::$calls === 1 && charges() === 1, '开启时后台同步小红书并扣费');
    WorkDataFetchService::fetch(7, 1, 'xhs', false, null, true);
    check(ToolsService::$calls === 1 && charges() === 1, '未到期的数据不重复定时同步');
    $persona = resetCase(1);
    ToolsService::$duringFetch = static function () use ($persona) { $persona['work_data_auto_sync'] = 0; };
    WorkDataFetchService::fetch(7, 1, 'douyin', false, null, true);
    check(ToolsService::$calls === 1 && charges() === 0, '同步在途关闭后不结算扣费');
    $persona = resetCase(1);
    $persona['status'] = 0;
    WorkDataFetchService::fetch(7, 1, 'douyin', false, null, true);
    check(ToolsService::$calls === 0 && charges() === 0, '冻结人设不自动同步');
    resetCase();
    ToolsService::$fail = true;
    try { WorkDataFetchService::fetch(7, 1, 'douyin', true); } catch (\RuntimeException $e) {}
    check(ToolsService::$calls === 1 && charges() === 0, '上游失败不扣费');
    // 定时同步是每分钟一次的 cron：失败必须记负缓存退避，否则每分钟重查一次，
    // 中台按次向站长计费而取数失败时用户一分算力没扣，这笔钱全由站长垫。
    $persona = resetCase(1);
    ToolsService::$fail = true;
    try { WorkDataFetchService::fetch(7, 1, 'xhs', false, null, true); } catch (\Throwable $e) {}
    check(ToolsService::$calls === 1 && charges() === 0, '定时同步上游失败不扣用户算力');
    try { WorkDataFetchService::fetch(7, 1, 'xhs', false, null, true); } catch (\Throwable $e) {}
    check(ToolsService::$calls === 1 && charges() === 0, '失败退避窗口内不重复请求中台');
    ToolsService::$fail = false;
    WorkDataFetchService::fetch(7, 1, 'xhs', true);
    check(ToolsService::$calls === 2 && charges() === 1, '手动刷新绕过失败退避可立即重试');
    $persona = resetCase();
    check(WorkDataLogic::syncSettings(['persona_id' => 1, 'auto_sync_enabled' => 1], 8) === false && $persona['work_data_auto_sync'] === 0, '不能修改其他用户的人设开关');
    check(WorkDataLogic::syncSettings(['persona_id' => 1, 'auto_sync_enabled' => 1], 7)['auto_sync_enabled'] === true, '本人开关保存与回显一致');
    $persona['delete_time'] = 123;
    check(WorkDataLogic::syncSettings(['persona_id' => 1, 'auto_sync_enabled' => 0], 7) === false, '已删除人设不能设置');
    echo "All work data sync checks passed.\n";
}
