<?php
/**
 * 人设素材库 → 外部素材库同步：切割上传跳过规则回归脚本（纯函数，不调库）。
 * 运行：php checks/persona_media_sync_cut_upload.php
 */
require __DIR__ . '/../vendor/autoload.php';

use app\common\enum\FileEnum;
use app\common\service\aiPersona\PersonaMediaLibrarySyncService;

$cases = [
    // 切割上传的视频 => 不同步（原片切片后会被删除，同步出去是坏素材）
    '视频 ffmpeg=2' => [['type' => FileEnum::VIDEO_TYPE], 2, true],
    '视频 ffmpeg=2（字符串）' => [['type' => FileEnum::VIDEO_TYPE], '2', true],
    // 非切割上传的视频照常同步
    '视频 ffmpeg=1' => [['type' => FileEnum::VIDEO_TYPE], 1, false],
    '视频 ffmpeg=0' => [['type' => FileEnum::VIDEO_TYPE], 0, false],
    // 图片即使误传 ffmpeg=2 也不跳过：图片不参与切割
    '图片 ffmpeg=2' => [['type' => FileEnum::IMAGE_TYPE], 2, false],
    // 其它类型本来就不会进外部素材库
    '音频 ffmpeg=2' => [['type' => FileEnum::AUDIO_TYPE], 2, false],
    '通用文件 ffmpeg=2' => [['type' => FileEnum::FILE_TYPE], 2, false],
    '缺少 type' => [[], 2, false],
];

$fail = 0;
foreach ($cases as $name => [$uploadResult, $ffmpeg, $expect]) {
    $got = PersonaMediaLibrarySyncService::skipForCutUpload($uploadResult, $ffmpeg);
    if ($got !== $expect) {
        $fail++;
        echo "FAIL: [{$name}] expect=" . var_export($expect, true) . ' got=' . var_export($got, true) . PHP_EOL;
    }
}

$flagCases = [
    ['1', true],
    [1, true],
    ['0', false],
    [0, false],
    ['', false],
    [null, false],
];
foreach ($flagCases as [$flag, $expect]) {
    $got = PersonaMediaLibrarySyncService::requested($flag);
    if ($got !== $expect) {
        $fail++;
        echo 'FAIL: requested(' . var_export($flag, true) . ') expect=' . var_export($expect, true)
            . ' got=' . var_export($got, true) . PHP_EOL;
    }
}

echo $fail === 0 ? 'ALL PASS' : "{$fail} FAILED";
echo PHP_EOL;
