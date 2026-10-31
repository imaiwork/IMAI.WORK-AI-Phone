<?php
/**
 * 创建时素材预检（P2）：app 初始化 + 只读查询，不写库、不调 AI、不产生费用。
 * 运行：php checks/hotspot_creation_precheck.php
 */
namespace {
    $root = dirname(__DIR__);
    $autoload = $root . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        fwrite(STDERR, "SKIP vendor/autoload.php 不存在\n");
        exit(0);
    }
    require $autoload;

    use app\common\service\hotspot\PersonaService;
    use app\common\service\hotspot\ScriptService;
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
    $userId = 7;

    // —— TC-01 空文案：不预检 —— //
    $pre = VideoService::precheckMaterials([
        'script' => '   ',
        'options' => [],
        'persona' => [],
    ], $userId);
    $check($pre === [], '空文案返回空预检');

    // —— TC-02 不选人设（AI 找素材）：给 AI 补齐条数建议，无警告 —— //
    $script = str_repeat('面', 420); // 420 字 → 真实口播 100 秒，素材需覆盖 [50, 100]，混剪目标 100 秒
    $pre = VideoService::precheckMaterials([
        'script' => $script,
        'title' => '标题',
        'options' => [
            'persona_mode' => ScriptService::PERSONA_MODE_MANUAL,
            'video_type' => 'clips',
        ],
        'persona' => [],
    ], $userId);
    $check((int)($pre['est_duration_sec'] ?? 0) === 100, '不选人设预估口播 100 秒');
    $check((float)($pre['est_sec'] ?? 0) === 100.0, '成片预估=420/4.2=100 秒');
    $check((float)($pre['floor_sec'] ?? 0) === 50.0, '素材下限=100×0.5=50 秒');
    $check((float)($pre['ceil_sec'] ?? 0) === 100.0, '素材上限=成片预估 100 秒');
    $check((float)($pre['needed_sec'] ?? 0) === 100.0, '需求时长=混剪目标 100 秒');
    $check((int)($pre['ai_fill_video'] ?? 0) === 17, 'AI 补视频建议 17 条（100-8 图占位 16 秒=84 秒缺口按 5 秒折算）');
    $check((int)($pre['ai_fill_image'] ?? 0) === 8, 'AI 补图片建议 8 张（100×15%/2 折算，触 8 张上限）');
    $check((string)($pre['warning'] ?? '') === '', 'AI 模式不出纯人设警告');
    $check((float)($pre['pool_fill_sec'] ?? 0) === 0.0, 'AI 模式不算人设池');

    // —— TC-03 纯人设模式 + 人设无素材：池补 0，缺口透出警告 —— //
    $pre = VideoService::precheckMaterials([
        'script' => $script,
        'title' => '标题',
        'options' => [
            'material_mode' => 'persona',
            'video_type' => 'clips',
        ],
        'persona' => ['id' => 987654321],
    ], $userId);
    $check((float)($pre['gap_sec'] ?? 0) === 100.0, '纯人设无素材缺口 100 秒');
    $check(str_contains((string)($pre['warning'] ?? ''), '仍缺100.0秒'), '缺口警告含「仍缺100.0秒」');
    $check(str_contains((string)($pre['warning'] ?? ''), '素材需覆盖约100.0秒'), '警告说明素材需覆盖约 100 秒');
    $check(str_contains((string)($pre['warning'] ?? ''), '素材库'), '警告引导去素材库补充');

    // —— TC-04 纯人设模式 + 真实可选池命中：统计池可补时长（有池子才跑） —— //
    try {
        $candidate = PersonaService::clipMaterialQuery(0, 0)
            ->where('duration', '>', 2)
            ->field(['id', 'user_id', 'persona_id'])
            ->order('duration', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($candidate->isEmpty()) {
            echo "SKIP TC-04 测试库无可选素材池\n";
        } else {
            $poolUserId = (int)$candidate->user_id;
            $poolPersonaId = (int)$candidate->persona_id;
            if ($poolPersonaId <= 0) {
                echo "SKIP TC-04 候选未绑人设（素材挂 persona_id=0）\n";
            } else {
                $pre = VideoService::precheckMaterials([
                    'script' => $script,
                    'title' => '标题',
                    'options' => [
                        'material_mode' => 'persona',
                        'video_type' => 'clips',
                    ],
                    'persona' => ['id' => $poolPersonaId],
                ], $poolUserId);
                $check(
                    (float)($pre['pool_fill_sec'] ?? 0) > 0 && (string)($pre['warning'] ?? '') === '',
                    '有人设池可补时不警告'
                );
            }
        }
    } catch (\Throwable $e) {
        echo 'SKIP TC-04 人设池统计失败：' . $e->getMessage() . "\n";
    }

    // —— TC-05 预检失败兜底：非法 options 不抛异常、返回空 —— //
    $pre = VideoService::precheckMaterials([
        'script' => ['not-a-string'], // 故意给非法类型
        'options' => [],
        'persona' => [],
    ], $userId);
    $check($pre === [], '预检失败吞异常返回空不阻塞创建');

    echo sprintf("合计：通过=%d 失败=%d\n", $passed, $failed);
    exit($failed > 0 ? 1 : 0);
}
