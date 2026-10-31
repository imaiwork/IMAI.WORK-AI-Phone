<?php
/**
 * 素材时长收口缺口透出（P0）：纯函数，不打库、不调上游。
 * 运行：php checks/hotspot_material_gate_warning.php
 *
 * 覆盖：
 * - TaskService::materialWarningText：缺口>0 给中文提示；缺口=0/无收口记录返回空
 * - TaskService::nextRetryOptions：重试清掉上一轮 material_gate
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

    use \app\common\service\hotspot\TaskService;

    // —— materialWarningText：缺口>0 —— //
    $gateOptions = [
        'material_gate' => [
            'est_duration_sec' => 90,
            'needed_sec' => 112.5,
            'available_sec' => 80.0,
            'gap_sec' => 32.5,
            'ai_round2' => 0,
            'pool_filled' => 3,
        ],
    ];
    $warning = TaskService::materialWarningText($gateOptions);
    $check(
        str_contains($warning, '素材总时长约80.0秒')
            && str_contains($warning, '口播预估约90秒')
            && str_contains($warning, '仍缺32.5秒')
            && str_contains($warning, '重复使用'),
        '缺口>0 返回含秒数与风险的中文提示'
    );

    // —— materialWarningText：缺口=0 / 无记录 —— //
    $check(
        TaskService::materialWarningText(['material_gate' => ['gap_sec' => 0.0]]) === '',
        '缺口=0 返回空串'
    );
    $check(TaskService::materialWarningText([]) === '', '无 material_gate 返回空串');
    $check(
        TaskService::materialWarningText(['material_gate' => null]) === '',
        'material_gate=null 返回空串'
    );

    // —— nextRetryOptions：重试清旧缺口、保留其他键 —— //
    $retry = TaskService::nextRetryOptions([
        'retry_seq' => 0,
        'material_gate' => ['gap_sec' => 12.5],
        'dispatch_status' => 'done',
        'shanjian_setting_id' => 77,
    ]);
    $check(
        !array_key_exists('material_gate', $retry),
        '重试 options 清掉旧 material_gate'
    );
    $check(
        (int)($retry['retry_seq'] ?? 0) === 1 && ($retry['dispatch_status'] ?? '') === 'pending',
        '重试序号+1 且 dispatch 状态回 pending'
    );
    $check(
        (int)($retry['shanjian_setting_id'] ?? 0) === 77,
        '重试不清闪剪绑定（沿用原单重试链路）'
    );

    echo sprintf("合计：通过=%d 失败=%d\n", $passed, $failed);
    exit($failed > 0 ? 1 : 0);
}
