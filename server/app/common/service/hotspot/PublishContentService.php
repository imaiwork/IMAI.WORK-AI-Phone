<?php

namespace app\common\service\hotspot;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\service\ToolsService;

/**
 * 热点成片后的发布文案：用口播 msg 作关键词请求 getPublishContent，不覆盖 msg。
 */
class PublishContentService
{
    public const OPTION_KEY = 'publish_copywriting';

    public const TOKEN_SCENE = 'coze_publish_content_generated';

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
        self::$testHooks['requestCalls'] = self::$testHooks['requestCalls'] ?? [];
        self::$testHooks['deductCalls'] = self::$testHooks['deductCalls'] ?? [];
        self::$testHooks['persistCalls'] = self::$testHooks['persistCalls'] ?? [];
        self::$testHooks['skipCalls'] = self::$testHooks['skipCalls'] ?? [];
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    /**
     * 闪剪回写热点完成后生成发布标题/正文/话题。失败只记日志，不影响成片。
     */
    public static function ensureAfterDone(mixed $sjTask, string $taskNo, int $retrySeq): void
    {
        try {
            self::generate($sjTask, $taskNo, $retrySeq);
        } catch (\Throwable $e) {
            HotspotLog::exception('热点发布文案生成失败', $e);
        }
    }

    public static function apiFields(mixed $copy): array
    {
        $normalized = self::normalize($copy);
        $exposed = $normalized;
        $exposed['retry_seq'] = (int)($normalized['retry_seq'] ?? 0);
        return [
            'publish_title' => $normalized['title'],
            'publish_content' => $normalized['content'],
            'publish_tag' => $normalized['tag'],
            'publish_copywriting' => $exposed,
        ];
    }

    public static function normalize(mixed $raw): array
    {
        $data = is_array($raw) ? $raw : [];
        return [
            'title' => trim((string)($data['title'] ?? '')),
            'content' => trim((string)($data['content'] ?? '')),
            'tag' => trim((string)($data['tag'] ?? '')),
            'from_ai' => (int)($data['from_ai'] ?? 0),
            'retry_seq' => array_key_exists('retry_seq', $data) ? (int)$data['retry_seq'] : null,
            'task_id' => trim((string)($data['task_id'] ?? '')),
        ];
    }

    public static function hasCopywriting(array $copy): bool
    {
        return $copy['title'] !== '' || $copy['content'] !== '' || $copy['tag'] !== '';
    }

