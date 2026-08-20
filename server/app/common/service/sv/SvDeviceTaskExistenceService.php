<?php

namespace app\common\service\sv;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDeviceTask;
use think\facade\Log;

class SvDeviceTaskExistenceService
{
    public static function emptySlotResult(): array
    {
        return [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_no_account' => 0,
            'skipped_empty_schedule' => 0,
        ];
    }

    public static function mergeSlotResult(array $base, array $delta): array
    {
        return [
            'created' => (int)($base['created'] ?? 0) + (int)($delta['created'] ?? 0),
            'skipped_existing' => (int)($base['skipped_existing'] ?? 0) + (int)($delta['skipped_existing'] ?? 0),
            'skipped_no_account' => (int)($base['skipped_no_account'] ?? 0) + (int)($delta['skipped_no_account'] ?? 0),
            'skipped_empty_schedule' => (int)($base['skipped_empty_schedule'] ?? 0) + (int)($delta['skipped_empty_schedule'] ?? 0),
        ];
    }

    /**
     * 平台账号未绑定：记日志并累加计数
     */
    public static function bumpMissingAccount(array &$result, string $deviceCode, int $accountType, string $taskName): void
    {
        $prefix = $taskName !== '' ? $taskName : '任务';
        Log::channel('auto')->write(
            $deviceCode . $prefix . '平台账号未绑定，跳过：平台=' . $accountType,
            'create'
        );
        $result['skipped_no_account'] = (int)($result['skipped_no_account'] ?? 0) + 1;
    }

    /**
     * 日程为空：记日志并累加计数
     */
    public static function bumpEmptySchedule(array &$result, string $deviceCode, string $taskName): void
    {
        $prefix = $taskName !== '' ? $taskName : '任务';
        Log::channel('auto')->write(
            $deviceCode . $prefix . '日程为空，跳过',
            'create'
        );
        $result['skipped_empty_schedule'] = (int)($result['skipped_empty_schedule'] ?? 0) + 1;
    }

    public static function dailyAutoTaskExists(
        int $userId,
        string $deviceCode,
        int $personaId,
        int $taskScene,
        int $accountType,
        string $day,
        int $startTime,
        int $endTime
    ): bool {
        return !SvDeviceTask::where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->where('persona_id', $personaId)
            ->where('auto_type', 1)
            ->where('day', $day)
            ->where('task_scene', $taskScene)
            ->where('account_type', $accountType)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->whereNull('delete_time')
            ->findOrEmpty()
            ->isEmpty();
    }

    public static function logSkipped(
        string $deviceCode,
        int $taskScene,
        int $accountType,
        string $day,
        int $startTime,
        int $endTime,
        string $taskName = ''
    ): void {
        $timeRange = date('H:i', $startTime) . '-' . date('H:i', $endTime);
        $sceneDesc = DeviceEnum::getTaskSceneDesc($taskScene) ?: (string)$taskScene;
        $prefix = $taskName !== '' ? $taskName : $sceneDesc;
        Log::channel('auto')->write(
            $deviceCode . $prefix . '今日已存在，跳过：平台=' . $accountType
            . ', 时间=' . $timeRange . ', 日期=' . $day,
            'create'
        );
    }

    public static function shouldSkipExistingSlot(
        int $userId,
        string $deviceCode,
        int $personaId,
        int $taskScene,
        int $accountType,
        int $startTime,
        int $endTime,
        string $taskName = ''
    ): bool {
        $day = date('Y-m-d', $startTime);
        if (!self::dailyAutoTaskExists($userId, $deviceCode, $personaId, $taskScene, $accountType, $day, $startTime, $endTime)) {
            return false;
        }

        self::logSkipped($deviceCode, $taskScene, $accountType, $day, $startTime, $endTime, $taskName);
        return true;
    }
}
