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

            $mediaId = $content['material_id'] ?? 0;
            $status = $content['status'] ?? 0;
            $where = [];
            if ($this->publishPlatform === DeviceEnum::PUBLISH_PLATFORM_WX) {
                $media = AiWechatCircleTask::where('id', $mediaId)->findOrEmpty();
                if (!$media->isEmpty()) {
                    $media->send_status = $status === 1 ? 2 : 3;
                    $media->remark = 'RPA执行：' . ($content['msg'] ?? '发布失败');
                    $media->update_time = time();
                    $media->finish_time = date('Y-m-d H:i', time());
                    $media->save();
                }
                $where = [
                    ['sub_task_id', '=', $media->task_config_id],
                    ['sub_data_id', '=', $media->id],
                    ['device_code', '=', $media->device_code],
                    ['account', '=', $media->wechat_id],
                ];
            } else {
                $media = SvPublishSettingDetail::where('id', $mediaId)->findOrEmpty();
                if (!$media->isEmpty()) {
                    // 服务端已写入算力不足等系统失败时, 禁止设备进度文案覆盖失败原因
                    $protectRemark = $this->isProtectedFailureRemark((string)$media->remark);
                    if (!$protectRemark) {
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
                $where = [
                    ['sub_task_id', '=', $media->publish_account_id],
                    ['sub_data_id', '=', $media->id],
                    ['device_code', '=', $media->device_code],
                    ['account', '=', $media->account],
                ];
            }

            // 主任务状态修改
            $task = SvDeviceTask::where($where)->findOrEmpty();
            if (!$task->isEmpty()) {
                $maps = [
                    1 => DeviceEnum::TASK_STATUS_FINISHED,
                    2 => DeviceEnum::TASK_STATUS_FAILED,
                    3 => DeviceEnum::TASK_STATUS_RUNNING,
                ];

                $protectTaskRemark = $this->isProtectedFailureRemark((string)$task->remark);
                if (!$protectTaskRemark) {
                    $task->status = $maps[$status] ?? DeviceEnum::TASK_STATUS_RUNNING;
                    $task->remark = $content['msg'] ?? '';
                    $task->update_time = time();
                    $task->save();
                }

                // 记录日志
                SvDeviceTaskLog::create([
                    'user_id' => $task->user_id,
                    'task_id' => $task->id,
                    'task_source' => $task->source,
                    'device_code' => $task->device_code,
                    'message' => $content['msg'] ?? '',
                    'image' => $content['imageUrl'] ?? '',
                    'create_time' => time(),
                ]);
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

    /** 服务端系统失败原因, 不允许被设备进度文案覆盖 */
    private function isProtectedFailureRemark(string $remark): bool
    {
        $remark = trim($remark);
        if ($remark === '') {
            return false;
        }
        return str_contains($remark, '算力不足')
            || str_starts_with($remark, '任务执行失败');
    }
}
