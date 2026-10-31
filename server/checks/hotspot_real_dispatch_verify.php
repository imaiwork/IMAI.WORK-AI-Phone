<?php
/**
 * 热点真实链路验证：真实 HotspotLogic::add + 真实 VideoService::dispatchOne。
 * ⚠️ 会产生真实 AI 按次抓素材费用并真实下发闪剪上游，仅测试库环境运行。
 * 运行：php checks/hotspot_real_dispatch_verify.php
 *
 * 覆盖：
 * - TC-A 人设模式：创建响应带 material_precheck（P2）；真实下发后收口缺口/提示落库（P0）
 * - TC-B 不选人设 AI 模式：真实 AI 找素材按时长锚点取条数（P1），日志/收口/提示全链路核对
 * - TC-C 后台「画面循环压力大」筛选真实命中（P0）
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use app\api\logic\hotspot\HotspotLogic;
use app\common\model\aiPersona\Material;
use app\common\model\hotspot\HotspotTask;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\hotspot\ManualAssetService;
use app\common\service\hotspot\TaskService;
use app\common\service\hotspot\VideoService;
use think\facade\Db;

const TITLE_PREFIX = '真实跑冒烟';

$pass = 0;
$fail = 0;
$skip = 0;
$record = function (string $id, string $scene, bool $ok, string $actual = '', bool $skipped = false) use (&$pass, &$fail, &$skip): void {
    if ($skipped) {
        echo "[SKIP] {$id} | {$scene} | {$actual}\n";
        $skip++;
        return;
    }
    if ($ok) {
        echo "[PASS] {$id} | {$scene}\n";
        $pass++;
        return;
    }
    echo "[FAIL] {$id} | {$scene} | {$actual}\n";
    $fail++;
};

echo "=== 热点真实链路验证 ===\n";

// 清理上一次冒烟残留（软删热点任务 + 删闪剪行，与 tests/hotspot_async_dispatch_verify.php 同口径）
$createdNos = [];
$createdSettingIds = [];
$cleanupTask = static function (HotspotTask $old) use (&$createdSettingIds): void {
    $opt = is_array($old->options_json) ? $old->options_json : [];
    $sid = (int)($opt['shanjian_setting_id'] ?? 0);
    if ($sid > 0) {
        $createdSettingIds[] = $sid;
    }
    $old->delete();
};
foreach (HotspotTask::where('title', 'like', TITLE_PREFIX . '%')->select() as $old) {
    $cleanupTask($old);
}
foreach ($createdSettingIds as $sid) {
    ShanjianVideoTask::where('video_setting_id', $sid)->delete();
    ShanjianVideoSetting::where('id', $sid)->delete();
}
$createdSettingIds = [];

// —— 选真实人设 + 素材 + 音色（同 tests/hotspot_async_dispatch_verify.php 口径）——
$materialPersona = Material::alias('m')
    ->join('ai_persona_digital_voice v', 'v.persona_id = m.persona_id AND v.user_id = m.user_id AND v.delete_time IS NULL')
    ->where('m.publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
    ->whereIn('m.material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE])
    ->where('m.use_status', Material::USE_STATUS_ENABLED)
    ->whereNull('m.delete_time')
    ->field(['m.user_id', 'm.persona_id'])
    ->order('m.id', 'desc')
    ->find();
$mp = $materialPersona ? (is_array($materialPersona) ? $materialPersona : $materialPersona->toArray()) : [];
$userId = (int)($mp['user_id'] ?? 0);
$personaId = (int)($mp['persona_id'] ?? 0);
$materialIds = [];
if ($userId > 0 && $personaId > 0) {
    $materialIds = Material::where('user_id', $userId)
        ->where('persona_id', $personaId)
        ->where('publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
        ->whereIn('material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE])
        ->where('use_status', Material::USE_STATUS_ENABLED)
        ->whereNull('delete_time')
        ->order('id', 'desc')
        ->limit(3)
        ->column('id');
    $materialIds = array_values(array_map('intval', $materialIds));
}

$scriptLong = str_repeat('这是一段用于热点追踪真实链路验证的口播正文内容，', 28); // ≈ 420 字
$scriptMedium = str_repeat('这是一段用于热点追踪真实AI找素材的口播正文，', 18); // ≈ 216 字

// ========== TC-A：人设模式真实链路（P2 预检 + P0 收口落库） ==========
if ($userId <= 0 || $personaId <= 0 || $materialIds === []) {
    $record('TC-A1', '人设模式：add 返回 material_precheck', false, '测试库缺少人设+素材+音色', true);
    $record('TC-A2', '人设模式：dispatchOne 真实下发', false, '缺少前置数据', true);
    $record('TC-A3', '人设模式：收口缺口/提示落库', false, '缺少前置数据', true);
} else {
    $added = HotspotLogic::add([
        'topic' => TITLE_PREFIX . '人设',
        'platform' => 'douyin',
        'title' => TITLE_PREFIX . '人设',
        'script' => $scriptLong,
        'persona' => ['id' => $personaId],
        'options' => [
            'video_type' => 'clips',
            'material_mode' => 'persona',
            'materials' => $materialIds,
        ],
    ], $userId);
    $taskNoA = is_array($added) ? (string)($added['id'] ?? '') : '';
    if ($taskNoA !== '') {
        $createdNos[] = $taskNoA;
    }
    $preA = is_array($added['material_precheck'] ?? null) ? $added['material_precheck'] : [];
    $record(
        'TC-A1',
        '人设模式：add 返回 material_precheck',
        is_array($added)
            && (int)($preA['est_duration_sec'] ?? 0) > 0
            && array_key_exists('available_sec', $preA)
            && array_key_exists('warning', $preA)
            && array_key_exists('pool_fill_sec', $preA),
        'est=' . (int)($preA['est_duration_sec'] ?? 0)
            . ' 可承载=' . (float)($preA['available_sec'] ?? 0)
            . ' 池可补=' . (float)($preA['pool_fill_sec'] ?? 0)
            . ' 缺口=' . (float)($preA['gap_sec'] ?? 0)
    );

    $once = $taskNoA !== '' ? VideoService::dispatchOne($taskNoA, $userId) : null;
    $settingIdA = (int)(($once['options']['shanjian_setting_id'] ?? 0));
    if ($settingIdA > 0) {
        $createdSettingIds[] = $settingIdA;
    }
    $record(
        'TC-A2',
        '人设模式：dispatchOne 真实下发闪剪',
        is_array($once) && $settingIdA > 0 && ($once['options']['dispatch_status'] ?? '') === 'done',
        'setting_id=' . $settingIdA . ' status=' . ($once['options']['dispatch_status'] ?? ($taskNoA === '' ? '未创建任务' : '无'))
    );

    // 收口缺口>0 时 material_gate 与 material_warning 必须落库；=0 时两者都应为空
    $freshA = $taskNoA !== '' ? TaskService::detail($taskNoA, $userId) : null;
    $gateA = is_array($freshA['options']['material_gate'] ?? null) ? $freshA['options']['material_gate'] : [];
    $gapA = (float)($gateA['gap_sec'] ?? 0);
    $warnA = (string)($freshA['material_warning'] ?? '__字段缺失__');
    $extraA = [];
    if ($settingIdA > 0) {
        $extraRaw = ShanjianVideoSetting::where('id', $settingIdA)->value('extra');
        $extraA = is_array($extraRaw) ? $extraRaw : (json_decode((string)$extraRaw, true) ?: []);
    }
    $extraGapA = (float)($extraA['material_gap_sec'] ?? 0);
    if ($gapA > 0) {
        $record(
            'TC-A3',
            '人设模式：缺口>0 落 material_gate + material_warning + 闪剪 extra',
            $gateA !== []
                && $warnA !== '' && $warnA !== '__字段缺失__'
                && abs($extraGapA - $gapA) < 0.01
                && str_contains($warnA, '仍缺'),
            "gap={$gapA} extra_gap={$extraGapA} warn=" . mb_substr($warnA, 0, 40)
        );
    } else {
        $record(
            'TC-A3',
            '人设模式：素材够用不出警告',
            $gateA === [] && $warnA === '',
            "gate=" . ($gateA === [] ? '无' : '有') . " warn={$warnA}"
        );
    }
}

// ========== TC-B：不选人设 AI 模式真实链路（P1 首轮按时长锚点） ==========
$voiceRows = [];
try {
    $voiceRows = $userId > 0 ? ManualAssetService::voices($userId) : [];
} catch (Throwable $e) {
    $voiceRows = [];
}
$voiceId = (int)($voiceRows[0]['id'] ?? 0);
if ($userId <= 0 || $voiceId <= 0) {
    $record('TC-B1', 'AI模式：add 返回预检 AI 补齐建议', false, $userId <= 0 ? '缺少人设用户' : '该用户无可用音色', true);
    $record('TC-B2', 'AI模式：真实AI找素材+下发', false, '缺少前置数据', true);
    $record('TC-B3', 'AI模式：热点日志出现首轮锚点行', false, '缺少前置数据', true);
} else {
    $addedB = HotspotLogic::add([
        'topic' => TITLE_PREFIX . 'AI',
        'platform' => 'douyin',
        'title' => TITLE_PREFIX . 'AI',
        'script' => $scriptMedium, // ≈216 字 → 预估 52 秒 → 需 65 秒 → 锚点换算 11~12 条（配额 8）
        'options' => [
            'persona_mode' => 2,
            'video_type' => 'clips',
            'voice_id' => $voiceId,
        ],
    ], $userId);
    $taskNoB = is_array($addedB) ? (string)($addedB['id'] ?? '') : '';
    if ($taskNoB !== '') {
        $createdNos[] = $taskNoB;
    }
    $preB = is_array($addedB['material_precheck'] ?? null) ? $addedB['material_precheck'] : [];
    $record(
        'TC-B1',
        'AI模式：add 返回预检 AI 补齐建议',
        is_array($addedB)
            && (int)($preB['est_duration_sec'] ?? 0) > 0
            && (int)($preB['ai_fill_video'] ?? 0) >= 8
            && in_array((int)($preB['ai_fill_image'] ?? 0), [2, 3], true)
            && (string)($preB['warning'] ?? '') === '',
        'est=' . (int)($preB['est_duration_sec'] ?? 0)
            . ' AI补视频=' . (int)($preB['ai_fill_video'] ?? 0)
            . ' AI补图=' . (int)($preB['ai_fill_image'] ?? 0)
    );

    $startedB = microtime(true);
    $onceB = $taskNoB !== '' ? VideoService::dispatchOne($taskNoB, $userId) : null;
    $elapsedB = (int)((microtime(true) - $startedB) * 1000);
    $settingIdB = (int)(($onceB['options']['shanjian_setting_id'] ?? 0));
    if ($settingIdB > 0) {
        $createdSettingIds[] = $settingIdB;
    }
    $record(
        'TC-B2',
        'AI模式：真实AI找素材+真实下发闪剪',
        is_array($onceB) && $settingIdB > 0 && ($onceB['options']['dispatch_status'] ?? '') === 'done',
        'setting_id=' . $settingIdB . ' status=' . ($onceB['options']['dispatch_status'] ?? ($taskNoB === '' ? '未创建任务' : '无')) . " elapsed_ms={$elapsedB}"
    );

    // 热点日志：本轮必须出现「首轮AI找素材按时长锚点」行（含任务号，按 mtime 取最新 3 份日志）
    $logFiles = [];
    $logDir = dirname(__DIR__) . '/runtime/log/hotspot';
    if (is_dir($logDir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logDir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->isFile() && $f->getExtension() === 'log') {
                $logFiles[] = ['p' => $f->getPathname(), 't' => $f->getMTime()];
            }
        }
    }
    usort($logFiles, static fn ($a, $b) => $b['t'] <=> $a['t']);
    $logText = implode("\n", array_map(
        static fn ($f) => (string)file_get_contents($f['p']),
        array_slice($logFiles, 0, 3)
    ));
    $hasAnchor = $logText !== '' && str_contains($logText, '首轮AI找素材按时长锚点');
    $hasTaskNo = str_contains($logText, '任务号=' . $taskNoB);
    $record(
        'TC-B3',
        'AI模式：热点日志出现首轮锚点行（含任务号）',
        $hasAnchor && $hasTaskNo,
        '日志文件=' . count($logFiles)
            . ' 锚点行=' . ($hasAnchor ? '有' : '无')
            . ' 任务号行=' . ($hasTaskNo ? '有' : '无') . ' 任务号=' . $taskNoB
    );

    // 收口结果条件断言：AI 抓够 → 无警告；抓不够 → gate+警告+闪剪 extra 缺口
    $freshB = $taskNoB !== '' ? TaskService::detail($taskNoB, $userId) : null;
    $gateB = is_array($freshB['options']['material_gate'] ?? null) ? $freshB['options']['material_gate'] : [];
    $gapB = (float)($gateB['gap_sec'] ?? 0);
    $warnB = (string)($freshB['material_warning'] ?? '__字段缺失__');
    $extraB = [];
    if ($settingIdB > 0) {
        $extraRawB = ShanjianVideoSetting::where('id', $settingIdB)->value('extra');
        $extraB = is_array($extraRawB) ? $extraRawB : (json_decode((string)$extraRawB, true) ?: []);
    }
    if ($gapB > 0) {
        $record(
            'TC-B4',
            'AI模式：AI补后仍缺 → gate+警告+闪剪extra一致',
            $gateB !== [] && $warnB !== '' && $warnB !== '__字段缺失__'
                && abs((float)($extraB['material_gap_sec'] ?? 0) - $gapB) < 0.01,
            "gap={$gapB} warn=" . mb_substr($warnB, 0, 40)
        );
    } else {
        $record(
            'TC-B4',
            'AI模式：AI补齐到位 → 不落gate不出警告',
            $gateB === [] && $warnB === '',
            "gate=" . ($gateB === [] ? '无' : '有') . " warn={$warnB}"
        );
    }
}

// ========== TC-C：后台「画面循环压力大」筛选真实命中 ==========
$gapOnlyNos = HotspotTask::whereRaw("JSON_EXTRACT(options_json, '$.material_gate.gap_sec') > 0")
    ->whereIn('title', [TITLE_PREFIX . '人设', TITLE_PREFIX . 'AI'])
    ->whereNull('delete_time')
    ->column('task_no');
$expectedGapNos = [];
if (isset($gapA) && $gapA > 0 && isset($taskNoA)) {
    $expectedGapNos[] = $taskNoA;
}
if (isset($gapB) && $gapB > 0 && isset($taskNoB)) {
    $expectedGapNos[] = $taskNoB;
}
if ($expectedGapNos === []) {
    $record('TC-C1', '后台筛选：material_gap=1 命中缺口任务', false, '本轮无缺口任务可验证', true);
} else {
    $missing = array_diff($expectedGapNos, $gapOnlyNos);
    $record(
        'TC-C1',
        '后台筛选：material_gap=1 命中缺口任务',
        $missing === [],
        '命中=' . count($gapOnlyNos) . ($missing === [] ? '' : ' 未命中=' . implode(',', $missing))
    );
}

// ========== 清理：软删热点任务 + 删闪剪行 ==========
foreach ($createdSettingIds as $sid) {
    ShanjianVideoTask::where('video_setting_id', $sid)->delete();
    ShanjianVideoSetting::where('id', $sid)->delete();
}
foreach ($createdNos as $no) {
    HotspotTask::where('task_no', $no)->delete();
}
echo sprintf("清理：热点任务=%d 闪剪设置=%d\n", count($createdNos), count($createdSettingIds));
echo sprintf("合计：通过=%d 失败=%d 跳过=%d\n", $pass, $fail, $skip);
exit($fail > 0 ? 1 : 0);
