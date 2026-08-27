<?php

namespace app\common\service\auto;

use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use think\facade\Log;

/**
 * 任务类型关闭后，同步剥离工作流节点平台（只关不加）
 * Class AutoTaskSceneScheduleSyncService
 * @package app\common\service\auto
 */
class AutoTaskSceneScheduleSyncService
{
    private const TEMPLATE_TYPES = [1, 2, 3];
    private const SCENE_VIDEO_PUBLISH = 5;
    private const MINUTES_PER_PLATFORM = 10;

    /**
     * 对比新旧配置，只收集 1→0 的关闭平台。重开（0→1）不进入结果。
     *
     * @param array $oldMap scene => {allow_add, allow_platforms}
     * @param array $newMap 本次写入的 scene => {allow_add, allow_platforms}
     * @return array<int, int[]> scene => [account_type, ...]
     */
    public static function collectClosedPlatforms(array $oldMap, array $newMap): array
    {
        $closed = [];
        foreach ($newMap as $scene => $newItem) {
            $scene = (int)$scene;
            if ($scene < AutoTaskSceneConfigService::SCENE_MIN || $scene > AutoTaskSceneConfigService::SCENE_MAX) {
                continue;
            }
            if (!is_array($newItem)) {
                continue;
            }

            $oldItem = is_array($oldMap[$scene] ?? null) ? $oldMap[$scene] : [];
            $oldAllow = array_key_exists('allow_add', $oldItem) ? (int)$oldItem['allow_add'] : 1;
            $newAllow = array_key_exists('allow_add', $newItem) ? (int)$newItem['allow_add'] : 1;
            $support = AutoTaskSceneConfigService::getSupportPlatforms($scene);

            if ($oldAllow === 1 && $newAllow === 0) {
                $closed[$scene] = $support;
                continue;
            }

            $oldStatus = self::platformStatusMap($scene, $oldItem['allow_platforms'] ?? null, true);
            $newStatus = self::platformStatusMap($scene, $newItem['allow_platforms'] ?? null, true);
            $types = [];
            foreach ($support as $accountType) {
                $wasOpen = ($oldStatus[$accountType] ?? 1) === 1;
                $nowOpen = ($newStatus[$accountType] ?? 1) === 1;
                if ($wasOpen && !$nowOpen) {
                    $types[] = $accountType;
                }
            }
            if ($types !== []) {
                $closed[$scene] = $types;
            }
        }
        return $closed;
    }

