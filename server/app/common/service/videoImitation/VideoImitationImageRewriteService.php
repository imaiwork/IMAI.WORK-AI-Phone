<?php

namespace app\common\service\videoImitation;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\ConfigService;
use app\common\service\draw\MediaModelsService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\ToolsService;
use think\facade\Db;
use think\facade\Log;

/**
 * 视频仿写任务 - 小红书图文图片改写
 */
class VideoImitationImageRewriteService
{
    public const PROCESSING_TIMEOUT_SECONDS = 1800;
    /** 无心跳超过该秒数且运行锁空闲时，视为孤儿 PROCESSING，可立即续跑 */
    public const ORPHAN_STALE_SECONDS = 240;
    public const MAX_RETRY_COUNT = 2;
    public const MAX_REWRITE_IMAGE_COUNT = 12;

    /** 图生图计费模型（技术 alias，展示名 image-2） */
    private const BILLING_MODEL_ALIAS = 'gpt-image-2';

    private const FIXED_PROMPT = <<<'PROMPT'
# 图片重绘提示词
请分析上传图片中的内容，并按照以下规则重新设计生成一张全新的图片：
## 内容保留
保留图片中的：
* 核心主题
* 信息层级
* 视觉重点
* 内容逻辑
* 版式结构
保持用户能够快速理解相同主题。
---
## 内容重构
重新设计：
* 配色方案
* 字体风格
* 图形元素
* 插画元素
* 图标样式
* 背景设计
* 排版细节
避免与原图高度一致。
---
## 标签处理
自动识别并移除：
* 品牌名称
* Logo
* 水印
* 用户昵称
* 账号信息
* 二维码
* 联系方式
* 平台ID
* 产品型号
* 公司名称
* 门店名称
对于上述内容：
* 不保留
* 不复刻
* 不生成相似内容
---
## 文案处理
自动检测图片中的文字内容：
* 提炼核心含义
* 保留主题方向
* 使用全新表达方式重写
要求：
* 不直接复制原文
* 不保留原句
* 不保留明显特征词
* 不保留个人IP表达
---
## 风格优化
生成符合当前主流社交媒体风格的图片：
* 简洁
* 高点击率
* 强视觉吸引力
* 信息清晰
* 更具现代感
---
## 输出要求
最终输出为：
* 全新设计
* 全新排版
* 全新视觉元素
* 全新文案表达
保留主题，不保留原图身份特征。
PROMPT;

    public static function sync(VideoImitationTask $task, ?callable $onHeartbeat = null): bool
    {
        $status = (int)$task->image_rewrite_status;
        if ($status === VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT) {
            return self::submit($task, $onHeartbeat);
        }
        if ($status === VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING) {
            return self::resume($task, $onHeartbeat);
        }

        self::log(sprintf(
            'sync跳过：非待改写/处理中状态 task_id=%d rewrite_status=%d status=%d',
            (int)$task->id,
            $status,
            (int)$task->status
        ));
        return false;
    }

    /**
     * 是否无心跳过久（软孤儿）。调用方需自行保证运行锁空闲/本进程持锁。
     */
    public static function isOrphanStale(int $startedAt, ?int $now = null): bool
    {
        if ($startedAt <= 0) {
            return true;
        }
        $now = $now ?? time();
        return $startedAt <= $now - self::ORPHAN_STALE_SECONDS;
    }

    /**
     * 本轮应改写张数（选图优先，受 MAX_REWRITE_IMAGE_COUNT 限制）
     */
    public static function resolveExpectedImageCount(VideoImitationTask $task): int
    {
        $target = self::resolveTargetImages($task);
        return count($target['images']);
    }

    public static function submit(VideoImitationTask $task, ?callable $onHeartbeat = null): bool
    {
        $taskId = (int)$task->id;
        $mediaType = (int)$task->media_type;
        $rewriteStatus = (int)$task->image_rewrite_status;
        if ($mediaType !== VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
            || $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT
        ) {
            self::log(sprintf(
                'submit跳过：状态不符 task_id=%d media_type=%d rewrite_status=%d',
                $taskId,
                $mediaType,
                $rewriteStatus
            ));
            return false;
        }

        if ($taskId <= 0) {
            self::log('submit跳过：无效 task_id');
            return false;
        }

        $billingRound = max(1, (int)($task->billing_round ?? 1));
        $billingTaskId = self::buildTaskId($taskId, $billingRound);
        $attemptId = '';
        $unit = 0.0;
        $images = [];
        $skippedCount = 0;
        $originalCount = 0;
        $results = [];
        $userId = (int)$task->user_id;
        $submitStarted = microtime(true);

        try {
            // 修复：整体延长 PHP 执行时间，防止批量处理超时
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $target = self::resolveTargetImages($task);
            $images = $target['images'];
            $skippedCount = $target['skipped'];
            $originalCount = $target['original_count'];
            $imageSource = $target['source'];
            if (empty($images)) {
                throw new \Exception('原图列表为空');
            }

            $unit = self::resolveBillingUnit();
            $needPoints = round($unit * count($images), 2);
            $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
            self::log(sprintf(
                'submit预检 task_id=%d user_id=%d source=%s image_count=%d original_count=%d skipped=%d unit=%.2f need_points=%.2f spendable=%.2f retry=%d billing_round=%d',
                $taskId,
                $userId,
                $imageSource,
                count($images),
                $originalCount,
                $skippedCount,
                $unit,
                $needPoints,
                $spendable,
                (int)$task->image_rewrite_retry_count,
                $billingRound
            ));
            self::precheckToken($userId, count($images), $unit);

            $startedAt = time();
            $attemptId = self::buildAttemptTaskId(
                $taskId,
                (int)$task->image_rewrite_retry_count,
                $startedAt,
                $billingRound
            );

            $affected = VideoImitationTask::where('id', $taskId)
                ->where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT)
                ->where('image_rewrite_charged_count', 0)
                ->update([
                    'status' => VideoImitationTask::STATUS_GENERATING,
                    'image_rewrite_task_id' => $attemptId,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING,
                    'image_rewrite_started_at' => $startedAt,
                    'rewritten_images' => '[]',
                    'image_rewrite_results' => '[]',
                    'image_rewrite_success_count' => 0,
                    'image_rewrite_fail_count' => 0,
                    'remarks' => '图片改写处理中',
                    'update_time' => $startedAt,
                ]);
            if ($affected === 0) {
                $latest = VideoImitationTask::where('id', $taskId)
                    ->field('image_rewrite_status,image_rewrite_charged_count,image_rewrite_task_id')
                    ->findOrEmpty();
                self::log(sprintf(
                    'submit抢占失败（可能并发） task_id=%d attempt_id=%s latest_rewrite_status=%s charged=%s latest_attempt=%s',
                    $taskId,
                    $attemptId,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->image_rewrite_status,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->image_rewrite_charged_count,
                    $latest->isEmpty() ? '-' : (string)$latest->image_rewrite_task_id
                ));
                return false;
            }

            self::log(sprintf(
                'submit已进入处理中 task_id=%d attempt_id=%s billing_task_id=%s image_total=%d',
                $taskId,
                $attemptId,
                $billingTaskId,
                count($images)
            ));

            $task = VideoImitationTask::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                self::log("submit失败：任务不存在 task_id={$taskId}");
                return false;
            }
            $userId = (int)$task->user_id;

