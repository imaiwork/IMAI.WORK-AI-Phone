<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\sv\SvDeviceTask;
use app\common\workerman\rpa\WorkerEnum;
use app\common\enum\DeviceEnum;



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

            // $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":taskStatus", json_encode([
            //     'taskStatus' => 'running',
            //     'taskType' => 'setMediaStatus',
            //     'msg' => '小红书正在更新发布笔记数据状态',
            //     'duration' => 10,
            //     'time' => date('Y-m-d H:i:s', time()),
            // ], JSON_UNESCAPED_UNICODE));

            $mediaId = $content['material_id'] ?? 0;
            $status = $content['status'] ?? 0;
            $where = [];
            if($this->payload['appType'] === DeviceEnum::ACCOUNT_TYPE_SPH){
                $media = AiWechatCircleTask::where('id', $mediaId)->findOrEmpty();
                if (!$media->isEmpty()) {
                    $media->send_status = $status === 1 ? 2 : 3;
                    $media->remark = $content['msg'] ?? '发布失败';
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
            }else{
                $media = SvPublishSettingDetail::where('id', $mediaId)->findOrEmpty();
                if (!$media->isEmpty()) {
                    $media->status = $status;
                    $media->remark = $content['msg'] ?? '';
                    $media->update_time = time();
                    $media->exec_time = time();
                    $media->save();
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
                $task->status = (int)$status === 2 ? DeviceEnum::TASK_STATUS_FAILED : DeviceEnum::TASK_STATUS_FINISHED;
                $task->remark = $content['msg'] ?? '';
                $task->update_time = time();
                $task->save();
            }
            $this->payload['reply'] = '发布数据状态已更新';
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'cron');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }
}
