<?php


namespace app\common\command;

use app\api\logic\aiPersona\SynthesisConfigLogic;
use app\api\logic\videoSynthesis\CopywritingAiGenerationLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoTask;
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
                print_r("\n 0:30-07:30内，任务跳过\n");
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

            // 统一设备维度：仿写(1) / AI直接生成(2) / 无需文案(3) / 文案库(4)
            $this->handleDeviceLevel();

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
            ->where('p.status', 1)//人设开启
            ->where('p.publish_mode', 1) // 仅制作视频（非成品库直发）
            // ->where('d.is_first', 0)
            ->where('d.persona_id', '>', 0);
        if ($sourceFilter !== null) {
            $query->join(
                'ai_persona_synthesis_config c',
                'c.persona_id = d.persona_id AND c.user_id = d.user_id'
            )->where('c.copywriting_source', $sourceFilter);
        }
        $devices = $query
            ->order(['d.synthesis_m_retry_count' => 'asc', 'd.id' => 'asc'])
            ->limit($limit)
            ->select();
        Log::channel('ipVideoSynthesis')->write('设备维度处理：' . count($devices) . '条');
        Log::channel('ipVideoSynthesis')->write(json_encode($devices->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        foreach ($devices as $device) {
            $cacheKey = 'command_video_synthesis_' . $device->device_code;
            try {
                if (!AiPersonaOptionService::isEnabledForPersonaId((int)$device->persona_id, 'video_clip')) {
                    Log::channel('ipVideoSynthesis')->write('global_option.video_clip=0，跳过设备视频合成：' . $device->device_code);
                    SvDevice::where('device_code', $device->device_code)->update(['synthesis_m' => 1]);
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
                $retryCount = (int)$device->synthesis_m_retry_count;
                $claimed = SvDevice::where('id', (int)$device->id)
                    ->where('synthesis_m', 0)
                    ->where('synthesis_m_retry_count', $retryCount)
                    ->inc('synthesis_m_retry_count')
                    ->update();
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
     * 爆款仿写：仅按设备当前绑定的 persona_id + device_code 取文案
     * 注：public 以便调试接口直接复用
     */
    public function processImitationForDevice($device, AiPersonaSynthesisConfig $config, ?string $cacheKey = null): void
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

        $publishCount = MarketingTemplateSchedule::getTodayPublishTaskCount($personaId);
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
     * 按设备当前绑定人设取可用仿写文案（device_code + persona_id + user_id）
     */
    private function getDeviceImitationCopywritings(
        int $userId,
        int $personaId,
        string $deviceCode,
        string $day,
        int $limit
    ): array {
        $limit = max(1, $limit);
        // 只认当前设备+当前人设，避免捞到改绑前或其他人设的文案
        $rows = AiPersonaSynthesisCopywriting::alias('a')
            ->field('a.*')
            ->join('sv_device_viral_record vr', 'vr.id = a.sv_device_viral_record_id')
            ->where('a.user_id', $userId)
            ->where('a.persona_id', $personaId)
            ->where('a.device_code', $deviceCode)
            ->where('a.publish_media_type', 1)
            ->where('a.use_state', AiPersonaSynthesisCopywriting::USE_STATE_UNUSED)
            ->where('vr.is_interested', 1)
            ->where('vr.use_time', 0)
            ->where('a.day', '<=', $day)
            ->order(['a.status' => 'desc', 'a.id' => 'asc'])
            ->limit($limit)
            ->select();

        return $rows->isEmpty() ? [] : $rows->all();
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

        SvDevice::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('device_code', $deviceCode)
            ->update(['synthesis_m' => 1]);

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
     * 是否处于允许执行的时段
     */
    private function isInExecTimeWindow(): bool
    {
        $currentTime = floatval(date('H')) + floatval(date('i')) / 60;
        return $currentTime >= self::EXEC_START_TIME && $currentTime < self::EXEC_END_TIME;
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
