<?php

namespace app\common\service\hotspot;

use app\api\logic\ApiLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\human\HumanVoice;
use app\common\Jobs\HotspotVideoDispatchJob;
use app\common\model\hotspot\HotspotTask;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use app\common\service\FileService;
use think\facade\Queue;

class VideoService
{
    public const SOURCE = 'hotspot';
    public const NAME_PREFIX = '热点追踪-';
    public const SETTING_NAME_MAX = 50;
    public const TASK_NAME_MAX = 200;
    public const DISPATCH_LOCK_TTL = 480;
    public const AI_MATERIAL_KEYWORD_BUDGET_SEC = 25;
    public const AI_MATERIAL_FETCH_BUDGET_MIN = 60;
    public const AI_MATERIAL_FETCH_BUDGET_MAX = 90;
    public const AI_MATERIAL_TRANSCODE_SEC = 15;
    public const AI_MATERIAL_BUDGET = [
        'max_elapsed_sec' => 60,
        'max_candidates_per_keyword' => 2,
        'max_transcode_fail' => 3,
        'max_transcode_sec' => 15,
    ];

    /**
     * 抓取阶段墙钟（不含抽词）。clips 大缺口 90s，digital/小缺口 60s。
     */
    public static function aiMaterialFetchBudgetSec(int $needV, int $needI): int
    {
        $needV = max(0, $needV);
        $needI = max(0, $needI);
        $budget = $needV >= 6
            ? self::AI_MATERIAL_FETCH_BUDGET_MAX
            : self::AI_MATERIAL_FETCH_BUDGET_MIN;
        if ($needV + $needI <= 0) {
            return self::AI_MATERIAL_FETCH_BUDGET_MIN;
        }
        return max(self::AI_MATERIAL_FETCH_BUDGET_MIN, min(self::AI_MATERIAL_FETCH_BUDGET_MAX, $budget));
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
            HotspotLog::write('原单不存在，回退新建：任务号=' . $taskNo);
            return 'need_dispatch';
        }

