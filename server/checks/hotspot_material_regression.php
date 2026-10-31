<?php
/**
 * 下发素材链路回归（真实口播口径 + 多轮补齐）：app 初始化 + 测试库只读（不写库），
 * AI 找素材用测试钩子拦截，不调真实 AI、不产生抓取费用。
 * 运行：php checks/hotspot_material_regression.php
 */
namespace {
    $root = dirname(__DIR__);
    $autoload = $root . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        fwrite(STDERR, "SKIP vendor/autoload.php 不存在\n");
        exit(0);
    }
    require $autoload;

    use app\common\service\hotspot\VideoService;

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

    $app = new \think\App();
    $app->initialize();

    // 钩子返回 20 条 5 秒视频候选，图片不补，全部按素材库行格式返回
    $videoRow = static function (string $tag, int $i, int $duration = 5): array {
        return [
            'id' => crc32($tag . '_' . $i),
            'file_url' => 'hotspot/hook_' . $tag . '_' . $i . '.mp4',
            'material_type' => 1,
            'duration' => $duration,
        ];
    };
    /** @var array<int, array{needV:int,needI:int}> $calls */
    $calls = [];

    // 每轮返回不同的 file_url，模拟「还有新素材可补」
    $uniqHook = static function ($script, $needV, $needI) use (&$calls, $videoRow): array {
        $round = count($calls);
        $calls[] = ['needV' => (int)$needV, 'needI' => (int)$needI];
        $rows = [];
        for ($i = 0; $i < 20; $i++) {
            $rows[] = $videoRow('r' . $round, $i);
        }
        return [$rows, []];
    };

    // 每轮返回同一批 file_url，模拟「候选全重复」，用于验证多轮会及时停手
    $dupHook = static function ($script, $needV, $needI) use (&$calls, $videoRow): array {
        $calls[] = ['needV' => (int)$needV, 'needI' => (int)$needI];
        $rows = [];
        for ($i = 0; $i < 2; $i++) {
            $rows[] = $videoRow('dup', $i);
        }
        return [$rows, []];
    };

    $optAi = static fn () => [
        'material_mode' => 'ai',
        'persona_mode' => 2,
        'video_type' => 'clips',
        'platform' => 'douyin',
        'materials' => [],
    ];

    // —— TC-01 长文案：首轮按「素材混剪目标 96 秒」折算，缺口 80 秒 → 16 条 + 8 图 —— //
    $calls = [];
    VideoService::setTestHooks(['getAiMaterials' => $uniqHook]);
    try {
        $script = str_repeat('面', 400); // 400 字 → 真实口播 96 秒（混剪目标 96 秒）
        $selected = VideoService::resolveMaterialsForTest(7, 0, $optAi(), $script);
        $check(
            $calls !== [] && (int)$calls[0]['needV'] === 16 && (int)$calls[0]['needI'] === 8,
            '长文案首轮按 96 秒目标折算 16 条视频 + 8 张图片',
        );
        $videoSec = 0.0;
        $videos = 0;
        foreach ($selected as $item) {
            if (($item['type'] ?? '') === 'video') {
                $videoSec += (float)($item['duration'] ?? 0);
                $videos++;
            }
        }
        // 首轮 16×5=80 秒，缺口 16 秒由第二轮补 4 条，最终约 100 秒（允许单条素材的粒度误差）
        $check(count($calls) === 2, '首轮未补足时多轮补齐生效（共 2 轮）');
        $check($videos === 20, "两轮合计 20 条视频（实际={$videos}）");
        $check($videoSec >= 96.0 && $videoSec <= 101.0, "素材可承载时长覆盖 96 秒且不超过 1 条误差（实际=" . round($videoSec, 1) . "秒）");
    } catch (\Throwable $e) {
        $check(false, 'TC-01 异常：' . $e->getMessage());
    } finally {
        VideoService::clearTestHooks();
    }

    // —— TC-02 短文案：按最低兜底展开，混剪目标 15 秒 → 3 视频 + 2 图片 —— //
    $calls = [];
    VideoService::setTestHooks(['getAiMaterials' => $uniqHook]);
    try {
        $script = str_repeat('面', 63); // 63 字 → 口播 15 秒（夹最低 15 秒）
        $selected = VideoService::resolveMaterialsForTest(7, 0, $optAi(), $script);
        $videos = 0;
        foreach ($selected as $item) {
            if (($item['type'] ?? '') === 'video') {
                $videos++;
            }
        }
        $check(
            $calls !== [] && (int)$calls[0]['needV'] === 3,
            '短文案 15 秒目标 → 3 条视频（扣 2 图占位 4 秒后缺口 11 秒）',
        );
        $check((int)$calls[0]['needI'] === 2, '短文案图片按 15×15%/2 折算兜底 2 张');
        $check($videos === 3, "短文案下发素材 3 条视频（实际={$videos}）");
        $check(count($calls) === 1, '首轮已覆盖目标不触发多轮');
    } catch (\Throwable $e) {
        $check(false, 'TC-02 异常：' . $e->getMessage());
    } finally {
        VideoService::clearTestHooks();
    }

    // —— TC-03 开关关闭 + 候选全重复：首轮恢复最低 2 条，多轮拉一次发现重复即停 —— //
    $calls = [];
    VideoService::setTestHooks(['getAiMaterials' => $dupHook]);
    config(['ai_round1_duration_enabled' => false], 'hotspot');
    try {
        $script = str_repeat('面', 400);
        $selected = VideoService::resolveMaterialsForTest(7, 0, $optAi(), $script);
        $check(
            $calls !== [] && (int)$calls[0]['needV'] === 2,
            '开关关闭恢复首轮最低兜底 2 条',
        );
        $check(count($calls) === 2, '候选全重复时只多拉一轮即停（不再 3 轮空抓）');
        $check(count($selected) === 2, "全重复候选不入库，下发素材仍 2 条（实际=" . count($selected) . "）");
    } catch (\Throwable $e) {
        $check(false, 'TC-03 异常：' . $e->getMessage());
    } finally {
        config(['ai_round1_duration_enabled' => true], 'hotspot');
        VideoService::clearTestHooks();
    }

    // —— TC-05 长素材：总量允许单条素材粒度超出上限，不再按秒裁穿 —— //
    $longHook = static function ($script, $needV, $needI) use (&$calls, $videoRow): array {
        $round = count($calls);
        $calls[] = ['needV' => (int)$needV, 'needI' => (int)$needI];
        $rows = [];
        for ($i = 0; $i < 20; $i++) {
            $rows[] = $videoRow('long' . $round, $i, 20);
        }
        return [$rows, []];
    };
    $calls = [];
    VideoService::setTestHooks(['getAiMaterials' => $longHook]);
    try {
        $script = str_repeat('面', 400); // 混剪目标 96 秒
        $selected = VideoService::resolveMaterialsForTest(7, 0, $optAi(), $script);
        $totalSec = 0.0;
        foreach ($selected as $item) {
            $totalSec += ($item['type'] ?? '') === 'image' ? 2.0 : (float)($item['duration'] ?? 0);
        }
        $check(
            $totalSec >= 96.0 && $totalSec <= 116.0,
            "20 秒长素材下总量落在 96 秒 + 1 条误差内（实际=" . round($totalSec, 1) . "秒）"
        );
    } catch (\Throwable $e) {
        $check(false, 'TC-05 异常：' . $e->getMessage());
    } finally {
        VideoService::clearTestHooks();
    }

    // —— TC-04 纯人设模式：AI 找素材不出手（不产生抓取费用），素材不足自动警告照常出片 —— //
    $calls = [];
    VideoService::setTestHooks(['getAiMaterials' => $uniqHook]);
    try {
        $selected = VideoService::resolveMaterialsForTest(7, 987654321, [
            'material_mode' => 'persona',
            'persona_mode' => 1,
            'video_type' => 'clips',
            'platform' => 'douyin',
            'materials' => [],
        ], str_repeat('面', 400));
        $check($calls === [], '纯人设模式不调 AI 找素材');
        $check($selected === [], '无素材纯人设任务素材为空（由收口闸门出警告）');
    } catch (\Throwable $e) {
        $check(false, 'TC-04 异常：' . $e->getMessage());
    } finally {
        VideoService::clearTestHooks();
    }

    echo sprintf("合计：通过=%d 失败=%d\n", $passed, $failed);
    exit($failed > 0 ? 1 : 0);
}
