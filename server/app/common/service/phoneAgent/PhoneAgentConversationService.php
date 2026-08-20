<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentConversation;
use app\common\model\phoneAgent\PhoneAgentTask;

class PhoneAgentConversationService
{
    private const SUMMARY_TASK_LIMIT = 5;
    private const SUMMARY_TEXT_LIMIT = 1000;
    private const MESSAGE_TEXT_LIMIT = 160;
    private const TITLE_LIMIT = 30;

    public static function taskConversationId(array|PhoneAgentTask $task): string
    {
        if ($task instanceof PhoneAgentTask) {
            $conversationId = trim((string)($task->conversation_id ?? ''));
            $taskId = trim((string)($task->task_id ?? ''));
        } else {
            $conversationId = trim((string)($task['conversation_id'] ?? ''));
            $taskId = trim((string)($task['task_id'] ?? ''));
        }

        return $conversationId !== '' ? $conversationId : $taskId;
    }

    public static function titleFromMessage(string $message): string
    {
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);
        if ($message === '') {
            return '手机操控会话';
        }

        return self::limitText($message, self::TITLE_LIMIT, '');
    }

    public static function statusText(string $status, string $errorMsg = ''): string
    {
        if ($status === PhoneAgentTask::STATUS_FAILED && trim($errorMsg) !== '') {
            return trim($errorMsg);
        }

        return match ($status) {
            PhoneAgentTask::STATUS_CREATED => '任务已创建',
            PhoneAgentTask::STATUS_OBSERVING => '正在观察手机页面',
            PhoneAgentTask::STATUS_MODEL_PENDING => '正在请求模型决策',
            PhoneAgentTask::STATUS_DISPATCHING => '正在下发手机动作',
            PhoneAgentTask::STATUS_WAITING_REPORT => '等待手机执行结果',
            PhoneAgentTask::STATUS_COMPLETED => '任务已完成',
            PhoneAgentTask::STATUS_FAILED => '任务失败',
            PhoneAgentTask::STATUS_CANCELED => '任务已取消',
            default => $status,
        };
    }

    public static function makeContextSummary(array $tasks): string
    {
        if (empty($tasks)) {
            return '';
        }

        usort($tasks, static function (array $left, array $right): int {
            return ((int)($left['create_time'] ?? 0)) <=> ((int)($right['create_time'] ?? 0));
        });

        $tasks = array_slice($tasks, -self::SUMMARY_TASK_LIMIT);
        $lines = [];
        foreach ($tasks as $index => $task) {
            $message = self::limitText(trim((string)($task['message'] ?? '')), self::MESSAGE_TEXT_LIMIT);
            if ($message === '') {
                continue;
            }
            $status = self::statusText((string)($task['status'] ?? ''), (string)($task['error_msg'] ?? ''));
            $deviceCode = trim((string)($task['device_code'] ?? ''));
            $devicePrefix = $deviceCode !== '' ? '设备：' . $deviceCode . '；' : '';
            $lines[] = ($index + 1) . '. ' . $devicePrefix . '用户指令：' . $message . '；执行结果：' . $status;
        }

        return self::limitText(implode("\n", $lines), self::SUMMARY_TEXT_LIMIT);
    }

    public static function prepareForDispatch(int $userId, string $deviceCode, string $message, string $conversationId = ''): PhoneAgentConversation
    {
        $conversationId = trim($conversationId);
        if ($conversationId === '') {
            return PhoneAgentConversation::create([
                'conversation_id' => generate_unique_task_id(),
                'user_id' => $userId,
                'device_code' => $deviceCode,
                'title' => self::titleFromMessage($message),
                'last_message' => trim($message),
                'last_task_id' => '',
                'task_count' => 0,
                'last_task_status' => PhoneAgentTask::STATUS_CREATED,
                'context_summary' => '',
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }

        $conversation = PhoneAgentConversation::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->lock(true)
            ->findOrEmpty();
        if ($conversation->isEmpty()) {
            throw new \Exception('会话不存在或无权限');
        }
        if (self::hasRunningTask($conversationId)) {
            throw new \Exception('当前会话任务执行中，请稍后再试');
        }

        return $conversation;
    }

    public static function userConversation(string $conversationId, int $userId): ?PhoneAgentConversation
    {
        $conversation = PhoneAgentConversation::where('conversation_id', trim($conversationId))
            ->where('user_id', $userId)
            ->findOrEmpty();
        return $conversation->isEmpty() ? null : $conversation;
    }

    public static function deleteConversation(string $conversationId, int $userId): void
    {
        $conversation = self::userConversation($conversationId, $userId);
        if ($conversation === null) {
            throw new \Exception('会话不存在或无权限');
        }
        if (self::hasRunningTask(trim($conversationId))) {
            throw new \Exception('当前会话任务执行中，无法删除');
        }

        $conversation->delete();
    }

    public static function markTaskCreated(PhoneAgentConversation $conversation, PhoneAgentTask $task): void
    {
        $conversationId = self::taskConversationId($task);
        $conversation->device_code = (string)$task->device_code;
        $conversation->last_message = (string)$task->message;
        $conversation->last_task_id = (string)$task->task_id;
        $conversation->last_task_status = (string)$task->status;
        $conversation->task_count = self::taskCount($conversationId);
        $conversation->update_time = time();
        $conversation->save();
    }

    public static function syncTask(PhoneAgentTask $task): void
    {
        $conversationId = self::taskConversationId($task);
        if ($conversationId === '') {
            return;
        }

        $conversation = PhoneAgentConversation::where('conversation_id', $conversationId)
            ->where('user_id', (int)$task->user_id)
            ->findOrEmpty();
        if ($conversation->isEmpty()) {
            return;
        }

        $conversation->last_message = self::statusText((string)$task->status, (string)$task->error_msg);
        $conversation->last_task_id = (string)$task->task_id;
        $conversation->last_task_status = (string)$task->status;
        $conversation->task_count = self::taskCount($conversationId);
        $conversation->context_summary = self::contextSummaryByConversation($conversationId);
        $conversation->update_time = time();
        $conversation->save();
    }

    public static function contextForTask(PhoneAgentTask $task): string
    {
        $conversationId = self::taskConversationId($task);
        if ($conversationId === '') {
            return '';
        }

        return (string)PhoneAgentConversation::where('conversation_id', $conversationId)
            ->where('user_id', (int)$task->user_id)
            ->where('last_task_status', 'in', [PhoneAgentTask::STATUS_COMPLETED])
            ->value('context_summary');
    }

    public static function devicesByConversationIds(array $conversationIds, int $userId): array
    {
        $conversationIds = array_values(array_unique(array_filter(array_map(static function ($conversationId): string {
            return trim((string)$conversationId);
        }, $conversationIds))));
        if (empty($conversationIds) || $userId <= 0) {
            return [];
        }

        $rows = PhoneAgentTask::alias('t')
            ->leftJoin('sv_device d', 'd.user_id = t.user_id and d.device_code = t.device_code')
            ->where('t.user_id', $userId)
            ->where('t.conversation_id', 'in', $conversationIds)
            ->where('t.device_code', '<>', '')
            ->field('t.conversation_id,t.device_code,MIN(t.id) AS first_task_id,MAX(d.device_name) AS device_name,MAX(d.device_model) AS device_model')
            ->group('t.conversation_id,t.device_code')
            ->order('first_task_id', 'asc')
            ->select()
            ->toArray();

        $devices = [];
        foreach ($rows as $row) {
            $conversationId = (string)($row['conversation_id'] ?? '');
            $deviceCode = (string)($row['device_code'] ?? '');
            if ($conversationId === '' || $deviceCode === '') {
                continue;
            }

            $deviceName = trim((string)($row['device_name'] ?? ''));
            $devices[$conversationId][] = [
                'device_code' => $deviceCode,
                'device_name' => $deviceName !== '' ? $deviceName : $deviceCode,
                'device_model' => (string)($row['device_model'] ?? ''),
            ];
        }

        return $devices;
    }

    private static function hasRunningTask(string $conversationId): bool
    {
        return PhoneAgentTask::where('conversation_id', $conversationId)
            ->whereNotIn('status', PhoneAgentTask::FINAL_STATUSES)
            ->count() > 0;
    }

    private static function taskCount(string $conversationId): int
    {
        return (int)PhoneAgentTask::where('conversation_id', $conversationId)->count();
    }

    private static function contextSummaryByConversation(string $conversationId): string
    {
        $tasks = PhoneAgentTask::where('conversation_id', $conversationId)
            ->where('status', 'in', PhoneAgentTask::FINAL_STATUSES)
            ->field('device_code,message,status,error_msg,create_time')
            ->order('create_time', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return self::makeContextSummary($tasks);
    }

    private static function limitText(string $text, int $limit, string $suffix = '...'): string
    {
        if ($limit <= 0 || $text === '') {
            return '';
        }

        if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
            return strlen($text) <= $limit ? $text : substr($text, 0, $limit) . $suffix;
        }

        return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . $suffix;
    }
}
