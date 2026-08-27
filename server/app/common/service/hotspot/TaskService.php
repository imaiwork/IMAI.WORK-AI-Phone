<?php

namespace app\common\service\hotspot;

use app\common\model\hotspot\HotspotTask;
use app\common\service\FileService;
use think\facade\Db;

class TaskService
{
    public const TASK_NO_PATTERN = '/^HOT_[0-9A-F]{12}$/';
    public const MAX_RETRY = 5;

    /** @var array<string, mixed> 测试钩子：仅 tests/ 注入，生产勿用。 */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
        self::$testHooks['clearShanjianBindingCalls'] = self::$testHooks['clearShanjianBindingCalls'] ?? [];
        self::$testHooks['patchOptionsCalls'] = self::$testHooks['patchOptionsCalls'] ?? [];
        self::$testHooks['bindShanjianCalls'] = self::$testHooks['bindShanjianCalls'] ?? [];
        self::$testHooks['markDoneCalls'] = self::$testHooks['markDoneCalls'] ?? [];
        self::$testHooks['savePublishCopywritingCalls'] = self::$testHooks['savePublishCopywritingCalls'] ?? [];
        self::$testHooks['saveScriptCalls'] = self::$testHooks['saveScriptCalls'] ?? [];
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    public static function lists(int $userId, int $pageNo = 1, int $pageSize = 25, string $status = ''): array
    {
        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(50, $pageSize));
        $allowed = ['running', 'done', 'fail', 'wait'];
        if ($status !== '' && !in_array($status, $allowed, true)) {
            $status = '';
        }
        $where = [['user_id', '=', $userId]];
        if ($status !== '') {
            $where[] = ['status', '=', $status];
        }
        $count = (int)HotspotTask::where($where)->count();
        $rows = HotspotTask::where($where)
            ->order('create_time', 'desc')
            ->page($pageNo, $pageSize)
            ->select();
        $out = [];
        foreach ($rows as $row) {
            $out[] = self::toApi($row);
        }
        HotspotLog::write(sprintf(
            '任务列表查询完成：用户=%d 状态=%s 数量=%d 页=%d 每页=%d 总数=%d',
            $userId,
            $status === '' ? '全部' : $status,
            count($out),
            $pageNo,
            $pageSize,
            $count
        ));
        return [
            'lists' => $out,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    public static function create(array $params, int $userId): array
    {
        $script = trim((string)($params['script'] ?? ''));
        if ($script === '') {
            throw new HotspotUpstreamException('口播文案不能为空');
        }
        $options = ScriptService::normalizeOptions(self::asArray($params['options'] ?? []));
        $title = (string)(($params['title'] ?? '') !== '' ? $params['title'] : ($params['topic'] ?? ''));
        $script = ScriptService::stripLeadingTitle($script, $title);
        if (isset($params['hashtags'])) {
            $options['hashtags'] = ScriptService::normalizeHashtags($params['hashtags']);
        }
        if (isset($params['shots'])) {
            $options['shots'] = ScriptService::normalizeShots($params['shots']);
        }
        ScriptService::assertCreateOptions($options);
        $options['dispatch_status'] = 'pending';
        $options['dispatch_locked_at'] = 0;

        $now = time();
        $task = HotspotTask::create([
            'task_no' => self::newTaskNo(),
            'user_id' => $userId,
            'topic' => (string)($params['topic'] ?? ''),
            'platform' => (string)($params['platform'] ?? 'douyin'),
            'title' => $title,
            'script' => $script,
            'persona_json' => self::asArray($params['persona'] ?? null),
            'core_points_json' => self::asArray($params['core_points'] ?? []),
            'citations_json' => self::asArray($params['citations'] ?? []),
            'analysis_json' => self::asArray($params['analysis'] ?? null),
            'options_json' => $options,
            'status' => 'running',
            'step_status_json' => [
                'select' => 'done',
                'search' => 'done',
                'analyze' => 'done',
                'script' => 'done',
                'video' => 'running',
            ],
            'error' => '',
            'video_url' => '',
            'shanjian_video_task_id' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        $api = self::toApi($task);
        HotspotLog::write(sprintf(
            '任务入库成功：用户=%d 任务号=%s 话题=%s 平台=%s 状态=%s 标题=%s 文案字数=%d',
            $userId,
            $api['id'],
            $api['topic'],
            $api['platform'],
            $api['status'],
            $api['title'],
            mb_strlen($script)
        ));
        HotspotLog::json('热点任务表写入 options_json：', [
            'video_type' => $options['video_type'] ?? '',
            'avatar_id' => $options['avatar_id'] ?? 0,
            'avatar' => $options['avatar'] ?? '',
            'materials' => $options['materials'] ?? [],
            'material_mode' => $options['material_mode'] ?? '',
            'goal' => $options['goal'] ?? '',
            'direction' => $options['direction'] ?? '',
            'duration_sec' => $options['duration_sec'] ?? 0,
            'hashtags' => $options['hashtags'] ?? [],
            'shots' => $options['shots'] ?? [],
            'persona_id' => (int)($api['persona']['id'] ?? 0),
            'dispatch_status' => $options['dispatch_status'] ?? '',
        ], 2000);
        return $api;
    }

    public static function detail(string $taskNo, int $userId = 0): ?array
    {
        if (!empty(self::$testHooks['skipPersist'])) {
            $hook = self::$testHooks['detail'] ?? null;
            if (is_callable($hook)) {
                return $hook($taskNo, $userId);
            }
            return is_array($hook) ? $hook : ['id' => $taskNo, 'options' => ['dispatch_status' => 'done']];
        }
        $row = self::findOwned($taskNo, $userId);
        return $row ? self::toApi($row, true) : null;
    }

    public static function delete(string $taskNo, int $userId = 0): bool
    {
        $row = self::findOwned($taskNo, $userId);
        if (!$row) {
            return false;
        }
        // 生成中同样放行：任务创建即 running，合成卡住时若在这里拦住，用户永远删不掉这张卡片。
        // 软删后下发/回写链路（listPendingDispatch、syncCompleted 等都走模型查询）自动跳过该行，
        // 已被认领的下发也会在 VideoService::dispatchOne 里以「认领后已删除」终止。
        $status = (string)$row->status;
        $row->delete();
        self::deleteBoundCreation($taskNo);
        HotspotLog::write('任务软删除成功：任务号=' . $taskNo . ' 原状态=' . $status);
        return true;
    }

    /**
     * 任务删除时同步软删关联创作记录：否则台账查不到任务会一直显示「排队合成中」
     */
    public static function deleteBoundCreation(string $taskNo): void
    {
        if ($taskNo === '') {
            return;
        }
        $count = \app\common\model\hotspot\HotspotCreation::where('task_no', $taskNo)
            ->update(['delete_time' => time()]);
        if ($count > 0) {
            HotspotLog::write(sprintf('任务关联创作记录已同步软删：任务号=%s 数量=%d', $taskNo, $count));
        }
    }

    public static function resetForRetry(string $taskNo, int $userId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再生成视频');
        }
        if (!self::isValidTaskNo($taskNo)) {
            throw new HotspotUpstreamException('任务不存在');
        }

        Db::startTrans();
        try {
            $row = HotspotTask::where('task_no', $taskNo)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            if (!$row) {
                throw new HotspotUpstreamException('任务不存在');
            }
            if ((string)$row->status !== 'fail') {
                throw new HotspotUpstreamException('仅失败任务可重试');
            }
            if (trim((string)$row->script) === '') {
                throw new HotspotUpstreamException('口播文案不能为空');
            }

            $update = self::buildRetryUpdate(
                (string)$row->status,
                (string)$row->script,
                self::asArray($row->options_json),
                (int)$row->shanjian_video_task_id
            );
            $options = $update['options'];
            $retrySeq = (int)($options['retry_seq'] ?? 0);

            $steps = self::asArray($row->step_status_json);
            $steps['video'] = 'running';

            $row->status = $update['status'];
            $row->error = $update['error'];
            $row->video_url = $update['video_url'];
            $row->shanjian_video_task_id = $update['shanjian_video_task_id'];
            $row->step_status_json = $steps;
            $row->options_json = $options;
            $row->publish_title = '';
            $row->publish_content = '';
            $row->publish_tag = '';
            $row->update_time = time();
            $row->save();
            Db::commit();

            $api = self::toApi($row);
            HotspotLog::write(sprintf(
                '任务已重置待重试：任务号=%s 用户=%d 重试序号=%d',
                $taskNo,
                $userId,
                $retrySeq
            ));
            return $api;
        } catch (HotspotUpstreamException $e) {
            Db::rollback();
            throw $e;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function markDone(string $taskNo, string $videoUrl, ?int $expectRetrySeq = null): ?array
    {
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['markDoneCalls'][] = [
                'task_no' => $taskNo,
                'video_url' => $videoUrl,
                'retry_seq' => $expectRetrySeq,
            ];
            $hook = self::$testHooks['detail'] ?? null;
            $task = is_callable($hook) ? $hook($taskNo, 0) : (is_array($hook) ? $hook : ['id' => $taskNo]);
            if (!is_array($task)) {
                $task = ['id' => $taskNo];
            }
            $task['status'] = 'done';
            $task['video_url'] = $videoUrl;
            return $task;
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $opt = self::asArray($row->options_json);
        $currentSeq = (int)($opt['retry_seq'] ?? 0);
        if ($expectRetrySeq !== null && $currentSeq !== $expectRetrySeq) {
            HotspotLog::write(sprintf(
                '忽略过期回写：任务号=%s 目标=done 回调序号=%d 当前序号=%d',
                $taskNo,
                $expectRetrySeq,
                $currentSeq
            ));
            return self::toApi($row);
        }
        if ((string)$row->status === 'done') {
            $currentUrl = self::rawVideoUrl($row);
            $nextUrl = trim($videoUrl);
            if ($nextUrl !== '' && $nextUrl !== $currentUrl) {
                $row->video_url = $nextUrl;
                $row->update_time = time();
                $row->save();
                HotspotLog::write('任务成片地址已刷新：任务号=' . $taskNo . ' 视频地址长度=' . mb_strlen($nextUrl));
            }
            return self::toApi($row);
        }
        $blocked = self::guardStatusWrite($row, 'done', $expectRetrySeq);
        if ($blocked !== null) {
            return $blocked;
        }
        $row->status = 'done';
        // 列宽 2048（v2.13.1.sql），超长带签名 URL 截断防御，避免严格模式下整次回写失败
        $row->video_url = mb_substr(trim($videoUrl), 0, 2048);
        $row->error = '';
        $row->step_status_json = [
            'select' => 'done',
            'search' => 'done',
            'analyze' => 'done',
            'script' => 'done',
            'video' => 'done',
        ];
        $row->update_time = time();
        $row->save();
        HotspotLog::write('任务标记完成：任务号=' . $taskNo . ' 视频地址长度=' . mb_strlen($videoUrl));
        return self::toApi($row);
    }

    public static function markFailed(
        string $taskNo,
        string $error,
        ?int $expectRetrySeq = null,
        array $optionsPatch = []
    ): ?array {
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $blocked = self::guardStatusWrite($row, 'fail', $expectRetrySeq);
        if ($blocked !== null) {
            return $blocked;
        }
        $steps = is_array($row->step_status_json) ? $row->step_status_json : [];
        $steps['video'] = 'fail';
        $row->status = 'fail';
        $row->error = mb_substr($error, 0, 500);
        $row->step_status_json = $steps;
        // options 补丁随状态一次写入，避免「先 markFailed 再 patchOptions」两段写之间的竞态
        if ($optionsPatch !== []) {
            $options = self::asArray($row->options_json);
            foreach ($optionsPatch as $key => $value) {
                $options[$key] = $value;
            }
            $row->options_json = $options;
        }
        $row->update_time = time();
        $row->save();
        HotspotLog::write('任务标记失败：任务号=' . $taskNo . ' 原因=' . mb_substr($error, 0, 200));
        return self::toApi($row);
    }

    public static function buildRetryUpdate(
        string $status,
        string $script,
        array $options,
        int $shanjianVideoTaskId = 0
    ): array {
        if ($status !== 'fail') {
            throw new HotspotUpstreamException('仅失败任务可重试');
        }
        if (trim($script) === '') {
            throw new HotspotUpstreamException('口播文案不能为空');
        }
        $options = self::nextRetryOptions($options);
        self::assertRetryAllowed((int)($options['retry_seq'] ?? 0));
        return [
            'status' => 'running',
            'error' => '',
            'video_url' => '',
            'shanjian_video_task_id' => $shanjianVideoTaskId,
            'options' => $options,
        ];
    }

    public static function nextRetryOptions(array $options): array
    {
        $options['retry_seq'] = (int)($options['retry_seq'] ?? 0) + 1;
        $options['dispatch_status'] = 'pending';
        $options['dispatch_locked_at'] = 0;
        unset($options[PublishContentService::OPTION_KEY]);
        return $options;
    }

    public static function assertRetryAllowed(int $retrySeq): void
    {
        if ($retrySeq > self::MAX_RETRY) {
            throw new HotspotUpstreamException('重试次数已达上限');
        }
    }

    public static function retryKeepsShanjianBinding(array $options): bool
    {
        return (int)($options['shanjian_setting_id'] ?? 0) > 0
            || (int)($options['shanjian_task_id'] ?? 0) > 0;
    }

    public static function clearShanjianBinding(string $taskNo, ?int $expectRetrySeq = null): ?array
    {
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['clearShanjianBindingCalls'][] = [
                'task_no' => $taskNo,
                'retry_seq' => $expectRetrySeq,
            ];
            return ['id' => $taskNo, 'options' => []];
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $options = self::asArray($row->options_json);
        if ($expectRetrySeq !== null && (int)($options['retry_seq'] ?? 0) !== $expectRetrySeq) {
            return self::toApi($row);
        }
        $row->shanjian_video_task_id = 0;
        unset($options['shanjian_setting_id'], $options['shanjian_task_id'], $options['shanjian_video_task_id']);
        $options['dispatch_status'] = 'pending';
        $options['dispatch_locked_at'] = 0;
        $row->options_json = $options;
        $row->update_time = time();
        $row->save();
        HotspotLog::write('原单不存在，已清空闪剪绑定并回退新建：任务号=' . $taskNo);
        return self::toApi($row);
    }

    public static function bindShanjian(string $taskNo, array $patch): ?array
    {
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['bindShanjianCalls'][] = [
                'task_no' => $taskNo,
                'patch' => $patch,
            ];
            return ['id' => $taskNo, 'options' => $patch];
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $sjTaskId = (int)($patch['shanjian_video_task_id'] ?? $patch['shanjian_task_id'] ?? 0);
        if ($sjTaskId > 0) {
            $patch['shanjian_task_id'] = $sjTaskId;
            $row->shanjian_video_task_id = $sjTaskId;
        }
        unset($patch['shanjian_video_task_id']);
        $options = self::asArray($row->options_json);
        foreach ($patch as $key => $value) {
            $options[$key] = $value;
        }
        $row->options_json = $options;
        $row->update_time = time();
        $row->save();
        $api = self::toApi($row);
        HotspotLog::json('热点任务回写闪剪关联：任务号=' . $taskNo . ' ', $patch, 800);
        return $api;
    }

    public static function patchOptions(string $taskNo, array $patch, ?int $expectRetrySeq = null): ?array
    {
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['patchOptionsCalls'][] = [
                'task_no' => $taskNo,
                'patch' => $patch,
                'retry_seq' => $expectRetrySeq,
            ];
            return ['id' => $taskNo, 'options' => $patch];
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $options = self::asArray($row->options_json);
        // retry_seq 守卫：用户已发起新一轮重试时，过期下发协程不得改写新一轮的 dispatch 状态
        if ($expectRetrySeq !== null && (int)($options['retry_seq'] ?? 0) !== $expectRetrySeq) {
            HotspotLog::write(sprintf(
                '忽略过期 options 回写：任务号=%s 回调序号=%d 当前序号=%d 补丁=%s',
                $taskNo,
                $expectRetrySeq,
                (int)($options['retry_seq'] ?? 0),
                HotspotLog::clip($patch, 200)
            ));
            return self::toApi($row);
        }
        foreach ($patch as $key => $value) {
            $options[$key] = $value;
        }
        $row->options_json = $options;
        $row->update_time = time();
        $row->save();
        return self::toApi($row);
    }

    public static function saveScript(string $taskNo, string $script): ?array
    {
        $script = trim($script);
        if ($script === '') {
            return null;
        }
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['saveScriptCalls'][] = [
                'task_no' => $taskNo,
                'script' => $script,
            ];
            return ['id' => $taskNo, 'script' => $script];
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $row->script = $script;
        $row->update_time = time();
        $row->save();
        HotspotLog::write(sprintf(
            '任务口播文案回写结尾引导：任务号=%s 字数=%d',
            $taskNo,
            mb_strlen($script)
        ));
        return self::toApi($row);
    }

    public static function savePublishCopywriting(string $taskNo, array $copy, ?int $expectRetrySeq = null): ?array
    {
        $normalized = PublishContentService::normalize($copy);
        $fields = [
            'publish_title' => $normalized['title'],
            'publish_content' => $normalized['content'],
            'publish_tag' => $normalized['tag'],
        ];
        if (!empty(self::$testHooks['skipPersist'])) {
            self::$testHooks['savePublishCopywritingCalls'][] = [
                'task_no' => $taskNo,
                'fields' => $fields,
                'retry_seq' => $expectRetrySeq,
            ];
            return ['id' => $taskNo, 'options' => [], ...$fields];
        }
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        $options = self::asArray($row->options_json);
        if ($expectRetrySeq !== null && (int)($options['retry_seq'] ?? 0) !== $expectRetrySeq) {
            HotspotLog::write(sprintf(
                '忽略过期发布文案回写：任务号=%s 回调序号=%d 当前序号=%d',
                $taskNo,
                $expectRetrySeq,
                (int)($options['retry_seq'] ?? 0)
            ));
            return self::toApi($row);
        }
        $row->publish_title = $fields['publish_title'];
        $row->publish_content = $fields['publish_content'];
        $row->publish_tag = $fields['publish_tag'];
        $row->update_time = time();
        $row->save();
        HotspotLog::write(sprintf(
            '热点任务发布文案已落库：任务号=%s 标题=%s 正文字数=%d 话题=%s',
            $taskNo,
            $fields['publish_title'],
            mb_strlen($fields['publish_content']),
            $fields['publish_tag']
        ));
        return self::toApi($row);
    }

    public static function listPendingDispatch(int $limit = 2, int $lockTtl = 480): array
    {
        $limit = max(1, $limit);
        // SQL 层排除已下发完成/失败的任务，否则合成中的老任务会占满扫描窗口，待下发的新任务被饿死
        $rows = HotspotTask::where('status', 'running')
            ->whereRaw(
                "(JSON_UNQUOTE(JSON_EXTRACT(options_json, '$.dispatch_status')) IS NULL"
                . " OR JSON_UNQUOTE(JSON_EXTRACT(options_json, '$.dispatch_status')) NOT IN ('done', 'fail'))"
            )
            ->order('id', 'asc')
            ->limit(max($limit * 5, 20))
            ->select();
        $out = [];
        foreach ($rows as $row) {
            if (!self::isDispatchable($row, $lockTtl)) {
                continue;
            }
            $item = self::toApi($row);
            $item['user_id'] = (int)$row->user_id;
            $out[] = $item;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    public static function isDispatchable(mixed $row, int $lockTtl = 480): bool
    {
        if (!$row instanceof HotspotTask) {
            return false;
        }
        if ((string)$row->status !== 'running') {
            return false;
        }
        $opt = self::asArray($row->options_json);
        if ((int)($opt['shanjian_setting_id'] ?? 0) > 0) {
            return false;
        }
        $status = (string)($opt['dispatch_status'] ?? 'pending');
        if ($status === 'done' || $status === 'fail') {
            return false;
        }
        if ($status === 'dispatching') {
            return self::isExpiredDispatchLock($opt, $lockTtl);
        }
        return true;
    }

    public static function isExpiredDispatchLock(array $opt, int $lockTtl): bool
    {
        $status = (string)($opt['dispatch_status'] ?? '');
        $lockedAt = (int)($opt['dispatch_locked_at'] ?? 0);
        return $status === 'dispatching' && $lockedAt > 0 && (time() - $lockedAt) >= $lockTtl;
    }

    public static function claimDispatch(string $taskNo, int $lockTtl = 480): ?array
    {
        if (!self::isValidTaskNo($taskNo)) {
            return null;
        }
        Db::startTrans();
        try {
            $row = HotspotTask::where('task_no', $taskNo)->lock(true)->find();
            if (!$row || !self::isDispatchable($row, $lockTtl)) {
                Db::rollback();
                return null;
            }
            $options = self::asArray($row->options_json);
            if (self::isExpiredDispatchLock($options, $lockTtl)) {
                HotspotLog::write('锁过期二次认领：任务号=' . $taskNo);
            }
            $options['dispatch_status'] = 'dispatching';
            $options['dispatch_locked_at'] = time();
            $row->options_json = $options;
            $row->update_time = time();
            $row->save();
            Db::commit();
            HotspotLog::write('热点任务已认领下发：任务号=' . $taskNo);
            $api = self::toApi($row);
            $api['user_id'] = (int)$row->user_id;
            return $api;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function isValidTaskNo(string $taskNo): bool
    {
        return (bool)preg_match(self::TASK_NO_PATTERN, $taskNo);
    }

    public static function hasBoundShanjian(array $task): bool
    {
        if ((int)($task['shanjian_video_task_id'] ?? 0) > 0) {
            return true;
        }
        $options = self::asArray($task['options'] ?? []);
        return (int)($options['shanjian_setting_id'] ?? 0) > 0
            || (int)($options['shanjian_task_id'] ?? 0) > 0;
    }

    public static function resolveBoundVideoTaskId(mixed $row): int
    {
        if (is_array($row)) {
            $col = (int)($row['shanjian_video_task_id'] ?? 0);
            if ($col > 0) {
                return $col;
            }
            $opt = self::asArray($row['options'] ?? ($row['options_json'] ?? []));
            return (int)($opt['shanjian_task_id'] ?? 0);
        }
        $col = (int)($row->shanjian_video_task_id ?? 0);
        if ($col > 0) {
            return $col;
        }
        $opt = self::asArray($row->options_json ?? []);
        return (int)($opt['shanjian_task_id'] ?? 0);
    }

    private static function guardStatusWrite(HotspotTask $row, string $to, ?int $expectRetrySeq): ?array
    {
        $opt = self::asArray($row->options_json);
        $currentSeq = (int)($opt['retry_seq'] ?? 0);
        if ($expectRetrySeq !== null && $currentSeq !== $expectRetrySeq) {
            HotspotLog::write(sprintf(
                '忽略过期回写：任务号=%s 目标=%s 回调序号=%d 当前序号=%d',
                (string)$row->task_no,
                $to,
                $expectRetrySeq,
                $currentSeq
            ));
            return self::toApi($row);
        }
        $from = (string)$row->status;
        if ($from === $to) {
            return self::toApi($row);
        }
        if ($from !== 'running') {
            HotspotLog::write(sprintf(
                '拒绝非法状态迁移：任务号=%s 当前=%s 目标=%s',
                (string)$row->task_no,
                $from,
                $to
            ));
            return self::toApi($row);
        }
        return null;
    }

    private static function findOwned(string $taskNo, int $userId): ?HotspotTask
    {
        $row = self::findByTaskNo($taskNo);
        if (!$row) {
            return null;
        }
        if ($userId > 0 && (int)$row->user_id !== $userId) {
            HotspotLog::write(sprintf('任务归属不匹配：任务号=%s 请求用户=%d 所属用户=%d', $taskNo, $userId, (int)$row->user_id));
            return null;
        }
        return $row;
    }

    private static function findByTaskNo(string $taskNo): ?HotspotTask
    {
        if (!self::isValidTaskNo($taskNo)) {
            HotspotLog::write('任务号格式非法：' . $taskNo);
            return null;
        }
        $row = HotspotTask::where('task_no', $taskNo)->findOrEmpty();
        return $row->isEmpty() ? null : $row;
    }

    private static function newTaskNo(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $no = 'HOT_' . strtoupper(bin2hex(random_bytes(6)));
            $exists = HotspotTask::where('task_no', $no)->findOrEmpty();
            if ($exists->isEmpty()) {
                return $no;
            }
        }
        return 'HOT_' . strtoupper(bin2hex(random_bytes(6)));
    }

    private static function toApi(HotspotTask $row, bool $withPic = false): array
    {
        $options = self::asArray($row->options_json);
        $copy = [
            'title' => trim((string)($row->publish_title ?? '')),
            'content' => trim((string)($row->publish_content ?? '')),
            'tag' => trim((string)($row->publish_tag ?? '')),
        ];
        if (!PublishContentService::hasCopywriting(PublishContentService::normalize($copy))) {
            $copy = $options[PublishContentService::OPTION_KEY] ?? [];
        }
        return array_merge([
            'id' => (string)$row->task_no,
            'topic' => (string)$row->topic,
            'platform' => (string)$row->platform,
            'title' => (string)$row->title,
            'script' => (string)$row->script,
            'persona' => self::asArray($row->persona_json),
            'core_points' => self::asArray($row->core_points_json),
            'citations' => self::asArray($row->citations_json),
            'analysis' => self::asArray($row->analysis_json),
            'options' => $options,
            'status' => (string)$row->status,
            'step_status' => self::asArray($row->step_status_json),
            'error' => (string)$row->error,
            'video_url' => self::formatVideoUrl((string)$row->video_url),
            'shanjian_video_task_id' => self::resolveBoundVideoTaskId($row),
            // 仅详情场景查封面（列表按行查会 N+1）
            'pic' => $withPic ? self::boundVideoPic($row) : '',
            'created_at' => self::formatDateTime($row, 'create_time'),
            'updated_at' => self::formatDateTime($row, 'update_time'),
        ], PublishContentService::apiFields($copy));
    }

    /** 成片封面：取绑定闪剪任务的封面图 */
    private static function boundVideoPic(HotspotTask $row): string
    {
        $videoTaskId = self::resolveBoundVideoTaskId($row);
        if ($videoTaskId <= 0) {
            return '';
        }
        $pic = (string)(\app\common\model\shanjian\ShanjianVideoTask::where('id', $videoTaskId)->value('pic') ?? '');
        return $pic !== '' ? FileService::getFileUrl($pic) : '';
    }

    public static function formatDateTime(mixed $row, string $field): string
    {
        $raw = null;
        if (is_object($row) && method_exists($row, 'getData')) {
            $raw = $row->getData($field);
        } elseif (is_array($row)) {
            $raw = $row[$field] ?? null;
        } elseif (is_object($row)) {
            $raw = $row->$field ?? null;
        }

        if ($raw === null || $raw === '') {
            return '';
        }
        if (is_numeric($raw)) {
            $ts = (int)$raw;
            if ($ts <= 0) {
                return '';
            }
            if ($ts > 9999999999) {
                $ts = (int)floor($ts / 1000);
            }
            return date('Y-m-d H:i:s', $ts);
        }

        $text = trim((string)$raw);
        if ($text === '') {
            return '';
        }
        $ts = strtotime($text);
        return $ts > 0 ? date('Y-m-d H:i:s', $ts) : '';
    }

    public static function formatVideoUrl(string $videoUrl): string
    {
        $videoUrl = trim($videoUrl);
        if ($videoUrl === '') {
            return '';
        }
        return FileService::getFileUrl($videoUrl);
    }

    private static function rawVideoUrl(HotspotTask $row): string
    {
        if (method_exists($row, 'getData')) {
            $raw = $row->getData('video_url');
            if (is_string($raw) || is_numeric($raw)) {
                return trim((string)$raw);
            }
        }
        return trim((string)($row->video_url ?? ''));
    }

    private static function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
