<?php

namespace app\common\service\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\model\sv\SvMediaMaterial;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\draw\MediaModelsService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\ToolsService;
use think\facade\Db;
use think\facade\Log;

class ViralImageRewriteService
{
    public const PROCESSING_TIMEOUT_SECONDS = 1800;
    public const MAX_RETRY_COUNT = 2;
    /** 单次图文改写最多尝试/成功张数 */
    public const MAX_REWRITE_IMAGE_COUNT = 12;

    /** 图生图计费模型（技术 alias，展示名 image-2） */
    private const BILLING_MODEL_ALIAS = 'gpt-image-2';

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

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

    public static function submit(SvDeviceViralRecord $record, ?callable $onHeartbeat = null): bool
    {
        if ((int)$record->image_rewrite_status !== SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT) {
            return false;
        }

        $recordId = (int)$record->id;
        if ($recordId <= 0) {
            return false;
        }

        $billingTaskId = self::buildTaskId($recordId);
        $attemptId = '';
        $unit = 0.0;
        $images = [];
        $skippedCount = 0;
        $originalCount = 0;
        $results = [];
        $localFilesToCleanup = [];
        $userId = (int)$record->user_id;
        try {
            $allImages = self::normalizeImages($record->original_images);
            if (empty($allImages)) {
                throw new \Exception('原图列表为空');
            }
            $limited = self::limitRewriteImages($allImages);
            $images = $limited['images'];
            $skippedCount = $limited['skipped'];
            $originalCount = count($allImages);

            // 生图前先校验模型状态/售价，再预检余额；通过后才进入 PROCESSING（按截断后张数，最多 12）
            $unit = self::resolveBillingUnit();
            self::precheckToken($userId, count($images), $unit);
            $startedAt = time();
            $attemptId = self::buildAttemptTaskId(
                $recordId,
                (int)$record->image_rewrite_retry_count,
                $startedAt
            );

            $affected = SvDeviceViralRecord::where('id', $recordId)
                ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT)
                ->where('image_rewrite_charged_count', 0)
                ->update([
                    'image_rewrite_task_id' => $attemptId,
                    'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING,
                    'image_rewrite_started_at' => $startedAt,
                    'rewritten_images' => '[]',
                    'image_rewrite_results' => '[]',
                    'image_rewrite_success_count' => 0,
                    'image_rewrite_fail_count' => 0,
                    'remark' => 'GPT-2图像改写处理中',
                    'update_time' => $startedAt,
                ]);
            if ($affected === 0) {
                return false;
            }

            $record = SvDeviceViralRecord::where('id', $recordId)->findOrEmpty();
            if ($record->isEmpty()) {
                return false;
            }
            $userId = (int)$record->user_id;

            foreach ($images as $index => $originalImagePath) {
                if (!self::heartbeatProcessing($recordId, $attemptId, $onHeartbeat)) {
                    // 失联交由 recoverExpired / 并发收尾按已写入的 rewritten_images 结算
                    return false;
                }

                // 先压缩原图再改写（幂等；历史未压本地大图在此补压）
                $beforeCompress = $originalImagePath;
                $originalImagePath = self::compressStoredImage($originalImagePath);
                if ($originalImagePath !== $beforeCompress) {
                    $images[$index] = $originalImagePath;
                    self::replaceOriginalImagePath($recordId, $beforeCompress, $originalImagePath);
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
                Log::channel('auto')->write("\nGPT-2图像改写开始 " . $imageUrl, 'img2');
                try {
                    $response = ToolsService::GptImage2()->editImage($imageUrl, self::FIXED_PROMPT);

                    $sanitizedResponse = self::sanitizeResponse($response);
                    $result['response'] = $sanitizedResponse;
                    Log::channel('auto')->write("GPT-2图像改写响应 " . json_encode($sanitizedResponse, JSON_UNESCAPED_UNICODE), 'img2');
                    if ((int)($response['code'] ?? 0) !== 10000) {
                        throw new \Exception((string)($response['msg'] ?? $response['message'] ?? 'GPT-2图像改写失败'));
                    }

                    $generatedImage = self::extractGeneratedImage($response);
                    if ($generatedImage === '') {
                        throw new \Exception('GPT-2未返回可用图片');
                    }

                    $storedImage = self::storeGeneratedImage($generatedImage, $response);
                    if ($storedImage === '') {
                        throw new \Exception('GPT-2图像改写图片保存失败');
                    }
                    Log::channel('auto')->write("GPT-2图像改写成功 " . $storedImage . "\n", 'img2');

                    // 单张先上 OSS，再立即追加 rewritten_images
                    $platformType = (int)($record->publish_platform ?? 0);
                    $uploadedLocals = self::uploadRewriteImagesToOss(
                        [$originalImagePath],
                        [$storedImage],
                        $recordId,
                        $userId,
                        $platformType
                    );
                    foreach ($uploadedLocals as $localPath) {
                        $localFilesToCleanup[] = $localPath;
                    }

                    $result['status'] = 'success';
                    $result['result_image'] = $storedImage;
                    $result['duration_ms'] = (int)round((microtime(true) - $imageStartedMicrotime) * 1000);
                    $results[] = $result;

                    if (!self::appendRewrittenImageProgress($recordId, $attemptId, $storedImage, $results)) {
                        return false;
                    }
                } catch (\Throwable $th) {
                    $result['error'] = $th->getMessage();
                    Log::channel('auto')->write("GPT-2图像改写失败 " . $th->getMessage(), 'img2');
                    $result['duration_ms'] = (int)round((microtime(true) - $imageStartedMicrotime) * 1000);
                    $results[] = $result;
                    if (!self::persistRewriteResultsProgress($recordId, $attemptId, $results)) {
                        return false;
                    }
                }

                // 单图结束后再心跳，避免仅靠图前心跳导致长请求被误判失联
                if (!self::heartbeatProcessing($recordId, $attemptId, $onHeartbeat)) {
                    return false;
                }
            }

            $dbRewritten = self::normalizeImages(
                SvDeviceViralRecord::where('id', $recordId)->value('rewritten_images')
            );
            $successCount = count($dbRewritten);
            $failCount = max(0, count($images) - $successCount);
            $skipLog = $skippedCount > 0
                ? "，原图{$originalCount}张超出上限跳过{$skippedCount}张"
                : '';
            Log::channel('auto')->write(
                "GPT-2图像改写完成 " . $successCount . '张成功，' . $failCount . '张失败' . $skipLog . "\n\n",
                'img2'
            );

            return self::settleRewriteAttempt(
                $recordId,
                $attemptId,
                $userId,
                $billingTaskId,
                $unit,
                $results,
                count($images),
                $skippedCount,
                $originalCount,
                $localFilesToCleanup
            );
        } catch (\Throwable $th) {
            Log::channel('auto')->write(
                "GPT-2图像改写提交失败 record_id={$recordId} error=" . $th->getMessage(),
                'img2'
            );

            if ($attemptId !== '') {
                try {
                    if ($unit <= 0) {
                        $unit = self::resolveBillingUnit();
                    }
                    $settled = self::settleRewriteAttempt(
                        $recordId,
                        $attemptId,
                        $userId,
                        $billingTaskId,
                        $unit,
                        $results,
                        count($images),
                        $skippedCount,
                        $originalCount,
                        $localFilesToCleanup
                    );
                    // settle 已按 rewritten_images 落 SUCCESS/FAIL
                    if ($settled || self::hasSettledRewrite($recordId)) {
                        return $settled;
                    }
                } catch (\Throwable $settleTh) {
                    Log::channel('auto')->write(
                        "GPT-2图像改写异常收尾失败 record_id={$recordId} error=" . $settleTh->getMessage(),
                        'img2'
                    );
                }

                SvDeviceViralRecord::where('id', $recordId)
                    ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
                    ->where('image_rewrite_task_id', $attemptId)
                    ->update([
                        'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
                        'status' => 5,
                        'remark' => '图文图片改写提交失败：' . $th->getMessage(),
                        'update_time' => time(),
                    ]);
                return false;
            }

            SvDeviceViralRecord::where('id', $recordId)
                ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT)
                ->update([
                    'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
                    'status' => 5,
                    'remark' => '图文图片改写提交失败：' . $th->getMessage(),
                    'update_time' => time(),
                ]);
            return false;
        }
    }

    public static function sync(SvDeviceViralRecord $record, ?callable $onHeartbeat = null): bool
    {
        if ((int)$record->image_rewrite_status === SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT) {
            return self::submit($record, $onHeartbeat);
        }

        return false;
    }

    /**
     * 刷新 PROCESSING 心跳；若任务已被回收则返回 false。
     * 心跳成功后可选调用 $onHeartbeat（用于续期运行锁）。
     */
    private static function heartbeatProcessing(int $recordId, string $attemptId, ?callable $onHeartbeat = null): bool
    {
        $heartbeatAt = time();
        $heartbeatAffected = SvDeviceViralRecord::where('id', $recordId)
            ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->update([
                'image_rewrite_started_at' => $heartbeatAt,
                'update_time' => $heartbeatAt,
            ]);
        // 同一秒内字段值未变时 MySQL affected_rows 可能为 0，需再确认任务仍归属本 attempt
        if ($heartbeatAffected === 0) {
            $stillMine = SvDeviceViralRecord::where('id', $recordId)
                ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
                ->where('image_rewrite_task_id', $attemptId)
                ->count();
            if ($stillMine === 0) {
                Log::channel('auto')->write("GPT-2图像改写任务已被回收 record_id={$recordId}", 'img2');
                return false;
            }
        }

        if ($onHeartbeat !== null) {
            try {
                $onHeartbeat();
            } catch (\Throwable $th) {
                Log::channel('auto')->write(
                    "GPT-2图像改写心跳回调失败 record_id={$recordId} error=" . $th->getMessage(),
                    'img2'
                );
            }
        }

        return true;
    }

    /**
     * 单张成功后追加 rewritten_images，并同步 results/计数（仍保持 PROCESSING）。
     */
    private static function appendRewrittenImageProgress(
        int $recordId,
        string $attemptId,
        string $storedImage,
        array $results
    ): bool {
        Db::startTrans();
        try {
            $record = SvDeviceViralRecord::where('id', $recordId)->lock(true)->findOrEmpty();
            if ($record->isEmpty()
                || (int)$record->image_rewrite_status !== SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING
                || (string)$record->image_rewrite_task_id !== $attemptId
            ) {
                Db::rollback();
                Log::channel('auto')->write("GPT-2图像改写任务已被回收 record_id={$recordId}", 'img2');
                return false;
            }

            $rewritten = self::normalizeImages($record->rewritten_images);
            $rewritten[] = $storedImage;
            $rewritten = array_values(array_unique($rewritten));
            $successCount = count($rewritten);
            $now = time();

            $record->rewritten_images = $rewritten;
            $record->image_rewrite_results = $results;
            $record->image_rewrite_success_count = $successCount;
            $record->image_rewrite_started_at = $now;
            $record->remark = 'GPT-2图像改写处理中，已成功' . $successCount . '张';
            $record->update_time = $now;
            $record->save();
            Db::commit();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            Log::channel('auto')->write(
                "GPT-2图像改写进度落库失败 record_id={$recordId} error=" . $th->getMessage(),
                'img2'
            );
            throw $th;
        }
    }

    /**
     * 仅同步 results（失败张也落库），便于收尾统计。
     */
    private static function persistRewriteResultsProgress(int $recordId, string $attemptId, array $results): bool
    {
        $now = time();
        $affected = SvDeviceViralRecord::where('id', $recordId)
            ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->update([
                'image_rewrite_results' => json_encode($results, JSON_UNESCAPED_UNICODE),
                'image_rewrite_started_at' => $now,
                'update_time' => $now,
            ]);
        if ($affected > 0) {
            return true;
        }

        $stillMine = SvDeviceViralRecord::where('id', $recordId)
            ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->count();
        if ($stillMine === 0) {
            Log::channel('auto')->write("GPT-2图像改写任务已被回收 record_id={$recordId}", 'img2');
            return false;
        }
        return true;
    }

    /**
     * 按库内 rewritten_images 张数扣费并落 SUCCESS/FAIL。
     */
    private static function settleRewriteAttempt(
        int $recordId,
        string $attemptId,
        int $userId,
        string $billingTaskId,
        float $unit,
        array $results,
        int $attemptedCount,
        int $skippedCount,
        int $originalCount,
        array $localFilesToCleanup = []
    ): bool {
        Db::startTrans();
        try {
            $record = SvDeviceViralRecord::where('id', $recordId)->lock(true)->findOrEmpty();
            if ($record->isEmpty()
                || (int)$record->image_rewrite_status !== SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING
                || (string)$record->image_rewrite_task_id !== $attemptId
            ) {
                Db::rollback();
                return (int)($record->image_rewrite_status ?? 0) === SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS;
            }

            $rewritten = self::normalizeImages($record->rewritten_images);
            $successCount = count($rewritten);
            if (empty($results)) {
                $results = is_array($record->image_rewrite_results) ? $record->image_rewrite_results : [];
            }
            if ($attemptedCount <= 0) {
                $attemptedCount = !empty($results)
                    ? count($results)
                    : max($successCount, (int)$record->image_rewrite_success_count + (int)$record->image_rewrite_fail_count);
            }
            $failCount = max(0, $attemptedCount - $successCount);

            $skipSuffix = $skippedCount > 0
                ? "（原图{$originalCount}张，超出上限跳过{$skippedCount}张）"
                : '';
            $now = time();

            if ($successCount > 0) {
                if ($unit <= 0) {
                    throw new \Exception('模型售价未配置，无法按改写结果扣费');
                }
                $record->rewritten_images = $rewritten;
                $record->image_rewrite_results = $results;
                $record->image_rewrite_success_count = $successCount;
                $record->image_rewrite_fail_count = $failCount;
                $record->image_rewrite_charged_count = $successCount;
                $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS;
                $record->status = 4;
                $record->remark = '图文图片改写成功' . $successCount . '张，失败' . $failCount . '张' . $skipSuffix;
                $record->update_time = $now;
                $record->save();
                self::deductToken(
                    $recordId,
                    $userId > 0 ? $userId : (int)$record->user_id,
                    $successCount,
                    $billingTaskId,
                    $unit
                );
                Db::commit();
                self::deleteLocalRewriteImages($localFilesToCleanup, $recordId);
                return true;
            }

            $record->image_rewrite_results = $results;
            $record->image_rewrite_success_count = 0;
            $record->image_rewrite_fail_count = max($attemptedCount, count($results));
            $record->image_rewrite_charged_count = 0;
            $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL;
            $record->status = 5;
            $record->remark = '图文图片改写全部失败' . $skipSuffix;
            $record->update_time = $now;
            $record->save();
            Db::commit();
            return false;
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    private static function hasSettledRewrite(int $recordId): bool
    {
        $status = (int)SvDeviceViralRecord::where('id', $recordId)->value('image_rewrite_status');
        return in_array($status, [
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS,
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
        ], true);
    }

    public static function recoverExpired(SvDeviceViralRecord $record): bool
    {
        if ((int)$record->image_rewrite_status !== SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING
            || !self::isProcessingExpired((int)$record->image_rewrite_started_at)
        ) {
            return false;
        }

        $recordId = (int)$record->id;
        $attemptId = (string)$record->image_rewrite_task_id;
        $startedAt = (int)$record->image_rewrite_started_at;
        $rewritten = self::normalizeImages($record->rewritten_images);

        // 超时但已有成功图：按 rewritten_images 扣费并 SUCCESS，不再重试
        if (!empty($rewritten)) {
            try {
                $unit = self::resolveBillingUnit();
                $results = is_array($record->image_rewrite_results) ? $record->image_rewrite_results : [];
                $attemptedCount = !empty($results)
                    ? count($results)
                    : max(count($rewritten), (int)$record->image_rewrite_success_count + (int)$record->image_rewrite_fail_count);

                return self::settleRewriteAttempt(
                    $recordId,
                    $attemptId,
                    (int)$record->user_id,
                    self::buildTaskId($recordId),
                    $unit,
                    $results,
                    $attemptedCount,
                    0,
                    0,
                    []
                );
            } catch (\Throwable $th) {
                Log::channel('auto')->write(
                    "GPT-2图像改写超时收尾失败 record_id={$recordId} error=" . $th->getMessage(),
                    'img2'
                );
                return false;
            }
        }

        $query = SvDeviceViralRecord::where('id', $recordId)
            ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
            ->where('image_rewrite_task_id', $attemptId)
            ->where('image_rewrite_started_at', $startedAt);

        $retryCount = (int)$record->image_rewrite_retry_count;
        if (!self::canRetry($retryCount)) {
            $query->update([
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
                'status' => 5,
                'remark' => '图文图片改写失联，已达到最大重试次数',
                'update_time' => time(),
            ]);
            return false;
        }

        $nextRetryCount = $retryCount + 1;
        $affected = $query->update([
            'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT,
            'image_rewrite_retry_count' => $nextRetryCount,
            'remark' => "图文图片改写超时，等待第{$nextRetryCount}次重试",
            'update_time' => time(),
        ]);

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

    private static function disabledLegacyImageRewrite(SvDeviceViralRecord $record): bool
    {
        try {
            $taskId = (string)$record->image_rewrite_task_id;
            if ($taskId === '') {
                throw new \Exception('图生图任务ID为空');
            }

            $response = ['code' => 0, 'msg' => '旧版异步图生图已禁用'];
            if ((int)($response['code'] ?? 0) !== 10000) {
                throw new \Exception((string)($response['msg'] ?? $response['message'] ?? '获取图生图结果失败'));
            }

            $data = $response['data'] ?? [];
            $results = $data['sub_task_results'] ?? [];
            if (empty($results)) {
                $record->remark = '图生图处理中';
                $record->update_time = time();
                $record->save();
                return false;
            }

            $hasPending = false;
            $rewrittenImages = [];
            $rewriteResults = [];
            foreach ($results as $index => $item) {
                $taskStatus = (int)($item['task_status'] ?? 0);
                $rewriteResult = [
                    'index' => $index,
                    'status' => 'fail',
                    'result_image' => '',
                    'response' => self::sanitizeResponse($item),
                ];
                if (in_array($taskStatus, [0, 2], true)) {
                    $hasPending = true;
                    $rewriteResult['status'] = 'processing';
                    $rewriteResults[] = $rewriteResult;
                    continue;
                }
                if ($taskStatus === 1 && !empty($item['image'])) {
                    $image = FileService::downloadFileBySource((string)$item['image'], 'image');
                    if ($image !== '') {
                        $rewrittenImages[] = $image;
                        $rewriteResult['status'] = 'success';
                        $rewriteResult['result_image'] = $image;
                    }
                }
                $rewriteResults[] = $rewriteResult;
            }

            if ($hasPending) {
                $record->image_rewrite_results = $rewriteResults;
                $record->remark = '图生图处理中';
                $record->update_time = time();
                $record->save();
                return false;
            }

            $rewrittenImages = array_values(array_filter(array_unique($rewrittenImages)));
            $successCount = count($rewrittenImages);
            if ($successCount === 0) {
                throw new \Exception('图生图未返回可用图片');
            }

            $record->rewritten_images = $rewrittenImages;
            $record->image_rewrite_results = $rewriteResults;
            $record->image_rewrite_success_count = $successCount;
            $record->image_rewrite_fail_count = max(0, count($results) - $successCount);
            $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS;
            $record->status = 4;
            $record->remark = '图生图改写成功';
            $record->update_time = time();
            $record->save();
            return true;
        } catch (\Throwable $th) {
            $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL;
            $record->status = 5;
            $record->remark = '图生图同步失败：' . $th->getMessage();
            $record->update_time = time();
            $record->save();
            return false;
        }
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
     * 单次改写最多处理 MAX_REWRITE_IMAGE_COUNT 张，超出部分跳过（不计入失败）。
     *
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

    /**
     * 估算图文改写所需算力（gpt-image-2 单价 × 张数，最多 12 张）
     *
     * @throws \Exception 模型未配置/未启用/售价无效
     */
    public static function estimateRewritePoints(int $imageCount): array
    {
        $unit = self::resolveBillingUnit();
        $count = max(0, min($imageCount, self::MAX_REWRITE_IMAGE_COUNT));
        return [
            'unit' => $unit,
            'image_count' => $count,
            'points' => round($unit * $count, 2),
            'model' => self::BILLING_MODEL_ALIAS,
        ];
    }

    /**
     * 生图前校验计费模型：存在、启用、主模型启用、售价 > 0
     *
     * @throws \Exception
     */
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
     * @throws \Exception
     */
    private static function precheckToken(int $userId, int $imageCount, float $unit): void
    {
        $points = round($unit * $imageCount, 2);
        if ($points <= 0) {
            return;
        }

        $user = User::findOrEmpty($userId)->toArray();
        if (empty($user)) {
            throw new \Exception('用户查询失败');
        }
        // 企业空间成员看企业钱包，勿用个人 tokens 预检（与 deductToken 一致）
        $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
        if ($spendable < $points) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception(
                $msg . "（需要{$points}，可用{$spendable}，单价{$unit}×{$imageCount}张）",
                4059
            );
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
            if ($storedImage === '' || preg_match('/^https?:\/\//i', $storedImage)) {
                return $storedImage;
            }

            $absolutePath = self::resolvePublicLocalPath($storedImage);
            if (is_file($absolutePath)) {
                $compressedPath = self::compressImage($absolutePath);
                if ($compressedPath !== $absolutePath && is_file($compressedPath)) {
                    @unlink($absolutePath);
                    $compressedFilename = basename($compressedPath);
                    $compressedDir = dirname($storedImage);
                    return $compressedDir . '/' . $compressedFilename;
                }
            }
            return $storedImage;
        }

        $format = $response['data']['output_format'] ?? '';
        
        return self::saveBase64Image($image, $format);
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
        $directory = public_path('uploads/rewrite/gpt2images/' . $date);
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            Log::channel('auto')->write('图文改写结果目录创建失败：' . $directory . ' error=' . $th->getMessage(), 'img2');
            return '';
        }
        $filename = date('YmdHis') . md5(uniqid('', true)) . '.' . $extension;
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $data) === false) {
            return '';
        }
        FileService::ensureWritableFile($path);

        $compressedPath = self::compressImage($path);
        if ($compressedPath !== $path && is_file($compressedPath)) {
            @unlink($path);
            $filename = basename($compressedPath);
            $path = $compressedPath;
        }

        return 'uploads/rewrite/gpt2images/' . $date . '/' . $filename;
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
            Log::channel('auto')->write('图文改写图片压缩失败：写入失败 path=' . $absolutePath, 'img2');
            return $absolutePath;
        }

        if ($newPath !== $absolutePath && @file_exists($newPath)) {
            @unlink($absolutePath);
        }

        $compressedSize = @filesize($newPath);
        Log::channel('auto')->write(sprintf(
            '图文改写图片压缩完成 path=%s original=%d compressed=%d ratio=%.1f%%',
            basename($newPath),
            $fileSize,
            $compressedSize !== false ? $compressedSize : 0,
            $fileSize > 0 && $compressedSize !== false ? round(($compressedSize / $fileSize) * 100, 1) : 0
        ), 'img2');

        return $newPath;
    }

    /**
     * 收集本地原图 + 成功图生图结果，全部上传 OSS。
     * 存储为 local 时跳过。
     * 本地缺失时若远端已存在则跳过（兼容多记录共享原图、前序任务已清理本地的场景）。
     * 本地与远端均不存在时抛异常（不删本地）。
     *
     * @param array $originalImages
     * @param array $rewrittenImages
     * @return array<int, string> 上传成功后待删除的本地绝对路径（仅 gpt2 结果图，不含共享原图）
     */
    private static function uploadRewriteImagesToOss(
        array $originalImages,
        array $rewrittenImages,
        int $recordId,
        int $userId = 0,
        int $platformType = 0
    ): array {
        $relativePaths = self::collectLocalRewriteImagePaths($originalImages, $rewrittenImages);
        if (empty($relativePaths)) {
            Log::channel('auto')->write(
                "图文改写OSS上传跳过：无本地图片 record_id={$recordId}",
                'img2'
            );
            return [];
        }

        $storageDefault = self::resolveStorageDefault();
        if ($storageDefault === 'local') {
            self::persistLocalRewrittenMaterials($rewrittenImages, $userId, $platformType);
            Log::channel('auto')->write(
                "图文改写OSS上传跳过：当前存储为local record_id={$recordId} count=" . count($relativePaths),
                'img2'
            );
            return [];
        }

        $storageConfig = [
            'default' => $storageDefault,
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ];

        $rewrittenRelativeSet = [];
        foreach ($rewrittenImages as $image) {
            $relativeUri = self::normalizeLocalRelativePath((string)$image);
            if ($relativeUri !== '') {
                $rewrittenRelativeSet[$relativeUri] = true;
            }
        }

        Log::channel('auto')->write(
            "图文改写开始上传OSS record_id={$recordId} storage={$storageDefault} files="
            . json_encode($relativePaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n",
            'img2'
        );

        $localAbsolutePaths = [];
        $uploadedCount = 0;
        $skippedCount = 0;
        foreach ($relativePaths as $relativeUri) {
            $localPath = self::resolvePublicLocalPath($relativeUri);
            if (!is_file($localPath)) {
                if (self::remoteRewriteImageExists($relativeUri)) {
                    $skippedCount++;
                    Log::channel('auto')->write(
                        "图文改写OSS上传跳过：本地文件不存在但远端已存在 record_id={$recordId} path={$relativeUri}",
                        'img2'
                    );
                    continue;
                }

                $isResult = isset($rewrittenRelativeSet[$relativeUri]);
                Log::channel('auto')->write(
                    "图文改写OSS上传失败：本地与远端均不存在 record_id={$recordId} path={$relativeUri}"
                    . " abs={$localPath} type=" . ($isResult ? 'result' : 'original'),
                    'img2'
                );
                throw new \Exception(
                    ($isResult ? '图文改写结果图' : '图文改写原图')
                    . '本地与远端均不存在：' . $relativeUri
                );
            }

            $isResult = isset($rewrittenRelativeSet[$relativeUri]);
            $size = 0;
            if ($isResult) {
                $gotSize = @filesize($localPath);
                if ($gotSize !== false && $gotSize > 0) {
                    $size = (int)$gotSize;
                }
            }

            try {
                self::uploadLocalFileToRemoteStorage($storageConfig, $localPath, $relativeUri);
            } catch (\Throwable $th) {
                Log::channel('auto')->write(
                    "图文改写OSS上传失败 record_id={$recordId} path={$relativeUri} error=" . $th->getMessage(),
                    'img2'
                );
                throw new \Exception('图文改写图片上传OSS失败：' . $relativeUri . '，' . $th->getMessage(), 0, $th);
            }

            if ($isResult) {
                self::persistRewrittenMaterial($userId, $platformType, $relativeUri, $size);
            }

            $uploadedCount++;
            // 仅清理本任务生成的 gpt2 结果图，避免删除多记录共享的原图
            if (self::isGpt2RewriteResultPath($relativeUri)) {
                $localAbsolutePaths[] = $localPath;
            }
            Log::channel('auto')->write(
                "图文改写OSS上传成功 record_id={$recordId} path={$relativeUri}",
                'img2'
            );
        }

        Log::channel('auto')->write(
            "图文改写OSS全部上传成功 record_id={$recordId} uploaded={$uploadedCount} skipped={$skippedCount}"
            . " cleanup=" . count($localAbsolutePaths),
            'img2'
        );

        return $localAbsolutePaths;
    }

    /**
     * @param array<int, mixed> $rewrittenImages
     */
    private static function persistLocalRewrittenMaterials(array $rewrittenImages, int $userId, int $platformType): void
    {
        foreach ($rewrittenImages as $image) {
            $relativeUri = self::normalizeLocalRelativePath((string)$image);
            if ($relativeUri === '') {
                continue;
            }
            $localPath = self::resolvePublicLocalPath($relativeUri);
            if (!is_file($localPath)) {
                continue;
            }
            $size = 0;
            $gotSize = @filesize($localPath);
            if ($gotSize !== false && $gotSize > 0) {
                $size = (int)$gotSize;
            }
            self::persistRewrittenMaterial($userId, $platformType, $relativeUri, $size);
        }
    }

    private static function persistRewrittenMaterial(int $userId, int $platformType, string $relativeUri, int $size): void
    {
        if ($userId <= 0 || $relativeUri === '') {
            return;
        }
        if ($platformType <= 0) {
            $platformType = DeviceEnum::ACCOUNT_TYPE_XHS;
        }
        SvMediaMaterial::persistImageRewriteMaterial(
            $userId,
            $platformType,
            SvMediaMaterial::IMAGE_REWRITE_SCENE_AUTO,
            $relativeUri,
            $size
        );
    }

    private static function resolveStorageDefault(): string
    {
        if (array_key_exists('storage', self::$testHooks)) {
            return (string)self::$testHooks['storage'];
        }
        return (string)ConfigService::get('storage', 'default', 'local');
    }

    public static function uploadRewriteImagesToOssForTest(
        array $originalImages,
        array $rewrittenImages,
        int $recordId,
        int $userId = 0,
        int $platformType = 0
    ): array {
        return self::uploadRewriteImagesToOss($originalImages, $rewrittenImages, $recordId, $userId, $platformType);
    }

    /**
     * @param array $originalImages
     * @param array $rewrittenImages
     * @return array<int, string>
     */
    private static function collectLocalRewriteImagePaths(array $originalImages, array $rewrittenImages): array
    {
        $paths = [];
        foreach (array_merge($originalImages, $rewrittenImages) as $image) {
            $relativeUri = self::normalizeLocalRelativePath((string)$image);
            if ($relativeUri === '') {
                continue;
            }
            $paths[$relativeUri] = $relativeUri;
        }

        return array_values($paths);
    }

    private static function normalizeLocalRelativePath(string $image): string
    {
        $image = trim(str_replace('\\', '/', $image));
        if ($image === '' || preg_match('/^https?:\/\//i', $image)) {
            return '';
        }

        return ltrim($image, '/');
    }

    private static function isGpt2RewriteResultPath(string $relativeUri): bool
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        return str_starts_with($relativeUri, 'uploads/rewrite/gpt2images/');
    }

    /**
     * 探测图文改写相对路径在远端存储是否可访问（强制走 OSS 域名，跳过本地优先）。
     */
    private static function remoteRewriteImageExists(string $relativeUri): bool
    {
        $url = FileService::getFileUrl($relativeUri, '', true);
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $statusCode >= 200 && $statusCode < 400;
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
        Log::channel('auto')->write(
            '图文改写原图压缩后更新路径 from=' . $relativeUri . ' to=' . $newRelative,
            'img2'
        );
        return $newRelative;
    }

    private static function resolvePublicLocalPath(string $relativeUri): string
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        return rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeUri);
    }

    /**
     * 原图压缩改名后回写 record.original_images，避免续跑/前端仍指向已删文件。
     */
    private static function replaceOriginalImagePath(int $recordId, string $oldPath, string $newPath): void
    {
        if ($recordId <= 0 || $oldPath === '' || $newPath === '' || $oldPath === $newPath) {
            return;
        }
        $oldKey = ltrim(str_replace('\\', '/', $oldPath), '/');
        try {
            $record = SvDeviceViralRecord::where('id', $recordId)->findOrEmpty();
            if ($record->isEmpty()) {
                return;
            }
            $images = self::normalizeImages($record->original_images);
            $changed = false;
            foreach ($images as $i => $image) {
                $key = ltrim(str_replace('\\', '/', (string)$image), '/');
                if ($key === $oldKey) {
                    $images[$i] = $newPath;
                    $changed = true;
                }
            }
            if (!$changed) {
                return;
            }
            $record->original_images = array_values($images);
            $record->update_time = time();
            $record->save();
            Log::channel('auto')->write(
                "图文改写原图路径已回写 record_id={$recordId} from={$oldPath} to={$newPath}",
                'img2'
            );
        } catch (\Throwable $th) {
            Log::channel('auto')->write(
                "图文改写原图路径回写失败 record_id={$recordId} error=" . $th->getMessage(),
                'img2'
            );
        }
    }

    private static function uploadLocalFileToRemoteStorage(array $storageConfig, string $localPath, string $relativeUri): void
    {
        if (array_key_exists('uploadRemote', self::$testHooks)) {
            $hook = self::$testHooks['uploadRemote'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            if (is_callable($hook)) {
                $hook($localPath, $relativeUri);
            }
            return;
        }

        $filename = basename($relativeUri);
        $saveDir = dirname($relativeUri);
        if ($saveDir === '.' || $saveDir === '\\') {
            $saveDir = '';
        }

        $storageDriver = new StorageDriver($storageConfig);
        $storageDriver->setUploadFileByFileName($localPath, $filename);
        if (!$storageDriver->upload($saveDir)) {
            throw new \Exception($storageDriver->getError() ?: '上传失败');
        }
    }

    /**
     * 仅删除本任务 gpt2 结果图的本地文件；共享原图保留给其他记录复用。
     *
     * @param array<int, string> $localAbsolutePaths
     */
    private static function deleteLocalRewriteImages(array $localAbsolutePaths, int $recordId): void
    {
        if (empty($localAbsolutePaths)) {
            return;
        }

        $deleted = 0;
        $failed = [];
        $skipped = [];
        foreach ($localAbsolutePaths as $localPath) {
            $localPath = (string)$localPath;
            if ($localPath === '') {
                continue;
            }

            // 双保险：绝对路径中若不包含 gpt2images，则跳过，避免误删共享原图
            $normalized = str_replace('\\', '/', $localPath);
            if (!str_contains($normalized, '/uploads/rewrite/gpt2images/')
                && !str_contains($normalized, '/rewrite/gpt2images/')
            ) {
                $skipped[] = $localPath;
                continue;
            }

            if (!is_file($localPath)) {
                continue;
            }
            if (@unlink($localPath)) {
                $deleted++;
                continue;
            }
            $reason = !is_writable(dirname($localPath))
                ? '父目录不可写（可能是 root/www 权限冲突）'
                : 'unlink失败';
            $failed[] = ['path' => $localPath, 'reason' => $reason];
        }

        Log::channel('auto')->write(
            "图文改写本地图片清理 record_id={$recordId} deleted={$deleted} failed="
            . json_encode($failed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . " skipped_non_gpt2=" . json_encode($skipped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'img2'
        );
    }

    private static function deductToken(int $recordId, int $userId, int $imgCount, string $taskId, float $unit): void
    {
        $points = round($imgCount * $unit, 2);
        if ($points <= 0) {
            return;
        }

        $user = User::where('id', $userId)->lock(true)->findOrEmpty();
        if ($user->isEmpty()) {
            throw new \Exception('用户查询失败');
        }
        if (self::hasTokenLog($userId, $taskId)) {
            return;
        }
        // 企业空间成员看企业钱包，勿用个人 tokens 预检
        $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
        if ($spendable < $points) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception(
                $msg . "（需要{$points}，可用{$spendable}，单价{$unit}×{$imgCount}张）",
                4059
            );
        }

        User::userTokensChange($userId, $points);
        $modelDisplay = MediaModelsService::resolveDisplayName(self::BILLING_MODEL_ALIAS, self::BILLING_MODEL_ALIAS);
        $extra = [
            '生成图片数'   => $imgCount,
            '算力单价'     => $unit,
            '实际消耗算力' => $points,
            '模型'         => self::BILLING_MODEL_ALIAS,
            '模型名称'     => $modelDisplay,
            '场景'         => '小红书图文仿写',
            'record_id'    => $recordId,
        ];
        AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE, $points, $taskId, $extra);
    }

    private static function hasTokenLog(int $userId, string $taskId): bool
    {
        return !UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
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

    private static function buildTaskId(int $recordId): string
    {
        return 'viral_img_rewrite_' . $recordId;
    }

    private static function buildAttemptTaskId(int $recordId, int $retryCount, int $startedAt): string
    {
        return self::buildTaskId($recordId) . '_attempt_' . $retryCount . '_' . $startedAt;
    }
}
