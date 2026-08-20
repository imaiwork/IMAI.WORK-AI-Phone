<?php

namespace app\common\service\digitalHuman;

class DefaultPublicTaskIdFactory
{
    /**
     * 默认公共形象克隆：生成 shanjian / chanjing 关联 task_id。
     *
     * @param array<string, array<string, mixed>> $templateTaskIds config 中 task_ids（仅含 status）
     * @return array{
     *     shanjian: string,
     *     chanjing: string,
     *     task_ids: array<string, array{task_id: string, status: int}>
     * }
     */
    public static function forAnchorClone(array $templateTaskIds): array
    {
        $shanjianTaskId = generate_unique_task_id();
        $chanjingTaskId = generate_unique_task_id();

        $channels = ['shanjian', 'chanjing', 'weiju'];
        $taskIds = [];
        foreach ($channels as $channel) {
            $status = (int)($templateTaskIds[$channel]['status'] ?? 0);
            $taskId = match ($channel) {
                'shanjian' => $shanjianTaskId,
                'chanjing' => $chanjingTaskId,
                default      => '',
            };
            $taskIds[$channel] = [
                'task_id' => $taskId,
                'status'  => $status,
            ];
        }

        return [
            'shanjian' => $shanjianTaskId,
            'chanjing' => $chanjingTaskId,
            'task_ids' => $taskIds,
        ];
    }

    public static function forVoiceClone(): string
    {
        return generate_unique_task_id();
    }
}
