<?php

namespace app\common\service\hotspot;

use app\api\logic\ApiLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\Jobs\HotspotVideoDispatchJob;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\hotspot\HotspotTask;
use app\common\model\human\HumanVoice;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\aiPersona\MaterialUsageService;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use app\common\service\FileService;
use app\common\service\OriginalMaterialSpec;
use app\common\service\square\SquareAssetService;
use think\facade\Queue;

class VideoService
{
    public const SOURCE = 'hotspot';
    public const NAME_PREFIX = '热点追踪-';
    public const SETTING_NAME_MAX = 50;
    public const TASK_NAME_MAX = 200;
    public const DISPATCH_LOCK_TTL = 480;
    /**
     * 抓取预算常量统一由 CopywritingImitationLogic 维护（热点、设备/微信、爆炸视频三条链路共用一份口径），
     * 这里只做别名，禁止在热点侧另写数值。
     */
    public const AI_MATERIAL_BUDGET = [
        'max_elapsed_sec' => CopywritingImitationLogic::AI_MATERIAL_FETCH_BUDGET_MIN,
        'max_candidates_per_keyword' => CopywritingImitationLogic::AI_MATERIAL_MAX_CANDIDATES_PER_KEYWORD,
        'max_transcode_fail' => CopywritingImitationLogic::AI_MATERIAL_MAX_TRANSCODE_FAIL,
        'max_transcode_sec' => CopywritingImitationLogic::TRANSCODE_VIDEO_SEC,
    ];
    public const AI_MATERIAL_KEYWORD_BUDGET_SEC = CopywritingImitationLogic::AI_MATERIAL_KEYWORD_BUDGET_SEC;

    /**
     * 抓取阶段墙钟（不含抽词）。clips 大缺口 90s，digital/小缺口 60s。
     */
    public static function aiMaterialFetchBudgetSec(int $needV, int $needI): int
    {
        return CopywritingImitationLogic::aiMaterialFetchBudgetSec($needV, $needI);
    }

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
        self::$testHooks['getAiMaterialsCalls'] = self::$testHooks['getAiMaterialsCalls'] ?? [];
        self::$testHooks['retryFailedGenerateCalls'] = self::$testHooks['retryFailedGenerateCalls'] ?? [];
        self::$testHooks['createAudioTaskCalls'] = self::$testHooks['createAudioTaskCalls'] ?? [];
        self::$testHooks['enqueueCalls'] = self::$testHooks['enqueueCalls'] ?? [];
        self::$testHooks['stamped'] = self::$testHooks['stamped'] ?? [];
        self::$testHooks['refreshedCreateTime'] = self::$testHooks['refreshedCreateTime'] ?? [];
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    private static function recordHook(string $key, mixed $value): void
    {
        if (self::$testHooks === []) {
            return;
        }
        if (!isset(self::$testHooks[$key]) || !is_array(self::$testHooks[$key])) {
            self::$testHooks[$key] = [];
        }
        self::$testHooks[$key][] = $value;
    }

    public static function enqueue(array $task, int $userId): void
    {
        $taskNo = (string)($task['id'] ?? '');
        if (!empty(self::$testHooks['skipEnqueue'])) {
            self::$testHooks['enqueueCalls'][] = ['task_no' => $taskNo, 'user_id' => $userId];
            return;
        }
        try {
            Queue::push(HotspotVideoDispatchJob::class, [
                'task_no' => $taskNo,
                'user_id' => $userId,
            ]);
            HotspotLog::write('热点下发已入队：任务号=' . $taskNo);
        } catch (\Throwable $e) {
            HotspotLog::write('热点下发入队失败，等待定时补偿：任务号=' . $taskNo . ' 原因=' . $e->getMessage());
        }
    }

    /**
     * 失败重试：优先在原闪剪单上重入队并复用已有 Turbo 音频；原单不在才新建。
     */
    public static function retryOrEnqueue(array $task, int $userId): array
    {
        $action = self::resumeFailedShanjian($task, $userId);
        if ($action !== 'reused') {
            self::enqueue($task, $userId);
            return $task;
        }
        $fresh = TaskService::detail((string)($task['id'] ?? ''), $userId);
        return $fresh ?? $task;
    }

    /**
     * @return 'reused'|'need_dispatch'
     */
    public static function resumeFailedShanjian(array $task, int $userId): string
    {
        $taskNo = (string)($task['id'] ?? '');
        $options = is_array($task['options'] ?? null) ? $task['options'] : [];
        $retrySeq = (int)($options['retry_seq'] ?? 0);
        $sjId = TaskService::resolveBoundVideoTaskId($task);
        if ($sjId <= 0) {
            $settingId = (int)($options['shanjian_setting_id'] ?? 0);
            if ($settingId > 0 && ScriptService::shouldRedispatchEmptyPersonaMaterials(
                $options,
                self::settingMaterialById($settingId)
            )) {
                HotspotLog::write(sprintf(
                    '原闪剪任务缺失且设置素材为空，回退新建：任务号=%s 设置id=%d',
                    $taskNo,
                    $settingId
                ));
                TaskService::clearShanjianBinding($taskNo, $retrySeq);
            } else {
                HotspotLog::write('原单不存在，回退新建：任务号=' . $taskNo);
            }
            return 'need_dispatch';
        }

        $sjTask = self::lookupShanjianTask($sjId);
        if ($sjTask === null || (method_exists($sjTask, 'isEmpty') && $sjTask->isEmpty())) {
            HotspotLog::write(sprintf('原闪剪单已删除，回退新建：任务号=%s 闪剪id=%d', $taskNo, $sjId));
            TaskService::clearShanjianBinding($taskNo, $retrySeq);
            return 'need_dispatch';
        }

        $settingMaterial = self::settingMaterialPayload($sjTask);
        if (ScriptService::shouldRedispatchEmptyPersonaMaterials($options, $settingMaterial)) {
            HotspotLog::write(sprintf(
                '原闪剪单素材为空但任务已勾选人设素材，回退新建：任务号=%s 闪剪id=%d',
                $taskNo,
                $sjId
            ));
            TaskService::clearShanjianBinding($taskNo, $retrySeq);
            return 'need_dispatch';
        }

        self::recoverMinimaxAudioIfNeeded($sjTask);
        $status = (int)($sjTask->status ?? 0);
        // 仅真正重新提交合成(原单 FAILED→重试)时才刷新 create_time;
        // 原单仍在 PENDING/PROCESSING 时不得刷新,否则会把闪剪 2h 状态轮询补偿与 24h 超时退费计时一并推迟
        $resubmitted = false;
        if ($status === ShanjianVideoTask::STATUS_FAILED) {
            $ok = self::callRetryFailedGenerate($sjId, $userId);
            $resubmitted = $ok;
            if (!$ok) {
                $err = ShanjianVideoTaskLogic::getError() ?: '复用原闪剪单失败';
                HotspotLog::write(sprintf('复用原闪剪单失败，回退新建：任务号=%s 原因=%s', $taskNo, $err));
                TaskService::clearShanjianBinding($taskNo, $retrySeq);
                return 'need_dispatch';
            }
        } elseif ($status === ShanjianVideoTask::STATUS_SUCCESS) {
            if (empty(self::$testHooks['skipPersist'])) {
                self::syncFromShanjianTask($sjTask);
            }
            TaskService::patchOptions($taskNo, ['dispatch_status' => 'done'], $retrySeq);
            return 'reused';
        }

        $fresh = self::lookupShanjianTask($sjId);
        if ($fresh !== null && !(method_exists($fresh, 'isEmpty') && $fresh->isEmpty())) {
            self::stampShanjianTask(
                $fresh,
                $taskNo,
                (int)($fresh->persona_id ?? ($task['persona']['id'] ?? 0)),
                (string)($task['title'] ?? ''),
                $retrySeq
            );
            if ($resubmitted) {
                self::refreshShanjianCreateTime($fresh, $taskNo);
            }
        }
        TaskService::patchOptions($taskNo, ['dispatch_status' => 'done'], $retrySeq);
        HotspotLog::write(sprintf(
            '已在原失败视频上重新合成：任务号=%s 闪剪任务id=%d 重试序号=%d',
            $taskNo,
            $sjId,
            $retrySeq
        ));
        return 'reused';
    }

    public static function shouldCreateMinimaxAudio(string $audioUrl): bool
    {
        return trim($audioUrl) === '';
    }

    public static function firstMinimaxResultUrl(mixed $results): string
    {
        if (is_object($results) && isset($results->results)) {
            $results = $results->results;
        }
        if (is_string($results) && $results !== '') {
            $decoded = json_decode($results, true);
            $results = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($results)) {
            return '';
        }
        foreach ($results as $item) {
            if (is_array($item)) {
                $url = trim((string)($item['url'] ?? $item['audio_url'] ?? ''));
                if ($url !== '') {
                    return $url;
                }
                continue;
            }
            if (is_string($item) && trim($item) !== '') {
                return trim($item);
            }
        }
        return '';
    }

    private static function lookupShanjianTask(int $sjId): mixed
    {
        if (array_key_exists('findShanjianTask', self::$testHooks)) {
            $hook = self::$testHooks['findShanjianTask'];
            return is_callable($hook) ? $hook($sjId) : $hook;
        }
        return ShanjianVideoTask::where('id', $sjId)->findOrEmpty();
    }

    private static function settingMaterialById(int $settingId): array
    {
        if ($settingId <= 0) {
            return [];
        }
        $setting = self::lookupShanjianSetting($settingId);
        if ($setting === null || $setting === false
            || (method_exists($setting, 'isEmpty') && $setting->isEmpty())
        ) {
            return [];
        }
        $raw = $setting->material ?? [];
        return is_array($raw) ? $raw : self::parseJsonArray($raw);
    }

    private static function settingMaterialPayload(mixed $sjTask): array
    {
        return self::settingMaterialById((int)($sjTask->video_setting_id ?? 0));
    }

    private static function lookupShanjianSetting(int $settingId): mixed
    {
        if (array_key_exists('findShanjianSetting', self::$testHooks)) {
            $hook = self::$testHooks['findShanjianSetting'];
            return is_callable($hook) ? $hook($settingId) : $hook;
        }
        if (!empty(self::$testHooks['skipPersist'])) {
            $stub = new \stdClass();
            $stub->id = $settingId;
            $stub->create_time = (int)(self::$testHooks['settingCreateTime'] ?? 0);
            return $stub;
        }
        return ShanjianVideoSetting::where('id', $settingId)->find();
    }

    private static function rawCreateTime(mixed $row): int
    {
        if (!is_object($row)) {
            return 0;
        }
        $raw = null;
        if (method_exists($row, 'getData')) {
            try {
                $raw = $row->getData('create_time');
            } catch (\Throwable) {
                $raw = null;
            }
        }
        if ($raw === null && isset($row->create_time)) {
            $raw = $row->create_time;
        }
        if (is_numeric($raw)) {
            return (int)$raw;
        }
        if (is_string($raw) && $raw !== '') {
            $ts = strtotime($raw);
            return $ts !== false ? $ts : 0;
        }
        return 0;
    }

    private static function formatCreateTimeLog(int $ts): string
    {
        if ($ts <= 0) {
            return '-';
        }
        return $ts . '(' . date('Y-m-d H:i:s', $ts) . ')';
    }