    /**
     * 当前配置里已关闭的平台（含 allow_add=0 的全部支持平台）。
     * 用于详情展示和再次保存时清理历史脏数据；不包含重开平台。
     *
     * @param array $configMap
     * @return array<int, int[]>
     */
    public static function collectCurrentlyClosedPlatforms(array $configMap): array
    {
        $closed = [];
        foreach ($configMap as $scene => $item) {
            $scene = (int)$scene;
            if ($scene < AutoTaskSceneConfigService::SCENE_MIN || $scene > AutoTaskSceneConfigService::SCENE_MAX) {
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $support = AutoTaskSceneConfigService::getSupportPlatforms($scene);
            if ((int)($item['allow_add'] ?? 1) !== 1) {
                $closed[$scene] = $support;
                continue;
            }
            $statusMap = self::platformStatusMap($scene, $item['allow_platforms'] ?? null, true);
            $types = [];
            foreach ($support as $accountType) {
                if (($statusMap[$accountType] ?? 1) !== 1) {
                    $types[] = $accountType;
                }
            }
            if ($types !== []) {
                $closed[$scene] = $types;
            }
        }
        return $closed;
    }

    /**
     * 按当前关闭配置清洗展示用节点：剥离已关平台，platform 为空的记录不返回。不写库、不加回。
     *
     * @param array $schedules
     * @param array|null $configMap
     * @return array
     */
    public static function sanitizeSchedulesForDisplay(array $schedules, ?array $configMap = null): array
    {
        $configMap = $configMap ?? AutoTaskSceneConfigService::getConfigMap();
        $rows = self::applyClosedPlatformsToSchedules(
            $schedules,
            self::collectCurrentlyClosedPlatforms($configMap)
        );
        $result = [];
        foreach ($rows as $row) {
            $platforms = $row['platform'] ?? [];
            if (!is_array($platforms) || $platforms === []) {
                continue;
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 对比新旧配置中「重开」的平台（0→1），仅供校验：同步路径禁止用它写回节点。
     *
     * @param array $oldMap
     * @param array $newMap
     * @return array<int, int[]>
     */
    public static function collectReopenedPlatforms(array $oldMap, array $newMap): array
    {
        $reopened = [];
        foreach ($newMap as $scene => $newItem) {
            $scene = (int)$scene;
            if ($scene < AutoTaskSceneConfigService::SCENE_MIN || $scene > AutoTaskSceneConfigService::SCENE_MAX) {
                continue;
            }
            if (!is_array($newItem)) {
                continue;
            }
            $oldItem = is_array($oldMap[$scene] ?? null) ? $oldMap[$scene] : [];
            $oldAllow = array_key_exists('allow_add', $oldItem) ? (int)$oldItem['allow_add'] : 1;
            $newAllow = array_key_exists('allow_add', $newItem) ? (int)$newItem['allow_add'] : 1;
            $support = AutoTaskSceneConfigService::getSupportPlatforms($scene);

            if ($oldAllow === 0 && $newAllow === 1) {
                $reopened[$scene] = $support;
                continue;
            }

            $oldStatus = self::platformStatusMap($scene, $oldItem['allow_platforms'] ?? null, true);
            $newStatus = self::platformStatusMap($scene, $newItem['allow_platforms'] ?? null, true);
            $types = [];
            foreach ($support as $accountType) {
                $wasOpen = ($oldStatus[$accountType] ?? 1) === 1;
                $nowOpen = ($newStatus[$accountType] ?? 1) === 1;
                if (!$wasOpen && $nowOpen) {
                    $types[] = $accountType;
                }
            }
            if ($types !== []) {
                $reopened[$scene] = $types;
            }
        }
        return $reopened;
    }

    /**
     * 内存中剥离节点平台，不写库、不加回。
     *
     * @param array $schedules
     * @param array<int, int[]> $closedByScene
     * @return array
     */
    public static function applyClosedPlatformsToSchedules(array $schedules, array $closedByScene): array
    {
        $result = [];
        foreach ($schedules as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scene = (int)($row['scene'] ?? 0);
            $platforms = self::decodePlatforms($row['platform'] ?? []);
            $beforeCount = count($platforms);
            $closedTypes = $closedByScene[$scene] ?? [];
            if ($closedTypes !== []) {
                $platforms = self::stripAccountTypes($platforms, $closedTypes);
            }
            $row['scene'] = $scene;
            $row['platform'] = $platforms;
            $result[] = self::syncLockedEndTime($row, $beforeCount);
        }
        return $result;
    }

    /**
     * 回写 type=1/2/3 未删除模板的节点平台。不删节点、不碰当天任务。
     *
     * @param array<int, int[]> $closedByScene
     * @return int 更新条数
     */
    public static function stripClosedPlatformsFromTemplates(array $closedByScene): int
    {
        $closedByScene = self::normalizeClosedByScene($closedByScene);
        if ($closedByScene === []) {
            return 0;
        }

        $templateIds = MarketingTemplate::whereIn('type', self::TEMPLATE_TYPES)->column('id');
        if ($templateIds === []) {
            return 0;
        }

        $schedules = MarketingTemplateSchedule::whereIn('template_id', $templateIds)
            ->whereIn('scene', array_keys($closedByScene))
            ->select();

        $updated = 0;
        foreach ($schedules as $schedule) {
            $scene = (int)$schedule->scene;
            $closedTypes = $closedByScene[$scene] ?? [];
            if ($closedTypes === []) {
                continue;
            }
            $platforms = self::decodePlatforms($schedule->platform);
            $stripped = self::stripAccountTypes($platforms, $closedTypes);
            $synced = self::syncLockedEndTime([
                'scene' => $scene,
                'start_time' => (string)($schedule->start_time ?? ''),
                'end_time' => (string)($schedule->end_time ?? ''),
                'platform' => $stripped,
            ], count($platforms));
            $endTime = (string)($synced['end_time'] ?? $schedule->end_time);
            if (self::sameAccountTypes($platforms, $stripped) && (string)$schedule->end_time === $endTime) {
                continue;
            }
            $schedule->platform = $stripped;
            $schedule->end_time = $endTime;
            $schedule->save();
            $updated++;
        }

        if ($updated > 0) {
            try {
                Log::write('任务类型关闭平台已同步到工作流节点，更新' . $updated . '条', 'info');
            } catch (\Throwable $e) {
                // 日志失败不影响同步
            }
        }

        return $updated;
    }

    /**
     * 从节点平台列表去掉指定账号类型，并重排 order。
     *
     * @param array $platforms
     * @param int[] $closedTypes
     * @return array
     */
    public static function stripAccountTypes(array $platforms, array $closedTypes): array
    {
        $closedSet = [];
        foreach ($closedTypes as $type) {
            $closedSet[(int)$type] = true;
        }
        $result = [];
        foreach ($platforms as $platform) {
            if (!is_array($platform)) {
                continue;
            }
            $accountType = (int)($platform['account_type'] ?? 0);
            if ($accountType <= 0 || isset($closedSet[$accountType])) {
                continue;
            }
            $result[] = $platform;
        }
        $order = 1;
        foreach ($result as $index => $item) {
            $result[$index]['order'] = $order++;
        }
        return array_values($result);
    }

    /**
     * 解析排期有效窗口：剥离已关平台后按锁定规则重算结束时间。
     * 任务间空档不属于本任务。
     *
     * @param array $schedule
     * @param array|null $configMap
     * @return array{scene:int,start_time:string,end_time:string,time_range:string,platform:array}
     */
    public static function resolveEffectiveWindow(array $schedule, ?array $configMap = null): array
    {
        $scene = (int)($schedule['scene'] ?? 0);
        $startTime = trim((string)($schedule['start_time'] ?? ''));
        $endTime = trim((string)($schedule['end_time'] ?? ''));
        $platforms = self::decodePlatforms($schedule['platform'] ?? []);
        $row = [
            'scene' => $scene,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'platform' => $platforms,
        ];

        $sanitized = self::sanitizeSchedulesForDisplay([$row], $configMap);
        if (!empty($sanitized[0]) && is_array($sanitized[0])) {
            $row = $sanitized[0];
        } else {
            $row = self::syncLockedEndTime($row, count($platforms));
        }

        $startTime = trim((string)($row['start_time'] ?? $startTime));
        $endTime = trim((string)($row['end_time'] ?? $endTime));
        $platforms = self::decodePlatforms($row['platform'] ?? $platforms);

        return [
            'scene' => (int)($row['scene'] ?? $scene),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_range' => ($startTime !== '' && $endTime !== '') ? $startTime . '-' . $endTime : '',
            'platform' => $platforms,
        ];
    }

    /**
     * 按开始时钟匹配排期并返回锁定后的有效窗口。
     *
     * @param string $startTime
     * @param array $schedules
     * @param int $scene
     * @param array|null $configMap
     * @return array|null
     */
    public static function matchEffectiveWindowByStart(
        string $startTime,
        array $schedules,
        int $scene = self::SCENE_VIDEO_PUBLISH,
        ?array $configMap = null
    ): ?array {
        $startTime = self::normalizeClock($startTime);
        if ($startTime === '') {
            return null;
        }
        foreach ($schedules as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }
            if ((int)($schedule['scene'] ?? 0) !== $scene) {
                continue;
            }
            $window = self::resolveEffectiveWindow($schedule, $configMap);
            if (($window['start_time'] ?? '') === $startTime) {
                return $window;
            }
        }
        return null;
    }

    /**
     * 按有效窗口均分各平台执行档。
     *
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param array $platforms
     * @return list<array{index:int,platform:array,start_time:int,end_time:int}>
     */
    public static function splitPlatformSlots(string $date, string $startTime, string $endTime, array $platforms): array
    {
        $platforms = array_values(self::decodePlatforms($platforms));
        $count = count($platforms);
        $st = strtotime($date . ' ' . $startTime . ':00');
        $et = strtotime($date . ' ' . $endTime . ':00');
        if ($count <= 0 || $st === false || $et === false || $et <= $st) {
            return [];
        }

        $interval = ($et - $st) / $count;
        $slots = [];
        foreach ($platforms as $index => $platform) {
            $start = (int)($st + $index * $interval);
            $slots[] = [
                'index' => $index,
                'platform' => $platform,
                'start_time' => $start,
                'end_time' => (int)($start + $interval),
            ];
        }
        return $slots;
    }

    /**
     * 视频发布始终按平台数锁定；多平台任务同样锁定（每平台 10 分钟）。
     *
     * @param int $scene
     * @param int $platformCount
     * @return bool
     */
    public static function shouldLockEndTime(int $scene, int $platformCount): bool
    {
        return $scene === self::SCENE_VIDEO_PUBLISH || $platformCount > 1;
    }

    /**
     * @param string $time
     * @return string
     */
    private static function normalizeClock(string $time): string
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($time), $matches)) {
            return '';
        }
        return sprintf('%02d:%02d', min(23, max(0, (int)$matches[1])), min(59, max(0, (int)$matches[2])));
    }

