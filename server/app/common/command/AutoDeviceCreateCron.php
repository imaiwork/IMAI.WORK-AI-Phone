<?php


namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;
use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\sv\SvDeviceTaskExistenceService;

/**
 * AutoDeviceCreateCron
 * @desc 自自动创建设备相关任务
 * @author dagouzi
 */
class AutoDeviceCreateCron extends Command
{
    private const RUNNING_LOCK_PREFIX = 'auto_device_create_cron:running:';
    private const RUNNING_LOCK_TTL = 21600;

    protected function configure()
    {
        $this->setName('auto_device_create_cron')
            ->setDescription('自动创建设备相关任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $date = date('Ymd');
        $runningLockKey = self::RUNNING_LOCK_PREFIX . $date;
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;
        $summary = [
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

        try {
            $lockAcquired = $this->acquireRunningLock($runningLockKey, $lockValue);
            if (!$lockAcquired) {
                return true;
            }

            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode, p.wechat_publish_mode, p.workflow_template_id')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                ->where('p.status', 1)
                ->where('d.is_first', 0)
                ->where('d.persona_id', '>', 0)
                ->select();
            \think\facade\Log::channel('auto')->write("24h自动创建设备相关任务SQL：" . \think\facade\Db::getLastSql(), 'create');
            \think\facade\Log::channel('auto')->write("24h自动创建设备相关任务设备列表：" . json_encode(array_column($devices->toArray(), 'device_code')), 'create');
            $summary['devices_total'] = count($devices);
            foreach ($devices as $device) {
                \think\facade\Log::channel('auto')->write($device->device_code . '每日自动化任务开始', 'create');
                $options = AiPersonaOptionService::getOptionsByPersonaId((int)$device->persona_id);
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

                $this->logPersonaTemplateSchedules($device);

                $viralResult = \app\api\logic\aiPersona\ViralRewriterLogic::autoViralRewriterTaskCron($device); //爆款复刻
                $this->mergeViralSummary($summary, is_array($viralResult) ? $viralResult : []);

                $dailyTaskExecuted = false;
                $deviceSlotResult = SvDeviceTaskExistenceService::emptySlotResult();

                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.sph_clues')) {
                    $slotResult = $this->runDailyTaskScenes($device, '精准线索任务', function () use ($device) {
                        return \app\api\logic\aiPersona\PreciseCluesLogic::autoPreciseCluesTaskCron($device);
                    });
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.sph_clues=0，跳过精准线索任务', 'create');
                }

                $slotResult = $this->runDailyTaskScenes($device, '养号任务', function () use ($device) {
                    return \app\api\logic\aiPersona\ActiveLogic::autoActiveTaskCron($device);
                });
                $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;

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
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
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
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
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
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.customer_service=0，跳过智能客服任务', 'create');
                }

                if ($device->publish_mode == 2) {
                    $slotResult = $this->runDailyTaskScenes($device, '素材发布任务', function () use ($device) {
                        return \app\api\logic\aiPersona\PublishLogic::materialPersonaPublishCron($device);
                    });
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
                }

                if ($device->wechat_publish_mode == 2) {
                    $slotResult = $this->runDailyTaskScenes($device, '朋友圈素材发布任务', function () use ($device) {
                        return \app\api\logic\aiPersona\PublishLogic::materialCirclePersonaPublishCron($device);
                    });
                    $this->mergeSlotSummary($deviceSlotResult, $slotResult);
                    $dailyTaskExecuted = $this->hasSlotActivity($slotResult) || $dailyTaskExecuted;
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

                \think\facade\Log::channel('auto')->write($device->device_code . "每日自动化任务完成\n\n", 'create');
            }

            Log::channel('auto')->write('AutoDeviceCreateCron执行汇总：' . json_encode($summary, JSON_UNESCAPED_UNICODE), 'create');
            return true;
        } catch (\Exception $e) {
            $summary['errors'][] = $e->getMessage();
            Log::channel('auto')->write('AutoDeviceCreateCron执行汇总：' . json_encode($summary, JSON_UNESCAPED_UNICODE), 'create');
            \think\facade\Log::channel('auto')->write('自动化失败' . $e->getMessage(), 'create');
            return false;
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($runningLockKey, $lockValue);
            }

            return true;
        }
    }

    /**
     * 子任务执行前记录人设工作流模板及节点时间段（便于排查）
     */
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

    private function acquireRunningLock(string $runningLockKey, string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx($runningLockKey, $lockValue)) {
                Log::channel('auto')->write('AutoDeviceCreateCron正在执行，跳过并发触发：' . date('Y-m-d H:i:s'), 'create');
                return false;
            }

            $redis->expire($runningLockKey, self::RUNNING_LOCK_TTL);
            return true;
        } catch (\Throwable $e) {
            Log::channel('auto')->write('AutoDeviceCreateCron获取运行锁失败，跳过执行：' . $e->getMessage(), 'create');
            return false;
        }
    }

    private function mergeViralSummary(array &$summary, array $result): void
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
    }

    private function runDailyTaskScenes(SvDevice $device, string $taskName, callable $callback): array
    {
        Log::channel('auto')->write($device->device_code . $taskName . '开始按平台时段补生成', 'create');
        $result = $callback();
        return $this->normalizeSlotResult($result);
    }

    private function normalizeSlotResult(mixed $result): array
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

    private function mergeSlotSummary(array &$summary, array $result): void
    {
        $summary['created'] += (int)($result['created'] ?? 0);
        $summary['skipped_existing'] += (int)($result['skipped_existing'] ?? 0);
        $summary['skipped_no_account'] += (int)($result['skipped_no_account'] ?? 0);
        $summary['skipped_empty_schedule'] += (int)($result['skipped_empty_schedule'] ?? 0);
    }

    private function hasSlotActivity(array $result): bool
    {
        return ((int)($result['created'] ?? 0)) > 0
            || ((int)($result['skipped_existing'] ?? 0)) > 0
            || ((int)($result['skipped_no_account'] ?? 0)) > 0
            || ((int)($result['skipped_empty_schedule'] ?? 0)) > 0;
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

    private function releaseRunningLock(string $runningLockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get($runningLockKey) === $lockValue) {
                $redis->del($runningLockKey);
            }
        } catch (\Throwable $e) {
            Log::channel('auto')->write('AutoDeviceCreateCron释放运行锁失败：' . $e->getMessage(), 'create');
        }
    }
}