    /**
     * 复用原闪剪单重试时，把 task/setting 的 create_time 刷成当前时间，便于按创建时间置顶。
     */
    private static function refreshShanjianCreateTime(object $sjTask, string $taskNo): void
    {
        $taskId = (int)($sjTask->id ?? 0);
        $settingId = (int)($sjTask->video_setting_id ?? 0);
        $now = time();
        $oldTask = self::rawCreateTime($sjTask);
        $settingSkipped = $settingId <= 0;
        $oldSetting = 0;
        if (!$settingSkipped) {
            $setting = self::lookupShanjianSetting($settingId);
            if ($setting === null || $setting === false || (method_exists($setting, 'isEmpty') && $setting->isEmpty())) {
                $settingSkipped = true;
            } else {
                $oldSetting = self::rawCreateTime($setting);
            }
        }

        self::recordHook('refreshedCreateTime', [
            'task_id' => $taskId,
            'setting_id' => $settingId,
            'now' => $now,
            'old_task_create_time' => $oldTask,
            'old_setting_create_time' => $oldSetting,
            'setting_skipped' => $settingSkipped,
        ]);

        if (empty(self::$testHooks['skipPersist'])) {
            try {
                if ($taskId > 0) {
                    ShanjianVideoTask::where('id', $taskId)->update([
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                }
                if (!$settingSkipped) {
                    ShanjianVideoSetting::where('id', $settingId)->update([
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                }
            } catch (\Throwable $e) {
                HotspotLog::exception('重试刷新闪剪创建时间失败', $e);
            }
        }

        $settingPart = $settingSkipped
            ? 'setting.create_time 旧=- 新=- setting跳过=是'
            : sprintf(
                'setting.create_time 旧=%s 新=%s',
                self::formatCreateTimeLog($oldSetting),
                self::formatCreateTimeLog($now)
            );
        HotspotLog::write(sprintf(
            '重试刷新闪剪创建时间：任务号=%s 闪剪任务id=%d 设置id=%d task.create_time 旧=%s 新=%s %s',
            $taskNo,
            $taskId,
            $settingId,
            self::formatCreateTimeLog($oldTask),
            self::formatCreateTimeLog($now),
            $settingPart
        ));
    }

    private static function callRetryFailedGenerate(int $sjId, int $userId): bool
    {
        self::recordHook('retryFailedGenerateCalls', ['id' => $sjId, 'user_id' => $userId]);
        if (array_key_exists('retryFailedGenerate', self::$testHooks)) {
            $hook = self::$testHooks['retryFailedGenerate'];
            return is_callable($hook) ? (bool)$hook($sjId, $userId) : (bool)$hook;
        }
        ShanjianVideoTaskLogic::$uid = $userId;
        return ShanjianVideoTaskLogic::retryFailedGenerate($sjId, true);
    }

    private static function recoverMinimaxAudioIfNeeded(object $sjTask): void
    {
        $audioUrl = trim((string)($sjTask->audio_url ?? ''));
        if ($audioUrl !== '') {
            return;
        }
        $minimaxTaskId = (int)($sjTask->minimax_task_id ?? 0);
        if ($minimaxTaskId <= 0) {
            return;
        }
        if (array_key_exists('minimaxResults', self::$testHooks)) {
            $url = self::firstMinimaxResultUrl(self::$testHooks['minimaxResults']);
        } else {
            $mm = MinimaxShanjianTask::where('id', $minimaxTaskId)->findOrEmpty();
            if ($mm->isEmpty()) {
                return;
            }
            $url = self::firstMinimaxResultUrl($mm);
        }
        if ($url === '') {
            return;
        }
        $sjTask->audio_url = $url;
        $sjTask->audio_type = 2;
        $sjTask->save();
        HotspotLog::write('已回填原MiniMax音频，跳过重复扣费：闪剪任务id=' . (int)$sjTask->id);
    }

    public static function dispatchPending(int $limit = 2): int
    {
        $done = 0;
        foreach (TaskService::listPendingDispatch($limit, self::DISPATCH_LOCK_TTL) as $item) {
            $taskNo = (string)($item['id'] ?? '');
            $userId = (int)($item['user_id'] ?? 0);
            if ($taskNo === '') {
                continue;
            }
            $result = self::dispatchOne($taskNo, $userId);
            if ($result !== null) {
                $done++;
            }
        }
        return $done;
    }

    public static function dispatchOne(string $taskNo, int $userId = 0): ?array
    {
        $claimed = TaskService::claimDispatch($taskNo, self::DISPATCH_LOCK_TTL);
        if ($claimed === null) {
            return null;
        }
        $uid = $userId > 0 ? $userId : (int)($claimed['user_id'] ?? 0);
        $task = TaskService::detail($taskNo, $uid);
        if ($task === null) {
            HotspotLog::write('热点任务认领后已删除：任务号=' . $taskNo);
            return null;
        }
        // 认领时的重试序号：所有状态/options 回写都带守卫，用户发起新一轮重试后过期协程不得覆写
        $seq = (int)($task['options']['retry_seq'] ?? 0);
        try {
            $recovered = self::bindExistingShanjian($task, $uid);
            if ($recovered !== null) {
                $patched = TaskService::patchOptions($taskNo, [
                    'dispatch_status' => 'done',
                ], $seq);
                return $patched ?? $recovered;
            }
            $result = self::dispatch($task, $uid);
            $patched = TaskService::patchOptions($taskNo, [
                'dispatch_status' => 'done',
            ], $seq);
            return $patched ?? $result;
        } catch (HotspotUpstreamException $e) {
            $recovered = self::bindExistingShanjian($task, $uid);
            if ($recovered !== null) {
                HotspotLog::write('下发异常但已有闪剪订单，仅回写绑定：任务号=' . $taskNo);
                $patched = TaskService::patchOptions($taskNo, [
                    'dispatch_status' => 'done',
                ], $seq);
                return $patched ?? $recovered;
            }
            TaskService::markFailed($taskNo, $e->getMessage(), $seq, ['dispatch_status' => 'fail']);
            HotspotLog::write('热点后台下发失败：任务号=' . $taskNo . ' 原因=' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            $recovered = self::bindExistingShanjian($task, $uid);
            if ($recovered !== null) {
                HotspotLog::write('下发异常但已有闪剪订单，仅回写绑定：任务号=' . $taskNo);
                $patched = TaskService::patchOptions($taskNo, [
                    'dispatch_status' => 'done',
                ], $seq);
                return $patched ?? $recovered;
            }
            TaskService::markFailed($taskNo, '服务异常，请稍后再试', $seq, ['dispatch_status' => 'fail']);
            HotspotLog::exception('热点后台下发异常：任务号=' . $taskNo, $e);
            return null;
        }
    }

    public static function dispatch(array $task, int $userId): array
    {
        $taskNo = (string)($task['id'] ?? '');
        $script = trim((string)($task['script'] ?? ''));
        if ($script === '') {
            throw new HotspotUpstreamException('口播文案不能为空');
        }

        $options = is_array($task['options'] ?? null) ? $task['options'] : [];
        $opt = ScriptService::normalizeOptions($options);
        $title = trim((string)($task['title'] ?? ''));
        if ($title === '') {
            $title = (string)($task['topic'] ?? '热点视频');
        }
        $scriptStripped = ScriptService::stripLeadingTitle($script, $title);
        if ($scriptStripped !== $script) {
            if ($taskNo !== '') {
                TaskService::saveScript($taskNo, $scriptStripped);
            }
            $script = $scriptStripped;
        }
        if ($opt['video_type'] === '') {
            throw new HotspotUpstreamException('请选择视频类型');
        }

        $recovered = self::bindExistingShanjian($task, $userId);
        if ($recovered !== null) {
            HotspotLog::write('下发前再次命中已有闪剪订单，跳过创建：任务号=' . $taskNo);
            return $recovered;
        }

        $manualMode = (int)($opt['persona_mode'] ?? ScriptService::PERSONA_MODE_PERSONA)
            === ScriptService::PERSONA_MODE_MANUAL;

        $persona = [];
        $personaId = 0;
        if (!$manualMode) {
            $persona = is_array($task['persona'] ?? null) ? $task['persona'] : [];
            if (PersonaService::hasIdentity($persona)) {
                $persona = PersonaService::resolve($userId, $persona);
            }
            $personaId = (int)($persona['id'] ?? 0);
            if ($personaId <= 0) {
                throw new HotspotUpstreamException('人设不能为空');
            }
        }

        $avatar = [];
        $voiceId = '';
        $modelVersion = 0;
        $pic = '';
        $manualVoiceName = '';
        if ($manualMode) {
            // 不选人设：形象与音色互不捆绑，各取用户自选项（与爆款复刻洗稿模式同源）
            if ($opt['video_type'] === 'digital') {
                $avatar = ManualAssetService::resolveAvatar($userId, (int)($opt['avatar_id'] ?? 0));
                $pic = (string)$avatar['pic'];
            }
            // 形象原音要靠所选形象才能解析出 speakerId，把 avatar_id 一并传下去
            $voice = ManualAssetService::resolveVoice(
                $userId,
                (int)($opt['voice_id'] ?? 0),
                (int)($opt['avatar_id'] ?? 0)
            );
            $voiceId = (string)$voice['voice_id'];
            $manualVoiceName = (string)$voice['name'];
        } elseif ($opt['video_type'] === 'digital') {
            $avatar = self::resolveAvatar($userId, $personaId, $opt);
            $voiceId = (string)$avatar['voice_id'];
            $pic = (string)$avatar['pic'];
        } else {
            $voiceId = self::resolveClipVoice($userId, $personaId);
        }
        $modelVersion = self::resolveVoiceModelVersion($voiceId, $userId);

        $opt['platform'] = (string)($task['platform'] ?? '');
        $synthesisConfig = self::resolveSynthesisConfig($userId, $personaId);
        $generationType = self::generationTypeByVideoType($opt['video_type']);
        // 语速先于素材评估算好：speed_ratio<1 会拉长实际口播时长，素材充足性必须按折算后的时长判
        $speedRatio = $manualMode
            ? AiPersonaSynthesisConfig::normalizeSpeechRate($opt['speech_rate'] ?? null)
            : self::resolveSpeechRate($synthesisConfig, $generationType);
        $materials = self::resolveMaterials($userId, $personaId, $opt, $script, $speedRatio, $taskNo);
        if ($opt['video_type'] === 'clips' && $materials === []) {
            throw new HotspotUpstreamException('素材混剪必须选择素材');
        }
        $materialIds = self::collectMaterialIds($materials, 'persona');
        $svMaterialIds = self::collectMaterialIds($materials, 'sv_media');
        $payloadMaterials = self::stripMaterialIds($materials);

        // 不选人设没有人设合成配置可依：背景音乐用高级设置里自传的曲子，没传就系统随机
        $musicUrl = $manualMode
            ? ManualAssetService::resolveMusicUrl(
                (string)($opt['music_url'] ?? ''),
                (string)($opt['system_music_id'] ?? ''),
                (int)($opt['music_source'] ?? ManualAssetService::MUSIC_SOURCE_SYSTEM)
            )
            : self::resolveMusicUrl($userId, $personaId, $synthesisConfig);
        // 人设模式跟随人设合成配置；不选人设没有配置可依，用高级设置里用户自选的值
        // （音量 0 由下游 buildBackgroundMusicRule 判成 audioSwitch=false，即静音）
        $volume = $manualMode
            ? AiPersonaSynthesisConfig::normalizeMusicVolume($opt['music_volume'] ?? null)
            : self::resolveVolume($synthesisConfig);
        $templateConfig = $synthesisConfig->isEmpty() ? [] : ($synthesisConfig->template_config ?? []);
        // 不选人设时用户可直接指定剪辑模板；没指定（或指定的已下架）仍按自动随机走
        $clipId = $manualMode
            ? ManualAssetService::resolveClipTemplateId(
                (string)($opt['clip_template_id'] ?? ''),
                (string)$opt['video_type']
            )
            : '';
        if ($clipId === '') {
            try {
                $clipId = SynthesisTemplateConfigService::pickTemplateId($templateConfig, $generationType, 1);
            } catch (\Throwable $e) {
                throw new HotspotUpstreamException($e->getMessage() ?: '视频模板选取失败');
            }
        }
        $retrySeq = (int)($options['retry_seq'] ?? 0);
        $extra = [
            'video_count' => 1,
            'volume' => $volume,
            // 口播语速：下游 ShanjianVideoTaskLogic::appendSpeakerSpeedRatio 只认 extra.speed_ratio，
            // 不传就恒为 1.0，人设里设的语速形同虚设（与 IP 合成/自动化同口径）
            'speed_ratio' => $speedRatio,
            'source' => self::SOURCE,
            'create_type' => self::SOURCE,
            'hotspot_task_no' => $taskNo,
            'retry_seq' => $retrySeq,
            // 背景音乐来源必须显式给：人设配「无背景音乐」时 resolveMusicUrl 返回空串，
            // 交给 normalizeAdvancedSettings 猜就会按「没有自定义音乐」判成系统内置，
            // 下发 backgroundMusic.audioSwitch=true 让闪剪自己配一首，人设的设置等于白设。
            // 有 URL 的两档（系统随机曲 / 人设音乐库）都已在 resolveMusicUrl 里取定，按自定义下发。
            'music_source' => $musicUrl !== ''
                ? ShanjianVideoSettingLogic::MUSIC_SOURCE_CUSTOM
                : ShanjianVideoSettingLogic::MUSIC_SOURCE_NONE,
        ];
        // 素材时长缺口透出：后台可据此筛「画面循环压力大」的成片
        if ((float)(self::$lastDurationGate['gap_sec'] ?? 0) > 0) {
            $extra['material_gap_sec'] = (float)self::$lastDurationGate['gap_sec'];
        }
        if (!$manualMode && $personaId > 0) {
            $extra['material_use_mode'] = ScriptService::resolveFrozenMaterialUseMode($opt, $personaId);
        }

        $settingName = self::composeRecordName($title, self::SETTING_NAME_MAX);
        $displayTitle = self::composeDisplayTitle($title, self::TASK_NAME_MAX);
        $params = [
            'user_id' => $userId,
            'name' => $settingName,
            'pic' => $pic,
            'shanjian_type' => $opt['video_type'] === 'digital' ? 1 : 3,
            'auto_type' => 0,
            'copywriting' => [[
                'title' => $displayTitle,
                'content' => $script,
            ]],
            'extra' => $extra,
            'music' => $musicUrl !== '' ? [$musicUrl] : [],
            'clip' => SynthesisTemplateConfigService::toClipPayload($clipId),
        ];

        if ($opt['video_type'] === 'digital') {
            $params['anchor'] = [[
                'anchor_id' => $avatar['anchor_id'],
                'pic' => $avatar['pic'],
            ]];
            $params['voice'] = [['voice_id' => $voiceId]];
            // 不选人设时没有人设档案，人物卡留空：ShanjianVideoSettingLogic::add 只校验数组非空，
            // 下游 compositeVideoCron 对 card_name/card_introduced 分别判空，两个都空就不会拼 introduceCard，
            // 效果与洗稿模式不传 introduceCard 一致（整段不传会被「人设信息不能为空」拦下）
            $params['character_design'] = [$manualMode
                ? ['name' => '', 'introduced' => '']
                : [
                    'name' => (string)($persona['name'] ?? ''),
                    'introduced' => (string)(($persona['business'] ?? '') !== '' ? $persona['business'] : ($persona['tone'] ?? '')),
                ]];
            $params['material'] = $payloadMaterials;
        } else {
            $params['voice'] = $voiceId;
            $params['material'] = [$payloadMaterials];
            if ($modelVersion > 0) {
                $params['model_version'] = $modelVersion;
            }
        }

        HotspotLog::json('视频合成参数：', [
            'task_no' => $taskNo,
            'user_id' => $userId,
            'persona_mode' => $manualMode ? '不选人设' : '人设',
            'persona_id' => $personaId,
            'persona_name' => (string)($persona['name'] ?? ''),
            'video_type' => $opt['video_type'],
            'shanjian_type' => $params['shanjian_type'],
            'material_mode' => $opt['material_mode'],
            'material_use_mode' => (int)($extra['material_use_mode'] ?? 0),
            'goal' => $opt['goal'],
            'direction' => $opt['direction'],
            'duration_sec' => $opt['duration_sec'],
            'title' => $title,
            'script_len' => mb_strlen($script),
            'avatar_id' => (int)($avatar['id'] ?? ($opt['avatar_id'] ?? 0)),
            'avatar' => (string)($avatar['name'] ?? ($opt['avatar'] ?? '')),
            'anchor_id' => (string)($avatar['anchor_id'] ?? ''),
            'voice_id' => $voiceId,
            'model_version' => $modelVersion,
            'pic' => $pic,
            'music_url' => $musicUrl,
            'clip_id' => $clipId,
            'generation_type' => $generationType,
            'template_mode' => SynthesisTemplateConfigService::templateMode($templateConfig, $generationType),
            'material_count' => count($materials),
            'materials' => self::summarizeMaterials($materials),
            'duration_gate' => self::$lastDurationGate,
            'extra' => $extra,
            'setting_name' => $params['name'],
        ], 2500);

        ApiLogic::$uid = $userId;
        ShanjianVideoSettingLogic::$uid = $userId;
        $ok = $opt['video_type'] === 'digital'
            ? ShanjianVideoSettingLogic::add($params)
            : ShanjianVideoSettingLogic::addType3($params);
        if (!$ok) {
            throw new HotspotUpstreamException(ShanjianVideoSettingLogic::getError() ?: '视频合成任务创建失败');
        }

        $setting = ShanjianVideoSettingLogic::getReturnData();
        $settingId = (int)($setting['id'] ?? 0);
        if ($settingId <= 0) {
            throw new HotspotUpstreamException('视频合成任务创建失败');
        }

        $sjTask = ShanjianVideoTask::where('video_setting_id', $settingId)
            ->order('id', 'asc')
            ->findOrEmpty();
        $sjTaskId = 0;
        if (!$sjTask->isEmpty()) {
            self::stampShanjianTask($sjTask, $taskNo, $personaId, $title, $retrySeq);
            $sjTaskId = (int)$sjTask->id;
        } else {
            HotspotLog::write(sprintf(
                '闪剪设置已创建、子任务待音频回填：任务号=%s 设置id=%d',
                $taskNo,
                $settingId
            ));
        }

        $patch = [
            'avatar_id' => (int)($avatar['id'] ?? ($opt['avatar_id'] ?? 0)),
            'avatar' => (string)($avatar['name'] ?? ($opt['avatar'] ?? '')),
            'shanjian_setting_id' => $settingId,
            'shanjian_task_id' => $sjTaskId,
            'source' => self::SOURCE,
        ];
        // 音色名回写：详情/后台展示不依赖客户端有没有传 voice，与 avatar 名回写同口径
        if ($manualMode && $manualVoiceName !== '') {
            $patch['voice'] = $manualVoiceName;
        }
        $logPatch = self::persistMaterialUseLogs($userId, $personaId, $sjTaskId, $materialIds, $taskNo);
        $patch = array_merge($patch, $logPatch);
        if ($svMaterialIds !== []) {
            $patch['sv_material_ids'] = $svMaterialIds;
        }
        // 素材时长收口结果随绑定回写：缺口>0 才记，任务详情/后台列表据此透出「画面重复使用」风险
        if ((float)(self::$lastDurationGate['gap_sec'] ?? 0) > 0) {
            $patch['material_gate'] = self::$lastDurationGate;
        }
        $bound = TaskService::bindShanjian($taskNo, $patch);
        HotspotLog::write(sprintf(
            '热点视频已下发闪剪：任务号=%s 类型=%s 设置id=%d 闪剪任务id=%d 素材数=%d',
            $taskNo,
            $opt['video_type'],
            $settingId,
            $sjTaskId,
            count($materials)
        ));
        HotspotLog::json('闪剪设置表写入：', [
            'table' => 'shanjian_video_setting',
            'id' => $settingId,
            'name' => (string)($setting['name'] ?? $params['name']),
            'user_id' => $userId,
            'shanjian_type' => (int)($setting['shanjian_type'] ?? $params['shanjian_type']),
            'auto_type' => (int)($setting['auto_type'] ?? 0),
            'status' => $setting['status'] ?? null,
            'video_count' => $setting['video_count'] ?? null,
            'extra' => $extra,
        ], 1500);
        if ($sjTaskId > 0) {
            HotspotLog::json('闪剪任务表写入：', [
                'table' => 'shanjian_video_task',
                'id' => $sjTaskId,
                'task_id' => (string)($sjTask->task_id ?? ''),
                'video_setting_id' => $settingId,
                'shanjian_type' => (int)($sjTask->shanjian_type ?? $params['shanjian_type']),
                'status' => (int)($sjTask->status ?? 0),
                'anchor_id' => (string)($sjTask->anchor_id ?? ''),
                'voice_id' => (string)($sjTask->voice_id ?? ''),
                'title' => (string)($sjTask->title ?? ''),
                'msg_len' => mb_strlen((string)($sjTask->msg ?? '')),
                'clip_id' => (string)($sjTask->clip_id ?? ''),
                'music_url' => (string)($sjTask->music_url ?? ''),
                'persona_id' => (int)($sjTask->persona_id ?? 0),
                'extra' => self::taskExtra($sjTask),
            ], 2000);
        }
        return $bound ?? $task;
    }

    public static function syncFromShanjianTask(mixed $sjTask): void
    {
        try {
            $extra = self::taskExtra($sjTask);
            if (($extra['source'] ?? '') !== self::SOURCE) {
                return;
            }
            $taskNo = trim((string)($extra['hotspot_task_no'] ?? ''));
            if (!TaskService::isValidTaskNo($taskNo)) {
                return;
            }

            $current = TaskService::detail($taskNo);
            if ($current === null) {
                return;
            }
            $callbackSeq = (int)($extra['retry_seq'] ?? 0);
            $currentSeq = (int)(($current['options']['retry_seq'] ?? 0));
            if ($callbackSeq !== $currentSeq) {
                HotspotLog::write(sprintf(
                    '忽略过期闪剪回写：任务号=%s 回调序号=%d 当前序号=%d',
                    $taskNo,
                    $callbackSeq,
                    $currentSeq
                ));
                return;
            }

            $status = (int)($sjTask->status ?? 0);
            $retrySeq = $currentSeq;
            $sjTaskId = (int)($sjTask->id ?? 0);
            if ($sjTaskId > 0) {
                TaskService::bindShanjian($taskNo, [
                    'shanjian_task_id' => $sjTaskId,
                ]);
            }
            if ($status === ShanjianVideoTask::STATUS_SUCCESS) {
                if (!self::isShanjianResultReady($sjTask)) {
                    HotspotLog::write(sprintf(
                        '闪剪已成功但成片地址为空，暂不回写完成：任务号=%s 闪剪任务id=%d',
                        $taskNo,
                        $sjTaskId
                    ));
                    return;
                }
                $url = self::resolveResultUrl($sjTask);
                TaskService::markDone($taskNo, $url, $retrySeq);
                HotspotLog::write(sprintf(
                    '闪剪回调回写热点完成：任务号=%s 闪剪状态=成功 video_url=%s',
                    $taskNo,
                    HotspotLog::clip($url, 180)
                ));
                PublishContentService::ensureAfterDone($sjTask, $taskNo, $retrySeq);
                return;
            }
            if ($status === ShanjianVideoTask::STATUS_FAILED) {
                $error = trim((string)($sjTask->remark ?? ''));
                TaskService::markFailed($taskNo, $error !== '' ? $error : '视频合成失败', $retrySeq);
                HotspotLog::write(sprintf(
                    '闪剪回调回写热点完成：任务号=%s 闪剪状态=失败 原因=%s',
                    $taskNo,
                    mb_substr($error !== '' ? $error : '视频合成失败', 0, 200)
                ));
            }
        } catch (\Throwable $e) {
            HotspotLog::exception('闪剪回调回写热点失败', $e);
        }
    }

    public static function compensate(array $task): array
    {
        $status = (string)($task['status'] ?? '');
        if (!in_array($status, ['running', 'done'], true)) {
            return $task;
        }
        $options = is_array($task['options'] ?? null) ? $task['options'] : [];
        $sjId = TaskService::resolveBoundVideoTaskId($task);
        $settingId = (int)($options['shanjian_setting_id'] ?? 0);
        $sjTask = $sjId > 0
            ? ShanjianVideoTask::where('id', $sjId)->findOrEmpty()
            : ($settingId > 0
                ? ShanjianVideoTask::where('video_setting_id', $settingId)->order('id', 'asc')->findOrEmpty()
                : null);
        if (!$sjTask || $sjTask->isEmpty()) {
            // 曾绑定过具体闪剪任务但查不到（多为用户在创作记录里删除，软删）：
            // 不判失败任务会永久停在合成中且无法删除，这里明确标记失败让用户可重试/删除
            if ($status === 'running' && $sjId > 0) {
                $taskNo = (string)($task['id'] ?? '');
                HotspotLog::write('绑定的闪剪任务已不存在，热点任务标记失败：任务号=' . $taskNo . ' 闪剪id=' . $sjId);
                $failed = TaskService::markFailed(
                    $taskNo,
                    '关联的视频合成记录已被删除，请点击重新生成',
                    (int)($options['retry_seq'] ?? 0)
                );
                return $failed ?? $task;
            }
            return $task;
        }
        if ($sjId <= 0) {
            $boundId = (int)$sjTask->id;
            TaskService::bindShanjian((string)($task['id'] ?? ''), [
                'shanjian_task_id' => $boundId,
            ]);
            self::stampShanjianTask(
                $sjTask,
                (string)($task['id'] ?? ''),
                (int)($task['persona']['id'] ?? 0),
                (string)($task['title'] ?? ''),
                (int)($options['retry_seq'] ?? 0)
            );
            $pendingIds = is_array($options['pending_material_ids'] ?? null)
                ? $options['pending_material_ids']
                : [];
            if ($boundId > 0 && $pendingIds !== []) {
                self::persistMaterialUseLogs(
                    (int)($sjTask->user_id ?? ($task['user_id'] ?? 0)),
                    (int)($task['persona']['id'] ?? 0),
                    $boundId,
                    $pendingIds,
                    (string)($task['id'] ?? '')
                );
                TaskService::patchOptions((string)($task['id'] ?? ''), [
                    'pending_material_ids' => [],
                ], (int)($options['retry_seq'] ?? 0));
            }
        }
        self::syncFromShanjianTask($sjTask);
        $fresh = TaskService::detail((string)($task['id'] ?? ''));
        return $fresh ?? $task;
    }

    public static function isShanjianResultReady(mixed $sjTask): bool
    {
        $status = (int)($sjTask->status ?? 0);
        if ($status !== ShanjianVideoTask::STATUS_SUCCESS) {
            return false;
        }
        return self::resolveResultUrl($sjTask) !== '';
    }

    public static function syncCompleted(int $limit = 20): int
    {
        $limit = max(1, min(50, $limit));
        // 已回写成片的 done 任务在 SQL 层排除，否则历史完成任务会永久占满扫描窗口，
        // 新的 running 任务永远轮不到补偿回写
        $rows = HotspotTask::where(function ($query) {
                $query->where('status', 'running')
                    ->whereOr(function ($q) {
                        $q->where('status', 'done')->where('video_url', '');
                    });
            })
            ->order('id', 'asc')
            ->limit(max($limit * 5, 50))
            ->select();
        $changed = 0;
        foreach ($rows as $row) {
            if ($changed >= $limit) {
                break;
            }
            $status = (string)$row->status;
            $videoUrl = method_exists($row, 'getData')
                ? trim((string)($row->getData('video_url') ?? ''))
                : trim((string)($row->video_url ?? ''));
            $opt = is_array($row->options_json) ? $row->options_json : [];
            $sjId = TaskService::resolveBoundVideoTaskId($row);
            $settingId = (int)($opt['shanjian_setting_id'] ?? 0);
            if ($sjId <= 0 && $settingId <= 0) {
                continue;
            }
            if ($status === 'done' && $videoUrl !== '') {
                continue;
            }
            $task = TaskService::detail((string)$row->task_no);
            if ($task === null) {
                continue;
            }
            $fresh = self::compensate($task);
            $freshStatus = (string)($fresh['status'] ?? '');
            $freshUrl = trim((string)($fresh['video_url'] ?? ''));
            $oldUrl = trim((string)($task['video_url'] ?? ''));
            if ($freshStatus !== $status || ($freshUrl !== '' && $freshUrl !== $oldUrl)) {
                $changed++;
                HotspotLog::write(sprintf(
                    '定时回写热点成片：任务号=%s 原状态=%s 新状态=%s',
                    (string)$row->task_no,
                    $status,
                    $freshStatus
                ));
            }
        }
        return $changed;
    }

    private static function resolveAvatar(int $userId, int $personaId, array $opt): array
    {
        if ($userId <= 0 || $personaId <= 0) {
            throw new HotspotUpstreamException('数字人形象不存在或不可用');
        }
        $query = AiPersonaDigitalAvatar::availableQuery()
            ->field(['ad.id', 'ad.avatar_name', 'ad.cover_url', 'ad.third_avatar_id', 'ad.third_voice_id'])
            ->where('ad.user_id', $userId)
            ->where('ad.persona_id', $personaId);
        $avatarId = (int)($opt['avatar_id'] ?? 0);
        $avatarName = trim((string)($opt['avatar'] ?? ''));
        if ($avatarId > 0) {
            $query->where('ad.id', $avatarId);
        } elseif ($avatarName !== '') {
            $query->where('ad.avatar_name', $avatarName);
        } else {
            throw new HotspotUpstreamException('数字人口播混剪需要选择数字人形象');
        }
        $row = $query->find();
        if (!$row) {
            throw new HotspotUpstreamException('数字人形象不存在或不可用');
        }
        $anchorId = trim((string)($row['third_avatar_id'] ?? ''));
        $voiceId = trim((string)($row['third_voice_id'] ?? ''));
        if ($anchorId === '' || $voiceId === '') {
            throw new HotspotUpstreamException('数字人形象不存在或不可用');
        }
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['avatar_name'] ?? ''),
            'anchor_id' => $anchorId,
            'voice_id' => $voiceId,
            'pic' => FileService::getFileUrl((string)($row['cover_url'] ?? '')),
        ];
    }

    private static function resolveClipVoice(int $userId, int $personaId): string
    {
        if ($userId <= 0 || $personaId <= 0) {
            throw new HotspotUpstreamException('人设没有绑定音色');
        }
        $voices = AiPersonaDigitalVoice::availableQuery()
            ->where('ad.user_id', $userId)
            ->where('ad.persona_id', $personaId)
            ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
            ->column('ad.third_voice_id');
        $voices = array_values(array_filter(array_map('strval', $voices)));
        if ($voices === []) {
            throw new HotspotUpstreamException('人设没有绑定音色');
        }
        return $voices[0];
    }

    private static function resolveVoiceModelVersion(string $voiceId, int $userId): int
    {
        if ($voiceId === '' || !ShanjianVideoSettingLogic::isMinimaxVoiceId($voiceId, $userId)) {
            return 0;
        }
        $query = HumanVoice::where('voice_id', $voiceId)->whereNull('delete_time');
        if ($userId > 0) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', 0);
            });
        }
        $version = (int)$query->value('model_version');
        return in_array($version, [10, 11], true) ? $version : 11;
    }

