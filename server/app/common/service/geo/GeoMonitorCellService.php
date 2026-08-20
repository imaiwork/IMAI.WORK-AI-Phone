<?php

namespace app\common\service\geo;

use app\common\enum\user\AccountLogEnum;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoTask;
use think\facade\Db;
use think\facade\Log;

/**
 * 监测 cell(场景问题 × 引擎)的执行与批次回写。
 * cron(geo_monitor_cron)与残留的 GeoMonitorCellJob 共用,避免两套结算口径。
 */
class GeoMonitorCellService
{
    /**
     * 创建 monitor_batch 并写入 pending cells,不入队。
     *
     * @param list<array{id:int,value:string,topic_id:int}> $questions
     * @param list<string> $engines
     * @return array{batch_task_id:int,queued:int,total:int,since:int,question_count:int,engine_count:int}
     */
    public static function createBatch(int $userId, int $projectId, array $questions, array $engines, string $source = '一键诊断'): array
    {
        $cells = [];
        foreach ($questions as $ques) {
            foreach ($engines as $eng) {
                $engine = is_array($eng) ? (string)($eng['key'] ?? '') : (string)$eng;
                if ($engine === '') {
                    continue;
                }
                $cells[] = [
                    'keyword_id' => (int)$ques['id'],
                    'topic_id'   => (int)($ques['topic_id'] ?? 0),
                    'query'      => (string)$ques['value'],
                    'engine'     => $engine,
                    'status'     => 'pending',
                    'attempts'   => 0,
                    'claimed_at' => 0,
                ];
            }
        }
        $total = count($cells);
        $since = time();
        $batch = GeoTask::create([
            'project_id' => $projectId,
            'task_type' => 'monitor_batch',
            'status' => $total > 0 ? 'running' : 'failed',
            'input' => json_encode([
                'user_id' => $userId,
                'source' => $source,
                'engines' => array_values(array_map(
                    fn($e) => is_array($e) ? (string)($e['key'] ?? '') : (string)$e,
                    $engines
                )),
                'keyword_ids' => array_map('intval', array_column($questions, 'id')),
                'since' => $since,
                'total' => $total,
                'cells' => $cells,
            ], JSON_UNESCAPED_UNICODE),
            'result_ref' => json_encode(['completed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0], JSON_UNESCAPED_UNICODE),
            'logs' => json_encode([], JSON_UNESCAPED_UNICODE),
            'create_time' => $since,
            'update_time' => $since,
        ]);
        return [
            'batch_task_id' => (int)$batch->id,
            'queued' => $total,
            'total' => $total,
            'since' => $since,
            'question_count' => count($questions),
            'engine_count' => count($engines),
        ];
    }

    /**
     * 执行一个 cell。终态会回写批次进度;引擎抖动抛异常供调用方决定是否重试。
     *
     * @return string success|failed|skipped
     */
    public static function runOne(array $data): string
    {
        $p = GeoProject::findOrEmpty((int)($data['project_id'] ?? 0));
        if ($p->isEmpty()) {
            self::writebackBatchProgress($data, 'failed');
            return 'failed';
        }

        $uid = (int)($data['user_id'] ?? 0);
        $settleRef = self::settleRef($data);
        if ($settleRef !== '' && GeoChargeService::hasSettled($uid, AccountLogEnum::TOKENS_DEC_GEO_MONITOR, $settleRef)) {
            self::writebackBatchProgress($data, 'success');
            return 'success';
        }

        $unit = GeoChargeService::estimate('geo_monitor');
        if ($unit > 0 && \app\common\service\TeamBillingService::spendableTokens($uid) < $unit) {
            Log::warning('GeoMonitorCellService: 余额不足,放弃该 cell', ['data' => $data]);
            self::writebackBatchProgress($data, 'skipped');
            return 'skipped';
        }

        GeoMonitorService::resetUsage();
        $row = GeoMonitorService::run($p, (string)$data['query'], (string)$data['engine'], [
            'keyword_id' => (int)($data['keyword_id'] ?? 0),
            'topic_id'   => (int)($data['topic_id'] ?? 0),
        ]);
        if (str_starts_with((string)($row['raw_answer'] ?? ''), '【模拟数据】')) {
            self::writebackBatchProgress($data, 'skipped');
            return 'skipped';
        }

        $usage = GeoMonitorService::usage();
        $ref = $settleRef !== '' ? $settleRef : ('auto_monitor:' . ($row['id'] ?? ''));
        if ((int)($usage['total'] ?? 0) <= 0 && GeoChargeService::enabled()) {
            if (!empty($row['id'])) {
                GeoMonitor::where('id', (int)$row['id'])->delete();
            }
            Log::warning('GeoMonitorCellService: 无 usage,已回滚快照且不扣费', ['data' => $data]);
            self::writebackBatchProgress($data, 'failed');
            return 'failed';
        }

        $settled = false;
        try {
            GeoChargeService::settleByUsage(
                $uid,
                'geo_monitor',
                $usage,
                $ref,
                [
                    '来源' => (string)($data['source'] ?? 'GEO监测'),
                    '引擎' => (string)$data['engine'],
                ]
            );
            $settled = true;
        } catch (\Throwable $se) {
            GeoChargeService::refund($uid, 'geo_monitor', $ref);
            if (!empty($row['id'])) {
                GeoMonitor::where('id', (int)$row['id'])->delete();
            }
            Log::error('GeoMonitorCellService 结算失败(已回滚快照/退费,不重跑): ' . $se->getMessage(), ['data' => $data]);
        }
        $outcome = $settled ? 'success' : 'skipped';
        self::writebackBatchProgress($data, $outcome);
        return $outcome;
    }

    /**
     * 稳定结算键:batch 用 batch_task_id;无批次时用项目+问题+引擎+日期。
     */
    public static function settleRef(array $data): string
    {
        $projectId = (int)($data['project_id'] ?? 0);
        $keywordId = (int)($data['keyword_id'] ?? 0);
        $engine = (string)($data['engine'] ?? '');
        $batchTaskId = (int)($data['batch_task_id'] ?? 0);
        if ($projectId <= 0 || $engine === '') {
            return '';
        }
        if ($batchTaskId > 0) {
            return sprintf('geo_cell:b%d:%d:%s', $batchTaskId, $keywordId, $engine);
        }
        return sprintf('geo_cell:d%d:%d:%s:%s', $projectId, $keywordId, $engine, date('Ymd'));
    }

    /**
     * 批次进度回写。skipped 计入 completed 与 skipped/failed,避免虚报诊断成功。
     */
    public static function writebackBatchProgress(array $data, string $outcome): void
    {
        $batchTaskId = (int)($data['batch_task_id'] ?? 0);
        if ($batchTaskId <= 0) {
            return;
        }
        try {
            Db::transaction(function () use ($batchTaskId, $outcome) {
                $row = Db::name('geo_task')->where('id', $batchTaskId)
                    ->where('task_type', 'monitor_batch')
                    ->lock(true)->find();
                if (!$row) {
                    return;
                }
                $ref = json_decode((string)($row['result_ref'] ?? ''), true) ?: [];
                self::applyOutcome($ref, $outcome);
                $update = [
                    'result_ref' => json_encode($ref, JSON_UNESCAPED_UNICODE),
                    'update_time' => time(),
                ];
                $input = json_decode((string)($row['input'] ?? ''), true) ?: [];
                $status = self::finalizeBatchStatus($ref, (int)($input['total'] ?? 0));
                if ($status !== null) {
                    $update['status'] = $status;
                }
                Db::name('geo_task')->where('id', $batchTaskId)->update($update);
            });
        } catch (\Throwable $e) {
            Log::error('GeoMonitorCellService 批次进度回写失败: ' . $e->getMessage(), [
                'batch_task_id' => $batchTaskId,
                'outcome' => $outcome,
            ]);
        }
    }

    public static function applyOutcome(array &$ref, string $outcome): void
    {
        $ref['completed'] = (int)($ref['completed'] ?? 0) + 1;
        if ($outcome === 'success') {
            $ref['success'] = (int)($ref['success'] ?? 0) + 1;
        } elseif ($outcome === 'failed') {
            $ref['failed'] = (int)($ref['failed'] ?? 0) + 1;
        } elseif ($outcome === 'skipped') {
            $ref['skipped'] = (int)($ref['skipped'] ?? 0) + 1;
            $ref['failed'] = (int)($ref['failed'] ?? 0) + 1;
        }
    }

    public static function finalizeBatchStatus(array $ref, int $total): ?string
    {
        if ($total > 0 && (int)($ref['completed'] ?? 0) >= $total) {
            return (int)($ref['success'] ?? 0) > 0 ? 'success' : 'failed';
        }
        return null;
    }
}
