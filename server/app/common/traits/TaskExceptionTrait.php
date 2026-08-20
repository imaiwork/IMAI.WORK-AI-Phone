<?php

declare(strict_types=1);

namespace app\common\traits;

use app\common\model\sv\SvDeviceTask;

trait TaskExceptionTrait
{
    /**
     * 获取发布失败的备注
     * @param SvDeviceTask $task
     * @return string
     */
    protected function getPublishRemark(SvDeviceTask $task): string
    {
        // 已有服务端系统失败原因(如算力不足)时优先保留, 避免被设备进度日志覆盖
        $existing = trim((string)($task->remark ?? ''));
        if ($existing !== '' && (str_contains($existing, '算力不足') || str_starts_with($existing, '任务执行失败'))) {
            return $existing;
        }

        //查询发布log
        $lastMsg = \app\common\model\sv\SvDeviceTaskLog::where('task_id', $task->id)
            ->where('task_source', $task->source)
            ->where('device_code', $task->device_code)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($lastMsg->isEmpty()) {
            return '执行过程中设备断网导致任务中断';
        }
        if (trim($lastMsg->message) !== '发布成功') {
            return  $lastMsg->message;
        }
        return '服务进程异常，请重启守护进程';
    }


    /**
     * 获取超时失败的备注
     * @param SvDeviceTask $task
     * @return string
     */
    protected function getTimeoutRemark(SvDeviceTask $task): string
    {

        \think\facade\Cache::store('redis')->handler()->select(intval(env('redis.WS_SELECT', 8)));
        $status = \think\facade\Cache::store('redis')->handler()->get("xhs:device:{$task->device_code}:status");
        //\think\facade\Log::channel('auto')->write($task->device_code . '设备状态:' . $status, 'create');
        if(empty($status)){
            return '设备离线';
        }
        if (unserialize($status) !== 'online') {
            return '设备离线';
        }
        return '执行任务的设备没有提前开启';
    }
}