    /**
     * clips：8 视频 + 2~3 图；digital：2~3 视频 + 2~3 图。
     */
    public static function shouldFillAiMaterials(string $mode): bool
    {
        return $mode === 'ai' || $mode === 'ai_persona';
    }

    /**
     * AI 找素材的最低条数兜底：视频 2 条，图片按目标秒数 × 15% / 每张 2 秒折算（夹 2~8 张）。
     * 实际视频条数由素材秒数锚点驱动（见 aiRound1VideoTarget），不再按固定配额找。
     *
     * @param float $targetSec 本轮素材目标秒数（数字人 floor / 素材混剪 ceil）
     */
    public static function materialQuota(string $videoType, float $targetSec = 0.0): array
    {
        $imageTarget = ScriptService::imageTargetCount($targetSec);
        return [
            'v_min' => ScriptService::MATERIAL_ANCHOR_MIN_VIDEO,
            'v_max' => ScriptService::MATERIAL_ANCHOR_MIN_VIDEO,
            'i_min' => $imageTarget,
            'i_max' => $imageTarget,
        ];
    }

    /**
     * 本轮素材目标秒数：数字人口播画面是穿插，按口播的一半（floor）即可；
     * 素材混剪画面要铺满全片，按口播全量（ceil）。
     *
     * @param array{est_sec:float,floor_sec:float,ceil_sec:float} $anchor
     */
    public static function materialTargetSec(string $videoType, array $anchor): float
    {
        return $videoType === 'digital'
            ? (float)($anchor['floor_sec'] ?? 0.0)
            : (float)($anchor['ceil_sec'] ?? 0.0);
    }

