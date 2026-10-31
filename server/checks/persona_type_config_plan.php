<?php
/**
 * 视频剪辑分类型配置（type_config）回归脚本（纯本地，不连库）。
 * 运行：php checks/persona_type_config_plan.php
 *
 * 覆盖：
 * - A. 结构归一：补全 1/3/4、裁剪枚举/越界、音量保留 1 位小数
 * - B. 老数据条数分配：5 条三类型 → 2/2/1，6 条两类型 → 3/3，2 条三类型 → 1/1/0
 * - C. 云端交错计划：不扎堆、条数为 0 的类型不出片
 * - D. 老数据音乐继承顶层值；显式配置按类型取
 * - E. PersonaTypePlanService 出片总量与计划
 * - F. 手机模式不读类型条数（条数由发布时段决定）
 */
require __DIR__ . '/../vendor/autoload.php';

use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\service\aiPersona\PersonaTypePlanService;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;

$app = new \think\App();
$app->initialize();

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

// —— A. 结构归一 —— //
$raw = [
    '1' => ['video_count' => 3, 'music_source' => 1, 'music_group_id' => 5, 'system_music_id' => ' 12 ', 'music_volume' => 0.35],
    '3' => ['video_count' => 99, 'music_source' => 9, 'music_group_id' => -3, 'system_music_id' => '77', 'music_volume' => 1.6],
    '4' => ['video_count' => -1, 'music_source' => 3, 'music_group_id' => 6, 'system_music_id' => '88', 'music_volume' => 0.6],
    '2' => ['video_count' => 5],
];
$normalized = AiPersonaSynthesisConfig::normalizeTypeConfig($raw);
$check(array_keys($normalized) === [1, 3, 4], 'A1 三个类型 key 恒存在且顺序固定');
$check($normalized[1]['video_count'] === 3, 'A2 合法条数保留');
$check($normalized[1]['system_music_id'] === '12', 'A3 指定曲目去空格');
$check(abs($normalized[1]['music_volume'] - 0.4) < 0.0001, 'A4 音量保留 1 位小数（0.35→0.4）');
$check($normalized[3]['video_count'] === 0, 'A5 条数越界（99）按 0');
$check($normalized[3]['music_source'] === AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM, 'A6 非法来源回落系统音乐库');
$check($normalized[3]['music_group_id'] === 0, 'A7 负数分组回落 0');
$personaSource = AiPersonaSynthesisConfig::normalizeTypeConfig([
    '1' => ['music_source' => 2, 'music_group_id' => 6, 'system_music_id' => '88'],
]);
$check($personaSource[1]['music_group_id'] === 0 && $personaSource[1]['system_music_id'] === '', 'A8 非系统来源清空分组与指定曲目');
$check($normalized[4]['music_source'] === AiPersonaSynthesisConfig::MUSIC_SOURCE_NONE, 'A9 来源=不使用保留');
$check($normalized[4]['system_music_id'] === '', 'A10 不使用背景音乐时清空指定曲目');
$check(abs($normalized[4]['music_volume'] - 0.6) < 0.0001, 'A11 音量 0.6 保留');
$check($normalized[3]['music_volume'] === AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT, 'A12 音量越界回落默认');
$check(!isset($normalized[2]), 'A13 未知类型被丢弃');

$empty = AiPersonaSynthesisConfig::normalizeTypeConfig(null);
$check($empty[1]['video_count'] === 0 && $empty[1]['music_source'] === AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM, 'A14 空输入补全默认值');
$stringJson = AiPersonaSynthesisConfig::normalizeTypeConfig('{"1":{"video_count":2}}');
$check($stringJson[1]['video_count'] === 2, 'A15 JSON 字符串输入可解析');

// 三类合计不得超过每日上限 20：溢出部分按 1/3/4 顺序截断
$overflow = AiPersonaSynthesisConfig::normalizeTypeConfig([
    '1' => ['video_count' => 20],
    '3' => ['video_count' => 20],
    '4' => ['video_count' => 20],
]);
$check(
    ($overflow[1]['video_count'] + $overflow[3]['video_count'] + $overflow[4]['video_count']) === 20
        && $overflow[1]['video_count'] === 20,
    'A16 三类合计截断到 20（口播先占满，其余归零）'
);
$partial = AiPersonaSynthesisConfig::normalizeTypeConfig([
    '1' => ['video_count' => 10],
    '3' => ['video_count' => 10],
    '4' => ['video_count' => 10],
]);
$check(
    $partial[1]['video_count'] === 10 && $partial[3]['video_count'] === 10 && $partial[4]['video_count'] === 0,
    'A17 10/10/10 截断为 10/10/0（末位让出）'
);

