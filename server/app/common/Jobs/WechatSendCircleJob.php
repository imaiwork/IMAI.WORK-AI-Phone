<?php

namespace app\common\Jobs;

use think\facade\Db;
use think\queue\Job;
use think\facade\Log;
use app\common\model\wechat\AiWechatCircleTask;

class WechatSendCircleJob
{
    /**
     * 微信ID
     * @var string
     */
    protected string $wechatId;

    /**
     * 发圈内容
     * @var string
     */
    protected string $content;

    /**
     * 设备编码
     * @var string
     */
    protected string $deviceCode;

    /**
     * 附件类型
     * @var string
     */
    protected string $attachmentType;

    /**
     * 附件内容
     * @var array
     */
    protected array $attachmentContent;

    /**
     * 附加评论
     * @var array
     */
    protected array $comment;

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
            $this->content = $data['content'] ?? '';
            $this->deviceCode = $data['device_code'] ?? '';
            $this->attachmentType = $data['attachment_type'] ?? '';
            $this->attachmentContent = $data['attachment_content'] ?? '';
            $this->comment = $data['comment'] ?? '';
            $this->uid = $data['uid'] ?? 0;
            $this->taskId = $data['task_id'] ?? '';

            if ($job->attempts() == 0)
            {
                Log::channel('jobs')->info("队列[发圈任务]开始执行:\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            if (!$this->wechatId || !$this->content || !$this->uid || !$this->deviceCode || !$this->taskId)
            {
                $job->delete();
                return;
            }

            // 查询任务是否存在
            $task = AiWechatCircleTask::where('task_id', $this->taskId)->where('user_id', $this->uid)->findOrEmpty();
            if ($task->isEmpty())
            {
                Log::channel('jobs')->info("队列[发圈任务]任务不存在【{$this->taskId}】");
                $job->delete();
                return;
            }

            // 执行中
            if ($task->send_status != 1)
            {
                Log::channel('jobs')->info("队列[发圈任务]任务状态有误【{$this->taskId}】");
                $job->delete();
                return;
            }

            // 发送消息
            $response = $this->sendMessage();

            Log::channel('jobs')->info("队列[发圈任务]业务执行结果【{$this->taskId}】:\n" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            // 记录日志
            if (isset($response['code']) && $response['code'] != 10000)
            {
                // 重试
                $this->retryJob($job, '业务执行失败', $data);
                return;
            }

            $task->send_status = 2;
            $task->finish_time = date('Y-m-d H:i');
            $task->save();

            // 任务执行成功，删除任务
            $job->delete();

            Log::channel('jobs')->info("队列[发圈任务]执行完毕【{$this->taskId}】- 重试次数: {$job->attempts()}\n");
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
        Log::channel('jobs')->info("队列[发圈任务]执行失败【{$this->taskId}】: {$errorMessage} - 重试次数: {$job->attempts()}\n");

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
     * @return array
     */
    private function sendMessage(): array
    {
        return \app\common\service\ToolsService::Wechat()->circle([
            'wechat_id'             => $this->wechatId,
            'device_code'           => $this->deviceCode,
            'content'               => $this->content,
            'attachment_type'       => $this->attachmentType,
            'attachment_content'    => $this->attachmentContent,
            'comment'               => $this->comment,
            'uid'                   => $this->uid,
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
        AiWechatCircleTask::where('task_id', $this->taskId)->where('user_id', $this->uid)->update([
            'task_status' => 3,
            'finish_time' => date('Y-m-d H:i'),
        ]);

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
