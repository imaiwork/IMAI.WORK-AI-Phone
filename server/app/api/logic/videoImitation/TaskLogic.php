<?php

namespace app\api\logic\videoImitation;

use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\minimax\VoiceLogic as MinimaxVoiceLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\sv\ToolsLogic;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\Jobs\VideoImitationGenerateJob;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\Material;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\MaterialReadinessService;
use app\common\service\draw\MediaModelsService;
use app\common\service\ShanjianQueueService;
use app\common\service\ToolsService;
use app\common\service\VideoInfoService;
use app\common\service\videoImitation\VideoImitationImageRewriteService;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class TaskLogic extends BaseLogic
{
    /**
     * 获取任务详情
     * @param int $id
     * @param int $userId
     * @return array|bool
     */
    public static function detail(int $id, int $userId)
    {
        $task = VideoImitationTask::withTrashed()->where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        $data = $task->toArray();
        $data['queue_status_text'] = ShanjianQueueService::statusText(
            (string)($data['queue_status'] ?? ''),
            (int)($data['queue_position'] ?? 0)
        );

        $data['avatar_name'] = '';
        if ($data['avatar_id']) {
            $avatar = AiPersonaDigitalAvatar::where('id', $data['avatar_id'])->find();
            if ($avatar) {
                $data['avatar_name'] = $avatar['avatar_name'];
            }
        }

        $data['voice_name'] = '';
        if ($data['is_material'] == 0) {
            $avatar = AiPersonaDigitalAvatar::where('id', $task->voice_id)->find();
            if ($avatar) {
                $data['voice_name'] = $avatar['voice_name'];
            }
        } else {
            if ($data['voice_id'] != 0) {
                $voice = AiPersonaDigitalVoice::where('voice_id', $data['voice_id'])->find();
                if ($voice) {
                    $data['voice_name'] = $voice['voice_name'];
                }
            }
        }


        $data['video_url'] = !empty($data['video_url']) ? FileService::getFileUrl($data['video_url']) : '';
        $data['thumbnail'] = !empty($data['thumbnail']) ? FileService::getFileUrl($data['thumbnail']) : '';
        if (!empty($data['analysis_tags']) && is_string($data['analysis_tags'])) {
            $data['analysis_tags'] = json_decode($data['analysis_tags'], true) ?: [];
        }

        $originalImages = is_array($task->original_images) ? $task->original_images : [];
        $selectedImages = is_array($task->selected_images) ? $task->selected_images : [];
        $rewrittenImages = is_array($task->rewritten_images) ? $task->rewritten_images : [];
        $data['original_images'] = array_map(static fn($u) => FileService::getFileUrl((string)$u), $originalImages);
        $data['selected_images'] = array_map(static fn($u) => FileService::getFileUrl((string)$u), $selectedImages);
        $data['rewritten_images'] = array_map(static fn($u) => FileService::getFileUrl((string)$u), $rewrittenImages);
        $data['image_count'] = count($rewrittenImages) > 0 ? count($rewrittenImages) : count($originalImages);
        $data['platform_type'] = (int)($data['platform_type'] ?? 4);
        $data['media_type'] = (int)($data['media_type'] ?? 1);
        $data['image_rewrite_status'] = (int)($data['image_rewrite_status'] ?? 0);
        $data['progress_steps'] = self::buildProgressSteps($task);

        if ((int)$data['media_type'] === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
            && (int)$data['image_rewrite_status'] === VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING
        ) {
            $estimateCount = min(
                count($originalImages),
                VideoImitationImageRewriteService::MAX_REWRITE_IMAGE_COUNT
            );
            $estimate = self::estimateImageRewriteTokens($estimateCount);
            $data['rewrite_unit_price'] = $estimate['unit_price'];
            $data['estimated_tokens'] = $estimate['estimated_tokens'];
        }

        return $data;
    }

    /**
     * 创作队列进度步（按 media_type 区分视频/图文）
     */
    public static function buildProgressSteps(VideoImitationTask $task): array
    {
        $status = (int)$task->status;
        $failed = $status === VideoImitationTask::STATUS_FAIL;
        $remarks = (string)($task->remarks ?? '');

        if ((int)$task->media_type === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            $rewriteStatus = (int)$task->image_rewrite_status;
            $stage = VideoImitationLogic::resolveImageTextStage($task);
            $hasRewrittenText = trim((string)$task->rewritten_text) !== '';
            $extractDone = $stage !== VideoImitationLogic::STAGE_EXTRACT;
            $publishDone = $hasRewrittenText;
            $selectDone = $hasRewrittenText
                && $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING
                && $rewriteStatus !== VideoImitationTask::IMAGE_REWRITE_STATUS_NONE
                && $status > VideoImitationTask::STATUS_PARSING;
            $rewriteDone = $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS
                || $status === VideoImitationTask::STATUS_SUCCESS;

            // 失败步与 resume_from / resolveImageTextStage 一一对应
            $extractFailed = $failed && $stage === VideoImitationLogic::STAGE_EXTRACT;
            $publishFailed = $failed && $stage === VideoImitationLogic::STAGE_PUBLISH_COPYWRITING;
            $selectFailed = $failed && $stage === VideoImitationLogic::STAGE_SELECT_IMAGES;
            $imageRewriteFailed = $rewriteStatus === VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL
                || ($failed && $stage === VideoImitationLogic::STAGE_IMAGE_REWRITE);

            return [
                self::stepItem('persona', '关联人设', true, false),
                self::stepItem('extract', '提取文案', $extractDone, $extractFailed, $remarks),
                self::stepItem(
                    'publish_copywriting',
                    '发布文案',
                    $publishDone,
                    $publishFailed,
                    $remarks
                ),
                self::stepItem(
                    'select_images',
                    '确认选图',
                    $selectDone,
                    $selectFailed,
                    $remarks
                ),
                self::stepItem(
                    'image_rewrite',
                    '图片改写',
                    $rewriteDone,
                    $imageRewriteFailed,
                    $remarks
                ),
                self::stepItem('done', '生成完成', $status === VideoImitationTask::STATUS_SUCCESS, false),
            ];
        }

        $hasOriginalText = trim((string)$task->original_text) !== '';
        $hasRewrittenText = trim((string)$task->rewritten_text) !== '';
        $hasRenderTrace = trim((string)($task->shanjian_task_id ?? '')) !== ''
            || trim((string)($task->queue_status ?? '')) !== '';
        $copyReady = $hasOriginalText && $hasRewrittenText;
        $extractDone = $hasOriginalText;
        $extractFailed = $failed && !$hasOriginalText;
        // 文案已齐的失败（含素材阶段失败）或已有成片痕迹 → 标云端渲染失败
        $renderFailed = $failed && ($copyReady || $hasRenderTrace);

        return [
            self::stepItem('persona', '关联人设', true, false),
            self::stepItem('extract', '提取文案', $extractDone, $extractFailed, $remarks),
            self::stepItem(
                'avatar',
                '匹配形象',
                $status >= VideoImitationTask::STATUS_GENERATING || ($failed && $copyReady),
                false
            ),
            self::stepItem(
                'render',
                '云端渲染',
                $status === VideoImitationTask::STATUS_SUCCESS,
                $renderFailed,
                $remarks
            ),
        ];
    }

    /**
     * 视频成片下发门禁（支持 FAIL 续跑）
     */
    private static function assertVideoGenerateAllowed(VideoImitationTask $task): bool
    {
        $status = (int)$task->status;
        $rewrittenText = trim((string)$task->rewritten_text);

        if ($status === VideoImitationTask::STATUS_SUCCESS) {
            self::setError('任务已成功，无需再次生成');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_GENERATING) {
            self::setError('视频生成中，请稍后再试');
            return false;
        }
        if ($status === VideoImitationTask::STATUS_FAIL) {
            if ($rewrittenText === '') {
                self::setError('文案未就绪，请先完成文案解析或仿写');
                return false;
            }
            return true;
        }
        if ($status === VideoImitationTask::STATUS_WAIT_CONFIRM) {
            return true;
        }
        if ($status === VideoImitationTask::STATUS_PARSING) {
            self::setError('任务解析中，请稍后再试');
            return false;
        }

        self::setError('当前任务状态不允许生成视频');
        return false;
    }

    /**
     * 成片失败续跑：清空队列/成片痕迹，复位退费标记
     */
    private static function prepareVideoGenerateResume(VideoImitationTask $task): void
    {
        $task->save([
            'shanjian_task_id' => '',
            'queue_status' => '',
            'queue_position' => 0,
            'queue_updated_time' => 0,
            'queue_refund_status' => 0,
            'video_url' => '',
            'thumbnail' => '',
            'remarks' => '成片续跑：重新生成视频',
        ]);
    }

    private static function stepItem(string $key, string $name, bool $done, bool $failed, string $remarks = ''): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'done' => $done,
            'failed' => $failed,
            'remarks' => $failed ? $remarks : '',
        ];
    }

    /**
     * 确认发布文案
     * @param int $id
     * @param mixed $userId
     * @param string $publishText
     * @return bool
     */
    public static function confirmPublishText(int $id, $userId, string $publishText, string $publishTopic): bool
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        $task->publish_text = $publishText;
        $task->publish_topic = $publishTopic;
        $task->publish_confirm = 1;
        $task->save();

        return true;
    }

    /**
     * 确认选图并进入图片改写排队
     *
     * @param list<int|string> $imageIndexes
     * @return array|false
     */
    public static function confirmImageRewrite(
        int $id,
        int $userId,
        array $imageIndexes,
        string $title = '',
        string $rewrittenText = ''
    ) {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('未找到该视频复刻记录');
            return false;
        }
        if ((int)$task->media_type !== VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            self::setError('仅图文复刻任务支持确认选图');
            return false;
        }
        if ((int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING) {
            self::setError('当前任务不在待选图状态，无法确认');
            return false;
        }

        $originalImages = is_array($task->original_images) ? array_values($task->original_images) : [];
        if (empty($originalImages)) {
            self::setError('原图列表为空，无法确认选图');
            return false;
        }

        $indexes = [];
        foreach ($imageIndexes as $index) {
            // 仅接受整型或纯数字字符串，拒绝 1.5 / 1.0 / true 等
            if (is_int($index)) {
                $indexes[] = $index;
            } elseif (is_string($index) && preg_match('/^-?\d+$/', $index) === 1) {
                $indexes[] = (int)$index;
            } else {
                self::setError('图片下标必须为整数');
                return false;
            }
        }
        $indexes = array_values(array_unique($indexes));
        if (empty($indexes)) {
            self::setError('请至少选择 1 张图片');
            return false;
        }
        if (count($indexes) > VideoImitationImageRewriteService::MAX_REWRITE_IMAGE_COUNT) {
            self::setError('最多选择 ' . VideoImitationImageRewriteService::MAX_REWRITE_IMAGE_COUNT . ' 张图片');
            return false;
        }

        $selectedImages = [];
        foreach ($indexes as $index) {
            if ($index < 0 || $index >= count($originalImages)) {
                self::setError('图片下标超出范围');
                return false;
            }
            $url = trim((string)$originalImages[$index]);
            if ($url === '') {
                self::setError('所选图片无效');
                return false;
            }
            $selectedImages[] = FileService::setFileUrl($url);
        }

        // 落库前按选中张数预检个人/团队可用算力，不足则零副作用返回
        try {
            VideoImitationImageRewriteService::assertEnoughRewriteTokens($userId, count($selectedImages));
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        // 可选文案：trim 后为空不更新；非空且与库内不一致才更新，并同步发布字段
        $title = trim($title);
        if ($title !== '' && $title !== trim((string)$task->title)) {
            $task->title = $title;
            $task->publish_title = $title;
        }
        $rewrittenText = trim($rewrittenText);
        if ($rewrittenText !== '' && $rewrittenText !== trim((string)$task->rewritten_text)) {
            $task->rewritten_text = $rewrittenText;
            $task->publish_text = $rewrittenText;
        }

        $task->selected_images = $selectedImages;
        $task->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT;
        $task->image_rewrite_task_id = '';
        $task->image_rewrite_started_at = 0;
        $task->image_rewrite_results = [];
        $task->image_rewrite_success_count = 0;
        $task->image_rewrite_fail_count = 0;
        $task->image_rewrite_charged_count = 0;
        $task->rewritten_images = [];
        $task->remarks = '已确认选图，等待图片改写';
        $task->save();

        $selectedCount = count($selectedImages);
        $estimate = self::estimateImageRewriteTokens($selectedCount);

        return [
            'id' => (int)$task->id,
            'selected_count' => $selectedCount,
            'unit_price' => $estimate['unit_price'],
            'estimated_tokens' => $estimate['estimated_tokens'],
            'image_rewrite_status' => VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT,
            'selected_images' => array_map(
                static fn($u) => FileService::getFileUrl((string)$u),
                $selectedImages
            ),
            'title' => (string)$task->title,
            'rewritten_text' => (string)$task->rewritten_text,
            'publish_title' => (string)$task->publish_title,
            'publish_text' => (string)$task->publish_text,
            'remarks' => (string)$task->remarks,
        ];
    }

    /**
     * 小红书图文：超时未确认选图则自动确认并进入改写排队
     *
     * @return array{
     *   confirmed: int,
     *   failed: int,
     *   skipped: int,
     *   confirmed_ids: list<int>,
     *   failed_ids: list<int>,
     *   skipped_ids: list<int>
     * }
     */
    public static function autoConfirmExpiredImageSelections(int $limit = 20, ?callable $onHeartbeat = null): array
    {
        $result = [
            'confirmed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'confirmed_ids' => [],
            'failed_ids' => [],
            'skipped_ids' => [],
        ];

        $limit = max(1, min(100, $limit));
        $deadlineTime = time() - 1800;

        try {
            $pendingTasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT)
                ->where('platform_type', DeviceEnum::ACCOUNT_TYPE_XHS)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING)
                ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
                ->where('task_delete', 0)
                ->where('update_time', '<', $deadlineTime)
                ->order('id', 'asc')
                ->limit($limit)
                ->select();
        } catch (\Throwable $e) {
            Log::channel('manual_2img')->write(
                '[auto_confirm_select] 扫描超时待选图失败：' . $e->getMessage()
            );
            return $result;
        }

        foreach ($pendingTasks as $task) {
            if (is_callable($onHeartbeat)) {
                try {
                    $onHeartbeat();
                } catch (\Throwable $heartbeatTh) {
                    // 续期失败不影响本条处理
                }
            }

            $taskId = (int)$task->id;
            $userId = (int)$task->user_id;

            try {
                $originalImages = is_array($task->original_images)
                    ? array_values($task->original_images)
                    : [];
                $indexes = [];
                foreach ($originalImages as $index => $url) {
                    if (trim((string)$url) === '') {
                        continue;
                    }
                    $indexes[] = (int)$index;
                    if (count($indexes) >= VideoImitationImageRewriteService::MAX_REWRITE_IMAGE_COUNT) {
                        break;
                    }
                }

                if (empty($indexes)) {
                    $task->status = VideoImitationTask::STATUS_FAIL;
                    $task->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL;
                    $task->remarks = '超时自动确认失败：原图列表为空';
                    $task->save();
                    $result['failed']++;
                    $result['failed_ids'][] = $taskId;
                    Log::channel('manual_2img')->write(sprintf(
                        '[auto_confirm_select] 失败 task_id=%d user_id=%d reason=原图列表为空',
                        $taskId,
                        $userId
                    ));
                    continue;
                }

                $confirmResult = self::confirmImageRewrite($taskId, $userId, $indexes);
                if ($confirmResult === false) {
                    $error = (string)self::getError();
                    // 用户已抢先确认等：视为跳过，不记业务失败
                    if (str_contains($error, '不在待选图状态') || str_contains($error, '未找到该视频复刻记录')) {
                        $result['skipped']++;
                        $result['skipped_ids'][] = $taskId;
                        Log::channel('manual_2img')->write(sprintf(
                            '[auto_confirm_select] 跳过 task_id=%d user_id=%d reason=%s',
                            $taskId,
                            $userId,
                            $error
                        ));
                    } elseif (str_contains($error, '算力不足')) {
                        $latest = VideoImitationTask::where('id', $taskId)->find();
                        if ($latest
                            && (int)$latest->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING
                        ) {
                            $latest->status = VideoImitationTask::STATUS_FAIL;
                            $latest->image_rewrite_status = VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL;
                            $latest->remarks = '超时自动确认失败：' . $error;
                            $latest->save();
                        }
                        $result['failed']++;
                        $result['failed_ids'][] = $taskId;
                        Log::channel('manual_2img')->write(sprintf(
                            '[auto_confirm_select] 失败 task_id=%d user_id=%d reason=%s',
                            $taskId,
                            $userId,
                            $error
                        ));
                    } else {
                        $result['failed']++;
                        $result['failed_ids'][] = $taskId;
                        Log::channel('manual_2img')->write(sprintf(
                            '[auto_confirm_select] 失败 task_id=%d user_id=%d reason=%s',
                            $taskId,
                            $userId,
                            $error !== '' ? $error : '确认选图返回失败'
                        ));
                    }
                    continue;
                }

                $latest = VideoImitationTask::where('id', $taskId)->find();
                if ($latest) {
                    $latest->remarks = '超时未确认选图，系统已自动确认，等待图片改写';
                    $latest->save();
                }

                $result['confirmed']++;
                $result['confirmed_ids'][] = $taskId;
                Log::channel('manual_2img')->write(sprintf(
                    '[auto_confirm_select] 成功 task_id=%d user_id=%d selected=%d',
                    $taskId,
                    $userId,
                    count($indexes)
                ));
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['failed_ids'][] = $taskId;
                Log::channel('manual_2img')->write(sprintf(
                    '[auto_confirm_select] 异常 task_id=%d user_id=%d error=%s',
                    $taskId,
                    $userId,
                    $e->getMessage()
                ));
            }
        }

        return $result;
    }

    /**
     * 按 gpt-image-2 单价预估图改写算力（不预扣）
     *
     * @return array{unit_price: float, estimated_tokens: float}
     */
    private static function estimateImageRewriteTokens(int $imageCount): array
    {
        $unitPrice = 0.0;
        try {
            $costRow = MediaModelsService::findCostByAlias('gpt-image-2', true);
            $unitPrice = (float)MediaModelsService::resolveUnitPrice($costRow);
        } catch (\Throwable $e) {
            $unitPrice = 0.0;
        }

        $count = min(
            max(0, $imageCount),
            VideoImitationImageRewriteService::MAX_REWRITE_IMAGE_COUNT
        );
        return [
            'unit_price' => $unitPrice,
            'estimated_tokens' => round($unitPrice * $count, 2),
        ];
    }

    /**
     * 删除任务：标记 task_delete=1，并 SoftDelete 写入 delete_time
     * （创作记录按 delete_time 过滤，需与仿写列表同步隐藏）
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function delete(int $id, int $userId)
    {
        $task = VideoImitationTask::withTrashed()->where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('未找到该视频复刻记录');
            return false;
        }
        $result = VideoImitationTask::withTrashed()->where('id', $task->id)->update([
            'task_delete' => 1,
            'status' => VideoImitationTask::STATUS_FAIL,
            'remarks' => '用户手动删除',
        ]);
        return $result;
    }

    /**
     * 异步入口:做必要的前置校验后,将生成任务推入队列
     * 队列消费时会调用 generate(),由素材池构建逻辑跳过未就绪/失败素材
     */
    public static function generateAsync(int $id, int $userId, string $rewrittenText)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        if ((int)$task->media_type === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            return self::generateImageText($task, $userId, $rewrittenText);
        }

        if (!self::assertVideoGenerateAllowed($task)) {
            return false;
        }

        Queue::push(
            VideoImitationGenerateJob::class,
            [
                'task_id' => $id,
                'user_id' => $userId,
                'rewritten_text' => $rewrittenText,
            ],
            env('QUEUE.VIDEO_IMITATION') ?: 'default'
        );

        return ['task_id' => $id, 'queued' => true];
    }

    /**
     * 图文任务确认完成（无闪剪）
     * 改写结算成功时已置 status=SUCCESS；此处支持幂等更新文案。
     */
    public static function generateImageText(VideoImitationTask $task, int $userId, string $rewrittenText)
    {
        $alreadyDone = (int)$task->status === VideoImitationTask::STATUS_SUCCESS
            && (int)$task->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS;

        if ((int)$task->status === VideoImitationTask::STATUS_SUCCESS && !$alreadyDone) {
            self::setError('该任务已完成，无法再次下发');
            return false;
        }
        if ((int)$task->status === VideoImitationTask::STATUS_FAIL) {
            self::setError($task->remarks ?: '任务已失败，请重试解析');
            return false;
        }
        if ((int)$task->image_rewrite_status !== VideoImitationTask::IMAGE_REWRITE_STATUS_SUCCESS) {
            if ((int)$task->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_FAIL) {
                self::setError($task->remarks ?: '图片改写失败，请重试');
                return false;
            }
            if ((int)$task->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_SELECTING) {
                self::setError('请先确认要改写的图片');
                return false;
            }
            self::setError('图片改写尚未完成，请稍后再试');
            return false;
        }

        $rewrittenImages = is_array($task->rewritten_images) ? $task->rewritten_images : [];
        if (empty($rewrittenImages)) {
            self::setError('改写图片为空，无法完成生成');
            return false;
        }

        $text = trim($rewrittenText) !== '' ? trim($rewrittenText) : (string)$task->rewritten_text;
        $task->rewritten_text = $text;
        if ($text !== '') {
            $task->publish_text = $text;
            if (empty($task->publish_title)) {
                $task->publish_title = mb_substr($text, 0, 20, 'UTF-8');
            }
        }
        $task->thumbnail = (string)$rewrittenImages[0];
        $task->status = VideoImitationTask::STATUS_SUCCESS;
        if (!$alreadyDone) {
            $task->remarks = '';
        }
        $task->save();

        return $task->toArray();
    }

    /**
     * 队列消费侧的失败标记
     * 此时尚未在 generate() 内扣费,故不涉及退款
     */
    public static function markGenerateFailed(VideoImitationTask $task, string $reason): void
    {
        Db::startTrans();
        try {
            $task->status = 4;
            $task->remarks = $reason;
            $task->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::channel('shanjian')->write("[生成失败标记] 写库异常 task_id={$task->id} err=" . $e->getMessage());
        }
    }

    private static function refundQueueFailure(VideoImitationTask $task, string $reason): void
    {
        if ((int)$task->queue_refund_status === 1) {
            return;
        }

        $tokenCode = (int)$task->is_material === 0
            ? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN
            : AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN;
        $deducted = abs((float)UserTokensLog::where('user_id', (int)$task->user_id)
            ->where('change_type', $tokenCode)
            ->where('task_id', (string)$task->id)
            ->where('action', AccountLogEnum::DEC)
            ->sum('change_amount'));
        $refunded = abs((float)UserTokensLog::where('user_id', (int)$task->user_id)
            ->where('change_type', $tokenCode)
            ->where('task_id', (string)$task->id)
            ->where('action', AccountLogEnum::INC)
            ->sum('change_amount'));
        $points = round(max(0, $deducted - $refunded), 2);
        if ($points > 0) {
            AccountLogLogic::recordUserTokensLog(false, (int)$task->user_id, $tokenCode, $points, (string)$task->id, [
                '扣费项目' => '视频仿写排队失败算力退回',
                '失败原因' => $reason,
            ]);
        }
        $task->queue_refund_status = 1;
    }

    /**
     * 队列轮询更新；视频仿写请求前已预扣且 status=2，waiting 时保持原状态。
     */
    public static function handleQueueStatus(string $taskId, array $queue): bool
    {
        Db::startTrans();
        try {
            $task = VideoImitationTask::where('id', (int)$taskId)->lock(true)->find();
            if (!$task || (int)$task->status >= 3) {
                Db::commit();
                return true;
            }

            $status = (string)($queue['queue_status'] ?? '');
            $task->queue_updated_time = time();
            if ($status === ShanjianQueueService::STATUS_WAITING) {
                if (
                    (int)$task->status !== 2
                    || (string)$task->queue_status === ShanjianQueueService::STATUS_SUBMITTED
                ) {
                    Db::commit();
                    return true;
                }
                $task->queue_status = $status;
                $task->queue_position = max(0, (int)($queue['queue_position'] ?? 0));
                $task->save();
            } elseif ($status === ShanjianQueueService::STATUS_SUBMITTED) {
                $upstreamTaskId = trim((string)($queue['upstream_task_id'] ?? ''));
                if ($upstreamTaskId === '') {
                    Db::commit();
                    return true;
                }
                $task->queue_status = $status;
                $task->queue_position = 0;
                $task->shanjian_task_id = $upstreamTaskId;
                $task->save();
            } elseif ($status === ShanjianQueueService::STATUS_FAILED) {
                if (!in_array((string)$task->queue_status, ['', ShanjianQueueService::STATUS_WAITING], true)) {
                    Db::commit();
                    return true;
                }
                $reason = mb_substr((string)((($queue['message'] ?? '') ?: '闪剪排队失败')), 0, 490, 'UTF-8');
                self::refundQueueFailure($task, $reason);
                $task->queue_status = $status;
                $task->queue_position = 0;
                $task->status = 4;
                $task->remarks = $reason;
                $task->save();
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::channel('shanjian')->write("[视频仿写队列] 更新失败 task_id={$taskId} err=" . $e->getMessage());
            return false;
        }
    }

    /**
     * 用户确认文案并下发生成视频任务
     * @param int $id 任务ID
     * @param int $userId 用户ID
     * @param string $rewrittenText 仿写文案
     * @return array|bool
     * @throws \Exception
     */
    public static function generate(int $id, int $userId, string $rewrittenText)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        if ((int)$task->media_type === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
            return self::generateImageText($task, $userId, $rewrittenText);
        }

        if (!self::assertVideoGenerateAllowed($task)) {
            return false;
        }

        $isGenerateResume = (int)$task->status === VideoImitationTask::STATUS_FAIL;
        if ($isGenerateResume) {
            self::prepareVideoGenerateResume($task);
            $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
            if (!$task) {
                self::setError('未找到该视频复刻记录');
                return false;
            }
        }

        $visualMaterialSource = $task->visual_material_source ?? 3;

        // 分析使用的资源
        $isMaterial = 0;
        $avatarId = '';
        $voiceId = '';
        $materials = [];

        $introduceCard = [];
        $persona = AiPersona::where('id', $task->persona_id)->where('user_id', $userId)->find();
        if ($persona) {
            if ($persona['persona_name'] != '') {
                $introduceCard['name'] = $persona['persona_name'];

                if ($persona['persona_desc'] != '') {
                    $introduceCard['description'] = $persona['persona_desc'];
                }
            }

            // 1. 优先提取数字人形象（随机选择）
            $avatar = AiPersonaDigitalAvatar::availableQuery()
                ->field('ad.*')
                ->where('ad.persona_id', $task->persona_id)
                ->where('ad.user_id', $userId)
                ->orderRand()
                ->find();
            if ($avatar && !empty($avatar['third_avatar_id'])) {
                $isMaterial = 0;
                $avatarId = $avatar['third_avatar_id'];
                $task->avatar_id = $avatar['id'];

                $voiceId = $avatar['third_voice_id'];
                $task->voice_id = $avatar['id'];
            } else {
                // 随机取一个音色
                $voice = AiPersonaDigitalVoice::availableQuery()
                    ->field('ad.*')
                    ->where('ad.persona_id', $task->persona_id)
                    ->where('ad.user_id', $userId)
                    ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                    ->orderRand()
                    ->find();
                if ($voice && !empty($voice['third_voice_id'])) {
                    $isMaterial = 1; // 2. 降级使用素材混剪
                    $voiceId = $voice['third_voice_id'];
                    $task->voice_id = $voice['voice_id'];
                } else {
                    $isMaterial = 2; // 3. 连音色也没有，降级为“新闻体”
                    $voiceId = '';
                }
            }
        }

        // 检查当前人设是否满足生成视频条件
        if ($isMaterial == 0 && (empty($avatarId) || empty($voiceId))) {
            self::setError("当前AI人设未绑定可用的数字人形象和音色，无法生成视频");
            return false;
        }
        if ($isMaterial == 1 && empty($voiceId)) {
            self::setError("当前AI人设未绑定可用的音色，无法生成视频");
            return false;
        }

        // 新闻体：先生成多行标题组；时长取人设合成配置
        $newsTitle = '';
        if ($isMaterial == 2) {
            try {
                $newsTitle = self::buildNewsMixcutTitle($rewrittenText, $userId);
            } catch (\Exception $e) {
                self::setError('新闻体标题生成失败：' . $e->getMessage());
                return false;
            }
            $duration = self::getNewsMixcutDuration((int)$task->persona_id, $userId);
        } else {
            $duration = (int) (mb_strlen($rewrittenText, 'UTF-8') / 3);
            $duration = $duration > 0 ? $duration : 1;
        }

        [$tokenScene, $tokenCode] = self::resolveTokenByMaterialType($isMaterial);

        try {
            $unit = TokenLogService::checkToken($userId, $tokenScene, 1);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

        $extraDesc = match ($isMaterial) {
            0 => '数字人混剪',
            2 => '新闻体视频',
            default => '素材混剪',
        };
        $points = $unit * $duration;

        // --- 开始 AI 素材预检 ---
        $grabVideoUnit = 0;
        $grabImageUnit = 0;
        $extractKeywordUnit = 0;
        if ($visualMaterialSource == 1 || $visualMaterialSource == 2) {
            try {
                $grabVideoUnit = TokenLogService::checkToken($userId, 'grab_video', 1);
                $grabImageUnit = TokenLogService::checkToken($userId, 'grab_image', 1);
                $extractKeywordUnit = TokenLogService::checkToken($userId, 'extract_keywords', 1);
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        }

        $rules = [
            0 => ['v_min' => 2, 'v_max' => 3, 'i_min' => 2, 'i_max' => 3], // 数字人口播混剪
            1 => ['v_min' => 8, 'v_max' => 8, 'i_min' => 2, 'i_max' => 3], // 素材混剪
            2 => ['v_min' => 5, 'v_max' => 5, 'i_min' => 2, 'i_max' => 3], // 新闻体
        ];
        $rule = $rules[$isMaterial] ?? $rules[1];
        $targetVideoCount = rand($rule['v_min'], $rule['v_max']);
        $targetImageCount = rand($rule['i_min'], $rule['i_max']);

        $localVideos = [];
        $localImages = [];
        if ($visualMaterialSource == 2 || $visualMaterialSource == 3) {
            $allMaterials = Material::where('persona_id', $task->persona_id)
                ->where('user_id', $userId)
                ->where('use_status', 1)
                ->where('publish_mode', 1)
                ->select()->toArray();
            $materialCheck = MaterialReadinessService::checkFileUrlsForSubmit(array_column($allMaterials, 'file_url'));
            $blockedUris = array_fill_keys(array_merge(
                $materialCheck['pending_uris'] ?? [],
                $materialCheck['failed_uris'] ?? []
            ), true);
            foreach ($allMaterials as $m) {
                if (!empty($m['file_url'])) {
                    $uri = self::normalizeMaterialUri((string)$m['file_url']);
                    if ($uri !== '' && isset($blockedUris[$uri])) {
                        continue;
                    }
                    if ($m['material_type'] == Material::MATERIAL_TYPE_VIDEO) {
                        $materialDuration = !empty($m['duration']) ? (int)$m['duration'] : 0;
                        if ($materialDuration <= 0) {
                            continue;
                        }
                        $localVideos[] = [
                            'fileUrl' => FileService::getFileUrl($m['file_url']),
                            'type' => 'video',
                            'duration' => $materialDuration
                        ];
                    } elseif ($m['material_type'] == Material::MATERIAL_TYPE_IMAGE) {
                        // 仅图片进图片桶：音乐素材(3)混入会被闪剪按"图片类型不符"拒单
                        $localImages[] = [
                            'fileUrl' => FileService::getFileUrl($m['file_url']),
                            'type' => 'image',
                            'duration' => 2
                        ];
                    }
                }
            }
        }

        $maxAiCost = 0;
        if ($visualMaterialSource == 1 || $visualMaterialSource == 2) {
            $maxGrabVideos = $targetVideoCount;
            $maxGrabImages = $targetImageCount;
            if ($visualMaterialSource == 2) {
                $maxGrabVideos = max(0, $targetVideoCount - count($localVideos));
                $maxGrabImages = max(0, $targetImageCount - count($localImages));
            }
            if ($maxGrabVideos > 0 || $maxGrabImages > 0) {
                $maxAiCost = $extractKeywordUnit + ($maxGrabVideos * $grabVideoUnit) + ($maxGrabImages * $grabImageUnit);
            } elseif ($visualMaterialSource == 1) {
                $maxAiCost = $extractKeywordUnit + ($maxGrabVideos * $grabVideoUnit) + ($maxGrabImages * $grabImageUnit);
            }
        }

        // 企业空间成员看企业钱包，勿用个人 tokens 预检
        $spendable = \app\common\service\TeamBillingService::spendableTokens((int)$userId);
        $totalMaxPoints = $points + $maxAiCost;
        if ($spendable < $totalMaxPoints) {
            $msg = \app\common\service\TeamBillingService::resolveSpender((int)$userId) !== null
                ? "当前团队算力不足以支付本次生成的最高预估消耗（预估可能需 {$totalMaxPoints} 算力，可用 {$spendable}）"
                : "您的算力余额不足以支付本次生成的最高预估消耗（预估可能需 {$totalMaxPoints} 算力）";
            self::setError($msg);
            return false;
        }

        // MiniMax 音色不能作为闪剪 speakerId 下发，需先 TTS 出音频改用 audioUrl 音频驱动
        $minimaxAudioUrl = '';
        if ($isMaterial != 2 && !empty($voiceId)
            && ShanjianVideoSettingLogic::isMinimaxVoiceId((string)$voiceId, (int)$userId)
        ) {
            $minimaxAudioUrl = self::buildMinimaxImitationAudio((string)$voiceId, $rewrittenText, $userId);
            if ($minimaxAudioUrl === '') {
                $ttsError = self::getError() ?: 'MiniMax音频合成失败';
                // TTS 失败回退人设下可用的闪剪音色，兜不住才报错
                $fallbackVoices = AiPersonaDigitalVoice::availableQuery()
                    ->where('ad.user_id', $userId)
                    ->where('ad.persona_id', $task->persona_id)
                    ->where('ad.provider', AiPersonaDigitalVoice::PROVIDER_SHANJIAN)
                    ->whereRaw('(hv.model_version IS NULL OR hv.model_version NOT IN (10, 11))')
                    ->column('ad.third_voice_id');
                $fallbackVoices = array_values(array_filter(array_map('strval', $fallbackVoices)));
                if (empty($fallbackVoices)) {
                    self::setError($ttsError . '，且人设下无可用闪剪音色，无法生成视频');
                    return false;
                }
                $voiceId = $fallbackVoices[random_int(0, count($fallbackVoices) - 1)];
                Log::channel('shanjian')->write(sprintf(
                    '[视频仿写] MiniMax TTS失败已回退闪剪音色 task_id=%d fallback_voice_id=%s err=%s',
                    (int)$task->id,
                    $voiceId,
                    $ttsError
                ));
            }
        }

        Db::startTrans();
        try {
            if ($points > 0) {
                User::userTokensChange($userId, $points);
                $extra = ['扣费项目' => '爆款视频复刻-' . $extraDesc, '算力单价' => $unit, "预估时长" => $duration . '秒', '实际消耗算力' => $points];
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, (string) $task->id, $extra);
            }
            $task->duration = $duration;
            $task->is_material = $isMaterial;
            $task->status = 2;
            $task->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            self::setError("启动生成前预扣费失败：" . $e->getMessage());
            return false;
        }

        // --- 开始获取素材 ---
        try {
            $materials = self::getMixedOrAiMaterials(
                $rewrittenText, 
                $visualMaterialSource, 
                $userId, 
                $task->persona_id, 
                $localVideos, 
                $localImages, 
                $targetVideoCount, 
                $targetImageCount,
                $extractKeywordUnit,
                $grabVideoUnit,
                $grabImageUnit,
                $task->id
            );
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if ($e instanceof HttpResponseException) {
                $responseData = $e->getResponse()->getData();
                $errorMsg = $responseData['msg'] ?? (is_string($responseData) ? $responseData : '');
            }
            $errorMsg = $errorMsg ?: '获取素材期间发生接口异常';

            Db::startTrans();
            try {
                if ($points > 0) {
                    User::userTokensChange($userId, $points, 'inc');
                    $extra = ['退回项目' => '爆款视频复刻-' . $extraDesc . ' 失败退回', '算力单价' => $unit, "预估时长" => $duration . '秒', '实际消耗算力' => $points];
                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $points, (string) $task->id, $extra);
                }
                $task->status = 4;
                $task->remarks = '获取素材失败：' . $errorMsg;
                $task->save();
                Db::commit();
            } catch (\Exception $e2) {
                Db::rollback();
            }
            self::setError("获取素材失败：" . $errorMsg);
            return false;
        }

        if (empty($materials)) {
            Db::startTrans();
            try {
                if ($points > 0) {
                    User::userTokensChange($userId, $points, 'inc');
                    $extra = ['退回项目' => '爆款视频复刻-' . $extraDesc . ' 失败退回', '算力单价' => $unit, "预估时长" => $duration . '秒', '实际消耗算力' => $points];
                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $points, (string) $task->id, $extra);
                }
                $task->status = 4;
                $task->remarks = '未获取到可用的辅助素材';
                $task->save();
                Db::commit();
            } catch (\Exception $e2) {
                Db::rollback();
            }
            self::setError("当前任务未能获取到可用的辅助素材，无法生成视频");
            return false;
        }

        // 组装接口数据
        $shanjianPayload = [
            "packRules" => [
                "backgroundMusic" => [
                    "audioSwitch" => true,
                    "volume" => $isMaterial == 2 ? 0.6 : 0.5
                ]
            ],
            "processRules" => [
                "watermarkShow" => false
            ],
            "materials" => $materials,
            "materialSoundSwitch" => false,
            "task_id" => (string) $task->id,
            "user_id" => (string) $userId,
            "now" => time(),
            "duration" => $duration
        ];

        if ($isMaterial == 2) {
            // 新闻体：多行 title，不传口播 content / speakerId
            $shanjianPayload['title'] = $newsTitle;
            $shanjianPayload['processRules']['videoDuration'] = (int)$duration;
            $task->title = $newsTitle;
        } else {
            $shanjianPayload['title'] = $task->title ?: date('Y-m-d H:i:s');
            if ($minimaxAudioUrl !== '') {
                // MiniMax 音频驱动：闪剪只认 audioUrl，不传 content/speakerId
                $shanjianPayload['audioUrl'] = $minimaxAudioUrl;
            } else {
                $shanjianPayload['content'] = $rewrittenText;
                if (!empty($voiceId)) {
                    $shanjianPayload['speakerId'] = $voiceId;
                }
            }
            $task->title = $shanjianPayload['title'];
        }

        if (!empty($introduceCard)) {
            $shanjianPayload['introduceCard'] = $introduceCard;
        }
        // 获取视频风格(剪辑模板)
        $styleScene = self::resolveStyleScene($isMaterial);
        $clip = ShanjianClipTemplate::where('scene', $styleScene)->orderRand()->find();
        if ($clip) {
            $shanjianPayload['styleId'] = $clip['id'];
        }

        $videoService = ToolsService::VideoImitation();
        Log::channel("shanjian")->write("[请求]下发视频仿写任务：" . json_encode($shanjianPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($isMaterial == 0) {
            $shanjianPayload['virtualmanId'] = $avatarId;
            $response = $videoService->virtualmanBroadcast($shanjianPayload);
        } elseif ($isMaterial == 1) {
            // 素材混剪
            $shanjianPayload['materialSoundSwitch'] = false;
            $response = $videoService->mixcutBroadcast($shanjianPayload);
        } else {
            // 新闻体
            $shanjianPayload['materialSoundSwitch'] = false;
            $response = $videoService->newsMixcut($shanjianPayload);
        }
        Log::channel("shanjian")->write("[响应]下发视频仿写任务：" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $queue = ShanjianQueueService::normalizeSubmission($response);
        $queueAccepted = $queue['queue_status'] === ShanjianQueueService::STATUS_WAITING
            || ($queue['queue_status'] === ShanjianQueueService::STATUS_SUBMITTED && $queue['upstream_task_id'] !== '');
        if ($queueAccepted) {
            // 第三方任务下发成功
            $task->queue_status = $queue['queue_status'];
            $task->queue_position = $queue['queue_status'] === ShanjianQueueService::STATUS_WAITING
                ? $queue['queue_position']
                : 0;
            $task->queue_updated_time = time();
            if ($queue['queue_status'] === ShanjianQueueService::STATUS_SUBMITTED) {
                $task->shanjian_task_id = $queue['upstream_task_id'];
            }
            // 提取标题 (生成短标题)
            ToolsLogic::getMatrixCopywriting(['user_id' => $userId, 'keywords' => $rewrittenText, 'number' => 1]);
            $titleResponse = ToolsLogic::getReturnData();
            if (!empty($titleResponse[0])) {
                $task->publish_title = $titleResponse[0]['title'];
                $task->publish_text = $titleResponse[0]['content'];
                $task->publish_topic = json_encode($titleResponse[0]['topic'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $task->publish_title = mb_substr($rewrittenText, 0, 10, 'UTF-8');
                $task->publish_text = $rewrittenText;
            }

            // 保存用户确认的仿写文案
            $task->rewritten_text = $rewrittenText;

            // status 已经在上面变成 2 了
            $task->save();
            $result = ['task_id' => $task->id];
            if ($isGenerateResume) {
                $result['resume_from'] = 'generate';
            }
            return $result;
        } elseif ($queue['queue_status'] === ShanjianQueueService::STATUS_FAILED) {
            self::handleQueueStatus((string)$task->id, $queue);
            self::setError(($queue['message'] ?? '') ?: '第三方平台排队失败');
            return false;
        } else {
            // 下发失败回退(开启事务)
            Db::startTrans();
            try {
                $task->status = 1; // 回退状态
                $task->save();
                if ($points > 0) {
                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $points, (string) $task->id, ['扣费项目' => '生成接口下发失败回退']);
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollback();
            }

            self::setError($response['message'] ?? '第三方平台任务下发失败');
            return false;
        }
    }

    /**
     * 服务端回调 - 更新视频状态并处理完结资源等
     */
    public static function notify(array $data)
    {
        $userId = $data['user_id'] ?? 0;
        $taskId = $data['task_id'] ?? 0;
        $statusStr = $data['status'] ?? '';

        // 【第一重检查】获取锁之前先查一次数据库
        $task = VideoImitationTask::where('id', $taskId)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("不存在的任务");
            return false;
        }

        // 如果已经成功或者失败了，无需再尝试获取锁和处理
        if ($task->status >= 3) {
            return true;
        }

        $lockKey = 'video_imitation_notify_' . $taskId;
        // 使用 Redis 分布式锁 (setnx)
        try {
            $redis = \think\facade\Cache::store('redis')->handler();
            if (!$redis->setnx($lockKey, 1)) {
                self::setError("任务正在处理中，请勿重复请求");
                return false;
            }
            $redis->expire($lockKey, 60); // 锁60秒
        } catch (\Exception $e) {
            // 如果 redis 异常，报错
            self::setError("获取分布式锁失败");
            return false;
        }

        try {
            // 【第二重检查】获取锁之后，重新加载数据，防止在等待锁的极短期间状态已被其他并发请求更改
            $task->refresh();
            if ($task->status >= 3) {
                return true;
            }

            $videoUrl = $data['url'] ?? ($data['result']['videoUrl'] ?? '');
            $duration = $data['duration'] ?? ($data['result']['duration'] ?? 0);
            $errorMessage = $data['message'] ?? ($data['errorMessage'] ?? ($data['reason'] ?? ''));

            [$tokenScene, $tokenCode] = self::resolveTokenByMaterialType((int)$task->is_material);
            $task->queue_status = ShanjianQueueService::STATUS_SUBMITTED;
            $task->queue_position = 0;
            $task->queue_updated_time = time();

            if ($statusStr === 'succeed' || $statusStr == 3 || !empty($videoUrl)) {
                // 生成成功
                Db::startTrans();
                try {
                    $task->status = 3; // 成功
                    $task->video_url = FileService::downloadFileBySource($videoUrl, 'video');

                    //生成缩略图
                    $videos          = [
                        'video_url' => FileService::getFileUrl($task->video_url),
                        'time'      => 1.0,
                        'options'   => [
                            'quality' => 2
                        ]
                    ];
                    $thumbnailResult = (new VideoInfoService())->commonVideoThumbnail($videos);
                    if ($thumbnailResult['result']) {
                        $task->thumbnail = FileService::setFileUrl($thumbnailResult['url']);
                    }
                    if ((float)$duration > 0) {
                        $actualDuration = $duration; // 不取整，允许扣除小数算力
                        $preDeductDuration = $task->duration;

                        // 如果实际时长和预估时长不一致，进行补扣或退费
                        if ($actualDuration != $preDeductDuration) {
                            [$tokenScene, $tokenCode] = self::resolveTokenByMaterialType((int)$task->is_material);
                            $unit = TokenLogService::getTypeScore($tokenScene); // 获取单价

                            if ($actualDuration > $preDeductDuration) {
                                // 补扣 (实际时长大于预估)
                                $diffDuration = (float)bcsub((string)$actualDuration, (string)$preDeductDuration, 2);
                                $diffPoints = (float)bcmul((string)$unit, (string)$diffDuration, 2);
                                if ($diffPoints > 0) {
                                    // 补扣主体按预扣那一次的空间(用户切换空间后仍算原空间)
                                    $settleTeamId = \app\common\service\TeamBillingService::deductByOriginalLog(
                                        $userId,
                                        $diffPoints,
                                        (int)$tokenCode,
                                        (string)$taskId
                                    );
                                    $extraAdd = [
                                        '扣费项目' => '爆款视频复刻-超出预估补扣',
                                        '算力单价' => $unit,
                                        '预估时长' => $preDeductDuration . '秒',
                                        '实际时长' => $actualDuration . '秒',
                                        '补扣时长' => $diffDuration . '秒',
                                        '实际消耗算力' => $diffPoints
                                    ];
                                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $diffPoints, (string)$taskId, $extraAdd, $settleTeamId);
                                }
                            } else {
                                // 退费 (实际时长小于预估)
                                $diffDuration = (float)bcsub((string)$preDeductDuration, (string)$actualDuration, 2);
                                $diffPoints = (float)bcmul((string)$unit, (string)$diffDuration, 2);
                                if ($diffPoints > 0) {
                                    // recordUserTokensLog 传false将自动调用 User::userTokensChange($userId, $diffPoints, 'inc') 进行退费
                                    $extraRefund = [
                                        '扣费项目' => '爆款视频复刻-结余预估退费',
                                        '算力单价' => $unit,
                                        '预估时长' => $preDeductDuration . '秒',
                                        '实际时长' => $actualDuration . '秒',
                                        '退费时长' => $diffDuration . '秒',
                                        '实际退费算力' => $diffPoints
                                    ];
                                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $diffPoints, (string)$taskId, $extraRefund);
                                }
                            }
                        }
                        $task->duration = $actualDuration;
                    }
                    $task->save();
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    self::setError('更新回调状态异常');
                    return false;
                }
            } elseif ($statusStr === 'failed' || $statusStr == 4) {
                // 生成失败
                Db::startTrans();
                try {
                    $task->status = 4; // 失败
                    $task->remarks = mb_substr($errorMessage ?: '第三方生成失败', 0, 490, 'UTF-8');
                    $task->save();

                    self::refundQueueFailure($task, $task->remarks);
                    $task->save();
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    self::setError('更新回调失败状态异常');
                    return false;
                }
            }
            return true;
        } finally {
            // 无论如何，执行完毕后释放锁
            try {
                $redis->del($lockKey);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 定时生成任务 (Cron服务调用)
     * 支持超时（例如30分钟）未由用户主动确认文案的任务 (status=1) 自动拉起下发生成逻辑
     */
    public static function autoGenerateTasksCron()
    {
        $timeoutDuration = 1800; // 30 mins
        $deadlineTime = time() - $timeoutDuration;

        try {
            $pendingTasks = VideoImitationTask::where('status', '=', 1)
                ->where('task_delete', '=', 0)
                ->where(function ($q) {
                    $q->whereNull('media_type')
                        ->whereOr('media_type', '<>', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT);
                })
                ->where('update_time', '<', $deadlineTime)
                ->limit(50) // 每次定时脚本处理上限防堆积
                ->select();

            foreach ($pendingTasks as $task) {
                // 如果用户在中途丢失了 persona_id 或原复刻文案丢失，则自动失败
                if (empty($task->persona_id) || empty($task->rewritten_text)) {
                    $task->status = 4;
                    $task->remarks = '自动下发：缺少必要的AI人设ID或复刻文案';
                    $task->save();
                    continue;
                }

                // 直接内部调用已有的下发逻辑
                $result = self::generate($task->id, $task->user_id, $task->rewritten_text);
                if ($result === false) {
                    $task->status = 4;
                    $task->remarks = self::getError();
                    $task->save();
                }

                Log::channel('shanjian')->write("定时触发视频仿写生成任务：" . json_encode(self::getError(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            Log::channel('shanjian')->write("定时触发视频仿写生成任务异常：" . $e->getMessage());
        }
    }

    /**
     * 随机抽取指定人设的素材集合
     * - 视频素材抽取 1~2 个（不足或重复时，单视频最多用2次）
     * - 图片素材抽取 2~3 个（不足或重复时，单图片最多用3次）
     */
    private static function getRandomMaterials(int $personaId, int $userId, int $isMaterial = 0): array
    {
        $allMaterials = Material::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->where('use_status', 1)
            ->where('publish_mode', 1)
            ->select()->toArray();

        $videos = [];
        $images = [];
        foreach ($allMaterials as $m) {
            if (!empty($m['file_url'])) {
                    if ($m['material_type'] == Material::MATERIAL_TYPE_VIDEO) {
                        $duration = !empty($m['duration']) ? (int)$m['duration'] : 0;
                        // 无有效时长时不参与抽取，避免默认5s低估真实时长触发闪剪5分钟限制
                        if ($duration <= 0) {
                            continue;
                        }
                        $videos[] = [
                            'fileUrl' => FileService::getFileUrl($m['file_url']),
                            'type' => 'video',
                            'duration' => $duration
                        ];
                    } elseif ($m['material_type'] == Material::MATERIAL_TYPE_IMAGE) {
                        // 仅图片进图片桶：音乐素材(3)混入会被闪剪按"图片类型不符"拒单
                        $images[] = [
                            'fileUrl' => FileService::getFileUrl($m['file_url']),
                            'type' => 'image',
                            'duration' => 2
                        ];
                    }
            }
        }

        $result = [];
        $currentDuration = 0;
        $maxDuration = 300; // 5分钟

        // 根据生成类型配置抽取数量要求
        $targetVideoCount = $isMaterial == 1 ? 8 : rand(2, 3);
        $targetImageCount = rand(2, 3);

        // 随机打乱（不可重复抽取）
        shuffle($videos);
        shuffle($images);

        $pickedVideoCount = 0;
        $pickedImageCount = 0;
        $unusedVideos = [];
        $unusedImages = [];

        // 1. 优先抽取视频
        foreach ($videos as $v) {
            if ($pickedVideoCount < $targetVideoCount) {
                $duration = (float)($v['duration'] ?? 0);
                if ($duration <= 0 || $duration > 59) {
                    continue;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue; // 跳过过长素材，继续尝试更短的
                }
                $result[] = $v;
                $currentDuration += $duration;
                $pickedVideoCount++;
            } else {
                $unusedVideos[] = $v;
            }
        }

        // 计算视频缺位，由图片补充
        $videoDeficit = max(0, $targetVideoCount - $pickedVideoCount);
        $neededImageCount = $targetImageCount + $videoDeficit;

        // 2. 抽取图片
        foreach ($images as $img) {
            if ($pickedImageCount < $neededImageCount) {
                $duration = (float)($img['duration'] ?? 2);
                if ($duration <= 0) {
                    $duration = 2;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue;
                }
                $result[] = $img;
                $currentDuration += $duration;
                $pickedImageCount++;
            } else {
                $unusedImages[] = $img;
            }
        }

        // 3. 检查图片缺位，若图片依然不够，则反向从剩余未使用视频内补充
        $imageDeficit = max(0, $neededImageCount - $pickedImageCount);
        if ($imageDeficit > 0 && !empty($unusedVideos)) {
            foreach ($unusedVideos as $v) {
                if ($imageDeficit <= 0) break;
                $duration = (float)($v['duration'] ?? 0);
                if ($duration <= 0 || $duration > 59) {
                    continue;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue;
                }
                $result[] = $v;
                $currentDuration += $duration;
                $imageDeficit--;
            }
        }

        return self::formatMaterialResult(ShanjianVideoSettingLogic::trimMaterialsByDuration($result));
    }

    private static function normalizeMaterialUri(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            return ltrim((string)$path, '/');
        }

        return ltrim($url, '/');
    }

    /**
     * 提交闪剪前素材门禁（与人设本地素材同一套口径）
     * transcode_status 1/2 转码中、4 失败 → 剔除；0/3 成功或无需转码 → 保留
     *
     * @param array<int, array<string, mixed>> $materials
     * @return array<int, array<string, mixed>>
     */
    private static function filterMaterialsReadyForSubmit(array $materials): array
    {
        if (empty($materials)) {
            return [];
        }

        $fileUrls = [];
        foreach ($materials as $m) {
            $url = (string)($m['fileUrl'] ?? $m['file_url'] ?? '');
            if ($url !== '') {
                $fileUrls[] = $url;
            }
        }
        if (empty($fileUrls)) {
            return $materials;
        }

        $materialCheck = MaterialReadinessService::checkFileUrlsForSubmit($fileUrls);
        $blockedUris = array_fill_keys(array_merge(
            $materialCheck['pending_uris'] ?? [],
            $materialCheck['failed_uris'] ?? []
        ), true);
        if (empty($blockedUris)) {
            return array_values($materials);
        }

        $kept = [];
        foreach ($materials as $m) {
            $uri = self::normalizeMaterialUri((string)($m['fileUrl'] ?? $m['file_url'] ?? ''));
            if ($uri !== '' && isset($blockedUris[$uri])) {
                continue;
            }
            $kept[] = $m;
        }

        Log::channel('shanjian')->write('[视频仿写AI抓素材门禁] ' . json_encode([
            'origin_count' => count($materials),
            'kept_count' => count($kept),
            'pending_uris' => $materialCheck['pending_uris'] ?? [],
            'failed_uris' => $materialCheck['failed_uris'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return array_values($kept);
    }

    private static function formatMaterialResult(array $list): array
    {
        $res = [];
        foreach ($list as $item) {
            $res[] = ['fileUrl' => $item['fileUrl'], 'type' => $item['type']];
        }
        return $res;
    }

    private static function getMixedOrAiMaterials(
        string $text,
        int $sourceMode,
        int $userId,
        int $personaId,
        array $localVideos,
        array $localImages,
        int $targetVideoCount,
        int $targetImageCount,
        float $extractPrice,
        float $grabVideoPrice,
        float $grabImagePrice,
        int $taskId
    ): array
    {
        $finalVideos = [];
        $finalImages = [];

        shuffle($localVideos);
        shuffle($localImages);

        if ($sourceMode == 3) {
            $finalVideos = array_slice($localVideos, 0, $targetVideoCount);
            $finalImages = array_slice($localImages, 0, $targetImageCount);
        } else {
            if ($sourceMode == 1) {
                // Pure AI
                $localVideos = [];
                $localImages = [];
            }
            $finalVideos = array_slice($localVideos, 0, $targetVideoCount);
            $finalImages = array_slice($localImages, 0, $targetImageCount);
            
            $vGap = $targetVideoCount - count($finalVideos);
            $iGap = $targetImageCount - count($finalImages);
            
            if ($vGap > 0 || $iGap > 0) {
                // Grab AI
                list($aiV, $aiI) = self::grabAiMaterials($text, $vGap, $iGap, $userId, $personaId, $extractPrice, $grabVideoPrice, $grabImagePrice, $taskId);
                $finalVideos = array_merge($finalVideos, $aiV);
                $finalImages = array_merge($finalImages, $aiI);
            }
        }

        // Limit duration check (max 300s)
        $result = [];
        $currentDuration = 0;
        $maxDuration = 300;
        
        $pickedVideoCount = 0;
        $pickedImageCount = 0;
        $unusedVideos = [];

        foreach ($finalVideos as $v) {
            if ($pickedVideoCount < $targetVideoCount) {
                $duration = (float)($v['duration'] ?? 0);
                if ($duration <= 0 || $duration > 59) {
                    continue;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue;
                }
                $result[] = $v;
                $currentDuration += $duration;
                $pickedVideoCount++;
            } else {
                $unusedVideos[] = $v;
            }
        }

        $videoDeficit = max(0, $targetVideoCount - $pickedVideoCount);
        $neededImageCount = $targetImageCount + $videoDeficit;

        foreach ($finalImages as $img) {
            if ($pickedImageCount < $neededImageCount) {
                $duration = (float)($img['duration'] ?? 2);
                if ($duration <= 0) {
                    $duration = 2;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue;
                }
                $result[] = $img;
                $currentDuration += $duration;
                $pickedImageCount++;
            }
        }

        $imageDeficit = max(0, $neededImageCount - $pickedImageCount);
        if ($imageDeficit > 0 && !empty($unusedVideos)) {
            foreach ($unusedVideos as $v) {
                if ($imageDeficit <= 0) break;
                $duration = (float)($v['duration'] ?? 0);
                if ($duration <= 0 || $duration > 59) {
                    continue;
                }
                if ($currentDuration + $duration > $maxDuration) {
                    continue;
                }
                $result[] = $v;
                $currentDuration += $duration;
                $imageDeficit--;
            }
        }

        return self::formatMaterialResult(ShanjianVideoSettingLogic::trimMaterialsByDuration($result));
    }

    private static function grabAiMaterials(
        string $text,
        int $vCount,
        int $iCount,
        int $userId,
        int $personaId,
        float $extractPrice,
        float $grabVideoPrice,
        float $grabImagePrice,
        int $taskId
    ): array
    {
        $videos = [];
        $images = [];
        $totalNeed = $vCount + $iCount;

        // 提取关键词扣费
        if ($extractPrice > 0) {
            User::userTokensChange($userId, $extractPrice);
            $extra = ['扣费项目' => '爆款仿写-仿写文案匹配关键词', '算力单价' => $extractPrice, '实际消耗算力' => $extractPrice];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS, $extractPrice, (string)$taskId, $extra);
        }

        $requestData['keywords'] = $text;
        $response = \app\common\service\ToolsService::Coze()->extractKeywords($requestData);
        $keywordsList = [];
        if (isset($response['code']) && $response['code'] == 10000 && !empty($response['data']['content'])) {
            $keywordsList = $response['data']['content'];
        }

        if (empty($keywordsList)) {
            $persona = AiPersona::where('id', $personaId)->find();
            if ($persona && !empty($persona['industry'])) {
                $keywordsList = explode(',', $persona['industry']);
            } else {
                $keywordsList = ['商业', '科技', '自然', '风景', '城市'];
            }
        }

        $grabbedVideosCount = 0;
        $grabbedImagesCount = 0;

        $grabReq = ['orientation' => 'portrait'];
        $grabReq['task_id'] = $taskId;
        $grabReq['user_id'] = $userId;
        $grabReq['now'] = time();
        
        foreach ($keywordsList as $keyword) {
            if ($grabbedVideosCount >= $vCount && $grabbedImagesCount >= $iCount) break;

            if ($grabbedVideosCount < $vCount) {
                $grabReq['keywords'] = $keyword;
                $grabReq['searchTerm'] = $keyword;
                $vRes = \app\common\service\ToolsService::Grab()->video($grabReq);
                if (isset($vRes['code']) && $vRes['code'] == 10000 && !empty($vRes['data'])) {
                    $list = $vRes['data'];
                    shuffle($list);
                    foreach ($list as $item) {
                        if ($grabbedVideosCount >= $vCount) break;
                        $link = (string)($item['link'] ?? '');
                        if ($link === '' || !FileService::isAllowedGrabMaterialUrl($link, 'video')) {
                            continue;
                        }
                        try {
                            $transRes = \app\common\service\UploadService::transcodeBySource($link, 'video', 0, 0);
                            $url = !empty($transRes['oss_uri']) ? $transRes['oss_uri'] : $transRes['url'];
                            if (empty($url)){
                                continue;
                            }
                            $duration = !empty($item['duration']) ? (int)$item['duration'] : 0;
                            if ($duration <= 0) {
                                // 抓取结果无时长时跳过，避免默认5s低估触发闪剪5分钟限制
                                continue;
                            }
                            $videos[] = [
                                'fileUrl' => FileService::getFileUrl($url),
                                'type' => 'video',
                                'duration' => $duration
                            ];
                            $grabbedVideosCount++;
                        } catch (\Exception $e) {
                            \think\facade\Log::error("视频素材抓取转码失败：" . $e->getMessage());
                            continue;
                        }
                        break;
                    }
                }
            }

            if ($grabbedImagesCount < $iCount) {
                $grabReq['keywords'] = $keyword;
                $grabReq['searchTerm'] = $keyword;
                $iRes = \app\common\service\ToolsService::Grab()->image($grabReq);
                if (isset($iRes['code']) && $iRes['code'] == 10000 && !empty($iRes['data'])) {
                    $list = $iRes['data'];
                    shuffle($list);
                    foreach ($list as $item) {
                        if ($grabbedImagesCount >= $iCount) break;
                        $link = (string)($item['link'] ?? '');
                        if ($link === '' || !FileService::isAllowedGrabMaterialUrl($link, 'image')) {
                            continue;
                        }
                        try {
                            $transRes = \app\common\service\UploadService::transcodeBySource($link, 'image', 0, 0);
                            $url = !empty($transRes['oss_uri']) ? $transRes['oss_uri'] : $transRes['url'];
                            if (empty($url)){
                                continue;
                            }
                            $images[] = [
                                'fileUrl' => FileService::getFileUrl($url),
                                'type' => 'image',
                                'duration' => 2
                            ];
                            $grabbedImagesCount++;
                        } catch (\Exception $e) {
                            \think\facade\Log::error("图片素材抓取转码失败：" . $e->getMessage());
                            continue;
                        }
                        break;
                    }
                }
            }
        }

        // 与人设本地素材同一套门禁：转码中/失败剔除，转码成功才保留
        $videos = self::filterMaterialsReadyForSubmit($videos);
        $images = self::filterMaterialsReadyForSubmit($images);
        $grabbedVideosCount = count($videos);
        $grabbedImagesCount = count($images);

        // 结算实际抓取的视频和图片扣费
        $actualVideoCost = $grabbedVideosCount * $grabVideoPrice;
        $actualImageCost = $grabbedImagesCount * $grabImagePrice;

        if ($actualVideoCost > 0) {
            User::userTokensChange($userId, $actualVideoCost);
            $extra = ['扣费项目' => '爆款仿写-AI自动找视频素材扣费', '算力单价' => $grabVideoPrice, '视频数量' => $grabbedVideosCount, '实际消耗算力' => $actualVideoCost];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_GRAB_VIDEO, $actualVideoCost, (string)$taskId, $extra);
        }

        if ($actualImageCost > 0) {
            User::userTokensChange($userId, $actualImageCost);
            $extra = ['扣费项目' => '爆款仿写-AI自动找图片素材扣费', '算力单价' => $grabImagePrice, '图片数量' => $grabbedImagesCount, '实际消耗算力' => $actualImageCost];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_GRAB_IMAGE, $actualImageCost, (string)$taskId, $extra);
        }

        return [$videos, $images];
    }

    /**
     * 按混剪类型解析计费 scene / 扣费码
     * @return array{0: string, 1: int}
     */
    private static function resolveTokenByMaterialType(int $isMaterial): array
    {
        return match ($isMaterial) {
            0 => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
            2 => ['shanjian_news_mixcut', AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN],
            default => ['shanjian_broadcast_mixcut', AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN],
        };
    }

    /**
     * 按混剪类型解析闪剪剪辑模板 scene
     */
    private static function resolveStyleScene(int $isMaterial): string
    {
        return match ($isMaterial) {
            0 => 'virtualman',
            2 => 'newsMixCutting',
            default => 'oralMixCutting',
        };
    }

    /**
     * 读取人设新闻体固定时长
     */
    private static function getNewsMixcutDuration(int $personaId, int $userId): int
    {
        $duration = null;
        if ($personaId > 0 && $userId > 0) {
            $duration = AiPersonaSynthesisConfig::where('persona_id', $personaId)
                ->where('user_id', $userId)
                ->value('news_mixcut_duration');
        }
        return AiPersonaSynthesisConfig::normalizeNewsMixcutDuration($duration);
    }

    /**
     * 根据仿写文案生成新闻体多行标题组
     */
    private static function buildNewsMixcutTitle(string $rewrittenText, int $userId): string
    {
        $result = AutoDeviceSettingLogic::copywriting([
            'keywords' => $rewrittenText,
            'number' => 1,
        ], $userId, 2);

        $content = $result['content'] ?? '';
        $lines = [];
        if (is_array($content)) {
            foreach ($content as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $lines = array_merge($lines, preg_split('/\r\n|\r|\n/', trim($item)) ?: []);
                } elseif (is_array($item)) {
                    $title = $item['title'] ?? ($item[0] ?? '');
                    if (is_array($title)) {
                        foreach ($title as $t) {
                            if (is_string($t) && trim($t) !== '') {
                                $lines[] = trim($t);
                            }
                        }
                    } elseif (trim((string)$title) !== '') {
                        $lines = array_merge($lines, preg_split('/\r\n|\r|\n/', trim((string)$title)) ?: []);
                    }
                }
            }
        } else {
            $text = str_replace(['\\n'], "\n", trim((string)$content));
            $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        }

        $lines = array_values(array_filter(array_map('trim', $lines), static fn($line) => $line !== ''));
        if (empty($lines)) {
            throw new \Exception('未返回有效新闻标题');
        }

        return implode("\n", array_slice($lines, 0, 6));
    }

    /**
     * MiniMax 音色先合成口播音频（闪剪 speakerId 不识别 MiniMax voice id，需音频驱动）
     * @param string $thirdVoiceId 人设绑定的 third_voice_id（= human_voice.voice_id）
     * @return string 成功返回音频完整URL，失败返回空串（原因在 self::getError()）
     */
    private static function buildMinimaxImitationAudio(string $thirdVoiceId, string $content, int $userId): string
    {
        $content = trim($content);
        if ($content === '') {
            self::setError('MiniMax音色合成音频缺少口播文案');
            return '';
        }

        $humanVoice = HumanVoice::where('voice_id', $thirdVoiceId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->findOrEmpty();
        if ($humanVoice->isEmpty()) {
            self::setError('MiniMax音色不存在或已失效');
            return '';
        }

        try {
            $ok = MinimaxVoiceLogic::audio([
                'minimax_voice_id' => (int)$humanVoice->id,
                'text' => $content,
            ], $userId);
        } catch (\Throwable $e) {
            self::setError('MiniMax音频合成异常：' . $e->getMessage());
            return '';
        }
        if (!$ok) {
            self::setError(MinimaxVoiceLogic::getError() ?: 'MiniMax音频合成失败');
            return '';
        }

        $audioUrl = trim((string)MinimaxVoiceLogic::getReturnData());
        if ($audioUrl === '') {
            self::setError('MiniMax音频合成未返回音频地址');
            return '';
        }
        return FileService::getFileUrl($audioUrl);
    }
}
