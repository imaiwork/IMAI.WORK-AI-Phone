<?php
/**
 * 热点库入库/更新入库：analysis 不能为空。
 * 运行：php checks/hotspot_stock_analysis.php
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

    $base = [
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'persona_id' => 0,
    ];

    $validate = static function (array $data): array {
        $v = new \app\api\validate\hotspot\HotspotValidate();
        $ok = $v->scene('stockAdd')->check($data);
        $error = $v->getError();
        $msg = is_array($error) ? implode(';', $error) : (string)$error;
        return [$ok, $msg];
    };

    [$ok, $msg] = $validate($base);
    $check($ok === false && $msg === '分析不能为空', '未传 analysis 拒绝：' . $msg);

    [$ok, $msg] = $validate($base + ['analysis' => []]);
    $check($ok === false && $msg === '分析不能为空', '空数组 analysis 拒绝：' . $msg);

    [$ok, $msg] = $validate($base + ['analysis' => '']);
    $check($ok === false && $msg === '分析不能为空', '空字符串 analysis 拒绝：' . $msg);

    [$ok, $msg] = $validate($base + ['analysis' => ['fit_reason' => '适合讲观点']]);
    $check($ok === true, '非空 analysis 通过校验：' . $msg);

    [$ok, $msg] = $validate($base + ['analysis' => [], 'analysis_id' => 8]);
    $check($ok === true, '空 analysis 但带 analysis_id 先放行，由业务层回读：' . $msg);

    $scriptScene = new \app\api\validate\hotspot\HotspotValidate();
    $scriptOk = $scriptScene->scene('script')->check([
        'topic' => '某热点话题',
        'platform' => 'douyin',
    ]);
    $check($scriptOk === true, '口播场景不要求 analysis：' . (string)$scriptScene->getError());

    $check(
        \app\common\service\hotspot\PoolService::analysisPayloadEmpty(null) === true
        && \app\common\service\hotspot\PoolService::analysisPayloadEmpty([]) === true
        && \app\common\service\hotspot\PoolService::analysisPayloadEmpty('x') === true
        && \app\common\service\hotspot\PoolService::analysisPayloadEmpty(['fit_score' => 1]) === false,
        'analysisPayloadEmpty 只把缺省、非数组、空数组当空'
    );

    $thrown = '';
    try {
        \app\common\service\hotspot\PoolService::stock(7, [
            'persona_id' => 0,
            'topic' => '某热点话题',
            'platform' => 'douyin',
            'analysis' => [],
            'analysis_id' => 0,
        ]);
    } catch (\app\common\service\hotspot\HotspotUpstreamException $e) {
        $thrown = $e->getMessage();
    } catch (\Throwable $e) {
        $thrown = get_class($e) . ':' . $e->getMessage();
    }
    $check($thrown === '分析不能为空', '入库/更新入库在查重前拒绝空 analysis：' . $thrown);

    \app\common\service\hotspot\HotspotLog::setTestCapture(true);
    $logged = \app\api\logic\hotspot\HotspotLogic::stock([
        'topic' => '某热点话题',
        'platform' => 'douyin',
        'persona_id' => 3,
        'analysis' => ['fit_reason' => '适合讲观点'],
        'script' => '口播正文',
        'token' => 'secret-token',
    ], 0);
    $logs = \app\common\service\hotspot\HotspotLog::testLogs();
    \app\common\service\hotspot\HotspotLog::setTestCapture(false);
    $line = $logs[0] ?? '';
    $check(
        $logged === false
        && str_contains($line, '热点库入库请求：用户=0')
        && str_contains($line, '"topic":"某热点话题"')
        && str_contains($line, '"persona_id":3')
        && str_contains($line, '"fit_reason":"适合讲观点"')
        && str_contains($line, '"script":"口播正文"')
        && str_contains($line, '"token":"***"')
        && !str_contains($line, 'secret-token'),
        '入库请求日志写入全部参数并打码 token：' . $line
    );

    echo "passed={$passed} failed={$failed}\n";
    exit($failed > 0 ? 1 : 0);
}
