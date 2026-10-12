<?php

namespace app\common\service\aiPersona;

use app\api\logic\aiPersona\PublishLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\CloudSvDevice;
use think\facade\Cache;
use think\facade\Log;

/**
 * 无手机模式成片成功后生成发布文案，写入 extra 的 publish_title / publish_content / publish_topic。
 * 固定 AI 生成，不走文案库、不走自定义。失败只记日志，不影响成片回调。
 */
class CloudPublishCopyService
{
    public const LOCK_TTL = 60;
    public const LOCK_KEY_PREFIX = 'cloud_publish_copy:';

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
        self::$testHooks['generateCalls'] = self::$testHooks['generateCalls'] ?? [];
        self::$testHooks['skipCalls'] = self::$testHooks['skipCalls'] ?? [];
        self::$testHooks['persistCalls'] = self::$testHooks['persistCalls'] ?? [];
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    public static function ensureAfterSuccess(mixed $task): void
    {
        try {
            self::generate($task);
        } catch (\Throwable $e) {
            Log::channel('ipVideoSynthesis')->write(
                '无手机发布文案生成失败：' . $e->getMessage()
            );
        }
    }

    private static function generate(mixed $task): void
    {
        if (!is_object($task)) {
            return;
        }
        if (!self::passesGate($task)) {
            return;
        }

        $lockKey = self::LOCK_KEY_PREFIX . self::lockIdentity($task);
        $lockState = self::tryLock($lockKey);
        if ($lockState === 0) {
            self::recordSkip('lock_held');
            Log::channel('ipVideoSynthesis')->write(
                '无手机发布文案跳过：未获取到生成锁 task_id=' . self::lockIdentity($task)
            );
            return;
        }

        try {
            self::refreshTask($task);
            $extra = self::taskExtra($task);
            $existing = ShanjianVideoTask::readPublishCopy($extra);
            if ($existing['title'] !== '' || $existing['content'] !== '') {
                self::recordSkip('already');
                Log::channel('ipVideoSynthesis')->write(
                    '无手机发布文案已存在，跳过生成：task_id=' . self::lockIdentity($task)
                );
                return;
            }

            $persona = self::resolvePersona((int)($task->persona_id ?? 0));
            if ($persona === null) {
                self::recordSkip('persona');
                return;
            }

            $oralTitle = trim((string)($task->title ?? ''));
            $oralMsg = trim((string)($task->msg ?? ''));
            $personaName = trim((string)($persona->persona_name ?? ''));
            $keywords = $oralMsg !== '' ? $oralMsg : ($oralTitle !== '' ? $oralTitle : $personaName);
            if ($keywords === '') {
                self::recordSkip('empty_keywords');
                Log::channel('ipVideoSynthesis')->write(
                    '无手机发布文案跳过：口播标题与人设名均为空 task_id=' . self::lockIdentity($task)
                );
                return;
            }

            $copyTaskId = trim((string)($task->task_id ?? ''));
            if ($copyTaskId === '') {
                $copyTaskId = function_exists('generate_unique_task_id')
                    ? generate_unique_task_id()
                    : ('CLOUDPUB_' . date('YmdHis') . mt_rand(1000, 9999));
            }

            $userId = (int)($task->user_id ?? 0);
            self::recordHook('generateCalls', [
                'user_id' => $userId,
                'keywords' => $keywords,
                'task_id' => $copyTaskId,
            ]);
            try {
                $response = self::requestAi($userId, $keywords, $copyTaskId);
            } catch (\Throwable $e) {
                Log::channel('ipVideoSynthesis')->write(
                    '无手机发布文案AI异常：task_id=' . self::lockIdentity($task) . ' ' . $e->getMessage()
                );
                $response = ['code' => 0, 'msg' => $e->getMessage()];
            }
            $aiCopy = self::copyFromResponse($response);
            if ($aiCopy !== null) {
                self::persist($task, $extra, $aiCopy['title'], $aiCopy['content'], $aiCopy['topic']);
                Log::channel('ipVideoSynthesis')->write(sprintf(
                    '无手机发布文案生成完成：task_id=%s 标题=%s 正文字数=%d 话题=%s',
                    self::lockIdentity($task),
                    $aiCopy['title'],
                    mb_strlen($aiCopy['content']),
                    $aiCopy['topic']
                ));
                return;
            }

            if ($oralTitle === '' && $oralMsg === '') {
                Log::channel('ipVideoSynthesis')->write(
                    '无手机发布文案AI失败且无口播可回落：task_id=' . self::lockIdentity($task)
                    . ' msg=' . mb_substr((string)($response['msg'] ?? $response['message'] ?? ''), 0, 200)
                );
                return;
            }

            self::persist($task, $extra, $oralTitle, $oralMsg, '');
            Log::channel('ipVideoSynthesis')->write(
                '无手机发布文案AI失败，已回落口播：task_id=' . self::lockIdentity($task)
                . ' msg=' . mb_substr((string)($response['msg'] ?? $response['message'] ?? '生成失败'), 0, 200)
            );
        } finally {
            if ($lockState === 1) {
                self::releaseLock($lockKey);
            }
        }
    }

    private static function passesGate(object $task): bool
    {
        if ((int)($task->status ?? -1) !== ShanjianVideoTask::STATUS_SUCCESS) {
            self::recordSkip('status');
            return false;
        }
        $deviceCode = (string)($task->device_code ?? '');
        if (!CloudSvDevice::isCloudCode($deviceCode)) {
            self::recordSkip('device');
            return false;
        }
        if ((int)($task->auto_type ?? 0) !== 1) {
            self::recordSkip('auto_type');
            return false;
        }
        if ((int)($task->wechat_type ?? 0) !== 0) {
            self::recordSkip('wechat_type');
            return false;
        }
        if ((int)($task->persona_id ?? 0) <= 0) {
            self::recordSkip('persona_id');
            return false;
        }
        if ((int)($task->is_final ?? 1) !== 1) {
            self::recordSkip('is_final');
            return false;
        }
        if ((int)($task->origin_task_id ?? 0) > 0) {
            self::recordSkip('origin_task_id');
            return false;
        }
        return true;
    }

