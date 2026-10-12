<?php

declare(strict_types=1);

namespace app\common\traits;

use app\common\model\sv\SvDeviceTask;
use app\common\service\sv\PublishRemarkGuard;

trait TaskExceptionTrait
{
    /**
     * 获取发布失败的备注
     * @param SvDeviceTask $task
     * @return string
     */
    protected function getPublishRemark(SvDeviceTask $task): string
    {
        $existing = trim((string)($task->remark ?? ''));
        if (PublishRemarkGuard::isBillingLocked($existing)) {
            return $existing;
        }

        $lastMsg = \app\common\model\sv\SvDeviceTaskLog::where('task_id', $task->id)
            ->where('task_source', $task->source)
            ->where('device_code', $task->device_code)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        $message = $lastMsg->isEmpty() ? '' : trim((string)$lastMsg->message);

        return PublishRemarkGuard::failInflightRemark($message, $existing);
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
