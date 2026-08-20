<?php

namespace app\common\service\geo;

use app\common\enum\user\AccountLogEnum;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoTask;
use think\facade\Db;
use think\facade\Log;

/**
 * 监测 cell 定时执行:扫 running 的 monitor_batch,CAS 抢 pending cell 后在 cron 进程内执行。
 * 对标 GeoSiteService::runDue:单轮上限、单条失败不中断、剩余下轮继续。
 */
class GeoMonitorCronService
{
    /** 单轮最多执行几个 cell(中台一次 7–40s,不能抄视频轮询的 50) */
    // 单轮上限只作失控兜底,真正的限流是 TIME_BUDGET(联网监测单 cell 7~40s,
    // 每分钟实际能跑 2~6 个)。旧值 2 会让 180 cell 的批次拖到 90 分钟,
    // 进度条长期 finished=false 被当成 bug。可用 env geo.monitor_max_per_run 覆盖。
    protected const MAX_PER_RUN = 30;

    /** 单轮时间预算(秒),避免拖死同一次 php think crontab */
    protected const TIME_BUDGET = 50;

    /** running 超过此时长视为进程中途挂了,回收后重试或标失败 */
    protected const STALE_SECONDS = 600;

    /**
     * @return array{handled:int,success:int,failed:int,skipped:int,capped:bool}
     */
    public static function runDue(): array
    {
        $s = ['handled' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'capped' => false];
        $deadline = time() + self::TIME_BUDGET;
        $batches = GeoTask::where('task_type', 'monitor_batch')
            ->where('status', 'running')
            ->order('id', 'asc')
            ->select();
        if ($batches->isEmpty()) {
            return $s;
        }

        $list = [];
        foreach ($batches as $batch) {
            // 先清算:僵尸批次(无 cell 清单)与"全 cell 已终态但状态没收口"的批次
            // 直接落终态,否则 status 恒为 running,进度轮询永远 finished=false
            if (self::reconcileBatch($batch)) {
                continue;
            }
            $list[] = $batch;
        }
        $n = count($list);
        // 轮转偏移必须跨轮持久化:局部变量每分钟归零会让每轮都从队列头开始领,
        // 头部的大批次永远吃满单轮预算,后面的批次(含每日自动监测)长期饿死
        // (实测:凌晨批次 7 小时 0 进展,cell 全部流向队列头部的项目)
        $offset = (int)\think\facade\Cache::get('geo_monitor_cron_offset', 0) % max(1, $n);
        $max = max(1, (int)env('geo.monitor_max_per_run', self::MAX_PER_RUN));

        while ($s['handled'] < $max && time() < $deadline) {
            $claimed = null;
            for ($i = 0; $i < $n; $i++) {
                $idx = ($offset + $i) % $n;
                try {
                    $claimed = self::claimNext($list[$idx]);
                } catch (\Throwable $e) {
                    // 单个批次领取异常绝不能毒死整轮轮转:一个坏批次抛异常会让
                    // 其后所有批次(含每日自动监测)每分钟都颗粒无收
                    Log::error('geo_monitor_cron claimNext 异常,跳过该批次: ' . $e->getMessage(), [
                        'batch_id' => (int)$list[$idx]->id,
                    ]);
                    $claimed = null;
                }
                if ($claimed) {
                    $offset = ($idx + 1) % $n;
                    break;
                }
            }
            if (!$claimed) {
                break;
            }

            try {
                $outcome = GeoMonitorCellService::runOne($claimed['data']);
                self::updateCellStatus($claimed['batch_task_id'], $claimed['index'], $outcome);
                if (isset($s[$outcome])) {
                    $s[$outcome]++;
                }
            } catch (\Throwable $e) {
                Log::error('geo_monitor_cron cell 失败: ' . $e->getMessage(), [
                    'batch_task_id' => $claimed['batch_task_id'],
                    'index' => $claimed['index'],
                    'attempts' => $claimed['attempts'],
                ]);
                // 永久性错误(参数/鉴权/接口不存在)重试也必然失败,却会让中台再产生
                // 一次真实上游成本——只对疑似抖动(超时/网络)的错误保留重试
                $msg = $e->getMessage();
                $permanent = (bool)preg_match('/参数|Unrecognized|鉴权|无权|签名|40000|401|403|404/i', $msg);
                if (!$permanent && (int)$claimed['attempts'] < 2) {
                    self::updateCellStatus($claimed['batch_task_id'], $claimed['index'], 'pending');
                } else {
                    GeoMonitorCellService::writebackBatchProgress($claimed['data'], 'failed');
                    self::updateCellStatus($claimed['batch_task_id'], $claimed['index'], 'failed');
                    $s['failed']++;
                }
            }
            $s['handled']++;
        }

        \think\facade\Cache::set('geo_monitor_cron_offset', $offset, 86400);
        $s['capped'] = self::hasPending($list);
        return $s;
    }

