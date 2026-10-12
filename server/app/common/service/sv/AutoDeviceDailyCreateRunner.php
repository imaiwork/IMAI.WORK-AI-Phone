<?php

namespace app\common\service\sv;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 24h 设备任务建单：运营/爆款 与 素材发布（内容+朋友圈）分档跑，各持短锁。
 */
class AutoDeviceDailyCreateRunner
{
    public const SCENE_OPS = 'ops';
    public const SCENE_PUBLISH = 'publish';

    /** 运营建单每轮台数：日志里纯运营约 1s/台 */
    public const BATCH_LIMIT_OPS = 15;
    /** 发布建单每轮台数：指定素材写文案中位约 38s、最长约 85s */
    public const BATCH_LIMIT_PUBLISH = 2;
    public const PROGRESS_TTL = 129600;

    /** 执行窗口（小时，半开区间 [start, end)） */
    public const EXEC_WINDOW_OPS = ['start' => 0.0, 'end' => 3.0];
    public const EXEC_WINDOW_PUBLISH = ['start' => 0.0, 'end' => 6.0];

    private const RUNNING_LOCK_PREFIX = 'auto_device_create_cron:running:';
    private const PROGRESS_KEY_PREFIX = 'auto_device_create_cron:progress:';
    public const RUNNING_LOCK_TTL = 600;

    public static function runningLockKey(string $scene, string $ymd): string
    {
        return self::RUNNING_LOCK_PREFIX . $scene . ':' . $ymd;
    }

    public static function progressKey(string $scene, string $ymd): string
    {
        return self::PROGRESS_KEY_PREFIX . $scene . ':' . $ymd;
    }

    /**
     * @return array{cursor_id:int,completed:int,updated_at:string}
     */
    public static function emptyProgress(): array
    {
        return [
            'cursor_id' => 0,
            'completed' => 0,
            'updated_at' => '',
        ];
    }

    /**
     * @return array{cursor_id:int,completed:int,updated_at:string}
     */
    public static function parseProgress(mixed $raw): array
    {
        $progress = self::emptyProgress();
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $data = $raw;
        } else {
            return $progress;
        }
        if (!is_array($data)) {
            return $progress;
        }

        $progress['cursor_id'] = max(0, (int)($data['cursor_id'] ?? 0));
        $progress['completed'] = !empty($data['completed']) ? 1 : 0;
        $progress['updated_at'] = (string)($data['updated_at'] ?? '');

