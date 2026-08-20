<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoProject;
use think\facade\Log;

/**
 * 每日自动监测:对开启 auto_monitor 的品牌,每天建一批 monitor_batch
 * (所有启用场景问题 × 所有已接入引擎),等价于用户手动点一次【一键诊断】。
 *
 * 执行模型:cron 命令 geo_daily_monitor 建议每天 03:00 跑一次,
 *  - 用 geo_project.last_auto_date 做"每天最多跑一次"的幂等闸(CAS 抢占防双 cron);
 *  - 只建批次,cell 由 geo_monitor_cron 执行(与一键诊断同口径);
 *  - 余额不足的项目当日整体跳过(不半跑),次日重试;
 *  - 预检按 estimate×cell 数;成功后按中台实际 token 结算(settleByUsage)。
 */
class GeoDailyMonitorService
{
    public static function runDue(): array
    {
        $today = date('Y-m-d');
        $summary = ['projects' => 0, 'queued' => 0, 'skipped' => 0];

        $projects = GeoProject::where('auto_monitor', 1)
            ->where('last_auto_date', '<>', $today)
            ->select();

        $engines = array_values(array_filter(GeoMonitorService::engineList(), fn($e) => !empty($e['available'])));
        $engineKeys = array_values(array_filter(array_map(
            fn($e) => (string)($e['key'] ?? ''),
            $engines
        )));
        if (!$engineKeys) {
            Log::warning('geo_daily_monitor: 无可用监测引擎,本轮跳过');
            return $summary;
        }

        foreach ($projects as $p) {
            try {
                \app\common\service\TeamMemberService::assertActive((int)$p->user_id);
                $questions = GeoKeyword::where('project_id', $p->id)
                    ->where('type', '场景问题')->where('status', 1)
                    ->field('id,value,topic_id')->select()->toArray();

                $prevDate = (string)$p->last_auto_date;
                $claimed = GeoProject::where('id', (int)$p->id)
                    ->where('last_auto_date', '<>', $today)
                    ->update(['last_auto_date' => $today, 'update_time' => time()]);
                if (!$claimed) {
                    continue;
                }

                if (!$questions) {
                    $summary['skipped']++;
                    continue;
                }

                // 防重复:该项目还有进行中的诊断批次(手动诊断/昨日未消化完)就跳过当日,
                // 避免同一批 cell 双份入队拖垮定时任务消费
                $hasRunning = \app\common\model\geo\GeoTask::where('project_id', (int)$p->id)
                    ->where('task_type', 'monitor_batch')
                    ->where('status', 'running')
                    ->count() > 0;
                if ($hasRunning) {
                    Log::warning("geo_daily_monitor: 项目{$p->id} 已有进行中的诊断批次,今日跳过");
                    $summary['skipped']++;
                    continue;
                }

                // 预检只能估:score=200 是 tokens/算力(除数),不能当「每条 200 算力」
                $unit = GeoChargeService::estimate('geo_monitor');
                $need = $unit * count($questions) * count($engineKeys);
                if ($unit > 0 && \app\common\service\TeamBillingService::spendableTokens((int)$p->user_id) < $need) {
                    Log::warning("geo_daily_monitor: 项目{$p->id} 算力不足(需{$need}),今日跳过");
                    $summary['skipped']++;
                    continue;
                }

                try {
                    $batch = GeoMonitorCellService::createBatch(
                        (int)$p->user_id,
                        (int)$p->id,
                        $questions,
                        $engineKeys,
                        '每日自动监测'
                    );
                    $summary['queued'] += (int)$batch['queued'];
                    $summary['projects']++;
                } catch (\Throwable $qe) {
                    GeoProject::where('id', (int)$p->id)->where('last_auto_date', $today)
                        ->update(['last_auto_date' => $prevDate, 'update_time' => time()]);
                    Log::error("geo_daily_monitor: 项目{$p->id} 建批次失败(已回滚落闸): " . $qe->getMessage());
                    $summary['skipped']++;
                }
            } catch (\Throwable $e) {
                Log::error("geo_daily_monitor: 项目{$p->id} 异常: " . $e->getMessage());
                $summary['skipped']++;
            }
        }
        return $summary;
    }
}