// —— B. 老数据条数分配（2/2/1 口径）—— //
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(5, [1, 4, 3]) === [1 => 2, 3 => 1, 4 => 2],
    'B1 5 条 + 三类型按顺序 → 2/2/1（1:2, 4:2, 3:1）'
);
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(6, [1, 4]) === [1 => 3, 3 => 0, 4 => 3],
    'B2 6 条 + 两类型 → 3/3，未启用类型 0'
);
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(5, [1, 4]) === [1 => 3, 3 => 0, 4 => 2],
    'B3 5 条 + 两类型 → 3/2'
);
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(2, [1, 4, 3]) === [1 => 1, 3 => 0, 4 => 1],
    'B4 2 条 + 三类型 → 1/1/0（末位不出片）'
);
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(0, [1, 4, 3]) === [1 => 0, 3 => 0, 4 => 0],
    'B5 0 条全部为 0'
);
$check(
    AiPersonaSynthesisConfig::splitLegacyVideoCount(5, []) === [1 => 0, 3 => 0, 4 => 0],
    'B6 无启用类型全部为 0'
);

$legacyConfig = [
    'daily_video_count' => 5,
    'generation_types' => [1, 4, 3],
    'music_source' => 1,
    'system_music_id' => '',
    'music_volume' => 0.3,
];
$check(
    AiPersonaSynthesisConfig::resolveCloudTypeCounts($legacyConfig) === [1 => 2, 3 => 1, 4 => 2],
    'B7 老数据（无 type_config）按 daily_video_count 均分'
);
$check(
    AiPersonaSynthesisConfig::resolveCloudTypeCount($legacyConfig, 4) === 2,
    'B8 单类型条数取分配结果'
);

$explicitConfig = [
    'daily_video_count' => 9,
    'generation_types' => [1, 4, 3],
    'type_config' => [
        '1' => ['video_count' => 3],
        '3' => ['video_count' => 3],
        '4' => ['video_count' => 0],
    ],
];
$check(
    AiPersonaSynthesisConfig::resolveCloudTypeCounts($explicitConfig) === [1 => 3, 3 => 3, 4 => 0],
    'B9 显式 type_config 优先于 daily_video_count'
);

// —— C. 云端交错计划 —— //
$plan = AiPersonaSynthesisConfig::buildCloudTypePlan(
    [1 => 2, 4 => 2, 3 => 1],
    [1, 4, 3]
);
$check($plan === [1, 4, 3, 1, 4], 'C1 老数据 2/2/1 交错计划 = [1,4,3,1,4]');

$plan33 = AiPersonaSynthesisConfig::buildCloudTypePlan([1 => 3, 4 => 3], [1, 4, 3]);
$check($plan33 === [1, 4, 1, 4, 1, 4], 'C2 3/3 交错不扎堆');

$planZero = AiPersonaSynthesisConfig::buildCloudTypePlan([1 => 0, 3 => 2, 4 => 0], [1, 4, 3]);
$check($planZero === [3, 3], 'C3 条数为 0 的类型不出片');

$planOrder = AiPersonaSynthesisConfig::buildCloudTypePlan([1 => 1, 3 => 1], [3, 1]);
$check($planOrder === [3, 1], 'C4 遵循 generation_types 的轮转顺序');

$planOrphan = AiPersonaSynthesisConfig::buildCloudTypePlan([4 => 1], [1]);
$check($planOrphan === [4], 'C5 配了条数但没列进 generation_types 仍会出片');

// —— D. 按类型音乐与老数据继承 —— //
$legacyMusic = AiPersonaSynthesisConfig::resolveTypeMusic($legacyConfig, 3);
$check(
    $legacyMusic['source'] === 1 && abs($legacyMusic['volume'] - 0.3) < 0.0001 && $legacyMusic['pinned_id'] === '',
    'D1 老数据三类音乐继承顶层来源与音量'
);

$typedMusicConfig = [
    'generation_types' => [1, 4, 3],
    'music_source' => 2,
    'system_music_id' => '99',
    'music_volume' => 0.5,
    'type_config' => [
        '1' => ['video_count' => 1, 'music_source' => 1, 'music_group_id' => 5, 'system_music_id' => '12', 'music_volume' => 0.3],
        '3' => ['video_count' => 1, 'music_source' => 1, 'music_group_id' => 6, 'system_music_id' => '', 'music_volume' => 0.1],
    ],
];
$music1 = AiPersonaSynthesisConfig::resolveTypeMusic($typedMusicConfig, 1);
$check($music1['source'] === 1 && $music1['group_id'] === 5 && $music1['pinned_id'] === '12', 'D2 口播类型用自己的分组与指定曲目');
$music3 = AiPersonaSynthesisConfig::resolveTypeMusic($typedMusicConfig, 3);
$check($music3['group_id'] === 6 && abs($music3['volume'] - 0.1) < 0.0001, 'D3 素材混剪用自己的分组与音量');
$music4 = AiPersonaSynthesisConfig::resolveTypeMusic($typedMusicConfig, 4);
$check($music4['source'] === 2 && abs($music4['volume'] - 0.5) < 0.0001, 'D4 未配置的类型回落顶层来源/音量');
$noneMusic = AiPersonaSynthesisConfig::resolveTypeMusic(
    ['type_config' => ['4' => ['music_source' => 3, 'system_music_id' => '5', 'music_group_id' => 2]]],
    4
);
$check($noneMusic['source'] === 3 && $noneMusic['pinned_id'] === '' && $noneMusic['group_id'] === 0, 'D5 不使用背景音乐时清空曲目与分组');