    public static function pickMaterialTargets(array $quota): array
    {
        if (isset(self::$testHooks['targets']) && is_array(self::$testHooks['targets'])) {
            return [
                'video' => (int)(self::$testHooks['targets']['video'] ?? 0),
                'image' => (int)(self::$testHooks['targets']['image'] ?? 0),
            ];
        }
        $vMin = (int)($quota['v_min'] ?? 0);
        $vMax = (int)($quota['v_max'] ?? $vMin);
        $iMin = (int)($quota['i_min'] ?? 0);
        $iMax = (int)($quota['i_max'] ?? $iMin);
        if ($vMax < $vMin) {
            $vMax = $vMin;
        }
        if ($iMax < $iMin) {
            $iMax = $iMin;
        }
        return [
            'video' => $vMin === $vMax ? $vMin : random_int($vMin, $vMax),
            'image' => $iMin === $iMax ? $iMin : random_int($iMin, $iMax),
        ];
    }

    public static function materialGap(array $counts, int $targetV, int $targetI): array
    {
        return [
            'video' => max(0, $targetV - (int)($counts['video'] ?? 0)),
            'image' => max(0, $targetI - (int)($counts['image'] ?? 0)),
        ];
    }

    /**
     * 首轮 AI 找素材的视频条数：素材秒数锚点下限扣掉已选素材可承载秒数与图片占位后，
     * 按均条长换算成总条数，不低于最低兜底 2 条、不超费用熔断 30 条。纯计算，不打库。
     *
     * @param array{video_sec:float,image_sec:float,video_count:int,image_count:int,unknown_dur_count:int} $summary
     *        已选素材时长盘点（ScriptService::sumMaterialSeconds 口径：图片每条 IMAGE_DISPLAY_SEC 秒）
     */
    public static function aiRound1VideoTarget(
        float $neededSec,
        array $summary,
        int $selectedVideoCount,
        float $avgVideoSec,
        int $minVideoCount,
        int $targetI
    ): int {
        // 图片占位只算「AI 还会补的图片」：已选图片的秒数已被 image_sec 覆盖，
        // 再按目标条数全量占位会重复扣减、让视频少补
        $aiImageWillAdd = max(0, $targetI - (int)($summary['image_count'] ?? 0));
        $videoGapSec = max(
            0.0,
            $neededSec - (float)($summary['video_sec'] ?? 0) - (float)($summary['image_sec'] ?? 0)
                - $aiImageWillAdd * ScriptService::IMAGE_DISPLAY_SEC
        );
        // 均条长异常（<=0）按保守兜底 5 秒换算，与 config('hotspot.avg_video_sec_fallback') 默认一致；
        // 不直接调 config()，保持本函数纯计算（调用方已传 averageVideoSeconds 的结果，正常恒 >=1）
        $avg = max(1.0, $avgVideoSec > 0 ? $avgVideoSec : 5.0);
        $targetByDuration = (int)ceil($videoGapSec / max(1.0, $avg)) + $selectedVideoCount;
        return max($minVideoCount, min($targetByDuration, ScriptService::MATERIAL_ANCHOR_VIDEO_CAP));
    }