        return $progress;
    }

    public static function encodeProgress(array $progress, string $updatedAt = ''): string
    {
        return json_encode([
            'cursor_id' => max(0, (int)($progress['cursor_id'] ?? 0)),
            'completed' => !empty($progress['completed']) ? 1 : 0,
            'updated_at' => $updatedAt !== '' ? $updatedAt : date('c'),
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function isProgressCompleted(array $progress): bool
    {
        return !empty($progress['completed']);
    }

    /**
     * @param int[] $deviceIds 本批已处理的设备 id，按 id 升序
     * @return array{cursor_id:int,completed:int,updated_at:string}
     */
    public static function progressAfterBatch(array $progress, array $deviceIds, int $batchLimit): array
    {
        if ($deviceIds === [] || count($deviceIds) < $batchLimit) {
            $progress['completed'] = 1;
            return $progress;
        }

        $progress['cursor_id'] = max((int)($progress['cursor_id'] ?? 0), (int)end($deviceIds));
        $progress['completed'] = 0;

        return $progress;
    }

    public static function isValidScene(string $scene): bool
    {
        return $scene === self::SCENE_OPS || $scene === self::SCENE_PUBLISH;
    }

    public static function batchLimit(string $scene): int
    {
        if ($scene === self::SCENE_PUBLISH) {
            return self::BATCH_LIMIT_PUBLISH;
        }
        if ($scene === self::SCENE_OPS) {
            return self::BATCH_LIMIT_OPS;
        }

        return 0;
    }

    /**
     * @return array{start:float,end:float}|null
     */
    public static function execWindow(string $scene): ?array
    {
        if ($scene === self::SCENE_OPS) {
            return self::EXEC_WINDOW_OPS;
        }
        if ($scene === self::SCENE_PUBLISH) {
            return self::EXEC_WINDOW_PUBLISH;
        }

        return null;
    }

    public static function isInExecWindow(string $scene, ?float $hour = null): bool
    {
        $window = self::execWindow($scene);
        if ($window === null) {
            return false;
        }

        $hour ??= (float)date('G') + (float)date('i') / 60;

        return $hour >= $window['start'] && $hour < $window['end'];
    }

    public static function execWindowLabel(string $scene): string
    {
        $window = self::execWindow($scene);
        if ($window === null) {
            return '';
        }

        return sprintf('%02d:00-%02d:00', (int)$window['start'], (int)$window['end']);
    }

    public static function isCloudRun(int $runMode): bool
    {
        return $runMode === AiPersona::RUN_MODE_CLOUD;
    }

    public static function shouldCreateContentPublish(int $runMode, int $publishMode): bool
    {
        return !self::isCloudRun($runMode) && $publishMode === 2;
    }

    public static function shouldCreateCirclePublish(int $runMode, int $wechatPublishMode): bool
    {
        return !self::isCloudRun($runMode) && $wechatPublishMode === 2;
    }

    /**
     * @return array{viral:bool,ops_daily:bool,content_publish:bool,circle_publish:bool}
     */
    public static function resolveWork(string $scene, int $runMode, int $publishMode, int $wechatPublishMode): array
    {
        $isOps = $scene === self::SCENE_OPS;
        $isPublish = $scene === self::SCENE_PUBLISH;

        return [
            'viral' => $isOps,
            'ops_daily' => $isOps,
            'content_publish' => $isPublish && self::shouldCreateContentPublish($runMode, $publishMode),
            'circle_publish' => $isPublish && self::shouldCreateCirclePublish($runMode, $wechatPublishMode),
        ];
    }

    public static function emptySummary(): array
    {
        return [
            'devices_total' => 0,
            'viral_created' => 0,
            'viral_created_from_record_pool' => 0,
            'viral_skipped_existing' => 0,
            'viral_skipped_not_configured' => 0,
            'viral_skipped_no_account' => 0,
            'viral_failed' => 0,
            'daily_task_devices' => 0,
            'daily_task_skipped_devices' => 0,
            'daily_task_created' => 0,
            'daily_task_skipped_existing' => 0,
            'daily_task_skipped_no_account' => 0,
            'daily_task_skipped_empty_schedule' => 0,
            'errors' => [],
        ];
    }

    public static function mergeViralSummary(array $summary, array $result): array
    {
        $summary['viral_created'] += (int)($result['created'] ?? 0);
        $summary['viral_created_from_record_pool'] += (int)($result['created_from_record_pool'] ?? 0);
        $summary['viral_skipped_existing'] += (int)($result['skipped_existing'] ?? 0);
        $summary['viral_skipped_not_configured'] += (int)($result['skipped_not_configured'] ?? 0);
        $summary['viral_skipped_no_account'] += (int)($result['skipped_no_account'] ?? 0);
        $summary['viral_failed'] += (int)($result['failed'] ?? 0);
        foreach (($result['errors'] ?? []) as $error) {
            if ($error !== '') {
                $summary['errors'][] = $error;
            }
        }

        return $summary;
    }

    public static function normalizeSlotResult(mixed $result): array
    {
        if (!is_array($result)) {
            return SvDeviceTaskExistenceService::emptySlotResult();
        }

        return [
            'created' => (int)($result['created'] ?? 0),
            'skipped_existing' => (int)($result['skipped_existing'] ?? 0),
            'skipped_no_account' => (int)($result['skipped_no_account'] ?? 0),
            'skipped_empty_schedule' => (int)($result['skipped_empty_schedule'] ?? 0),
        ];
    }

    public static function mergeSlotSummary(array $summary, array $result): array
    {
        $summary['created'] += (int)($result['created'] ?? 0);
        $summary['skipped_existing'] += (int)($result['skipped_existing'] ?? 0);
        $summary['skipped_no_account'] += (int)($result['skipped_no_account'] ?? 0);
        $summary['skipped_empty_schedule'] += (int)($result['skipped_empty_schedule'] ?? 0);

        return $summary;
    }

    public static function hasSlotActivity(array $result): bool
    {
        return ((int)($result['created'] ?? 0)) > 0
            || ((int)($result['skipped_existing'] ?? 0)) > 0
            || ((int)($result['skipped_no_account'] ?? 0)) > 0
            || ((int)($result['skipped_empty_schedule'] ?? 0)) > 0;
    }

    public function run(string $scene): bool
    {
        if (!self::isValidScene($scene)) {
            Log::channel('auto')->write('AutoDeviceDailyCreateRunner未知档位：' . $scene, 'create');
            return false;
        }

        $label = self::commandLabel($scene);
        if (!self::isInExecWindow($scene)) {
            Log::channel('auto')->write(
                $label . '不在执行窗口，跳过：' . date('H:i') . ' 窗口=' . self::execWindowLabel($scene),
                'create'
            );
            return true;
        }

        $date = date('Ymd');
        $progress = $this->loadProgress($scene, $date);
        if (self::isProgressCompleted($progress)) {
            Log::channel('auto')->write($label . '当日分页已完成，跳过：' . $date, 'create');
            return true;
        }

        $runningLockKey = self::runningLockKey($scene, $date);
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;
        $summary = self::emptySummary();

        try {
            $lockAcquired = $this->acquireRunningLock($runningLockKey, $lockValue, $label);
            if (!$lockAcquired) {
                return true;
            }

            $devices = $this->queryDevices($scene, (int)$progress['cursor_id']);
            Log::channel('auto')->write(self::roundTitle($scene) . 'SQL：' . Db::getLastSql(), 'create');
            Log::channel('auto')->write(self::roundTitle($scene) . '设备列表：' . json_encode(array_column($devices->toArray(), 'device_code')), 'create');
            $summary['devices_total'] = count($devices);

            $deviceIds = [];
            foreach ($devices as $device) {
                $deviceIds[] = (int)$device->id;
                try {
                    $this->processDevice($scene, $device, $summary);
                } catch (\Throwable $e) {
                    $summary['errors'][] = $device->device_code . ':' . $e->getMessage();
                    Log::channel('auto')->write(
                        $label . '单台处理失败 device=' . $device->device_code . ' ' . $e->getMessage(),
                        'create'
                    );
                }
            }

            $progress = self::progressAfterBatch($progress, $deviceIds, self::batchLimit($scene));
            $this->saveProgress($scene, $date, $progress);
            $summary['cursor_id'] = (int)$progress['cursor_id'];
            $summary['completed'] = (int)$progress['completed'];
            $summary['batch_limit'] = self::batchLimit($scene);
            if (self::isProgressCompleted($progress)) {
                Log::channel('auto')->write($label . '当日分页完成：' . $date, 'create');
            }

            Log::channel('auto')->write($label . '执行汇总：' . json_encode($summary, JSON_UNESCAPED_UNICODE), 'create');
            return true;
        } catch (\Exception $e) {
            $summary['errors'][] = $e->getMessage();
            Log::channel('auto')->write($label . '执行汇总：' . json_encode($summary, JSON_UNESCAPED_UNICODE), 'create');
            Log::channel('auto')->write($label . '自动化失败' . $e->getMessage(), 'create');
            return false;
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($runningLockKey, $lockValue, $label);
            }
        }
    }

    private function queryDevices(string $scene, int $cursorId)
    {
        $query = SvDevice::alias('d')
            ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode, p.wechat_publish_mode, p.workflow_template_id, p.run_mode')
            ->join('ai_persona p', 'd.persona_id = p.id')
            ->where('d.auto_type', 1)
            ->where('d.is_first', 0)
            ->where('d.persona_id', '>', 0)
            ->where('d.id', '>', $cursorId);

        if ($scene === self::SCENE_PUBLISH) {
            $query->where('p.run_mode', '<>', AiPersona::RUN_MODE_CLOUD)
                ->where(function ($q) {
                    $q->where('p.publish_mode', 2)
                        ->whereOr('p.wechat_publish_mode', 2);
                });
        }

        return $query->order('d.id', 'asc')->limit(self::batchLimit($scene))->select();
    }

    private function processDevice(string $scene, SvDevice $device, array &$summary): void
    {
        $startLabel = $scene === self::SCENE_PUBLISH ? '每日素材发布任务开始' : '每日自动化任务开始';
        $doneLabel = $scene === self::SCENE_PUBLISH ? '每日素材发布任务完成' : '每日自动化任务完成';
        Log::channel('auto')->write($device->device_code . $startLabel, 'create');

        $runMode = (int)($device->run_mode ?? AiPersona::RUN_MODE_PHONE);
        $publishMode = (int)($device->publish_mode ?? 0);
        $wechatPublishMode = (int)($device->wechat_publish_mode ?? 0);
        $work = self::resolveWork($scene, $runMode, $publishMode, $wechatPublishMode);

        $options = [];
        if ($work['ops_daily'] || $work['viral']) {
            $options = AiPersonaOptionService::getOptionsByPersonaId((int)$device->persona_id);
            $this->logOpsDeviceCheck($device, $options);
        }

        $this->logPersonaTemplateSchedules($device);

        if ($work['viral']) {
            $viralResult = \app\api\logic\aiPersona\ViralRewriterLogic::autoViralRewriterTaskCron($device);
            $summary = self::mergeViralSummary($summary, is_array($viralResult) ? $viralResult : []);
        }

        $dailyTaskExecuted = false;
        $deviceSlotResult = SvDeviceTaskExistenceService::emptySlotResult();

        if ($work['ops_daily']) {
            [$deviceSlotResult, $dailyTaskExecuted] = $this->runOpsDailyTasks($device, $options, $deviceSlotResult);
        }

        if ($scene === self::SCENE_PUBLISH && self::isCloudRun($runMode)) {
            Log::channel('auto')->write($device->device_code . ' 无手机模式，跳过素材发布/朋友圈素材发布任务', 'create');
        }

        if ($work['content_publish']) {
            $slotResult = $this->runDailyTaskScenes($device, '素材发布任务', function () use ($device) {
                return \app\api\logic\aiPersona\PublishLogic::materialPersonaPublishCron($device);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        }

        if ($work['circle_publish']) {
            $slotResult = $this->runDailyTaskScenes($device, '朋友圈素材发布任务', function () use ($device) {
                return \app\api\logic\aiPersona\PublishLogic::materialCirclePersonaPublishCron($device);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        }

        $summary['daily_task_created'] += (int)$deviceSlotResult['created'];
        $summary['daily_task_skipped_existing'] += (int)$deviceSlotResult['skipped_existing'];
        $summary['daily_task_skipped_no_account'] += (int)$deviceSlotResult['skipped_no_account'];
        $summary['daily_task_skipped_empty_schedule'] += (int)$deviceSlotResult['skipped_empty_schedule'];

        if ($dailyTaskExecuted) {
            $summary['daily_task_devices']++;
            $this->logDailyTaskFinishReason($device->device_code, $deviceSlotResult, true);
        } else {
            $summary['daily_task_skipped_devices']++;
            $this->logDailyTaskFinishReason($device->device_code, $deviceSlotResult, false);
        }

        Log::channel('auto')->write($device->device_code . $doneLabel . "\n\n", 'create');
    }

    private function runOpsDailyTasks(SvDevice $device, array $options, array $deviceSlotResult): array
    {
        $dailyTaskExecuted = false;

        if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.sph_clues')) {
            $slotResult = $this->runDailyTaskScenes($device, '精准线索任务', function () use ($device) {
                return \app\api\logic\aiPersona\PreciseCluesLogic::autoPreciseCluesTaskCron($device);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        } else {
            Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.sph_clues=0，跳过精准线索任务', 'create');
        }

        if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.dy_search')) {
            $slotResult = $this->runDailyTaskScenes($device, '抖音搜寻获客任务', function () use ($device) {
                return \app\api\logic\device\DySearchTaskLogic::autoDySearchTaskCron($device);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        } else {
            Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.dy_search=0，跳过抖音搜寻获客任务', 'create');
        }

        $slotResult = $this->runDailyTaskScenes($device, '养号任务', function () use ($device) {
            return \app\api\logic\aiPersona\ActiveLogic::autoActiveTaskCron($device);
        });
        $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
        $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;

        if (AiPersonaOptionService::isEnabled($options, 'auto_clues.status')) {
            $slotResult = $this->runDailyTaskScenes($device, '获客截流任务', function () use ($device) {
                return \app\api\logic\aiPersona\ClueTouchLogic::trafficTaskCron($device, [
                    DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
                    DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
                    DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
                    DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE,
                    DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE,
                    DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF,
                    DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY,
                ]);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        } else {
            Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.status=0，跳过获客截流任务', 'create');
        }

        if (AiPersonaOptionService::isEnabled($options, 'private_operation.status')) {
            $slotResult = $this->runDailyTaskScenes($device, '私域运营任务', function () use ($device) {
                return \app\api\logic\aiPersona\InteractiveLogic::autoInteractiveTaskCron($device, [
                    DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT,
                    DeviceEnum::AUTO_TASK_SCENE_FRIENDS,
                ]);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        } else {
            Log::channel('auto')->write($device->device_code . ' global_option.private_operation.status=0，跳过私域运营任务', 'create');
        }

        if (AiPersonaOptionService::isEnabled($options, 'customer_service')) {
            $slotResult = $this->runDailyTaskScenes($device, '智能客服任务', function () use ($device) {
                return \app\api\logic\aiPersona\TakeOverLogic::autoTakeOverTaskCron($device, [
                    DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER,
                    DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER,
                    DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE,
                ]);
            });
            $deviceSlotResult = self::mergeSlotSummary($deviceSlotResult, $slotResult);
            $dailyTaskExecuted = self::hasSlotActivity($slotResult) || $dailyTaskExecuted;
        } else {
            Log::channel('auto')->write($device->device_code . ' global_option.customer_service=0，跳过智能客服任务', 'create');
        }

        return [$deviceSlotResult, $dailyTaskExecuted];
    }

    private function logOpsDeviceCheck(SvDevice $device, array $options): void
    {
        $hotWordsEnabled = AiPersonaOptionService::isEnabled($options, 'hot_words');
        $copywritingSource = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
            ->order('id desc')
            ->value('copywriting_source');
        $douyinAccountCount = SvAccount::where('type', 4)
            ->where('user_id', $device->user_id)
            ->where('device_code', $device->device_code)
            ->count();

        Log::channel('auto')->write($device->device_code . '每日自动化任务设备检查：' . json_encode([
            'persona_id' => (int)$device->persona_id,
            'hot_words' => $hotWordsEnabled ? 1 : 0,
            'copywriting_source' => $copywritingSource,
            'douyin_account_count' => $douyinAccountCount,
            'daily_tasks_enabled_this_run' => 1,
            'options' => $options,
        ], JSON_UNESCAPED_UNICODE), 'create');
    }

    private function logPersonaTemplateSchedules(SvDevice $device): void
    {
        $personaId = (int)$device->persona_id;
        $templateId = (int)($device->workflow_template_id ?? 0);
        $schedules = [];

        if ($templateId > 0) {
            $rows = MarketingTemplateSchedule::where('template_id', $templateId)
                ->field('id,scene,start_time,end_time')
                ->order(['scene' => 'asc', 'start_time' => 'asc'])
                ->select()
                ->toArray();
            foreach ($rows as $row) {
                $schedules[] = [
                    'id' => (int)$row['id'],
                    'scene' => (int)$row['scene'],
                    'time' => ($row['start_time'] ?? '') . '-' . ($row['end_time'] ?? ''),
                ];
            }
        }

        Log::channel('auto')->write($device->device_code . '每日自动化任务人设模板：' . json_encode([
            'persona_id' => $personaId,
            'workflow_template_id' => $templateId,
            'schedule_count' => count($schedules),
            'schedules' => $schedules,
        ], JSON_UNESCAPED_UNICODE), 'create');
    }

    private function runDailyTaskScenes(SvDevice $device, string $taskName, callable $callback): array
    {
        Log::channel('auto')->write($device->device_code . $taskName . '开始按平台时段补生成', 'create');
        return self::normalizeSlotResult($callback());
    }

    private function logDailyTaskFinishReason(string $deviceCode, array $slotResult, bool $hasActivity): void
    {
        $created = (int)($slotResult['created'] ?? 0);
        $skippedExisting = (int)($slotResult['skipped_existing'] ?? 0);
        $skippedNoAccount = (int)($slotResult['skipped_no_account'] ?? 0);
        $skippedEmptySchedule = (int)($slotResult['skipped_empty_schedule'] ?? 0);
        $counts = [
            'created' => $created,
            'skipped_existing' => $skippedExisting,
            'skipped_no_account' => $skippedNoAccount,
            'skipped_empty_schedule' => $skippedEmptySchedule,
        ];

        if ($created > 0) {
            return;
        }

        if ($skippedNoAccount > 0 || $skippedEmptySchedule > 0) {
            Log::channel('auto')->write(
                $deviceCode . '非爆款每日任务本轮空跑：账号未绑定或日程为空 ' . json_encode($counts, JSON_UNESCAPED_UNICODE),
                'create'
            );
            return;
        }

        if ($skippedExisting > 0) {
            Log::channel('auto')->write(
                $deviceCode . '非爆款每日任务今日槽位均已存在 ' . json_encode($counts, JSON_UNESCAPED_UNICODE),
                'create'
            );
            return;
        }

        if (!$hasActivity) {
            Log::channel('auto')->write(
                $deviceCode . '非爆款每日任务本轮无产出：开关未启用或子任务提前返回 ' . json_encode($counts, JSON_UNESCAPED_UNICODE),
                'create'
            );
        }
    }

    private function loadProgress(string $scene, string $ymd): array
    {
        try {
            return self::parseProgress(Cache::store('redis')->get(self::progressKey($scene, $ymd)));
        } catch (\Throwable $e) {
            Log::channel('auto')->write(self::commandLabel($scene) . '读取分页进度失败：' . $e->getMessage(), 'create');
            return self::emptyProgress();
        }
    }

    private function saveProgress(string $scene, string $ymd, array $progress): void
    {
        try {
            Cache::store('redis')->set(self::progressKey($scene, $ymd), self::encodeProgress($progress), self::PROGRESS_TTL);
        } catch (\Throwable $e) {
            Log::channel('auto')->write(self::commandLabel($scene) . '写入分页进度失败：' . $e->getMessage(), 'create');
        }
    }

    private function acquireRunningLock(string $runningLockKey, string $lockValue, string $label): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx($runningLockKey, $lockValue)) {
                Log::channel('auto')->write($label . '正在执行，跳过并发触发：' . date('Y-m-d H:i:s'), 'create');
                return false;
            }

            $redis->expire($runningLockKey, self::RUNNING_LOCK_TTL);
            return true;
        } catch (\Throwable $e) {
            Log::channel('auto')->write($label . '获取运行锁失败，跳过执行：' . $e->getMessage(), 'create');
            return false;
        }
    }

    private function releaseRunningLock(string $runningLockKey, string $lockValue, string $label): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get($runningLockKey) === $lockValue) {
                $redis->del($runningLockKey);
            }
        } catch (\Throwable $e) {
            Log::channel('auto')->write($label . '释放运行锁失败：' . $e->getMessage(), 'create');
        }
    }

    private static function commandLabel(string $scene): string
    {
        return $scene === self::SCENE_PUBLISH ? 'AutoDevicePublishCreateCron' : 'AutoDeviceCreateCron';
    }

    private static function roundTitle(string $scene): string
    {
        return $scene === self::SCENE_PUBLISH ? '24h素材发布任务生成' : '24h自动创建设备相关任务';
    }
}