// —— E. PersonaTypePlanService —— //
$cloudConfig = [
    'run_mode' => 2,
    'daily_video_count' => 0,
    'generation_types' => [1, 4, 3],
    'type_config' => [
        '1' => ['video_count' => 3],
        '3' => ['video_count' => 2],
        '4' => ['video_count' => 0],
    ],
];
$check(PersonaTypePlanService::resolveCloudTargetCount($cloudConfig) === 5, 'E1 云端出片总量 = 三类条数之和');
$check(PersonaTypePlanService::buildCloudPlan($cloudConfig) === [1, 3, 1, 3, 1], 'E2 云端计划交错铺满且不含 0 条类型');
$check(PersonaTypePlanService::sliceCloudPlan($cloudConfig, 0, 2) === [1, 3], 'E3 计划游标从头取');
$check(PersonaTypePlanService::sliceCloudPlan($cloudConfig, 4, 3) === [1], 'E4 计划游标越界只取剩余部分');
$check(PersonaTypePlanService::resolveCloudTypeCount($cloudConfig, 3) === 2, 'E5 单类型条数可查');

$legacyTarget = PersonaTypePlanService::resolveCloudTargetCount($legacyConfig);
$check($legacyTarget === 5, 'E6 老数据出片总量 = daily_video_count');
$check(PersonaTypePlanService::buildCloudPlan($legacyConfig) === [1, 4, 3, 1, 4], 'E7 老数据计划按 2/2/1 交错');

$emptyTarget = PersonaTypePlanService::resolveCloudTargetCount(['daily_video_count' => 0, 'generation_types' => [1]]);
$check($emptyTarget === AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT, 'E8 全空兜底 1 条');

// —— F. 手机模式不读类型条数 —— //
// 手机模式的条数来自发布时段，这里只验证「模型层不提供手机模式条数入口」：
// resolveCloudTypeCounts 只是数据映射，是否消费由调用方（CopywritingAiGenerationLogic）决定。
$phoneConfig = ['generation_types' => [1, 3], 'type_config' => ['1' => ['video_count' => 9], '3' => ['video_count' => 9]]];
$check(
    !method_exists(AiPersonaSynthesisConfig::class, 'resolvePhoneTypeCounts'),
    'F1 手机模式没有类型条数入口（条数只由发布时段决定）'
);
$check(
    AiPersonaSynthesisConfig::resolveGenerationOrder($phoneConfig) === [1, 3],
    'F2 手机模式仍以 generation_types 作为轮转顺序'
);

// —— G. 非系统音乐来源回写顶层字段 —— //
$top = AiPersonaSynthesisConfig::resolveTypeMusic(
    ['type_config' => ['1' => ['music_source' => 2, 'music_group_id' => 4, 'system_music_id' => '7', 'music_volume' => 0.7]]],
    1
);
$check($top['source'] === 2 && $top['group_id'] === 0 && $top['pinned_id'] === '', 'G1 人设音乐库不保留系统分组/曲目');

// —— H. 口播语速按类型独立 —— //
$rateRaw = AiPersonaSynthesisConfig::normalizeTypeConfig([
    '1' => ['speech_rate' => 1.5],
    '3' => ['speech_rate' => '0.9'],
    '4' => ['speech_rate' => 9],
]);
$check(abs($rateRaw[1]['speech_rate'] - 1.5) < 0.0001, 'H1 类型自己的语速保留（1.5）');
$check(abs($rateRaw[3]['speech_rate'] - 0.9) < 0.0001, 'H2 字符串语速可解析（0.9）');
$check($rateRaw[4]['speech_rate'] === AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT, 'H3 语速越界回落默认值（与顶层同口径）');
$check(
    $rateRaw[1]['speech_rate'] !== $rateRaw[3]['speech_rate'],
    'H4 三个类型的语速互不影响（改一类不会带着另外两类变）'
);
$rateEmpty = AiPersonaSynthesisConfig::normalizeTypeConfig(null);
$check($rateEmpty[1]['speech_rate'] === AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT, 'H5 缺省语速回落默认值');

