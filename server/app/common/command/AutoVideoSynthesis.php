<?php


namespace app\common\command;

use app\api\logic\aiPersona\SynthesisConfigLogic;
use app\api\logic\videoSynthesis\CopywritingAiGenerationLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\CloudSvDevice;
use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * AutoVideoSynthesis
 * @desc 自动合成视频任务（统一入口）
 *       仅按设备维度遍历 SvDevice，读取当前绑定人设的 ai_persona_synthesis_config 分流：
 *         - 1 爆款仿写  -> 按 device_code + persona_id 查文案，调用 CopywritingImitationLogic
 *         - 2 AI直接生成 -> CopywritingAiGenerationLogic::videoSynthesis
 *         - 3 无需文案   -> CopywritingAiGenerationLogic::processNoCopywritingSynthesis
 *         - 4 文案库     -> CopywritingAiGenerationLogic::librarySynthesis
 *         - 5 热点生成   -> CopywritingAiGenerationLogic::hotspotSynthesis
 * @author dagouzi
 */
class AutoVideoSynthesis extends Command
{
    /**
     * 执行时间段（小时，浮点数）
     */
    const EXEC_START_TIME = 0.5;
    const EXEC_END_TIME   = 8.0;

    /**
     * 单设备的执行去重缓存时间（秒）
     */
    const DEDUP_CACHE_TTL = 3600;
    /**
     * 无手机模式「一条都没建成」时最多连续重试几轮，之后退回 1 小时节流。
     * 失败若发生在逐条建单的循环里会落失败任务、自然把配额吃满从而收敛；
     * 但建单之前就抛错（比如文案参数构造失败）不会留下任何任务，
     * 不设这个闸就会在凌晨 7 个多小时里每 8 分钟重试一次，白烧算力。
     */
    const CLOUD_NO_PROGRESS_RETRY_MAX = 3;

    /**
     * 单批处理上限
     */
    const DEVICE_BATCH_LIMIT      = 12;
    const COPYWRITING_BATCH_LIMIT = 6;