    /**
     * 行锁取下一个 pending;顺带回收超时 running。
     *
     * @return array{batch_task_id:int,index:int,attempts:int,data:array}|null
     */
    /**
     * 批次清算:返回 true 表示批次已落终态,不再参与 cell 领取。
     *  - 无 cell 清单(旧版/异常数据):永远不会被 claimNext 领取,直接判失败终止;
     *  - 全部 cell 已终态但批次仍 running(进度回写丢失):按 cell 实况重算并收口。
     */
    protected static function reconcileBatch(GeoTask $batch): bool
    {
        $input = json_decode((string)$batch->input, true) ?: [];
        $cells = (array)($input['cells'] ?? []);
        if (!$cells) {
            $batch->status = 'failed';
            $batch->result_ref = json_encode(
                ['completed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'note' => '批次无cell清单(旧版数据),已终止'],
                JSON_UNESCAPED_UNICODE
            );
            $batch->update_time = time();
            $batch->save();
            return true;
        }
        $pendingOrRunning = 0;
        $counts = ['completed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($cells as $c) {
            $st = (string)($c['status'] ?? 'pending');
            if ($st === 'pending' || $st === 'running') {
                $pendingOrRunning++;
                continue;
            }
            $counts['completed']++;
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }
        if ($pendingOrRunning > 0) {
            return false;
        }
        $batch->status = $counts['success'] > 0 ? 'success' : 'failed';
        $batch->result_ref = json_encode($counts, JSON_UNESCAPED_UNICODE);
        $batch->update_time = time();
        $batch->save();
        return true;
    }

    protected static function claimNext(GeoTask $batch): ?array
    {
        $batchId = (int)$batch->id;
        return Db::transaction(function () use ($batchId) {
            $row = Db::name('geo_task')->where('id', $batchId)
                ->where('task_type', 'monitor_batch')
                ->where('status', 'running')
                ->lock(true)->find();
            if (!$row) {
                return null;
            }
            $input = json_decode((string)($row['input'] ?? ''), true) ?: [];
            $cells = $input['cells'] ?? [];
            if (!$cells) {
                return null;
            }
            $ref = json_decode((string)($row['result_ref'] ?? ''), true) ?: [];
            $userId = self::batchUserId($row, $input);
            $now = time();
            $dirty = false;

            foreach ($cells as $i => $cell) {
                if (($cell['status'] ?? '') !== 'running') {
                    continue;
                }
                if ((int)($cell['claimed_at'] ?? 0) > $now - self::STALE_SECONDS) {
                    continue;
                }
                $data = self::cellData($row, $input, $cell, $userId);
                $settleRef = GeoMonitorCellService::settleRef($data);
                if ($settleRef !== '' && GeoChargeService::hasSettled(
                    $userId,
                    AccountLogEnum::TOKENS_DEC_GEO_MONITOR,
                    $settleRef
                )) {
                    $cells[$i]['status'] = 'success';
                } elseif ((int)($cell['attempts'] ?? 0) >= 2) {
                    $cells[$i]['status'] = 'failed';
                    GeoMonitorCellService::applyOutcome($ref, 'failed');
                } else {
                    $cells[$i]['status'] = 'pending';
                }
                $dirty = true;
            }

            $picked = null;
            foreach ($cells as $i => $cell) {
                if (($cell['status'] ?? '') !== 'pending') {
                    continue;
                }
                $cells[$i]['status'] = 'running';
                $cells[$i]['attempts'] = (int)($cell['attempts'] ?? 0) + 1;
                $cells[$i]['claimed_at'] = $now;
                $picked = [
                    'batch_task_id' => $batchId,
                    'index' => $i,
                    'attempts' => (int)$cells[$i]['attempts'],
                    'data' => self::cellData($row, $input, $cells[$i], $userId),
                ];
                $dirty = true;
                break;
            }

            if ($dirty) {
                $input['cells'] = $cells;
                $update = [
                    'input' => json_encode($input, JSON_UNESCAPED_UNICODE),
                    'result_ref' => json_encode($ref, JSON_UNESCAPED_UNICODE),
                    'update_time' => $now,
                ];
                $status = GeoMonitorCellService::finalizeBatchStatus($ref, (int)($input['total'] ?? 0));
                if ($status !== null) {
                    $update['status'] = $status;
                }
                Db::name('geo_task')->where('id', $batchId)->update($update);
            }
            return $picked;
        });
    }

    protected static function updateCellStatus(int $batchTaskId, int $index, string $status): void
    {
        try {
            Db::transaction(function () use ($batchTaskId, $index, $status) {
                $row = Db::name('geo_task')->where('id', $batchTaskId)
                    ->where('task_type', 'monitor_batch')
                    ->lock(true)->find();
                if (!$row) {
                    return;
                }
                $input = json_decode((string)($row['input'] ?? ''), true) ?: [];
                $cells = $input['cells'] ?? [];
                if (!isset($cells[$index])) {
                    return;
                }
                $cells[$index]['status'] = $status;
                $input['cells'] = $cells;
                Db::name('geo_task')->where('id', $batchTaskId)->update([
                    'input' => json_encode($input, JSON_UNESCAPED_UNICODE),
                    'update_time' => time(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('geo_monitor_cron 更新 cell 状态失败: ' . $e->getMessage(), [
                'batch_task_id' => $batchTaskId,
                'index' => $index,
                'status' => $status,
            ]);
        }
    }

    protected static function cellData(array $row, array $input, array $cell, int $userId): array
    {
        return [
            'project_id' => (int)$row['project_id'],
            'user_id' => $userId,
            'keyword_id' => (int)($cell['keyword_id'] ?? 0),
            'topic_id' => (int)($cell['topic_id'] ?? 0),
            'query' => (string)($cell['query'] ?? ''),
            'engine' => (string)($cell['engine'] ?? ''),
            'batch_task_id' => (int)$row['id'],
            'source' => (string)($input['source'] ?? 'GEO监测'),
        ];
    }

    protected static function batchUserId(array $row, array $input): int
    {
        $uid = (int)($input['user_id'] ?? 0);
        if ($uid > 0) {
            return $uid;
        }
        $p = GeoProject::findOrEmpty((int)$row['project_id']);
        return $p->isEmpty() ? 0 : (int)$p->user_id;
    }

    protected static function hasPending(array $batches): bool
    {
        foreach ($batches as $batch) {
            $fresh = GeoTask::findOrEmpty((int)$batch->id);
            if ($fresh->isEmpty() || (string)$fresh->status !== 'running') {
                continue;
            }
            $input = json_decode((string)$fresh->input, true) ?: [];
            foreach ($input['cells'] ?? [] as $cell) {
                if (in_array((string)($cell['status'] ?? ''), ['pending', 'running'], true)) {
                    return true;
                }
            }
        }
        return false;
    }
}
