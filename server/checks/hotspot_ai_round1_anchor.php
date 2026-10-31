<?php
/**
 * 首轮 AI 找素材按「素材目标秒数」折算条数的回归脚本（2026-10-29 真实口播口径）
 * 只验纯计算：materialAnchorSeconds（真实口播 + 下发时长基准）、VideoService::aiRound1VideoTarget、
 * VideoService::materialTargetSec（数字人 floor / 素材混剪 ceil）、materialQuota（图片动态张数）。
 * 不落库、不调 AI、不产生费用。
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use app\common\service\hotspot\ScriptService;
use app\common\service\hotspot\VideoService;

$pass = 0;
$fail = 0;
$check = static function (bool $ok, string $name, string $detail = '') use (&$pass, &$fail): void {
    if ($ok) {
        $pass++;
        echo "PASS {$name}\n";
        return;
    }
    $fail++;
    echo "FAIL {$name}" . ($detail !== '' ? " {$detail}" : '') . "\n";
};

// ═══ 一、锚点：真实口播秒数（400 字 → 96 秒，夹 15~180） ═══
$anchor = ScriptService::materialAnchorSeconds(str_repeat('字', 400));
$check(
    $anchor['est_sec'] === 96.0 && $anchor['floor_sec'] === 48.0 && $anchor['ceil_sec'] === 96.0,
    '400 字锚点 96/48/96',
    json_encode($anchor)
);

$anchor180 = ScriptService::materialAnchorSeconds(str_repeat('字', 100000));
$check($anchor180['est_sec'] === 180.0 && $anchor180['ceil_sec'] === 180.0, '超长文案口播夹 180 秒', json_encode($anchor180));

$anchorBase = ScriptService::materialAnchorSeconds(str_repeat('字', 400), 1.0, 133.0);
$check($anchorBase['est_sec'] === 133.0 && $anchorBase['floor_sec'] === 66.5, '下发 133 秒作上限 133/66.5', json_encode($anchorBase));

// ═══ 二、本轮目标秒数：数字人 floor、素材混剪 ceil ═══
$check(VideoService::materialTargetSec('digital', $anchor) === 48.0, '数字人目标取 floor=48 秒');
$check(VideoService::materialTargetSec('clips', $anchor) === 96.0, '素材混剪目标取 ceil=96 秒');
$check(VideoService::materialTargetSec('digital', $anchorBase) === 66.5, '爆款复刻数字人目标取 66.5 秒');

// ═══ 三、图片动态配额：目标秒数 × 15% / 2 秒，夹 2~8 ═══
$quota = VideoService::materialQuota('clips', 96.0);
$check($quota['i_min'] === 8 && $quota['i_max'] === 8, '混剪 96 秒图片配额 8 张', json_encode($quota));
$check($quota['v_min'] === 2 && $quota['v_max'] === 2, '视频兜底仍 2 条');

$quota = VideoService::materialQuota('digital', 48.0);
$check($quota['i_min'] === 4, '数字人 48 秒图片配额 4 张');

$quota = VideoService::materialQuota('digital', 0.0);
$check($quota['i_min'] === 2, '目标秒数缺失图片兜底 2 张');

// ═══ 四、首轮条数折算 ═══
// 素材混剪 400 字：48 秒图片占位（8 张）后缺口 0，视频兜底 2 条
$target = VideoService::aiRound1VideoTarget(96.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 8);
$check($target === 16, '混剪 96 秒无已选素材 → 16 视频（缺口 80 秒 / 5 秒）+ 8 图', "target={$target}");

// 数字人 400 字：图片 4 张占位 8 秒，缺口 40 秒 → 8 条
$target = VideoService::aiRound1VideoTarget(48.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 4);
$check($target === 8, '数字人 48 秒无已选素材 → 8 视频 + 4 图', "target={$target}");

// 已选素材够用时不追加
$target = VideoService::aiRound1VideoTarget(96.0, ['video_sec' => 100.0, 'image_sec' => 10.0], 3, 33.3, 2, 8);
$check($target === 3, '已选素材已覆盖目标 → 目标条数=已选 3 条', "target={$target}");

// 已选素材偏短：只补缺口
$target = VideoService::aiRound1VideoTarget(96.0, ['video_sec' => 20.0, 'image_sec' => 0], 2, 10.0, 2, 8);
$check($target === 8, '已选 2×10 秒 + 8 图占位 → 缺口 60 秒补 6 条共 8 条', "target={$target}");

// 均条长未知（0/负数）按 5 秒兜底
$target = VideoService::aiRound1VideoTarget(96.0, ['video_sec' => 0, 'image_sec' => 0], 0, 0.0, 2, 0);
$check($target === 20, '均条长 0 且无图片占位 → 按 5 秒兜底 20 条', "target={$target}");

// 熔断 50 条
$target = VideoService::aiRound1VideoTarget(100000.0, ['video_sec' => 0, 'image_sec' => 0], 0, 5.0, 2, 2);
$check($target === 50, '极端缺口熔断 50 条', "target={$target}");

echo "合计：通过={$pass} 失败={$fail}\n";
exit($fail > 0 ? 1 : 0);
