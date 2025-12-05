<?php

namespace app\common\Jobs;

use think\facade\Db;
use think\queue\Job;
use think\facade\Log;
use app\common\model\wechat\AiWechatLog;

class WechatAcceptFriendJob
{
    /**
     * 微信ID
     * @var string
     */
    protected string $wechatId;

    /**
     * 好友ID
     * @var string
     */
    protected string $friendId;

    /**
     * 设备编码
     * @var string
     */
    protected string $deviceCode;

    /**
     * 用户ID
     * @var int
     */
    protected int $uid;

    /**
     * 任务ID
     * @var string
     */
    protected string $taskId;

    /**
     * 执行任务
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function fire(Job $job, array $data)
    {
        try
        {
            $this->wechatId = $data['wechat_id'] ?? '';
            $this->friendId = $data['friend_id'] ?? '';
            $this->deviceCode = $data['device_code'] ?? '';
            $this->uid = $data['uid'] ?? 0;
            $this->taskId = $data['task_id'] ?? '';

            if ($job->attempts() == 0)
            {
                Log::channel('jobs')->info("队列[接受好友]开始执行:\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            if (!$this->wechatId || !$this->friendId || !$this->uid || !$this->deviceCode || !$this->taskId)
            {
                $job->delete();
                return;
            }

            // 发送消息
            $this->sendMessage();

            // 任务执行成功，删除任务
            $job->delete();

            // 记录日志
            AiWechatLog::create([
                'user_id'   => $this->uid,
                'wechat_id' => $this->wechatId,
                'friend_id' => $this->friendId,
                'log_type'      => AiWechatLog::TYPE_ACCEPT_FRIEND
            ]);

            Log::channel('jobs')->info("队列[接受好友]执行完毕【{$this->taskId}】- 重试次数: {$job->attempts()}\n");
        }
        catch (\Throwable $e)
        {
            // 任务执行失败，重试任务
            $this->retryJob($job, $e->getMessage(), $data);
        }
    }


    /**
     * 重试任务
     * @param Job $job
     * @param string $errorMessage
     * @param array $data
     * @return void
     */
    private function retryJob(Job $job, string $errorMessage, array $data)
    {
        Log::channel('jobs')->info("队列[接受好友]执行失败【{$this->taskId}】: {$errorMessage} - 重试次数: {$job->attempts()}\n");

        if ($job->attempts() >= 3)
        {
            // 超过重试次数，记录到失败任务表
            $this->markAsFailed($job, $errorMessage, $data);
            $job->delete();
        }
        else
        {
            // 重试任务
            $job->release(5);
        }
    }

    /**
     * 发送消息
     * @return void
     */
    private function sendMessage()
    {
        \app\common\service\ToolsService::Wechat()->accept([
            'wechat_id'     => $this->wechatId,
            'friend_id'     => $this->friendId,
            'device_code'   => $this->deviceCode,
            'task_id'       => $this->taskId,
        ]);
    }

    /**
     * 标记任务失败
     * @param Job $job
     * @param string $errorMessage
     * @param array $data
     * @return void
     */
    protected function  markAsFailed(Job $job, string $errorMessage, array $data): void
    {
        Db::name('failed_jobs')->insert([
            'user_id' => $data['uid'],
            'job_id' => $this->taskId,
            'job_class' => get_class(),
            'job_data' => json_encode($data),
            'error_message' => $errorMessage,
            'attempts' => $job->attempts(),
            'failed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
