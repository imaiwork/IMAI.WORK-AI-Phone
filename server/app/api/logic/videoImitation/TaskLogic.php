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
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\file\File;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\sv\SvMediaMaterial;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\MaterialReadinessService;
use app\common\service\draw\MediaModelsService;
use app\common\service\ShanjianQueueService;
use app\common\service\ToolsService;
use app\common\service\UploadService;
use app\common\service\VideoInfoService;
use app\common\service\videoImitation\VideoImitationImageLifecycle;
use app\common\service\videoImitation\VideoImitationImageRewriteService;
use app\common\service\videoImitation\ManualGenerationAssetService;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class TaskLogic extends BaseLogic
{
    /** 洗稿模式各确认步骤的超时自动确认秒数 */
    private const WASH_AUTO_CONFIRM_STALE_SECONDS = 1800;

    /** 手动纯AI素材搜索与转码的同步处理限制 */
    private const WASH_STRICT_MATERIAL_TIMEOUT_SECONDS = 120;
    private const WASH_STRICT_MATERIAL_MAX_ROUNDS = 5;
    private const WASH_STRICT_KEYWORD_MAX_ROUNDS = 3;
    private const WASH_STRICT_KEYWORD_REQUEST_TIMEOUT_SECONDS = 30;
    private const WASH_STRICT_TRANSCODE_POLL_MICROSECONDS = 1000000;

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
        $data['rewrite_mode'] = (int)($data['rewrite_mode'] ?? VideoImitationTask::REWRITE_MODE_PERSONA);
        $data['generation_type'] = (int)($data['generation_type'] ?? VideoImitationTask::GENERATION_TYPE_NONE);
        $data['generation_config_confirmed'] = (int)($data['generation_config_confirmed'] ?? 0);
        $data['rewritten_text_confirmed'] = (int)($data['rewritten_text_confirmed'] ?? 0);
        $data['wash_avatar_id'] = (int)($data['wash_avatar_id'] ?? 0);
        $data['wash_voice_id'] = (int)($data['wash_voice_id'] ?? 0);
        if ($data['rewrite_mode'] === VideoImitationTask::REWRITE_MODE_WASH
            && $data['media_type'] === VideoImitationTask::MEDIA_TYPE_VIDEO
        ) {
            if ($data['wash_avatar_id'] > 0) {
                $data['avatar_name'] = (string)DigitalHumanAnchor::where('id', $data['wash_avatar_id'])
                    ->where('user_id', $userId)
                    ->value('name');
            }
            if ($data['wash_voice_id'] > 0) {
                $data['voice_name'] = (string)HumanVoice::where('id', $data['wash_voice_id'])
                    ->where('user_id', $userId)
                    ->value('name');
            }
            $data['generation_next_step'] = (int)$task->status >= VideoImitationTask::STATUS_GENERATING
                ? ((int)$task->status === VideoImitationTask::STATUS_SUCCESS ? 'done' : 'render')
                : ManualGenerationAssetService::nextStep($task);
        } else {
            $data['generation_next_step'] = '';
        }
        // 中台快照仅供服务端重试复用，不向客户端暴露。
        unset($data['wash_third_avatar_id'], $data['wash_third_voice_id']);
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

    public static function generationOptions(int $id, int $userId)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('任务不存在');
            return false;
        }
        try {
            return ManualGenerationAssetService::options($task, $userId);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 洗稿模式：确认（并保存编辑后的）洗稿文案，仅落状态不触发合成
     * 用于详情页“确认文案”后返回重进时恢复到视频配置步
     */
    public static function confirmRewrittenText(int $id, int $userId, string $rewrittenText)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('任务不存在');
            return false;
        }
        if ((int)$task->rewrite_mode !== VideoImitationTask::REWRITE_MODE_WASH) {
            self::setError('仅洗稿模式任务支持单独确认文案');
            return false;
        }
        if (!in_array((int)$task->status, [
            VideoImitationTask::STATUS_WAIT_CONFIRM,
            VideoImitationTask::STATUS_FAIL,
        ], true)) {
            self::setError('当前任务状态不能确认文案');
            return false;
        }
        $rewrittenText = trim($rewrittenText);
        if ($rewrittenText === '') {
            $rewrittenText = trim((string)$task->rewritten_text);
        }
        $textLength = mb_strlen($rewrittenText, 'UTF-8');
        if ($textLength < 3 || $textLength > 1800) {
            self::setError('文案内容需在3-1800字之间');
            return false;
        }
        $task->save([
            'rewritten_text' => $rewrittenText,
            'rewritten_text_confirmed' => 1,
        ]);
        return ['id' => (int)$task->id, 'rewritten_text' => $rewrittenText, 'rewritten_text_confirmed' => 1];
    }

    public static function confirmGenerationOptions(
        int $id,
        int $userId,
        int $generationType,
        int $avatarId = 0,
        int $voiceId = 0,
        string $rewrittenText = ''
    ) {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('任务不存在');
            return false;
        }
        if (!in_array((int)$task->status, [
            VideoImitationTask::STATUS_WAIT_CONFIRM,
            VideoImitationTask::STATUS_FAIL,
        ], true)) {
            self::setError((int)$task->status === VideoImitationTask::STATUS_GENERATING
                ? '视频生成中，请勿重复确认'
                : '当前任务状态不能确认生成配置');
            return false;
        }
        // 确认配置时可一并提交用户编辑后的洗稿文案
        $rewrittenText = trim($rewrittenText);
        if ($rewrittenText !== '') {
            $textLength = mb_strlen($rewrittenText, 'UTF-8');
            if ($textLength < 3 || $textLength > 1800) {
                self::setError('文案内容需在3-1800字之间');
                return false;
            }
            $task->rewritten_text = $rewrittenText;
        }
        $task->rewritten_text_confirmed = 1;
        if (trim((string)$task->rewritten_text) === '') {
            self::setError('洗稿文案尚未生成，暂不能确认视频配置');
            return false;
        }

        try {
            $selection = ManualGenerationAssetService::resolveSelection(
                $task,
                $userId,
                $generationType,
                $avatarId,
                $voiceId
            );
            $task->save($selection);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        return self::generate($id, $userId, (string)$task->rewritten_text);
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

            $isWash = (int)$task->rewrite_mode === VideoImitationTask::REWRITE_MODE_WASH;
            return [
                self::stepItem($isWash ? 'wash_mode' : 'persona', $isWash ? '洗稿模式' : '关联人设', true, false),
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

        if ((int)$task->rewrite_mode === VideoImitationTask::REWRITE_MODE_WASH) {
            $typeSelected = (int)$task->generation_type !== VideoImitationTask::GENERATION_TYPE_NONE;
            $needAvatar = (int)$task->generation_type === VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN;
            $needVoice = in_array((int)$task->generation_type, [
                VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
                VideoImitationTask::GENERATION_TYPE_MATERIAL,
            ], true);
            $configConfirmed = (int)$task->generation_config_confirmed === 1;
            return [
                self::stepItem('wash_mode', '洗稿模式', true, false),
                self::stepItem('extract', '提取文案', $extractDone, $extractFailed, $remarks),
                self::stepItem('rewrite', '洗稿仿写', $hasRewrittenText, $failed && $extractDone && !$hasRewrittenText, $remarks),
                self::stepItem('generation_type', '选择视频类型', $typeSelected, false),
                self::stepItem('avatar', '选择形象', $typeSelected && (!$needAvatar || (int)$task->wash_avatar_id > 0), false),
                self::stepItem('voice', '选择音色', $typeSelected && (!$needVoice || (int)$task->wash_voice_id > 0), false),
                self::stepItem('confirm', '确认生成配置', $configConfirmed, false),
                self::stepItem('render', '云端渲染', $status === VideoImitationTask::STATUS_SUCCESS, $renderFailed, $remarks),
            ];
        }

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
        $deadlineTime = time() - VideoImitationImageLifecycle::AUTO_CONFIRM_STALE_SECONDS;

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

        $isWashMode = (int)$task->rewrite_mode === VideoImitationTask::REWRITE_MODE_WASH;
        $isWashPureAiMaterial = $isWashMode
            && (int)$task->platform_type === DeviceEnum::ACCOUNT_TYPE_DY;
        if ($isWashMode && (int)$task->generation_config_confirmed !== 1) {
            self::setError('请先完成视频类型、形象和音色选择');
            return false;
        }
        $visualMaterialSource = $isWashMode ? 1 : ($task->visual_material_source ?? 3);
        // 宽松数量与历史素材过滤仅覆盖手动洗稿，以及手动人设模式的纯AI找素材；混合/纯人设素材保持原逻辑。
        $isPersonaPureAiMaterial = (int)$task->rewrite_mode === VideoImitationTask::REWRITE_MODE_PERSONA
            && (int)$visualMaterialSource === 1;
        $useManagedPureAiMaterial = $isWashPureAiMaterial || $isPersonaPureAiMaterial;

        // 分析使用的资源
        $isMaterial = 0;
        $avatarId = '';
        $voiceId = '';
        $materials = [];

        $introduceCard = [];
        if ($isWashPureAiMaterial) {
            $isMaterial = match ((int)$task->generation_type) {
                VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN => 0,
                VideoImitationTask::GENERATION_TYPE_MATERIAL => 1,
                VideoImitationTask::GENERATION_TYPE_NEWS => 2,
                default => -1,
            };
            if ($isMaterial < 0) {
                self::setError('请先选择视频类型');
                return false;
            }
            $avatarId = trim((string)$task->wash_third_avatar_id);
            $voiceId = trim((string)$task->wash_third_voice_id);
            $task->avatar_id = (int)$task->wash_avatar_id;
            $task->voice_id = (int)$task->wash_voice_id;
        } else {
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
        }

        // 检查当前人设是否满足生成视频条件
        if ($isMaterial == 0 && (empty($avatarId) || empty($voiceId))) {
            self::setError($isWashMode
                ? '所选数字人形象或音色缺少可复用的中台ID，请重新选择'
                : '当前AI人设未绑定可用的数字人形象和音色，无法生成视频');
            return false;
        }
        if ($isMaterial == 1 && empty($voiceId)) {
            self::setError($isWashMode
                ? '所选音色缺少可复用的中台ID，请重新选择'
                : '当前AI人设未绑定可用的音色，无法生成视频');
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
        if ($isWashMode) {
            [$targetVideoCount, $targetImageCount] = self::resolveWashStrictMaterialTargets(
                (int)$task->id,
                $isMaterial
            );
        } else {
            // 人设模式保持原随机数量逻辑不变。
            $rule = $rules[$isMaterial] ?? $rules[1];
            $targetVideoCount = rand($rule['v_min'], $rule['v_max']);
            $targetImageCount = rand($rule['i_min'], $rule['i_max']);
        }

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
        $isMinimaxVoice = $isWashMode
            ? (string)$task->wash_voice_provider === ManualGenerationAssetService::PROVIDER_MINIMAX
            : ShanjianVideoSettingLogic::isMinimaxVoiceId((string)$voiceId, (int)$userId);
        if ($isMaterial != 2 && !empty($voiceId) && $isMinimaxVoice) {
            $minimaxAudioUrl = self::buildMinimaxImitationAudio((string)$voiceId, $rewrittenText, $userId);
            if ($minimaxAudioUrl === '') {
                $ttsError = self::getError() ?: 'MiniMax音频合成失败';
                if ($isWashMode) {
                    self::setError($ttsError);
                    return false;
                }
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
                $task->id,
                (int)$task->platform_type,
                $useManagedPureAiMaterial,
                $useManagedPureAiMaterial,
                false
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
                // 洗稿任务必须由用户明确选择；未确认的保持待选择，不随机、不失败。
                // 注意必须在 SQL 层排除,否则这些永不推进的行会长期占满 limit(50) 窗口,饿死其它待自动下发任务
                ->where(function ($q) {
                    $q->where('rewrite_mode', '<>', VideoImitationTask::REWRITE_MODE_WASH)
                        ->whereOr('generation_config_confirmed', '=', 1);
                })
                ->order('update_time', 'asc')
                ->limit(50) // 每次定时脚本处理上限防堆积
                ->select();

            foreach ($pendingTasks as $task) {
                // 如果用户在中途丢失了 persona_id 或原复刻文案丢失，则自动失败
                if ((int)$task->rewrite_mode !== VideoImitationTask::REWRITE_MODE_WASH
                    && (empty($task->persona_id) || empty($task->rewritten_text))
                ) {
                    $task->status = 4;
                    $task->remarks = '自动下发：缺少必要的AI人设ID或复刻文案';
                    $task->save();
                    continue;
                }
                if (empty($task->rewritten_text)) {
                    // 已确认的异常洗稿数据也不自动失败，等待人工恢复文案。
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
     * 洗稿模式超时兜底：
     * 1) 文案生成 30 分钟未确认 → 自动确认文案，进入视频类型/形象/音色选择
     * 2) 文案确认后再 30 分钟未选配置 → 自动选择默认类型+随机形象+随机音色并下发生成
     * 仅处理洗稿视频任务，人设模式/图文模式不受影响
     */
    public static function autoConfirmWashTasksCron(int $limit = 20): void
    {
        $limit = max(1, min(100, $limit));
        $deadlineTime = time() - self::WASH_AUTO_CONFIRM_STALE_SECONDS;

        self::autoConfirmExpiredWashTexts($limit, $deadlineTime);
        self::autoConfirmExpiredWashConfigs($limit, $deadlineTime);
    }

    /**
     * 洗稿阶段一：文案超时未确认 → 自动确认（仅落状态，不触发合成）
     */
    private static function autoConfirmExpiredWashTexts(int $limit, int $deadlineTime): void
    {
        try {
            $tasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_VIDEO)
                ->where('rewrite_mode', VideoImitationTask::REWRITE_MODE_WASH)
                ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
                ->where('rewritten_text_confirmed', 0)
                ->where('task_delete', 0)
                ->where('update_time', '<', $deadlineTime)
                ->order('id', 'asc')
                ->limit($limit)
                ->select();
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('洗稿文案超时自动确认扫描失败：' . $e->getMessage());
            return;
        }

        foreach ($tasks as $task) {
            $taskId = (int)$task->id;
            if (trim((string)$task->rewritten_text) === '') {
                Log::channel('shanjian')->write("洗稿文案超时自动确认跳过 task_id={$taskId} reason=文案为空");
                continue;
            }
            try {
                // 条件更新占位：用户已抢先手动确认则跳过
                $affected = VideoImitationTask::where('id', $taskId)
                    ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
                    ->where('rewritten_text_confirmed', 0)
                    ->update([
                        'rewritten_text_confirmed' => 1,
                        'remarks' => '超时未确认文案，系统已自动确认，请选择视频类型、形象和音色',
                        'update_time' => time(),
                    ]);
                if ($affected > 0) {
                    Log::channel('shanjian')->write("洗稿文案超时自动确认成功 task_id={$taskId}");
                }
            } catch (\Throwable $e) {
                Log::channel('shanjian')->write("洗稿文案超时自动确认异常 task_id={$taskId} error=" . $e->getMessage());
            }
        }
    }

    /**
     * 洗稿阶段二：配置超时未选择 → 自动选择并下发生成
     */
    private static function autoConfirmExpiredWashConfigs(int $limit, int $deadlineTime): void
    {
        try {
            $tasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_VIDEO)
                ->where('rewrite_mode', VideoImitationTask::REWRITE_MODE_WASH)
                ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
                ->where('rewritten_text_confirmed', 1)
                ->where('generation_config_confirmed', 0)
                ->where('task_delete', 0)
                ->where('update_time', '<', $deadlineTime)
                ->order('id', 'asc')
                ->limit($limit)
                ->select();
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('洗稿配置超时自动确认扫描失败：' . $e->getMessage());
            return;
        }

        foreach ($tasks as $task) {
            $taskId = (int)$task->id;
            $userId = (int)$task->user_id;
            try {
                $selection = self::resolveWashAutoSelection($task, $userId);
                if ($selection === null) {
                    continue;
                }

                // 条件更新占位：用户已抢先手动确认则放弃自动下发
                $affected = VideoImitationTask::where('id', $taskId)
                    ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
                    ->where('generation_config_confirmed', 0)
                    ->update($selection + [
                        'remarks' => '超时未选择视频配置，系统已自动选择并生成',
                        'update_time' => time(),
                    ]);
                if ($affected <= 0) {
                    Log::channel('shanjian')->write("洗稿配置超时自动确认跳过 task_id={$taskId} reason=用户已确认");
                    continue;
                }

                $result = self::generate($taskId, $userId, (string)$task->rewritten_text);
                if ($result === false) {
                    VideoImitationTask::where('id', $taskId)->update([
                        'status' => VideoImitationTask::STATUS_FAIL,
                        'remarks' => '超时自动生成失败：' . self::getError(),
                        'update_time' => time(),
                    ]);
                }
                Log::channel('shanjian')->write(sprintf(
                    '洗稿配置超时自动确认下发 task_id=%d generation_type=%d ok=%s error=%s',
                    $taskId,
                    (int)$selection['generation_type'],
                    $result === false ? '0' : '1',
                    $result === false ? (string)self::getError() : ''
                ));
            } catch (\Throwable $e) {
                Log::channel('shanjian')->write("洗稿配置超时自动确认异常 task_id={$taskId} error=" . $e->getMessage());
            }
        }
    }

    /**
     * 洗稿超时自动选择：随机配置经 resolveSelection 校验，冻结等资源问题按 数字人→素材→新闻体 逐级降级；
     * 全部失败则置任务失败，返回 null
     */
    private static function resolveWashAutoSelection(VideoImitationTask $task, int $userId): ?array
    {
        $picked = ManualGenerationAssetService::randomSelection($task, $userId);
        $candidates = match ($picked['generation_type']) {
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN => [
                $picked,
                [
                    'generation_type' => VideoImitationTask::GENERATION_TYPE_MATERIAL,
                    'avatar_id' => 0,
                    'voice_id' => $picked['voice_id'],
                ],
                [
                    'generation_type' => VideoImitationTask::GENERATION_TYPE_NEWS,
                    'avatar_id' => 0,
                    'voice_id' => 0,
                ],
            ],
            VideoImitationTask::GENERATION_TYPE_MATERIAL => [
                $picked,
                [
                    'generation_type' => VideoImitationTask::GENERATION_TYPE_NEWS,
                    'avatar_id' => 0,
                    'voice_id' => 0,
                ],
            ],
            default => [$picked],
        };

        $lastError = '';
        foreach ($candidates as $candidate) {
            try {
                return ManualGenerationAssetService::resolveSelection(
                    $task,
                    $userId,
                    $candidate['generation_type'],
                    $candidate['avatar_id'],
                    $candidate['voice_id']
                );
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        VideoImitationTask::where('id', (int)$task->id)
            ->where('status', VideoImitationTask::STATUS_WAIT_CONFIRM)
            ->update([
                'status' => VideoImitationTask::STATUS_FAIL,
                'remarks' => '超时自动确认失败：' . $lastError,
                'update_time' => time(),
            ]);
        Log::channel('shanjian')->write(sprintf(
            '洗稿配置超时自动确认失败 task_id=%d error=%s',
            (int)$task->id,
            $lastError
        ));
        return null;
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

    /**
     * 不落库的稳定目标数：同一任务、同一成片类型重试时结果保持一致。
     *
     * @return array{0:int,1:int} [视频数, 图片数]
     */
    private static function resolveWashStrictMaterialTargets(int $taskId, int $isMaterial): array
    {
        $imageCount = self::stableWashMaterialCount($taskId, $isMaterial, 'image', 2, 3);
        $videoCount = match ($isMaterial) {
            0 => self::stableWashMaterialCount($taskId, $isMaterial, 'video', 2, 3),
            2 => 5,
            default => 8,
        };

        return [$videoCount, $imageCount];
    }

    private static function stableWashMaterialCount(
        int $taskId,
        int $isMaterial,
        string $materialType,
        int $min,
        int $max
    ): int {
        if ($max <= $min) {
            return $min;
        }
        $unsignedHash = (int)sprintf('%u', crc32($taskId . ':' . $isMaterial . ':' . $materialType));
        return $min + ($unsignedHash % ($max - $min + 1));
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
        int $taskId,
        int $platformType = 0,
        bool $strictCount = false,
        bool $filterExistingLibrary = false,
        bool $reuseExistingLibrary = false
    ): array
    {
        if ($strictCount) {
            if ($sourceMode !== 1) {
                throw new \RuntimeException('手动纯AI素材流程仅支持AI找素材');
            }
            return self::grabStrictWashAiMaterials(
                $text,
                $targetVideoCount,
                $targetImageCount,
                $userId,
                $extractPrice,
                $grabVideoPrice,
                $grabImagePrice,
                $taskId,
                $platformType,
                $filterExistingLibrary,
                $reuseExistingLibrary
            );
        }

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
                list($aiV, $aiI) = self::grabAiMaterials($text, $vGap, $iGap, $userId, $personaId, $extractPrice, $grabVideoPrice, $grabImagePrice, $taskId, $platformType);
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

    /**
     * 手动纯AI素材专用：同步补抓并等待转码，目标数量仅作为上限，有可用素材即可返回。
     *
     * @return array<int, array{fileUrl:string,type:string}>
     */
    private static function grabStrictWashAiMaterials(
        string $text,
        int $targetVideoCount,
        int $targetImageCount,
        int $userId,
        float $extractPrice,
        float $grabVideoPrice,
        float $grabImagePrice,
        int $taskId,
        int $platformType,
        bool $filterExistingLibrary = false,
        bool $reuseExistingLibrary = false
    ): array {
        $deadline = microtime(true) + self::WASH_STRICT_MATERIAL_TIMEOUT_SECONDS;
        $candidates = ['video' => [], 'image' => []];
        $grabCalls = [];
        $seenRemoteUrls = [];

        try {
            $keywords = self::extractWashStrictKeywords(
                $text,
                $deadline
            );
            // 关键词提取成功后再结算，避免超时或空结果仍扣除用户算力。
            if ($extractPrice > 0 && empty(self::$testHooks['skipUserTokens'])) {
                Db::startTrans();
                try {
                    User::userTokensChange($userId, $extractPrice);
                    $extra = [
                        '扣费项目' => '爆款仿写-仿写文案匹配关键词',
                        '算力单价' => $extractPrice,
                        '实际消耗算力' => $extractPrice,
                    ];
                    AccountLogLogic::recordUserTokensLog(
                        true,
                        $userId,
                        AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS,
                        $extractPrice,
                        (string)$taskId,
                        $extra
                    );
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    throw $e;
                }
            }
            Log::channel('shanjian')->write('[手动纯AI找素材关键词] ' . json_encode([
                'task_id' => $taskId,
                'keyword_count' => count($keywords),
                'keywords' => $keywords,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $keywordCursor = ['video' => 0, 'image' => 1];
            $selection = self::selectWashStrictMaterials(
                $candidates,
                $targetVideoCount,
                $targetImageCount
            );
            $searchStoppedByDeadline = false;

            for ($round = 1; $round <= self::WASH_STRICT_MATERIAL_MAX_ROUNDS; $round++) {
                if (microtime(true) >= $deadline) {
                    $searchStoppedByDeadline = true;
                    break;
                }
                $gaps = [
                    'video' => $targetVideoCount - count($selection['videos']),
                    'image' => $targetImageCount - count($selection['images']),
                ];
                if ($gaps['video'] <= 0 && $gaps['image'] <= 0) {
                    break;
                }

                foreach (['video', 'image'] as $type) {
                    for ($index = 0; $index < $gaps[$type]; $index++) {
                        if (microtime(true) >= $deadline) {
                            $searchStoppedByDeadline = true;
                            break 2;
                        }
                        $keyword = $keywords[$keywordCursor[$type] % count($keywords)];
                        $keywordCursor[$type]++;
                        $grabTaskId = (string)generate_unique_task_id();
                        $grabCalls[$grabTaskId] = [
                            'type' => $type,
                            'refunded' => false,
                        ];

                        try {
                            $response = self::requestWashStrictTool(
                                $type === 'video' ? '/api/media/grab/video' : '/api/media/grab/image',
                                [
                                    'orientation' => 'portrait',
                                    'task_id' => $grabTaskId,
                                    'user_id' => $userId,
                                    'now' => time(),
                                    'keywords' => $keyword,
                                    'searchTerm' => $keyword,
                                ],
                                $deadline,
                                15
                            );
                            $list = self::resolveWashStrictGrabList($response);
                            $candidate = self::dispatchWashStrictCandidate(
                                $list,
                                $type,
                                $grabTaskId,
                                $seenRemoteUrls,
                                $userId,
                                $filterExistingLibrary,
                                $reuseExistingLibrary
                            );
                            if ($candidate !== null) {
                                $candidates[$type][] = $candidate;
                            } else {
                                self::refundWashStrictGrabCall(
                                    $grabTaskId,
                                    $type,
                                    $userId,
                                    $grabCalls,
                                    $deadline,
                                    '未找到可投递转码的候选素材'
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::channel('shanjian')->write(sprintf(
                                '[手动纯AI找素材] 抓取失败 task_id=%d grab_task_id=%s type=%s round=%d err=%s',
                                $taskId,
                                $grabTaskId,
                                $type,
                                $round,
                                $e->getMessage()
                            ));
                            self::refundWashStrictGrabCall(
                                $grabTaskId,
                                $type,
                                $userId,
                                $grabCalls,
                                $deadline,
                                '抓取或转码投递失败'
                            );
                            if (microtime(true) >= $deadline) {
                                $searchStoppedByDeadline = true;
                                break 2;
                            }
                        }
                    }
                }

                if (!$searchStoppedByDeadline) {
                    self::waitWashStrictTranscodes(
                        $candidates,
                        $grabCalls,
                        $userId,
                        $deadline
                    );
                    $searchStoppedByDeadline = microtime(true) >= $deadline;
                }
                $selection = self::selectWashStrictMaterials(
                    $candidates,
                    $targetVideoCount,
                    $targetImageCount
                );

                Log::channel('shanjian')->write('[手动纯AI找素材] ' . json_encode([
                    'task_id' => $taskId,
                    'round' => $round,
                    'target_video_count' => $targetVideoCount,
                    'target_image_count' => $targetImageCount,
                    'ready_video_count' => count($selection['videos']),
                    'ready_image_count' => count($selection['images']),
                    'total_duration' => $selection['total_duration'],
                    'deadline_reached' => $searchStoppedByDeadline ? 1 : 0,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                if ($searchStoppedByDeadline) {
                    break;
                }
            }

            $selected = array_merge($selection['videos'], $selection['images']);
            // 复用现有时长门禁；目标数量仅为上限，不再要求视频、图片分别达标。
            $selected = ShanjianVideoSettingLogic::trimMaterialsByDuration($selected, 300, 59);
            if ($selected === []) {
                throw new \RuntimeException('未找到与文案关键词匹配的可用素材');
            }
            $selectedVideos = array_values(array_filter(
                $selected,
                static fn(array $item): bool => ($item['type'] ?? '') === 'video'
            ));
            $selectedImages = array_values(array_filter(
                $selected,
                static fn(array $item): bool => ($item['type'] ?? '') === 'image'
            ));

            $selectedGrabTaskIds = array_fill_keys(array_map(
                static fn(array $item): string => (string)$item['grab_task_id'],
                $selected
            ), true);
            self::refundUnusedWashStrictGrabCalls(
                $grabCalls,
                $selectedGrabTaskIds,
                $userId,
                $deadline,
                '素材未进入最终提交集合'
            );

            $newVideoCount = count(array_filter(
                $selected,
                static fn(array $item): bool => ($item['type'] ?? '') === 'video' && empty($item['reused'])
            ));
            $newImageCount = count(array_filter(
                $selected,
                static fn(array $item): bool => ($item['type'] ?? '') === 'image' && empty($item['reused'])
            ));
            self::settleWashStrictMaterialCost(
                $userId,
                $taskId,
                $newVideoCount,
                $newImageCount,
                $grabVideoPrice,
                $grabImagePrice
            );
            self::persistGrabbedAiMaterialsSafe(
                $userId,
                $platformType,
                $selectedVideos,
                $selectedImages
            );

            return self::formatMaterialResult($selected);
        } catch (\Throwable $e) {
            self::refundUnusedWashStrictGrabCalls(
                $grabCalls,
                [],
                $userId,
                $deadline,
                '手动纯AI找素材失败或超时'
            );
            if ($e instanceof \Exception) {
                throw $e;
            }
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * 最多请求三轮关键词；只使用最终文案提取结果，不追加人设行业词或固定兜底词。
     *
     * @return array<int, string>
     */
    private static function extractWashStrictKeywords(
        string $text,
        float $deadline
    ): array
    {
        $keywords = [];
        $attempts = 0;
        $lastError = '';
        for ($round = 1; $round <= self::WASH_STRICT_KEYWORD_MAX_ROUNDS; $round++) {
            // 一个关键词可搜索多个素材，取得任意有效关键词后即可进入素材搜索。
            if ($keywords !== []) {
                break;
            }
            if (microtime(true) >= $deadline) {
                break;
            }
            $attempts++;
            try {
                $response = self::requestWashStrictTool(
                    '/api/coze/extractkeywords',
                    ['keywords' => $text],
                    $deadline,
                    self::WASH_STRICT_KEYWORD_REQUEST_TIMEOUT_SECONDS
                );
                if ((int)($response['code'] ?? 0) !== 10000) {
                    throw new \RuntimeException(trim((string)($response['message'] ?? '关键词提取接口返回失败')));
                }
                $content = $response['data']['content'] ?? [];
                foreach (self::normalizeWashStrictKeywords($content) as $keyword) {
                    $key = mb_strtolower($keyword, 'UTF-8');
                    $keywords[$key] = $keyword;
                }
                if ($keywords === []) {
                    $lastError = '关键词提取结果为空';
                }
            } catch (\Throwable $e) {
                $lastError = trim($e->getMessage()) ?: '关键词提取请求失败';
                Log::channel('shanjian')->write(sprintf(
                    '[手动纯AI找素材] 第%d轮关键词提取失败：%s',
                    $round,
                    $lastError
                ));
            }
        }

        if ($keywords === []) {
            $message = '未从文案中提取到有效素材关键词';
            if ($lastError !== '') {
                $message .= '：' . $lastError;
            }
            throw new \RuntimeException($message);
        }

        Log::channel('shanjian')->write('[手动纯AI关键词提取] ' . json_encode([
            'attempts' => $attempts,
            'keyword_count' => count($keywords),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return array_values($keywords);
    }

    /**
     * @param mixed $content
     * @return array<int, string>
     */
    private static function normalizeWashStrictKeywords($content): array
    {
        $values = is_array($content) ? $content : [$content];
        $keywords = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $value = $value['keyword'] ?? $value['name'] ?? '';
            }
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            $parts = preg_split('/[\s,，、;；\r\n]+/u', trim((string)$value)) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $keywords[] = mb_substr($part, 0, 50, 'UTF-8');
                }
            }
        }
        return $keywords;
    }

    /**
     * 严格分支独立设置中台请求超时，不改变 ToolsService 共享方法的默认行为。
     */
    private static function requestWashStrictTool(
        string $endpoint,
        array $request,
        float $deadline,
        int $maxRequestSeconds
    ): array {
        self::assertWashStrictDeadline($deadline);
        if (array_key_exists('requestWashStrictTool', self::$testHooks)
            && is_callable(self::$testHooks['requestWashStrictTool'])
        ) {
            $response = (self::$testHooks['requestWashStrictTool'])($endpoint, $request);
            return is_array($response) ? $response : [];
        }
        $remaining = max(1, (int)ceil($deadline - microtime(true)));
        $requestTimeout = max(1, min($maxRequestSeconds, $remaining));
        $connectTimeout = max(1, min(5, $requestTimeout));
        $service = app(ToolsService::class)
            ->setApiUrl($endpoint)
            ->setRequest($request)
            ->setMethod('POST')
            ->setTimeout($connectTimeout, $requestTimeout)
            ->sendWithoutThrow();

        return is_array($service->response) ? $service->response : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function resolveWashStrictGrabList(array $response): array
    {
        if ((int)($response['code'] ?? 0) !== 10000) {
            return [];
        }
        $data = $response['data'] ?? [];
        if (isset($data['list']) && is_array($data['list'])) {
            $data = $data['list'];
        }
        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    /**
     * 单次搜索会继续尝试后续候选，直到找到可投递转码的一条素材。
     *
     * @param array<int, array<string, mixed>> $list
     * @param array<string, bool> $seenRemoteUrls
     * @return array<string, mixed>|null
     */
    private static function dispatchWashStrictCandidate(
        array $list,
        string $type,
        string $grabTaskId,
        array &$seenRemoteUrls,
        int $userId = 0,
        bool $filterExistingLibrary = false,
        bool $reuseExistingLibrary = false
    ): ?array {
        foreach ($list as $item) {
            $link = trim((string)($item['link'] ?? ''));
            if ($link === '' || isset($seenRemoteUrls[$link])) {
                continue;
            }
            $seenRemoteUrls[$link] = true;
            if (!FileService::isAllowedGrabMaterialUrl($link, $type)) {
                continue;
            }
            if (
                $filterExistingLibrary
                && self::isExistingVideoImitationMaterial($userId, $link)
            ) {
                self::logSkippedExistingStrictMaterial($grabTaskId, $type, $link, 'remote_url');
                continue;
            }
            if ($reuseExistingLibrary && !$filterExistingLibrary) {
                $reused = self::buildReusedVideoImitationCandidate($userId, $type, $link, $item, $grabTaskId);
                if ($reused !== null) {
                    return $reused;
                }
            }

            $duration = $type === 'video' ? (float)($item['duration'] ?? 0) : 2.0;
            if ($type === 'video' && ($duration <= 0 || $duration > 59)) {
                continue;
            }

            try {
                $transRes = self::transcodeGrabbedSource($link, $type);
                $fileId = (int)($transRes['id'] ?? 0);
                $storedUrl = (string)(($transRes['oss_uri'] ?? '') ?: ($transRes['url'] ?? ''));
                if ($fileId <= 0 || $storedUrl === '') {
                    continue;
                }
                $fileUrl = FileService::getFileUrl($storedUrl);
                if (
                    $filterExistingLibrary
                    && self::isExistingVideoImitationMaterial($userId, '', $fileUrl)
                ) {
                    self::logSkippedExistingStrictMaterial($grabTaskId, $type, $link, 'content');
                    continue;
                }
                return [
                    'file_id' => $fileId,
                    'remote_url' => $link,
                    'fileUrl' => $fileUrl,
                    'type' => $type,
                    'duration' => $duration,
                    'size' => self::resolveGrabbedMaterialSize($transRes),
                    'grab_task_id' => $grabTaskId,
                    'transcode_status' => 'pending',
                ];
            } catch (\Throwable $e) {
                Log::channel('shanjian')->write(sprintf(
                    '[手动纯AI找素材] 候选转码投递失败 grab_task_id=%s type=%s url=%s err=%s',
                    $grabTaskId,
                    $type,
                    $link,
                    $e->getMessage()
                ));
            }
        }

        return null;
    }

    /**
     * 与手动爆款复刻素材入库使用同一用户、同一来源的查重口径。
     */
    private static function isExistingVideoImitationMaterial(
        int $userId,
        string $remoteUrl = '',
        string $fileUrl = ''
    ): bool {
        if ($userId <= 0) {
            return false;
        }
        if (
            trim($remoteUrl) !== ''
            && SvMediaMaterial::findExistingIdByRemoteUrl(
                $userId,
                SvMediaMaterial::SOURCE_VIDEO_IMITATION,
                $remoteUrl
            ) > 0
        ) {
            return true;
        }
        return trim($fileUrl) !== ''
            && SvMediaMaterial::findExistingId(
                $userId,
                SvMediaMaterial::SOURCE_VIDEO_IMITATION,
                $fileUrl
            ) > 0;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private static function buildReusedVideoImitationCandidate(
        int $userId,
        string $type,
        string $link,
        array $item,
        string $grabTaskId
    ): ?array {
        $row = SvMediaMaterial::findExistingVideoImitationRow($userId, $link);
        if ($row === [] || (int)($row['id'] ?? 0) <= 0) {
            return null;
        }
        $content = trim((string)($row['content'] ?? ''));
        if ($content === '') {
            return null;
        }
        $duration = $type === 'video'
            ? (float)((int)($row['duration'] ?? 0) > 0 ? $row['duration'] : ($item['duration'] ?? 0))
            : 2.0;
        if ($type === 'video' && ($duration <= 0 || $duration > 59)) {
            return null;
        }
        try {
            Log::channel('shanjian')->write(sprintf(
                '手动洗稿找素材复用已有素材 grab_task_id=%s type=%s material_id=%d remote_url=%s',
                $grabTaskId,
                $type,
                (int)$row['id'],
                mb_substr($link, 0, 180, 'UTF-8')
            ));
        } catch (\Throwable $e) {
        }
        return [
            'file_id' => 0,
            'id' => (int)$row['id'],
            'remote_url' => $link,
            'fileUrl' => FileService::getFileUrl($content),
            'type' => $type,
            'duration' => $duration,
            'size' => (int)($row['size'] ?? 0),
            'material_store' => 'sv_media',
            'grab_task_id' => $grabTaskId,
            'transcode_status' => 'ready',
            'reused' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function transcodeGrabbedSource(string $link, string $type): array
    {
        if (array_key_exists('transcodeBySource', self::$testHooks)) {
            $hook = self::$testHooks['transcodeBySource'];
            if (is_callable($hook)) {
                $result = $hook($link, $type);
                return is_array($result) ? $result : [];
            }
            return is_array($hook) ? $hook : [];
        }
        return UploadService::transcodeBySource($link, $type, 0, 0);
    }

    /**
     * @param array<string, mixed> $grabReq
     * @return array<string, mixed>
     */
    private static function requestGrabList(string $type, array $grabReq): array
    {
        $hookName = $type === 'image' ? 'grabImage' : 'grabVideo';
        if (array_key_exists($hookName, self::$testHooks) && is_callable(self::$testHooks[$hookName])) {
            $response = (self::$testHooks[$hookName])($grabReq);
            return is_array($response) ? $response : [];
        }
        $res = $type === 'image'
            ? \app\common\service\ToolsService::Grab()->image($grabReq)
            : \app\common\service\ToolsService::Grab()->video($grabReq);
        return is_array($res) ? $res : [];
    }

    /**
     * @param array<int, array<string, mixed>> $materials
     * @return array<int, array<string, mixed>>
     */
    private static function keepReusedOrReadyMaterials(array $materials): array
    {
        $reused = [];
        $others = [];
        foreach ($materials as $item) {
            if (!empty($item['reused'])) {
                $reused[] = $item;
            } else {
                $others[] = $item;
            }
        }
        return array_merge($reused, self::filterMaterialsReadyForSubmit($others));
    }

    private static function logSkippedExistingStrictMaterial(
        string $grabTaskId,
        string $type,
        string $remoteUrl,
        string $matchedBy
    ): void {
        try {
            Log::channel('shanjian')->write(sprintf(
                '[手动纯AI找素材] 跳过素材库重复候选 grab_task_id=%s type=%s matched_by=%s remote_url=%s',
                $grabTaskId,
                $type,
                $matchedBy,
                mb_substr($remoteUrl, 0, 180, 'UTF-8')
            ));
        } catch (\Throwable $e) {
        }
    }

    /**
     * 等待本轮素材进入可用或失败终态；到达截止时间时保留已就绪素材，转码中素材不计入结果。
     *
     * @param array{video:array<int,array<string,mixed>>,image:array<int,array<string,mixed>>} $candidates
     * @param array<string, array{type:string,refunded:bool}> $grabCalls
     */
    private static function waitWashStrictTranscodes(
        array &$candidates,
        array &$grabCalls,
        int $userId,
        float $deadline
    ): void {
        while (true) {
            if (microtime(true) >= $deadline) {
                return;
            }
            $pendingIds = [];
            foreach (['video', 'image'] as $type) {
                foreach ($candidates[$type] as $candidate) {
                    if (($candidate['transcode_status'] ?? '') === 'pending') {
                        $pendingIds[] = (int)$candidate['file_id'];
                    }
                }
            }
            if (empty($pendingIds)) {
                return;
            }

            $statusRows = File::whereIn('id', array_values(array_unique($pendingIds)))
                ->column('transcode_status', 'id');
            $hasPending = false;
            foreach (['video', 'image'] as $type) {
                foreach ($candidates[$type] as &$candidate) {
                    if (($candidate['transcode_status'] ?? '') !== 'pending') {
                        continue;
                    }
                    $fileId = (int)$candidate['file_id'];
                    $rawStatus = $statusRows[$fileId] ?? null;
                    $status = is_numeric($rawStatus) ? (int)$rawStatus : 4;
                    if (in_array($status, [0, 3], true)) {
                        $candidate['transcode_status'] = 'ready';
                    } elseif (in_array($status, [1, 2], true)) {
                        $hasPending = true;
                    } else {
                        $candidate['transcode_status'] = 'failed';
                        self::refundWashStrictGrabCall(
                            (string)$candidate['grab_task_id'],
                            $type,
                            $userId,
                            $grabCalls,
                            $deadline,
                            '素材转码失败'
                        );
                    }
                }
                unset($candidate);
            }

            if (!$hasPending) {
                return;
            }
            $remainingMicroseconds = (int)max(0, ($deadline - microtime(true)) * 1000000);
            if ($remainingMicroseconds <= 0) {
                return;
            }
            usleep(min(self::WASH_STRICT_TRANSCODE_POLL_MICROSECONDS, $remainingMicroseconds));
        }
    }

    /**
     * 按类型上限选择：图片固定2秒，视频优先较短素材，并为目标图片预留总时长。
     *
     * @param array{video:array<int,array<string,mixed>>,image:array<int,array<string,mixed>>} $candidates
     * @return array{videos:array<int,array<string,mixed>>,images:array<int,array<string,mixed>>,total_duration:float}
     */
    private static function selectWashStrictMaterials(
        array $candidates,
        int $targetVideoCount,
        int $targetImageCount
    ): array {
        $images = array_values(array_filter(
            $candidates['image'] ?? [],
            static fn(array $item): bool => ($item['transcode_status'] ?? '') === 'ready'
                && trim((string)($item['fileUrl'] ?? '')) !== ''
        ));
        $images = array_slice($images, 0, $targetImageCount);

        $videos = array_values(array_filter(
            $candidates['video'] ?? [],
            static function (array $item): bool {
                $duration = (float)($item['duration'] ?? 0);
                return ($item['transcode_status'] ?? '') === 'ready'
                    && trim((string)($item['fileUrl'] ?? '')) !== ''
                    && $duration > 0
                    && $duration <= 59;
            }
        ));
        usort($videos, static function (array $left, array $right): int {
            $durationCompare = (float)$left['duration'] <=> (float)$right['duration'];
            return $durationCompare !== 0
                ? $durationCompare
                : ((int)($left['file_id'] ?? 0) <=> (int)($right['file_id'] ?? 0));
        });

        $selectedVideos = [];
        $videoDuration = 0.0;
        $videoBudget = 300 - ($targetImageCount * 2);
        foreach ($videos as $video) {
            if (count($selectedVideos) >= $targetVideoCount) {
                break;
            }
            $duration = (float)$video['duration'];
            if (($videoDuration + $duration) > $videoBudget) {
                continue;
            }
            $selectedVideos[] = $video;
            $videoDuration += $duration;
        }

        return [
            'videos' => $selectedVideos,
            'images' => $images,
            'total_duration' => $videoDuration + (count($images) * 2),
        ];
    }

    /**
     * 最终只按实际提交且转码成功的精确数量结算素材费用。
     */
    private static function settleWashStrictMaterialCost(
        int $userId,
        int $taskId,
        int $videoCount,
        int $imageCount,
        float $grabVideoPrice,
        float $grabImagePrice
    ): void {
        $videoCost = $videoCount * $grabVideoPrice;
        $imageCost = $imageCount * $grabImagePrice;
        if (array_key_exists('settleCost', self::$testHooks) && is_callable(self::$testHooks['settleCost'])) {
            (self::$testHooks['settleCost'])($userId, $taskId, $videoCount, $imageCount, $videoCost, $imageCost);
            return;
        }
        Db::startTrans();
        try {
            if ($videoCost > 0) {
                User::userTokensChange($userId, $videoCost);
                AccountLogLogic::recordUserTokensLog(
                    true,
                    $userId,
                    AccountLogEnum::TOKENS_DEC_GRAB_VIDEO,
                    $videoCost,
                    (string)$taskId,
                    [
                        '扣费项目' => '爆款仿写-AI自动找视频素材扣费',
                        '算力单价' => $grabVideoPrice,
                        '视频数量' => $videoCount,
                        '实际消耗算力' => $videoCost,
                    ]
                );
            }
            if ($imageCost > 0) {
                User::userTokensChange($userId, $imageCost);
                AccountLogLogic::recordUserTokensLog(
                    true,
                    $userId,
                    AccountLogEnum::TOKENS_DEC_GRAB_IMAGE,
                    $imageCost,
                    (string)$taskId,
                    [
                        '扣费项目' => '爆款仿写-AI自动找图片素材扣费',
                        '算力单价' => $grabImagePrice,
                        '图片数量' => $imageCount,
                        '实际消耗算力' => $imageCost,
                    ]
                );
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * @param array<string, array{type:string,refunded:bool}> $grabCalls
     * @param array<string, bool> $selectedGrabTaskIds
     */
    private static function refundUnusedWashStrictGrabCalls(
        array &$grabCalls,
        array $selectedGrabTaskIds,
        int $userId,
        float $deadline,
        string $reason
    ): void {
        foreach ($grabCalls as $grabTaskId => $call) {
            if (isset($selectedGrabTaskIds[$grabTaskId]) || !empty($call['refunded'])) {
                continue;
            }
            self::refundWashStrictGrabCall(
                (string)$grabTaskId,
                (string)$call['type'],
                $userId,
                $grabCalls,
                $deadline,
                $reason
            );
        }
    }

    /**
     * 中台抓取退费为尽力操作，且必须服从本次120秒共享截止时间。
     *
     * @param array<string, array{type:string,refunded:bool}> $grabCalls
     */
    private static function refundWashStrictGrabCall(
        string $grabTaskId,
        string $type,
        int $userId,
        array &$grabCalls,
        float $deadline,
        string $reason
    ): void {
        if (!isset($grabCalls[$grabTaskId]) || !empty($grabCalls[$grabTaskId]['refunded'])) {
            return;
        }
        // 标记已尝试，避免转码轮询与最终清理重复发起退费。
        $grabCalls[$grabTaskId]['refunded'] = true;
        if (array_key_exists('refundWashStrictGrabCall', self::$testHooks)
            && is_callable(self::$testHooks['refundWashStrictGrabCall'])
        ) {
            (self::$testHooks['refundWashStrictGrabCall'])($grabTaskId, $type, $userId, $reason);
            return;
        }
        if (microtime(true) >= $deadline) {
            Log::channel('shanjian')->write(sprintf(
                '[手动纯AI找素材] 已到截止时间，退费请求未发送 grab_task_id=%s type=%s reason=%s',
                $grabTaskId,
                $type,
                $reason
            ));
            return;
        }

        try {
            $response = self::requestWashStrictTool(
                $type === 'image' ? '/api/media/grab/image' : '/api/media/grab/video',
                [
                    'is_return' => 1,
                    'task_id' => $grabTaskId,
                    'user_id' => $userId,
                ],
                $deadline,
                3
            );
            if ((int)($response['code'] ?? 0) !== 10000) {
                Log::channel('shanjian')->write(sprintf(
                    '[手动纯AI找素材] 中台退费失败 grab_task_id=%s type=%s reason=%s response=%s',
                    $grabTaskId,
                    $type,
                    $reason,
                    json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ));
            }
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write(sprintf(
                '[手动纯AI找素材] 中台退费异常 grab_task_id=%s type=%s reason=%s err=%s',
                $grabTaskId,
                $type,
                $reason,
                $e->getMessage()
            ));
        }
    }

    private static function assertWashStrictDeadline(float $deadline): void
    {
        if (microtime(true) >= $deadline) {
            throw new \RuntimeException(sprintf(
                'AI素材处理超过%d秒',
                self::WASH_STRICT_MATERIAL_TIMEOUT_SECONDS
            ));
        }
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
        int $taskId,
        int $platformType = 0
    ): array
    {
        $videos = [];
        $images = [];
        $totalNeed = $vCount + $iCount;

        // 提取关键词扣费
        if ($extractPrice > 0 && empty(self::$testHooks['skipUserTokens'])) {
            User::userTokensChange($userId, $extractPrice);
            $extra = ['扣费项目' => '爆款仿写-仿写文案匹配关键词', '算力单价' => $extractPrice, '实际消耗算力' => $extractPrice];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS, $extractPrice, (string)$taskId, $extra);
        }

        $requestData['keywords'] = $text;
        if (array_key_exists('extractKeywords', self::$testHooks) && is_callable(self::$testHooks['extractKeywords'])) {
            $response = (self::$testHooks['extractKeywords'])($requestData);
            $response = is_array($response) ? $response : [];
        } else {
            $response = \app\common\service\ToolsService::Coze()->extractKeywords($requestData);
        }
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

        $readyVideosCount = 0;
        $readyImagesCount = 0;
        $grabbedVideosCount = 0;
        $grabbedImagesCount = 0;

        $grabReq = ['orientation' => 'portrait'];
        $grabReq['task_id'] = $taskId;
        $grabReq['user_id'] = $userId;
        $grabReq['now'] = time();
        
        foreach ($keywordsList as $keyword) {
            if ($readyVideosCount >= $vCount && $readyImagesCount >= $iCount) break;

            if ($readyVideosCount < $vCount) {
                $grabReq['keywords'] = $keyword;
                $grabReq['searchTerm'] = $keyword;
                $vRes = self::requestGrabList('video', $grabReq);
                if (isset($vRes['code']) && $vRes['code'] == 10000 && !empty($vRes['data'])) {
                    $list = $vRes['data'];
                    shuffle($list);
                    foreach ($list as $item) {
                        if ($readyVideosCount >= $vCount) break;
                        $link = (string)($item['link'] ?? '');
                        if ($link === '' || !FileService::isAllowedGrabMaterialUrl($link, 'video')) {
                            continue;
                        }
                        $reused = self::buildReusedVideoImitationCandidate($userId, 'video', $link, $item, (string)$taskId);
                        if ($reused !== null) {
                            $videos[] = $reused;
                            $readyVideosCount++;
                            break;
                        }
                        try {
                            $transRes = self::transcodeGrabbedSource($link, 'video');
                            $url = !empty($transRes['oss_uri']) ? $transRes['oss_uri'] : ($transRes['url'] ?? '');
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
                                'duration' => $duration,
                                'size' => self::resolveGrabbedMaterialSize($transRes),
                                'thumbnail_url' => self::resolveGrabbedVideoCover((string)($item['image'] ?? '')),
                                'remote_url' => $link,
                            ];
                            $readyVideosCount++;
                            $grabbedVideosCount++;
                        } catch (\Exception $e) {
                            \think\facade\Log::error("视频素材抓取转码失败：" . $e->getMessage());
                            continue;
                        }
                        break;
                    }
                }
            }

            if ($readyImagesCount < $iCount) {
                $grabReq['keywords'] = $keyword;
                $grabReq['searchTerm'] = $keyword;
                $iRes = self::requestGrabList('image', $grabReq);
                if (isset($iRes['code']) && $iRes['code'] == 10000 && !empty($iRes['data'])) {
                    $list = $iRes['data'];
                    shuffle($list);
                    foreach ($list as $item) {
                        if ($readyImagesCount >= $iCount) break;
                        $link = (string)($item['link'] ?? '');
                        if ($link === '' || !FileService::isAllowedGrabMaterialUrl($link, 'image')) {
                            continue;
                        }
                        $reused = self::buildReusedVideoImitationCandidate($userId, 'image', $link, $item, (string)$taskId);
                        if ($reused !== null) {
                            $images[] = $reused;
                            $readyImagesCount++;
                            break;
                        }
                        try {
                            $transRes = self::transcodeGrabbedSource($link, 'image');
                            $url = !empty($transRes['oss_uri']) ? $transRes['oss_uri'] : ($transRes['url'] ?? '');
                            if (empty($url)){
                                continue;
                            }
                            $images[] = [
                                'fileUrl' => FileService::getFileUrl($url),
                                'type' => 'image',
                                'duration' => 2,
                                'size' => self::resolveGrabbedMaterialSize($transRes),
                                'remote_url' => $link,
                            ];
                            $readyImagesCount++;
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
        $videos = self::keepReusedOrReadyMaterials($videos);
        $images = self::keepReusedOrReadyMaterials($images);
        self::persistGrabbedAiMaterialsSafe($userId, $platformType, $videos, $images);
        $grabbedVideosCount = count(array_filter($videos, static fn(array $item): bool => empty($item['reused'])));
        $grabbedImagesCount = count(array_filter($images, static fn(array $item): bool => empty($item['reused'])));

        // 结算实际新下载的视频和图片扣费；复用条数不扣、不退。
        $actualVideoCost = $grabbedVideosCount * $grabVideoPrice;
        $actualImageCost = $grabbedImagesCount * $grabImagePrice;

        if (array_key_exists('settleCost', self::$testHooks) && is_callable(self::$testHooks['settleCost'])) {
            (self::$testHooks['settleCost'])($userId, $taskId, $grabbedVideosCount, $grabbedImagesCount, $actualVideoCost, $actualImageCost);
        } elseif ($actualVideoCost > 0 && empty(self::$testHooks['skipUserTokens'])) {
            User::userTokensChange($userId, $actualVideoCost);
            $extra = ['扣费项目' => '爆款仿写-AI自动找视频素材扣费', '算力单价' => $grabVideoPrice, '视频数量' => $grabbedVideosCount, '实际消耗算力' => $actualVideoCost];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_GRAB_VIDEO, $actualVideoCost, (string)$taskId, $extra);
        }

        if (!array_key_exists('settleCost', self::$testHooks) && $actualImageCost > 0 && empty(self::$testHooks['skipUserTokens'])) {
            User::userTokensChange($userId, $actualImageCost);
            $extra = ['扣费项目' => '爆款仿写-AI自动找图片素材扣费', '算力单价' => $grabImagePrice, '图片数量' => $grabbedImagesCount, '实际消耗算力' => $actualImageCost];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_GRAB_IMAGE, $actualImageCost, (string)$taskId, $extra);
        }

        return [$videos, $images];
    }

    /**
     * 抓取视频封面：转存 Grab 返回的 image，失败返回空，不阻断视频入库。
     */
    private static function resolveGrabbedVideoCover(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return '';
        }
        if (!FileService::isAllowedGrabMaterialUrl($imageUrl, 'image')) {
            try {
                Log::channel('shanjian')->write('手动爆款复刻视频封面URL不允许：' . mb_substr($imageUrl, 0, 180));
            } catch (\Throwable $e) {
            }
            return '';
        }
        try {
            $transRes = \app\common\service\UploadService::transcodeBySource($imageUrl, 'image', 0, 0);
            return self::resolveGrabbedCoverUrl($transRes);
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('手动爆款复刻视频封面转存失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return '';
        }
    }

    /**
     * @param array<string, mixed> $transRes
     */
    private static function resolveGrabbedCoverUrl(array $transRes): string
    {
        $url = !empty($transRes['oss_uri']) ? (string)$transRes['oss_uri'] : (string)($transRes['url'] ?? '');
        $url = trim($url);
        return $url === '' ? '' : (string)FileService::getFileUrl($url);
    }

    /**
     * 转码刚结束时本地文件还在，优先取字节数写入素材库。
     *
     * @param array<string, mixed> $transRes
     */
    private static function resolveGrabbedMaterialSize(array $transRes): int
    {
        $size = (int)($transRes['size'] ?? $transRes['file_size'] ?? 0);
        if ($size > 0) {
            return $size;
        }
        $local = (string)($transRes['uri'] ?? '');
        if ($local !== '' && is_file($local)) {
            $got = filesize($local);
            if ($got !== false && $got > 0) {
                return (int)$got;
            }
        }
        return 0;
    }

    /**
     * 门禁通过后的 AI 素材同步 SV 库；失败只记日志，不阻断成片。
     *
     * @param array<int, array<string, mixed>> $videos
     * @param array<int, array<string, mixed>> $images
     */
    private static function persistGrabbedAiMaterialsSafe(int $userId, int $platformType, array $videos, array $images): void
    {
        if (array_key_exists('persistGrabbedAiMaterials', self::$testHooks)
            && is_callable(self::$testHooks['persistGrabbedAiMaterials'])
        ) {
            (self::$testHooks['persistGrabbedAiMaterials'])($userId, $platformType, $videos, $images);
            return;
        }
        try {
            SvMediaMaterial::persistVideoImitationMaterials(
                $userId,
                $platformType,
                array_merge(array_values($videos), array_values($images))
            );
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('手动爆款复刻素材同步SV库失败：' . $e->getMessage());
        }
    }

    public static function persistGrabbedAiMaterialsForTest(int $userId, int $platformType, array $videos, array $images): void
    {
        self::persistGrabbedAiMaterialsSafe($userId, $platformType, $videos, $images);
    }

    public static function resolveGrabbedMaterialSizeForTest(array $transRes): int
    {
        return self::resolveGrabbedMaterialSize($transRes);
    }

    public static function resolveGrabbedVideoCoverForTest(string $imageUrl): string
    {
        return self::resolveGrabbedVideoCover($imageUrl);
    }

    public static function resolveGrabbedCoverUrlForTest(array $transRes): string
    {
        return self::resolveGrabbedCoverUrl($transRes);
    }

    public static function isExistingVideoImitationMaterialForTest(
        int $userId,
        string $remoteUrl = '',
        string $fileUrl = ''
    ): bool {
        return self::isExistingVideoImitationMaterial($userId, $remoteUrl, $fileUrl);
    }

    public static function getMixedOrAiMaterialsForTest(
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
        int $taskId,
        int $platformType = 0,
        bool $strictCount = false,
        bool $filterExistingLibrary = false,
        bool $reuseExistingLibrary = false
    ): array {
        return self::getMixedOrAiMaterials(
            $text,
            $sourceMode,
            $userId,
            $personaId,
            $localVideos,
            $localImages,
            $targetVideoCount,
            $targetImageCount,
            $extractPrice,
            $grabVideoPrice,
            $grabImagePrice,
            $taskId,
            $platformType,
            $strictCount,
            $filterExistingLibrary,
            $reuseExistingLibrary
        );
    }

    /**
     * @param array<int, array<string, mixed>> $list
     * @param array<string, bool> $seenRemoteUrls
     * @return array<string, mixed>|null
     */
    public static function dispatchWashStrictCandidateForTest(
        array $list,
        string $type,
        string $grabTaskId,
        array &$seenRemoteUrls,
        int $userId = 0,
        bool $filterExistingLibrary = false,
        bool $reuseExistingLibrary = false
    ): ?array {
        return self::dispatchWashStrictCandidate(
            $list,
            $type,
            $grabTaskId,
            $seenRemoteUrls,
            $userId,
            $filterExistingLibrary,
            $reuseExistingLibrary
        );
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>}
     */
    public static function grabAiMaterialsForTest(
        string $text,
        int $vCount,
        int $iCount,
        int $userId,
        int $personaId,
        float $extractPrice,
        float $grabVideoPrice,
        float $grabImagePrice,
        int $taskId,
        int $platformType = 0
    ): array {
        return self::grabAiMaterials(
            $text,
            $vCount,
            $iCount,
            $userId,
            $personaId,
            $extractPrice,
            $grabVideoPrice,
            $grabImagePrice,
            $taskId,
            $platformType
        );
    }

    /** @return array{0:int,1:int} */
    public static function resolveWashStrictMaterialTargetsForTest(int $taskId, int $isMaterial): array
    {
        return self::resolveWashStrictMaterialTargets($taskId, $isMaterial);
    }

    /**
     * @param array{video:array<int,array<string,mixed>>,image:array<int,array<string,mixed>>} $candidates
     * @return array{videos:array<int,array<string,mixed>>,images:array<int,array<string,mixed>>,total_duration:float}
     */
    public static function selectWashStrictMaterialsForTest(
        array $candidates,
        int $targetVideoCount,
        int $targetImageCount
    ): array {
        return self::selectWashStrictMaterials($candidates, $targetVideoCount, $targetImageCount);
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