    /**
     * 按锁定规则重算结束时间。当前或剥离前满足锁定条件时都重算，避免关平台后仍沿用旧时长。
     *
     * @param array $row
     * @param int $previousPlatformCount 剥离前平台数；-1 表示未知，仅按当前平台判断
     * @return array
     */
    public static function syncLockedEndTime(array $row, int $previousPlatformCount = -1): array
    {
        $scene = (int)($row['scene'] ?? 0);
        $count = count(self::decodePlatforms($row['platform'] ?? []));
        $currentLocked = self::shouldLockEndTime($scene, $count);
        $previousLocked = $previousPlatformCount >= 0 && self::shouldLockEndTime($scene, $previousPlatformCount);
        if ($count <= 0 || (!$currentLocked && !$previousLocked)) {
            return $row;
        }
        $endTime = self::calcLockedEndTime((string)($row['start_time'] ?? ''), $count);
        if ($endTime !== null) {
            $row['end_time'] = $endTime;
        }
        return $row;
    }

    /**
     * @param string $startTime
     * @param int $platformCount
     * @return string|null
     */
    public static function calcLockedEndTime(string $startTime, int $platformCount): ?string
    {
        if ($platformCount <= 0 || !preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $startTime, $matches)) {
            return null;
        }
        $total = (int)$matches[1] * 60 + (int)$matches[2] + $platformCount * self::MINUTES_PER_PLATFORM;
        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    /**
     * @param mixed $raw
     * @return array
     */
    public static function decodePlatforms($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }

