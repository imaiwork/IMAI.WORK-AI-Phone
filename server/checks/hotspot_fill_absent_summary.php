<?php
/**
 * 热点生成：摘要「暂无」换成话题，占用行记下同一份 summary。
 * 运行：php checks/hotspot_fill_absent_summary.php
 * 不调用方舟。与 tests/hotspot/fill_absent_summary.php 同内容（tests 目录被 gitignore）。
 */
namespace {
    $root = dirname(__DIR__);
    $autoload = $root . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        fwrite(STDERR, "SKIP vendor/autoload.php 不存在\n");
        exit(0);
    }
    require $autoload;

    $failed = 0;
    $passed = 0;
    $check = static function (bool $ok, string $msg) use (&$failed, &$passed): void {
        if ($ok) {
            $passed++;
            echo "PASS {$msg}\n";
            return;
        }
        $failed++;
        echo "FAIL {$msg}\n";
    };

    $logic = \app\api\logic\videoSynthesis\CopywritingAiGenerationLogic::class;
    $topic = '4.8级地震';
    $brief = "1. 事件是什么：暂无\n2. 关键数据或事实：暂无\n3. 最新进展：已有官方通报\n4. 舆论讨论点：暂无\n5. 容易被误传的信息：暂无";

    $filled = $logic::fillAbsentSummaryForTest($brief, $topic);
    $check(str_contains($filled, '1. 事件是什么：' . $topic), '暂无换成话题且保留小标题');
    $check(!str_contains($filled, '暂无'), '摘要里不再留下暂无');
    $check(str_contains($filled, '3. 最新进展：已有官方通报'), '已有内容不被替换');
    $check($logic::fillAbsentSummaryForTest($brief, '  ' . $topic . ' ') === $filled, '话题首尾空白不影响替换');
    $check($logic::fillAbsentSummaryForTest('只有已核实事实', $topic) === '只有已核实事实', '没有暂无时原文不变');
    $check($logic::fillAbsentSummaryForTest($brief, '') === $brief, '话题为空时不替换');
    $check($logic::fillAbsentSummaryForTest('', $topic) === '', '空摘要保持为空');
    $check($logic::fillAbsentSummaryForTest('暂无', $topic) === $topic, '整段暂无换成话题');

    $row = new class($topic, $filled) {
        public string $title = '';
        public string $topic;
        public string $summary;
        public function __construct(string $topic, string $summary)
        {
            $this->topic = $topic;
            $this->summary = $summary;
        }
    };
    $log = new class {
        public string $title = '';
        public string $script = '';
        public string $summary = '未写入';
        public int $saves = 0;
        public function save(): bool
        {
            $this->saves++;
            return true;
        }
    };
    $logic::writeHotspotUseSnapshotForTest($log, $row, '生成标题', '口播正文');
    $check($log->summary === $filled, '占用行摘要与送入口播的文本一致');
    $check($log->script === '口播正文' && $log->title === '生成标题' && $log->saves === 1, '占用行同时写入标题和口播');

    $emptyRow = new class($topic) {
        public string $title = '';
        public string $topic;
        public string $summary = '';
        public function __construct(string $topic)
        {
            $this->topic = $topic;
        }
    };
    $emptyLog = new class {
        public string $title = '';
        public string $script = '';
        public ?string $summary = null;
        public function save(): bool
        {
            return true;
        }
    };
    $logic::writeHotspotUseSnapshotForTest($emptyLog, $emptyRow, '', '口播正文');
    $check($emptyLog->summary === '', '没有摘要时占用行写空串');
    $check($emptyLog->title === $topic, '生成标题为空时占用标题回落到话题');

    $appReady = true;
    try {
        (new \think\App())->initialize();
    } catch (\Throwable $e) {
        $appReady = false;
        echo "FAIL 应用初始化：" . $e->getMessage() . "\n";
        $failed++;
    }

    if ($appReady) {
        \app\common\service\hotspot\HotspotLog::setTestCapture(true);
        $makePool = static function () use ($topic, $brief): \app\common\model\hotspot\HotspotPool {
            $pool = new class extends \app\common\model\hotspot\HotspotPool {
                public int $saveCount = 0;

                public function save(array|object $data = [], ?string $sequence = null): bool
                {
                    $this->saveCount++;
                    return true;
                }
            };
            $pool->id = 9;
            $pool->topic = $topic;
            $pool->platform = 'douyin';
            $pool->script = '';
            $pool->summary = $brief;
            $pool->analysis_id = 0;
            $pool->analysis_json = [];
            $pool->core_points_json = [['label' => '要点', 'detail' => '暂无']];
            $pool->goal = 'traffic';
            $pool->direction = '观点输出';
            return $pool;
        };

        $seen = '';
        $logic::setHotspotTestHooks([
            'scriptResult' => static function ($pool) use (&$seen): array {
                $seen = (string)($pool->summary ?? '');
                return ['script' => '口播正文点到' . (string)$pool->topic, 'title' => '标题'];
            },
        ]);
        try {
            $pool = $makePool();
            $pointsBefore = $pool->core_points_json;
            $config = (object)['visual_material_source' => 2];
            $logic::generateHotspotScriptForTest($pool, ['id' => 0], $config, 1, 1);
            $check($seen === $filled, '现写口播拿到的是替换后的摘要');
            $check((string)$pool->summary === $filled && $pool->saveCount === 1, '替换后的摘要写回热点库');
            $check($pool->core_points_json === $pointsBefore, '核心要点里的暂无不替换');

            $plain = '只有已核实事实';
            $plainPool = $makePool();
            $plainPool->summary = $plain;
            $seenPlain = '';
            $logic::setHotspotTestHooks([
                'scriptResult' => static function ($pool) use (&$seenPlain): array {
                    $seenPlain = (string)($pool->summary ?? '');
                    return ['script' => '口播正文', 'title' => '标题'];
                },
            ]);
            $logic::generateHotspotScriptForTest($plainPool, ['id' => 0], $config, 1, 1);
            $check($seenPlain === $plain && $plainPool->saveCount === 0, '已有摘要不含暂无时不改写、不保存');

            $fresh = $makePool();
            $fresh->summary = '';
            $freshPoints = [['label' => '要点', 'detail' => '暂无']];
            $seenFresh = '';
            $logic::setHotspotTestHooks([
                'research' => [
                    'summary' => $brief,
                    'core_points' => $freshPoints,
                ],
                'scriptResult' => static function ($pool) use (&$seenFresh): array {
                    $seenFresh = (string)($pool->summary ?? '');
                    return ['script' => '核清后的口播', 'title' => '标题'];
                },
            ]);
            $logic::generateHotspotScriptForTest($fresh, ['id' => 0], $config, 1, 1);
            $check($seenFresh === $filled && (string)$fresh->summary === $filled, '仅标题核清返回暂无后先替换再送入口播');
            $check($fresh->saveCount === 1, '核清写回只保存一次');
            $storedPoints = $fresh->core_points_json;
            $check(
                is_array($storedPoints)
                && ($storedPoints[0]['detail'] ?? '') === '暂无',
                '核清写回的核心要点保持原样'
            );
        } catch (\Throwable $e) {
            $failed++;
            echo "FAIL 现写摘要替换：" . $e->getMessage() . "\n";
        } finally {
            $logic::clearHotspotTestHooks();
            \app\common\service\hotspot\HotspotLog::setTestCapture(false);
        }
    }

    echo "合计 {$passed} 通过，{$failed} 失败\n";
    exit($failed > 0 ? 1 : 0);
}
