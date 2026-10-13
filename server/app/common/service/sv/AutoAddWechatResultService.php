<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDeviceTask;

class AutoAddWechatResultService
{
    public const CALLBACK_GRACE_SECONDS = 300;
    public const UNKNOWN_RESULT = '设备未返回添加结果，请核实后处理';
    public const QUEUED_RETRY_RESULT = '已排入次日重试';

    /** 仅在用户授权的时间到达后调用；不发送消息，也不重置期间已返回的结果。 */
    public static function queueRetry(int $userId, string $deviceCode, array $recordIds, int $lastAttemptBefore, int $notBefore): int
    {
        if (time() < $notBefore || $userId <= 0 || $deviceCode === '' || $recordIds === []) {
            return 0;
        }
        return SvAddWechatRecord::where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->where('id', 'in', $recordIds)
            ->where('status', SvAddWechatRecord::STATUS_RESULT_UNKNOWN)
            ->where('update_time', '<=', $lastAttemptBefore)
            ->update([
                'status' => 4,
                'result' => self::QUEUED_RETRY_RESULT,
                'update_time' => time(),
            ]);
    }

    /** 仅结束等待，不重新发送好友申请。已结束的父任务也必须扫描。 */
    public static function expire(int $now, string $deviceCode = ''): int
    {
        $before = $now - self::CALLBACK_GRACE_SECONDS;
        $records = SvAddWechatRecord::alias('r')
            ->join('sv_device_task t', 't.id = r.exec_task_id AND t.user_id = r.user_id AND t.device_code = r.device_code')
            ->where('r.status', 2)
            ->where('r.dy_search_task_id', 0)
            ->when($deviceCode !== '', static fn($query) => $query->where('r.device_code', $deviceCode))
            ->where('r.update_time', '<=', $before)
            ->where('t.auto_type', 1)
            ->where('t.task_type', DeviceEnum::AUTO_TYPE_WECHAT_FRIEND)
            ->where('t.end_time', '>', 0)
            ->where('t.end_time', '<=', $before)
            ->field('r.id,r.exec_task_id,r.update_time')
            ->order('r.id', 'asc')
            ->limit(200)
            ->select();

        $changed = 0;
        $taskIds = [];
        foreach ($records as $record) {
            // 回调或重新执行已改变记录时，不能覆盖新的结果。
            $changed += SvAddWechatRecord::where('id', $record->id)
                ->where('status', 2)
                ->where('exec_task_id', $record->exec_task_id)
                ->where('update_time', $record->getData('update_time'))
                ->update([
                    'status' => SvAddWechatRecord::STATUS_RESULT_UNKNOWN,
                    'result' => self::UNKNOWN_RESULT,
                    'update_time' => $now,
                ]);
            $taskIds[(int)$record->exec_task_id] = true;
        }
        foreach (array_keys($taskIds) as $taskId) {
            self::refreshTaskRemark($taskId);
        }
        return $changed;
    }

    public static function taskRemark(SvDeviceTask $task): string
    {
        $records = SvAddWechatRecord::where('exec_task_id', $task->id)
            ->where('device_code', $task->device_code)
            ->where('user_id', $task->user_id)
            ->column('status');
        $pending = count(array_filter($records, static fn($status) => in_array(
            (int)$status, [2, SvAddWechatRecord::STATUS_RESULT_UNKNOWN], true
        )));
        if ($pending > 0) {
            return '加微时段已结束，' . $pending . '条结果待确认';
        }
        return $records === [] ? '加微时段已结束，无下发记录' : '加微时段已结束，结果已返回';
    }

    public static function refreshTaskRemark(int $taskId): void
    {
        $task = SvDeviceTask::where('id', $taskId)
            ->where('auto_type', 1)
            ->where('task_type', DeviceEnum::AUTO_TYPE_WECHAT_FRIEND)
            ->where('status', DeviceEnum::TASK_STATUS_FINISHED)
            ->whereNull('delete_time')
            ->findOrEmpty();
        if (!$task->isEmpty()) {
            SvDeviceTask::where('id', $taskId)
                ->where('status', DeviceEnum::TASK_STATUS_FINISHED)
                ->update(['remark' => self::taskRemark($task)]);
        }
    }

    /** 迟到结果可结束“待确认”；重复回调不能覆盖已返回结果。 */
    public static function acceptResult(SvAddWechatRecord $record, array $data): bool
    {
        $changed = SvAddWechatRecord::where('id', $record->id)
            ->where('exec_task_id', $record->exec_task_id)
            ->where(static function ($query) {
                $query->where('status', 'in', [2, SvAddWechatRecord::STATUS_RESULT_UNKNOWN])
                    ->whereOr(static function ($pending) {
                        $pending->where('status', 4)->where('result', self::QUEUED_RETRY_RESULT);
                    });
            })
            ->update($data);
        if ($changed) {
            self::refreshTaskRemark((int)$record->exec_task_id);
        }
        return $changed > 0;
    }
}