    /**
     * @param int $scene
     * @param mixed $raw
     * @param bool $defaultOpen
     * @return array<int, int>
     */
    private static function platformStatusMap(int $scene, $raw, bool $defaultOpen): array
    {
        $map = [];
        foreach (AutoTaskSceneConfigService::normalizeAllowPlatforms($scene, $raw, $defaultOpen) as $item) {
            $map[(int)$item['account_type']] = (int)$item['status'];
        }
        return $map;
    }

    /**
     * @param array $closedByScene
     * @return array<int, int[]>
     */
    private static function normalizeClosedByScene(array $closedByScene): array
    {
        $normalized = [];
        foreach ($closedByScene as $scene => $types) {
            $scene = (int)$scene;
            if ($scene < AutoTaskSceneConfigService::SCENE_MIN || $scene > AutoTaskSceneConfigService::SCENE_MAX) {
                continue;
            }
            if (!is_array($types) || $types === []) {
                continue;
            }
            $unique = [];
            foreach ($types as $type) {
                $accountType = (int)$type;
                if ($accountType > 0) {
                    $unique[$accountType] = $accountType;
                }
            }
            if ($unique !== []) {
                $normalized[$scene] = array_values($unique);
            }
        }
        return $normalized;
    }

    /**
     * @param array $left
     * @param array $right
     * @return bool
     */
    private static function sameAccountTypes(array $left, array $right): bool
    {
        $leftTypes = [];
        foreach ($left as $item) {
            if (is_array($item)) {
                $leftTypes[] = (int)($item['account_type'] ?? 0);
            }
        }
        $rightTypes = [];
        foreach ($right as $item) {
            if (is_array($item)) {
                $rightTypes[] = (int)($item['account_type'] ?? 0);
            }
        }
        sort($leftTypes);
        sort($rightTypes);
        return $leftTypes === $rightTypes;
    }
}