        $sjTask = self::lookupShanjianTask($sjId);
        if ($sjTask === null || (method_exists($sjTask, 'isEmpty') && $sjTask->isEmpty())) {
            HotspotLog::write(sprintf('原闪剪单已删除，回退新建：任务号=%s 闪剪id=%d', $taskNo, $sjId));
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

        $persona = is_array($task['persona'] ?? null) ? $task['persona'] : [];
        if (PersonaService::hasIdentity($persona)) {
            $persona = PersonaService::resolve($userId, $persona);
        }
        $personaId = (int)($persona['id'] ?? 0);
        if ($personaId <= 0) {
            throw new HotspotUpstreamException('人设不能为空');
        }

        $avatar = [];
        $voiceId = '';
        $modelVersion = 0;
        $pic = '';
        if ($opt['video_type'] === 'digital') {
            $avatar = self::resolveAvatar($userId, $personaId, $opt);
            $voiceId = (string)$avatar['voice_id'];
            $pic = (string)$avatar['pic'];
        } else {
            $voiceId = self::resolveClipVoice($userId, $personaId);
        }
        $modelVersion = self::resolveVoiceModelVersion($voiceId, $userId);

        $opt['platform'] = (string)($task['platform'] ?? '');
        $materials = self::resolveMaterials($userId, $personaId, $opt, $script);
        if ($opt['video_type'] === 'clips' && $materials === []) {
            throw new HotspotUpstreamException('素材混剪必须选择素材');
        }
        $materialIds = self::collectMaterialIds($materials, 'persona');
        $svMaterialIds = self::collectMaterialIds($materials, 'sv_media');
        $payloadMaterials = self::stripMaterialIds($materials);

        $synthesisConfig = self::resolveSynthesisConfig($userId, $personaId);
        $musicUrl = self::resolveMusicUrl($userId, $personaId, $synthesisConfig);
        $volume = self::resolveVolume($synthesisConfig);
        $generationType = self::generationTypeByVideoType($opt['video_type']);
        $templateConfig = $synthesisConfig->isEmpty() ? [] : ($synthesisConfig->template_config ?? []);
        try {
            $clipId = SynthesisTemplateConfigService::pickTemplateId($templateConfig, $generationType, 1);
        } catch (\Throwable $e) {
            throw new HotspotUpstreamException($e->getMessage() ?: '视频模板选取失败');
        }
        $retrySeq = (int)($options['retry_seq'] ?? 0);
        $extra = [
            'video_count' => 1,
            'volume' => $volume,
            'source' => self::SOURCE,
            'create_type' => self::SOURCE,
            'hotspot_task_no' => $taskNo,
            'retry_seq' => $retrySeq,
            'ai_music' => $musicUrl === '',
        ];

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
            $params['character_design'] = [[
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
            'persona_id' => $personaId,
            'persona_name' => (string)($persona['name'] ?? ''),
            'video_type' => $opt['video_type'],
            'shanjian_type' => $params['shanjian_type'],
            'material_mode' => $opt['material_mode'],
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
        $logPatch = self::persistMaterialUseLogs($userId, $personaId, $sjTaskId, $materialIds, $taskNo);
        $patch = array_merge($patch, $logPatch);
        if ($svMaterialIds !== []) {
            $patch['sv_material_ids'] = $svMaterialIds;
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

    public static function materialQuota(string $videoType): array
    {
        return $videoType === 'clips'
            ? ['v_min' => 8, 'v_max' => 8, 'i_min' => 2, 'i_max' => 3]
            : ['v_min' => 2, 'v_max' => 3, 'i_min' => 2, 'i_max' => 3];
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

    public static function trimMaterialsToTargets(array $selected, int $targetV, int $targetI): array
    {
        $videos = [];
        $images = [];
        foreach ($selected as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') === 'video') {
                if (count($videos) < $targetV) {
                    $videos[] = $item;
                }
            } elseif (count($images) < $targetI) {
                $images[] = $item;
            }
        }
        return array_values(array_merge($videos, $images));
    }

    private static function resolveMaterials(int $userId, int $personaId, array $opt, string $script): array
    {
        $mode = (string)($opt['material_mode'] ?? 'ai_persona');
        $selected = $mode === 'ai' ? [] : self::loadPersonaMaterials($userId, $personaId, $opt['materials'] ?? []);
        $quota = self::materialQuota((string)($opt['video_type'] ?? ''));
        $targets = self::pickMaterialTargets($quota);
        $targetV = (int)$targets['video'];
        $targetI = (int)$targets['image'];

        if (self::shouldFillAiMaterials($mode)) {
            // 用户已选的人设素材全量保留，配额只用于计算 AI 需要补齐的缺口；
            // 此前先按配额裁剪用户选择，会把多选的素材直接丢掉，后台/成片都看不到用户选的素材
            $counts = self::countTypes($selected);
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
                        (string)($opt['platform'] ?? '')
                    );
                    $ai = self::formatRawMaterials(is_array($aiV) ? $aiV : []);
                    $ai = array_merge($ai, self::formatRawMaterials(is_array($aiI) ? $aiI : []));
                    // 只裁剪 AI 补来的部分，不动用户已选素材
                    $ai = self::trimMaterialsToTargets(
                        ShanjianVideoSettingLogic::trimMaterialsByDuration($ai),
                        $needV,
                        $needI
                    );
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
                        throw new HotspotUpstreamException(self::clipsEmptyMaterialError($mode, $e->getMessage()));
                    }
                    if ($opt['video_type'] === 'digital') {
                        HotspotLog::write('数字人口播AI找素材失败，降级为纯口播');
                    }
                }
            }
        }

        if ($opt['video_type'] === 'clips' && $mode === 'ai' && $selected === []) {
            throw new HotspotUpstreamException(self::clipsEmptyMaterialError($mode, ''));
        }

        return array_values($selected);
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
        string $platform = ''
    ): array {
        self::recordHook('getAiMaterialsCalls', [
            'script' => $script,
            'needV' => $needV,
            'needI' => $needI,
            'user_id' => $userId,
            'persona_id' => $personaId,
            'material_store' => 'sv_media',
            'platform' => $platform,
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
            array_merge(self::AI_MATERIAL_BUDGET, [
                'max_elapsed_sec' => self::aiMaterialFetchBudgetSec($needV, $needI),
                'max_keyword_elapsed_sec' => self::AI_MATERIAL_KEYWORD_BUDGET_SEC,
                'max_transcode_sec' => self::AI_MATERIAL_TRANSCODE_SEC,
                'material_store' => 'sv_media',
                'platform' => $platform,
            ])
        );
    }

    /**
     * clips 全空时的失败文案。转存熔断保留原因，避免只写笼统「AI找素材失败」。
     */
    public static function clipsEmptyMaterialError(string $mode, string $error): string
    {
        if ($error !== '' && mb_strpos($error, '转存失败') !== false) {
            return $error;
        }
        return $mode === 'ai' ? 'AI找素材失败，请改用纯人设素材或稍后重试' : '素材混剪必须选择素材';
    }

    /**
     * clips 空素材才整单失败；digital 降级纯口播。
     */
    public static function shouldFailClipsOnAiMaterialError(string $videoType, array $selected): bool
    {
        return $videoType === 'clips' && $selected === [];
    }

    private static function loadPersonaMaterials(int $userId, int $personaId, array $ids): array
    {
        if (array_key_exists('personaMaterials', self::$testHooks)) {
            return is_array(self::$testHooks['personaMaterials']) ? self::$testHooks['personaMaterials'] : [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        if ($ids === [] || $userId <= 0 || $personaId <= 0) {
            return [];
        }
        $rows = Material::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
            ->whereIn('material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE])
            ->where('use_status', Material::USE_STATUS_ENABLED)
            ->whereIn('id', $ids)
            ->where(function ($query) {
                $query->where('source_type', '<>', 'slice')
                    ->whereOr('slice_status', Material::SLICE_STATUS_SUCCESS);
            })
            ->field(['id', 'file_url', 'thumbnail_url', 'material_type'])
            ->select()
            ->toArray();
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
            if ($id <= 0) {
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

    private static function resolveVolume($config): float
    {
        return AiPersonaSynthesisConfig::normalizeMusicVolume(
            $config->isEmpty() ? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT : ($config->music_volume ?? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT)
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
        if ((int)($options['shanjian_setting_id'] ?? 0) > 0) {
            HotspotLog::write(sprintf(
                '跳过重复下发：任务号=%s 已绑定闪剪设置id=%d',
                $taskNo,
                (int)$options['shanjian_setting_id']
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
