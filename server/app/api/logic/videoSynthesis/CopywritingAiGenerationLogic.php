<?php

namespace app\api\logic\videoSynthesis;

use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\aiPersona\CopywritingLibraryLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryUseLog;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvDevice;
use think\facade\Cache;
use app\common\exception\MaterialNotReadyException;
use think\facade\Db;
use think\facade\Log;

/**
 * AI文案生成视频合成（设备维度）
 * 处理 copywriting_source=2，支持全部 visual_material_source、video_cover_source
 * 处理 copywriting_source=3 无文案纯素材混剪
 * 处理 copywriting_source=4 文案库
 */
class CopywritingAiGenerationLogic extends CopywritingImitationLogic
{
    const CONTENT_PUBLISH_SCENE = 5;

    /**
     * 无文案模式设备视频合成主入口（copywriting_source=3）
     * 固定 shanjian_type=3，visual_material_source=3，video_cover_source 按合成配置
     */
    public static function processNoCopywritingSynthesis($deviceCode)
    {
        $device = null;
        try {
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
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

            $execTimes = self::getExecTime(self::getTimesByType($persona->persona_type), $device, $persona->persona_type);
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

            $groupedData = self::getMaterialsForImitation($device);
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
    private static function createNoCopywritingDeviceVideoTasks($device, $persona, $config, array $groupedData, int $taskCount, int $taskType = 3)
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
        $device->synthesis_m = 1;
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

    private static function formatContentScheduleSlots($schedules): array
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

    private static function getExecTime(array $times, SvDevice $device, $personaType): array
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
    public static function videoSynthesis($deviceCode)
    {
        return self::videoSynthesisByTypes($deviceCode);
    }

    public static function newsMixcutSynthesis($deviceCode)
    {
        return self::videoSynthesisByTypes($deviceCode, [4]);
    }

    /**
     * 文案库模式设备视频合成主入口（copywriting_source=4）
     */
    public static function librarySynthesis($deviceCode)
    {
        $device = null;
        try {
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
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

            $groupedData = self::getMaterialsForImitation($device);
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

    private static function videoSynthesisByTypes($deviceCode, ?array $forceVideoTypes = null)
    {
        $device = null;
        try {
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
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
            $groupedData = self::getMaterialsForImitation($device);

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
     * 批量创建设备视频合成任务
     * 素材选择复用父类 createImitationVideoTask：
     * - shanjian_type=1 且 visual_material_source=1/2 → selectMaterialsForTaskTypeAndVisualMaterialSource（AI抓素材）
     * - shanjian_type=3/4 及其它 → selectMaterialsForTaskType（仅素材库）
     */
    private static function createDeviceVideoTasks($device, $persona, $config, array $groupedData, array $videoTypes, array $publishTimeSlots = [])
    {
        $deviceCode = $device->device_code;
        $userId = $device->user_id;
        $createdTasks = [];
        $publishTimeSlots = array_values($publishTimeSlots);

        try {
            $coze = self::buildPersonaCopywritingParams($device, $persona);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        foreach ($videoTypes as $key => $shanjianType) {
            $shanjianType = (int)$shanjianType;
            $execTime = $publishTimeSlots[$key] ?? '';
            $taskTitle = '';
            $taskMsg = '';
            try {
                $canUseAiMaterials = $shanjianType === 1
                    && in_array((int)$config->visual_material_source, [1, 2], true);
                if (!$canUseAiMaterials) {
                    self::assertGroupedMaterialsAvailable(
                        $groupedData,
                        (int)$device->persona_id,
                        [$shanjianType],
                        AiPersonaSynthesisConfig::VISUAL_MATERIAL_SOURCE_MATERIAL
                    );
                }

                $aiCopywriting = self::generateAiCopywritingForTask($coze, $userId, $shanjianType);
                $taskTitle = $aiCopywriting['title'];
                $taskMsg = $aiCopywriting['msg'];
                $materialKeywords = $aiCopywriting['material_keywords'];

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
                if ($result) {
                    $createdTasks[] = $result;
                }
            } catch (MaterialNotReadyException $e) {
                // 素材转码未就绪:不落库失败任务,异常透传给 cron 等下一轮
                throw $e;
            } catch (\Throwable $e) {
                // 单条失败：落失败任务后继续下一条（失败也占名额，不锁设备）
                $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，任务创建失败: ' . $e->getMessage();
                Log::channel('ipVideoSynthesis')->write($msg);
                $failed = self::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                    'config' => $config,
                    'copywriting_source' => 2,
                    'exec_time' => $execTime,
                    'title' => $taskTitle ?: 'AI自动生成视频',
                    'msg' => $taskMsg ?? '',
                ]);
                if ($failed) {
                    $createdTasks[] = $failed;
                }
                continue;
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

    private static function createLibraryDeviceVideoTasks($device, $persona, $config, array $groupedData, array $videoTypes, array $publishTimeSlots = [])
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

            try {
                if (!empty($library)) {
                    $libraryId = (int)$library['id'];
                    $taskTitle = (string)($library['title'] ?? '');
                    $taskMsg = (string)($library['content'] ?? '');
                    if ($shanjianType === 4) {
                        $taskMsg = $taskTitle;
                    }
                    $materialKeywords = $taskMsg ?: $taskTitle;
                } else {
                    // 文案库无可用文案：回退 AI 生成（AutoDeviceSettingLogic::copywriting 内扣费）
                    if ($coze === null) {
                        $coze = self::buildPersonaCopywritingParams($device, $persona);
                    }
                    $aiCopywriting = self::generateAiCopywritingForTask($coze, $userId, $shanjianType);
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
                    $execTime
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
    private static function generateAiCopywritingForTask(array $coze, int $userId, int $shanjianType): array
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

        $copywritingResult = AutoDeviceSettingLogic::copywriting($cozeParams, $userId, 6);
        $taskMsg = $copywritingResult['content'][0] ?? '';
        if (empty($taskMsg) && $shanjianType != 3) {
            throw new \Exception('AI文案生成失败');
        }

        $titleResult = AutoDeviceSettingLogic::copywriting([
            'sn' => 8,
            'number' => 1,
            'length' => 10,
            'keywords' => $taskMsg ?: ($cozeParams['keywords'] ?? ''),
        ], $userId, 6);
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
