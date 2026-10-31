<?php
/**
 * 素材秒数锚点边界冒烟（2026-10-29 素材锚点改真实口播口径）
 * 覆盖：materialAnchorSeconds 空/极值/语速/下发时长基准、imageTargetCount 折算、
 * aiRound1VideoTarget 零缺口/熔断/已选溢出、trimMaterialsToTargets 按秒累加、
 * trimAiMaterialsToCeil 保底不裁穿、resolveAnchorMaterialTargets 数字人/素材混剪目标。
 * 全部走公开/反射调用，不落库、不调 AI、不产生费用。
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use app\common\service\hotspot\ScriptService;
use app\common\service\hotspot\VideoService;
use app\api\logic\videoImitation\TaskLogic;

$pass = 0;
$fail = 0;
$check = static function (bool $ok, string $name) use (&$pass, &$fail): void {
    if ($ok) {
        $pass++;
        echo "PASS {$name}\n";
        return;
    }
    $fail++;
    echo "FAIL {$name}\n";
};

$refTrim = new ReflectionMethod(VideoService::class, 'trimAiMaterialsToCeil');
$refTrim->setAccessible(true);
$refAnchorTargets = new ReflectionMethod(TaskLogic::class, 'resolveAnchorMaterialTargets');
$refAnchorTargets->setAccessible(true);

$videoRow = static fn(int $i, int $dur): array => ['id' => $i + 1, 'type' => 'video', 'url' => "v{$i}.mp4", 'fileUrl' => "v{$i}.mp4", 'duration' => $dur];
$imageRow = static fn(int $i): array => ['id' => 1000 + $i, 'type' => 'image', 'url' => "i{$i}.jpg", 'fileUrl' => "i{$i}.jpg"];

// ═══ 一、materialAnchorSeconds ═══
$a = ScriptService::materialAnchorSeconds('');
$check($a['est_sec'] === 0.0 && $a['floor_sec'] === 0.0 && $a['ceil_sec'] === 0.0, '空文案锚点全 0');

$a = ScriptService::materialAnchorSeconds("   \n\t  ");
$check($a['est_sec'] === 0.0, '纯空白文案锚点全 0（wordCount 已去空白）');

$a = ScriptService::materialAnchorSeconds('字');
$check($a['est_sec'] === 15.0 && $a['floor_sec'] === 7.5 && $a['ceil_sec'] === 15.0, '单字口播按最低 15 秒夹取');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400));
$check($a['est_sec'] === 96.0 && $a['floor_sec'] === 48.0 && $a['ceil_sec'] === 96.0, '400 字口播 96/48/96');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 0.0);
$check($a['est_sec'] === 96.0, '语速 0 按 1.0 兜底');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400), -2.0);
$check($a['est_sec'] === 96.0, '语速负数按 1.0 兜底');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 2.0);
$check($a['est_sec'] === 48.0 && $a['floor_sec'] === 24.0, '语速 2 倍口播减半 48/24');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 100000));
$check($a['est_sec'] === 180.0 && $a['floor_sec'] === 90.0, '超长文案口播按上限夹 180 秒');

$a = ScriptService::materialAnchorSeconds("hello world 你好"); // 去空白后 12 字符
$check($a['est_sec'] === 15.0, '中英混排按去空白字符数折算并夹最低 15 秒');

// 爆款复刻：下发时长（字数/3）比口播更长时取较大值作上限
$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 1.0, 133.0);
$check($a['est_sec'] === 133.0 && $a['floor_sec'] === 66.5 && $a['ceil_sec'] === 133.0, '下发 133 秒基准 133/66.5/133');

$a = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 1.0, 20.0);
$check($a['est_sec'] === 96.0, '下发时长小于口播时仍以口播为准');

// ═══ 二、imageTargetCount ═══
$check(ScriptService::imageTargetCount(0) === 2, '目标 0 秒图片兜底 2 张');
$check(ScriptService::imageTargetCount(15) === 2, '目标 15 秒图片 2 张');
$check(ScriptService::imageTargetCount(20) === 2, '目标 20 秒图片 2 张（承载 3 秒）');
foreach ([40 => 3, 96 => 8, 180 => 8] as $sec => $expect) {
    $check(ScriptService::imageTargetCount((float)$sec) === $expect, "目标 {$sec} 秒图片 {$expect} 张");
}

// ═══ 三、aiRound1VideoTarget ═══
$v = VideoService::aiRound1VideoTarget(0.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 2);
$check($v === 2, '零缺口仍兜底 2 条');

$v = VideoService::aiRound1VideoTarget(-5.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 2);
$check($v === 2, '负缺口不炸裂仍兜底 2 条');

$v = VideoService::aiRound1VideoTarget(40.0, ['video_sec' => 200.0, 'image_sec' => 100.0], 20, 5.0, 2, 2);
$check($v === 20, '已选素材已超目标时不追加（目标=已选 20 条）');

$v = VideoService::aiRound1VideoTarget(100000.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 2);
$check($v === ScriptService::MATERIAL_ANCHOR_VIDEO_CAP && $v === 50, '极端缺口熔断 50 条');

$v = VideoService::aiRound1VideoTarget(40.0, ['video_sec' => 0, 'image_sec' => 0], 0, -1.0, 2, 2);
$check($v === 8, '均条长负数按 5 秒兜底折算 8 条');

$v = VideoService::aiRound1VideoTarget(40.0, [], 0, 5.0, 2, 2);
$check($v === 8, 'summary 缺键按 0 秒处理不告警');

// ═══ 四、trimMaterialsToTargets 按秒累加 ═══
$out = VideoService::trimMaterialsToTargets(
    [$videoRow(1, 10), $imageRow(1), $videoRow(2, 20), $imageRow(2), $imageRow(3)],
    3,
    3,
    30.0
);
$check(count(array_filter($out, fn($m) => $m['type'] === 'video')) === 2, '按秒累加到 30 秒停（10+20 秒 + 2 图=34）');
$check(count(array_filter($out, fn($m) => $m['type'] === 'image')) === 3, '图片先按目标张数取满 3 张');

$out = VideoService::trimMaterialsToTargets([$videoRow(1, 10), $imageRow(1), $videoRow(2, 20)], 1, 1, 0.0);
$check(
    count($out) === 2 && $out[0]['type'] === 'video' && $out[1]['type'] === 'image',
    '目标秒数 0 时退化为按条数收割'
);

// ═══ 五、trimAiMaterialsToCeil ═══
$sel = [$videoRow(1, 10)];
$ai = [$videoRow(2, 10), $videoRow(3, 10)];
$out = $refTrim->invoke(null, $sel, $ai, 0.0);
$check(count($out) === 2, 'ceil=0 不裁剪（闸门关闭态）');

$out = $refTrim->invoke(null, $sel, [], 5.0);
$check($out === [], 'AI 为空不裁剪');

$out = $refTrim->invoke(null, [], [$videoRow(1, 30), $videoRow(2, 30), $videoRow(3, 30)], 80.0);
$check(count($out) === 2, '3×30 秒裁到 ≤80 秒留 2 条（且触底 2 条不再裁）');

// 2 视频触底后超上限也不裁穿；图片同理保底 2 张
$ai = [$videoRow(1, 50), $videoRow(2, 50), $imageRow(1), $imageRow(2), $imageRow(3)];
$out = $refTrim->invoke(null, [], $ai, 40.0);
$vids = count(array_filter($out, fn($m) => $m['type'] === 'video'));
$imgs = count(array_filter($out, fn($m) => $m['type'] === 'image'));
$check($vids === 2 && $imgs === 2, '触底保底 2 视频 + 2 图片不裁穿');

// 用户已选素材永不进裁剪范围
$sel = [$videoRow(1, 100), $videoRow(2, 100)];
$ai = [$videoRow(3, 10)];
$out = $refTrim->invoke(null, $sel, $ai, 50.0);
$check(count($out) === 0, '用户已选 200 秒超上限时裁光 AI 部分、已选保留');

// 尾部优先：先裁列表末尾的 AI 素材（$videoRow 的 id = $i + 1）
$sel = [];
$ai = [$videoRow(1, 10), $videoRow(2, 20), $videoRow(3, 30), $videoRow(4, 40)];
$out = $refTrim->invoke(null, $sel, $ai, 70.0);
$ids = array_column($out, 'id');
$check($ids === [2, 3, 4], '从尾部裁掉 40 秒那条，保留前 3 条共 60 秒');

// ═══ 六、dedupeMaterialsByUrl ═══
$out = VideoService::dedupeMaterialsByUrl(
    [$videoRow(1, 10), $videoRow(1, 10), $imageRow(1), $imageRow(1)],
    [$videoRow(9, 10)]
);
$check(count($out) === 2, '同 URL 只留一条（重复视频+重复图片各去一）');

$out = VideoService::dedupeMaterialsByUrl([$videoRow(9, 10)], [$videoRow(9, 10)]);
$check($out === [], '与已选素材同 URL 的候选被剔除');

// ═══ 七、resolveAnchorMaterialTargets（爆款复刻） ═══
$anchor = ScriptService::materialAnchorSeconds(str_repeat('字', 400)); // 96/48/96
[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor, [], [], 0);
$check($tv === 8 && $ti === 4, "数字人 400 字 → 8 视频 4 图片（实际={$tv}/{$ti}）");

[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor, [], [], 1);
$check($tv === 16 && $ti === 8, "素材混剪 400 字 → 16 视频 8 图片（实际={$tv}/{$ti}）");

[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor, array_fill(0, 4, ['duration' => 30]), [], 1);
$check($tv === 4 && $ti === 8, "人设 4×30 秒已超目标 → 目标=已选 4 条不追加（实际={$tv}）");

[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor, array_fill(0, 2, ['duration' => 5]), array_fill(0, 3, []), 1);
$check($ti === 8, '人设已选 3 图 → 图片目标仍按目标秒数折算 8 张');
$check($tv === 16, "2×5 秒人设视频 + 3 图 → 缺口 70 秒按 5 秒均条长补 14 条共 16 条（实际={$tv}）");

// 本地素材缺 duration 字段：按 0 秒计、均条长兜底 5 秒（已选 2 条仍计入目标条数）
[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor, [['url' => 'a.mp4'], ['url' => 'b.mp4']], [], 1);
$check($tv === 18 && $ti === 8, "人设视频缺 duration 按 0 秒计、缺口 80 秒补 16 条共 18 条（实际={$tv}/{$ti}）");

$anchor0 = ScriptService::materialAnchorSeconds('');
[$tv, $ti] = $refAnchorTargets->invoke(null, $anchor0, [], [], 1);
$check($tv === 2 && $ti === 2, '空文案锚点仍兜底 2 视频 2 图片（调用方已先拦 floor=0）');

// 超长下发基准熔断
$anchorBig = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 1.0, 3000.0);
[$tv, $ti] = $refAnchorTargets->invoke(null, $anchorBig, [], [], 1);
$check($tv === 50 && $ti === 8, "下发时长 3000 秒熔断 50 条视频（实际={$tv}）");

// ═══ 八、前后端常量口径一致性 ═══
$fe = file_get_contents('D:/PhpTool/WWW/company-web/uniapp/src/ai_modules/hotspot/enums/index.ts');
$check(str_contains($fe, 'CHARS_PER_SEC: 4.2') && str_contains($fe, 'FLOOR_RATIO: 0.5'), '前端 hotspot 锚点常量 4.2/0.5 与后端一致');
$check(str_contains($fe, 'MIN_VIDEO: 2') && str_contains($fe, 'MIN_IMAGE: 2'), '前端 hotspot 兜底 2/2 与后端一致');
$check(
    str_contains($fe, 'IMAGE_DISPLAY_SEC: 2')
        && str_contains($fe, 'IMAGE_SHARE: 0.15')
        && str_contains($fe, 'IMAGE_MAX: 8'),
    '前端 hotspot 图片口径 2秒/15%/8张 与后端一致'
);
$check(
    str_contains($fe, 'AVG_VIDEO_SEC_FALLBACK: 5') && str_contains($fe, 'VIDEO_CAP: 50'),
    '前端 hotspot 条数预估常量 5秒/50条 与后端一致'
);
$fe2 = file_get_contents('D:/PhpTool/WWW/company-web/uniapp/src/ai_modules/hot_write/enums/index.ts');
$check(str_contains($fe2, 'CHARS_PER_SEC: 4.2') && str_contains($fe2, 'FLOOR_RATIO: 0.5'), '前端 hot_write 锚点常量 4.2/0.5 与后端一致');
$check(
    str_contains($fe2, 'IMAGE_DISPLAY_SEC: 2')
        && str_contains($fe2, 'IMAGE_SHARE: 0.15')
        && str_contains($fe2, 'IMAGE_MAX: 8')
        && str_contains($fe2, 'VIDEO_CAP: 50'),
    '前端 hot_write 图片口径 2秒/15%/8张/50条 与后端一致'
);

echo "合计：通过={$pass} 失败={$fail}\n";
exit($fail > 0 ? 1 : 0);
