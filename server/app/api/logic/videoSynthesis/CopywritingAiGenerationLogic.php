<?php

namespace app\api\logic\videoSynthesis;

use app\api\logic\aiPersona\CopywritingLibraryLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\exception\MaterialNotReadyException;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryUseLog;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\hotspot\HotspotPool;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\CloudSvDevice;
use app\common\model\sv\SvDevice;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\PersonaService;
use app\common\service\hotspot\PoolService;
use app\common\service\hotspot\ResearchService;
use app\common\service\hotspot\ScriptService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI文案生成视频合成（设备维度）
 * 处理 copywriting_source=2，支持全部 visual_material_source、video_cover_source
 * 处理 copywriting_source=3 无文案纯素材混剪
 * 处理 copywriting_source=4 文案库
 * 处理 copywriting_source=5 热点生成
 */
class CopywritingAiGenerationLogic extends CopywritingImitationLogic
{
    const CONTENT_PUBLISH_SCENE = 5;

    /** @var array<string, mixed> */
    private static array $hotspotTestHooks = [];

    public static function setHotspotTestHooks(array $hooks): void
    {
        self::$hotspotTestHooks = $hooks;
    }

    public static function clearHotspotTestHooks(): void
    {
        self::$hotspotTestHooks = [];
    }

    /**
     * 供 tests/ 直接走取用+建单闭环，跳过设备入口校验。生产勿用。
     */
    public static function hotspotCreateTasksForTest(mixed $device, mixed $persona, mixed $config, array $videoTypes, array $publishTimeSlots = [])
    {
        return self::createHotspotDeviceVideoTasks($device, $persona, $config, [], $videoTypes, $publishTimeSlots);
    }

    /**
     * 供 tests/ 核对热点类型轮询（含新闻体 4，不含音色降级）。生产勿用。
     */
    public static function hotspotRotateTypesForTest(array $allVideoTypes, int $taskCountNeeded, ?int $lastType): array
    {
        return self::rotateGenerationTypes($allVideoTypes, $taskCountNeeded, $lastType);
    }

    /**
     * 供 tests/ 直接走仅标题现写。生产勿用。
     */
    public static function generateHotspotScriptForTest(HotspotPool $pool, array $persona, mixed $config, int $shanjianType, int $userId): array
    {
        return self::generateHotspotScript($pool, $persona, $config, $shanjianType, $userId);
    }

    /**
     * 供 tests/ 核对与追踪页同结构的 options。生产勿用。
     */
    public static function buildHotspotScriptOptionsForTest(HotspotPool $pool, array $analysis, mixed $config, int $shanjianType): array
    {
        return self::buildHotspotScriptOptions($pool, $analysis, $config, $shanjianType);
    }

    /**
     * 供 tests/ 核对「暂无」换成话题。生产勿用。
     */
    public static function fillAbsentSummaryForTest(string $summary, string $topic): string
    {
        return self::fillAbsentSummary($summary, $topic);
    }

    /**
     * 供 tests/ 核对占用行写入的摘要。生产勿用。
     */
    public static function writeHotspotUseSnapshotForTest(mixed $log, mixed $row, string $generatedTitle, string $script): void
    {
        self::writeHotspotUseSnapshot($log, $row, $generatedTitle, $script);
    }

