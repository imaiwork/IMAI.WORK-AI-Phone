<?php

namespace app\api\logic\videoImitation;

use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\aiPersona\PublishLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\model\aiPersona\AiPersona;
use app\common\service\aiPersona\XhsImageNoteExtractService;
use app\common\service\TeamBillingService;
use app\common\service\ToolsService;
use app\common\service\videoImitation\VideoImitationImageRewriteService;
use think\exception\HttpResponseException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class VideoImitationLogic extends BaseLogic
{
    /** 续跑步骤：全量提取（视频/图文共用） */
    public const RESUME_FROM_EXTRACT = 'extract';
    /** 图文续跑步骤：发布文案 */
    public const RESUME_FROM_PUBLISH_COPYWRITING = 'publish_copywriting';
    /** 图文续跑步骤：图片改写 */
    public const RESUME_FROM_IMAGE_REWRITE = 'image_rewrite';
    /** 视频续跑步骤：文案仿写 */
    public const RESUME_FROM_REWRITE = 'rewrite';
    /** 视频续跑步骤：成片生成 */
    public const RESUME_FROM_GENERATE = 'generate';

    /** 图文提取超过该秒数无进展 → 标 FAIL，供手动重试 */
    public const PARSE_STALE_SECONDS = 1800;

    /**
     * 视频仿写解析与生成（支持按失败步骤续跑；文案已齐时转发 generate）
     * @param string $url 视频分享链接
     * @param int $userId 当前用户ID
     * @param int $personaId 使用的AI人设ID
     * @param int $id   视频仿写任务主键ID
     * @return array|bool
     * @throws \Exception
     */
    public static function createOrUpdateTask(string $url, int $userId, int $personaId, int $id = 0, int $visualMaterialSource = 3)
    {
        if ($id > 0) {
            $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }
            if ((int)$task->media_type !== VideoImitationTask::MEDIA_TYPE_VIDEO) {
                self::setError('该任务不是视频复刻任务，无法重跑');
                return false;
            }
            if (!self::assertVideoRetryAllowed($task, $url, $personaId)) {
                return false;
            }

            // 文案已齐的成片失败：转发 generate（兼容前端重试只打 video2text）
            if (self::shouldForwardVideoGenerate($task, $url, $personaId)) {
                $genResult = TaskLogic::generate(
                    (int)$task->id,
                    $userId,
                    (string)$task->rewritten_text
                );
                if ($genResult === false) {
                    self::setError(TaskLogic::getError() ?: '成片重新提交失败');
                    return false;
                }
                if (!is_array($genResult)) {
                    $genResult = ['task_id' => (int)$task->id];
                }
                $genResult['resume_from'] = self::RESUME_FROM_GENERATE;
                $genResult['forwarded_from'] = 'video2text';
                return $genResult;
            }

            $resumeFrom = self::resolveVideoResumeFrom($task, $url, $personaId);
            if ($resumeFrom === self::RESUME_FROM_REWRITE) {
                self::applyVideoRewriteResume($task, $url, $personaId, $visualMaterialSource);
                $task = VideoImitationTask::where('id', (int)$task->id)->find();
                self::dispatchAsyncParse($task);
                $result = $task ? $task->toArray() : [];
                $result['resume_from'] = self::RESUME_FROM_REWRITE;
                return $result;
            }

            self::applyVideoExtractResume($task, $url, $personaId, $visualMaterialSource);
            $task = VideoImitationTask::where('id', (int)$task->id)->find();
            self::dispatchAsyncParse($task);
            $result = $task ? $task->toArray() : [];
            $result['resume_from'] = self::RESUME_FROM_EXTRACT;
            return $result;
        }

        $task = VideoImitationTask::create([
            'user_id' => $userId,
            'prompt' => $url,
            'persona_id' => $personaId,
            'visual_material_source' => $visualMaterialSource,
            'platform_type' => DeviceEnum::ACCOUNT_TYPE_DY,
            'media_type' => VideoImitationTask::MEDIA_TYPE_VIDEO,
            'status' => VideoImitationTask::STATUS_PARSING,
        ]);
        $task = VideoImitationTask::where('id', (int)$task->id)->find();
        self::dispatchAsyncParse($task);
        $result = $task ? $task->toArray() : [];
        $result['resume_from'] = self::RESUME_FROM_EXTRACT;
        return $result;
    }

    /**
     * 视频手动重试门禁
     */
    private static function assertVideoRetryAllowed(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): bool {
        $status = (int)$task->status;

        if ($status === VideoImitationTask::STATUS_SUCCESS) {
            self::setError('任务已成功，无需重试');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_PARSING) {
            self::setError('任务解析中，请稍后再试');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_GENERATING) {
            self::setError('视频生成中，请稍后再试');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_WAIT_CONFIRM) {
            self::setError('文案待确认中，请确认后生成视频或使用重新生成文案');
            return false;
        }
        if ($status !== VideoImitationTask::STATUS_FAIL) {
            self::setError('当前任务状态不允许重试');
            return false;
        }

        // 文案已齐的 FAIL 由 createOrUpdateTask 转发 generate，此处放行
        return true;
    }

    /**
     * 是否应将 video2text 重试转发为成片 generate
     */
    private static function shouldForwardVideoGenerate(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): bool {
        if ((int)$task->status !== VideoImitationTask::STATUS_FAIL) {
            return false;
        }
        if (trim($url) !== trim((string)$task->prompt)
            || $personaId !== (int)$task->persona_id
        ) {
            return false;
        }
        return trim((string)$task->original_text) !== ''
            && trim((string)$task->rewritten_text) !== '';
    }

    /**
     * 推断视频续跑步骤
     */
    private static function resolveVideoResumeFrom(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): string {
        if (trim($url) !== trim((string)$task->prompt)
            || $personaId !== (int)$task->persona_id
        ) {
            return self::RESUME_FROM_EXTRACT;
        }

        $originalText = trim((string)$task->original_text);
        if ($originalText === '') {
            return self::RESUME_FROM_EXTRACT;
        }

        if (trim((string)$task->rewritten_text) === '') {
            return self::RESUME_FROM_REWRITE;
        }

        return self::RESUME_FROM_EXTRACT;
    }

    /**
     * 视频全量提取续跑
     */
    private static function applyVideoExtractResume(
        VideoImitationTask $task,
        string $url,
        int $personaId,
        int $visualMaterialSource
    ): void {
        $task->save([
            'user_id' => (int)$task->user_id,
            'prompt' => $url,
            'persona_id' => $personaId,
            'visual_material_source' => $visualMaterialSource,
            'platform_type' => DeviceEnum::ACCOUNT_TYPE_DY,
            'media_type' => VideoImitationTask::MEDIA_TYPE_VIDEO,
            'status' => VideoImitationTask::STATUS_PARSING,
            'original_text' => '',
            'rewritten_text' => '',
            'word_count' => 0,
            'analysis_tags' => '',
            'remarks' => '视频续跑：重新提取文案',
            'title' => '',
            'platform_task_id' => '',
            'origin_video_duration' => 0,
        ]);
    }

    /**
     * 视频仿写续跑：保留原文，跳过远程提取与解析扣费
     */
    private static function applyVideoRewriteResume(
        VideoImitationTask $task,
        string $url,
        int $personaId,
        int $visualMaterialSource
    ): void {
        $task->save([
            'prompt' => $url,
            'persona_id' => $personaId,
            'visual_material_source' => $visualMaterialSource,
            'status' => VideoImitationTask::STATUS_PARSING,
            'rewritten_text' => '',
            'word_count' => 0,
            'analysis_tags' => '',
            'remarks' => '视频续跑：重新仿写文案',
            'title' => '',
            'compliance_status' => '',
            'persona_role' => '',
            'persona_tone' => '',
        ]);
    }

    /**
     * 小红书图文任务创建/按失败步骤续跑（异步解析或交由 Cron）
     */
    public static function createOrUpdateImageTextTask(string $url, int $userId, int $personaId, int $id = 0)
    {
        if ($id > 0) {
            $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }
            if ((int)$task->media_type !== VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
                self::setError('该任务不是图文复刻任务，无法重跑');
                return false;
            }
            if (!self::assertImageTextRetryAllowed($task)) {
                return false;
            }

            $resumeFrom = self::resolveImageTextResumeFrom($task, $url, $personaId);
            if ($resumeFrom === self::RESUME_FROM_IMAGE_REWRITE) {
                self::applyImageRewriteResume($task, $url, $personaId);
                $task = VideoImitationTask::where('id', (int)$task->id)->find();
                $result = $task ? $task->toArray() : [];
                $result['resume_from'] = self::RESUME_FROM_IMAGE_REWRITE;
                return $result;
            }

            if ($resumeFrom === self::RESUME_FROM_PUBLISH_COPYWRITING) {
                self::applyPublishCopywritingResume($task, $url, $personaId);
                $task = VideoImitationTask::where('id', (int)$task->id)->find();
                self::dispatchAsyncParse($task);
                $result = $task ? $task->toArray() : [];
                $result['resume_from'] = self::RESUME_FROM_PUBLISH_COPYWRITING;
                return $result;
            }

            self::applyExtractResume($task, $url, $personaId);
            $task = VideoImitationTask::where('id', (int)$task->id)->find();
            self::dispatchAsyncParse($task);
            $result = $task ? $task->toArray() : [];
            $result['resume_from'] = self::RESUME_FROM_EXTRACT;
            return $result;
        }

        $task = VideoImitationTask::create([
            'user_id' => $userId,
            'prompt' => $url,
            'persona_id' => $personaId,
            'platform_type' => DeviceEnum::ACCOUNT_TYPE_XHS,
            'media_type' => VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT,
            'status' => VideoImitationTask::STATUS_PARSING,
            'visual_material_source' => 3,
            'billing_round' => 1,
        ]);
        $task = VideoImitationTask::where('id', (int)$task->id)->find();
        self::dispatchAsyncParse($task);
        $result = $task ? $task->toArray() : [];
        $result['resume_from'] = self::RESUME_FROM_EXTRACT;
        return $result;
    }

    /**
     * 图文手动重试门禁
     */
    private static function assertImageTextRetryAllowed(VideoImitationTask $task): bool
    {
        $status = (int)$task->status;
        $rewriteStatus = (int)$task->image_rewrite_status;

        if ($status === VideoImitationTask::STATUS_SUCCESS
            || $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS
        ) {
            self::setError('任务已成功，无需重试');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_PARSING) {
            if (self::isImageTextParseStale($task)) {
                // 提取超时僵尸任务：放行手动重试
                return true;
            }
            self::setError('任务解析中，请稍后再试');
            return false;
        }
        if ($rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING) {
            self::setError('图片改写处理中，请稍后再试');
            return false;
        }
        if ($rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT
            && $status !== VideoImitationTask::STATUS_FAIL
        ) {
            // 脏态：WAIT 但 charged>0 且无成功图 → 放行手动续跑清零，避免 Cron/门禁死锁
            if (self::isDirtyWaitChargedWithoutRewritten($task)) {
                return true;
            }
            self::setError('图片改写排队中，请稍后再试');
            return false;
        }
        if ($rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING
            && $status !== VideoImitationTask::STATUS_FAIL
        ) {
            self::setError('任务待选图中，请先确认选图');
            return false;
        }
        if ($status !== VideoImitationTask::STATUS_FAIL
            && $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL
        ) {
            self::setError('当前任务状态不允许重试');
            return false;
        }

        return true;
    }

    /**
     * WAIT + charged>0 + 无成功改写图：submit CAS 抢不到，需手动/Cron 清零
     */
    public static function isDirtyWaitChargedWithoutRewritten(VideoImitationTask $task): bool
    {
        if ((int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT) {
            return false;
        }
        if ((int)$task->image_rewrite_charged_count <= 0) {
            return false;
        }
        return !self::hasNonEmptyImageList($task->rewritten_images);
    }

    /** 图文阶段：提取 */
    public const STAGE_EXTRACT = 'extract';
    /** 图文阶段：发布文案 */
    public const STAGE_PUBLISH_COPYWRITING = 'publish_copywriting';
    /** 图文阶段：确认选图 */
    public const STAGE_SELECT_IMAGES = 'select_images';
    /** 图文阶段：图片改写 */
    public const STAGE_IMAGE_REWRITE = 'image_rewrite';
    /** 图文阶段：完成 */
    public const STAGE_DONE = 'done';

    /**
     * 图文当前业务阶段（展示 progress_steps 与续跑共用口径）
     *
     * @return string extract|publish_copywriting|select_images|image_rewrite|done
     */
    public static function resolveImageTextStage(VideoImitationTask $task): string
    {
        $status = (int)$task->status;
        $rewriteStatus = (int)$task->image_rewrite_status;
        $hasImages = self::hasNonEmptyImageList($task->original_images);
        $originalText = trim((string)$task->original_text);
        $rewrittenText = trim((string)$task->rewritten_text);

        if ($status === VideoImitationTask::STATUS_SUCCESS
            || $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS
        ) {
            return self::STAGE_DONE;
        }

        if (!$hasImages || $originalText === '') {
            return self::STAGE_EXTRACT;
        }

        if ($rewrittenText === '') {
            return self::STAGE_PUBLISH_COPYWRITING;
        }

        if ($rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING
            || $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_NONE
        ) {
            return self::STAGE_SELECT_IMAGES;
        }

        return self::STAGE_IMAGE_REWRITE;
    }

    /**
     * 推断图文续跑步骤
     */
    private static function resolveImageTextResumeFrom(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): string {
        if (trim($url) !== trim((string)$task->prompt)
            || $personaId !== (int)$task->persona_id
        ) {
            return self::RESUME_FROM_EXTRACT;
        }

        $status = (int)$task->status;
        $rewriteStatus = (int)$task->image_rewrite_status;
        $stage = self::resolveImageTextStage($task);

        if ($stage === self::STAGE_EXTRACT) {
            return self::RESUME_FROM_EXTRACT;
        }

        // 已提取、文案未出：从发布文案续跑（含超时 FAIL / 僵尸 PARSING）
        if ($stage === self::STAGE_PUBLISH_COPYWRITING
            && (
                $status === VideoImitationTask::STATUS_FAIL
                || $status === VideoImitationTask::STATUS_PARSING
            )
        ) {
            return self::RESUME_FROM_PUBLISH_COPYWRITING;
        }

        // 已有发布文案：改写失败 / 脏 WAIT charged / 卡在选图的 FAIL → 改写续跑
        if ($stage === self::STAGE_SELECT_IMAGES || $stage === self::STAGE_IMAGE_REWRITE) {
            $rewriteFailed = $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL
                || ($status === VideoImitationTask::STATUS_FAIL
                    && $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS)
                || self::isDirtyWaitChargedWithoutRewritten($task);
            if ($rewriteFailed) {
                return self::RESUME_FROM_IMAGE_REWRITE;
            }
        }

        return self::RESUME_FROM_EXTRACT;
    }

    /**
     * 全量提取续跑：清空下游并升 billing_round
     */
    private static function applyExtractResume(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): void {
        $task->save([
            'user_id' => (int)$task->user_id,
            'prompt' => $url,
            'persona_id' => $personaId,
            'platform_type' => DeviceEnum::ACCOUNT_TYPE_XHS,
            'media_type' => VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT,
            'status' => VideoImitationTask::STATUS_PARSING,
            'visual_material_source' => 3,
            'original_text' => '',
            'rewritten_text' => '',
            'word_count' => 0,
            'analysis_tags' => '',
            'remarks' => '图文续跑：重新提取',
            'title' => '',
            'original_images' => [],
            'selected_images' => [],
            'rewritten_images' => [],
            'tikhub_raw' => [],
            'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
            'image_rewrite_task_id' => '',
            'image_rewrite_started_at' => 0,
            'image_rewrite_retry_count' => 0,
            'image_rewrite_results' => [],
            'image_rewrite_success_count' => 0,
            'image_rewrite_fail_count' => 0,
            'image_rewrite_charged_count' => 0,
            'thumbnail' => '',
            'billing_round' => max(1, (int)($task->billing_round ?? 1) + 1),
        ]);
    }

    /**
     * 发布文案续跑：保留提取结果，跳过 TikHub
     */
    private static function applyPublishCopywritingResume(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): void {
        $task->save([
            'prompt' => $url,
            'persona_id' => $personaId,
            'status' => VideoImitationTask::STATUS_PARSING,
            'rewritten_text' => '',
            'word_count' => 0,
            'analysis_tags' => '',
            'remarks' => '图文续跑：重新生成发布文案',
            'selected_images' => [],
            'rewritten_images' => [],
            'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
            'image_rewrite_task_id' => '',
            'image_rewrite_started_at' => 0,
            'image_rewrite_retry_count' => 0,
            'image_rewrite_results' => [],
            'image_rewrite_success_count' => 0,
            'image_rewrite_fail_count' => 0,
            'image_rewrite_charged_count' => 0,
            'thumbnail' => '',
        ]);
    }

    /**
     * 图片改写续跑：保留提取与文案，交由 Cron
     */
    private static function applyImageRewriteResume(
        VideoImitationTask $task,
        string $url,
        int $personaId
    ): void {
        $hasSelected = self::hasNonEmptyImageList($task->selected_images);
        $rewriteStatus = $hasSelected
            ? VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT
            : VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING;
        $remarks = $hasSelected
            ? '图文续跑：已回到待改写队列'
            : '图文续跑：请重新确认要改写的图片';

        $task->save([
            'prompt' => $url,
            'persona_id' => $personaId,
            'status' => VideoImitationTask::STATUS_WAIT_CONFIRM,
            'remarks' => $remarks,
            'rewritten_images' => [],
            'image_rewrite_status' => $rewriteStatus,
            'image_rewrite_task_id' => '',
            'image_rewrite_started_at' => 0,
            'image_rewrite_retry_count' => 0,
            'image_rewrite_results' => [],
            'image_rewrite_success_count' => 0,
            'image_rewrite_fail_count' => 0,
            'image_rewrite_charged_count' => 0,
            'thumbnail' => '',
        ]);
    }

    private static function hasNonEmptyImageList(mixed $images): bool
    {
        if (!is_array($images)) {
            return false;
        }
        foreach ($images as $image) {
            if (trim((string)$image) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * 异步解析签名（内部 fire-and-forget 调用校验）
     * 绑定 update_time：续跑写库后旧请求签名自动失效
     */
    public static function buildParseSign(VideoImitationTask $task): string
    {
        $taskId = (int)$task->id;
        $userId = (int)$task->user_id;
        $createTime = self::resolveTaskUnixTime($task, 'create_time');
        $updateTime = self::resolveTaskUnixTime($task, 'update_time');

        return hash_hmac(
            'sha256',
            $taskId . '|' . $userId . '|' . $createTime . '|' . $updateTime,
            (string)config('project.unique_identification')
        );
    }

    private static function resolveTaskUnixTime(VideoImitationTask $task, string $field): int
    {
        $raw = $task->getData($field);
        if ($raw === null || $raw === '') {
            return 0;
        }
        if (is_numeric($raw)) {
            return (int)$raw;
        }
        $ts = (int)strtotime((string)$raw);
        return $ts > 0 ? $ts : 0;
    }

    public static function verifyParseSign(VideoImitationTask $task, string $parseSign): bool
    {
        if ($parseSign === '') {
            return false;
        }
        $expected = self::buildParseSign($task);
        return hash_equals($expected, $parseSign);
    }

    /**
     * 触发本地异步解析（替代队列）
     */
    private static function dispatchAsyncParse(?VideoImitationTask $task): void
    {
        if (!$task || (int)$task->id <= 0) {
            return;
        }

        $taskId = (int)$task->id;
        $baseUrl = self::resolveAsyncParseBaseUrl();
        $asyncUrl = rtrim($baseUrl, '/') . '/api/videoImitation.task/asyncParse';
        $started = microtime(true);
        $ch = curl_init();
        if ($ch === false) {
            Log::channel('shanjian')->write(
                "VideoImitation asyncParse 触发失败：curl_init 失败 task_id={$taskId} url={$asyncUrl}"
            );
            return;
        }
        curl_setopt($ch, CURLOPT_URL, $asyncUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'task_id' => $taskId,
            'parse_sign' => self::buildParseSign($task),
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $elapsedMs = (int)round((microtime(true) - $started) * 1000);

        // TIMEOUT(28) 在 fire-and-forget 场景属预期
        if ($errno !== 0 && $errno !== 28) {
            Log::channel('shanjian')->write(sprintf(
                'VideoImitation asyncParse 触发异常 task_id=%d url=%s errno=%d error=%s http=%d elapsed_ms=%d',
                $taskId,
                $asyncUrl,
                $errno,
                $error,
                $httpCode,
                $elapsedMs
            ));
            return;
        }

        Log::channel('shanjian')->write(sprintf(
            'VideoImitation asyncParse 已触发 task_id=%d url=%s errno=%d http=%d elapsed_ms=%d body_len=%d',
            $taskId,
            $asyncUrl,
            $errno,
            $httpCode,
            $elapsedMs,
            is_string($body) ? strlen($body) : 0
        ));
    }

    /**
     * 异步解析自调用 base URL：优先配置站点域名，再回落 request()->domain()
     */
    public static function resolveAsyncParseBaseUrl(): string
    {
        $configured = trim((string)config('project.website.domain'));
        if ($configured === '') {
            $configured = trim((string)config('project.website.url'));
        }
        if ($configured !== '') {
            if (!preg_match('/^https?:\/\//i', $configured)) {
                $configured = 'https://' . ltrim($configured, '/');
            }
            return rtrim($configured, '/');
        }

        try {
            $domain = trim((string)request()->domain());
            if ($domain !== '') {
                return rtrim($domain, '/');
            }
        } catch (\Throwable $th) {
            // CLI 无 request
        }

        $appHost = trim((string)env('app.host', ''));
        if ($appHost !== '') {
            if (!preg_match('/^https?:\/\//i', $appHost)) {
                $appHost = 'http://' . ltrim($appHost, '/');
            }
            return rtrim($appHost, '/');
        }

        return '';
    }

    public static function isImageTextParseStale(VideoImitationTask $task, ?int $now = null): bool
    {
        if ((int)$task->status !== VideoImitationTask::STATUS_PARSING) {
            return false;
        }
        if ((int)$task->media_type !== VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            return false;
        }
        $now = $now ?? time();
        $updateRaw = $task->getData('update_time');
        if ($updateRaw === null || $updateRaw === '') {
            $updateRaw = $task->getData('create_time');
        }
        if ($updateRaw === null || $updateRaw === '') {
            return true;
        }
        $updateAt = is_numeric($updateRaw) ? (int)$updateRaw : (int)strtotime((string)$updateRaw);
        if ($updateAt <= 0) {
            return true;
        }
        return $updateAt <= $now - self::PARSE_STALE_SECONDS;
    }

    /**
     * 提取/文案超时：按已有中间结果分支落库，便于用户按步骤续跑（不做自动重投）
     *
     * @return array{action:string}
     */
    public static function recoverStaleImageTextParse(VideoImitationTask $task): array
    {
        $taskId = (int)$task->id;
        if ($taskId <= 0 || !self::isImageTextParseStale($task)) {
            return ['action' => 'skip'];
        }

        $lockKey = 'video_imitation:parse_recover_lock:' . $taskId;
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->set($lockKey, (string)(getmypid() ?: 1), ['nx', 'ex' => 60])) {
                return ['action' => 'locked'];
            }
        } catch (\Throwable $th) {
            Log::channel('shanjian')->write(
                'VideoImitation 解析超时回收加锁失败 task_id=' . $taskId . ' error=' . $th->getMessage()
            );
        }

        try {
            $stage = self::resolveImageTextStage($task);
            $now = time();
            $baseQuery = VideoImitationTask::where('id', $taskId)
                ->where('status', VideoImitationTask::STATUS_PARSING)
                ->where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT);

            if ($stage === self::STAGE_EXTRACT) {
                $affected = $baseQuery->update([
                    'status' => VideoImitationTask::STATUS_FAIL,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
                    'remarks' => '图文提取超时，请重试',
                    'update_time' => $now,
                ]);
                $action = $affected > 0 ? 'fail_extract' : 'skip';
            } elseif ($stage === self::STAGE_PUBLISH_COPYWRITING) {
                $affected = $baseQuery->update([
                    'status' => VideoImitationTask::STATUS_FAIL,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
                    'remarks' => '图文发布文案超时，请重试',
                    'update_time' => $now,
                ]);
                $action = $affected > 0 ? 'fail_publish' : 'skip';
            } else {
                // 已有发布文案仍卡在 PARSING：恢复至待选图，避免误标「提取超时」
                $affected = $baseQuery->update([
                    'status' => VideoImitationTask::STATUS_WAIT_CONFIRM,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING,
                    'remarks' => '图文解析超时，已恢复至待确认选图',
                    'update_time' => $now,
                ]);
                $action = $affected > 0 ? 'recover_selecting' : 'skip';
            }

            Log::channel('shanjian')->write(sprintf(
                'VideoImitation 解析超时回收：task_id=%d stage=%s action=%s affected=%d stale_seconds=%d',
                $taskId,
                $stage,
                $action,
                (int)($affected ?? 0),
                self::PARSE_STALE_SECONDS
            ));
            return ['action' => $action === 'skip' ? 'skip' : (
                str_starts_with($action, 'fail') ? 'fail' : $action
            )];
        } finally {
            try {
                Cache::store('redis')->delete($lockKey);
            } catch (\Throwable $th) {
                // ignore
            }
        }
    }

    /**
     * 处理异步解析任务的核心逻辑（替代原队列handle）
     */
    public static function processParseTask(array $data)
    {
        $taskId = (int)($data['task_id'] ?? 0);
        $parseSign = (string)($data['parse_sign'] ?? '');
        $task = VideoImitationTask::where('id', $taskId)->find();
        if (!$task) {
            return;
        }
        if (!self::verifyParseSign($task, $parseSign)) {
            Log::channel('shanjian')->write('VideoImitation asyncParse 签名校验失败 task_id=' . $taskId);
            return;
        }
        if ((int)$task->status !== VideoImitationTask::STATUS_PARSING) {
            Log::channel('shanjian')->write('VideoImitation asyncParse 非解析中状态跳过 task_id=' . $taskId . ' status=' . $task->status);
            return;
        }

        // 一律以任务表为准，忽略 POST 中的 user_id/persona_id/url/media_type
        $mediaType = (int)($task->media_type ?? VideoImitationTask::MEDIA_TYPE_VIDEO);
        if ($mediaType === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            self::processImageTextParseTask([
                'task_id' => $taskId,
            ], $task);
            return;
        }

        self::processVideoParseTask([
            'task_id' => $taskId,
            'url' => (string)$task->prompt,
            'persona_id' => (int)$task->persona_id,
            'user_id' => (int)$task->user_id,
        ], $task);
    }

    /**
     * 小红书图文异步解析（支持续跑时跳过 TikHub）
     */
    public static function processImageTextParseTask(array $data, ?VideoImitationTask $task = null): void
    {
        $taskId = (int)($data['task_id'] ?? 0);
        $charged = false;
        $billingRound = 1;
        $rewriteUnit = 0.0;
        $url = '';
        $userId = 0;

        try {
            if (!$task) {
                $task = VideoImitationTask::where('id', $taskId)->find();
            }
            if (!$task) {
                return;
            }
            if ((int)$task->status !== VideoImitationTask::STATUS_PARSING) {
                return;
            }

            // 强制使用任务表字段
            $url = (string)$task->prompt;
            $personaId = (int)$task->persona_id;
            $userId = (int)$task->user_id;
            $taskId = (int)$task->id;
            $billingRound = max(1, (int)($task->billing_round ?? 1));

            // 先预检算力
            $rewriteUnit = (float)TokenLogService::checkToken($userId, 'images_explosion_rewrite');

            $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \RuntimeException('IP人设不存在');
            }

            $originalText = trim((string)$task->original_text);
            $fallbackTitle = (string)$task->title;
            $skipExtract = self::hasNonEmptyImageList($task->original_images) && $originalText !== '';

            if (!$skipExtract) {
                $note = XhsImageNoteExtractService::extract($url);
                $noteType = strtolower(trim((string)($note['type'] ?? '')));
                $images = is_array($note['images'] ?? null) ? array_values($note['images']) : [];
                if ($noteType === 'video' && count($images) <= 0) {
                    throw new \RuntimeException((string)($note['error'] ?? '暂不支持小红书视频分享链接'));
                }
                if (empty($images)) {
                    throw new \RuntimeException('小红书笔记解析失败：未获取到可用图片');
                }

                $originalText = self::buildImageTextSourceText($note);
                $fallbackTitle = (string)($note['title'] ?? '');
                $task->original_text = $originalText;
                $task->original_images = $images;
                $task->tikhub_raw = $note['tikhub_raw'] ?? [];
                $task->title = $fallbackTitle;
                $task->remarks = '';
                $task->save();
            } else {
                Log::channel('shanjian')->write(
                    'VideoImitationImageTextParse 跳过TikHub续跑文案 task_id=' . $taskId
                );
            }

            $charged = self::chargeImageExplosionRewriteForImitationTask(
                $userId,
                $taskId,
                $url,
                (float)$rewriteUnit,
                $billingRound
            );

            self::buildImageTextPublishCopywriting(
                $task,
                $persona,
                $userId,
                $originalText,
                $fallbackTitle
            );

            // CAS：仅 PARSING 可落选图态，避免并发旧请求覆盖已成功任务
            $now = time();
            $affected = VideoImitationTask::where('id', $taskId)
                ->where('status', VideoImitationTask::STATUS_PARSING)
                ->update([
                    'status' => VideoImitationTask::STATUS_WAIT_CONFIRM,
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING,
                    'selected_images' => '[]',
                    'image_rewrite_success_count' => 0,
                    'image_rewrite_fail_count' => 0,
                    'image_rewrite_charged_count' => 0,
                    'remarks' => '图文文案已生成，请确认要改写的图片',
                    'update_time' => $now,
                ]);
            if ($affected <= 0) {
                Log::channel('shanjian')->write(
                    'VideoImitationImageTextParse 成功落库 CAS 未命中（可能并发续跑） task_id=' . $taskId
                );
            }
        } catch (\Throwable $th) {
            $errorMsg = $th->getMessage();
            if ($th instanceof HttpResponseException) {
                $respData = $th->getResponse()->getData();
                $errorMsg = is_array($respData)
                    ? (string)($respData['msg'] ?? json_encode($respData, JSON_UNESCAPED_UNICODE))
                    : $errorMsg;
            }
            Log::channel('shanjian')->write('VideoImitationImageTextParse 失败: ' . $errorMsg);

            // 仅本轮真实扣费才退；幂等 skip 不得退掉另一请求的净扣费
            if ($charged && $userId > 0 && $taskId > 0 && $rewriteUnit > 0) {
                try {
                    self::refundImageExplosionRewriteForImitationTask(
                        $userId,
                        $taskId,
                        $url,
                        (float)$rewriteUnit,
                        $billingRound
                    );
                } catch (\Throwable $refundTh) {
                    Log::channel('shanjian')->write(
                        'VideoImitationImageTextParse 退还抓取费失败: ' . $refundTh->getMessage()
                    );
                }
            }

            $isTokenError = (int)$th->getCode() === 4059
                || str_contains($errorMsg, '算力不足');
            $remarks = mb_substr(
                $isTokenError
                    ? ('图文信息抓取扣费失败：' . ($errorMsg !== '' ? $errorMsg : '用户算力不足'))
                    : ($errorMsg !== '' ? $errorMsg : '图文解析失败，请重试'),
                0,
                490,
                'UTF-8'
            );
            // CAS：仅 PARSING 可标失败，避免覆盖已进入选图/成功态
            VideoImitationTask::where('id', $taskId)
                ->where('status', VideoImitationTask::STATUS_PARSING)
                ->update([
                    'status' => VideoImitationTask::STATUS_FAIL,
                    // 解析/文案失败尚未进入改写：保持 NONE，避免 progress/续跑误判为改写失败
                    'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
                    'remarks' => $remarks,
                    'update_time' => time(),
                ]);
        }
    }

    private static function buildExtractBillingTaskId(int $taskId, int $billingRound): string
    {
        return 'video_imitation_info_extract_' . $taskId . '_r' . max(1, $billingRound);
    }

    /**
     * 对齐 ViralRewriterHandler::chargeImageExplosionRewrite：按次扣图文信息抓取费
     *
     * @return bool 本轮是否真实扣费（幂等 skip / unit<=0 返回 false，避免并发误退费）
     */
    private static function chargeImageExplosionRewriteForImitationTask(
        int $userId,
        int $taskId,
        string $shareUrl,
        float $unit,
        int $billingRound = 1
    ): bool {
        if ($unit <= 0) {
            return false;
        }

        $billingTaskId = self::buildExtractBillingTaskId($taskId, $billingRound);
        Db::startTrans();
        try {
            $user = User::where('id', $userId)->lock(true)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \RuntimeException('用户查询失败');
            }

            // 净扣费：DEC 次数大于 INC 退费次数才视为已扣费（退费后同 round 可再扣）
            $decCount = UserTokensLog::where('user_id', $userId)
                ->where('task_id', $billingTaskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::DEC)
                ->count();
            $incCount = UserTokensLog::where('user_id', $userId)
                ->where('task_id', $billingTaskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::INC)
                ->count();
            if ($decCount > $incCount) {
                Db::commit();
                return false;
            }

            $spendable = TeamBillingService::spendableTokens($userId);
            if ($spendable < $unit) {
                $msg = TeamBillingService::resolveSpender($userId) !== null
                    ? '当前团队算力不足，请联系团队主' : '用户算力不足';
                throw new \RuntimeException($msg, 4059);
            }

            User::userTokensChange($userId, $unit);
            AccountLogLogic::recordUserTokensLog(
                true,
                $userId,
                AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
                $unit,
                $billingTaskId,
                [
                    '扣费项目' => '手动-图文爆款仿写信息抓取',
                    '算力单价' => $unit,
                    '实际消耗算力' => $unit,
                    'task_id' => $taskId,
                    'billing_round' => max(1, $billingRound),
                    'share_url' => $shareUrl,
                ]
            );
            Db::commit();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    /**
     * 文案失败等场景退还本轮抓取费（幂等）
     */
    private static function refundImageExplosionRewriteForImitationTask(
        int $userId,
        int $taskId,
        string $shareUrl,
        float $unit,
        int $billingRound = 1
    ): void {
        if ($unit <= 0) {
            return;
        }

        $billingTaskId = self::buildExtractBillingTaskId($taskId, $billingRound);
        Db::startTrans();
        try {
            $user = User::where('id', $userId)->lock(true)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \RuntimeException('用户查询失败');
            }

            $decCount = (int)UserTokensLog::where('user_id', $userId)
                ->where('task_id', $billingTaskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::DEC)
                ->count();
            $incCount = (int)UserTokensLog::where('user_id', $userId)
                ->where('task_id', $billingTaskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::INC)
                ->count();
            // 净扣费为 0 则无需退费（兼容同 round 多次扣退）
            if ($decCount <= $incCount) {
                Db::commit();
                return;
            }

            $decLog = UserTokensLog::where('user_id', $userId)
                ->where('task_id', $billingTaskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::DEC)
                ->order('id', 'desc')
                ->findOrEmpty();
            $refundAmount = abs((float)($decLog->change_amount ?? $unit));
            if ($refundAmount <= 0) {
                Db::commit();
                return;
            }

            AccountLogLogic::recordUserTokensLog(
                false,
                $userId,
                AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
                $refundAmount,
                $billingTaskId,
                [
                    '扣费项目' => '手动-图文爆款仿写信息抓取失败退费',
                    '算力单价' => $unit,
                    '实际恢复算力' => $refundAmount,
                    'task_id' => $taskId,
                    'billing_round' => max(1, $billingRound),
                    'share_url' => $shareUrl,
                ]
            );
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    private static function buildImageTextSourceText(array $note): string
    {
        $parts = [];
        $title = trim((string)($note['title'] ?? ''));
        $body = trim((string)($note['desc'] ?? ''));
        $tags = $note['tags'] ?? [];
        if ($title !== '') {
            $parts[] = '标题：' . $title;
        }
        if ($body !== '') {
            $parts[] = '正文：' . $body;
        }
        if (is_array($tags) && !empty($tags)) {
            $tagStr = [];
            foreach ($tags as $tag) {
                if (is_array($tag)) {
                    $tag = $tag['name'] ?? $tag['title'] ?? '';
                }
                $tag = trim((string)$tag);
                if ($tag !== '') {
                    $tagStr[] = $tag;
                }
            }
            if (!empty($tagStr)) {
                $parts[] = '标签：' . implode(' ', $tagStr);
            }
        }
        return implode("\n", $parts);
    }

    private static function buildImageTextPublishCopywriting(
        VideoImitationTask $task,
        AiPersona $persona,
        int $userId,
        string $originalText,
        string $fallbackTitle = ''
    ): void {
        $rule = null;
        if ((int)$persona->persona_type === 1) {
            $rule = $persona->individual;
        } elseif ((int)$persona->persona_type === 2) {
            $rule = $persona->enterprise;
        } elseif ((int)$persona->persona_type === 3) {
            $rule = $persona->local;
        }
        if (!$rule) {
            throw new \RuntimeException('IP人设规则不存在');
        }

        $keywords = (string)$rule->getClueContent($persona);
        $originalText = trim($originalText);
        if ($originalText !== '') {
            $keywords .= "\n\n原笔记正文：\n" . $originalText;
        }

        $taskId = generate_unique_task_id();
        $response = PublishLogic::resolveContentPublishCopywriting(
            $persona,
            $keywords,
            $taskId,
            $userId,
            DeviceEnum::ACCOUNT_TYPE_XHS,
            true
        );
        Log::channel('shanjian')->write(
            'VideoImitationImageTextParse 发布文案: ' . json_encode($response, JSON_UNESCAPED_UNICODE)
        );
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new \RuntimeException((string)($response['msg'] ?? $response['message'] ?? '内容发布文案生成失败'));
        }
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        if (!empty($data['library_empty'])) {
            throw new \RuntimeException((string)($data['library_message'] ?? '发布文案库暂无可用文案'));
        }

        $content = trim((string)($data['content'] ?? $originalText));
        $title = trim((string)($data['title'] ?? $fallbackTitle));
        $tag = trim((string)($data['tag'] ?? ''));

        $task->title = $title !== '' ? $title : mb_substr($content, 0, 20, 'UTF-8');
        $task->rewritten_text = $content;
        $task->publish_title = $task->title;
        $task->publish_text = $content;
        $task->publish_topic = $tag;
        $task->word_count = mb_strlen($content, 'UTF-8');
        $task->save();
    }

    /**
     * 抖音/视频异步解析（支持跳过提取、仅续跑仿写）
     */
    private static function processVideoParseTask(array $data, VideoImitationTask $task): void
    {
        $unit = TokenLogService::getTypeScore('video_imitation_copywriting_parse');
        $taskId = (int)($data['task_id'] ?? $task->id);
        $extractedCheckpoint = false;
        try {
            $url = (string)($data['url'] ?? $task->prompt);
            $personaId = (int)($data['persona_id'] ?? $task->persona_id);
            $userId = (int)($data['user_id'] ?? $task->user_id);

            $audioText = trim((string)$task->original_text);
            $skipExtract = $audioText !== '';

            if ($skipExtract) {
                Log::channel('shanjian')->write(
                    'VideoImitationParseTask 跳过提取续跑仿写 task_id=' . $taskId
                );
            } else {
                $response = ToolsService::VideoImitation()->video2text($url);
                Log::channel('shanjian')->write(
                    'VideoImitationParseTask 视频解析: ' . json_encode($response, JSON_UNESCAPED_UNICODE)
                );
                if (!isset($response['code']) || (int)$response['code'] !== 10000) {
                    $task->refresh();
                    if ((int)$task->status === VideoImitationTask::STATUS_PARSING) {
                        $task->status = VideoImitationTask::STATUS_FAIL;
                        $innerCode = $response['data']['code'] ?? null;
                        $task->remarks = ($innerCode !== null && (int)$innerCode !== 200)
                            ? '无法提取该视频文案'
                            : (string)($response['message'] ?? '文案解析失败或第三方格式解析错误');
                        $task->save();
                    }
                    return;
                }

                $resData = is_array($response['data'] ?? null) ? $response['data'] : [];
                $task->origin_video_duration = $resData['duration'] ?? 0;
                $audioText = trim((string)($resData['audio_text'] ?? ''));
                $task->platform_task_id = (string)($resData['request_id'] ?? '');

                if ($audioText === '') {
                    $requestParams = [
                        'input' => ['prompt' => $url],
                        'version' => 'v2',
                    ];
                    $res = ToolsService::Copywriting()->videoImitation($requestParams);
                    Log::channel('shanjian')->write(
                        'VideoImitationParseTask MCP视频解析: ' . json_encode($res, JSON_UNESCAPED_UNICODE)
                    );
                    if (isset($res['code']) && (int)$res['code'] === 10000) {
                        $mcpData = is_array($res['data'] ?? null) ? $res['data'] : [];
                        $messageJson = !empty($mcpData['message'])
                            ? str_replace(["\r", '\\n', "\n", '\\'], '', $mcpData['message'])
                            : '';
                        $parsedMsg = json_decode($messageJson, true);
                        if (is_array($parsedMsg)) {
                            $audioText = trim((string)($parsedMsg['original_text'] ?? ''));
                        }
                    }
                }

                $task->original_text = $audioText;

                if ((int)($resData['code'] ?? 0) === 200 && $audioText === '') {
                    $task->status = VideoImitationTask::STATUS_FAIL;
                    $task->remarks = '未识别到视频中含有文案';
                    $task->save();

                    if ($unit > 0) {
                        self::chargeVideoCopywritingParseFee(
                            $userId,
                            $taskId,
                            (float)$unit,
                            1,
                            [
                                '扣费项目' => '视频文案提取(成本费)',
                                '算力单价' => $unit,
                                '原视频时长' => $task->origin_video_duration . '秒',
                                '实际消耗算力' => $unit,
                                '原因' => '未识别到视频中含有文案',
                            ]
                        );
                    }
                    return;
                }

                // 检查点：原文先落库，仿写失败后可断点续跑
                $task->remarks = '';
                $task->save();
                $extractedCheckpoint = true;

                if ((int)($resData['code'] ?? 0) === 200 && $unit > 0) {
                    $minutes = max(1, (int)ceil(((float)$task->origin_video_duration) / 60));
                    $point = $unit * $minutes;
                    self::chargeVideoCopywritingParseFee(
                        $userId,
                        $taskId,
                        (float)$point,
                        $minutes,
                        [
                            '扣费项目' => '视频文案提取',
                            '算力单价' => $unit,
                            '原视频时长' => $task->origin_video_duration . '秒',
                            '实际消耗算力' => $point,
                        ]
                    );
                }
            }

            if ($audioText === '') {
                $task->status = VideoImitationTask::STATUS_FAIL;
                $task->remarks = '视频文案为空，无法仿写';
                $task->save();
                return;
            }

            self::buildRewriteCopywriting($task, $userId, $audioText, $personaId);
            $task->status = VideoImitationTask::STATUS_WAIT_CONFIRM;
            $task->remarks = '';
            $task->save();
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if ($e instanceof HttpResponseException) {
                $respData = $e->getResponse()->getData();
                $errorMsg = json_encode($respData, JSON_UNESCAPED_UNICODE);
            }

            Log::channel('shanjian')->write('VideoImitationParseTask 执行抛出异常: ' . $errorMsg);

            $task = VideoImitationTask::where('id', $taskId)->find();
            if ($task && (int)$task->status === VideoImitationTask::STATUS_PARSING) {
                $task->status = VideoImitationTask::STATUS_FAIL;
                $hasOriginal = trim((string)$task->original_text) !== '';
                $task->remarks = ($extractedCheckpoint || $hasOriginal)
                    ? '视频文案仿写失败，请重试'
                    : '视频文案提取失败，请重试';
                $task->save();
            }
        }
    }

    /**
     * 视频文案提取扣费（按任务 id 幂等，避免重试双扣）
     */
    private static function chargeVideoCopywritingParseFee(
        int $userId,
        int $taskId,
        float $point,
        int $minutes,
        array $extra
    ): void {
        if ($point <= 0 || $userId <= 0 || $taskId <= 0) {
            return;
        }

        $billingTaskId = (string)$taskId;
        $exists = UserTokensLog::where('user_id', $userId)
            ->where('task_id', $billingTaskId)
            ->where('change_type', AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE)
            ->where('action', AccountLogEnum::DEC)
            ->findOrEmpty();
        if (!$exists->isEmpty()) {
            Log::channel('shanjian')->write(
                'VideoImitationParseTask 解析扣费已存在，跳过 task_id=' . $taskId
            );
            return;
        }

        User::userTokensChange($userId, $point);
        $extra['分钟数'] = $minutes;
        AccountLogLogic::recordUserTokensLog(
            true,
            $userId,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
            $point,
            $billingTaskId,
            $extra
        );
    }

    /**
     * @desc 语音转文字
     * @param string $audioUrl 语音文件链接
     * @return array|bool
     * @date 2026/4/10 16:22
     * @author MonitorAllen
     */
    public static function speech2Text($audioUrl)
    {
        $response = ToolsService::VideoImitation()->speech2Text($audioUrl);
        if (isset($response['code']) && $response['code'] == 10000) {
            return ['text' => $response['data']['message']];
        }
        return false;
    }

    /**
     * 封装重新仿写的核心处理逻辑
     * @param VideoImitationTask $task 仿写任务
     * @param int $userId 用户ID
     * @param string $originalText 原始文案
     * @param int $personaId AI人设ID
     * @return VideoImitationTask
     */
    public static function buildRewriteCopywriting(VideoImitationTask $task, int $userId, string $originalText, int $personaId): VideoImitationTask
    {
        $promptContent = "视频文案：\n{$originalText}";

        if ($personaId) {
            // 获取人设信息
            $persona = AiPersona::where('id', $personaId)->find();
            if ($persona) {
                $type = $persona['persona_type'];
                $personaStr = '';
                $productContent = "我的IP人设产品内容：\n主营业务/产品：{$persona['main_business']}\n目标客户与痛点：{$persona['target_pain_points']}\n差异化优势与行为引导：{$persona['conversion_hook']}";

                if ($type == 1) {
                    $ext = \app\common\model\aiPersona\AiPersonaIndividual::where('persona_id', $personaId)->find();
                    if ($ext) {
                        $personaStr = $ext->getClueContent($persona);
                    }
                } elseif ($type == 2) {
                    $ext = \app\common\model\aiPersona\AiPersonaEnterprise::where('persona_id', $personaId)->find();
                    if ($ext) {
                        $personaStr = $ext->getClueContent($persona);
                    }
                } elseif ($type == 3) {
                    $ext = \app\common\model\aiPersona\AiPersonaLocal::where('persona_id', $personaId)->find();
                    if ($ext) {
                        $personaStr = $ext->getClueContent($persona);
                    }
                }
                $promptContent = "我的IP人设内容是：\n" . $personaStr . "\n\n" . $productContent . "\n\n视频文案：\n{$originalText}";
            }
        }

        $titlecoze['keywords'] = $promptContent;
        Log::channel('shanjian')->write("VideoImitationParseTask 请求参数: " . json_encode($titlecoze, JSON_UNESCAPED_UNICODE));
        $imitationResult = AutoDeviceSettingLogic::copywriting($titlecoze, $userId, 5);
        Log::channel('shanjian')->write("VideoImitationParseTask 文案仿写: " . json_encode($imitationResult, JSON_UNESCAPED_UNICODE));
        $contentData = $imitationResult['content'] ?? [];
        if (is_string($contentData)) {
            // 如果是字符串，手动解析为数组
            $contentData = json_decode($contentData, true) ?: [];
        }

        $taskTitle = $contentData['title'] ?? '';
        $task->title = $taskTitle ?: mb_substr($originalText, 0, 10, 'utf-8');

        $task->rewritten_text = $contentData['rewritten_text'] ?? '';
        $task->word_count = $contentData['word_count'] ?? 0;
        $task->analysis_tags = json_encode($contentData['analysis_tags'] ?? [], JSON_UNESCAPED_UNICODE);
        $task->compliance_status = $contentData['compliance_status'] ?? '';
        $task->persona_role = $contentData['persona_role'] ?? '';
        $task->persona_tone = $contentData['persona_tone'] ?? '';

        return $task;
    }

    /**
     * 重新发起文案仿写（供独立接口调用）
     */
    public static function reGenerateCopywriting(int $taskId, int $userId)
    {
        $task = VideoImitationTask::where('id', $taskId)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到对应的任务数据");
            return false;
        }

        if (empty($task->original_text)) {
            self::setError("原视频已解析文案异常或为空，无法重新生成");
            return false;
        }

        try {
            if ((int)$task->media_type === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
                $persona = AiPersona::where('id', (int)$task->persona_id)->where('user_id', $userId)->findOrEmpty();
                if ($persona->isEmpty()) {
                    self::setError('IP人设不存在');
                    return false;
                }
                self::buildImageTextPublishCopywriting(
                    $task,
                    $persona,
                    $userId,
                    (string)$task->original_text,
                    (string)$task->title
                );
                $task->status = VideoImitationTask::STATUS_WAIT_CONFIRM;
                $rewriteStatus = (int)$task->image_rewrite_status;
                if (in_array($rewriteStatus, [
                    VideoImitationTask::IMAGE_REWRITE_STATUS_NONE,
                    VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL,
                ], true)) {
                    $task->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING;
                    $task->remarks = '图文文案已生成，请确认要改写的图片';
                }
            } else {
                self::buildRewriteCopywriting($task, $userId, $task->original_text, $task->persona_id);
                $task->status = VideoImitationTask::STATUS_WAIT_CONFIRM;
            }
            $task->save();
            return $task->toArray();
        } catch (\Exception $e) {
            self::setError("重新生成仿写文案异常：" . $e->getMessage());
            return false;
        }
    }
}
