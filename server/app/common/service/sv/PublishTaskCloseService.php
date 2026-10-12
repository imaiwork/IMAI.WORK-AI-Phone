<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceTaskLog;
use app\common\model\sv\SvPublishSettingDetail;

/**
 * 发布任务到期收口（有 IO）。调度器只负责窗口判断和通知。
 */
class PublishTaskCloseService
{
    /**
     * @param callable(SvDeviceTask):string $timeoutRemark
     */
    public static function close(SvDeviceTask $task, callable $timeoutRemark): void
    {
        $task = self::reloadTask($task);
        $publish = self::loadDetail($task);
        $decision = self::decide($task, $publish);

        // 失败路径写库前再读一次，避免 RPA 成功回写被默认断网盖掉。
        $action = (string)($decision['action'] ?? '');
        if (in_array($action, [
            PublishTaskReconcile::CLOSE_FAIL_INFLIGHT,
            PublishTaskReconcile::CLOSE_TIMEOUT,
            PublishTaskReconcile::CLOSE_FAIL_DETAIL,
        ], true)) {
            $task = self::reloadTask($task);
            $publish = self::loadDetail($task);
            $decision = self::decide($task, $publish);
        }

        $now = time();
        $action = (string)($decision['action'] ?? '');
        if ($action === PublishTaskReconcile::CLOSE_FAIL_INFLIGHT) {
            $inflightRemark = PublishRemarkGuard::failInflightRemark(
                self::lastLogMessage($task),
                (string)$task->remark
            );
            $action = PublishTaskReconcile::resolveAfterInflightRemark($action, $inflightRemark);
            if ($action === PublishTaskReconcile::CLOSE_SUCCESS_FROM_LOG) {
                self::applySuccessFromLog($task, $publish, $now);
                return;
            }
            self::applyFailInflight($task, $publish, $inflightRemark, $now);
            return;
        }

        switch ($action) {
            case PublishTaskReconcile::CLOSE_BILLING:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->update_time = $now;
                $task->save();
                if (!empty($decision['fail_inflight_detail']) && !$publish->isEmpty()) {
                    $publish->status = 2;
                    $publish->remark = (string)$task->remark;
                    $publish->update_time = $now;
                    $publish->save();
                }
                break;
            case PublishTaskReconcile::CLOSE_FINISH:
                $task->status = DeviceEnum::TASK_STATUS_FINISHED;
                $remark = $publish->isEmpty() ? '' : trim((string)$publish->remark);
                $task->remark = $remark !== '' ? $remark : PublishRemarkGuard::SUCCESS_REMARK;
                $task->update_time = $now;
                $task->save();
                break;
            case PublishTaskReconcile::CLOSE_FAIL_DETAIL:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = $publish->isEmpty() ? (string)$task->remark : (string)$publish->remark;
                $task->update_time = $now;
                $task->save();
                break;
            case PublishTaskReconcile::CLOSE_SUCCESS_FROM_LOG:
                self::applySuccessFromLog($task, $publish, $now);
                break;
            default:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = $timeoutRemark($task);
                $task->update_time = $now;
                $task->save();
                break;
        }
    }

    /**
     * @return array{action:string,fail_inflight_detail?:bool}
     */
    private static function decide(SvDeviceTask $task, SvPublishSettingDetail $publish): array
    {
        $detailStatus = $publish->isEmpty() ? null : (int)$publish->status;
        return PublishTaskReconcile::decideClose(
            (string)$task->remark,
            $detailStatus,
            self::lastLogMessage($task),
            (int)$task->status
        );
    }

    private static function applySuccessFromLog(SvDeviceTask $task, SvPublishSettingDetail $publish, int $now): void
    {
        if (!$publish->isEmpty()) {
            $publish->status = 1;
            $publish->remark = PublishRemarkGuard::SUCCESS_REMARK;
            $publish->update_time = $now;
            $publish->exec_time = $now;
            $publish->save();
        }
        $task->status = DeviceEnum::TASK_STATUS_FINISHED;
        $task->remark = PublishRemarkGuard::SUCCESS_REMARK;
        $task->update_time = $now;
        $task->save();
    }

    private static function applyFailInflight(
        SvDeviceTask $task,
        SvPublishSettingDetail $publish,
        string $remark,
        int $now
    ): void {
        if (!$publish->isEmpty()) {
            $publish->status = 2;
            $publish->remark = $remark;
            $publish->update_time = $now;
            $publish->save();
        }
        $task->status = DeviceEnum::TASK_STATUS_FAILED;
        $task->remark = $remark;
        $task->update_time = $now;
        $task->save();
    }

    private static function loadDetail(SvDeviceTask $task): SvPublishSettingDetail
    {
        return SvPublishSettingDetail::where('id', $task->sub_data_id)->findOrEmpty();
    }

    private static function reloadTask(SvDeviceTask $task): SvDeviceTask
    {
        $fresh = SvDeviceTask::where('id', $task->id)->findOrEmpty();
        if ($fresh->isEmpty()) {
            return $task;
        }

        $task->status = $fresh->status;
        $task->remark = $fresh->remark;
        $task->update_time = $fresh->update_time;

        return $task;
    }

    private static function lastLogMessage(SvDeviceTask $task): string
    {
        $lastMsg = SvDeviceTaskLog::where('task_id', $task->id)
            ->where('task_source', $task->source)
            ->where('device_code', $task->device_code)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($lastMsg->isEmpty()) {
            return '';
        }

        return trim((string)$lastMsg->message);
    }
}
