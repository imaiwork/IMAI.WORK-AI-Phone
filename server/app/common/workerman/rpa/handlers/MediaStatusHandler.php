<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\sv\SvDeviceTask;
use app\common\workerman\rpa\WorkerEnum;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDeviceLog;
use app\common\model\sv\SvDeviceTaskLog;
use app\common\service\sv\PublishRemarkGuard;



class MediaStatusHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->publishPlatform = $content['publish_platform'] ?? 0;

            $mediaId = (int)($content['material_id'] ?? 0);
            $status = (int)($content['status'] ?? 0);
            $isWechat = $this->publishPlatform === DeviceEnum::PUBLISH_PLATFORM_WX;
            if ($isWechat) {
                $media = AiWechatCircleTask::where('id', $mediaId)->findOrEmpty();
                $where = [
                    ['sub_task_id', '=', $media->task_config_id],
                    ['sub_data_id', '=', $media->id],
                    ['device_code', '=', $media->device_code],
                    ['account', '=', $media->wechat_id],
                ];
            } else {
                $media = SvPublishSettingDetail::where('id', $mediaId)->findOrEmpty();
                $where = [
                    ['sub_task_id', '=', $media->publish_account_id],
                    ['sub_data_id', '=', $media->id],
                    ['device_code', '=', $media->device_code],
                    ['account', '=', $media->account],
                ];
            }

            $task = $this->findDeviceTask($where, $payload, $mediaId);
            if ($this->shouldForceTimeoutFail($task, $status)) {
                $remark = $this->applyTimeoutFail($task, $media, $content, $status, $isWechat);
                $this->writeTaskLog($task, $content, $remark);
                $this->payload['reply'] = '发布数据状态已更新';
                $this->recordDeviceLog($content, $media, $task);
                $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                return;
            }

            if ($isWechat) {
                $wechatCurrent = $media->isEmpty() ? 0 : ((int)$media->send_status === 2 ? 1 : ((int)$media->send_status === 3 ? 2 : 0));
                if (!$media->isEmpty() && in_array($status, [1, 2], true)
                    && PublishRemarkGuard::shouldApplyRpaDetailStatus($status, $wechatCurrent, (string)$media->remark)) {
                    $media->send_status = $status === 1 ? 2 : 3;
                    $media->remark = 'RPA执行：' . ($content['msg'] ?? '发布失败');
                    $media->update_time = time();
                    $media->finish_time = date('Y-m-d H:i', time());
                    $media->save();
                }
            } elseif (!$media->isEmpty()) {
                if (PublishRemarkGuard::shouldApplyRpaDetailStatus($status, (int)$media->status, (string)$media->remark)) {
                    $media->status = $status;
                    $media->remark = 'RPA执行：' . ($content['msg'] ?? '');
                    $media->update_time = time();
                    $media->exec_time = time();
                    $media->save();
                }
                $find = SvPublishSettingDetail::where('publish_account_id', $media->publish_account_id)->where('status', 'in', [0, 3])->findOrEmpty();
                if ($find->isEmpty() && ((int)$status === 1 || (int)$status === 2)) {
                    SvPublishSettingAccount::where('id', $media->publish_account_id)->update([
                        'status' => 2,
                        'update_time' => time()
                    ]);
                    SvPublishSetting::where('id', $media->publish_id)->update([
                        'status' => 3,
                        'update_time' => time()
                    ]);
                }
            }

            // 主任务状态修改
            if (!$task->isEmpty()) {
                $maps = [
                    1 => DeviceEnum::TASK_STATUS_FINISHED,
                    2 => DeviceEnum::TASK_STATUS_FAILED,
                    3 => DeviceEnum::TASK_STATUS_RUNNING,
                ];

                if (PublishRemarkGuard::shouldApplyRpaTaskStatus($status, (int)$task->status, (string)$task->remark)) {
                    $task->status = $maps[$status] ?? DeviceEnum::TASK_STATUS_RUNNING;
                    $task->remark = $content['msg'] ?? '';
                    $task->update_time = time();
                    $task->save();
                }

                $this->writeTaskLog($task, $content);
            }
            $this->payload['reply'] = '发布数据状态已更新';
            $this->recordDeviceLog($content, $media, $task);
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'cron');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function findDeviceTask(array $where, array $payload, int $mediaId): SvDeviceTask
    {
        $task = SvDeviceTask::where($where)->findOrEmpty();
        if (!$task->isEmpty()) {
            return $task;
        }

        $deviceCode = (string)($payload['deviceId'] ?? '');
        if ($deviceCode === '' || $mediaId <= 0) {
            return $task;
        }

        return SvDeviceTask::where('device_code', $deviceCode)
            ->where('sub_data_id', $mediaId)
            ->findOrEmpty();
    }

    private function shouldForceTimeoutFail(SvDeviceTask $task, int $incoming): bool
    {
        if ($task->isEmpty()) {
            return false;
        }
        if ((int)$task->end_time >= time()) {
            return false;
        }
        if ($incoming === 1) {
            return false;
        }
        if ((int)$task->status === DeviceEnum::TASK_STATUS_FINISHED) {
            return false;
        }
        if (PublishRemarkGuard::isBillingLocked((string)$task->remark)) {
            return false;
        }

        return true;
    }

    private function applyTimeoutFail(
        SvDeviceTask $task,
        AiWechatCircleTask|SvPublishSettingDetail $media,
        array $content,
        int $status,
        bool $isWechat
    ): string {
        $now = time();
        $remark = '任务执行超时';
        if ($status === 2) {
            $msg = trim((string)($content['msg'] ?? ''));
            if ($msg !== '') {
                $remark = 'RPA执行：' . $msg;
            }
        }

        $task->status = DeviceEnum::TASK_STATUS_FAILED;
        $task->remark = $remark;
        $task->update_time = $now;
        $task->save();

        if ($media->isEmpty()) {
            return $remark;
        }

        if ($isWechat) {
            if ((int)$media->send_status !== 2) {
                $media->send_status = 3;
                $media->remark = $remark;
                $media->update_time = $now;
                $media->finish_time = date('Y-m-d H:i', $now);
                $media->save();
            }
            return $remark;
        }

        if ((int)$media->status !== 1) {
            $media->status = 2;
            $media->remark = $remark;
            $media->update_time = $now;
            $media->exec_time = $now;
            $media->save();
        }

        $find = SvPublishSettingDetail::where('publish_account_id', $media->publish_account_id)
            ->where('status', 'in', [0, 3])
            ->findOrEmpty();
        if ($find->isEmpty()) {
            SvPublishSettingAccount::where('id', $media->publish_account_id)->update([
                'status' => 2,
                'update_time' => $now,
            ]);
            SvPublishSetting::where('id', $media->publish_id)->update([
                'status' => 3,
                'update_time' => $now,
            ]);
        }

        return $remark;
    }

    private function writeTaskLog(SvDeviceTask $task, array $content, ?string $message = null): void
    {
        if ($task->isEmpty()) {
            return;
        }

        SvDeviceTaskLog::create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'task_source' => $task->source,
            'device_code' => $task->device_code,
            'message' => $message ?? ($content['msg'] ?? ''),
            'image' => $content['imageUrl'] ?? '',
            'create_time' => time(),
        ]);
    }

    private function recordDeviceLog(array $content, AiWechatCircleTask|SvPublishSettingDetail $media, SvDeviceTask $task): void
    {
        try {
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }

            // {"log": "筛选完成 -- 查看视频", "tag": "寻找爆款 STEP_13", "image": ""}
            SvDeviceLog::create([
                'user_id' => $task->user_id,
                'device_code' => $task->device_code,
                'app_type' => $this->payload['appType'] ?? $this->publishPlatform ?? 0,
                'content' => [
                    'msg' => $content['msg'] ?? '',
                    'title' => '发布状态',
                    'info' => $content,
                    'image' => $content['imageUrl'] ?? '',
                ],
                'app_version' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'day' => date('Y-m-d'),
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            $this->setLog('MediaStatusHandler recordDeviceLog error: ' . $e->getMessage(), 'error');
        }
    }

}