    /**
     * 按「秒数」而非「条数」收割 AI 素材：先取图片到目标张数（每张 2 秒），
     * 再按视频时长累加到目标秒数停止；条数上限仍按目标条数封顶，保底 2 视频 + 2 图片。
     * $targetSec <= 0 时退化为原来的按条数收割。
     */
    public static function trimMaterialsToTargets(array $selected, int $targetV, int $targetI, float $targetSec = 0.0): array
    {
        $videos = [];
        $images = [];
        $imageSec = 0.0;
        foreach ($selected as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') === 'video') {
                continue;
            }
            if (count($images) < $targetI) {
                $images[] = $item;
                $imageSec += ScriptService::IMAGE_DISPLAY_SEC;
            }
        }
        $videoSec = 0.0;
        foreach ($selected as $item) {
            if (!is_array($item) || ($item['type'] ?? '') !== 'video') {
                continue;
            }
            if (count($videos) >= $targetV) {
                break;
            }
            $videos[] = $item;
            $videoSec += (float)($item['duration'] ?? 0);
            if ($targetSec > 0
                && $videoSec + $imageSec >= $targetSec
                && count($videos) >= ScriptService::MATERIAL_ANCHOR_MIN_VIDEO) {
                break;
            }
        }
        return array_values(array_merge($videos, $images));
    }

    /**
     * 按 fileUrl 去重：同一素材在多轮抓取里反复命中时只保留第一次出现的，
     * 否则成片会出现同一段画面反复播（用户反馈的「素材大量重复」）。
     * 传 $existing 时，与已选素材重复的候选也一并剔除。
     */
    public static function dedupeMaterialsByUrl(array $materials, array $existing = []): array
    {
        $seen = [];
        foreach ($existing as $item) {
            if (is_array($item)) {
                $url = trim((string)($item['fileUrl'] ?? $item['file_url'] ?? ''));
                if ($url !== '') {
                    $seen[$url] = true;
                }
            }
        }
        $out = [];
        foreach ($materials as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = trim((string)($item['fileUrl'] ?? $item['file_url'] ?? ''));
            if ($url !== '') {
                if (isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
            }
            $out[] = $item;
        }
        return array_values($out);
    }

    /**
     * 上限收口：AI 找来的素材让总量超过成片预估秒数时，从尾部裁掉多余的 AI 素材
     * （视频优先裁，AI 部分至少保留 2 视频 + 2 图片）。用户已选素材一律不动。
     *
     * @param array $selected 用户已选素材
     * @param array $ai AI 补来的素材
     */
    private static function trimAiMaterialsToCeil(array $selected, array $ai, float $ceilSec): array
    {
        if ($ceilSec <= 0 || $ai === []) {
            return $ai;
        }
        // 用户已选素材自身已盖过上限时，AI 部分整体多余，全部裁掉（保底条数只兜「不够」不兜「超量」）
        $selectedOnly = ScriptService::sumMaterialSeconds($selected);
        if ($selectedOnly['video_sec'] + $selectedOnly['image_sec'] >= $ceilSec) {
            HotspotLog::write(sprintf(
                'AI找素材超上限裁剪：用户已选素材=%.1f秒已盖过成片预估=%.1f秒，裁掉全部AI素材=%d条',
                $selectedOnly['video_sec'] + $selectedOnly['image_sec'],
                $ceilSec,
                count($ai)
            ));
            return [];
        }
        $sum = ScriptService::sumMaterialSeconds(array_merge($selected, $ai));
        if ($sum['video_sec'] + $sum['image_sec'] <= $ceilSec) {
            return $ai;
        }
        $counts = self::countTypes($ai);
        $videoCount = (int)($counts['video'] ?? 0);
        $imageCount = (int)($counts['image'] ?? 0);
        $dropped = 0;
        for ($i = count($ai) - 1; $i >= 0; $i--) {
            $current = ScriptService::sumMaterialSeconds(array_merge($selected, $ai));
            if ($current['video_sec'] + $current['image_sec'] <= $ceilSec) {
                break;
            }
            if (($ai[$i]['type'] ?? '') === 'video') {
                if ($videoCount <= ScriptService::MATERIAL_ANCHOR_MIN_VIDEO) {
                    continue;
                }
                $videoCount--;
            } else {
                if ($imageCount <= ScriptService::MATERIAL_ANCHOR_MIN_IMAGE) {
                    continue;
                }
                $imageCount--;
            }
            unset($ai[$i]);
            $dropped++;
        }
        if ($dropped > 0) {
            HotspotLog::write(sprintf(
                'AI找素材超上限裁剪：成片预估=%.1f秒 裁掉AI素材=%d条 保留视频=%d 图片=%d',
                $ceilSec,
                $dropped,
                $videoCount,
                $imageCount
            ));
        }
        return array_values($ai);
    }

    /**
     * 装载用户已选素材与模式上下文：resolveMaterials（下发收口）与 precheckMaterials（创建预检）共用，
     * 保证两处对「谁将被 AI 补齐、谁靠纯人设素材」的判断同口径。
     *
     * @param array $opt normalizeOptions 后的设置
     * @return array{selected:list,mode:string,manualMode:bool,frozenUseMode:?int,isOriginalMode:bool}
     */
    private static function loadSelectedMaterials(int $userId, int $personaId, array $opt): array
    {
        $mode = (string)($opt['material_mode'] ?? 'ai_persona');
        $manualMode = (int)($opt['persona_mode'] ?? ScriptService::PERSONA_MODE_PERSONA)
            === ScriptService::PERSONA_MODE_MANUAL;
        $frozenUseMode = (!$manualMode && $personaId > 0)
            ? ScriptService::resolveFrozenMaterialUseMode($opt, $personaId)
            : ShanjianVideoSettingLogic::MATERIAL_USE_MODE_SLICE;
        $isOriginalMode = !$manualMode && $personaId > 0
            && $frozenUseMode === ShanjianVideoSettingLogic::MATERIAL_USE_MODE_ORIGINAL;
        $selectedIds = $mode === 'ai' ? [] : ($opt['materials'] ?? []);
        $selected = $mode === 'ai' ? [] : self::loadPersonaMaterials(
            $userId,
            $personaId,
            $selectedIds,
            ScriptService::resolveClipReloadUseMode($opt)
        );
        if ($mode !== 'ai') {
            ScriptService::assertSelectedMaterialsReloaded(is_array($selectedIds) ? $selectedIds : [], $selected);
            $missing = ScriptService::selectedIdsMissingFromReload(
                is_array($selectedIds) ? $selectedIds : [],
                self::collectMaterialIds($selected)
            );
            if ($missing !== []) {
                HotspotLog::write(sprintf(
                    '人设素材部分未回表：已选=%d 回表=%d 未命中id=%s',
                    count(array_filter(array_map('intval', is_array($selectedIds) ? $selectedIds : []), static fn($id) => $id > 0)),
                    count($selected),
                    implode(',', $missing)
                ));
            }
        }
        return [
            'selected' => $selected,
            'mode' => $mode,
            'manualMode' => $manualMode,
            'frozenUseMode' => $frozenUseMode,
            'isOriginalMode' => $isOriginalMode,
        ];
    }

    /**
     * 创建时素材预检（锚点前置）：按素材秒数锚点盘点用户已选素材与同人设池可补时长，
     * 给前端返回「成片预估/下限/上限秒数 + AI 将补齐 N 条 / 纯人设素材预计仍缺多少秒」。
     * 只读：不写库、不调 AI、不产生费用。任何异常都吞掉返回 []，预检只是建议，绝不阻塞任务创建。
     *
     * @param array $task TaskService::create 返回的任务数组（含 script/options/persona）
     * @return array{
     *     est_duration_sec:int,est_sec:float,floor_sec:float,ceil_sec:float,
     *     needed_sec:float,available_sec:float,gap_sec:float,
     *     ai_fill_video:int,ai_fill_image:int,pool_fill_sec:float,warning:string
     * }|array{}
     */
    public static function precheckMaterials(array $task, int $userId): array
    {
        try {
            $title = trim((string)($task['title'] ?? ''));
            if ($title === '') {
                $title = (string)($task['topic'] ?? '');
            }
            // 与下发链路同口径：先去文案开头标题，再估时长
            $script = ScriptService::stripLeadingTitle(trim((string)($task['script'] ?? '')), $title);
            if ($script === '') {
                return [];
            }
            $opt = ScriptService::normalizeOptions(is_array($task['options'] ?? null) ? $task['options'] : []);
            $manualMode = (int)($opt['persona_mode'] ?? ScriptService::PERSONA_MODE_PERSONA)
                === ScriptService::PERSONA_MODE_MANUAL;
            $videoType = (string)($opt['video_type'] ?? '');
            if ($videoType === '') {
                $videoType = 'clips';
            }
            $personaId = 0;
            if ($manualMode) {
                // 不选人设：语速按高级设置折算，素材固定 AI 找，没有人设池可查
                $speedRatio = AiPersonaSynthesisConfig::normalizeSpeechRate($opt['speech_rate'] ?? null);
            } else {
                $persona = is_array($task['persona'] ?? null) ? $task['persona'] : [];
                $personaId = (int)($persona['id'] ?? 0);
                if ($personaId <= 0) {
                    return [];
                }
                // 素材充足性要按该生成类型的语速折算时长，不然三类语速不同时估得不准
                $speedRatio = self::resolveSpeechRate(
                    self::resolveSynthesisConfig($userId, $personaId),
                    self::generationTypeByVideoType($videoType)
                );
            }
            $anchor = ScriptService::materialAnchorSeconds($script, $speedRatio);
            if ($anchor['est_sec'] <= 0) {
                return [];
            }
            $estSec = ScriptService::estimateSpokenDurationSec($script, $speedRatio);
            // 数字人按口播一半（floor）、素材混剪按口播全量（ceil）驱动首轮目标
            $neededSec = self::materialTargetSec($videoType, $anchor);
            $ctx = self::loadSelectedMaterials($userId, $personaId, $opt);
            $selected = $ctx['selected'];
            $mode = $ctx['manualMode'] ? 'ai' : $ctx['mode'];
            $summary = ScriptService::sumMaterialSeconds($selected);
            $availableSec = $summary['video_sec'] + $summary['image_sec'];
            $counts = self::countTypes($selected);
            $quota = self::materialQuota($videoType, $neededSec);
            $targets = self::pickMaterialTargets($quota);
            $targetI = (int)$targets['image'];
            $precheck = [
                'est_duration_sec' => $estSec,
                'est_sec' => $anchor['est_sec'],
                'floor_sec' => $anchor['floor_sec'],
                'ceil_sec' => $anchor['ceil_sec'],
                'needed_sec' => round($neededSec, 2),
                'available_sec' => round($availableSec, 2),
                'gap_sec' => round(max(0.0, $neededSec - $availableSec), 2),
                'ai_fill_video' => 0,
                'ai_fill_image' => 0,
                'pool_fill_sec' => 0.0,
                'warning' => '',
            ];
            if (self::shouldFillAiMaterials($mode)) {
                // AI 模式：缺口由首轮 AI 补（最低 2 视频 + 2 图片兜底，秒数锚点同 resolveMaterials），预检只给数量建议
                $targetV = self::aiRound1VideoTarget(
                    $neededSec,
                    $summary,
                    $counts['video'],
                    self::averageVideoSeconds($selected),
                    (int)$targets['video'],
                    $targetI
                );
                $gap = self::materialGap($counts, $targetV, $targetI);
                $precheck['ai_fill_video'] = (int)$gap['video'];
                $precheck['ai_fill_image'] = (int)$gap['image'];
                return $precheck;
            }
            // 纯人设：缺口靠同人设池自动补入，预检先只读盘点池子能补多少，仍缺就出提示
            $gapSec = max(0.0, $neededSec - $availableSec);
            if ($gapSec > 0 && $personaId > 0) {
                $pool = self::poolFillableSeconds(
                    $userId,
                    $personaId,
                    $ctx['frozenUseMode'],
                    $selected,
                    $gapSec,
                    $ctx['isOriginalMode']
                );
                $precheck['pool_fill_sec'] = $pool['added_sec'];
                $remaining = round(max(0.0, $gapSec - $pool['added_sec']), 2);
                $precheck['gap_sec'] = $remaining;
                if ($remaining > 0) {
                    $precheck['warning'] = sprintf(
                        '素材预计可承载约%.1f秒（含同人设池可补%.1f秒），成片约%.1f秒、素材需覆盖约%.1f秒，仍缺%.1f秒；建议先在素材库补充素材，或缩短文案后再生成',
                        $availableSec,
                        $pool['added_sec'],
                        $anchor['est_sec'],
                        $neededSec,
                        $remaining
                    );
                }
            }
            return $precheck;
        } catch (\Throwable $e) {
            HotspotLog::write('创建素材预检失败（不阻塞创建）：' . $e->getMessage());
            return [];
        }
    }

    /**
     * 同人设池候选按「使用次数升序 + 时长倒序」排序：先轮转用得少的，同次数内优先长素材，
     * 避免固定按最长优先导致短素材永远轮不到。
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private static function sortPersonaPoolCandidates(array $candidates, int $personaId): array
    {
        if ($candidates === []) {
            return [];
        }
        $ids = [];
        foreach ($candidates as $candidate) {
            $id = (int)($candidate['material_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        // 热点补入链路包含手动任务(auto_type=0)，统计时一并计入，否则次数恒为 0、轮转不生效
        $usage = MaterialUsageService::countsForPersona($personaId, $ids, true);
        shuffle($candidates);
        usort($candidates, function ($a, $b) use ($usage) {
            $usageA = $usage[(int)($a['material_id'] ?? 0)] ?? 0;
            $usageB = $usage[(int)($b['material_id'] ?? 0)] ?? 0;
            if ($usageA !== $usageB) {
                return $usageA <=> $usageB;
            }
            return (float)($b['duration'] ?? 0) <=> (float)($a['duration'] ?? 0);
        });
        return $candidates;
    }

    /**
     * 纯人设预检用：只读统计同人设可选视频池还能补进的秒数，不改动素材、不落库。
     * 与下发链路 fillFromPersonaPool 同口径：可选池排除已选、按使用次数升序（同次数时长倒序）、
     * 累计到缺口即止；原图模式候选先按时长裁剪再统计（与补入动作一致）。
     *
     * @return array{count:int,added_sec:float}
     */
    private static function poolFillableSeconds(
        int $userId,
        int $personaId,
        ?int $frozenUseMode,
        array $selected,
        float $gapSec,
        bool $isOriginalMode
    ): array {
        $haveIds = [];
        foreach ($selected as $item) {
            $id = (int)($item['material_id'] ?? 0);
            if ($id > 0) {
                $haveIds[] = $id;
            }
        }
        try {
            $query = PersonaService::clipMaterialQuery($userId, $personaId, $frozenUseMode, $frozenUseMode !== null)
                ->where('duration', '>', 0)
                ->field(['id', 'file_url', 'material_type', 'duration'])
                ->order('duration', 'desc')
                ->limit(self::PERSONA_POOL_FILL_LIMIT);
            if ($haveIds !== []) {
                $query->whereNotIn('id', $haveIds);
            }
            $rows = $query->select()->toArray();
        } catch (\Throwable $e) {
            HotspotLog::write('创建预检同人设池统计失败：' . $e->getMessage());
            return ['count' => 0, 'added_sec' => 0.0];
        }
        $candidates = self::formatRawMaterials($rows);
        if ($isOriginalMode) {
            $candidates = ShanjianVideoSettingLogic::trimMaterialsByDuration($candidates);
        }
        $candidates = self::sortPersonaPoolCandidates($candidates, $personaId);
        $count = 0;
        $addedSec = 0.0;
        foreach ($candidates as $candidate) {
            if ($addedSec >= $gapSec) {
                break;
            }
            if (($candidate['type'] ?? '') !== 'video') {
                continue;
            }
            $duration = (float)($candidate['duration'] ?? 0);
            if ($duration <= 0) {
                continue;
            }
            $count++;
            $addedSec += $duration;
        }
        return ['count' => $count, 'added_sec' => round($addedSec, 2)];
    }

    private static function resolveMaterials(int $userId, int $personaId, array $opt, string $script, float $speedRatio = 1.0, string $taskNo = ''): array
    {
        $ctx = self::loadSelectedMaterials($userId, $personaId, $opt);
        $selected = $ctx['selected'];
        // 上一轮（或前几次重试）被全候选拒绝的词，本轮不再回到 available
        $blockedKeywords = CopywritingImitationLogic::normalizeBlockedKeywords($opt['ai_blocked_keywords'] ?? []);
        $mode = $ctx['mode'];
        $manualMode = $ctx['manualMode'];
        $frozenUseMode = $ctx['frozenUseMode'];
        $isOriginalMode = $ctx['isOriginalMode'];
        // clips=素材混剪(3)，digital=数字人口播(1)；原图缺视频只拦混剪
        $requiresOriginalVideo = ShanjianVideoSettingLogic::originalModeRequiresVideo(
            ($opt['video_type'] ?? '') === 'clips' ? 3 : 1
        );
        // 原图模式判空：纯人设素材没有后手，这里就得报错；
        // ai/ai_persona 的缺口留给下面 AI 补齐，补完再判一次，否则会在 AI 出手前先被判死
        if ($isOriginalMode && !self::shouldFillAiMaterials($mode)) {
            $beforeTrim = $selected;
            $selected = ShanjianVideoSettingLogic::trimMaterialsByDuration($selected);
            // 只有素材混剪缺视频出不了片才判死；数字人口播的素材是穿插画面，
            // 只选图片照样能合成，不该按「原图模式必须选视频」整单打回
            // （与下面 AI 补齐后那次收口同口径）
            if ($requiresOriginalVideo) {
                $emptyVideoError = ShanjianVideoSettingLogic::originalModeEmptyVideoError($selected, $beforeTrim);
                if ($emptyVideoError !== '') {
                    throw new HotspotUpstreamException($emptyVideoError);
                }
            } elseif (count($selected) !== count($beforeTrim)) {
                HotspotLog::write(sprintf(
                    '原图模式素材按时长裁剪：视频类型=%s 裁剪前=%d 裁剪后=%d',
                    (string)$opt['video_type'],
                    count($beforeTrim),
                    count($selected)
                ));
            }
        }
        $anchor = ScriptService::materialAnchorSeconds($script, $speedRatio);
        $videoType = (string)($opt['video_type'] ?? '');
        $targetSec = self::materialTargetSec($videoType, $anchor);
        $quota = self::materialQuota($videoType, $targetSec);
        $targets = self::pickMaterialTargets($quota);
        $targetV = (int)$targets['video'];
        $targetI = (int)$targets['image'];

        if (self::shouldFillAiMaterials($mode)) {
            // 用户已选的人设素材全量保留，最低兜底条数只用于计算 AI 需要补齐的缺口；
            // 此前先按配额裁剪用户选择，会把多选的素材直接丢掉，后台/成片都看不到用户选的素材
            $counts = self::countTypes($selected);
            // —— 首轮目标按素材秒数锚点换算（「创建时锚点」落地点）——
            // 数字人口播按口播的一半（floor）、素材混剪按口播全量（ceil），
            // 扣掉已选素材可承载秒数与图片目标占位后，按已选素材均条长换算成视频条数，
            // 不低于 2 条兜底、不超 50 条费用熔断。
            // 短素材多时首轮就能补足时长，不必落二轮再抓一遍（省一次 AI 按次抓取费用）。
            if ((bool)config('hotspot.ai_round1_duration_enabled', true) && $targetSec > 0) {
                $estSec = ScriptService::estimateSpokenDurationSec($script, $speedRatio);
                $neededSec = $targetSec;
                $summary = ScriptService::sumMaterialSeconds($selected);
                $avgSec = self::averageVideoSeconds($selected);
                $quotaTargetV = $targetV;
                $targetV = self::aiRound1VideoTarget(
                    $neededSec,
                    $summary,
                    $counts['video'],
                    $avgSec,
                    $targetV,
                    $targetI
                );
                if ($targetV !== $quotaTargetV) {
                    HotspotLog::write(sprintf(
                        '首轮AI找素材按秒数锚点：任务号=%s 类型=%s 口播预估=%d秒 素材预估=%.1f秒 下限=%.1f秒 上限=%.1f秒 已选视频=%d条%.1f秒（未知时长%d条） 均条长=%.1f秒 兜底=%d条 取=%d条 熔断=%d条',
                        $taskNo !== '' ? $taskNo : '-',
                        (string)$opt['video_type'],
                        $estSec,
                        $anchor['est_sec'],
                        $anchor['floor_sec'],
                        $anchor['ceil_sec'],
                        $counts['video'],
                        $summary['video_sec'],
                        $summary['unknown_dur_count'],
                        $avgSec,
                        $quotaTargetV,
                        $targetV,
                        ScriptService::MATERIAL_ANCHOR_VIDEO_CAP
                    ));
                }
            }
            $gap = self::materialGap($counts, $targetV, $targetI);
            $needV = $gap['video'];
            $needI = $gap['image'];
            if ($needV > 0 || $needI > 0) {
                HotspotLog::write(sprintf(
                    '开始AI找素材：模式=%s 目标视频=%d 图片=%d 已有视频=%d 图片=%d 缺口视频=%d 图片=%d',
                    $mode,
                    $targetV,
                    $targetI,
                    $counts['video'],
                    $counts['image'],
                    $needV,
                    $needI
                ));
                try {
                    [$aiV, $aiI] = self::fetchAiMaterials(
                        $script,
                        $needV,
                        $needI,
                        $userId,
                        $personaId,
                        (string)($opt['platform'] ?? ''),
                        $blockedKeywords,
                        $taskNo
                    );
                    $blockedKeywords = self::mergeBlockedKeywords(
                        $blockedKeywords,
                        CopywritingImitationLogic::lastGrabFailedKeywords()
                    );
                    $ai = self::formatRawMaterials(is_array($aiV) ? $aiV : []);
                    $ai = array_merge($ai, self::formatRawMaterials(is_array($aiI) ? $aiI : []));
                    // 同一 URL 反复命中或多轮重复，都会让成片画面重复，先按 URL 去重（含与已选素材重复的）
                    $ai = self::dedupeMaterialsByUrl($ai, $selected);
                    if ($isOriginalMode) {
                        $ai = self::filterMaterialsByOriginalSpec($ai);
                    }
                    // 只裁剪 AI 补来的部分，不动用户已选素材；按目标秒数累加收割
                    $ai = self::trimMaterialsToTargets(
                        ShanjianVideoSettingLogic::trimMaterialsByDuration($ai),
                        $needV,
                        $needI,
                        $targetSec
                    );
                    // 总量超过成片预估秒数时从尾部裁掉多余 AI 素材（用户已选素材不动）
                    $ai = self::trimAiMaterialsToCeil($selected, $ai, $anchor['ceil_sec']);
                    $selected = array_merge($selected, $ai);
                    $stats = CopywritingImitationLogic::lastGrabStats();
                    HotspotLog::write(sprintf(
                        'AI找素材抓取次数 付费视频=%d/%d 付费图片=%d/%d 封面补图=%d 保留视频=%d 保留图片=%d',
                        (int)($stats['paid_video'] ?? count(is_array($aiV) ? $aiV : [])),
                        $needV,
                        (int)($stats['paid_image'] ?? count(is_array($aiI) ? $aiI : [])),
                        $needI,
                        (int)($stats['cover_image'] ?? 0),
                        (int)($stats['kept_video'] ?? 0),
                        (int)($stats['kept_image'] ?? 0)
                    ));
                    $got = self::countTypes($selected);
                    if ($got['video'] < $targetV || $got['image'] < $targetI) {
                        HotspotLog::write(sprintf(
                            'AI补齐未完成，仅用人设素材继续：模式=%s 当前视频=%d 图片=%d 目标视频=%d 图片=%d',
                            $mode,
                            $got['video'],
                            $got['image'],
                            $targetV,
                            $targetI
                        ));
                    }
                } catch (\Throwable $e) {
                    HotspotLog::write('AI找素材失败：' . $e->getMessage());
                    if (self::shouldFailClipsOnAiMaterialError((string)$opt['video_type'], $selected)) {
                        throw new HotspotUpstreamException(self::clipsEmptyMaterialError($mode, $e->getMessage(), $manualMode));
                    }
                    if ($opt['video_type'] === 'digital') {
                        HotspotLog::write('数字人口播AI找素材失败，降级为纯口播');
                    }
                }
            }
        }

        if ($opt['video_type'] === 'clips' && $mode === 'ai' && $selected === []) {
            throw new HotspotUpstreamException(self::clipsEmptyMaterialError($mode, '', $manualMode));
        }

        // AI 补齐之后再收一次口：人设原片与 AI 素材合到一起才知道总时长够不够
        if ($isOriginalMode && self::shouldFillAiMaterials($mode)) {
            $beforeTrim = $selected;
            $selected = ShanjianVideoSettingLogic::trimMaterialsByDuration($selected);
            // 素材混剪必须有可用视频；数字人口播沿用上面「AI失败降级为纯口播」的处置，不因缺视频整单失败
            if ($requiresOriginalVideo) {
                $emptyVideoError = ShanjianVideoSettingLogic::originalModeEmptyVideoError($selected, $beforeTrim);
                if ($emptyVideoError !== '') {
                    throw new HotspotUpstreamException($emptyVideoError);
                }
            }
        }

        // —— 素材可承载时长收口：保证送上游的素材总时长 ≥ 口播实际预估时长 × 裕量 ——
        // 切片模式此前没有任何时长闸门（trimMaterialsByDuration 只跑原图模式），
        // 素材可承载时长短于口播时上游只能循环素材补齐，成片观感就是同一段画面反复出现。
        self::$lastDurationGate = self::ensureMaterialDurationCoverage($userId, $personaId, $opt, $script, $speedRatio, $selected, [
            'frozenUseMode' => $frozenUseMode,
            'isOriginalMode' => $isOriginalMode,
            'blockedKeywords' => $blockedKeywords,
            'taskNo' => $taskNo,
        ]);
        // 本轮失败词并入热点任务选项，重试下发时通过 ai_blocked_keywords 阻断（最小落库方案）
        $blockedKeywords = self::mergeBlockedKeywords(
            $blockedKeywords,
            CopywritingImitationLogic::lastGrabFailedKeywords()
        );
        if ($taskNo !== '' && ($blockedKeywords['video'] !== [] || $blockedKeywords['image'] !== [])) {
            TaskService::patchOptions($taskNo, ['ai_blocked_keywords' => $blockedKeywords]);
        }

        return array_values($selected);
    }

    /** 最近一次素材时长收口结果，供下发日志与 extra 透出 */
    private static array $lastDurationGate = [
        'est_duration_sec' => 0,
        'needed_sec' => 0.0,
        'available_sec' => 0.0,
        'gap_sec' => 0.0,
        'ai_round2' => 0,
        'pool_filled' => 0,
    ];

    /** AI 补齐每轮最多追加的视频条数（兜抓取费用） */
    public const AI_ROUND2_VIDEO_CAP = 20;

    /** AI 补齐最多轮次：整单视频总数另有 50 条熔断兜底 */
    public const AI_ROUND2_MAX_ROUNDS = 3;

    /** 同人设池自动补入的候选扫描行数：先取足够大的候选集，再按使用次数轮转挑选 */
    public const PERSONA_POOL_FILL_LIMIT = 200;

    /**
     * 素材可承载时长收口：不足素材下限秒数时二轮补齐，补齐后仍不足按配置警告或拦截。
     * 直接改写 $selected（引用传入），返回收口明细供日志与 extra 透出。
     *
     * @param array{frozenUseMode:?int,isOriginalMode:bool} $ctx resolveMaterials 已算好的模式上下文
     * @return array{est_duration_sec:int,est_sec:float,floor_sec:float,ceil_sec:float,needed_sec:float,available_sec:float,gap_sec:float,ai_round2:int,pool_filled:int}
     */
    private static function ensureMaterialDurationCoverage(
        int $userId,
        int $personaId,
        array $opt,
        string $script,
        float $speedRatio,
        array &$selected,
        array $ctx
    ): array {
        $videoType = (string)($opt['video_type'] ?? 'clips');
        $mode = (string)($opt['material_mode'] ?? 'ai_persona');
        $anchor = ScriptService::materialAnchorSeconds($script, $speedRatio);
        $estSec = ScriptService::estimateSpokenDurationSec($script, $speedRatio);
        $gate = [
            'est_duration_sec' => 0,
            'est_sec' => 0.0,
            'floor_sec' => 0.0,
            'ceil_sec' => 0.0,
            'needed_sec' => 0.0,
            'available_sec' => 0.0,
            'gap_sec' => 0.0,
            'ai_round2' => 0,
            'pool_filled' => 0,
        ];
        // 空文案不评：原素材为空下发时另有兜底，这里不重复拦
        if ($estSec <= 0 || $anchor['floor_sec'] <= 0) {
            return $gate;
        }
        // 收口阈值 = 本轮素材目标秒数（数字人 floor / 素材混剪 ceil）
        $neededSec = self::materialTargetSec($videoType, $anchor);
        if ($neededSec <= 0) {
            return $gate;
        }

        $summary = ScriptService::sumMaterialSeconds($selected);
        $availableSec = $summary['video_sec'] + $summary['image_sec'];
        $gapSec = $neededSec - $availableSec;
        $gate['est_duration_sec'] = $estSec;
        $gate['est_sec'] = $anchor['est_sec'];
        $gate['floor_sec'] = $anchor['floor_sec'];
        $gate['ceil_sec'] = $anchor['ceil_sec'];
        $gate['needed_sec'] = round($neededSec, 2);
        $gate['available_sec'] = round($availableSec, 2);
        $gate['gap_sec'] = round(max(0.0, $gapSec), 2);

        HotspotLog::write(sprintf(
            '热点素材时长收口：类型=%s 模式=%s 口播预估=%d秒 素材预估=%.1f秒 下限=%.1f秒 上限=%.1f秒 素材=%.1f秒（视频%d条共%.1f秒，未知时长%d条；图片%d条共%.1f秒） 缺口=%.1f秒',
            $videoType,
            $mode,
            $estSec,
            $anchor['est_sec'],
            $anchor['floor_sec'],
            $anchor['ceil_sec'],
            $availableSec,
            $summary['video_count'],
            $summary['video_sec'],
            $summary['unknown_dur_count'],
            $summary['image_count'],
            $summary['image_sec'],
            $gapSec
        ));
        if ($gapSec <= 0) {
            return $gate;
        }

        $frozenUseMode = is_int($ctx['frozenUseMode'] ?? null) ? $ctx['frozenUseMode'] : null;
        $isOriginalMode = (bool)($ctx['isOriginalMode'] ?? false);
        $blockedKeywords = CopywritingImitationLogic::normalizeBlockedKeywords($ctx['blockedKeywords'] ?? []);
        $taskNo = (string)($ctx['taskNo'] ?? '');

        // —— 多轮补齐 AI：首轮按目标秒数补但仍可能因素材偏短没补够 ——
        // 每轮按缺口折算条数（单轮 ≤20 条、视频总数 ≤50），候选与已有素材全重复或抓空即停，
        // 避免同一批素材被反复抓、费用白花且画面重复。
        if (self::shouldFillAiMaterials($mode) && (bool)config('hotspot.ai_second_round_enabled', true)) {
            for ($round = 1; $round <= self::AI_ROUND2_MAX_ROUNDS; $round++) {
                $summaryNow = ScriptService::sumMaterialSeconds($selected);
                $gapNow = $neededSec - ($summaryNow['video_sec'] + $summaryNow['image_sec']);
                // 单条素材本身有 1 秒级时长误差，差不足 1 秒视为已覆盖，避免再花一次抓取费用
                if ($gapNow < 1.0) {
                    break;
                }
                $budget = ScriptService::MATERIAL_ANCHOR_VIDEO_CAP - (int)$summaryNow['video_count'];
                if ($budget <= 0) {
                    HotspotLog::write(sprintf(
                        '热点素材AI补齐停止：视频条数已达熔断上限 %d 条',
                        ScriptService::MATERIAL_ANCHOR_VIDEO_CAP
                    ));
                    break;
                }
                $avgSec = self::averageVideoSeconds($selected);
                $needV = (int)ceil($gapNow / $avgSec);
                $needV = max(1, min($needV, self::AI_ROUND2_VIDEO_CAP, $budget));
                HotspotLog::write(sprintf(
                    '热点素材第%d轮AI补齐开始：缺口=%.1f秒 平均条长=%.1f秒 追加视频=%d条',
                    $round,
                    $gapNow,
                    $avgSec,
                    $needV
                ));
                try {
                    [$aiV, $aiI] = self::fetchAiMaterials(
                        $script,
                        $needV,
                        0,
                        $userId,
                        $personaId,
                        (string)($opt['platform'] ?? ''),
                        $blockedKeywords,
                        $taskNo
                    );
                    $blockedKeywords = self::mergeBlockedKeywords(
                        $blockedKeywords,
                        CopywritingImitationLogic::lastGrabFailedKeywords()
                    );
                    $ai = self::formatRawMaterials(is_array($aiV) ? $aiV : []);
                    if ($isOriginalMode) {
                        $ai = self::filterMaterialsByOriginalSpec($ai);
                    }
                    $ai = ShanjianVideoSettingLogic::trimMaterialsByDuration($ai);
                    $ai = self::dedupeMaterialsByUrl($ai, $selected);
                    if ($ai === []) {
                        HotspotLog::write(sprintf(
                            '热点素材第%d轮AI补齐停止：候选与已有素材全部重复或抓空（拉取=%d条）',
                            $round,
                            count(is_array($aiV) ? $aiV : [])
                        ));
                        break;
                    }
                    // 本轮只补当前缺口，够填即停，避免一条长素材把后续轮次额度吃满
                    $ai = self::trimMaterialsToTargets($ai, $needV, 0, $gapNow);
                    if ($ai === []) {
                        break;
                    }
                    $selected = array_merge($selected, $ai);
                    $gate['ai_round2'] += count($ai);
                    HotspotLog::write(sprintf(
                        '热点素材第%d轮AI补齐完成：追加=%d条 拉取量（付费视频=%d 图片=%d）',
                        $round,
                        count($ai),
                        count(is_array($aiV) ? $aiV : []),
                        count(is_array($aiI) ? $aiI : [])
                    ));
                } catch (\Throwable $e) {
                    HotspotLog::write('热点素材AI补齐失败：' . $e->getMessage());
                    break;
                }
            }
        } elseif ($mode === 'persona' && $personaId > 0
            && (bool)config('hotspot.persona_pool_autofill_enabled', true)) {
            $filled = self::fillFromPersonaPool($userId, $personaId, $frozenUseMode, $selected, $gapSec, $isOriginalMode);
            $gate['pool_filled'] = (int)$filled['count'];
        }

        // 原图模式补进新素材后整单重裁：前置用户素材在前、后补素材在后，裁掉只会裁到后补的
        if ($isOriginalMode) {
            $selected = ShanjianVideoSettingLogic::trimMaterialsByDuration($selected);
        }

        $summary2 = ScriptService::sumMaterialSeconds($selected);
        $available2 = $summary2['video_sec'] + $summary2['image_sec'];
        $gap2 = round(max(0.0, $neededSec - $available2), 2);
        $gate['available_sec'] = round($available2, 2);
        $gate['gap_sec'] = $gap2;

        if ($gap2 <= 0) {
            HotspotLog::write(sprintf(
                '热点素材时长收口完成：补齐后素材=%.1f秒 已覆盖需=%.1f秒（AI追加=%d条 池补入=%d条）',
                $available2,
                $neededSec,
                $gate['ai_round2'],
                $gate['pool_filled']
            ));
            return $gate;
        }

        $msg = sprintf(
            '素材总时长约%.1f秒，成片约%.1f秒（素材需覆盖约%.1f秒），仍缺%.1f秒；请在素材库补充素材，或把口播时长改短后重试',
            $available2,
            $anchor['est_sec'],
            $neededSec,
            $gap2
        );
        $fail = $videoType === 'clips'
            ? (bool)config('hotspot.material_gap_fail_clips', false)
            : (bool)config('hotspot.material_gap_fail_digital', false);
        if ($fail) {
            HotspotLog::write('热点素材时长不足整单拦截：类型=' . $videoType . ' ' . $msg);
            throw new HotspotUpstreamException($msg);
        }
        HotspotLog::write(sprintf(
            '热点素材时长不足（警告照常出片）：类型=%s %s',
            $videoType,
            $msg
        ));
        return $gate;
    }

    /**
     * persona（纯人设勾选）模式自动补入：从同人设可选素材池按使用次数升序补视频。
     * 与混剪素材列表同口径（clipMaterialQuery 按快照/人设模式滤），不产生 AI 找素材费用。
     *
     * @return array{count:int,added_sec:float,ids:list<int>}
     */
    private static function fillFromPersonaPool(
        int $userId,
        int $personaId,
        ?int $frozenUseMode,
        array &$selected,
        float $gapSec,
        bool $isOriginalMode
    ): array {
        $haveIds = [];
        foreach ($selected as $item) {
            $id = (int)($item['material_id'] ?? 0);
            if ($id > 0) {
                $haveIds[] = $id;
            }
        }
        try {
            $query = PersonaService::clipMaterialQuery($userId, $personaId, $frozenUseMode, $frozenUseMode !== null)
                ->where('duration', '>', 0)
                ->field(['id', 'file_url', 'thumbnail_url', 'material_type', 'duration'])
                ->order('duration', 'desc')
                ->limit(self::PERSONA_POOL_FILL_LIMIT);
            if ($haveIds !== []) {
                $query->whereNotIn('id', $haveIds);
            }
            $rows = $query->select()->toArray();
        } catch (\Throwable $e) {
            HotspotLog::write('热点素材同人设池补入查询失败：' . $e->getMessage());
            return ['count' => 0, 'added_sec' => 0.0, 'ids' => []];
        }
        $candidates = self::formatRawMaterials($rows);
        if ($isOriginalMode) {
            $candidates = ShanjianVideoSettingLogic::trimMaterialsByDuration($candidates);
        }
        $candidates = self::sortPersonaPoolCandidates($candidates, $personaId);
        $added = 0;
        $addedSec = 0.0;
        $addedIds = [];
        foreach ($candidates as $candidate) {
            if ($addedSec >= $gapSec) {
                break;
            }
            if (($candidate['type'] ?? '') !== 'video') {
                continue;
            }
            $duration = (float)($candidate['duration'] ?? 0);
            if ($duration <= 0) {
                continue;
            }
            $selected[] = $candidate;
            $added++;
            $addedSec += $duration;
            if (!empty($candidate['material_id'])) {
                $addedIds[] = (int)$candidate['material_id'];
            }
        }
        if ($added > 0) {
            HotspotLog::write(sprintf(
                '热点素材同人设池自动补入：候选=%d 补入=%d条 追加时长=%.1f秒 素材id=%s',
                count($candidates),
                $added,
                $addedSec,
                implode(',', $addedIds)
            ));
        } else {
            HotspotLog::write(sprintf(
                '热点素材同人设池无可补素材：候选=%d 缺口=%.1f秒',
                count($candidates),
                $gapSec
            ));
        }
        return ['count' => $added, 'added_sec' => round($addedSec, 2), 'ids' => $addedIds];
    }

    /** 已选素材视频均条长：无可用时长时退保守值（可配），换算 AI 追加条数用 */
    private static function averageVideoSeconds(array $materials): float
    {
        $total = 0.0;
        $count = 0;
        foreach ($materials as $item) {
            if (is_array($item) && ($item['type'] ?? '') === 'video') {
                $duration = (float)($item['duration'] ?? 0);
                if ($duration > 0) {
                    $total += $duration;
                    $count++;
                }
            }
        }
        if ($count > 0) {
            return max(1.0, $total / $count);
        }
        $fallback = (float)config('hotspot.avg_video_sec_fallback', 5.0);
        return $fallback > 0 ? max(1.0, $fallback) : 5.0;
    }

    public static function resolveMaterialsForTest(int $userId, int $personaId, array $opt, string $script): array
    {
        return self::resolveMaterials($userId, $personaId, $opt, $script);
    }

    private static function fetchAiMaterials(
        string $script,
        int $needV,
        int $needI,
        int $userId,
        int $personaId,
        string $platform = '',
        array $blockedKeywords = [],
        string $taskNo = ''
    ): array {
        self::recordHook('getAiMaterialsCalls', [
            'script' => $script,
            'needV' => $needV,
            'needI' => $needI,
            'user_id' => $userId,
            'persona_id' => $personaId,
            'material_store' => 'sv_media',
            'platform' => $platform,
            'blocked_keywords' => $blockedKeywords,
        ]);
        if (array_key_exists('getAiMaterials', self::$testHooks)) {
            $hook = self::$testHooks['getAiMaterials'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            if (is_callable($hook)) {
                return $hook($script, $needV, $needI, $userId, $personaId);
            }
            return is_array($hook) ? $hook : [[], []];
        }
        return CopywritingImitationLogic::getAiMaterials(
            $script,
            $needV,
            $needI,
            $userId,
            $personaId,
            CopywritingImitationLogic::aiMaterialBudgetOpts($needV, $needI, [
                'material_store' => 'sv_media',
                'platform' => $platform,
                'blocked_keywords' => $blockedKeywords,
                // 日志贯通：热点任务号即 task_id，trace_id 固定，便于按任务回放多轮抓取
                'task_id' => $taskNo,
                'trace_id' => $taskNo !== '' ? 'hotspot_' . $taskNo : '',
            ])
        );
    }

    /**
     * 合并本轮新失败词到历史阻断词（按类型去重），供重试链路落库。
     */
    public static function mergeBlockedKeywords(array $blocked, array $failed): array
    {
        $merged = CopywritingImitationLogic::normalizeBlockedKeywords($blocked);
        $failed = CopywritingImitationLogic::normalizeBlockedKeywords($failed);
        foreach (['video', 'image'] as $type) {
            $merged[$type] = array_values(array_unique(array_merge($merged[$type], $failed[$type])));
        }
        return $merged;
    }

    /**
     * clips 全空时的失败文案。转存熔断保留原因，避免只写笼统「AI找素材失败」。
     */
    public static function clipsEmptyMaterialError(string $mode, string $error, bool $manualMode = false): string
    {
        if ($error !== '' && mb_strpos($error, '转存失败') !== false) {
            return $error;
        }
        if ($mode !== 'ai') {
            return '素材混剪必须选择素材';
        }
        // 不选人设时没有人设素材库可退，别给出做不到的建议
        $tip = $manualMode
            ? 'AI找素材失败，请稍后重试或改用数字人口播混剪'
            : 'AI找素材失败，请改用纯人设素材或稍后重试';
        // 带上上游给的真实原因：只写一句笼统失败，用户和排查的人都分不清是中台抖动还是内容被拒
        return $error === '' ? $tip : $tip . '（' . mb_substr($error, 0, 60, 'UTF-8') . '）';
    }

    /**
     * clips 空素材才整单失败；digital 降级纯口播。
     */
    public static function shouldFailClipsOnAiMaterialError(string $videoType, array $selected): bool
    {
        return $videoType === 'clips' && $selected === [];
    }

    private static function loadPersonaMaterials(int $userId, int $personaId, array $ids, ?int $frozenUseMode = null): array
    {
        if (array_key_exists('personaMaterials', self::$testHooks)) {
            return is_array(self::$testHooks['personaMaterials']) ? self::$testHooks['personaMaterials'] : [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        if ($ids === [] || $userId <= 0 || $personaId <= 0) {
            return [];
        }
        [$ownIds] = SquareAssetService::splitClipIds($ids);
        $rows = [];
        if ($ownIds !== []) {
            $rows = PersonaService::clipMaterialQuery(
                $userId,
                $personaId,
                $frozenUseMode,
                $frozenUseMode !== null
            )
                ->whereIn('id', $ownIds)
                ->field(['id', 'file_url', 'thumbnail_url', 'material_type', 'duration'])
                ->select()
                ->toArray();
        }
        $rows = array_merge($rows, SquareAssetService::clipRowsForSynthesis($userId, $personaId, $ids));
        // 按用户勾选顺序排列，而非数据库返回顺序
        $order = array_flip($ids);
        usort($rows, static fn($a, $b) => ($order[(int)$a['id']] ?? PHP_INT_MAX) <=> ($order[(int)$b['id']] ?? PHP_INT_MAX));
        return self::formatRawMaterials($rows);
    }

    public static function collectMaterialIds(array $materials, string $store = ''): array
    {
        $ids = [];
        foreach ($materials as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ($store !== '') {
                $itemStore = (string)($item['material_store'] ?? 'persona');
                if ($itemStore !== $store) {
                    continue;
                }
            }
            $id = (int)($item['material_id'] ?? $item['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    public static function stripMaterialIds(array $materials): array
    {
        $out = [];
        foreach ($materials as $item) {
            if (!is_array($item)) {
                continue;
            }
            unset($item['material_id'], $item['id'], $item['material_store']);
            $out[] = $item;
        }
        return $out;
    }

    public static function buildMaterialUseLogRows(int $userId, int $personaId, int $taskId, array $materialIds): array
    {
        $now = time();
        $logs = [];
        foreach ($materialIds as $id) {
            $id = (int)$id;
            if ($id <= 0 || SquareAssetService::isClipId($id)) {
                continue;
            }
            $logs[] = [
                'material_id' => $id,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'task_id' => $taskId,
                'publish_mode' => 1,
                'use_scene' => MaterialUseLog::USE_SCENE_AI_GENERATE,
                'use_status' => MaterialUseLog::USE_STATUS_USING,
                'create_time' => $now,
            ];
        }
        return $logs;
    }

    public static function persistMaterialUseLogs(
        int $userId,
        int $personaId,
        int $sjTaskId,
        array $materialIds,
        string $taskNo = ''
    ): array {
        if ($sjTaskId > 0) {
            self::writeMaterialUseLogs($userId, $personaId, $sjTaskId, $materialIds);
            return [];
        }
        if ($materialIds === []) {
            return [];
        }
        if ($taskNo !== '') {
            HotspotLog::write(sprintf(
                '闪剪子任务未落库，素材使用日志待补写：任务号=%s 素材数=%d',
                $taskNo,
                count($materialIds)
            ));
        }
        return ['pending_material_ids' => $materialIds];
    }

    public static function writeMaterialUseLogs(int $userId, int $personaId, int $taskId, array $materialIds): void
    {
        if ($taskId <= 0 || $personaId <= 0) {
            return;
        }
        $logs = self::buildMaterialUseLogRows($userId, $personaId, $taskId, $materialIds);
        if ($logs === []) {
            if ($materialIds !== []) {
                HotspotLog::write('素材缺少id，跳过使用日志：任务id=' . $taskId);
            }
            return;
        }
        if (array_key_exists('materialUseLogStore', self::$testHooks)) {
            $exists = 0;
            foreach (self::$testHooks['materialUseLogStore'] as $row) {
                if ((int)($row['task_id'] ?? 0) === $taskId && (int)($row['persona_id'] ?? 0) === $personaId) {
                    $exists++;
                }
            }
            if ($exists > 0) {
                return;
            }
            foreach ($logs as $log) {
                self::$testHooks['materialUseLogStore'][] = $log;
            }
            return;
        }
        $exists = (int)MaterialUseLog::where('task_id', $taskId)
            ->where('persona_id', $personaId)
            ->count();
        if ($exists > 0) {
            return;
        }
        MaterialUseLog::insertAll($logs);
        HotspotLog::write(sprintf(
            '已写入素材使用日志：任务id=%d 人设=%d 素材数=%d',
            $taskId,
            $personaId,
            count($logs)
        ));
    }

    /**
     * 原图模式下按原图硬规格筛 AI/热点库素材，不合格的丢掉不用。
     *
     * 下发前 ShanjianVideoSettingLogic::preflightMaterials 会对每条素材跑同一套规格，
     * 而且一条不过就把整个任务判失败(status=2)——不是剔素材。所以这里提前筛，
     * 宁可少几条素材，也不把不合规的带到下发环节炸整单。
     * 与自动合成链路 splitGrabbedMaterialsByOriginalSpec 同思路（那边还会降级入库，
     * 这里的 AI 素材进的是热点库、不归人设素材模式管，只做本次取舍）。
     *
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private static function filterMaterialsByOriginalSpec(array $items): array
    {
        $kept = [];
        $dropped = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = trim((string)($item['fileUrl'] ?? ''));
            if ($url === '') {
                continue;
            }
            $guard = ($item['type'] ?? '') === 'video'
                ? OriginalMaterialSpec::assertVideo(['url' => $url])
                : OriginalMaterialSpec::assertImage(['url' => $url]);
            if ($guard === true) {
                $kept[] = $item;
                continue;
            }
            $dropped[] = [
                'type' => (string)($item['type'] ?? ''),
                'reason' => (string)$guard,
                'url' => mb_substr($url, 0, 180, 'UTF-8'),
            ];
        }
        if ($dropped !== []) {
            HotspotLog::json('原图模式剔除不合规AI素材：', [
                'kept' => count($kept),
                'dropped' => $dropped,
            ], 2000);
        }
        return array_values($kept);
    }

    private static function formatRawMaterials(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fileUrl = trim((string)($row['fileUrl'] ?? $row['file_url'] ?? ''));
            if ($fileUrl === '') {
                continue;
            }
            $type = (int)($row['material_type'] ?? 0);
            $isVideo = $type === Material::MATERIAL_TYPE_VIDEO || ($row['type'] ?? '') === 'video';
            $item = [
                'type' => $isVideo ? 'video' : 'image',
                'fileUrl' => FileService::getFileUrl($fileUrl),
            ];
            $materialId = (int)($row['id'] ?? $row['material_id'] ?? 0);
            if ($materialId > 0) {
                $item['material_id'] = $materialId;
            }
            $item['material_store'] = (string)($row['material_store'] ?? 'persona');
            if (!$isVideo) {
                $item['duration'] = 2;
            } elseif (isset($row['duration']) && (float)$row['duration'] > 0) {
                $item['duration'] = (float)$row['duration'];
            }
            $cover = trim((string)($row['cover'] ?? $row['thumbnail_url'] ?? ''));
            if ($cover !== '') {
                $item['cover'] = FileService::getFileUrl($cover);
            }
            $out[] = $item;
        }
        return $out;
    }

    private static function countTypes(array $materials): array
    {
        $video = 0;
        $image = 0;
        foreach ($materials as $item) {
            if (($item['type'] ?? '') === 'video') {
                $video++;
            } else {
                $image++;
            }
        }
        return ['video' => $video, 'image' => $image];
    }

    public static function generationTypeByVideoType(string $videoType): int
    {
        return $videoType === 'digital'
            ? SynthesisTemplateConfigService::TYPE_DIGITAL_ORAL
            : SynthesisTemplateConfigService::TYPE_MATERIAL_MIXCUT;
    }

    private static function resolveSynthesisConfig(int $userId, int $personaId)
    {
        return AiPersonaSynthesisConfig::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->findOrEmpty();
    }

    private static function resolveMusicUrl(int $userId, int $personaId, $config = null): string
    {
        $config = $config ?? self::resolveSynthesisConfig($userId, $personaId);
        if ($config->isEmpty()) {
            return CopywritingImitationLogic::resolveSystemMusicUrl();
        }
        return CopywritingImitationLogic::resolveMusicUrlByConfig($config, $personaId);
    }

    private static function resolveVolume(mixed $config): float
    {
        return AiPersonaSynthesisConfig::normalizeMusicVolume(
            $config->isEmpty() ? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT : ($config->music_volume ?? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT)
        );
    }

    private static function resolveSpeechRate(mixed $config, int $generationType = 0): float
    {
        if ($config->isEmpty()) {
            return AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT;
        }
        // 按生成类型取语速，缺项回落顶层（老数据三类继承顶层）
        return AiPersonaSynthesisConfig::resolveTypeSpeechRate(
            $config,
            $generationType > 0 ? $generationType : AiPersonaSynthesisConfig::GENERATION_TYPE_DIGITAL_HUMAN
        );
    }

    private static function summarizeMaterials(array $materials): array
    {
        $out = [];
        foreach (array_slice($materials, 0, 20) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $out[] = [
                'type' => (string)($item['type'] ?? ''),
                'fileUrl' => HotspotLog::clip((string)($item['fileUrl'] ?? ''), 120),
                'duration' => $item['duration'] ?? null,
            ];
        }
        if (count($materials) > 20) {
            $out[] = ['type' => 'more', 'fileUrl' => '另有' . (count($materials) - 20) . '条未展开'];
        }
        return $out;
    }

    public static function resolveResultUrl(mixed $sjTask): string
    {
        if (is_object($sjTask) && method_exists($sjTask, 'getData')) {
            $raw = $sjTask->getData('video_result_url');
            if (is_string($raw) || is_numeric($raw)) {
                return trim((string)$raw);
            }
        }
        if (is_array($sjTask)) {
            return trim((string)($sjTask['video_result_url'] ?? ''));
        }
        return trim((string)($sjTask->video_result_url ?? ''));
    }

    public static function composeDisplayTitle(string $title, int $maxLen = self::TASK_NAME_MAX): string
    {
        $prefix = self::NAME_PREFIX;
        $middle = trim($title);
        if (str_starts_with($middle, $prefix)) {
            $middle = trim(mb_substr($middle, mb_strlen($prefix)));
        }
        $middle = (string)preg_replace('/-\d{14}$/u', '', $middle);
        $middle = trim($middle, "- \t\n\r\0\x0B");
        if ($middle === '') {
            $middle = '热点视频';
        }
        return mb_substr($middle, 0, max(1, $maxLen));
    }

    public static function composeRecordName(string $title, int $maxLen, ?string $datetime = null): string
    {
        $prefix = self::NAME_PREFIX;
        $stamp = $datetime !== null && preg_match('/^\d{14}$/', $datetime)
            ? $datetime
            : date('YmdHis');
        $suffix = '-' . $stamp;
        $maxLen = max(1, $maxLen);
        $reserved = mb_strlen($prefix) + mb_strlen($suffix);
        if ($maxLen < $reserved) {
            return mb_substr($prefix . $stamp, 0, $maxLen);
        }

        $middle = self::composeDisplayTitle($title, $maxLen - $reserved);
        return $prefix . $middle . $suffix;
    }

    public static function recoverExistingIfAny(string $taskNo, int $userId): ?array
    {
        $task = TaskService::detail($taskNo, $userId);
        if ($task === null) {
            return null;
        }
        $recovered = self::bindExistingShanjian($task, $userId);
        if ($recovered === null) {
            return null;
        }
        return TaskService::patchOptions($taskNo, [
            'dispatch_status' => 'done',
        ], (int)($task['options']['retry_seq'] ?? 0)) ?? $recovered;
    }

    private static function bindExistingShanjian(array $task, int $userId): ?array
    {
        $taskNo = (string)($task['id'] ?? '');
        if (!TaskService::isValidTaskNo($taskNo)) {
            return null;
        }
        $options = is_array($task['options'] ?? null) ? $task['options'] : [];
        $retrySeq = (int)($options['retry_seq'] ?? 0);
        $boundSettingId = (int)($options['shanjian_setting_id'] ?? 0);
        if ($boundSettingId > 0) {
            if (ScriptService::shouldRedispatchEmptyPersonaMaterials(
                $options,
                self::settingMaterialById($boundSettingId)
            )) {
                HotspotLog::write(sprintf(
                    '已绑定闪剪设置但素材为空，忽略复用并重新下发：任务号=%s 设置id=%d',
                    $taskNo,
                    $boundSettingId
                ));
                TaskService::clearShanjianBinding($taskNo, $retrySeq);
                return null;
            }
            HotspotLog::write(sprintf(
                '跳过重复下发：任务号=%s 已绑定闪剪设置id=%d',
                $taskNo,
                $boundSettingId
            ));
            return $task;
        }
        $found = self::findExistingShanjianBinding($taskNo, $retrySeq);
        if ($found === null) {
            return null;
        }
        HotspotLog::write(sprintf(
            '复用已有闪剪订单，不再扣费：任务号=%s 设置id=%d 闪剪任务id=%d 重试序号=%d 用户=%d',
            $taskNo,
            (int)$found['shanjian_setting_id'],
            (int)$found['shanjian_task_id'],
            $retrySeq,
            $userId
        ));
        return TaskService::bindShanjian($taskNo, $found) ?? $task;
    }

    private static function findExistingShanjianBinding(string $taskNo, int $retrySeq): ?array
    {
        $needle = '"hotspot_task_no":"' . $taskNo . '"';
        $tasks = ShanjianVideoTask::whereLike('extra', '%' . $needle . '%')
            ->order('id', 'desc')
            ->limit(20)
            ->select();
        foreach ($tasks as $sjTask) {
            $extra = self::taskExtra($sjTask);
            if (!self::isCurrentHotspotExtra($extra, $taskNo, $retrySeq)) {
                continue;
            }
            $settingId = (int)($sjTask->video_setting_id ?? 0);
            if ($settingId <= 0) {
                continue;
            }
            return [
                'shanjian_setting_id' => $settingId,
                'shanjian_task_id' => (int)$sjTask->id,
                'source' => self::SOURCE,
            ];
        }

        $settings = ShanjianVideoSetting::whereLike('extra', '%' . $needle . '%')
            ->order('id', 'desc')
            ->limit(20)
            ->select();
        foreach ($settings as $setting) {
            $extra = self::parseJsonArray($setting->extra ?? []);
            if (!self::isCurrentHotspotExtra($extra, $taskNo, $retrySeq)) {
                continue;
            }
            $settingId = (int)($setting->id ?? 0);
            if ($settingId <= 0) {
                continue;
            }
            $sjTask = ShanjianVideoTask::where('video_setting_id', $settingId)->order('id', 'asc')->findOrEmpty();
            return [
                'shanjian_setting_id' => $settingId,
                'shanjian_task_id' => $sjTask->isEmpty() ? 0 : (int)$sjTask->id,
                'source' => self::SOURCE,
            ];
        }
        return null;
    }

    private static function isCurrentHotspotExtra(array $extra, string $taskNo, int $retrySeq): bool
    {
        if (($extra['source'] ?? '') !== self::SOURCE) {
            return false;
        }
        if (trim((string)($extra['hotspot_task_no'] ?? '')) !== $taskNo) {
            return false;
        }
        return (int)($extra['retry_seq'] ?? 0) === $retrySeq;
    }

    private static function parseJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private static function stampShanjianTask(
        object $sjTask,
        string $taskNo,
        int $personaId,
        string $title = '',
        int $retrySeq = 0
    ): void {
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['stamped'][] = [
                'task_no' => $taskNo,
                'retry_seq' => $retrySeq,
                'sj_id' => (int)($sjTask->id ?? 0),
            ];
            return;
        }
        $nameSeed = trim($title);
        if ($nameSeed === '') {
            $nameSeed = trim((string)($sjTask->title ?? ''));
        }
        $sjTask->title = self::composeDisplayTitle($nameSeed, self::TASK_NAME_MAX);
        $sjTask->name = self::composeRecordName($nameSeed, self::TASK_NAME_MAX);
        $extra = self::taskExtra($sjTask);
        $extra['source'] = self::SOURCE;
        $extra['create_type'] = self::SOURCE;
        $extra['hotspot_task_no'] = $taskNo;
        $extra['retry_seq'] = $retrySeq;
        $sjTask->extra = $extra;
        $sjTask->save();
        if ($personaId > 0) {
            try {
                $sjTask->persona_id = $personaId;
                $sjTask->save();
            } catch (\Throwable $e) {
                HotspotLog::write('闪剪任务写入人设id跳过：' . $e->getMessage());
            }
        }
    }

    private static function taskExtra(mixed $sjTask): array
    {
        $extra = $sjTask->extra ?? [];
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            $extra = is_array($decoded) ? $decoded : [];
        }
        return is_array($extra) ? $extra : [];
    }
}