    private static function generate(mixed $sjTask, string $taskNo, int $retrySeq): void
    {
        $userId = (int)($sjTask->user_id ?? 0);
        $msg = trim((string)($sjTask->msg ?? ''));
        if ($msg === '') {
            HotspotLog::write('热点发布文案跳过：口播文案为空 任务号=' . $taskNo);
            return;
        }
        if ($userId <= 0) {
            HotspotLog::write('热点发布文案跳过：用户为空 任务号=' . $taskNo);
            return;
        }

        $existing = self::findExisting($sjTask, $taskNo, $retrySeq);
        if ($existing !== null) {
            self::recordHook('skipCalls', [
                'task_no' => $taskNo,
                'retry_seq' => $retrySeq,
                'reason' => 'already',
            ]);
            HotspotLog::write(sprintf(
                '热点发布文案已存在，跳过生成：任务号=%s 重试序号=%d',
                $taskNo,
                $retrySeq
            ));
            return;
        }

        $unit = self::checkToken($userId);
        $taskId = function_exists('generate_unique_task_id')
            ? generate_unique_task_id()
            : ('HOTPUB_' . date('YmdHis') . mt_rand(1000, 9999));
        $params = [
            'keywords' => $msg,
            'task_id' => $taskId,
            'source' => 'shanjian2',
            'user_id' => $userId,
        ];
        self::recordHook('requestCalls', $params);
        $response = self::requestPublishContent($params);
        if ((int)($response['code'] ?? 0) !== 10000) {
            HotspotLog::write(sprintf(
                '热点发布文案上游失败：任务号=%s code=%s msg=%s',
                $taskNo,
                (string)($response['code'] ?? ''),
                mb_substr((string)($response['msg'] ?? $response['message'] ?? '生成失败'), 0, 200)
            ));
            return;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $copy = self::normalize([
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'tag' => $data['tag'] ?? '',
            'from_ai' => 1,
            'retry_seq' => $retrySeq,
            'task_id' => $taskId,
        ]);
        if (!self::hasCopywriting($copy)) {
            HotspotLog::write('热点发布文案上游成功但标题正文话题均为空：任务号=' . $taskNo);
            return;
        }

        self::deduct($userId, $taskId, (float)$unit);
        self::persist($sjTask, $taskNo, $retrySeq, $copy);
        HotspotLog::write(sprintf(
            '热点发布文案生成完成：任务号=%s 标题=%s 正文字数=%d 话题=%s',
            $taskNo,
            $copy['title'],
            mb_strlen($copy['content']),
            $copy['tag']
        ));
    }

    private static function findExisting(mixed $sjTask, string $taskNo, int $retrySeq): ?array
    {
        $current = TaskService::detail($taskNo);
        $fromColumns = self::normalize([
            'title' => $current['publish_title'] ?? '',
            'content' => $current['publish_content'] ?? '',
            'tag' => $current['publish_tag'] ?? '',
        ]);
        if (self::hasCopywriting($fromColumns)) {
            return $fromColumns;
        }
        $fromOptions = self::normalize(
            is_array($current['options'][self::OPTION_KEY] ?? null)
                ? $current['options'][self::OPTION_KEY]
                : []
        );
        if (self::usable($fromOptions, $retrySeq)) {
            return $fromOptions;
        }

        $fromExtra = self::normalize(self::taskExtra($sjTask)[self::OPTION_KEY] ?? []);
        if (self::usable($fromExtra, $retrySeq)) {
            return $fromExtra;
        }
        return null;
    }

    private static function usable(array $copy, int $retrySeq): bool
    {
        if (!self::hasCopywriting($copy)) {
            return false;
        }
        if ($copy['retry_seq'] === null) {
            return $retrySeq === 0;
        }
        return (int)$copy['retry_seq'] === $retrySeq;
    }

    private static function checkToken(int $userId): float
    {
        if (array_key_exists('checkToken', self::$testHooks)) {
            $hook = self::$testHooks['checkToken'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            return (float)$hook;
        }
        return (float)TokenLogService::checkToken($userId, self::TOKEN_SCENE);
    }

    private static function requestPublishContent(array $params): array
    {
        HotspotLog::json('发布文案调用getPublishContent请求参数：', HotspotLog::safe($params), 3000);
        if (array_key_exists('getPublishContent', self::$testHooks)) {
            $hook = self::$testHooks['getPublishContent'];
            if (is_callable($hook)) {
                $response = $hook($params);
                $response = is_array($response) ? $response : ['code' => 0];
                HotspotLog::json('发布文案调用getPublishContent返回值：', HotspotLog::safe($response), 3000);
                return $response;
            }
            if ($hook === false) {
                $response = ['code' => 0, 'msg' => '生成失败'];
                HotspotLog::json('发布文案调用getPublishContent返回值：', HotspotLog::safe($response), 3000);
                return $response;
            }
            $response = is_array($hook) ? $hook : ['code' => 0];
            HotspotLog::json('发布文案调用getPublishContent返回值：', HotspotLog::safe($response), 3000);
            return $response;
        }
        try {
            $raw = ToolsService::Sv()->getPublishContent($params);
            $response = is_array($raw) ? $raw : ['code' => 0, 'msg' => '返回值非数组'];
        } catch (\Throwable $e) {
            HotspotLog::exception('发布文案调用getPublishContent异常', $e);
            throw $e;
        }
        HotspotLog::json('发布文案调用getPublishContent返回值：', HotspotLog::safe($response), 3000);
        return $response;
    }

    private static function deduct(int $userId, string $taskId, float $points): void
    {
        self::recordHook('deductCalls', [
            'user_id' => $userId,
            'task_id' => $taskId,
            'points' => $points,
        ]);
        if (array_key_exists('getPublishContent', self::$testHooks) || !empty(self::$testHooks['skipCharge'])) {
            return;
        }
        if ($points <= 0) {
            return;
        }
        $tokenCode = AccountLogEnum::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED;
        $extra = ['算力单价' => $points . '算力/条', '实际消耗算力' => $points, '场景' => '热点追踪-发布文案AI生成'];
        User::userTokensChange($userId, $points);
        AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
    }

    private static function persist(mixed $sjTask, string $taskNo, int $retrySeq, array $copy): void
    {
        self::recordHook('persistCalls', [
            'task_no' => $taskNo,
            'retry_seq' => $retrySeq,
            'copy' => $copy,
        ]);
        TaskService::savePublishCopywriting($taskNo, $copy, $retrySeq);
        if (!empty(self::$testHooks['skipPersist'])) {
            return;
        }
        if (!is_object($sjTask)) {
            return;
        }
        $extra = self::taskExtra($sjTask);
        $extra[self::OPTION_KEY] = $copy;
        $sjTask->extra = $extra;
        if (method_exists($sjTask, 'save')) {
            $sjTask->save();
        }
    }

    private static function taskExtra(mixed $sjTask): array
    {
        $extra = is_object($sjTask) ? ($sjTask->extra ?? []) : [];
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            $extra = is_array($decoded) ? $decoded : [];
        }
        return is_array($extra) ? $extra : [];
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