    private static function resolvePersona(int $personaId): ?AiPersona
    {
        if (array_key_exists('resolvePersona', self::$testHooks)) {
            $hook = self::$testHooks['resolvePersona'];
            if (is_callable($hook)) {
                $persona = $hook($personaId);
            } else {
                $persona = $hook;
            }
            if ($persona instanceof AiPersona) {
                return self::personaUsable($persona) ? $persona : null;
            }
            return null;
        }
        if ($personaId <= 0) {
            return null;
        }
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            Log::channel('ipVideoSynthesis')->write('无手机发布文案跳过：人设不存在 persona_id=' . $personaId);
            return null;
        }
        if (!self::personaUsable($persona)) {
            Log::channel('ipVideoSynthesis')->write(sprintf(
                '无手机发布文案跳过：人设不可用 persona_id=%d status=%s run_mode=%s',
                $personaId,
                (string)($persona->status ?? ''),
                (string)($persona->run_mode ?? '')
            ));
            return null;
        }
        return $persona;
    }

    private static function personaUsable(AiPersona $persona): bool
    {
        if ((int)($persona->status ?? 0) !== AiPersona::STATUS_ENABLED) {
            return false;
        }
        return AiPersona::normalizeRunMode($persona->run_mode ?? AiPersona::RUN_MODE_PHONE)
            === AiPersona::RUN_MODE_CLOUD;
    }

    private static function requestAi(int $userId, string $keywords, string $taskId): array
    {
        if (array_key_exists('generatePublishContentByAi', self::$testHooks)) {
            $hook = self::$testHooks['generatePublishContentByAi'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            if (is_callable($hook)) {
                $response = $hook($userId, $keywords, $taskId);
                return is_array($response) ? $response : ['code' => 0, 'msg' => '生成失败'];
            }
            if ($hook === false) {
                return ['code' => 0, 'msg' => '生成失败'];
            }
            return is_array($hook) ? $hook : ['code' => 0, 'msg' => '生成失败'];
        }
        return PublishLogic::generatePublishContentByAi($userId, $keywords, $taskId);
    }

    /**
     * @return array{title:string,content:string,topic:string}|null
     */
    private static function copyFromResponse(array $response): ?array
    {
        if ((int)($response['code'] ?? 0) !== 10000) {
            return null;
        }
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        if (!empty($data['empty_copy'])) {
            return null;
        }
        $title = trim((string)($data['title'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));
        $topic = ShanjianVideoTask::stringifyTopic($data['tag'] ?? '');
        if ($title === '' && $content === '' && $topic === '') {
            return null;
        }
        return [
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
        ];
    }

    private static function persist(object $task, array $extra, string $title, string $content, string $topic): void
    {
        $merged = ShanjianVideoTask::mergePublishCopy($extra, $title, $content, $topic);
        self::recordHook('persistCalls', [
            'task_id' => self::lockIdentity($task),
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
        ]);
        if (!empty(self::$testHooks['skipPersist'])) {
            $task->extra = $merged;
            return;
        }
        $task->extra = $merged;
        if (method_exists($task, 'save')) {
            $task->save();
        }
    }

    private static function taskExtra(object $task): array
    {
        $extra = $task->extra ?? [];
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            $extra = is_array($decoded) ? $decoded : [];
        }
        return is_array($extra) ? $extra : [];
    }

    private static function refreshTask(object $task): void
    {
        if (!empty(self::$testHooks['skipRefresh'])) {
            return;
        }
        if (!method_exists($task, 'refresh') || (int)($task->id ?? 0) <= 0) {
            return;
        }
        try {
            $task->refresh();
        } catch (\Throwable $e) {
            // 刷新失败仍用当前对象继续，避免丢掉这次生成
        }
    }

    private static function lockIdentity(object $task): string
    {
        $taskId = trim((string)($task->task_id ?? ''));
        if ($taskId !== '') {
            return $taskId;
        }
        return (string)((int)($task->id ?? 0));
    }

    /**
     * 1=持锁 0=已被占用 -1=Redis 异常，无锁继续
     */
    private static function tryLock(string $lockKey): int
    {
        if (array_key_exists('acquireLock', self::$testHooks)) {
            $hook = self::$testHooks['acquireLock'];
            if (is_callable($hook)) {
                return (int)$hook($lockKey);
            }
            return (int)$hook;
        }
        try {
            $redis = Cache::store('concurrent_redis')->handler();
            $fullKey = (string)config('cache.stores.concurrent_redis.prefix', '') . $lockKey;
            if (!$redis->setnx($fullKey, 1)) {
                return 0;
            }
            $redis->expire($fullKey, self::LOCK_TTL);
            return 1;
        } catch (\Throwable $e) {
            Log::channel('ipVideoSynthesis')->write('无手机发布文案锁失败，无锁继续：' . $e->getMessage());
            return -1;
        }
    }

    private static function releaseLock(string $lockKey): void
    {
        if (array_key_exists('acquireLock', self::$testHooks)) {
            return;
        }
        try {
            ShanjianVideoTaskLogic::releaseRedisLock($lockKey);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private static function recordSkip(string $reason): void
    {
        self::recordHook('skipCalls', ['reason' => $reason]);
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
}
