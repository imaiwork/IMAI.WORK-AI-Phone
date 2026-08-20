<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\api\logic\aiPersona\PublishLogic;
use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceViral;
use app\common\model\sv\SvDeviceViralRecord;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class ViralRewriterDeadlineFallbackService
{
    public const FALLBACK_TIME = '03:00';//03:00
    public const LAST_SCAN_TIME = '06:00';//06:00
    public const IMAGE_TEXT_FALLBACK_TIME = '05:00';//05:00
    public const IMAGE_TEXT_LAST_SCAN_TIME = '07:00';//07:00

    private const LOCK_TTL = 120;

    /**
     * 扫描当天未完成的爆款仿写任务：视频兜底为3点至6点，图文兜底为5点至7点。
     */
    public function scanAndTrigger(int $currentTime, bool $isDev, ?Output $output = null): void
    {
        $today = date('Y-m-d', $currentTime);
        $deadline = strtotime($today . ' ' . self::FALLBACK_TIME . ':00');
        if ($deadline === false || $currentTime < $deadline) {
            return;
        }

        $lastDeadline = strtotime($today . ' ' . self::IMAGE_TEXT_LAST_SCAN_TIME . ':00');
        if ($currentTime > $lastDeadline) {
            return;
        }

        
        $tasks = SvDeviceTask::field('*')
            ->where('status', 'in', [
                DeviceEnum::TASK_STATUS_WAIT,
                DeviceEnum::TASK_STATUS_RUNNING,
                DeviceEnum::TASK_STATUS_FINISHED,
                DeviceEnum::TASK_STATUS_FAILED,
            ])
            ->where('auto_type', '=', 1)
            ->where('task_type', '=', DeviceEnum::TASK_TYPE_VIRAL_REWRITER)
            ->where('day', '=', $today)
            ->where('start_time', '<=', $currentTime)
            ->where('device_code', 'in', function ($query) {
                $query->name('sv_device')->field('device_code')->where('auto_type', '=', 1);
            })
            ->select();

        foreach ($this->dedupeTasksForScan($tasks) as $task) {
            $this->log("爆款仿写兜底扫描命中: ID={$task->id}, user_id={$task->user_id}, 设备={$task->device_code}, status={$task->status}, day={$task->day}, start_time={$task->start_time}, end_time={$task->end_time}");
            $result = $this->triggerForTask($task, $currentTime, true);
            $this->log('爆款仿写兜底扫描触发: ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($result['triggered'] ?? false) {
                $this->log("\n爆款仿写兜底扫描触发: ID={$task->id}, 设备={$task->device_code}, 结果={$result['msg']}");
                if ($isDev && $output !== null) {
                    $output->writeln($task->device_code . "爆款仿写兜底扫描触发: ID={$task->id}, {$result['msg']}");
                }
            }
        }
    }

    /**
     * 视频任务3点兜底补齐降级文案；图文任务5点先生成图文发布记录，缺口再用视频填坑。
     *
     * @return array{triggered:bool,created:int,msg:string}
     */
    public function triggerForTask(SvDeviceTask $task, ?int $currentTime = null, bool $fromScan = false): array
    {
        $viralTask = $this->getTaskConfig($task);
        if ($viralTask->isEmpty()) {
            return ['triggered' => true, 'created' => 0, 'msg' => $task->device_code . '爆款仿写任务不存在'];
        }

        $isImageTextTask = (int)($viralTask->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO)
            === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
        if (!$this->isFallbackTime($isImageTextTask, $currentTime, $fromScan)) {
            return [
                'triggered' => false,
                'created' => 0,
                'msg' => $task->device_code . $this->buildFallbackTimeSkipReason($isImageTextTask, $currentTime, $fromScan),
            ];
        }

        $taskDay = $this->getTaskDay($task);
        if ($isImageTextTask && $this->isImageTextFallbackSettled(
            $taskDay,
            (int)$task->user_id,
            (string)$task->device_code,
            (int)$viralTask->persona_id
        )) {
            return [
                'triggered' => false,
                'created' => 0,
                'msg' => $task->device_code . '图文兜底已结案，跳过',
            ];
        }

        $lockKey = $this->buildLockKey($taskDay, (int)$task->user_id, (string)$task->device_code, (int)$viralTask->persona_id);
        $lockValue = (string)(getmypid() ?: 0) . ':' . microtime(true);
        if (!$this->acquireLock($lockKey, $lockValue)) {
            return ['triggered' => false, 'created' => 0, 'msg' => $task->device_code . '兜底处理中，跳过本次'];
        }

        try {
            return $this->runTriggerForTask($task, $viralTask);
        } finally {
            $this->releaseLock($lockKey, $lockValue);
        }
    }

    /**
     * @return array{triggered:bool,created:int,msg:string}
     */
    private function runTriggerForTask(SvDeviceTask $task, SvDeviceViral $viralTask): array
    {
        $isImageTextTask = (int)($viralTask->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO)
            === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;

        try {
            $publishTimeCount = $this->getPublishTimeCount($viralTask);
            if ($publishTimeCount <= 0) {
                return ['triggered' => false, 'created' => 0, 'msg' => $task->device_code . '发布时段为空，无需兜底'];
            }

            $successCount = $this->getSuccessCount($task, $viralTask);
            $shortage = max(0, $publishTimeCount - $successCount);

            // 图文任务：即使配额已满，仍需补扫成功图文生成发布记录，再对空坑视频兜底
            if ($isImageTextTask) {
                return $this->triggerImageTextVideoFallback($task, $viralTask, $publishTimeCount, $successCount, $shortage);
            }

            if ($shortage <= 0) {
                return ['triggered' => false, 'created' => 0, 'msg' => $task->device_code . '已达到发布时段文案数'];
            }

            $this->log("爆款仿写3点兜底开始: 任务ID={$task->id}, 发布数={$publishTimeCount}, 已生成={$successCount}, 待补齐={$shortage}, 设备={$task->device_code}");

            $taskDay = $this->getTaskDay($task);
            $failureRemark = $this->resolveLastFailureRemark(
                (string)$task->device_code,
                $taskDay,
                (int)$viralTask->persona_id,
                (int)($viralTask->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO)
            );

            $created = 0;
            for ($i = 0; $i < $shortage; $i++) {
                if ($this->getSuccessCount($task, $viralTask) >= $publishTimeCount) {
                    break;
                }
                if ($this->createFallbackCopywriting($viralTask, $task, $i + 1, $shortage, $failureRemark)) {
                    $created++;
                }
            }

            if ($created < $shortage && $this->getSuccessCount($task, $viralTask) < $publishTimeCount) {
                return ['triggered' => true, 'created' => $created, 'msg' => $task->device_code . "3点兜底部分失败，已补齐{$created}条"];
            }

            return ['triggered' => $created > 0, 'created' => $created, 'msg' => $task->device_code . "3点兜底完成，补齐降级文案{$created}条"];
        } catch (\Throwable $th) {
            $fallbackHour = $isImageTextTask ? '5点图文' : '3点';
            $this->log($task->device_code . '爆款仿写' . $fallbackHour . '兜底异常: ' . $th->getTraceAsString());
            return ['triggered' => true, 'created' => 0, 'msg' => $task->device_code . $fallbackHour . '兜底异常:' . $th->getMessage()];
        }
    }

    private function isFallbackTime(
        bool $isImageTextTask = false,
        ?int $currentTime = null,
        bool $enforceVideoScanEnd = false
    ): bool
    {
        return $this->buildFallbackTimeSkipReason($isImageTextTask, $currentTime, $enforceVideoScanEnd) === '';
    }

    /**
     * 未到开始时间或已过扫描截止时返回原因文案；可兜底时返回空串。
     */
    private function buildFallbackTimeSkipReason(
        bool $isImageTextTask = false,
        ?int $currentTime = null,
        bool $enforceVideoScanEnd = false
    ): string
    {
        $currentTime = $currentTime ?? time();
        $today = date('Y-m-d', $currentTime);
        $startLabel = $isImageTextTask ? '5点' : '3点';
        $startTime = $isImageTextTask ? self::IMAGE_TEXT_FALLBACK_TIME : self::FALLBACK_TIME;
        $deadline = strtotime($today . ' ' . $startTime . ':00');
        if ($deadline === false || $currentTime < $deadline) {
            return '未到' . $startLabel . '兜底时间';
        }

        // 图文兜底严格限制在5点至7点；视频扫描限制在3点至6点，完成回调在3点后仍可触发。
        if ($isImageTextTask || $enforceVideoScanEnd) {
            $endLabel = $isImageTextTask ? '7点' : '6点';
            $endTime = $isImageTextTask ? self::IMAGE_TEXT_LAST_SCAN_TIME : self::LAST_SCAN_TIME;
            $lastDeadline = strtotime($today . ' ' . $endTime . ':00');
            if ($lastDeadline === false || $currentTime > $lastDeadline) {
                return '已过' . $endLabel . '兜底扫描时间';
            }
        }

        return '';
    }

    /**
     * 5点图文兜底：先为改写成功的图文生成发布记录，再对剩余空坑用视频填坑。
     *
     * @return array{triggered:bool,created:int,msg:string}
     */
    private function triggerImageTextVideoFallback(SvDeviceTask $task, SvDeviceViral $viralTask, int $publishTimeCount, int $successCount, int $shortage): array
    {
        $device = SvDevice::where('device_code', (string)$task->device_code)
            ->where('user_id', (int)$task->user_id)
            ->where('persona_id', (int)$viralTask->persona_id)
            ->findOrEmpty();
        if ($device->isEmpty()) {
            return ['triggered' => true, 'created' => 0, 'msg' => $task->device_code . '小红书图文视频兜底失败：设备不存在'];
        }

        $taskDay = $this->getTaskDay($task);
        $userId = (int)$task->user_id;
        $deviceCode = (string)$task->device_code;
        $personaId = (int)$viralTask->persona_id;

        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return ['triggered' => true, 'created' => 0, 'msg' => $deviceCode . '小红书图文视频兜底失败：IP人设不存在'];
        }

        $hasSlot = PublishLogic::hasAvailableImageTextPublishSlot(
            $device,
            $persona,
            $taskDay,
            AiPersona::PUBLISH_PLATFORM_XHS
        );
        $hasUnusedInventory = !empty(ViralImageTextPublishFillService::listUnusedRecords(
            $userId,
            $deviceCode,
            $personaId,
            1
        ));
        $hasPending = PublishLogic::hasPendingImageTextViralWithoutPublish($device, $taskDay);

        // 无空坑且无待填库存：无需再跑 fill/视频兜底；无 pending 时结案避免每分钟空跑
        if (!$hasSlot && !$hasUnusedInventory) {
            if (!$hasPending) {
                $this->markImageTextFallbackSettled($taskDay, $userId, $deviceCode, $personaId);
            }
            $this->log(
                $deviceCode . '图文兜底早退：无空闲发布时段且无待填库存'
                . ($hasPending ? '（存在改写未完成，暂不结案）' : '，已结案')
                . "，任务ID={$task->id}, 日期={$taskDay}, 图文成功={$successCount}, 图文缺口={$shortage}"
            );

            return [
                'triggered' => false,
                'created' => 0,
                'msg' => $deviceCode . ($hasPending
                    ? '小红书图文视频兜底跳过：存在改写未完成的图文仿写且无空闲发布时段'
                    : '图文兜底已结案，跳过'),
            ];
        }

        $this->log("爆款仿写5点图文兜底开始: 任务ID={$task->id}, 发布数={$publishTimeCount}, 已生成={$successCount}, 待补齐={$shortage}, 设备={$deviceCode}");

        // 与定时填坑共用：跨天未使用库存按 id ASC 填目标发布日空闲时段
        $fillResult = ViralImageTextPublishFillService::fillGroup(
            $userId,
            $deviceCode,
            $personaId,
            $taskDay
        );
        $imagePublishCreated = (int)($fillResult['created'] ?? 0);
        $imagePublishFailed = (int)($fillResult['failed'] ?? 0);
        $readyCount = $imagePublishCreated + $imagePublishFailed;

        if ($imagePublishCreated === 0 && $imagePublishFailed === 0) {
            $this->log(
                "图文仿写不足用视频兜底：无成功图文仿写，改用视频兜底。任务ID={$task->id}, 日期={$taskDay}, 发布时间段={$publishTimeCount}, 图文成功={$successCount}, 图文缺口={$shortage}, 设备={$deviceCode}"
                . (($fillResult['error'] ?? '') !== '' ? ', tip=' . $fillResult['error'] : '')
            );
        } else {
            $this->log(
                "5点图文发布补扫完成: 任务ID={$task->id}, 日期={$taskDay}, 待生成={$readyCount}, 成功={$imagePublishCreated}, 失败={$imagePublishFailed}, 设备={$deviceCode}"
            );
        }

        // 按发布时间段数作为上限，实际创建条数由空时段数截断
        $fallback = PublishLogic::createShanjianVideoFallbackForImageText($device, $taskDay, $publishTimeCount);
        $created = (int)($fallback['created'] ?? 0);
        $emptySlots = (int)($fallback['empty_slots'] ?? 0);
        $availableVideos = (int)($fallback['available_videos'] ?? 0);
        $requested = (int)($fallback['requested'] ?? 0);
        $remaining = max(0, $requested - $created);
        $fallbackMsg = (string)($fallback['msg'] ?? '');
        $totalCreated = $imagePublishCreated + $created;

        $this->log(
            "图文仿写不足用视频兜底：任务ID={$task->id}, 日期={$taskDay}, 发布时间段={$publishTimeCount}, 图文成功={$successCount}, 图文缺口={$shortage}, 图文发布已生成={$imagePublishCreated}, 空时段={$emptySlots}, 可用视频={$availableVideos}, 视频已生成={$created}, 未完成={$remaining}, msg={$fallbackMsg}, 设备={$deviceCode}"
        );

        if ($created > 0) {
            $defaultRemark = '图文仿写不足，用视频补位';
            $failureRemark = $this->resolveLastFailureRemark(
                $deviceCode,
                $taskDay,
                $personaId,
                AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
                $defaultRemark
            );
            $remarkSource = $failureRemark === $defaultRemark ? '默认文案' : '图文失败记录';
            $errorCreated = $this->createImageTextVideoFallbackErrorRecords(
                $viralTask,
                $task,
                $failureRemark,
                $created
            );
            $this->log(
                "图文视频补位写入status=7: 任务ID={$task->id}, 设备={$deviceCode}, 视频占位={$created}, 错误记录={$errorCreated}, remark来源={$remarkSource}, remark={$failureRemark}"
            );
        }

        if ($fallbackMsg === '存在改写未完成的图文仿写') {
            return [
                'triggered' => true,
                'created' => $totalCreated,
                'msg' => $deviceCode . '小红书图文视频兜底跳过：存在改写未完成的图文仿写'
                    . ($imagePublishCreated > 0 ? "，已先生成图文发布{$imagePublishCreated}条" : ''),
            ];
        }
        if ($emptySlots <= 0 && $created <= 0) {
            if ($imagePublishCreated > 0) {
                // 本轮已补齐图文发布且无空坑，结案避免后续空跑
                $this->markImageTextFallbackSettled($taskDay, $userId, $deviceCode, $personaId);

                return [
                    'triggered' => true,
                    'created' => $imagePublishCreated,
                    'msg' => $deviceCode . "小红书图文发布补齐完成，生成{$imagePublishCreated}条，无空时段需视频兜底",
                ];
            }

            // 无新建、无空坑：结案；triggered=false 降低扫描噪声
            $this->markImageTextFallbackSettled($taskDay, $userId, $deviceCode, $personaId);

            return [
                'triggered' => false,
                'created' => 0,
                'msg' => $deviceCode . '小红书图文缺口已无空闲发布时段，无需重复生成',
            ];
        }
        if ($availableVideos <= 0 && $created <= 0) {
            return [
                'triggered' => true,
                'created' => $totalCreated,
                'msg' => $deviceCode . '小红书图文视频兜底无同时段其他平台可复用视频'
                    . ($imagePublishCreated > 0 ? "，已先生成图文发布{$imagePublishCreated}条" : ''),
            ];
        }
        if ($created < $requested) {
            return [
                'triggered' => true,
                'created' => $totalCreated,
                'msg' => $deviceCode . "图文仿写不足用视频兜底部分完成，图文发布{$imagePublishCreated}条，视频{$created}条，未完成{$remaining}条",
            ];
        }

        // 视频兜底已按空坑补齐，结案
        if ($emptySlots > 0 && $created >= $requested) {
            $this->markImageTextFallbackSettled($taskDay, $userId, $deviceCode, $personaId);
        }

        return [
            'triggered' => true,
            'created' => $totalCreated,
            'msg' => $deviceCode . "图文仿写不足用视频兜底完成，图文发布{$imagePublishCreated}条，视频{$created}条",
        ];
    }

    private function buildImageTextSettledKey(string $day, int $userId, string $deviceCode, int $personaId): string
    {
        return 'viral_rewriter_image_text_fallback_settled:' . $day . ':' . $userId . ':' . $deviceCode . ':' . $personaId;
    }

    private function getImageTextSettledTtl(string $day): int
    {
        $end = strtotime($day . ' ' . self::IMAGE_TEXT_LAST_SCAN_TIME . ':00');
        if ($end === false) {
            return 3600;
        }

        return max(60, $end - time());
    }

    private function isImageTextFallbackSettled(string $day, int $userId, string $deviceCode, int $personaId): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            return (bool)$redis->exists($this->buildImageTextSettledKey($day, $userId, $deviceCode, $personaId));
        } catch (\Throwable $th) {
            $this->log('图文兜底结案标记读取失败: ' . $th->getMessage());

            return false;
        }
    }

    private function markImageTextFallbackSettled(string $day, int $userId, string $deviceCode, int $personaId): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $redis->setex(
                $this->buildImageTextSettledKey($day, $userId, $deviceCode, $personaId),
                $this->getImageTextSettledTtl($day),
                '1'
            );
        } catch (\Throwable $th) {
            $this->log('图文兜底结案标记写入失败: ' . $th->getMessage());
        }
    }

    private function getTaskDay(SvDeviceTask $task): string
    {
        if (!empty($task->day)) {
            return (string)$task->day;
        }
        if (!empty($task->start_time)) {
            return date('Y-m-d', (int)$task->start_time);
        }

        return date('Y-m-d');
    }

    private function getTaskConfig(SvDeviceTask $task)
    {
        return SvDeviceViral::alias('ps')
            ->field('ps.*,s.id as viral_account_id,s.account_type,s.account,s.nickname,s.avatar,s.device_code,IF(s.publish_platform > 0, s.publish_platform, s.account_type) as publish_platform,IF(s.publish_media_type > 0, s.publish_media_type, IFNULL(ps.publish_media_type, 1)) as publish_media_type')
            ->join('sv_device_viral_account s', 's.viral_id = ps.id')
            ->where('ps.id', (int)$task->sub_task_id)
            ->where('s.device_code', '=', (string)$task->device_code)
            ->where('s.account_type', (int)$task->account_type)
            ->limit(1)
            ->findOrEmpty();
    }

    /**
     * 与 ViralRewriterHandler 一致：统计占用配额（进行中+可用成功），不含失败与取消兴趣。
     */
    private function getSuccessCount(SvDeviceTask $task, SvDeviceViral $viralTask): int
    {
        $taskDay = $this->getTaskDay($task);
        $isImageTextTask = (int)($viralTask->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
        $query = SvDeviceViralRecord::where('device_code', (string)$task->device_code)
            ->where('day', $taskDay)
            ->where('persona_id', (int)$viralTask->persona_id)
            ->where('publish_media_type', $isImageTextTask ? AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT : AiPersona::PUBLISH_MEDIA_TYPE_VIDEO)
            ->where('status', 'in', [0, 3, 4, 6])
            ->where('is_interested', 1);

        if ($isImageTextTask) {
            $query->where('publish_platform', AiPersona::PUBLISH_PLATFORM_XHS);
        }

        return $query->count();
    }

    private function getPublishTimeCount(SvDeviceViral $viralTask): int
    {
        $persona = AiPersona::where('id', $viralTask->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            $this->log($viralTask->device_code . '爆款仿写兜底跳过：IP人设不存在，' . AiPersona::formatLabel(null, (int)$viralTask->persona_id));
            return 0;
        }

        $schedules = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
            ->where('scene', 5)
            ->field('id,platform')
            ->select();

        $userRemoveIds = AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
            ->where('template_id', $persona->workflow_template_id)
            ->where('user_id', $viralTask->user_id)
            ->where('scene', 5)
            ->where('status', 0)
            ->column('schedule_id');
        $disabledIds = array_map('intval', $userRemoveIds);
        $isImageTextTask = (int)($viralTask->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
        $count = 0;

        foreach ($schedules as $schedule) {
            if (in_array((int)$schedule->id, $disabledIds, true)) {
                continue;
            }
            if ($isImageTextTask && !$this->scheduleContainsPlatform($schedule->platform, AiPersona::PUBLISH_PLATFORM_XHS)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    private function scheduleContainsPlatform(mixed $platforms, int $platform): bool
    {
        if ($platform <= 0) {
            return false;
        }
        if (is_string($platforms)) {
            $decoded = json_decode($platforms, true);
            $platforms = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($platforms)) {
            return false;
        }
        foreach ($platforms as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (is_array($item) && (int)($item['account_type'] ?? 0) === $platform) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取同维度最新失败记录 remark；无失败记录时返回缺省文案。
     */
    private function resolveLastFailureRemark(
        string $deviceCode,
        string $taskDay,
        int $personaId,
        int $publishMediaType,
        string $defaultRemark = 'AI手机异常'
    ): string {
        $remark = SvDeviceViralRecord::where('device_code', $deviceCode)
            ->where('day', $taskDay)
            ->where('persona_id', $personaId)
            ->where('publish_media_type', $publishMediaType)
            ->where('status', 5)
            ->order('id', 'desc')
            ->value('remark');

        $remark = trim((string)$remark);
        return $remark !== '' ? $remark : $defaultRemark;
    }

    /**
     * 图文用视频占位成功后，按占位条数写入 status=7 错误标记（不写 status=6）。
     */
    private function createImageTextVideoFallbackErrorRecords(
        SvDeviceViral $viralTask,
        SvDeviceTask $task,
        string $failureRemark,
        int $count
    ): int {
        $count = max(0, $count);
        if ($count <= 0) {
            return 0;
        }

        $created = 0;
        $taskDay = !empty($task->day) ? (string)$task->day : date('Y-m-d', (int)$task->start_time);
        $dayKey = str_replace('-', '', $taskDay);
        $now = time();

        for ($index = 1; $index <= $count; $index++) {
            try {
                $keyword = $this->getFallbackKeyword($viralTask, $index);
                $errorContent = 'image_text_video_fallback_error://'
                    . $viralTask->id . '/' . $dayKey . '/' . $index . '/' . uniqid('', true);
                $errorHash = hash('sha256', $errorContent);

                SvDeviceViralRecord::create([
                    'user_id'            => $viralTask->user_id,
                    'viral_id'           => $viralTask->id,
                    'viral_account_id'   => $viralTask->viral_account_id,
                    'auto_type'          => $viralTask->auto_type,
                    'device_code'        => $task->device_code,
                    'account'            => $viralTask->account,
                    'nickname'           => $viralTask->nickname,
                    'persona_id'         => $viralTask->persona_id,
                    'keyword'            => $keyword,
                    'generation_types'   => $viralTask->generation_types,
                    'publish_platform'   => AiPersona::PUBLISH_PLATFORM_XHS,
                    'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
                    'video_duration'     => 0,
                    'content'            => $errorContent,
                    'hash'               => $errorHash,
                    'copywriting'        => [],
                    'copywriting_type'   => SvDeviceViralRecord::COPYWRITING_TYPE_FALLBACK_ERROR,
                    'status'             => SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
                    'remark'             => $failureRemark,
                    'day'                => $taskDay,
                    'use_time'           => $now,
                    'retry'              => 0,
                    'create_time'        => $now,
                    'update_time'        => $now,
                ]);
                $created++;
            } catch (\Throwable $th) {
                $this->log(
                    $task->device_code
                    . "图文视频补位status=7写入失败: 任务ID={$task->id}, index={$index}/{$count}, 错误="
                    . $th->getMessage()
                );
            }
        }

        return $created;
    }

    private function createFallbackCopywriting(
        SvDeviceViral $viralTask,
        SvDeviceTask $task,
        int $index,
        int $total,
        string $failureRemark
    ): bool {
        try {
            $now = time();
            $keyword = $this->getFallbackKeyword($viralTask, $index);
            $taskDay = !empty($task->day) ? (string)$task->day : date('Y-m-d', (int)$task->start_time);
            $dayKey = str_replace('-', '', $taskDay);
            $content = 'deadline_fallback://' . $viralTask->id . '/' . $dayKey . '/' . $index . '/' . uniqid('', true);
            $hash = hash('sha256', $content);
            $errorContent = 'deadline_fallback_error://' . $viralTask->id . '/' . $dayKey . '/' . $index . '/' . uniqid('', true);
            $errorHash = hash('sha256', $errorContent);
            $copywriting = $this->buildFallbackCopywriting($viralTask, $keyword, $taskDay, $index, $total);
            $copywritingJson = json_encode($copywriting, JSON_UNESCAPED_UNICODE);
            $publishMediaType = (int)($viralTask->publish_media_type ?? 1);
            $publishPlatform = (int)($viralTask->publish_platform ?? $viralTask->account_type ?? 4);

            $baseData = [
                'user_id'            => $viralTask->user_id,
                'viral_id'           => $viralTask->id,
                'viral_account_id'   => $viralTask->viral_account_id,
                'auto_type'          => $viralTask->auto_type,
                'device_code'        => $task->device_code,
                'account'            => $viralTask->account,
                'nickname'           => $viralTask->nickname,
                'persona_id'         => $viralTask->persona_id,
                'keyword'            => $keyword,
                'generation_types'   => $viralTask->generation_types,
                'publish_platform'   => $publishPlatform,
                'publish_media_type' => $publishMediaType,
                'video_duration'     => 0,
                'copywriting'        => $copywriting,
                'day'                => $taskDay,
                'use_time'           => 0,
                'retry'              => 0,
                'create_time'        => $now,
                'update_time'        => $now,
            ];

            Db::startTrans();
            $record = SvDeviceViralRecord::create(array_merge($baseData, [
                'content'          => $content,
                'hash'             => $hash,
                'copywriting_type' => SvDeviceViralRecord::COPYWRITING_TYPE_DEADLINE_FALLBACK,
                'status'           => SvDeviceViralRecord::STATUS_DEADLINE_FALLBACK,
                'remark'           => "3点兜底补齐降级文案({$index}/{$total})",
            ]));

            AiPersonaSynthesisCopywriting::create([
                'user_id'                   => $viralTask->user_id,
                'device_code'               => $task->device_code,
                'persona_id'                => $viralTask->persona_id,
                'sv_device_viral_record_id' => $record->id,
                'publish_media_type'        => $publishMediaType,
                'copywriting'               => $copywritingJson,
                'status'                    => AiPersonaSynthesisCopywriting::STATUS_FAILED,
                'use_state'                 => AiPersonaSynthesisCopywriting::USE_STATE_UNUSED,
                'day'                       => $taskDay,
                'create_time'               => $now,
                'update_time'               => $now,
            ]);

            // $errorRecord = SvDeviceViralRecord::create(array_merge($baseData, [
            //     'content'          => $errorContent,
            //     'hash'             => $errorHash,
            //     'copywriting_type' => SvDeviceViralRecord::COPYWRITING_TYPE_FALLBACK_ERROR,
            //     'status'           => SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
            //     'use_time'           => $now,
            //     'remark'           => $failureRemark,
            // ]));

            Db::commit();
            // $this->log(
            //     $task->device_code
            //     . "爆款仿写3点兜底降级文案创建成功: 任务ID={$task->id}, 记录ID={$record->id}, 错误记录ID={$errorRecord->id}"
            // );
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            $this->log($task->device_code . '爆款仿写3点兜底降级文案创建失败: ' . $th->getTraceAsString());
            return false;
        }
    }

    private function getFallbackKeyword(SvDeviceViral $viralTask, int $index): string
    {
        $keywords = $viralTask->keywords;
        if (!is_array($keywords)) {
            $keywords = json_decode((string)$keywords, true) ?: [];
        }

        if (empty($keywords)) {
            return '3点兜底文案' . $index;
        }

        return (string)$keywords[($index - 1) % count($keywords)];
    }

    private function buildFallbackCopywriting(SvDeviceViral $viralTask, string $keyword, string $taskDay, int $index, int $total): array
    {
        $persona = AiPersona::where('id', $viralTask->persona_id)->findOrEmpty();
        $personaName = $persona->isEmpty() ? '' : (string)($persona->persona_name ?? '');
        $business = $persona->isEmpty() ? '' : (string)($persona->main_business ?? '');
        $title = $keyword !== '' ? $keyword : '爆款仿写兜底文案';
        $parts = [];

        if ($personaName !== '') {
            $parts[] = "围绕{$personaName}";
        }
        if ($business !== '') {
            $parts[] = "结合{$business}";
        }
        $prefix = empty($parts) ? '围绕当前账号定位' : implode('，', $parts);

        return [
            'title' => $title,
            'rewritten_text' => "{$prefix}，以“{$title}”为主题，提炼用户关心的痛点、场景和行动理由，生成一条适合短视频发布的营销文案。突出真实需求、明确价值和下一步咨询引导。",
            'source' => 'deadline_fallback',
            'keyword' => $keyword,
            'day' => $taskDay,
            'fallback_index' => $index,
            'fallback_total' => $total,
        ];
    }

    private function log(string $content, string $level = 'viral_bottom'): void
    {
        Log::channel('auto')->{$level}($content);
    }

    /**
     * @param iterable<int, SvDeviceTask> $tasks
     * @return list<SvDeviceTask>
     */
    private function dedupeTasksForScan(iterable $tasks): array
    {
        $grouped = [];
        foreach ($tasks as $task) {
            if (!$task instanceof SvDeviceTask) {
                continue;
            }

            // 按 sub_task_id 区分视频/图文配置；同媒体多时间窗仍去重为一条
            $dedupeKey = $this->getTaskDay($task) . ':' . (int)$task->user_id . ':' . (string)$task->device_code
                . ':' . (int)$task->persona_id . ':' . (int)$task->sub_task_id;
            if (!isset($grouped[$dedupeKey])) {
                $grouped[$dedupeKey] = $task;
                continue;
            }

            if ($this->shouldPreferTaskForScan($task, $grouped[$dedupeKey])) {
                $grouped[$dedupeKey] = $task;
            }
        }

        return array_values($grouped);
    }

    private function shouldPreferTaskForScan(SvDeviceTask $candidate, SvDeviceTask $current): bool
    {
        $candidateRunning = (int)$candidate->status === DeviceEnum::TASK_STATUS_RUNNING;
        $currentRunning = (int)$current->status === DeviceEnum::TASK_STATUS_RUNNING;
        if ($candidateRunning && !$currentRunning) {
            return true;
        }
        if (!$candidateRunning && $currentRunning) {
            return false;
        }

        return (int)$candidate->id < (int)$current->id;
    }

    private function buildLockKey(string $day, int $userId, string $deviceCode, int $personaId): string
    {
        return 'viral_rewriter_deadline_fallback:' . $day . ':' . $userId . ':' . $deviceCode . ':' . $personaId;
    }

    private function acquireLock(string $lockKey, string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx($lockKey, $lockValue)) {
                return false;
            }
            $redis->expire($lockKey, self::LOCK_TTL);

            return true;
        } catch (\Throwable $th) {
            $this->log('爆款仿写兜底加锁失败: ' . $th->getMessage());

            return false;
        }
    }

    private function releaseLock(string $lockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get($lockKey) === $lockValue) {
                $redis->del($lockKey);
            }
        } catch (\Throwable $th) {
            $this->log('爆款仿写兜底解锁失败: ' . $th->getMessage());
        }
    }
}
