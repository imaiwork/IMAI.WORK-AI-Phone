<?php

namespace app\api\logic\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCityTouchRecord;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDevicePreciseClues;
use app\common\model\sv\SvDevicePreciseCluesAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvGroupBuyRecord;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\sv\SvDeviceTaskExistenceService;
use think\facade\Db;
use think\facade\Log;

class PreciseCluesLogic extends BasePersonaLogic
{
    public static function getTimesByType(int $personaType, int $accountType)
    {
        $schedule = self::getPreciseCluesScheduleConfig();
        foreach (self::getPreciseCluesPlatforms() as $platform) {
            if ((int)$platform['account_type'] === $accountType) {
                return ($schedule['start_time'] ?? '03:00') . '-' . ($schedule['end_time'] ?? '06:00');
            }
        }

        return [];
    }

    public static function autoPreciseCluesTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code} 自动创建精准获客任务\n");
        Log::channel('auto')->write($device->device_code . ' 自动创建精准获客任务', 'create');
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                Log::channel('auto')->write($device->device_code . ' 未找到人设数据 ' . Db::getLastSql(), 'create');
                return SvDeviceTaskExistenceService::emptySlotResult();
            }

            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'auto_clues.options.sph_clues')) {
                Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.sph_clues=0，跳过精准获客任务', 'create');
                return SvDeviceTaskExistenceService::emptySlotResult();
            }

            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }

            $persona->device_code = $device->device_code;
            $persona->rule = $rule;

            return self::createPreciseCluesTask($persona);
        } catch (\Throwable $th) {
            Log::channel('auto')->write($th->__toString(), 'create');
            return SvDeviceTaskExistenceService::emptySlotResult();
        }
    }

    private static function createPreciseCluesTask(AiPersona $persona): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        $platformTasks = self::collectAvailablePlatformTasks($persona);
        if (empty($platformTasks)) {
            Log::channel('auto')->write($persona->device_code . ' 没有可用的精准获客平台任务', 'create');
            return $result;
        }

        Db::startTrans();
        try {
            $date = date('Y-m-d');
            $schedule = self::getPreciseCluesScheduleConfig();
            $timeRanges = self::splitTimeRanges(
                $date,
                (string)($schedule['start_time'] ?? '03:00'),
                (string)($schedule['end_time'] ?? '06:00'),
                count($platformTasks)
            );

            foreach ($platformTasks as $index => $platformTask) {
                $account = $platformTask['account'];
                $clues = $platformTask['clues'];
                $timeRange = $timeRanges[$index];
                $execTime = $timeRange['range'];
                $startTime = $timeRange['start_time'];
                $endTime = $timeRange['end_time'];
                $taskName = '自动化精准获客任务' . date('mdHis');

                if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                    (int)$persona->user_id,
                    (string)$persona->device_code,
                    (int)$persona->id,
                    DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES,
                    (int)$account->type,
                    $startTime,
                    $endTime,
                    '精准获客任务'
                )) {
                    $result['skipped_existing']++;
                    continue;
                }

                $task = SvDevicePreciseClues::create([
                    'user_id' => $persona->user_id,
                    'task_name' => $taskName,
                    'auto_type' => 1,
                    'accounts' => json_encode($account->toArray(), JSON_UNESCAPED_UNICODE),
                    'status' => 0,
                    'persona_id' => $persona->id,
                    'custom_date' => json_encode([$date], JSON_UNESCAPED_UNICODE),
                    'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                ]);

                $row = SvDevicePreciseCluesAccount::create([
                    'precise_clues_id' => $task->id,
                    'user_id' => $persona->user_id,
                    'account' => $account->account,
                    'account_type' => $account->type,
                    'nickname' => $account->nickname,
                    'avatar' => $account->avatar,
                    'auto_type' => 1,
                    'device_code' => $persona->device_code,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $startTime),
                    'clues' => $clues,
                    'status' => 0,
                    'persona_id' => $persona->id,
                    'create_time' => time(),
                ]);

                SvDeviceTask::create([
                    'user_id' => $persona->user_id,
                    'device_code' => $persona->device_code,
                    'task_type' => DeviceEnum::TASK_TYPE_PRECISE_CLUES,
                    'account' => $account->account,
                    'account_type' => $account->type,
                    'nickname' => $account->nickname,
                    'avatar' => $account->avatar,
                    'auto_type' => 1,
                    'task_name' => $taskName,
                    'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $startTime),
                    'sub_task_id' => $task->id,
                    'sub_data_id' => $row->id,
                    'persona_id' => $persona->id,
                    'task_scene' => DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES,
                    'source' => DeviceEnum::TASK_SOURCE_PRECISE_CLUES,
                    'create_time' => time(),
                ]);
                $result['created']++;
            }

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            Db::rollback();
            Log::channel('auto')->write('自动创建精准获客任务失败 ' . $persona->device_code . ' ' . $th->__toString(), 'create');
            return $result;
        }
    }

    private static function collectAvailablePlatformTasks(AiPersona $persona): array
    {
        $tasks = [];
        foreach (self::getPreciseCluesPlatforms() as $platform) {
            $accountType = (int)($platform['account_type'] ?? 0);
            if ($accountType <= 0) {
                continue;
            }

            $account = SvAccount::field('id,account,type,nickname,avatar')
                ->where('type', $accountType)
                ->where('user_id', $persona->user_id)
                ->where('device_code', $persona->device_code)
                ->findOrEmpty();
            if ($account->isEmpty()) {
                Log::channel('auto')->write($persona->device_code . ' 精准获客账号未绑定：' . $accountType, 'create');
                continue;
            }

            $clueField = (string)($platform['clue_field'] ?? 'account');
            $clues = self::getCluesList($persona, $accountType, $clueField);
            if (empty($clues)) {
                Log::channel('auto')->write($persona->device_code . ' 精准获客线索池为空：' . $accountType, 'create');
                continue;
            }

            $tasks[] = [
                'platform' => $platform,
                'account' => $account,
                'clues' => $clues,
            ];
        }

        return $tasks;
    }

    private static function getPreciseCluesScheduleConfig(): array
    {
        foreach (DeviceEnum::getDefaultScheduleScene() as $schedule) {
            if ((int)($schedule['scene'] ?? 0) === DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES) {
                return $schedule;
            }
        }

        return [
            'start_time' => '03:00',
            'end_time' => '06:00',
            'platform' => [],
        ];
    }

    private static function getPreciseCluesPlatforms(): array
    {
        $schedule = self::getPreciseCluesScheduleConfig();
        $platforms = $schedule['platform'] ?? [];
        if (!is_array($platforms)) {
            return [];
        }

        usort($platforms, function ($left, $right) {
            return (int)($left['order'] ?? 0) <=> (int)($right['order'] ?? 0);
        });

        return array_values($platforms);
    }

    private static function splitTimeRanges(string $date, string $start, string $end, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $startTime = self::buildTimestamp($date, $start);
        $endTime = self::buildTimestamp($date, $end);
        if ($endTime <= $startTime) {
            $endTime += 86400;
        }

        $duration = $endTime - $startTime;
        $ranges = [];
        for ($index = 0; $index < $count; $index++) {
            $segmentStart = $startTime + intdiv($duration * $index, $count);
            $segmentEnd = $index === $count - 1
                ? $endTime
                : $startTime + intdiv($duration * ($index + 1), $count);

            $ranges[] = [
                'range' => date('H:i', $segmentStart) . '-' . date('H:i', $segmentEnd),
                'start_time' => $segmentStart,
                'end_time' => $segmentEnd,
            ];
        }

        return $ranges;
    }

    private static function buildTimestamp(string $date, string $time): int
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        $timestamp = strtotime($date . ' ' . $time);
        return $timestamp ?: strtotime($date . ' 03:00:00');
    }

    private static function getCluesList(AiPersona $persona, int $accountType = DeviceEnum::ACCOUNT_TYPE_DY, string $clueField = 'account'): array
    {
        $clueField = in_array($clueField, ['account', 'account_name'], true) ? $clueField : 'account';
        $date = date('Y-m-d', strtotime('-1 day'));
        $st = strtotime($date . ' 00:00:00');
        $et = strtotime($date . ' 23:59:59');

        $clues = array_merge(
            self::queryClues(SvCityTouchRecord::class, $persona, $accountType, $clueField, $st, $et),
            self::queryClues(SvGroupBuyRecord::class, $persona, $accountType, $clueField, $st, $et),
            self::queryClues(SvLeadScrapingRecord::class, $persona, $accountType, $clueField, $st, $et)
        );

        $clues = array_map([self::class, 'normalizeClue'], $clues);
        $clues = array_values(array_unique(array_filter($clues)));

        Log::channel('auto')->write(
            $persona->device_code . ' 昨日精准获客线索[' . $accountType . '][' . $clueField . ']：' . json_encode($clues, JSON_UNESCAPED_UNICODE),
            'create'
        );

        return $clues;
    }

    private static function queryClues(string $model, AiPersona $persona, int $accountType, string $clueField, int $st, int $et): array
    {
        return $model::where('device_code', $persona->device_code)
            ->field($clueField)
            ->where('account_type', $accountType)
            ->where('create_time', 'between', [$st, $et])
            ->where($clueField, '<>', '')
            ->group($clueField)
            ->column($clueField);
    }

    private static function normalizeClue(mixed $value): string
    {
        $value = trim((string)$value);
        $prefixes = [
            "\xE6\x8A\x96\xE9\x9F\xB3\xE5\x8F\xB7\xEF\xBC\x9A",
            "\xE6\x8A\x96\xE9\x9F\xB3\xE5\x8F\xB7:",
            "\xE5\xB0\x8F\xE7\xBA\xA2\xE4\xB9\xA6\xE5\x8F\xB7\xEF\xBC\x9A",
            "\xE5\xB0\x8F\xE7\xBA\xA2\xE4\xB9\xA6\xE5\x8F\xB7:",
        ];

        return trim(str_replace($prefixes, '', $value));
    }
}