    protected function configure()
    {
        $this->setName('auto_video_synthesis')
            ->setDescription('自动合成视频任务（统一入口，按设备绑定人设与AI合成规则分流）');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            if (!$this->isInExecTimeWindow()) {
                print_r("\n 不在 0:30-12:00 内，任务跳过\n");
                return true;
            }

            print_r("\n ip人设视频合成开始...\n");
            $clientIp  = request()->ip();
            $userAgent = request()->header('user-agent');
            Log::channel('ipVideoSynthesis')->info(
                "ip人设自动合成视频任务（统一入口），任务触发源 - IP: {$clientIp}, UA: {$userAgent}"
            );
            // 不限制超时时间和执行内存
            set_time_limit(0);
            ini_set('memory_limit', '-1');

            // 统一设备维度：仿写(1) / AI直接生成(2) / 无需文案(3) / 文案库(4) / 热点生成(5)
            $this->handleDeviceLevel();
            // 无手机模式人设：不依赖设备行，按人设维度走同一套合成逻辑
            $this->handlePersonaLevel();

            return true;
        } catch (\Exception $e) {
            Log::channel('ipVideoSynthesis')->info('自动合成视频任务（统一入口）失败：' . $e->getMessage());
            return false;
        } finally {
            print_r("\n ip人设视频合成结束...\n");
        }
    }

    /**
     * 设备维度处理：按当前绑定人设的 copywriting_source 分流
     * 注：public 以便调试接口直接复用
     */
    public function handleDeviceLevel(?int $sourceFilter = null, ?int $batchLimit = null): void
    {
        $limit = max(1, $batchLimit ?? self::DEVICE_BATCH_LIMIT);
        $query = SvDevice::alias('d')
            ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
            ->join('ai_persona p', 'd.persona_id = p.id')
            ->where('d.auto_type', 1)//自动化操作
            ->where('d.synthesis_m', 0)//社媒任务没有完成
            // 过渡期交集条件：旧布尔锁与完成日期都判定"今天未完成"才挑选，日期化稳定后旧条件下线
            ->whereRaw(SvDevice::synthesisPendingDateSql(SvDevice::SYNTHESIS_SCENE_SOCIAL, 'd'))
            ->where('p.status', 1)//人设开启
            ->where('p.publish_mode', 1) // 仅制作视频（非成品库直发）
            // 无手机模式人设走 handlePersonaLevel，绑了手机也不在设备维度重复生成
            ->where('p.run_mode', AiPersona::RUN_MODE_PHONE)
            // ->where('d.is_first', 0)
            ->where('d.persona_id', '>', 0);
        if ($sourceFilter !== null) {
            $query->join(
                'ai_persona_synthesis_config c',
                'c.persona_id = d.persona_id AND c.user_id = d.user_id'
            )->where('c.copywriting_source', $sourceFilter);
        }
        $devices = $query
            // retry_date 非当天视为计数 0，避免历史失败计数让设备长期排在队尾
            ->orderRaw('IF(d.retry_date IS NULL OR d.retry_date < CURDATE(), 0, d.synthesis_m_retry_count) ASC, d.id ASC')
            ->limit($limit)
            ->select();
        Log::channel('ipVideoSynthesis')->write('设备维度处理：' . count($devices) . '条');
        Log::channel('ipVideoSynthesis')->write(json_encode($devices->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        foreach ($devices as $device) {
            $cacheKey = 'command_video_synthesis_' . $device->device_code;
            try {
                if (!AiPersonaOptionService::isEnabledForPersonaId((int)$device->persona_id, 'video_clip')) {
                    Log::channel('ipVideoSynthesis')->write('global_option.video_clip=0，跳过设备视频合成：' . $device->device_code);
                    SvDevice::markSynthesisDoneWhere(['device_code' => $device->device_code], SvDevice::SYNTHESIS_SCENE_SOCIAL);
                    continue;
                }

                if (Cache::store('material_redis')->has($cacheKey)) {
                    Log::channel('ipVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $device->device_code);
                    continue;
                }

                $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                    ->where('user_id', $device->user_id)
                    ->findOrEmpty();
                if ($config->isEmpty()) {
                    AiPersonaSynthesisConfig::create(
                        SynthesisConfigLogic::buildDefaultConfig(
                            (int) $device->persona_id,
                            (int) $device->user_id,
                            (int) $device->persona_type,
                            (int) ($device->publish_mode ?? 1)
                        )
                    );
                    Log::channel('ipVideoSynthesis')->write('设备未找到AI合成规则配置，跳过：' . $device->device_code);
                    continue;
                }

                $source = (int)$config->copywriting_source;

                // 仅真正开始处理时计数并乐观抢占，避免缓存命中/配置缺失也增加 retry_count。
                // retry_date 非当天时计数视为 0（按天惰性清零，替代 reset_video_synthesis 的全量清零）
                $retryCount = (int)$device->synthesis_m_retry_count;
                $isSameDay = ($device->retry_date ?? '') === date('Y-m-d');
                $claimed = SvDevice::where('id', (int)$device->id)
                    ->where('synthesis_m', 0)
                    ->where('synthesis_m_retry_count', $retryCount)
                    ->update([
                        'synthesis_m_retry_count' => $isSameDay ? $retryCount + 1 : 1,
                        'retry_date' => date('Y-m-d'),
                    ]);
                if (!$claimed) {
                    Log::channel('ipVideoSynthesis')->write('设备已被其他进程抢占，跳过：' . $device->device_code);
                    continue;
                }

                // 占位防重，避免本批与下一批之间被重复挑选
                Cache::store('material_redis')->set($cacheKey, 1, self::DEDUP_CACHE_TTL);

                switch ($source) {
                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE:
                        $this->processImitationForDevice($device, $config, $cacheKey);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI:
                        Log::channel('ipVideoSynthesis')->write('设备视频合成-AI生成：' . $device->device_code);
                        CopywritingAiGenerationLogic::videoSynthesis($device->device_code);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_NONE:
                        Log::channel('ipVideoSynthesis')->write('设备视频合成-无文案：' . $device->device_code);
                        CopywritingAiGenerationLogic::processNoCopywritingSynthesis($device->device_code);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_LIBRARY:
                        Log::channel('ipVideoSynthesis')->write('设备视频合成-文案库：' . $device->device_code);
                        CopywritingAiGenerationLogic::librarySynthesis($device->device_code);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_HOTSPOT:
                        Log::channel('ipVideoSynthesis')->write('设备视频合成-热点生成：' . $device->device_code);
                        CopywritingAiGenerationLogic::hotspotSynthesis($device->device_code);
                        break;

                    default:
                        Log::channel('ipVideoSynthesis')->write(
                            '设备视频合成-未知文案来源(' . $source . ')，跳过：' . $device->device_code
                        );
                        break;
                }
            } catch (\app\common\exception\MaterialNotReadyException $e) {
                Cache::store('material_redis')->delete($cacheKey);
                Log::channel('ipVideoSynthesis')->write(
                    '素材转码未就绪,本轮跳过待下一轮，device_code=' . $device->device_code . '，' . $e->getMessage()
                );
                continue;
            } catch (\Throwable $e) {
                Log::channel('ipVideoSynthesis')->info(
                    '设备视频合成异常，device_code=' . $device->device_code . '，错误：' . $e->getMessage()
                );
                continue;
            }
        }
    }

    /**
     * 人设维度处理（无手机模式）：没有设备行，用 cloud:{persona_id} 虚拟设备号驱动同一套合成逻辑，
     * 配额、去重、任务分区都沿用设备号语义；日锁与重试计数落人设表 cloud_* 三列。
     * 爆款仿写的文案靠绑定手机抓取，无手机模式下一律按 AI 生成处理。
     * 注：public 以便调试接口直接复用
     */
    public function handlePersonaLevel(?int $batchLimit = null): void
    {
        $limit = max(1, $batchLimit ?? self::DEVICE_BATCH_LIMIT);
        $personas = AiPersona::alias('p')
            ->field('p.*')
            ->where('p.run_mode', AiPersona::RUN_MODE_CLOUD)
            ->where('p.status', 1)
            ->where('p.publish_mode', 1)
            ->whereRaw(CloudSvDevice::pendingDateSql('p'))
            // 与设备维度同款：重试日期非当天视为计数 0
            ->orderRaw('IF(p.cloud_retry_date IS NULL OR p.cloud_retry_date < CURDATE(), 0, p.cloud_retry_count) ASC, p.id ASC')
            ->limit($limit)
            ->select();
        Log::channel('ipVideoSynthesis')->write('人设维度处理（无手机模式）：' . count($personas) . '条');
        foreach ($personas as $persona) {
            $personaId = (int)$persona->id;
            $deviceCode = CloudSvDevice::codeFor($personaId);
            $cacheKey = 'command_video_synthesis_' . $deviceCode;
            try {
                if (!AiPersonaOptionService::isEnabledForPersonaId($personaId, 'video_clip')) {
                    Log::channel('ipVideoSynthesis')->write('global_option.video_clip=0，跳过人设视频合成：' . $deviceCode);
                    CloudSvDevice::markPersonaDone($personaId);
                    continue;
                }

                if (Cache::store('material_redis')->has($cacheKey)) {
                    Log::channel('ipVideoSynthesis')->write('人设60分钟内已执行过，跳过：' . $deviceCode);
                    continue;
                }

                $config = AiPersonaSynthesisConfig::where('persona_id', $personaId)
                    ->where('user_id', (int)$persona->user_id)
                    ->findOrEmpty();
                if ($config->isEmpty()) {
                    $default = SynthesisConfigLogic::buildDefaultConfig(
                        $personaId,
                        (int)$persona->user_id,
                        (int)$persona->persona_type,
                        (int)($persona->publish_mode ?? 1)
                    );
                    AiPersonaSynthesisConfig::create($default);
                    Log::channel('ipVideoSynthesis')->write('人设未找到AI合成规则配置，已建默认配置，本轮跳过：' . $deviceCode);
                    continue;
                }

                $source = (int)$config->copywriting_source;

                // 乐观抢占：计数落人设表，按天惰性清零
                $retryCount = (int)($persona->cloud_retry_count ?? 0);
                $isSameDay = (string)($persona->cloud_retry_date ?? '') === date('Y-m-d');
                $claimed = AiPersona::where('id', $personaId)
                    ->whereRaw(CloudSvDevice::pendingDateSql())
                    ->where('cloud_retry_count', $retryCount)
                    ->update([
                        'cloud_retry_count' => $isSameDay ? $retryCount + 1 : 1,
                        'cloud_retry_date' => date('Y-m-d'),
                    ]);
                if (!$claimed) {
                    Log::channel('ipVideoSynthesis')->write('人设已被其他进程抢占，跳过：' . $deviceCode);
                    continue;
                }

                Cache::store('material_redis')->set($cacheKey, 1, self::DEDUP_CACHE_TTL);
                $attempt = $isSameDay ? $retryCount + 1 : 1;
                $tasksBefore = $this->countCloudTasksToday($personaId, $deviceCode);

                switch ($source) {
                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE:
                        // 爆款仿写在无手机模式下照常跑：爆款解析走第三方接口不碰手机，
                        // 设备号只是分区键，用虚拟设备顶上即可（没绑手机时爆款要用户手动导入分享链接）
                        Log::channel('ipVideoSynthesis')->write('人设视频合成-爆款仿写：' . $deviceCode);
                        $this->processImitationForDevice(CloudSvDevice::fromPersona($persona), $config, $cacheKey);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI:
                        Log::channel('ipVideoSynthesis')->write('人设视频合成-AI生成：' . $deviceCode);
                        CopywritingAiGenerationLogic::videoSynthesis($deviceCode);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_NONE:
                        Log::channel('ipVideoSynthesis')->write('人设视频合成-无文案：' . $deviceCode);
                        CopywritingAiGenerationLogic::processNoCopywritingSynthesis($deviceCode);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_LIBRARY:
                        Log::channel('ipVideoSynthesis')->write('人设视频合成-文案库：' . $deviceCode);
                        CopywritingAiGenerationLogic::librarySynthesis($deviceCode);
                        break;

                    case AiPersonaSynthesisConfig::COPYWRITING_SOURCE_HOTSPOT:
                        Log::channel('ipVideoSynthesis')->write('人设视频合成-热点生成：' . $deviceCode);
                        CopywritingAiGenerationLogic::hotspotSynthesis($deviceCode);
                        break;

                    default:
                        Log::channel('ipVideoSynthesis')->write(
                            '人设视频合成-未知文案来源(' . $source . ')，跳过：' . $deviceCode
                        );
                        break;
                }

                // 本轮没把当天条数补齐就清掉去重缓存，允许下一轮继续补。
                // 不清的话，一次中途失败或被打断会把这个人设锁死一小时，
                // 而凌晨窗口一共才 7 个多小时，白白丢掉一大截。
                $this->releaseCloudDedupWhenQuotaUnreached(
                    $personaId,
                    $config,
                    $cacheKey,
                    $deviceCode,
                    $tasksBefore,
                    $attempt
                );
            } catch (\app\common\exception\MaterialNotReadyException $e) {
                Cache::store('material_redis')->delete($cacheKey);
                Log::channel('ipVideoSynthesis')->write(
                    '素材转码未就绪,本轮跳过待下一轮，device_code=' . $deviceCode . '，' . $e->getMessage()
                );
                continue;
            } catch (\Throwable $e) {
                Log::channel('ipVideoSynthesis')->info(
                    '人设视频合成异常，device_code=' . $deviceCode . '，错误：' . $e->getMessage()
                );
                continue;
            }
        }
    }

    /**
     * 无手机模式当天条数没补齐时释放去重缓存，让下一轮继续补齐。
     * 与设备维度仿写分支「未达发布数量，保留待补齐」是同一套口径。
     */
    private function releaseCloudDedupWhenQuotaUnreached(
        int $personaId,
        AiPersonaSynthesisConfig $config,
        string $cacheKey,
        string $deviceCode,
        int $tasksBefore,
        int $attempt
    ): void {
        $configured = AiPersonaSynthesisConfig::normalizeDailyVideoCount($config->daily_video_count);
        $expect = $configured > 0 ? $configured : AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT;
        $generated = $this->countCloudTasksToday($personaId, $deviceCode);
        if ($generated >= $expect) {
            return;
        }

        // 这一轮一条都没建成：多半是建单之前就出错，不会留下失败任务，配额永远吃不满。
        // 连续几轮无进展就不再放行，退回 1 小时节流，避免整夜重试。
        if ($generated <= $tasksBefore && $attempt >= self::CLOUD_NO_PROGRESS_RETRY_MAX) {
            Log::channel('ipVideoSynthesis')->write(sprintf(
                '无手机模式连续%d轮无进展，保留去重缓存节流：%s expect=%d generated=%d',
                $attempt,
                $deviceCode,
                $expect,
                $generated
            ));
            return;
        }

        Cache::store('material_redis')->delete($cacheKey);
        Log::channel('ipVideoSynthesis')->write(sprintf(
            '无手机模式未达当天条数，已释放去重缓存待下轮补齐：%s expect=%d generated=%d attempt=%d',
            $deviceCode,
            $expect,
            $generated,
            $attempt
        ));
    }

    /** 无手机模式的每日出片条数：0=未配置兜底 1 条，与 CopywritingAiGenerationLogic 同口径 */
    private static function resolveCloudDailyCount(AiPersonaSynthesisConfig $config): int
    {
        $count = AiPersonaSynthesisConfig::normalizeDailyVideoCount($config->daily_video_count);
        return $count > 0 ? $count : AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT;
    }

    /** 今天挂在这个云端设备号下的自动社媒任务数（含失败，与配额口径一致） */
    private function countCloudTasksToday(int $personaId, string $deviceCode): int
    {
        $today = date('Y-m-d');
        return (int)ShanjianVideoTask::where('device_code', $deviceCode)
            ->where('persona_id', $personaId)
            ->where('auto_type', 1)
            ->where('wechat_type', 0)
            ->whereBetween('create_time', [strtotime($today . ' 00:00:00'), strtotime($today . ' 23:59:59')])
            ->count();
    }

    /**
     * 爆款仿写：仅按设备当前绑定的 persona_id + device_code 取文案
     * 注：public 以便调试接口直接复用
     */
    public function processImitationForDevice(SvDevice $device, AiPersonaSynthesisConfig $config, ?string $cacheKey = null): void
    {
        $today = date('Y-m-d');
        $deviceCode = (string)$device->device_code;
        $userId = (int)$device->user_id;
        $personaId = (int)$device->persona_id;

        if ((int)($device->publish_mode ?? 0) !== 1) {
            Log::channel('explosionVideoSynthesis')->write(
                '爆款仿写人设发布模式不符合要求，跳过：' . json_encode([
                    'device_code' => $deviceCode,
                    'persona_id' => $personaId,
                    'publish_mode' => (int)($device->publish_mode ?? 0),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            return;
        }

        // 含新闻体时，先走新闻体固定 AI 生成（与历史兜底一致）
        // 注意：新闻体只补类型4，未达发布数时不得锁 synthesis_m（由下方配额判断统一标记）
        if ($this->hasNewsMixcutType($config)) {
            Log::channel('ipVideoSynthesis')->write(
                '设备视频合成-新闻体固定AI生成：' . $deviceCode . '，新闻体时长=' . $config->news_mixcut_duration
            );
            try {
                CopywritingAiGenerationLogic::newsMixcutSynthesis($deviceCode);
            } catch (\Throwable $e) {
                // 新闻体失败不中断后续仿写补齐
                Log::channel('ipVideoSynthesis')->write(
                    '设备视频合成-新闻体固定AI生成失败，继续仿写补齐：' . $deviceCode . '，' . $e->getMessage()
                );
            }
        }

        // 手机模式的条数来自发布时段行数；无手机模式没有发布时段，按「每日出片条数」算，
        // 与 AI 生成那条链路（resolveCloudDailyVideoCount）同一口径，0=未配置兜底 1 条
        $publishCount = $device instanceof CloudSvDevice
            ? self::resolveCloudDailyCount($config)
            : MarketingTemplateSchedule::getTodayPublishTaskCount($personaId);
        if ($publishCount <= 0) {
            Log::channel('explosionVideoSynthesis')->write('爆款仿写发布时段为空，跳过设备视频合成：' . json_encode([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 不标 synthesis_m，等配置好发布时段后下轮再试
            return;
        }

        $generatedCount = $this->getImitationSynthesisVideoCount($userId, $personaId, $deviceCode, $today);
        $remaining = $publishCount - $generatedCount;
        if ($remaining <= 0) {
            $this->markDeviceSynthesisFinishedIfReached(
                $userId,
                $personaId,
                $deviceCode,
                $today,
                $publishCount,
                $generatedCount
            );
            Log::channel('explosionVideoSynthesis')->write('爆款仿写合成数量已达发布数量，跳过：' . json_encode([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
                'day' => $today,
                'publish_count' => $publishCount,
                'generated_count' => $generatedCount,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        $copywritings = $this->getDeviceImitationCopywritings(
            $userId,
            $personaId,
            $deviceCode,
            $today,
            min($remaining, self::COPYWRITING_BATCH_LIMIT)
        );
        if (empty($copywritings)) {
            Log::channel('explosionVideoSynthesis')->write('爆款仿写暂无可用文案，本轮跳过待下轮：' . json_encode([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
                'day' => $today,
                'remaining' => $remaining,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 关键关键：无文案时不锁设备，避免永远不合成
            if ($cacheKey) {
                Cache::store('material_redis')->delete($cacheKey);
            }
            return;
        }

        Log::channel('explosionVideoSynthesis')->write('设备爆款仿写待合成文案：' . json_encode([
            'user_id' => $userId,
            'persona_id' => $personaId,
            'device_code' => $deviceCode,
            'copywriting_ids' => array_map(static fn($item) => (int)$item->id, $copywritings),
            'remaining' => $remaining,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($copywritings as $copywriting) {
            $itemCacheKey = 'command_video_synthesis_cw_' . $copywriting->id;
            try {
                // 二次校验：文案必须属于设备当前绑定人设，且规则仍为仿写
                if ((int)$copywriting->persona_id !== $personaId
                    || (string)$copywriting->device_code !== $deviceCode
                    || (int)$copywriting->user_id !== $userId
                ) {
                    Log::channel('explosionVideoSynthesis')->write('文案与设备当前绑定不匹配，跳过：' . json_encode([
                        'copywriting_id' => (int)$copywriting->id,
                        'copywriting_persona_id' => (int)$copywriting->persona_id,
                        'copywriting_device_code' => (string)$copywriting->device_code,
                        'device_persona_id' => $personaId,
                        'device_code' => $deviceCode,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    continue;
                }

                $latestConfig = AiPersonaSynthesisConfig::where('persona_id', $personaId)
                    ->where('user_id', $userId)
                    ->findOrEmpty();
                if ($latestConfig->isEmpty()
                    || (int)$latestConfig->copywriting_source !== AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE
                ) {
                    Log::channel('explosionVideoSynthesis')->write(
                        '设备当前合成规则已非爆款仿写，停止仿写：' . json_encode([
                            'copywriting_id' => (int)$copywriting->id,
                            'persona_id' => $personaId,
                            'device_code' => $deviceCode,
                            'copywriting_source' => $latestConfig->isEmpty() ? null : (int)$latestConfig->copywriting_source,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );
                    // 规则已变：不误锁设备，留给设备维度下轮按新规则处理
                    if ($cacheKey) {
                        Cache::store('material_redis')->delete($cacheKey);
                    }
                    return;
                }

                if (Cache::store('material_redis')->has($itemCacheKey)) {
                    Log::channel('explosionVideoSynthesis')->write('文案60分钟内已执行过，跳过：' . $copywriting->id);
                    continue;
                }
                Cache::store('material_redis')->set($itemCacheKey, 1, self::DEDUP_CACHE_TTL);

                Log::channel('explosionVideoSynthesis')->write('文案视频合成-爆款仿写：' . $copywriting->id);
                $result = CopywritingImitationLogic::copywritingImitation($copywriting->id);
                if ($result) {
                    $this->markDeviceSynthesisFinishedIfReached(
                        $userId,
                        $personaId,
                        $deviceCode,
                        $today
                    );
                    // 已达发布数则无需再跑后续文案
                    $deviceRow = SvDevice::where('user_id', $userId)
                        ->where('persona_id', $personaId)
                        ->where('device_code', $deviceCode)
                        ->field('synthesis_m')
                        ->find();
                    if ($deviceRow && (int)$deviceRow->synthesis_m === 1) {
                        return;
                    }
                } else {
                    // 单条文案失败：清文案级缓存，继续下一条
                    Cache::store('material_redis')->delete($itemCacheKey);
                }
            } catch (\app\common\exception\MaterialNotReadyException $e) {
                Cache::store('material_redis')->delete($itemCacheKey);
                if ($cacheKey) {
                    Cache::store('material_redis')->delete($cacheKey);
                }
                Log::channel('explosionVideoSynthesis')->write(
                    '素材转码未就绪,本轮跳过待下一轮，id=' . $copywriting->id . '，' . $e->getMessage()
                );
                throw $e;
            } catch (\Throwable $e) {
                Cache::store('material_redis')->delete($itemCacheKey);
                Log::channel('explosionVideoSynthesis')->info(
                    '文案视频合成异常，id=' . $copywriting->id . '，错误：' . $e->getMessage() . '，继续下一条'
                );
                continue;
            }
        }

        // 本轮结束后仍未达发布数：清设备去重缓存，允许下轮继续补齐
        $generatedAfter = $this->getImitationSynthesisVideoCount($userId, $personaId, $deviceCode, $today);
        if ($generatedAfter < $publishCount && $cacheKey) {
            Cache::store('material_redis')->delete($cacheKey);
            Log::channel('explosionVideoSynthesis')->write('爆款仿写未达发布数量，保留待补齐：' . json_encode([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
                'day' => $today,
                'publish_count' => $publishCount,
                'generated_count' => $generatedAfter,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @deprecated 已降级：不再做文案全局遍历，改为只处理仿写规则设备
     *             保留方法签名供调试接口 mode=copywriting 复用
     */
    public function handleCopywritingLevel(?int $batchLimit = null): void
    {
        Log::channel('explosionVideoSynthesis')->write(
            'handleCopywritingLevel 已降级为设备维度仿写过滤，不再全局遍历文案表'
        );
        $this->handleDeviceLevel(AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE, $batchLimit);
    }

    /**
     * 按人设取可用仿写文案：手机模式优先当前设备，取不到再按人设兜底
     */
    private function getDeviceImitationCopywritings(
        int $userId,
        int $personaId,
        string $deviceCode,
        string $day,
        int $limit
    ): array {
        $limit = max(1, $limit);
        $isCloud = CloudSvDevice::isCloudCode($deviceCode);

        // 无手机模式没有"当前设备"这回事：爆款是按人设导入的，文案的 device_code 可能是
        // 导入当时绑着的手机、甚至是早就解绑的旧设备号，按 cloud:{id} 去筛一条也捞不到
        // （persona 196 实测：按 cloud 键 0 条，按人设 8 条）。所以直接按人设取。
        $rows = $this->queryImitationCopywritings($userId, $personaId, $day, $limit, $isCloud ? '' : $deviceCode);

        // 手机模式当前设备没文案时按人设兜底：手机坏了换一台、或解绑重绑之后，
        // 之前采集的文案还挂在旧设备号上，不兜底的话这些文案永远出不去，换机当天就断供。
        // 人设维度已由 persona_id 锁死，放开设备号不会串到别的人设。
        if (!$isCloud && $rows->isEmpty()) {
            $rows = $this->queryImitationCopywritings($userId, $personaId, $day, $limit, '');
            if (!$rows->isEmpty()) {
                Log::channel('explosionVideoSynthesis')->write('当前设备无可用仿写文案，按人设取旧设备遗留文案：' . json_encode([
                    'user_id' => $userId,
                    'persona_id' => $personaId,
                    'device_code' => $deviceCode,
                    'count' => count($rows),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        if ($rows->isEmpty()) {
            return [];
        }

        // 取到就把文案归到本次使用的设备号下：下游 CopywritingImitationLogic 全程用
        // $copywritingRecord->device_code 造任务，不归位的话成片会挂在旧设备号上——
        // 既算不进当前配额（永远补不齐、整夜重试），又会被别的设备的发布 cron 选中发出去。
        foreach ($rows as $row) {
            if ((string)$row->device_code === $deviceCode) {
                continue;
            }
            AiPersonaSynthesisCopywriting::where('id', (int)$row->id)->update([
                'device_code' => $deviceCode,
                'update_time' => time(),
            ]);
            $row->device_code = $deviceCode;
        }

        return $rows->all();
    }

    /**
     * 仿写文案查询：$deviceCode 传空串表示不限设备，只按人设取
     */
    private function queryImitationCopywritings(
        int $userId,
        int $personaId,
        string $day,
        int $limit,
        string $deviceCode
    ) {
        $query = AiPersonaSynthesisCopywriting::alias('a')
            ->field('a.*')
            ->join('sv_device_viral_record vr', 'vr.id = a.sv_device_viral_record_id')
            ->where('a.user_id', $userId)
            ->where('a.persona_id', $personaId)
            ->where('a.publish_media_type', 1)
            ->where('a.use_state', AiPersonaSynthesisCopywriting::USE_STATE_UNUSED)
            ->where('vr.is_interested', 1)
            ->where('vr.use_time', 0)
            ->where('a.day', '<=', $day);
        if ($deviceCode !== '') {
            $query->where('a.device_code', $deviceCode);
        }

        return $query->order(['a.status' => 'desc', 'a.id' => 'asc'])
            ->limit($limit)
            ->select();
    }

    private function getImitationSynthesisVideoCount(int $userId, int $personaId, string $deviceCode, string $day): int
    {
        $start = strtotime($day . ' 00:00:00');
        $end = strtotime($day . ' 23:59:59');
        if ($start === false || $end === false) {
            return 0;
        }

        // 含失败任务：失败也占用一个合成名额
        $videoTaskCount = (int)ShanjianVideoTask::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('device_code', $deviceCode)
            ->where('auto_type', 1)
            ->where('wechat_type', 0)
            ->where('copywriting_source', 'in', [
                AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE,
                AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI,
            ])
            ->whereBetween('create_time', [$start, $end])
            ->count();

        // MiniMax 自动任务建单时即落 status=-1 占位视频任务，会计入上方 $videoTaskCount。
        // 此处仅兼容旧数据：曾只建 MinimaxShanjianTask、尚未落 ShanjianVideoTask 的中间态。
        $minimaxPendingCount = (int)MinimaxShanjianTask::alias('m')
            ->join('shanjian_video_setting s', 's.id = m.shanjian_setting_id')
            ->leftJoin(
                'shanjian_video_task v',
                'v.video_setting_id = s.id AND v.delete_time IS NULL'
            )
            ->where('m.user_id', $userId)
            ->where('s.user_id', $userId)
            ->where('s.device_code', $deviceCode)
            ->where('s.auto_type', 1)
            ->whereNull('m.delete_time')
            ->whereNull('s.delete_time')
            ->whereNull('v.id')
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(s.request_json, '$.auto_pending_task.persona_id')) = ?",
                [$personaId]
            )
            ->whereBetween('m.create_time', [$start, $end])
            ->count();

        return $videoTaskCount + $minimaxPendingCount;
    }

    private function markDeviceSynthesisFinishedIfReached(
        int $userId,
        int $personaId,
        string $deviceCode,
        string $day,
        int $publishCount = 0,
        ?int $generatedCount = null
    ): void {
        if ($publishCount <= 0) {
            $publishCount = MarketingTemplateSchedule::getTodayPublishTaskCount($personaId);
        }
        if ($publishCount <= 0) {
            return;
        }

        if ($generatedCount === null) {
            $generatedCount = $this->getImitationSynthesisVideoCount($userId, $personaId, $deviceCode, $day);
        }
        if ($generatedCount < $publishCount) {
            return;
        }

        if (CloudSvDevice::isCloudCode($deviceCode)) {
            // 云端设备号在 sv_device 里没有行，标记要落人设表，否则这个人设整晚被反复挑中
            CloudSvDevice::markPersonaDone($personaId);
        } else {
            SvDevice::markSynthesisDoneWhere([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
            ], SvDevice::SYNTHESIS_SCENE_SOCIAL);
        }

        Log::channel('explosionVideoSynthesis')->write('爆款仿写合成数量已达发布数量，设备标记已合成：' . json_encode([
            'user_id' => $userId,
            'persona_id' => $personaId,
            'device_code' => $deviceCode,
            'day' => $day,
            'publish_count' => $publishCount,
            'generated_count' => $generatedCount,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 是否处于允许执行的时段（0:30 含，12:00 不含）
     */
    public static function isTimeInWindow(?float $currentTime = null): bool
    {
        if ($currentTime === null) {
            $currentTime = floatval(date('H')) + floatval(date('i')) / 60;
        }
        return $currentTime >= self::EXEC_START_TIME && $currentTime < self::EXEC_END_TIME;
    }

    private function isInExecTimeWindow(): bool
    {
        return self::isTimeInWindow();
    }

    private function hasNewsMixcutType(AiPersonaSynthesisConfig $config): bool
    {
        $generationTypes = $config->generation_types ?? [];
        if (!is_array($generationTypes)) {
            return false;
        }

        foreach ($generationTypes as $type) {
            if ((int)$type === 4) {
                return true;
            }
        }

        return false;
    }
}
