<?php
/**
 * 人设素材库「整组导入」判重键回归脚本（纯函数，不调库）。
 * 运行：php checks/persona_group_import_dedupe.php
 *
 * 背景：自动适配模式下整组导入外部素材组后，切到原图模式再整组导入，
 * 曾因判重键不含 keep_original 而全部被当成「已导入」跳过（提示已跳过、列表一条没有）。
 */
require __DIR__ . '/../vendor/autoload.php';

use app\api\logic\aiPersona\MaterialLogic;
use app\common\model\aiPersona\Material;

$method = new ReflectionMethod(MaterialLogic::class, 'groupImportDedupeKey');
$method->setAccessible(true);
$key = static function (int $type, int $publishMode, int $keepOriginal, int $materialId) use ($method): string {
    return (string)$method->invoke(null, $type, $publishMode, $keepOriginal, $materialId);
};

$fail = 0;
$expect = static function (string $name, $actual, $expected) use (&$fail): void {
    if ($actual !== $expected) {
        $fail++;
        echo "FAIL: [{$name}] expect=" . var_export($expected, true)
            . ' got=' . var_export($actual, true) . PHP_EOL;
    }
};

$video = Material::MATERIAL_TYPE_VIDEO;
$image = Material::MATERIAL_TYPE_IMAGE;
$music = Material::MATERIAL_TYPE_MUSIC;
$slice = Material::PUBLISH_MODE_MAKE_VIDEO;
$direct = Material::PUBLISH_MODE_DIRECT_SEND;
$auto = Material::KEEP_ORIGINAL_NO;
$original = Material::KEEP_ORIGINAL_YES;

// 同一素材：自动适配 / 原图 必须互不相撞（本次修复的主场景）
$expect(
    '同组视频 自动适配 vs 原图 不同键',
    $key($video, $slice, $auto, 22) !== $key($video, $slice, $original, 22),
    true
);
$expect(
    '同组图片 自动适配 vs 原图 不同键',
    $key($image, $slice, $auto, 22) !== $key($image, $slice, $original, 22),
    true
);
// 剪辑原片库 vs 成品直发库 也是两套列表，判重不能串
$expect(
    '同组视频 剪辑原片库 vs 成品直发库 不同键',
    $key($video, $slice, $auto, 22) !== $key($video, $direct, $auto, 22),
    true
);
// 同一库同一模式：重复导入照旧跳过（键稳定）
$expect(
    '同组视频 同库同模式 键稳定',
    $key($video, $slice, $auto, 22),
    $key($video, $slice, $auto, 22)
);
// 不同素材 id 不能撞键
$expect(
    '同组不同素材 不同键',
    $key($video, $slice, $auto, 21) !== $key($video, $slice, $auto, 22),
    true
);
// 音乐库两种模式共用一份列表，音乐判重不分库不分模式（否则会插出重复音频）
$expect(
    '音乐 自动适配 vs 原图 同键',
    $key($music, $slice, $auto, 8),
    $key($music, $slice, $original, 8)
);
$expect(
    '音乐 剪辑原片库 vs 成品直发库 同键',
    $key($music, $slice, $auto, 8),
    $key($music, $direct, $auto, 8)
);

echo $fail === 0 ? 'ALL PASS' : "{$fail} FAILED";
echo PHP_EOL;
exit($fail === 0 ? 0 : 1);