            return self::runRewriteBatch(
                $taskId,
                $attemptId,
                $userId,
                $billingTaskId,
                $unit,
                $images,
                $skippedCount,
                $originalCount,
                [],
                $onHeartbeat,
                $submitStarted,
                'submit'
            );
        } catch (\Throwable $th) {
            self::log(sprintf(
                '图片改写提交失败 task_id=%d attempt_id=%s user_id=%d error=%s file=%s line=%d',
                $taskId,
                $attemptId !== '' ? $attemptId : '-',
                $userId,
                $th->getMessage(),
                $th->getFile(),
                $th->getLine()
            ));

            if ($attemptId !== '') {
                try {
                    if ($unit <= 0) {
                        $unit = self::resolveBillingUnit();
                    }
                    $settled = self::settleIfCompleteOrLeaveResumable(
                        $taskId,
                        $attemptId,
                        $userId,
                        $billingTaskId,
                        $unit,
                        $results,
                        $images,
                        $skippedCount,
                        $originalCount,
                        '异常收尾'
                    );
                    if ($settled || self::hasSettledRewrite($taskId)) {
                        self::log(sprintf(
                            '异常后已结算 task_id=%d attempt_id=%s settled=%s',
                            $taskId,
                            $attemptId,
                            $settled ? '1' : '0'
                        ));
                        return $settled;
                    }

                    $latest = VideoImitationTask::where('id', $taskId)
                        ->field('image_rewrite_status,rewritten_images,image_rewrite_results')
                        ->findOrEmpty();
                    $hasProgress = !$latest->isEmpty()
                        && (
                            !empty(self::normalizeImages($latest->rewritten_images))
                            || !empty($latest->image_rewrite_results)
                        );
                    if ($hasProgress
                        && (int)$latest->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
                    ) {
                        self::log("异常后保留PROCESSING待续跑 task_id={$taskId} attempt_id={$attemptId}");
                        return false;
                    }
                } catch (\Throwable $settleTh) {
                    self::log(sprintf(
                        '图片改写异常收尾失败 task_id=%d attempt_id=%s error=%s',
                        $taskId,
                        $attemptId,
                        $settleTh->getMessage()
                    ));
                }

                VideoImitationTask::where('id', $taskId)
                    ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
                    ->where('image_rewrite_task_id', $attemptId)
                    ->update([
                        'status' => VideoImitationTask::STATUS_FAIL,
                        'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL,
                        'remarks' => '图片改写提交失败：' . $th->getMessage(),
                        'update_time' => time(),
                    ]);
                self::log("已标记失败(PROCESSING) task_id={$taskId} attempt_id={$attemptId}");
                return false;
            }

            VideoImitationTask::where('id', $taskId)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT)
                ->update([
                    'status' => VideoImitationTask::STATUS_FAIL,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL,
                    'remarks' => '图片改写提交失败：' . $th->getMessage(),
                    'update_time' => time(),
                ]);
            self::log("已标记失败(WAIT) task_id={$taskId}");
            return false;
        }
    }

    /**
     * 续跑 PROCESSING：跳过已成功原图，只处理剩余图，不清空 rewritten_images
     */
    public static function resume(VideoImitationTask $task, ?callable $onHeartbeat = null): bool
    {
        $taskId = (int)$task->id;
        $mediaType = (int)$task->media_type;
        $rewriteStatus = (int)$task->image_rewrite_status;
        $attemptId = (string)$task->image_rewrite_task_id;
        $resumeStarted = microtime(true);

        if ($taskId <= 0
            || $mediaType !== VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
            || $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
            || $attemptId === ''
        ) {
            self::log(sprintf(
                'resume跳过：状态不符 task_id=%d media_type=%d rewrite_status=%d attempt_id=%s',
                $taskId,
                $mediaType,
                $rewriteStatus,
                $attemptId !== '' ? $attemptId : '-'
            ));
            return false;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $billingRound = max(1, (int)($task->billing_round ?? 1));
        $billingTaskId = self::buildTaskId($taskId, $billingRound);
        $userId = (int)$task->user_id;
        $unit = self::resolveBillingUnit();
        $target = self::resolveTargetImages($task);
        $images = $target['images'];
        $skippedCount = $target['skipped'];
        $originalCount = $target['original_count'];
        if (empty($images)) {
            self::log("resume跳过：原图列表为空 task_id={$taskId}");
            return false;
        }

        $existingResults = is_array($task->image_rewrite_results) ? $task->image_rewrite_results : [];
        $resultsByIndex = self::indexRewriteResults($existingResults);
        $successOriginals = self::collectSuccessOriginals($resultsByIndex);
        $doneCount = count($successOriginals);
        $expected = count($images);
        $remain = 0;
        foreach ($images as $index => $originalImagePath) {
            $normalized = self::normalizeImageKey($originalImagePath);
            $existing = $resultsByIndex[$index] ?? null;
            $alreadySuccess = ($existing['status'] ?? '') === 'success'
                || isset($successOriginals[$normalized]);
            if (!$alreadySuccess) {
                $remain++;
            }
        }

        self::log(sprintf(
            'resume开始 task_id=%d attempt_id=%s expected=%d done=%d remain=%d source=%s',
            $taskId,
            $attemptId,
            $expected,
            $doneCount,
            $remain,
            $target['source']
        ));

        if ($remain <= 0) {
            $settled = self::settleRewriteAttempt(
                $taskId,
                $attemptId,
                $userId,
                $billingTaskId,
                $unit,
                self::orderedRewriteResults($resultsByIndex),
                $expected,
                $skippedCount,
                $originalCount
            );
            self::log(sprintf(
                'resume无剩余直接结算 task_id=%d attempt_id=%s settled=%s elapsed_ms=%d',
                $taskId,
                $attemptId,
                $settled ? '1' : '0',
                (int)round((microtime(true) - $resumeStarted) * 1000)
            ));
            return $settled;
        }

        $now = time();
        $touch = VideoImitationTask::where('id', $taskId)
            ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->update([
                'status' => VideoImitationTask::STATUS_GENERATING,
                'image_rewrite_started_at' => $now,
                'remarks' => sprintf('图片改写续跑中，已成功%d张，剩余%d张', $doneCount, $remain),
                'update_time' => $now,
            ]);
        if ($touch === 0) {
            self::log("resume抢占失败 task_id={$taskId} attempt_id={$attemptId}");
            return false;
        }

        return self::runRewriteBatch(
            $taskId,
            $attemptId,
            $userId,
            $billingTaskId,
            $unit,
            $images,
            $skippedCount,
            $originalCount,
            $resultsByIndex,
            $onHeartbeat,
            $resumeStarted,
            'resume'
        );
    }

    public static function recoverExpired(VideoImitationTask $task): bool
    {
        if ((int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
            || !self::isProcessingExpired((int)$task->image_rewrite_started_at)
        ) {
            return false;
        }

        $taskId = (int)$task->id;
        $attemptId = (string)$task->image_rewrite_task_id;
        $startedAt = (int)$task->image_rewrite_started_at;
        $rewritten = self::normalizeImages($task->rewritten_images);
        $results = is_array($task->image_rewrite_results) ? $task->image_rewrite_results : [];
        $target = self::resolveTargetImages($task);
        $images = $target['images'];
        $expected = count($images);
        $age = $startedAt > 0 ? max(0, time() - $startedAt) : -1;
        self::log(sprintf(
            '超时回收开始 task_id=%d attempt_id=%s started_at=%d age=%ds rewritten=%d expected=%d retry=%d',
            $taskId,
            $attemptId,
            $startedAt,
            $age,
            count($rewritten),
            $expected,
            (int)$task->image_rewrite_retry_count
        ));

        // 已全部成功，或每张都有终态结果 → 才结算；未满图绝不按部分成功收尾
        if ($expected > 0 && (count($rewritten) >= $expected || self::isRewriteAttemptComplete($images, $results))) {
            try {
                $unit = self::resolveBillingUnit();
                $settled = self::settleRewriteAttempt(
                    $taskId,
                    $attemptId,
                    (int)$task->user_id,
                    self::buildTaskId($taskId, max(1, (int)($task->billing_round ?? 1))),
                    $unit,
                    $results,
                    $expected,
                    $target['skipped'],
                    $target['original_count']
                );
                self::log(sprintf(
                    '超时回收：批次已完整，按结果结算 task_id=%d attempt_id=%s rewritten=%d expected=%d settled=%s',
                    $taskId,
                    $attemptId,
                    count($rewritten),
                    $expected,
                    $settled ? '1' : '0'
                ));
                return $settled;
            } catch (\Throwable $th) {
                self::log("图片改写超时收尾失败 task_id={$taskId} error=" . $th->getMessage());
                return false;
            }
        }

        if (!empty($rewritten) || !empty($results)) {
            $remain = max(0, $expected - count($rewritten));
            $now = time();
            $affected = VideoImitationTask::where('id', $taskId)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
                ->where('image_rewrite_task_id', $attemptId)
                ->where('image_rewrite_started_at', $startedAt)
                ->update([
                    'status' => VideoImitationTask::STATUS_GENERATING,
                    'image_rewrite_started_at' => $now,
                    'remarks' => sprintf(
                        '图片改写失联，等待续跑剩余图（已成功%d张，剩余约%d张）',
                        count($rewritten),
                        $remain
                    ),
                    'update_time' => $now,
                ]);
            self::log(sprintf(
                '超时回收：未满图不结算，标记可续跑 task_id=%d attempt_id=%s rewritten=%d expected=%d remain=%d affected=%d',
                $taskId,
                $attemptId,
                count($rewritten),
                $expected,
                $remain,
                (int)$affected
            ));
            return $affected > 0;
        }

        $query = VideoImitationTask::where('id', $taskId)
            ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->where('image_rewrite_started_at', $startedAt);

        $retryCount = (int)$task->image_rewrite_retry_count;
        if (!self::canRetry($retryCount)) {
            $query->update([
                'status' => VideoImitationTask::STATUS_FAIL,
                'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL,
                'remarks' => '图片改写失联，已达到最大重试次数',
                'update_time' => time(),
            ]);
            self::log(sprintf(
                '超时回收：达到最大重试，标记失败 task_id=%d attempt_id=%s retry=%d',
                $taskId,
                $attemptId,
                $retryCount
            ));
            return false;
        }

        $nextRetryCount = $retryCount + 1;
        $affected = $query->update([
            'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT,
            'image_rewrite_retry_count' => $nextRetryCount,
            'remarks' => "图片改写超时，等待第{$nextRetryCount}次重试",
            'update_time' => time(),
        ]);
        self::log(sprintf(
            '超时回收：回退待重试 task_id=%d attempt_id=%s next_retry=%d affected=%d',
            $taskId,
            $attemptId,
            $nextRetryCount,
            $affected
        ));

        return $affected > 0;
    }

    public static function isProcessingExpired(int $startedAt, ?int $now = null): bool
    {
        if ($startedAt <= 0) {
            return true;
        }

        $now = $now ?? time();
        return $startedAt <= $now - self::PROCESSING_TIMEOUT_SECONDS;
    }

    public static function canRetry(int $retryCount): bool
    {
        return $retryCount < self::MAX_RETRY_COUNT;
    }

    private static function heartbeatProcessing(int $taskId, string $attemptId, ?callable $onHeartbeat = null): bool
    {
        $heartbeatAt = time();
        $heartbeatAffected = VideoImitationTask::where('id', $taskId)
            ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->update([
                'image_rewrite_started_at' => $heartbeatAt,
                'update_time' => $heartbeatAt,
            ]);
        if ($heartbeatAffected === 0) {
            $stillMine = VideoImitationTask::where('id', $taskId)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
                ->where('image_rewrite_task_id', $attemptId)
                ->count();
            if ($stillMine === 0) {
                self::log("图片改写任务已被回收 task_id={$taskId} attempt_id={$attemptId} stage=heartbeat");
                return false;
            }
        }

        if ($onHeartbeat !== null) {
            try {
                $onHeartbeat();
            } catch (\Throwable $th) {
                self::log('图片改写心跳回调失败 task_id=' . $taskId . ' error=' . $th->getMessage());
            }
        } else {
            // 无回调时记录心跳日志，便于排查锁续期情况
            self::log('图片改写心跳无回调 task_id=' . $taskId . ' attempt_id=' . $attemptId);
        }

        return true;
    }

    private static function appendRewrittenImageProgress(
        int $taskId,
        string $attemptId,
        string $storedImage,
        array $results
    ): bool {
        Db::startTrans();
        try {
            $task = VideoImitationTask::where('id', $taskId)->lock(true)->findOrEmpty();
            if ($task->isEmpty()
                || (int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
                || (string)$task->image_rewrite_task_id !== $attemptId
            ) {
                Db::rollback();
                self::log("图片改写任务已被回收 task_id={$taskId} attempt_id={$attemptId} stage=append_progress");
                return false;
            }

            $rewritten = self::normalizeImages($task->rewritten_images);
            $rewritten[] = $storedImage;
            $rewritten = array_values(array_unique($rewritten));
            $successCount = count($rewritten);
            $now = time();

            $task->rewritten_images = $rewritten;
            $task->image_rewrite_results = $results;
            $task->image_rewrite_success_count = $successCount;
            $task->image_rewrite_started_at = $now;
            $task->remarks = '图片改写处理中，已成功' . $successCount . '张';
            $task->update_time = $now;
            $task->save();
            Db::commit();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::log("图片改写进度落库失败 task_id={$taskId} attempt_id={$attemptId} error=" . $th->getMessage());
            throw $th;
        }
    }

    private static function persistRewriteResultsProgress(int $taskId, string $attemptId, array $results): bool
    {
        $now = time();
        $affected = VideoImitationTask::where('id', $taskId)
            ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->update([
                'image_rewrite_results' => json_encode($results, JSON_UNESCAPED_UNICODE),
                'image_rewrite_started_at' => $now,
                'update_time' => $now,
            ]);
        if ($affected > 0) {
            return true;
        }

        $stillMine = VideoImitationTask::where('id', $taskId)
            ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->count();
        if ($stillMine === 0) {
            self::log("图片改写任务已被回收 task_id={$taskId} attempt_id={$attemptId} stage=persist_fail_progress");
            return false;
        }
        return true;
    }

    private static function settleRewriteAttempt(
        int $taskId,
        string $attemptId,
        int $userId,
        string $billingTaskId,
        float $unit,
        array $results,
        int $attemptedCount,
        int $skippedCount,
        int $originalCount
    ): bool {
        Db::startTrans();
        try {
            $task = VideoImitationTask::where('id', $taskId)->lock(true)->findOrEmpty();
            if ($task->isEmpty()
                || (int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
                || (string)$task->image_rewrite_task_id !== $attemptId
            ) {
                Db::rollback();
                $currentStatus = (int)($task->image_rewrite_status ?? 0);
                self::log(sprintf(
                    '结算跳过：状态已变 task_id=%d expect_attempt=%s current_rewrite_status=%d current_attempt=%s',
                    $taskId,
                    $attemptId,
                    $currentStatus,
                    $task->isEmpty() ? '-' : (string)$task->image_rewrite_task_id
                ));
                return $currentStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS;
            }

            $rewritten = self::normalizeImages($task->rewritten_images);
            $successCount = count($rewritten);
            if (empty($results)) {
                $results = is_array($task->image_rewrite_results) ? $task->image_rewrite_results : [];
            }
            if ($attemptedCount <= 0) {
                $attemptedCount = !empty($results)
                    ? count($results)
                    : max($successCount, (int)$task->image_rewrite_success_count + (int)$task->image_rewrite_fail_count);
            }
            $failCount = max(0, $attemptedCount - $successCount);
            $skipSuffix = $skippedCount > 0
                ? "（原图{$originalCount}张，超出上限跳过{$skippedCount}张）"
                : '';
            $now = time();

            if ($successCount > 0) {
                if ($unit <= 0) {
                    throw new \Exception('计费单价未配置，无法按改写结果扣费');
                }
                $task->rewritten_images = $rewritten;
                $task->image_rewrite_results = $results;
                $task->image_rewrite_success_count = $successCount;
                $task->image_rewrite_fail_count = $failCount;
                $task->image_rewrite_charged_count = $successCount;
                $task->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS;
                $task->status = VideoImitationTask::STATUS_SUCCESS;
                $task->thumbnail = (string)$rewritten[0];
                $task->remarks = '图片改写成功' . $successCount . '张，失败' . $failCount . '张' . $skipSuffix;
                $task->update_time = $now;
                $task->save();
                self::deductToken(
                    $taskId,
                    $userId > 0 ? $userId : (int)$task->user_id,
                    $successCount,
                    $billingTaskId,
                    $unit
                );
                Db::commit();
                self::log(sprintf(
                    '结算成功 task_id=%d attempt_id=%s billing_task_id=%s status=%d rewrite_status=%d success=%d fail=%d charged=%d unit=%.2f points=%.2f thumbnail=%s',
                    $taskId,
                    $attemptId,
                    $billingTaskId,
                    VideoImitationTask::STATUS_SUCCESS,
                    VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS,
                    $successCount,
                    $failCount,
                    $successCount,
                    $unit,
                    round($successCount * $unit, 2),
                    mb_substr((string)$rewritten[0], 0, 120)
                ));
                return true;
            }

            $task->image_rewrite_results = $results;
            $task->image_rewrite_success_count = 0;
            $task->image_rewrite_fail_count = max($attemptedCount, count($results));
            $task->image_rewrite_charged_count = 0;
            $task->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL;
            $task->status = VideoImitationTask::STATUS_FAIL;
            $task->remarks = '图片改写全部失败' . $skipSuffix;
            $task->update_time = $now;
            $task->save();
            Db::commit();
            self::log(sprintf(
                '结算失败(全部失败) task_id=%d attempt_id=%s attempted=%d fail=%d%s',
                $taskId,
                $attemptId,
                $attemptedCount,
                max($attemptedCount, count($results)),
                $skipSuffix
            ));
            return false;
        } catch (\Throwable $th) {
            Db::rollback();
            self::log(sprintf(
                '结算异常 task_id=%d attempt_id=%s error=%s',
                $taskId,
                $attemptId,
                $th->getMessage()
            ));
            throw $th;
        }
    }

    private static function hasSettledRewrite(int $taskId): bool
    {
        $status = (int)VideoImitationTask::where('id', $taskId)->value('image_rewrite_status');
        return in_array($status, [
            VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS,
            VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL,
        ], true);
    }

    /**
     * @return array{images: array<int, string>, skipped: int, original_count: int, source: string}
     */
    private static function resolveTargetImages(VideoImitationTask $task): array
    {
        $selectedImages = self::normalizeImages($task->selected_images ?? []);
        $allImages = !empty($selectedImages)
            ? $selectedImages
            : self::normalizeImages($task->original_images);
        $limited = self::limitRewriteImages($allImages);
        return [
            'images' => $limited['images'],
            'skipped' => $limited['skipped'],
            'original_count' => count($allImages),
            'source' => !empty($selectedImages) ? 'selected_images' : 'original_images',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $resultsByIndex
     * @param array<int, string> $images
     */
    private static function runRewriteBatch(
        int $taskId,
        string $attemptId,
        int $userId,
        string $billingTaskId,
        float $unit,
        array $images,
        int $skippedCount,
        int $originalCount,
        array $resultsByIndex,
        ?callable $onHeartbeat,
        float $startedMicrotime,
        string $stage
    ): bool {
        $imageTotal = count($images);

        foreach ($images as $index => $originalImagePath) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $normalizedOriginal = self::normalizeImageKey($originalImagePath);
            $existing = $resultsByIndex[$index] ?? null;
            if (($existing['status'] ?? '') === 'success'
                || self::hasSuccessOriginal($resultsByIndex, $normalizedOriginal)
            ) {
                if (!isset($resultsByIndex[$index]) && $existing === null) {
                    // 按原图去重命中时补齐 index，便于完整判断
                    foreach ($resultsByIndex as $row) {
                        if (($row['status'] ?? '') === 'success'
                            && self::normalizeImageKey((string)($row['original_image'] ?? '')) === $normalizedOriginal
                        ) {
                            $row['index'] = $index;
                            $resultsByIndex[$index] = $row;
                            break;
                        }
                    }
                }
                self::log(sprintf(
                    '单图跳过(已成功) task_id=%d attempt_id=%s index=%d/%d stage=%s',
                    $taskId,
                    $attemptId,
                    $index + 1,
                    $imageTotal,
                    $stage
                ));
                continue;
            }

            if ($onHeartbeat !== null) {
                try {
                    $onHeartbeat();
                } catch (\Throwable $th) {
                    self::log('单图处理前运行锁续期失败 task_id=' . $taskId . ' index=' . ($index + 1) . ' error=' . $th->getMessage());
                }
            }

            if (!self::heartbeatProcessing($taskId, $attemptId, $onHeartbeat)) {
                self::log(sprintf(
                    '%s中断：心跳失败 task_id=%d attempt_id=%s index=%d/%d',
                    $stage,
                    $taskId,
                    $attemptId,
                    $index + 1,
                    $imageTotal
                ));
                return false;
            }

            // 先压缩原图再改写（幂等；历史未压本地大图在此补压）
            $beforeCompress = $originalImagePath;
            $originalImagePath = self::compressStoredImage($originalImagePath);
            if ($originalImagePath !== $beforeCompress) {
                $images[$index] = $originalImagePath;
                self::replaceImagePathInTask($taskId, $beforeCompress, $originalImagePath);
            }

            $result = [
                'index' => $index,
                'original_image' => $originalImagePath,
                'status' => 'fail',
                'result_image' => '',
                'error' => '',
            ];
            $imageStartedMicrotime = microtime(true);
            $imageUrl = FileService::getFileUrl($originalImagePath);
            self::log(sprintf(
                '单图开始 task_id=%d attempt_id=%s index=%d/%d path=%s url=%s stage=%s',
                $taskId,
                $attemptId,
                $index + 1,
                $imageTotal,
                mb_substr((string)$originalImagePath, 0, 160),
                mb_substr($imageUrl, 0, 200),
                $stage
            ));

            $editStarted = microtime(true);
            $orderedResults = [];
            try {
                $response = ToolsService::GptImage2()->editImage($imageUrl, self::FIXED_PROMPT);
                $sanitizedResponse = self::sanitizeResponse($response);
                $result['response'] = $sanitizedResponse;
                $responseCode = (int)($response['code'] ?? 0);

                self::log('单图接口返回 ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($responseCode !== 10000) {
                    throw new \Exception(sprintf(
                        '接口返回失败 code=%s msg=%s',
                        (string)($response['code'] ?? ''),
                        (string)($response['msg'] ?? $response['message'] ?? '图片改写失败')
                    ));
                }

                $generatedImage = self::extractGeneratedImage($response);
                if ($generatedImage === '') {
                    throw new \Exception('未返回可用图片');
                }

                $storedImage = self::storeGeneratedImage($generatedImage, $response);
                if ($storedImage === '') {
                    throw new \Exception('图片改写结果保存失败');
                }

                $result['status'] = 'success';
                $result['result_image'] = $storedImage;
                $result['duration_ms'] = (int)round((microtime(true) - $imageStartedMicrotime) * 1000);
                $resultsByIndex[$index] = $result;
                $orderedResults = self::orderedRewriteResults($resultsByIndex);

                $editDurationMs = (int)round((microtime(true) - $editStarted) * 1000);
                if ($editDurationMs > 300000) {
                    self::log('单图处理超时告警 task_id=' . $taskId . ' index=' . ($index + 1) . ' duration_ms=' . $editDurationMs . ' 超过300秒阈值');
                }

                self::log(sprintf(
                    '单图成功 task_id=%d index=%d/%d duration_ms=%d stored=%s stage=%s',
                    $taskId,
                    $index + 1,
                    $imageTotal,
                    $result['duration_ms'],
                    $storedImage,
                    $stage
                ));

                if (!self::appendRewrittenImageProgress($taskId, $attemptId, $storedImage, $orderedResults)) {
                    self::log(sprintf(
                        '%s中断：进度落库失败 task_id=%d attempt_id=%s index=%d/%d',
                        $stage,
                        $taskId,
                        $attemptId,
                        $index + 1,
                        $imageTotal
                    ));
                    return false;
                }
            } catch (\Throwable $th) {
                $result['error'] = $th->getMessage();
                $result['duration_ms'] = (int)round((microtime(true) - $imageStartedMicrotime) * 1000);
                $resultsByIndex[$index] = $result;
                $orderedResults = self::orderedRewriteResults($resultsByIndex);
                self::log(sprintf(
                    '单图失败 task_id=%d index=%d/%d duration_ms=%d error=%s stage=%s',
                    $taskId,
                    $index + 1,
                    $imageTotal,
                    $result['duration_ms'],
                    $th->getMessage(),
                    $stage
                ));
                if (!self::persistRewriteResultsProgress($taskId, $attemptId, $orderedResults)) {
                    self::log(sprintf(
                        '%s中断：失败结果落库失败 task_id=%d attempt_id=%s index=%d/%d',
                        $stage,
                        $taskId,
                        $attemptId,
                        $index + 1,
                        $imageTotal
                    ));
                    return false;
                }
            }

            if (!self::heartbeatProcessing($taskId, $attemptId, $onHeartbeat)) {
                self::log(sprintf(
                    '%s中断：单图后心跳失败 task_id=%d attempt_id=%s index=%d/%d',
                    $stage,
                    $taskId,
                    $attemptId,
                    $index + 1,
                    $imageTotal
                ));
                return false;
            }
        }

        $orderedResults = self::orderedRewriteResults($resultsByIndex);
        $dbRewritten = self::normalizeImages(
            VideoImitationTask::where('id', $taskId)->value('rewritten_images')
        );
        $successCount = count($dbRewritten);
        $failCount = max(0, $imageTotal - $successCount);
        $skipLog = $skippedCount > 0
            ? "，原图{$originalCount}张超出上限跳过{$skippedCount}张"
            : '';
        $elapsedMs = (int)round((microtime(true) - $startedMicrotime) * 1000);
        self::log(sprintf(
            '改写批次完成 task_id=%d attempt_id=%s 成功%d张 失败%d张 elapsed_ms=%d stage=%s%s',
            $taskId,
            $attemptId,
            $successCount,
            $failCount,
            $elapsedMs,
            $stage,
            $skipLog
        ));

        $settled = self::settleRewriteAttempt(
            $taskId,
            $attemptId,
            $userId,
            $billingTaskId,
            $unit,
            $orderedResults,
            $imageTotal,
            $skippedCount,
            $originalCount
        );
        self::log(sprintf(
            '结算结果 task_id=%d attempt_id=%s settled=%s elapsed_ms=%d stage=%s',
            $taskId,
            $attemptId,
            $settled ? '1' : '0',
            (int)round((microtime(true) - $startedMicrotime) * 1000),
            $stage
        ));
        return $settled;
    }

    /**
     * 批次完整则结算；未满图则保留 PROCESSING 供续跑（不按部分成功收尾）
     *
     * @param array<int, mixed> $results
     * @param array<int, string> $images
     */
    private static function settleIfCompleteOrLeaveResumable(
        int $taskId,
        string $attemptId,
        int $userId,
        string $billingTaskId,
        float $unit,
        array $results,
        array $images,
        int $skippedCount,
        int $originalCount,
        string $stage
    ): bool {
        $latest = VideoImitationTask::where('id', $taskId)->findOrEmpty();
        if ($latest->isEmpty()) {
            return false;
        }
        $rewritten = self::normalizeImages($latest->rewritten_images);
        $dbResults = is_array($latest->image_rewrite_results) ? $latest->image_rewrite_results : $results;
        $expected = count($images);

        if ($expected > 0 && (count($rewritten) >= $expected || self::isRewriteAttemptComplete($images, $dbResults))) {
            return self::settleRewriteAttempt(
                $taskId,
                $attemptId,
                $userId,
                $billingTaskId,
                $unit,
                $dbResults,
                $expected,
                $skippedCount,
                $originalCount
            );
        }

        if (!empty($rewritten) || !empty($dbResults)) {
            $now = time();
            VideoImitationTask::where('id', $taskId)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
                ->where('image_rewrite_task_id', $attemptId)
                ->update([
                    'status' => VideoImitationTask::STATUS_GENERATING,
                    'image_rewrite_started_at' => $now,
                    'remarks' => sprintf(
                        '图片改写%s中断，等待续跑剩余图（已成功%d张）',
                        $stage,
                        count($rewritten)
                    ),
                    'update_time' => $now,
                ]);
            self::log(sprintf(
                '%s：未满图保留PROCESSING task_id=%d attempt_id=%s rewritten=%d expected=%d',
                $stage,
                $taskId,
                $attemptId,
                count($rewritten),
                $expected
            ));
            return false;
        }

        return false;
    }

    /**
     * @param array<int, mixed> $results
     * @return array<int, array<string, mixed>>
     */
    private static function indexRewriteResults(array $results): array
    {
        $indexed = [];
        foreach ($results as $row) {
            if (!is_array($row) || !isset($row['index'])) {
                continue;
            }
            $indexed[(int)$row['index']] = $row;
        }
        return $indexed;
    }

    /**
     * @param array<int, array<string, mixed>> $resultsByIndex
     * @return array<string, true>
     */
    private static function collectSuccessOriginals(array $resultsByIndex): array
    {
        $success = [];
        foreach ($resultsByIndex as $row) {
            if (($row['status'] ?? '') !== 'success') {
                continue;
            }
            $key = self::normalizeImageKey((string)($row['original_image'] ?? ''));
            if ($key !== '') {
                $success[$key] = true;
            }
        }
        return $success;
    }

    /**
     * @param array<int, array<string, mixed>> $resultsByIndex
     */
    private static function hasSuccessOriginal(array $resultsByIndex, string $normalizedOriginal): bool
    {
        if ($normalizedOriginal === '') {
            return false;
        }
        foreach ($resultsByIndex as $row) {
            if (($row['status'] ?? '') === 'success'
                && self::normalizeImageKey((string)($row['original_image'] ?? '')) === $normalizedOriginal
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $resultsByIndex
     * @return array<int, array<string, mixed>>
     */
    private static function orderedRewriteResults(array $resultsByIndex): array
    {
        ksort($resultsByIndex);
        return array_values($resultsByIndex);
    }

    /**
     * @param array<int, string> $images
     * @param array<int, mixed> $results
     */
    private static function isRewriteAttemptComplete(array $images, array $results): bool
    {
        if (empty($images)) {
            return false;
        }
        $indexed = self::indexRewriteResults($results);
        foreach ($images as $index => $_) {
            $status = (string)($indexed[$index]['status'] ?? '');
            if ($status !== 'success' && $status !== 'fail') {
                return false;
            }
        }
        return true;
    }

    private static function normalizeImageKey(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }
        $path = parse_url($image, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            return ltrim($path, '/');
        }
        return ltrim($image, '/');
    }

    private static function normalizeImages(mixed $images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : preg_split('/[,，\s]+/', $images);
        }
        if (!is_array($images)) {
            return [];
        }

        $result = [];
        foreach ($images as $image) {
            if (is_array($image)) {
                $image = $image['url'] ?? $image['src'] ?? $image['path'] ?? $image['url_default'] ?? $image['url_pre'] ?? $image['origin_url'] ?? $image['original_url'] ?? $image['image_url'] ?? ($image['url_list'][0] ?? '') ?? ($image['info_list'][0]['url'] ?? '');
            }
            $image = trim((string)$image);
            if ($image !== '') {
                $result[] = $image;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param array<int, string> $images
     * @return array{images: array<int, string>, skipped: int}
     */
    private static function limitRewriteImages(array $images): array
    {
        $limited = array_slice(array_values($images), 0, self::MAX_REWRITE_IMAGE_COUNT);
        return [
            'images' => $limited,
            'skipped' => count($images) - count($limited),
        ];
    }

    private static function resolveBillingUnit(): float
    {
        $costRow = MediaModelsService::findCostByAlias(self::BILLING_MODEL_ALIAS, true);
        $unit = MediaModelsService::resolveUnitPrice($costRow);
        if ($unit <= 0) {
            $displayName = MediaModelsService::resolveDisplayName(
                self::BILLING_MODEL_ALIAS,
                (string)($costRow['name'] ?? self::BILLING_MODEL_ALIAS)
            );
            throw new \Exception('模型售价未配置: ' . $displayName);
        }

        return $unit;
    }

    /**
     * 确认选图/提交改写前：按张数预检个人或团队可用算力
     *
     * @throws \Exception code=4059 算力不足；售价未配置时抛业务异常
     */
    public static function assertEnoughRewriteTokens(int $userId, int $imageCount): void
    {
        if ($imageCount <= 0) {
            return;
        }
        $unit = self::resolveBillingUnit();
        self::precheckToken($userId, $imageCount, $unit);
    }

    /**
     * @throws \Exception
     */
    private static function precheckToken(int $userId, int $imageCount, float $unit): void
    {
        $points = round($unit * $imageCount, 2);
        if ($points <= 0 || $imageCount <= 0) {
            return;
        }

        $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
        if ($spendable < $points) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }
    }

    private static function extractGeneratedImage(array $response): string
    {
        $data = $response['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                if (is_array($item)) {
                    $image = trim((string)($item['b64_json'] ?? $item['url'] ?? $item['image'] ?? $item['image_url'] ?? ''));
                    if ($image !== '') {
                        return $image;
                    }
                }
            }
        }

        if (isset($data['url']) || isset($data['b64_json']) || isset($data['image']) || isset($data['image_url'])) {
            return trim((string)($data['url'] ?? $data['b64_json'] ?? $data['image'] ?? $data['image_url'] ?? ''));
        }
        if (isset($data[0]) && is_array($data[0])) {
            return trim((string)($data[0]['url'] ?? $data[0]['b64_json'] ?? $data[0]['image'] ?? $data[0]['image_url'] ?? ''));
        }

        return self::findFirstImageValue($data);
    }

    private static function findFirstImageValue(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }
        foreach (['url', 'b64_json', 'image', 'image_url'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return trim($data[$key]);
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $found = self::findFirstImageValue($value);
                if ($found !== '') {
                    return $found;
                }
            }
        }
        return '';
    }

    private static function storeGeneratedImage(string $image, array $response = []): string
    {
        if (preg_match('/^https?:\/\//i', $image)) {
            $storedImage = trim((string)FileService::downloadFileBySource($image, 'image'));
            if ($storedImage !== '' && !preg_match('/^https?:\/\//i', $storedImage)) {
                return self::relocateToResultsDir($storedImage);
            }
            return '';
        }

        $format = $response['data']['output_format'] ?? '';
        return self::saveBase64Image($image, $format);
    }

    private static function relocateToResultsDir(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || preg_match('/^https?:\/\//i', $relativePath)) {
            return '';
        }
        if (str_starts_with($relativePath, 'uploads/rewrite/results/')) {
            $existingAbsolute = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (is_file($existingAbsolute)) {
                return self::persistResultImage($existingAbsolute, $relativePath);
            }
            return $relativePath;
        }

        $localPath = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($localPath)) {
            return $relativePath;
        }

        $date = date('Ymd');
        $directory = public_path('uploads/rewrite/results/' . $date);
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            self::log('图片改写结果目录创建失败：' . $th->getMessage());
            return $relativePath;
        }

        $extension = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'png';
        $filename = date('YmdHis') . md5(uniqid('', true)) . '.' . $extension;
        $targetPath = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!@copy($localPath, $targetPath)) {
            return $relativePath;
        }
        FileService::ensureWritableFile($targetPath);

        $relativeUri = 'uploads/rewrite/results/' . $date . '/' . $filename;
        return self::persistResultImage($targetPath, $relativeUri);
    }

    private static function saveBase64Image(string $base64, string $format = ''): string
    {
        $extension = '';
        if ($format !== '') {
            $extension = strtolower($format);
        } elseif (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = strtolower($matches[1] ?: '');
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } elseif (strpos($base64, ',') !== false) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }
        if ($extension === '') {
            $extension = 'png';
        }

        $data = base64_decode($base64, true);
        if ($data === false || $data === '') {
            return '';
        }

        $date = date('Ymd');
        $directory = public_path('uploads/rewrite/results/' . $date);
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            self::log('图片改写结果目录创建失败：' . $th->getMessage());
            return '';
        }
        $filename = date('YmdHis') . md5(uniqid('', true)) . '.' . $extension;
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $data) === false) {
            return '';
        }
        FileService::ensureWritableFile($path);

        $relativeUri = 'uploads/rewrite/results/' . $date . '/' . $filename;
        return self::persistResultImage($path, $relativeUri);
    }

    /**
     * 压缩已落盘的相对路径图片（幂等）。本地不存在、远程 URL 或无需压缩时原样返回。
     */
    public static function compressStoredImage(string $relativeUri): string
    {
        $relativeUri = trim(str_replace('\\', '/', $relativeUri));
        if ($relativeUri === '' || preg_match('/^https?:\/\//i', $relativeUri)) {
            return $relativeUri;
        }
        $relativeUri = ltrim($relativeUri, '/');

        $absolutePath = self::resolvePublicLocalPath($relativeUri);
        if (!is_file($absolutePath)) {
            return $relativeUri;
        }

        $compressedPath = self::compressImage($absolutePath);
        if ($compressedPath === $absolutePath || !is_file($compressedPath)) {
            return $relativeUri;
        }

        $relativeDir = dirname($relativeUri);
        $newRelative = ($relativeDir === '.' || $relativeDir === '')
            ? basename($compressedPath)
            : $relativeDir . '/' . basename($compressedPath);
        $newRelative = ltrim(str_replace('\\', '/', $newRelative), '/');
        self::log('原图压缩后更新路径 from=' . $relativeUri . ' to=' . $newRelative);
        return $newRelative;
    }

    private static function resolvePublicLocalPath(string $relativeUri): string
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        return rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeUri);
    }

    /**
     * 原图压缩改名后回写 selected_images / original_images。
     */
    private static function replaceImagePathInTask(int $taskId, string $oldPath, string $newPath): void
    {
        if ($taskId <= 0 || $oldPath === '' || $newPath === '' || $oldPath === $newPath) {
            return;
        }
        $oldKey = self::normalizeImageKey($oldPath);
        try {
            $task = VideoImitationTask::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                return;
            }
            $changed = false;
            $selected = self::normalizeImages($task->selected_images ?? []);
            foreach ($selected as $i => $image) {
                if (self::normalizeImageKey((string)$image) === $oldKey) {
                    $selected[$i] = $newPath;
                    $changed = true;
                }
            }
            $original = self::normalizeImages($task->original_images);
            foreach ($original as $i => $image) {
                if (self::normalizeImageKey((string)$image) === $oldKey) {
                    $original[$i] = $newPath;
                    $changed = true;
                }
            }
            if (!$changed) {
                return;
            }
            if (!empty($selected)) {
                $task->selected_images = array_values($selected);
            }
            $task->original_images = array_values($original);
            $task->update_time = time();
            $task->save();
            self::log(sprintf(
                '原图路径已回写 task_id=%d from=%s to=%s',
                $taskId,
                $oldPath,
                $newPath
            ));
        } catch (\Throwable $th) {
            self::log(sprintf(
                '原图路径回写失败 task_id=%d error=%s',
                $taskId,
                $th->getMessage()
            ));
        }
    }

    /**
     * 压缩图片：对大图进行无损压缩以减小体积
     *
     * @param string $absolutePath 图片绝对路径
     * @param int $quality JPEG压缩质量（1-100）
     * @param bool $toJpeg 是否将PNG转为JPEG
     * @param int $maxSizeBytes 触发压缩的最小文件大小（字节）
     * @return string 压缩后的文件绝对路径，压缩失败返回原路径
     */
    private static function compressImage(
        string $absolutePath,
        int $quality = 85,
        bool $toJpeg = true,
        int $maxSizeBytes = 512000
    ): string {
        if ($absolutePath === '' || !is_file($absolutePath)) {
            return $absolutePath;
        }

        $fileSize = @filesize($absolutePath);
        if ($fileSize !== false && $fileSize <= $maxSizeBytes) {
            return $absolutePath;
        }

        $imageInfo = @getimagesize($absolutePath);
        if ($imageInfo === false) {
            return $absolutePath;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return $absolutePath;
        }

        $newExtension = ($toJpeg && $extension !== 'webp') ? 'jpg' : $extension;

        $image = match ($extension) {
            'png' => @imagecreatefrompng($absolutePath),
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
            'gif' => @imagecreatefromgif($absolutePath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            default => false,
        };

        if ($image === false) {
            return $absolutePath;
        }

        if ($extension === 'png' && $toJpeg) {
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagedestroy($image);
            $image = $canvas;
        }

        $newPath = preg_replace(
            '/\\.' . preg_quote($extension, '/') . '$/',
            '.' . $newExtension,
            $absolutePath
        );

        $saved = false;
        if ($newExtension === 'jpg') {
            $saved = imagejpeg($image, $newPath, $quality);
        } elseif ($newExtension === 'png') {
            $saved = imagepng($image, $newPath, 9);
        } elseif ($newExtension === 'webp' && function_exists('imagewebp')) {
            $saved = imagewebp($image, $newPath, $quality);
        } else {
            $saved = imagejpeg($image, $newPath, $quality);
        }

        imagedestroy($image);

        if (!$saved) {
            self::log('图片压缩失败：写入失败 path=' . $absolutePath);
            return $absolutePath;
        }

        if ($newPath !== $absolutePath && @file_exists($newPath)) {
            @unlink($absolutePath);
        }

        $compressedSize = @filesize($newPath);
        self::log(sprintf(
            '图片压缩完成 path=%s original=%d compressed=%d ratio=%.1f%%',
            basename($newPath),
            $fileSize,
            $compressedSize !== false ? $compressedSize : 0,
            $fileSize > 0 && $compressedSize !== false ? round(($compressedSize / $fileSize) * 100, 1) : 0
        ));

        return $newPath;
    }

    /**
     * 本地落盘后按存储配置收尾：local 保留文件；非 local 上传 OSS 并删本地。
     * 上传失败返回空串，由调用方记为该张改写失败。
     */
    private static function persistResultImage(string $absolutePath, string $relativeUri): string
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        if ($absolutePath === '' || $relativeUri === '' || !is_file($absolutePath)) {
            self::log(sprintf(
                '结果图持久化跳过：本地文件无效 path=%s abs=%s',
                $relativeUri !== '' ? $relativeUri : '-',
                $absolutePath !== '' ? $absolutePath : '-'
            ));
            return '';
        }

        $compressedPath = self::compressImage($absolutePath);
        if ($compressedPath !== $absolutePath && is_file($compressedPath)) {
            $absolutePath = $compressedPath;
            $compressedFilename = basename($compressedPath);
            $relativeDir = dirname($relativeUri);
            $relativeUri = $relativeDir . '/' . $compressedFilename;
            self::log('图片压缩后更新路径 uri=' . $relativeUri);
        }

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return $relativeUri;
        }

        try {
            self::uploadResultImageToRemote($absolutePath, $relativeUri);
        } catch (\Throwable $th) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            self::log(sprintf(
                '结果图上传OSS失败 path=%s storage=%s error=%s',
                $relativeUri,
                $storageDefault,
                $th->getMessage()
            ));
            return '';
        }

        if (is_file($absolutePath) && !@unlink($absolutePath)) {
            self::log(sprintf(
                '结果图上传OSS成功但本地删除失败 path=%s abs=%s',
                $relativeUri,
                $absolutePath
            ));
        } else {
            self::log(sprintf(
                '结果图上传OSS成功并已删除本地 path=%s storage=%s',
                $relativeUri,
                $storageDefault
            ));
        }

        return $relativeUri;
    }

    private static function uploadResultImageToRemote(string $absolutePath, string $relativeUri): void
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        $filename = basename($relativeUri);
        $saveDir = dirname($relativeUri);
        if ($saveDir === '.' || $saveDir === '\\') {
            $saveDir = '';
        }

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return;
        }

        $storageConfig = [
            'default' => $storageDefault,
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ];
        $storageDriver = new StorageDriver($storageConfig);
        $storageDriver->setUploadFileByFileName($absolutePath, $filename);
        if (!$storageDriver->upload($saveDir)) {
            throw new \RuntimeException($storageDriver->getError() ?: '上传失败');
        }
    }

    private static function deductToken(int $taskId, int $userId, int $imgCount, string $taskIdKey, float $unit): void
    {
        $points = round($imgCount * $unit, 2);
        if ($points <= 0) {
            return;
        }

        $user = User::where('id', $userId)->lock(true)->findOrEmpty();
        if ($user->isEmpty()) {
            throw new \Exception('用户查询失败');
        }
        if (self::hasTokenLog($userId, $taskIdKey)) {
            return;
        }

        $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
        if ($spendable < $points) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }

        User::userTokensChange($userId, $points);
        $modelDisplay = MediaModelsService::resolveDisplayName(self::BILLING_MODEL_ALIAS, self::BILLING_MODEL_ALIAS);
        $extra = [
            '生成图片数'   => $imgCount,
            '算力单价'     => $unit,
            '实际消耗算力' => $points,
            '模型'         => self::BILLING_MODEL_ALIAS,
            '模型名称'     => $modelDisplay,
            '场景'         => '手动-小红书图文仿写',
            'task_id'      => $taskId,
        ];
        AccountLogLogic::recordUserTokensLog(
            true,
            $userId,
            AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE,
            $points,
            $taskIdKey,
            $extra
        );
    }

    private static function hasTokenLog(int $userId, string $taskIdKey): bool
    {
        return !UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskIdKey)
            ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE)
            ->where('action', AccountLogEnum::DEC)
            ->findOrEmpty()
            ->isEmpty();
    }

    private static function sanitizeResponse(mixed $response)
    {
        if (!is_array($response)) {
            return $response;
        }

        foreach ($response as $key => $value) {
            if ($key === 'b64_json' && is_string($value)) {
                $response[$key] = '[base64 omitted]';
                continue;
            }
            if (is_array($value)) {
                $response[$key] = self::sanitizeResponse($value);
            }
        }

        return $response;
    }

    private static function buildTaskId(int $taskId, int $billingRound = 1): string
    {
        return 'video_imitation_img_' . $taskId . '_r' . max(1, $billingRound);
    }

    private static function buildAttemptTaskId(
        int $taskId,
        int $retryCount,
        int $startedAt,
        int $billingRound = 1
    ): string {
        return self::buildTaskId($taskId, $billingRound) . '_attempt_' . $retryCount . '_' . $startedAt;
    }

    private static function log(string $message): void
    {
        Log::channel('manual_2img')->write('[service] ' . $message);
    }
}