    /**
     * 无文案模式设备视频合成主入口（copywriting_source=3）
     * 固定 shanjian_type=3，visual_material_source=3，video_cover_source 按合成配置
     */
    public static function processNoCopywritingSynthesis(string $deviceCode)
    {
        $device = null;
        try {
            // 真设备查表；无手机模式的 cloud:{persona_id} 按人设造虚拟设备
            $device = CloudSvDevice::resolve($deviceCode);
            if ($device->isEmpty()) {
                throw new \Exception('-不存在');
            }

            $cacheKey = 'video_synthesis_no_copywriting_' . $deviceCode;
            if (Cache::store('material_redis')->get($cacheKey)) {
                // throw new \Exception('视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('material_redis')->set($cacheKey, 1, 800);

            if ($device->persona_id <= 0) {
                throw new \Exception('-未绑定人设');
            }
            if ($device->synthesis_m == 1) {
                throw new \Exception('-视频合成任务已执行');
            }

            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('-绑定的人设' . AiPersona::formatLabel(null, (int)$device->persona_id) . '不存在或发布模式不符合要求');
            }

            $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                ->where('user_id', $device->user_id)
                ->where('copywriting_source', 3)
                ->findOrEmpty();
            if ($config->isEmpty()) {
                throw new \Exception('-绑定的人设未配置文案来源为无需文案的合成规则');
            }

            $execTimes = self::isCloudRunDevice($device)
                ? self::buildCloudPublishTimeSlots(self::resolveCloudDailyVideoCount($device))
                : self::getExecTime(self::getTimesByType($persona->persona_type), $device, $persona->persona_type);
            if (empty($execTimes)) {
                throw new \Exception('-绑定的人设' . AiPersona::formatLabel($persona) . '下没有可用的执行时间');
            }

            $allVideoTypes = $config->generation_types ?? [];
            if (empty($allVideoTypes)) {
                throw new \Exception('-绑定的人设ai合成规则，生成视频类型为空');
            }
            if (count($allVideoTypes) != 1 || (int)$allVideoTypes[0] !== 3) {
                throw new \Exception('-无文案模式仅支持素材混剪(类型3)，当前配置：' . json_encode($allVideoTypes, JSON_UNESCAPED_UNICODE));
            }

            $publishCount = count($execTimes);
            $generatedCount = self::getTodayDeviceSocialVideoCount($device);
            $taskCountNeeded = $publishCount - $generatedCount;
            if ($taskCountNeeded <= 0) {
                self::finishSocialSynthesisWhenQuotaReached($device, $publishCount, $generatedCount, '无文案');
                return true;
            }
            $resolvedType = self::resolveTaskTypeByVoice(3, (int)$device->persona_id, (int)$device->user_id);
            $selectedTypesForToday = array_fill(0, $taskCountNeeded, $resolvedType);

            $groupedData = self::getMaterialsForImitation(
                $device,
                ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode((int)$device->persona_id)
            );
            self::assertGroupedMaterialsAvailable($groupedData, (int)$device->persona_id, $selectedTypesForToday, 3);

            return self::createNoCopywritingDeviceVideoTasks($device, $persona, $config, $groupedData, $taskCountNeeded, $resolvedType);
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:不锁死设备(不标 synthesis_m=1),异常透传给 cron 清缓存等下一轮
            Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . ' 素材转码未就绪,本轮跳过待下一轮：' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            // 失败不锁设备，留给下轮继续补齐到发布数量
            $msg = '设备号' . $deviceCode . '无文案视频合成任务失败：' . $errorMsg;
            Log::channel('ipVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    /**
     * 批量创建无文案素材混剪任务
     */
    private static function createNoCopywritingDeviceVideoTasks(mixed $device, mixed $persona, mixed $config, array $groupedData, int $taskCount, int $taskType = 3)
    {
        $deviceCode = $device->device_code;
        $createdTasks = [];
        $titlePrefix = '纯素材混剪-' . date('YmdHis');

        for ($key = 0; $key < $taskCount; $key++) {
            try {
                $taskTitle = $taskCount > 1 ? ($titlePrefix . '-' . ($key + 1)) : $titlePrefix;
                $result = self::createImitationVideoTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    '',
                    $taskTitle,
                    $taskType,
                    3
                );
                if ($result) {
                    $createdTasks[] = $result;
                }
            } catch (MaterialNotReadyException $e) {
                // 素材转码未就绪:不落库失败任务,异常透传给 cron 等下一轮
                throw $e;
            } catch (\Throwable $e) {
                // 单条失败：落失败任务后继续下一条（失败也占合成名额）
                $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '个无文案素材混剪任务创建失败: ' . $e->getMessage();
                Log::channel('ipVideoSynthesis')->write($msg);
                $failed = self::createFailedSynthesisTask($device, $persona, $taskType, $e->getMessage(), [
                    'config' => $config,
                    'copywriting_source' => 3,
                    'title' => $taskTitle ?? ('纯素材混剪-' . date('YmdHis')),
                ]);
                if ($failed) {
                    $createdTasks[] = $failed;
                }
                continue;
            }
        }

        if (empty($createdTasks)) {
            throw new \Exception('未成功创建任何无文案视频合成任务');
        }

        // 任务数（含失败）达到发布数才标记完成
        self::markSocialSynthesisFinishedIfQuotaReached($device, '无文案');

        return [
            'device' => $device->toArray(),
            'persona' => $persona->toArray(),
            'tasks' => $createdTasks,
            'count' => count($createdTasks),
        ];
    }

    public static function getTimesByType(int $personaType): array
    {
        $maps = [
            1 => [
                1 => [
                    '08:00-08:30' => '08:02,0',
                ],
            ],
            2 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                ],
            ],
            3 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                    '16:30-17:00' => '16:31,1',
                ],
            ],
        ];
        return $maps[$personaType] ?? [];
    }

    private static function getContentPublishTimeSlots(AiPersona $persona, SvDevice $device): array
    {
        // 无手机模式不发布，也就没有"发布时段"可言：条数改由「视频剪辑」员工的每日出片条数决定。
        // 这里仍然返回等量的时段字符串，是为了让下游的配额差集(resolveRemainingPublishTimeSlots)、
        // 类型轮转、extra.exec_time 记账原样复用，手机模式一行都不用改。
        if (self::isCloudRunDevice($device)) {
            return self::buildCloudPublishTimeSlots(self::resolveCloudDailyVideoCount($device));
        }

        if ((int)$persona->workflow_template_id > 0) {
            $hasConfig = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
                ->where('scene', self::CONTENT_PUBLISH_SCENE)
                ->count() > 0;

            if ($hasConfig) {
                return self::formatContentScheduleSlots(self::getAutoSchedule($persona, self::CONTENT_PUBLISH_SCENE));
            }
        }

        return self::getDefaultContentPublishTimeSlots((int)$persona->persona_type, $device);
    }

    /**
     * 今日已创建的社媒视频任务数（不含朋友圈）
     * 含成功/处理中/失败/MiniMax占位(status=-1)：失败与等待TTS也占用合成名额
     */
    private static function getTodayDeviceSocialVideoCount(SvDevice $device, ?string $day = null): int
    {
        $day = $day ?: date('Y-m-d');
        $start = strtotime($day . ' 00:00:00');
        $end = strtotime($day . ' 23:59:59');
        if ($start === false || $end === false) {
            return 0;
        }

        $videoTaskCount = (int)ShanjianVideoTask::where('user_id', (int)$device->user_id)
            ->where('persona_id', (int)$device->persona_id)
            ->where('device_code', (string)$device->device_code)
            ->where('auto_type', 1)
            ->where('wechat_type', 0)
            ->whereBetween('create_time', [$start, $end])
            ->count();

        return $videoTaskCount + count(self::getPendingMinimaxRequestJsons($device, $start, $end));
    }

    /**
     * 今日社媒视频已占用的发布时间段（含失败任务，失败也占名额）
     */
    private static function getTodayUsedContentPublishSlots(SvDevice $device, ?string $day = null): array
    {
        $day = $day ?: date('Y-m-d');
        $start = strtotime($day . ' 00:00:00');
        $end = strtotime($day . ' 23:59:59');
        if ($start === false || $end === false) {
            return [];
        }

        $extras = ShanjianVideoTask::where('user_id', (int)$device->user_id)
            ->where('persona_id', (int)$device->persona_id)
            ->where('device_code', (string)$device->device_code)
            ->where('auto_type', 1)
            ->where('wechat_type', 0)
            ->whereBetween('create_time', [$start, $end])
            ->column('extra');

        // MiniMax pending 阶段尚未创建 ShanjianVideoTask，发布时间段保存在
        // request_json.auto_pending_task.extra 中，也必须占用，避免重复分配同一时段。
        foreach (self::getPendingMinimaxRequestJsons($device, $start, $end) as $requestJson) {
            $requestData = is_array($requestJson)
                ? $requestJson
                : json_decode((string)$requestJson, true);
            if (!is_array($requestData)) {
                continue;
            }
            $pendingExtra = $requestData['auto_pending_task']['extra'] ?? null;
            if (is_array($pendingExtra)) {
                $extras[] = $pendingExtra;
            } elseif (is_string($pendingExtra) && $pendingExtra !== '') {
                $extras[] = $pendingExtra;
            }
        }

        $usedSlots = [];
        foreach ($extras as $extra) {
            if (empty($extra)) {
                continue;
            }
            $extraData = is_array($extra) ? $extra : json_decode((string)$extra, true);
            if (!is_array($extraData) || empty($extraData['exec_time'])) {
                continue;
            }
            $execTime = $extraData['exec_time'];
            if (is_string($execTime)) {
                $decoded = json_decode($execTime, true);
                if (is_array($decoded)) {
                    $execTime = $decoded;
                } else {
                    $execTime = [$execTime];
                }
            }
            if (!is_array($execTime)) {
                continue;
            }
            foreach ($execTime as $slot) {
                $slot = trim((string)$slot);
                if ($slot !== '') {
                    $usedSlots[] = $slot;
                }
            }
        }

        return array_values(array_unique($usedSlots));
    }

    /**
     * 获取当天尚未落 ShanjianVideoTask 的自动化 MiniMax 中间任务（兼容旧数据）。
     * 新流程建单即落 status=-1 占位任务，不再依赖此查询；保留给迁移期旧 pending。
     */
    private static function getPendingMinimaxRequestJsons(SvDevice $device, int $start, int $end): array
    {
        return MinimaxShanjianTask::alias('m')
            ->join('shanjian_video_setting s', 's.id = m.shanjian_setting_id')
            ->leftJoin(
                'shanjian_video_task v',
                'v.video_setting_id = s.id AND v.delete_time IS NULL'
            )
            ->where('m.user_id', (int)$device->user_id)
            ->where('s.user_id', (int)$device->user_id)
            ->where('s.device_code', (string)$device->device_code)
            ->where('s.auto_type', 1)
            ->whereNull('m.delete_time')
            ->whereNull('s.delete_time')
            ->whereNull('v.id')
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(s.request_json, '$.auto_pending_task.persona_id')) = ?",
                [(int)$device->persona_id]
            )
            ->whereBetween('m.create_time', [$start, $end])
            ->column('s.request_json');
    }

    /**
     * 扣除今日已合成占用的发布时间段，保证累计合成数不超过发布时段数
     */
    private static function resolveRemainingPublishTimeSlots(SvDevice $device, array $publishTimeSlots): array
    {
        $publishTimeSlots = array_values($publishTimeSlots);
        $publishCount = count($publishTimeSlots);
        if ($publishCount <= 0) {
            return [];
        }

        $generatedCount = self::getTodayDeviceSocialVideoCount($device);
        $remainingByCount = $publishCount - $generatedCount;
        if ($remainingByCount <= 0) {
            return [];
        }

        $usedSlots = self::getTodayUsedContentPublishSlots($device);
        $remainingBySlot = [];
        foreach ($publishTimeSlots as $slot) {
            if (!in_array($slot, $usedSlots, true)) {
                $remainingBySlot[] = $slot;
            }
        }

        if (!empty($remainingBySlot)) {
            return array_slice(array_values($remainingBySlot), 0, $remainingByCount);
        }

        // 历史任务未写 exec_time 时，按数量从后段顺延扣减
        return array_slice($publishTimeSlots, $generatedCount, $remainingByCount);
    }

    private static function finishSocialSynthesisWhenQuotaReached(SvDevice $device, int $publishCount, int $generatedCount, string $scene): void
    {
        if ($publishCount <= 0 || $generatedCount < $publishCount) {
            return;
        }
        $device->markSynthesisDone(SvDevice::SYNTHESIS_SCENE_SOCIAL);
        $device->save();
        Log::channel('ipVideoSynthesis')->write(sprintf(
            '设备号%s%s合成已达发布数量，标记完成：publish=%d generated=%d',
            (string)$device->device_code,
            $scene,
            $publishCount,
            $generatedCount
        ));
    }

    /**
     * 仅当今日社媒任务数（含失败）>= 发布时段数时标记 synthesis_m=1
     */
    private static function markSocialSynthesisFinishedIfQuotaReached(SvDevice $device, string $scene = ''): bool
    {
        $persona = AiPersona::where('id', (int)$device->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            return false;
        }

        $publishTimeSlots = self::getContentPublishTimeSlots($persona, $device);
        $publishCount = count($publishTimeSlots);
        if ($publishCount <= 0) {
            $publishCount = MarketingTemplateSchedule::getTodayPublishTaskCount((int)$device->persona_id);
        }
        if ($publishCount <= 0) {
            return false;
        }

        $generatedCount = self::getTodayDeviceSocialVideoCount($device);
        if ($generatedCount < $publishCount) {
            Log::channel('ipVideoSynthesis')->write(sprintf(
                '设备号%s%s未达发布数量，不标记完成：publish=%d task_count=%d',
                (string)$device->device_code,
                $scene,
                $publishCount,
                $generatedCount
            ));
            return false;
        }

        self::finishSocialSynthesisWhenQuotaReached($device, $publishCount, $generatedCount, $scene);
        return true;
    }

    private static function formatContentScheduleSlots(\think\Collection $schedules): array
    {
        $slots = [];
        foreach ($schedules as $schedule) {
            $startTime = trim((string)$schedule->start_time);
            $endTime = trim((string)$schedule->end_time);
            if ($startTime === '' || $endTime === '') {
                continue;
            }
            $slots[] = $startTime . '-' . $endTime;
        }

        return array_values(array_unique($slots));
    }

    /** 是否无手机模式的虚拟设备（真设备恒 false，手机模式行为完全不受影响） */
    private static function isCloudRunDevice(SvDevice $device): bool
    {
        return $device instanceof CloudSvDevice;
    }

    /**
     * 无手机模式的每日出片条数：取「视频剪辑」员工配置的 daily_video_count，
     * 未配置(0)时兜底 1 条——不能回落到发布时段，否则又绕回了要配发布时段才出片。
     */
    private static function resolveCloudDailyVideoCount(SvDevice $device): int
    {
        $count = AiPersonaSynthesisConfig::normalizeDailyVideoCount(
            AiPersonaSynthesisConfig::where('persona_id', (int)$device->persona_id)
                ->where('user_id', (int)$device->user_id)
                ->value('daily_video_count')
        );
        return $count > 0 ? $count : AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT;
    }

    /**
     * 按条数造等量且互不相同的时段串。
     * 必须互不相同：下游 resolveRemainingPublishTimeSlots 用 exec_time 做差集去重，
     * 串重复会被并成一条，当天配额算错、多出片。08:00-20:00 均分，20 条时仍相隔 36 分钟。
     */
    private static function buildCloudPublishTimeSlots(int $count): array
    {
        $count = max(1, $count);
        $startMinute = 8 * 60;
        $step = intdiv(12 * 60, $count);
        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            $begin = $startMinute + $i * $step;
            $end = $begin + max(1, min($step, 30));
            $slots[] = sprintf(
                '%02d:%02d-%02d:%02d',
                intdiv($begin, 60),
                $begin % 60,
                intdiv($end, 60) % 24,
                $end % 60
            );
        }
        return $slots;
    }

    private static function getDefaultContentPublishTimeSlots(int $personaType, SvDevice $device): array
    {
        $slots = [];
        foreach (self::getTimesByType($personaType) as $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                list($st, $et) = explode('-', $slot);
                if (!self::checkScheduleIsCreate([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'persona_type' => $personaType,
                    'start_time' => $st,
                    'end_time' => $et,
                    'persona_id'=>$device->persona_id,
                    'scene' => self::CONTENT_PUBLISH_SCENE
                ])) {
                    continue;
                }
                $slots[] = $slot;
            }
        }

        return array_values(array_unique($slots));
    }

    private static function getExecTime(array $times, SvDevice $device, int $personaType): array
    {
        $res = [];
        foreach ($times as $key => $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                list($st, $et) = explode('-', $slot);
                if (!self::checkScheduleIsCreate([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'persona_type' => $personaType,
                    'start_time' => $st,
                    'end_time' => $et,
                    'persona_id' => $device->persona_id,
                    'scene' => self::CONTENT_PUBLISH_SCENE
                ])) {
                    continue;
                }
                list($time, $flag) = explode(',', $value);
                $res[$flag][$slot][$key] = $time;
            }
        }
        return $res;
    }

    /**
     * 设备视频合成主入口（copywriting_source=2）
     */
    public static function videoSynthesis(string $deviceCode)
    {
        return self::videoSynthesisByTypes($deviceCode);
    }

    public static function newsMixcutSynthesis(string $deviceCode)
    {
        return self::videoSynthesisByTypes($deviceCode, [4]);
    }

    /**
     * 文案库模式设备视频合成主入口（copywriting_source=4）
     */
    public static function librarySynthesis(string $deviceCode)
    {
        $device = null;
        try {
            // 真设备查表；无手机模式的 cloud:{persona_id} 按人设造虚拟设备
            $device = CloudSvDevice::resolve($deviceCode);
            if ($device->isEmpty()) {
                throw new \Exception('-不存在');
            }

            $cacheKey = 'video_synthesis_library_' . $deviceCode;
            if (Cache::store('material_redis')->get($cacheKey)) {
                // throw new \Exception('视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('material_redis')->set($cacheKey, 1, 800);

            if ($device->persona_id <= 0) {
                throw new \Exception('-未绑定人设');
            }
            if ($device->synthesis_m == 1) {
                throw new \Exception('-视频合成任务已执行');
            }

            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('-绑定的人设' . $device->persona_id . '不存在或发布模式不符合要求');
            }

            $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                ->where('user_id', $device->user_id)
                ->where('copywriting_source', AiPersonaSynthesisConfig::COPYWRITING_SOURCE_LIBRARY)
                ->findOrEmpty();
            if ($config->isEmpty()) {
                Log::channel('ipVideoSynthesis')->write('设备未配置文案库合成规则，跳过：' . $deviceCode);
                return true;
            }

            $publishTimeSlots = self::getContentPublishTimeSlots($persona, $device);
            if (empty($publishTimeSlots)) {
                throw new \Exception('-绑定的人设' . $device->persona_id . '下没有可用的内容发布时间段');
            }
            $publishCount = count($publishTimeSlots);
            $generatedCount = self::getTodayDeviceSocialVideoCount($device);
            $publishTimeSlots = self::resolveRemainingPublishTimeSlots($device, $publishTimeSlots);
            if (empty($publishTimeSlots)) {
                self::finishSocialSynthesisWhenQuotaReached($device, $publishCount, $generatedCount, '文案库');
                return true;
            }

            $visualMaterialSource = (int)$config->visual_material_source;
            $allVideoTypes = $config->generation_types ?? [];
            if (is_array($allVideoTypes)) {
                $allVideoTypes = array_values(array_map('intval', $allVideoTypes));
            }
            if (empty($allVideoTypes)) {
                throw new \Exception('-绑定的人设ai合成规则，生成视频类型为空');
            }

            $taskCountNeeded = count($publishTimeSlots);
            $startIndex = 0;
            $lastTask = Db::name('shanjian_video_task')
                ->where('device_code', $deviceCode)
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('persona_id', $device->persona_id)
                ->order('id', 'desc')
                ->field('shanjian_type')
                ->find();

            $lastType = $lastTask ? (int)$lastTask['shanjian_type'] : null;
            if ($lastType !== null) {
                $foundKey = array_search($lastType, $allVideoTypes);
                $startIndex = ($foundKey !== false) ? (($foundKey + 1) % count($allVideoTypes)) : 0;
            }

            $selectedTypesForToday = [];
            $totalTypes = count($allVideoTypes);
            for ($i = 0; $i < $taskCountNeeded; $i++) {
                $index = ($startIndex + $i) % $totalTypes;
                $selectedTypesForToday[] = self::resolveTaskTypeByVoice(
                    (int)$allVideoTypes[$index],
                    (int)$device->persona_id,
                    (int)$device->user_id
                );
            }

            $groupedData = self::getMaterialsForImitation(
                $device,
                ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode((int)$device->persona_id)
            );
            self::assertGroupedMaterialsAvailable($groupedData, (int)$device->persona_id, $selectedTypesForToday, $visualMaterialSource);

            return self::createLibraryDeviceVideoTasks($device, $persona, $config, $groupedData, $selectedTypesForToday, $publishTimeSlots);
        } catch (MaterialNotReadyException $e) {
            Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . ' 素材转码未就绪,本轮跳过待下一轮：' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            // 失败不锁设备，留给下轮继续补齐到发布数量
            $msg = '设备号' . $deviceCode . '文案库视频合成任务失败：' . $errorMsg;
            Log::channel('ipVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    /**
     * 热点生成模式设备视频合成主入口（copywriting_source=5）
     * 空池跳过、不回退 AI；每次现写标题和口播并写入占用记录。
     */
    public static function hotspotSynthesis(string $deviceCode)
    {
        $device = null;
        try {
            $device = CloudSvDevice::resolve($deviceCode);
            if ($device->isEmpty()) {
                throw new \Exception('-不存在');
            }

            $cacheKey = 'video_synthesis_hotspot_' . $deviceCode;
            Cache::store('material_redis')->set($cacheKey, 1, 800);

            if ($device->persona_id <= 0) {
                throw new \Exception('-未绑定人设');
            }
            if ($device->synthesis_m == 1) {
                throw new \Exception('-视频合成任务已执行');
            }

            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('-绑定的人设' . AiPersona::formatLabel(null, (int)$device->persona_id) . '不存在或发布模式不符合要求');
            }

            $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                ->where('user_id', $device->user_id)
                ->where('copywriting_source', AiPersonaSynthesisConfig::COPYWRITING_SOURCE_HOTSPOT)
                ->findOrEmpty();
            if ($config->isEmpty()) {
                Log::channel('ipVideoSynthesis')->write('设备未配置热点生成合成规则，跳过：' . $deviceCode);
                return true;
            }

            $publishTimeSlots = self::getContentPublishTimeSlots($persona, $device);
            if (empty($publishTimeSlots)) {
                throw new \Exception('-绑定的人设' . $device->persona_id . '下没有可用的内容发布时间段');
            }
            $publishCount = count($publishTimeSlots);
            $generatedCount = self::getTodayDeviceSocialVideoCount($device);
            $publishTimeSlots = self::resolveRemainingPublishTimeSlots($device, $publishTimeSlots);
            if (empty($publishTimeSlots)) {
                self::finishSocialSynthesisWhenQuotaReached($device, $publishCount, $generatedCount, '热点生成');
                return true;
            }

            $visualMaterialSource = (int)$config->visual_material_source;
            $allVideoTypes = $config->generation_types ?? [];
            if (is_array($allVideoTypes)) {
                $allVideoTypes = array_values(array_map('intval', $allVideoTypes));
            }
            if (empty($allVideoTypes)) {
                throw new \Exception('-绑定的人设ai合成规则，生成视频类型为空');
            }

            $taskCountNeeded = count($publishTimeSlots);
            $lastTask = Db::name('shanjian_video_task')
                ->where('device_code', $deviceCode)
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('persona_id', $device->persona_id)
                ->order('id', 'desc')
                ->field('shanjian_type')
                ->find();

            $lastType = $lastTask ? (int)$lastTask['shanjian_type'] : null;
            $rotatedTypes = self::rotateGenerationTypes($allVideoTypes, $taskCountNeeded, $lastType);
            $selectedTypesForToday = [];
            foreach ($rotatedTypes as $type) {
                $selectedTypesForToday[] = self::resolveTaskTypeByVoice(
                    (int)$type,
                    (int)$device->persona_id,
                    (int)$device->user_id
                );
            }

            $groupedData = self::$hotspotTestHooks['groupedData'] ?? self::getMaterialsForImitation(
                $device,
                ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode((int)$device->persona_id)
            );
            $materialTypes = array_values(array_map('intval', $selectedTypesForToday));
            if ($materialTypes !== [] && empty(self::$hotspotTestHooks['groupedData'])) {
                self::assertGroupedMaterialsAvailable($groupedData, (int)$device->persona_id, $materialTypes, $visualMaterialSource);
            }

            return self::createHotspotDeviceVideoTasks($device, $persona, $config, $groupedData, $selectedTypesForToday, $publishTimeSlots);
        } catch (MaterialNotReadyException $e) {
            Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . ' 素材转码未就绪,本轮跳过待下一轮：' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $msg = '设备号' . $deviceCode . '热点生成视频合成任务失败：' . $errorMsg;
            Log::channel('ipVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    private static function videoSynthesisByTypes(string $deviceCode, ?array $forceVideoTypes = null)
    {
        $device = null;
        try {
            // 真设备查表；无手机模式的 cloud:{persona_id} 按人设造虚拟设备
            $device = CloudSvDevice::resolve($deviceCode);
            if ($device->isEmpty()) {
                throw new \Exception('-不存在');
            }

            $cacheKey = 'video_synthesis_ai_gen_' . $deviceCode;
            if (Cache::store('material_redis')->get($cacheKey)) {
                // throw new \Exception('视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('material_redis')->set($cacheKey, 1, 800);

            if ($device->persona_id <= 0) {
                throw new \Exception('-未绑定人设');
            }
            if ($device->synthesis_m == 1) {
                throw new \Exception('-视频合成任务已执行');
            }

            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('-绑定的人设' . AiPersona::formatLabel(null, (int)$device->persona_id) . '不存在或发布模式不符合要求');
            }

            $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                ->where('user_id', $device->user_id)
                ->findOrEmpty();
            if (!$config->isEmpty() && $forceVideoTypes === null && (int)$config->copywriting_source !== AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI) {
                Log::channel('ipVideoSynthesis')->write('设备未配置AI生成合成规则，跳过：' . $deviceCode);
                return true;
            }
            if ($config->isEmpty()) {
                Log::channel('ipVideoSynthesis')->write('设备未配置AI生成合成规则，跳过：' . $deviceCode);
                return true;
            }

            $publishTimeSlots = self::getContentPublishTimeSlots($persona, $device);
            if (empty($publishTimeSlots)) {
                throw new \Exception('-绑定的人设' . AiPersona::formatLabel($persona) . '下没有可用的内容发布时间段');
            }
            $publishCount = count($publishTimeSlots);
            $generatedCount = self::getTodayDeviceSocialVideoCount($device);
            $publishTimeSlots = self::resolveRemainingPublishTimeSlots($device, $publishTimeSlots);
            if (empty($publishTimeSlots)) {
                self::finishSocialSynthesisWhenQuotaReached($device, $publishCount, $generatedCount, 'AI生成');
                return true;
            }

            $allVideoTypes = $config->generation_types ?? [];
            if (is_array($allVideoTypes)) {
                $allVideoTypes = array_values(array_map('intval', $allVideoTypes));
            }
            if (empty($allVideoTypes)) {
                throw new \Exception('-绑定的人设ai合成规则，生成视频类型为空');
            }

            $taskCountNeeded = count($publishTimeSlots);
            $startIndex = 0;
            $lastTask = Db::name('shanjian_video_task')
                ->where('device_code', $deviceCode)
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('persona_id', $device->persona_id)
                ->order('id', 'desc')
                ->field('shanjian_type')
                ->find();

            $lastType = $lastTask ? (int)$lastTask['shanjian_type'] : null;
            if ($lastType !== null) {
                $foundKey = array_search($lastType, $allVideoTypes);
                $startIndex = ($foundKey !== false) ? (($foundKey + 1) % count($allVideoTypes)) : 0;
            }

            $selectedTypesForToday = [];
            $totalTypes = count($allVideoTypes);
            for ($i = 0; $i < $taskCountNeeded; $i++) {
                $index = ($startIndex + $i) % $totalTypes;
                $selectedTypesForToday[] = self::resolveTaskTypeByVoice(
                    (int)$allVideoTypes[$index],
                    (int)$device->persona_id,
                    (int)$device->user_id
                );
            }
            if ($forceVideoTypes !== null) {
                $allowedTypes = array_map('intval', $forceVideoTypes);
                $filteredTypes = [];
                $filteredSlots = [];
                foreach ($selectedTypesForToday as $index => $type) {
                    if (in_array((int)$type, $allowedTypes, true)) {
                        $filteredTypes[] = (int)$type;
                        $filteredSlots[] = $publishTimeSlots[$index] ?? '';
                    }
                }
                if (empty($filteredTypes)) {
                    Log::channel('ipVideoSynthesis')->write('设备新闻体固定AI生成无匹配时段，跳过：' . $deviceCode);
                    return true;
                }
                $selectedTypesForToday = $filteredTypes;
                $publishTimeSlots = $filteredSlots;
            }

            // 仅预加载素材，不在批次入口拦截。缺素材时由单条任务捕获异常并落失败任务，
            // 数字人口播的 AI/AI+素材库模式仍可继续尝试 AI 找素材。
            $groupedData = self::getMaterialsForImitation(
                $device,
                ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode((int)$device->persona_id)
            );

            return self::createDeviceVideoTasks($device, $persona, $config, $groupedData, $selectedTypesForToday, $publishTimeSlots);
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:不锁死设备(不标 synthesis_m=1),异常透传给 cron 清缓存等下一轮
            Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . ' 素材转码未就绪,本轮跳过待下一轮：' . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            // 失败不锁设备，留给下轮继续补齐到发布数量
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $errorMsg;
            Log::channel('ipVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    /**
     * 按 generation_types 从上次类型之后轮询，保留新闻体 4。
     *
     * @param array<int, int> $allVideoTypes
     * @return array<int, int>
     */
    private static function rotateGenerationTypes(array $allVideoTypes, int $taskCountNeeded, ?int $lastType): array
    {
        $allVideoTypes = array_values(array_map('intval', $allVideoTypes));
        if ($allVideoTypes === [] || $taskCountNeeded <= 0) {
            return [];
        }
        $totalTypes = count($allVideoTypes);
        $startIndex = 0;
        if ($lastType !== null) {
            $foundKey = array_search($lastType, $allVideoTypes, true);
            $startIndex = ($foundKey !== false) ? (($foundKey + 1) % $totalTypes) : 0;
        }
        $selected = [];
        for ($i = 0; $i < $taskCountNeeded; $i++) {
            $selected[] = (int)$allVideoTypes[($startIndex + $i) % $totalTypes];
        }
        return $selected;
    }

    /**
     * 批量创建设备视频合成任务
     * 素材选择复用父类 createImitationVideoTask：
     * - shanjian_type=1 且 visual_material_source=1/2 → selectMaterialsForTaskTypeAndVisualMaterialSource（AI抓素材）
     * - shanjian_type=3/4 及其它 → selectMaterialsForTaskType（仅素材库）
     */
    private static function createDeviceVideoTasks(SvDevice $device, AiPersona $persona, AiPersonaSynthesisConfig $config, array $groupedData, array $videoTypes, array $publishTimeSlots = [])
    {
        $createdTasks = [];
        $publishTimeSlots = array_values($publishTimeSlots);

        try {
            $coze = self::buildPersonaCopywritingParams($device, $persona);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        foreach ($videoTypes as $key => $shanjianType) {
            $result = self::createAiGeneratedSlotTask(
                $device,
                $persona,
                $config,
                $groupedData,
                $coze,
                (int)$shanjianType,
                $publishTimeSlots[$key] ?? '',
                $key
            );
            if ($result) {
                $createdTasks[] = $result;
            }
        }

        if (empty($createdTasks)) {
            throw new \Exception('未成功创建任何视频合成任务');
        }

        // 任务数（含失败）未达发布数绝不标完成
        self::markSocialSynthesisFinishedIfQuotaReached($device, 'AI生成');

        return [
            'device' => $device->toArray(),
            'persona' => $persona->toArray(),
            'tasks' => $createdTasks,
            'count' => count($createdTasks),
        ];
    }

    /**
     * 单条 AI 文案建单（含新闻体 type=4）。AI 生成批次与热点遇新闻体共用。
     */
    private static function createAiGeneratedSlotTask(
        mixed $device,
        mixed $persona,
        mixed $config,
        array $groupedData,
        array $coze,
        int $shanjianType,
        string $execTime,
        int $slotIndex
    ): ?array {
        $deviceCode = (string)$device->device_code;
        $userId = (int)$device->user_id;
        $taskTitle = '';
        $taskMsg = '';
        try {
            $skipMaterialAssert = array_key_exists('createVideo', self::$hotspotTestHooks)
                || array_key_exists('aiCopywriting', self::$hotspotTestHooks)
                || !empty(self::$hotspotTestHooks['groupedData']);
            $canUseAiMaterials = ShanjianVideoSettingLogic::canUseAiMaterials(
                $shanjianType,
                ShanjianVideoSettingLogic::resolveVisualMaterialSource($config)
            );
            if (!$canUseAiMaterials && !$skipMaterialAssert) {
                self::assertGroupedMaterialsAvailable(
                    $groupedData,
                    (int)$device->persona_id,
                    [$shanjianType],
                    AiPersonaSynthesisConfig::VISUAL_MATERIAL_SOURCE_MATERIAL
                );
            }

            if (array_key_exists('aiCopywriting', self::$hotspotTestHooks)) {
                $hook = self::$hotspotTestHooks['aiCopywriting'];
                $aiCopywriting = is_callable($hook)
                    ? $hook($coze, $config, $userId, $shanjianType)
                    : $hook;
            } else {
                $aiCopywriting = self::generateAiCopywritingForTask($coze, $config, $userId, $shanjianType);
            }
            $taskTitle = (string)($aiCopywriting['title'] ?? '');
            $taskMsg = (string)($aiCopywriting['msg'] ?? '');
            $materialKeywords = (string)($aiCopywriting['material_keywords'] ?? ($taskMsg ?: $taskTitle));

            if (array_key_exists('createVideo', self::$hotspotTestHooks)
                || !empty(self::$hotspotTestHooks['failVideoCreate'])
            ) {
                $result = self::dispatchHotspotVideoTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    $taskMsg,
                    $taskTitle,
                    $shanjianType,
                    AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI,
                    $execTime
                );
            } else {
                $result = self::createImitationVideoTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    $taskMsg,
                    $taskTitle,
                    $shanjianType,
                    AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI,
                    0,
                    $materialKeywords,
                    $execTime
                );
            }
            return $result ?: null;
        } catch (MaterialNotReadyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $msg = '设备号: ' . $deviceCode . '第' . ($slotIndex + 1) . '类型视频' . $shanjianType
                . '，任务创建失败: ' . $e->getMessage();
            Log::channel('ipVideoSynthesis')->write($msg);
            if (!empty(self::$hotspotTestHooks['skipFailedTask'])) {
                return null;
            }
            return self::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                'config' => $config,
                'copywriting_source' => AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI,
                'exec_time' => $execTime,
                'title' => $taskTitle !== '' ? $taskTitle : 'AI自动生成视频',
                'msg' => $taskMsg,
            ]) ?: null;
        }
    }

    private static function createHotspotDeviceVideoTasks(mixed $device, mixed $persona, mixed $config, array $groupedData, array $videoTypes, array $publishTimeSlots = [])
    {
        $deviceCode = (string)$device->device_code;
        $userId = (int)$device->user_id;
        $personaId = (int)$device->persona_id;
        $createdTasks = [];
        $publishTimeSlots = array_values($publishTimeSlots);
        $skipHotspotGenerate = false;
        $resolvedPersona = [];
        $excludeIds = [];
        $consumedAny = false;
        $roundTouched = false;
        $pickMode = AiPersonaSynthesisConfig::requireValidHotspotPickMode($config->hotspot_pick_mode ?? 1);
        $coze = null;

        foreach ($videoTypes as $key => $shanjianType) {
            $shanjianType = (int)$shanjianType;
            $execTime = $publishTimeSlots[$key] ?? '';
            if ($shanjianType === 4) {
                Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . '热点生成遇新闻体，改走新闻体固定AI生成');
                if ($coze === null && !array_key_exists('aiCopywriting', self::$hotspotTestHooks)) {
                    try {
                        $coze = self::buildPersonaCopywritingParams($device, $persona);
                    } catch (\Throwable $e) {
                        Log::channel('ipVideoSynthesis')->write(
                            '设备号' . $deviceCode . '新闻体固定AI文案参数失败：' . $e->getMessage()
                        );
                        if (empty(self::$hotspotTestHooks['skipFailedTask'])) {
                            $failed = self::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                                'config' => $config,
                                'copywriting_source' => AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI,
                                'exec_time' => $execTime,
                                'title' => 'AI自动生成视频',
                                'msg' => '',
                            ]);
                            if ($failed) {
                                $createdTasks[] = $failed;
                            }
                        }
                        continue;
                    }
                }
                $newsTask = self::createAiGeneratedSlotTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    $coze ?? [],
                    $shanjianType,
                    $execTime,
                    $key
                );
                if ($newsTask) {
                    $createdTasks[] = $newsTask;
                }
                continue;
            }

            if ($skipHotspotGenerate) {
                Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . '算力不足，跳过后续热点名额');
                continue;
            }

            $onLocked = function (mixed $row, mixed $log) use (
                $config,
                $shanjianType,
                $userId,
                $personaId,
                &$resolvedPersona
            ) {
                if (!empty(self::$hotspotTestHooks['personaFrozen'])) {
                    throw new HotspotUpstreamException(AiPersona::FROZEN_USAGE_MESSAGE);
                }
                if ($resolvedPersona === []) {
                    if (isset(self::$hotspotTestHooks['resolvedPersona'])
                        && is_array(self::$hotspotTestHooks['resolvedPersona'])
                    ) {
                        $resolvedPersona = self::$hotspotTestHooks['resolvedPersona'];
                    } else {
                        PoolService::requireEnabledPersona($userId, $personaId);
                        $resolvedPersona = PersonaService::resolve($userId, ['id' => $personaId]);
                    }
                }
                $generated = self::generateHotspotScript($row, $resolvedPersona, $config, $shanjianType, $userId);
                $taskMsg = trim((string)($generated['script'] ?? ''));
                if (PoolService::isTitleOnly($taskMsg)) {
                    throw new HotspotUpstreamException('生成失败');
                }
                if (!PoolService::claimPoolForPersona($row, $personaId)) {
                    throw new HotspotUpstreamException('热点与当前人设不匹配');
                }
                self::writeHotspotUseSnapshot($log, $row, (string)($generated['title'] ?? ''), $taskMsg);
            };

            while (true) {
            $consumed = null;
            try {
                $consumed = PoolService::consumeForDevice(
                    $userId,
                    $personaId,
                    $deviceCode,
                    $pickMode,
                    $excludeIds,
                    $onLocked
                );
            } catch (HotspotUpstreamException $e) {
                $roundTouched = true;
                $poolId = (int)$e->poolId;
                if ($poolId > 0) {
                    $excludeIds[] = $poolId;
                }
                $msg = $e->getMessage();
                if ($msg === ScriptService::ERROR_SCRIPT_MISS_TOPIC && $poolId > 0) {
                    PoolService::parkMissTopicForDevice($userId, $personaId, $deviceCode, $poolId, $pickMode);
                    Log::channel('ipVideoSynthesis')->write(
                        '设备号' . $deviceCode . '热点编号=' . $poolId . '口播未点到话题，已按设备停用，不再重试'
                    );
                    continue;
                }
                Log::channel('ipVideoSynthesis')->write(
                    '设备号' . $deviceCode . '热点编号=' . ($poolId > 0 ? $poolId : '-') . '口播生成失败：' . $msg
                );
                if ($e->getCode() === 4059 || str_contains($msg, '算力不足')) {
                    $skipHotspotGenerate = true;
                    break;
                }
                continue;
            }

            if ($consumed === null) {
                $hasLaterNews = false;
                for ($j = $key + 1, $typeCount = count($videoTypes); $j < $typeCount; $j++) {
                    if ((int)$videoTypes[$j] === 4) {
                        $hasLaterNews = true;
                        break;
                    }
                }
                if ($hasLaterNews) {
                    Log::channel('ipVideoSynthesis')->write(
                        '设备号' . $deviceCode . '热点暂无待用，继续后续新闻体名额'
                    );
                    break;
                }
                if ($createdTasks === []) {
                    if (PoolService::catalogCount($userId, $personaId) <= 0) {
                        Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . '热点库暂无待用');
                    } else {
                        Log::channel('ipVideoSynthesis')->write(
                            '设备号' . $deviceCode . self::hotspotExhaustedCopy($pickMode)
                        );
                    }
                    return true;
                }
                Log::channel('ipVideoSynthesis')->write(sprintf(
                    '设备号%s%s，已建%d条，剩余名额不补AI',
                    $deviceCode,
                    self::hotspotExhaustedCopy($pickMode),
                    count($createdTasks)
                ));
                break 2;
            }

            $consumedAny = true;
            $pool = $consumed['pool'];
            $useLogId = (int)($consumed['use_log_id'] ?? 0);
            $poolId = (int)$pool->id;
            $taskTitle = trim((string)($consumed['title'] ?? ''));
            $taskMsg = trim((string)($consumed['script'] ?? ''));
            $copywritingSource = AiPersonaSynthesisConfig::COPYWRITING_SOURCE_HOTSPOT;
            $publishTopic = ShanjianVideoTask::stringifyTopic($pool->topic ?? '');

            try {
                if (!empty(self::$hotspotTestHooks['materialNotReady'])) {
                    throw new MaterialNotReadyException('素材转码尚未完成,热点生成测试');
                }
                if (PoolService::isTitleOnly($taskMsg)) {
                    PoolService::revertDeviceUse($poolId, $deviceCode, $useLogId, true);
                    $excludeIds[] = $poolId;
                    Log::channel('ipVideoSynthesis')->write(
                        '设备号' . $deviceCode . '现写未产出口播，跳过热点编号=' . $poolId
                    );
                    continue;
                }

                $result = self::dispatchHotspotVideoTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    $taskMsg,
                    $taskTitle,
                    $shanjianType,
                    $copywritingSource,
                    $execTime,
                    $publishTopic
                );
                if (!$result) {
                    throw new \Exception('热点生成建单失败');
                }
                $createdTasks[] = $result;
                PoolService::bindVideoTask($poolId, (int)($result['task_id'] ?? 0), $useLogId);
                break;
            } catch (MaterialNotReadyException $e) {
                PoolService::revertDeviceUse($poolId, $deviceCode, $useLogId, true);
                throw $e;
            } catch (HotspotUpstreamException $e) {
                PoolService::revertDeviceUse($poolId, $deviceCode, $useLogId, true);
                $excludeIds[] = $poolId;
                $msg = $e->getMessage();
                Log::channel('ipVideoSynthesis')->write(
                    '设备号' . $deviceCode . '热点编号=' . $poolId . '口播生成失败：' . $msg
                );
                if ($e->getCode() === 4059 || str_contains($msg, '算力不足')) {
                    $skipHotspotGenerate = true;
                    break;
                }
                continue;
            } catch (\Throwable $e) {
                PoolService::revertDeviceUse($poolId, $deviceCode, $useLogId, true);
                $excludeIds[] = $poolId;
                $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType
                    . '，热点生成任务创建失败: ' . $e->getMessage();
                Log::channel('ipVideoSynthesis')->write($msg);
                if (empty(self::$hotspotTestHooks['skipFailedTask'])) {
                    $failed = self::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                        'config' => $config,
                        'copywriting_source' => $copywritingSource,
                        'exec_time' => $execTime,
                        'title' => $taskTitle ?: '热点生成视频',
                        'msg' => $taskMsg,
                        'publish_topic' => $publishTopic,
                    ]);
                    if ($failed) {
                        $createdTasks[] = $failed;
                        break;
                    }
                }
                continue;
            }
            }
        }

        if (empty($createdTasks)) {
            $hasHotspotSlot = false;
            foreach ($videoTypes as $type) {
                if ((int)$type !== 4) {
                    $hasHotspotSlot = true;
                    break;
                }
            }
            if (!$hasHotspotSlot) {
                Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . '本轮未建成新闻体固定AI视频');
                return true;
            }
            if (!$consumedAny && !$roundTouched) {
                if (PoolService::catalogCount($userId, $personaId) <= 0) {
                    Log::channel('ipVideoSynthesis')->write('设备号' . $deviceCode . '热点库暂无待用');
                } else {
                    Log::channel('ipVideoSynthesis')->write(
                        '设备号' . $deviceCode . self::hotspotExhaustedCopy($pickMode)
                    );
                }
                return true;
            }
            Log::channel('ipVideoSynthesis')->write(
                '设备号' . $deviceCode . '本轮未建成热点视频（口播未点题已停用或建单失败）'
            );
            return true;
        }

        if (empty(self::$hotspotTestHooks['skipMarkFinished'])) {
            self::markSocialSynthesisFinishedIfQuotaReached($device, '热点生成');
        }

        return [
            'device' => $device->toArray(),
            'persona' => $persona->toArray(),
            'tasks' => $createdTasks,
            'count' => count($createdTasks),
        ];
    }

    private static function hotspotExhaustedCopy(int $pickMode): string
    {
        return $pickMode === AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_ONCE_DEVICE
            ? '热点库已无未用待用'
            : '该设备已无未用待用';
    }

    /**
     * 占用行和建单共用的标题：生成标题为空则用热点库标题，再空则用话题，最长 200 字。
     */
    private static function resolveHotspotUseTitle(string $generatedTitle, mixed $pool): string
    {
        $title = trim($generatedTitle);
        if ($title === '') {
            $title = trim((string)($pool->title ?? ''));
        }
        if ($title === '') {
            $title = trim((string)($pool->topic ?? ''));
        }
        return mb_substr($title, 0, 200);
    }

    /**
     * 摘要里的「暂无」换成热点话题。话题为空或摘要里没有「暂无」时原样返回。
     */
    private static function fillAbsentSummary(string $summary, string $topic): string
    {
        $topic = trim($topic);
        if ($topic === '' || $summary === '' || !str_contains($summary, '暂无')) {
            return $summary;
        }
        return str_replace('暂无', $topic, $summary);
    }

    /**
     * 库里已有摘要仍含「暂无」时换话题并写回。核清刚写入的摘要已替换过，这里不再保存。
     */
    private static function persistFilledSummary(HotspotPool $pool, string $topic): void
    {
        $current = (string)($pool->summary ?? '');
        $filled = self::fillAbsentSummary($current, $topic);
        if ($filled === $current) {
            return;
        }
        $pool->summary = $filled;
        $pool->update_time = time();
        $pool->save();
        HotspotLog::write(sprintf(
            '热点生成摘要暂无已换成话题：编号=%d 话题=%s',
            (int)($pool->id ?? 0),
            $topic
        ));
        Log::channel('ipVideoSynthesis')->write(
            '热点编号=' . (int)($pool->id ?? 0) . '摘要暂无已换成话题'
        );
    }

    private static function writeHotspotUseSnapshot(mixed $log, mixed $row, string $generatedTitle, string $script): void
    {
        $log->title = self::resolveHotspotUseTitle($generatedTitle, $row);
        $log->script = $script;
        $log->summary = (string)($row->summary ?? '');
        $log->save();
    }

    /**
     * 仅标题且摘要为空：复用追踪核清写回池，再现写。
     */
    private static function ensureTitleOnlyResearch(HotspotPool $pool, int $userId): void
    {
        if (!PoolService::isTitleOnly((string)($pool->script ?? ''))) {
            return;
        }
        if (trim((string)($pool->summary ?? '')) !== '') {
            return;
        }
        $topic = trim((string)($pool->topic ?? ''));
        $platform = (string)($pool->platform ?: 'douyin');
        $poolId = (int)($pool->id ?? 0);
        if (array_key_exists('research', self::$hotspotTestHooks)) {
            $hook = self::$hotspotTestHooks['research'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            $result = is_callable($hook)
                ? $hook($topic, $platform, $userId, $pool)
                : (is_array($hook) ? $hook : []);
        } else {
            $result = ResearchService::research($topic, $platform, '', $userId);
        }
        if (!is_array($result)) {
            $result = [];
        }
        $summary = self::fillAbsentSummary(trim((string)($result['summary'] ?? '')), $topic);
        if ($summary === '') {
            HotspotLog::write(sprintf(
                '热点生成仅标题核清无摘要：编号=%d 话题=%s',
                $poolId,
                $topic
            ));
            Log::channel('ipVideoSynthesis')->write(
                '热点编号=' . ($poolId > 0 ? $poolId : '-') . '仅标题核清无摘要，仍按空材料现写'
            );
            return;
        }
        $points = $result['core_points'] ?? $result['core_points_json'] ?? [];
        if (!is_array($points)) {
            $points = [];
        }
        $pool->summary = $summary;
        $pool->core_points_json = $points;
        $pool->update_time = time();
        $pool->save();
        HotspotLog::write(sprintf(
            '热点生成仅标题已核清摘要：编号=%d 话题=%s 摘要字数=%d 要点数=%d',
            $poolId,
            $topic,
            mb_strlen($summary),
            count($points)
        ));
        Log::channel('ipVideoSynthesis')->write(
            '热点编号=' . ($poolId > 0 ? $poolId : '-') . '仅标题已核清摘要，要点数=' . count($points)
        );
    }

    private static function generateHotspotScript(HotspotPool $pool, array $persona, mixed $config, int $shanjianType, int $userId): array
    {
        $topic = trim((string)($pool->topic ?? ''));
        if ($topic === '') {
            throw new HotspotUpstreamException('生成失败');
        }
        self::ensureTitleOnlyResearch($pool, $userId);
        self::persistFilledSummary($pool, $topic);
        $analysis = PoolService::resolveAnalysis($pool);
        $corePoints = is_array($pool->core_points_json ?? null) ? $pool->core_points_json : [];
        $options = self::buildHotspotScriptOptions($pool, $analysis, $config, $shanjianType);
        HotspotLog::write(sprintf(
            '热点生成现写口播：编号=%d 话题=%s 类型=%d',
            (int)$pool->id,
            $topic,
            $shanjianType
        ));
        if (array_key_exists('scriptResult', self::$hotspotTestHooks)) {
            $hook = self::$hotspotTestHooks['scriptResult'];
            $result = is_callable($hook) ? $hook($pool, $shanjianType, $userId) : $hook;
            if ($result instanceof \Throwable) {
                throw $result;
            }
            if (!is_array($result)) {
                $result = [];
            }
        } else {
            $result = ScriptService::generate(
                $topic,
                (string)($pool->platform ?: 'douyin'),
                $corePoints,
                (string)($pool->summary ?? ''),
                $persona,
                $analysis,
                $options,
                $userId
            );
        }
        $script = trim((string)($result['script'] ?? ''));
        if ($script === '') {
            throw new HotspotUpstreamException('生成失败');
        }
        return $result;
    }

    /**
     * 热点生成现写的 options：键与热点追踪页进 generate() 的缺省对齐。
     * 不塞合成配置的文案生成类型，也不写追踪创作台账。
     */
    private static function buildHotspotScriptOptions(HotspotPool $pool, array $analysis, mixed $config, int $shanjianType): array
    {
        return [
            'goal' => (string)($pool->goal ?: ($analysis['recommended_goal'] ?? 'traffic')),
            'direction' => (string)($pool->direction ?: ($analysis['recommended_direction'] ?? '观点输出')),
            'material_mode' => PoolService::visualMaterialMode((int)($config->visual_material_source ?? 2)),
            'duration_sec' => 60,
            'video_type' => $shanjianType === 1 ? 'digital' : 'clips',
            'persona_mode' => ScriptService::PERSONA_MODE_PERSONA,
            'cta' => '',
            'product' => '',
            'skip_topic_guard' => true,
            'include_topic_in_original' => true,
        ];
    }

    private static function dispatchHotspotVideoTask(
        mixed $device,
        mixed $persona,
        mixed $config,
        array $groupedData,
        string $taskMsg,
        string $taskTitle,
        int $shanjianType,
        int $copywritingSource,
        string $execTime,
        string $publishTopic = ''
    ) {
        if (array_key_exists('createVideo', self::$hotspotTestHooks)) {
            $hook = self::$hotspotTestHooks['createVideo'];
            return is_callable($hook)
                ? $hook($device, $persona, $config, $groupedData, $taskMsg, $taskTitle, $shanjianType, $copywritingSource, $execTime)
                : $hook;
        }
        if (!empty(self::$hotspotTestHooks['failVideoCreate'])) {
            $err = self::$hotspotTestHooks['failVideoCreate'];
            if ($err instanceof \Throwable) {
                throw $err;
            }
            throw new \Exception(is_string($err) ? $err : '热点生成建单失败');
        }
        return self::createImitationVideoTask(
            $device,
            $persona,
            $config,
            $groupedData,
            $taskMsg,
            $taskTitle,
            $shanjianType,
            $copywritingSource,
            0,
            $taskMsg ?: $taskTitle,
            $execTime,
            $publishTopic
        );
    }

    private static function createLibraryDeviceVideoTasks(SvDevice $device, AiPersona $persona, AiPersonaSynthesisConfig $config, array $groupedData, array $videoTypes, array $publishTimeSlots = [])
    {
        $deviceCode = $device->device_code;
        $userId = (int)$device->user_id;
        $createdTasks = [];
        $skipped = [];
        $publishTimeSlots = array_values($publishTimeSlots);

        $coze = null;
        try {
            $coze = self::buildPersonaCopywritingParams($device, $persona);
        } catch (\Throwable $e) {
            // 文案库有货时可继续；仅在需要 AI 兜底时再失败
            Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '文案库模式预加载AI文案参数失败：' . $e->getMessage());
        }

        foreach ($videoTypes as $key => $shanjianType) {
            $shanjianType = (int)$shanjianType;
            $execTime = $publishTimeSlots[$key] ?? '';
            $library = CopywritingLibraryLogic::consumeVideoCopywriting(
                $userId,
                (int)$device->persona_id,
                $shanjianType,
                $config
            );

            $taskTitle = '';
            $taskMsg = '';
            $materialKeywords = '';
            $copywritingSource = AiPersonaSynthesisConfig::COPYWRITING_SOURCE_LIBRARY;
            $libraryId = 0;
            $publishTopic = '';
            $pairedPublish = [];

            try {
                if (!empty($library)) {
                    $libraryId = (int)$library['id'];
                    $taskTitle = (string)($library['title'] ?? '');
                    $taskMsg = (string)($library['content'] ?? '');
                    if ($shanjianType === 4) {
                        $taskMsg = $taskTitle;
                    }
                    $materialKeywords = $taskMsg ?: $taskTitle;
                    $publishTopic = ShanjianVideoTask::stringifyTopic($library['topic'] ?? '');
                    $pairedPublish = [
                        'title' => trim((string)($library['publish_title'] ?? '')),
                        'content' => trim((string)($library['publish_content'] ?? '')),
                        'topic' => ShanjianVideoTask::stringifyTopic($library['publish_topic'] ?? ''),
                    ];
                } else {
                    // 文案库无可用文案：回退 AI 生成（AutoDeviceSettingLogic::copywriting 内扣费）
                    if ($coze === null) {
                        $coze = self::buildPersonaCopywritingParams($device, $persona);
                    }
                    $aiCopywriting = self::generateAiCopywritingForTask($coze, $config, $userId, $shanjianType);
                    $taskTitle = $aiCopywriting['title'];
                    $taskMsg = $aiCopywriting['msg'];
                    $materialKeywords = $aiCopywriting['material_keywords'];
                    $copywritingSource = AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI;
                    $skipped[] = [
                        'index' => $key + 1,
                        'shanjian_type' => $shanjianType,
                        'reason' => '视频驱动文案库暂无可用文案，已回退AI生成',
                    ];
                    Log::channel('ipVideoSynthesis')->write(
                        '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '文案库耗尽，回退AI生成并扣费'
                    );
                }

                $result = self::createImitationVideoTask(
                    $device,
                    $persona,
                    $config,
                    $groupedData,
                    $taskMsg,
                    $taskTitle,
                    $shanjianType,
                    $copywritingSource,
                    0,
                    $materialKeywords,
                    $execTime,
                    $publishTopic,
                    $pairedPublish
                );
                if ($result) {
                    $createdTasks[] = $result;
                    if ($libraryId > 0) {
                        CopywritingLibraryLogic::recordUse(
                            $libraryId,
                            AiPersonaCopywritingLibraryUseLog::SCENE_VIDEO,
                            [
                                'device_code' => (string)$deviceCode,
                                'related_video_task_id' => (int)($result['task_id'] ?? 0),
                                'shanjian_type' => $shanjianType,
                            ]
                        );
                    }
                }
            } catch (MaterialNotReadyException $e) {
                if ($libraryId > 0) {
                    CopywritingLibraryLogic::revertCopywritingUse($libraryId);
                }
                throw $e;
            } catch (\Throwable $e) {
                if ($libraryId > 0) {
                    CopywritingLibraryLogic::revertCopywritingUse($libraryId);
                }
                // 单条失败：落失败任务后继续下一条（失败也占名额，不锁设备）
                $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType
                    . '，' . ($libraryId > 0 ? '文案库' : '文案库AI兜底') . '任务创建失败: ' . $e->getMessage();
                Log::channel('ipVideoSynthesis')->write($msg);
                $failed = self::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                    'config' => $config,
                    'copywriting_source' => $copywritingSource,
                    'exec_time' => $execTime,
                    'title' => $taskTitle ?: ($libraryId > 0 ? '文案库视频' : 'AI自动生成视频'),
                    'msg' => $taskMsg,
                    'publish_topic' => $publishTopic,
                    'paired_publish' => $pairedPublish,
                ]);
                if ($failed) {
                    $createdTasks[] = $failed;
                }
                continue;
            }
        }

        if (empty($createdTasks)) {
            throw new \Exception('未成功创建任何文案库视频合成任务');
        }

        // 任务数（含失败）达到发布数才标记完成
        self::markSocialSynthesisFinishedIfQuotaReached($device, '文案库');

        return [
            'device' => $device->toArray(),
            'persona' => $persona->toArray(),
            'tasks' => $createdTasks,
            'count' => count($createdTasks),
            'skipped' => $skipped,
        ];
    }

    /**
     * 与 AI 合成一致的文案/标题生成（内部走 AutoDeviceSettingLogic::copywriting 扣费）
     */
    private static function generateAiCopywritingForTask(array $coze, AiPersonaSynthesisConfig $config, int $userId, int $shanjianType): array
    {
        $cozeParams = $coze;
        switch ($shanjianType) {
            case 1:
                $cozeParams['sn'] = 0;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 100;
                break;
            case 2:
                $cozeParams['sn'] = 0;
                $cozeParams['number'] = 5;
                $cozeParams['length'] = 80;
                break;
            case 3:
                $cozeParams['sn'] = 5;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 80;
                break;
            case 4:
                $cozeParams['sn'] = 2;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 80;
                break;
            default:
                throw new \Exception('视频类型不存在');
        }
        $voice = AiPersonaSynthesisConfig::buildCopywritingGenerationVoice(
            (int)($config->copywriting_generation_type ?? AiPersonaSynthesisConfig::COPYWRITING_GENERATION_TYPE_KNOWLEDGE),
            (string)($config->copywriting_generation_custom ?? '')
        );
       

        $cozeParams['voice'] = $voice;
        $cozeParams['hook'] = '';

        $copywritingLogContext = [
            'user_id' => (int)$userId,
            'task_type' => (int)$shanjianType,
            'sn' => (int)$cozeParams['sn'],
            'number' => (int)$cozeParams['number'],
            'length' => (int)$cozeParams['length'],
            'keywords_preview' => mb_substr((string)($cozeParams['keywords'] ?? ''), 0, 500),
            'copywriting_generation_type' => (int)($config->copywriting_generation_type ?? 1),
            'voice' => $voice,
            'hook' => '',
        ];
        Log::channel('ipVideoSynthesis')->write('纯AI文案生成请求：' . json_encode($copywritingLogContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $copywritingResult = AutoDeviceSettingLogic::copywriting($cozeParams, $userId, 6);
        Log::channel('ipVideoSynthesis')->write('纯AI文案生成正文结果：' . json_encode($copywritingResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $taskMsg = $copywritingResult['content'][0] ?? '';
        if (empty($taskMsg) && $shanjianType != 3) {
            throw new \Exception('AI文案生成失败');
        }


        $titleResult = AutoDeviceSettingLogic::copywriting([
            'sn' => 8,
            'number' => 1,
            'length' => 10,
            'keywords' => $taskMsg ?: ($cozeParams['keywords'] ?? ''),
            'voice' => $voice,
            'hook' => '',
        ], $userId, 6);
        Log::channel('ipVideoSynthesis')->write('纯AI文案生成标题结果：' . json_encode($titleResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $taskTitle = $titleResult['content'][0] ?? 'AI自动生成视频';

        if ($shanjianType == 4) {
            $taskTitle = $taskMsg;
        }

        return [
            'title' => $taskTitle,
            'msg' => $taskMsg,
            'material_keywords' => $taskMsg ?: ($coze['keywords'] ?? ''),
        ];
    }

}