$rateConfig = [
    'speech_rate' => 1.2,
    'type_config' => [
        '1' => ['speech_rate' => 1.8],
        '4' => ['speech_rate' => 0.6],
    ],
];
$check(abs(AiPersonaSynthesisConfig::resolveTypeSpeechRate($rateConfig, 1) - 1.8) < 0.0001, 'H6 口播取自己的语速');
$check(abs(AiPersonaSynthesisConfig::resolveTypeSpeechRate($rateConfig, 4) - 0.6) < 0.0001, 'H7 新闻体取自己的语速');
$check(abs(AiPersonaSynthesisConfig::resolveTypeSpeechRate($rateConfig, 3) - 1.2) < 0.0001, 'H8 未配置的类型回落顶层 speech_rate');
$check(
    abs(AiPersonaSynthesisConfig::resolveTypeSpeechRate(['speech_rate' => 0.8], 4) - 0.8) < 0.0001,
    'H9 老数据（无 type_config）三类都继承顶层语速，行为不变'
);
$check(
    AiPersonaSynthesisConfig::resolveTypeSpeechRate([], 1) === AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT,
    'H10 全空回落默认语速'
);

// —— I. 新闻体一镜到底 —— //
$shotRaw = AiPersonaSynthesisConfig::normalizeTypeConfig([
    '1' => ['single_shot' => 1],
    '3' => ['single_shot' => 1],
    '4' => ['single_shot' => 1],
]);
$check($shotRaw[4]['single_shot'] === 1, 'I1 新闻体开启一镜到底保留为 1');
$check($shotRaw[1]['single_shot'] === 0 && $shotRaw[3]['single_shot'] === 0, 'I2 非新闻体类型恒 0');
$shotTruthy = AiPersonaSynthesisConfig::normalizeTypeConfig(['4' => ['single_shot' => true]]);
$check($shotTruthy[4]['single_shot'] === 1, 'I3 布尔 true 归一为 1');
$shotInvalid = AiPersonaSynthesisConfig::normalizeTypeConfig(['4' => ['single_shot' => 2]]);
$check($shotInvalid[4]['single_shot'] === 0, 'I4 非法值（2）按关闭');

$shotOffConfig = ['type_config' => ['4' => ['video_count' => 1]]];
$check(
    AiPersonaSynthesisConfig::resolveTypeSingleShot($shotOffConfig, 4) === false,
    'I5 老数据缺键 = 关闭，行为不变'
);
$shotOnConfig = ['type_config' => ['4' => ['single_shot' => 1], '3' => ['single_shot' => 1]]];
$check(AiPersonaSynthesisConfig::resolveTypeSingleShot($shotOnConfig, 4) === true, 'I6 新闻体显式开启为 true');
$check(AiPersonaSynthesisConfig::resolveTypeSingleShot($shotOnConfig, 3) === false, 'I7 素材混剪不支持一镜到底');
$check(
    AiPersonaSynthesisConfig::resolveTypeSingleShot(['type_config' => '{"4":{"single_shot":1}}'], 4) === true,
    'I8 JSON 字符串形式可解析'
);

$shotVideos = [
    ['id' => 1, 'duration' => 12],
    ['id' => 2, 'duration' => 40],
    ['id' => 3, 'duration' => 8],
];
$pickedLongEnough = CopywritingImitationLogic::pickSingleShotVideo($shotVideos, 20);
$check((int)$pickedLongEnough['id'] === 2, 'I9 优先取时长 >= 设定时长的视频');
$pickedLongest = CopywritingImitationLogic::pickSingleShotVideo($shotVideos, 120);
$check((int)$pickedLongest['id'] === 2, 'I10 都不够设定时长时取最长的一条');
$pickedOnlyImages = CopywritingImitationLogic::pickSingleShotVideo([], 30);
$check($pickedOnlyImages === [], 'I11 没有视频候选返回空');
$pickedFiltered = CopywritingImitationLogic::pickSingleShotVideo([
    ['id' => 4, 'duration' => 0],
    ['id' => 5, 'duration' => 120],
    ['id' => 6, 'duration' => 30],
], 60);
$check((int)$pickedFiltered['id'] === 6, 'I12 过滤 0 秒与超过 59 秒的素材');
$pickedTie = CopywritingImitationLogic::pickSingleShotVideo([
    ['id' => 7, 'duration' => 15],
    ['id' => 8, 'duration' => 15],
], 60);
$check(in_array((int)$pickedTie['id'], [7, 8], true), 'I13 同时长随机取一条');

echo "---\n";
echo $failed === 0
    ? "ALL PASS（{$passed} 通过）\n"
    : "{$failed} FAILED（{$passed} 通过）\n";
exit($failed === 0 ? 0 : 1);
