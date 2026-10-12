<?php
/**
 * 热点库口播人设隔离：纯函数，不打库、不调 Coze。
 * 运行：php checks/hotspot_pool_persona_isolation.php
 * 与 tests/hotspot/pool_persona_isolation.php 同内容（tests 目录被 gitignore）。
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

    $pool = [
        'user_id' => 7,
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'creation_id' => 0,
    ];
    $scriptA = '人设A的口播正文要足够长不能当仅标题';
    $scriptB = '人设B的口播正文要足够长不能当仅标题';

    $check(
        \app\common\service\hotspot\PoolService::scopedPersonaIds(5) === [5],
        'catalogPoolQuery/findPending 人设5只认 5、不含 0'
    );
    $check(
        \app\common\service\hotspot\PoolService::scopedPersonaIds(0) === [0],
        '未绑人设目录只认 0'
    );

    $check(
        \app\common\service\hotspot\PoolService::creationPersonaMatches(
            ['persona_id' => 8, 'persona_name' => '同名'],
            5,
            '同名'
        ) === false,
        '同名人设不同 id 不匹配'
    );
    $check(
        \app\common\service\hotspot\PoolService::creationPersonaMatches(
            ['persona_id' => 0, 'persona_name' => '人设A'],
            5,
            '人设A'
        ) === false,
        '旧创作 persona_id=0 即使名字相同也不回填'
    );
    $check(
        \app\common\service\hotspot\PoolService::creationPersonaMatches(
            ['persona_id' => 5, 'persona_name' => '人设A'],
            5,
            '人设A'
        ) === true,
        '创作 persona_id 与当前人设一致才匹配'
    );

    $creationB = [[
        'id' => 11,
        'user_id' => 7,
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'title' => 'B标题',
        'script' => $scriptB,
        'persona_id' => 8,
        'persona_name' => '同名',
    ]];
    $hitB = \app\common\service\hotspot\PoolService::selectReusableFromCandidates(
        $pool,
        $creationB,
        [],
        5,
        '同名'
    );
    $check($hitB === null, '同话题创作属人设B时人设A回填不到');

    $creationLegacy = [[
        'id' => 12,
        'user_id' => 7,
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'title' => '旧标题',
        'script' => $scriptA,
        'persona_id' => 0,
        'persona_name' => '人设A',
    ]];
    $hitLegacy = \app\common\service\hotspot\PoolService::selectReusableFromCandidates(
        $pool,
        $creationLegacy,
        [],
        5,
        '人设A'
    );
    $check($hitLegacy === null, '创作 persona_id=0 且名字等于A时不回填');

    $taskA = [[
        'id' => 21,
        'user_id' => 7,
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'title' => 'A标题',
        'script' => $scriptA,
        'persona_id' => 5,
    ]];
    $hitTask = \app\common\service\hotspot\PoolService::selectReusableFromCandidates(
        $pool,
        [],
        $taskA,
        5,
        '人设A'
    );
    $check(
        is_array($hitTask)
            && ($hitTask['source'] ?? '') === 'task'
            && ($hitTask['script'] ?? '') === $scriptA,
        '任务 persona_json.id=A 时人设A可回填'
    );

    $stockZero = \app\common\service\hotspot\PoolService::resolveStockCreationId(
        7,
        5,
        '人设A',
        '某热点话题',
        'douyin',
        12,
        [
            'id' => 12,
            'user_id' => 7,
            'topic' => '某热点话题',
            'platform' => 'douyin',
            'persona_id' => 0,
            'persona_name' => '人设A',
        ]
    );
    $check($stockZero === 0, '入库不关联 persona_id=0 的旧创作');

    $stockOk = \app\common\service\hotspot\PoolService::resolveStockCreationId(
        7,
        5,
        '人设A',
        '某热点话题',
        'douyin',
        13,
        [
            'id' => 13,
            'user_id' => 7,
            'topic' => '某热点话题',
            'platform' => 'douyin',
            'persona_id' => 5,
            'persona_name' => '人设A',
        ]
    );
    $check($stockOk === 13, '入库可关联当前人设的创作');

    $merged = \app\common\service\hotspot\PoolService::mergeStockUpdate(
        ['script' => '', 'title' => '某热点话题', 'topic' => '某热点话题'],
        ['script' => $scriptA, 'title' => '已有标题']
    );
    $check(
        ($merged['script'] ?? '') === $scriptA && ($merged['title'] ?? '') === '已有标题',
        'mergeStockUpdate 仅标题仍保留已有口播（不再认领0行后不会把B的稿划给A）'
    );

    $keyA = \app\common\service\hotspot\RecordService::creationLookupKey(7, 5, '某热点话题', 'douyin');
    $keyB = \app\common\service\hotspot\RecordService::creationLookupKey(7, 8, '某热点话题', 'douyin');
    $check(
        $keyA['persona_id'] === 5 && $keyB['persona_id'] === 8 && $keyA !== $keyB,
        'recordCreation 查找键同名不同人设 id 互不覆盖'
    );

    $check(
        \app\common\service\hotspot\PoolService::isPendingStockRow(['use_count' => 0]) === true,
        'use_count=0 视为未使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::isPendingStockRow(['id' => 1]) === true,
        '缺 use_count 视为未使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::isPendingStockRow(['use_count' => 1]) === false,
        'use_count>0 计为已使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::isPendingStockRow(['use_count' => 3]) === false,
        '多次占用计为已使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::isPendingStockRow([
            'use_count' => 0,
            'use_status' => \app\common\model\hotspot\HotspotPool::USE_STATUS_USED,
        ]) === false,
        '占用日志可将 use_count=0 计为已使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::useStatusFromCount(0)
            === \app\common\model\hotspot\HotspotPool::USE_STATUS_PENDING,
        'use_count=0 映射 use_status 未使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::useStatusFromCount(1)
            === \app\common\model\hotspot\HotspotPool::USE_STATUS_USED,
        'use_count>0 映射 use_status 已使用'
    );
    $check(
        \app\common\service\hotspot\PoolService::useStatusForRow(9, 0, [9 => true])
            === \app\common\model\hotspot\HotspotPool::USE_STATUS_USED,
        '有占用日志时 use_count=0 仍为已使用'
    );

    $otherRows = [
        ['persona_id' => 227, 'script' => $scriptA],
    ];
    $check(
        \app\common\service\hotspot\PoolService::scriptCopiesOtherPersona($scriptA, 167, $otherRows) === true,
        '与其他人设逐字相同的口播视为串稿'
    );
    $check(
        \app\common\service\hotspot\PoolService::scriptCopiesOtherPersona($scriptA, 227, $otherRows) === false,
        '只和当前人设自己的口播相同不算串稿'
    );
    $check(
        \app\common\service\hotspot\PoolService::scriptCopiesOtherPersona($scriptB, 167, $otherRows) === false,
        '文案不同不算串稿'
    );
    $check(
        \app\common\service\hotspot\PoolService::scriptCopiesOtherPersona('', 167, $otherRows) === false,
        '空口播不算串稿'
    );
    $check(
        \app\common\service\hotspot\PoolService::scriptCopiesOtherPersona('  ' . $scriptA . '  ', 167, $otherRows) === true,
        '首尾空白不影响逐字相同判定'
    );
    $kept = \app\common\service\hotspot\PoolService::mergeStockUpdate(
        ['script' => '', 'title' => '某热点话题', 'topic' => '某热点话题', '_title_from_topic' => true],
        ['script' => $scriptB, 'title' => '人设B标题']
    );
    $check(
        ($kept['script'] ?? '') === $scriptB && ($kept['title'] ?? '') === '人设B标题',
        '串稿被清空后更新入库仍保留当前人设自己的口播'
    );

    echo sprintf("总计 %d 通过 %d 失败 %d\n", $passed + $failed, $passed, $failed);
    exit($failed > 0 ? 1 : 0);
}
