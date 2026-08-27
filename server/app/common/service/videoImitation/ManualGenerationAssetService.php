<?php

namespace app\common\service\videoImitation;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;

/**
 * 手动爆款复刻洗稿模式专用资源查询器。
 * 不复用、也不改变人设及自动合成的共享 availableQuery 行为。
 */
class ManualGenerationAssetService
{
    public const PROVIDER_SHANJIAN = 'shanjian';
    public const PROVIDER_MINIMAX = 'minimax';

    public static function options(VideoImitationTask $task, int $userId): array
    {
        self::assertWashVideoTask($task, $userId);

        $avatars = [];
        $rows = DigitalHumanAnchor::where('user_id', $userId)
            ->where('status', 2)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->select();
        foreach ($rows as $row) {
            $anchor = self::findAvailableAnchor((int)$row->id, $userId);
            if (!$anchor) {
                continue;
            }
            $avatars[] = [
                'id' => (int)$row->id,
                'name' => (string)$row->name,
                'image' => self::fileUrl((string)($row->image ?: $row->avatar)),
                'preview_url' => self::fileUrl((string)$row->result_url),
                'remark' => (string)$row->remark,
                'is_system_default' => (string)$row->remark === 'system_default',
            ];
        }

        $voices = [];
        $voiceRows = HumanVoice::where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('model_version', [8, 10, 11])
            ->where('voice_id', '<>', '')
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->select();
        foreach ($voiceRows as $row) {
            $provider = self::voiceProvider((int)$row->model_version);
            $voices[] = [
                'id' => (int)$row->id,
                'name' => (string)$row->name,
                'provider' => $provider,
                'preview_url' => self::fileUrl((string)$row->voice_urls),
                'remark' => (string)$row->remark,
                'is_system_default' => (string)$row->remark === 'system_default_voice',
            ];
        }

        return [
            'task_id' => (int)$task->id,
            'generation_type' => (int)$task->generation_type,
            'generation_config_confirmed' => (int)$task->generation_config_confirmed,
            'selected_avatar_id' => (int)$task->wash_avatar_id,
            'selected_voice_id' => (int)$task->wash_voice_id,
            'avatars' => $avatars,
            'voices' => $voices,
            'next_step' => self::nextStep($task),
        ];
    }

    /**
     * 校验选择并返回可安全落库的业务ID和中台ID快照。
     */
    public static function resolveSelection(
        VideoImitationTask $task,
        int $userId,
        int $generationType,
        int $avatarId,
        int $voiceId
    ): array {
        self::assertWashVideoTask($task, $userId);
        if (!in_array($generationType, [
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
            VideoImitationTask::GENERATION_TYPE_MATERIAL,
            VideoImitationTask::GENERATION_TYPE_NEWS,
        ], true)) {
            throw new \RuntimeException('视频类型不正确');
        }

        $thirdAvatarId = '';
        $thirdVoiceId = '';
        $provider = '';

        if ($generationType === VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN) {
            if ($avatarId <= 0) {
                throw new \RuntimeException('数字人口播必须选择出镜形象');
            }
            $avatar = DigitalHumanAnchor::where('id', $avatarId)
                ->where('user_id', $userId)
                ->where('status', 2)
                ->whereNull('delete_time')
                ->find();
            if (!$avatar) {
                throw new \RuntimeException('形象不存在、不可用或不属于当前用户');
            }
            $anchor = self::findAvailableAnchor($avatarId, $userId);
            if (!$anchor) {
                throw new \RuntimeException('所选形象尚未生成可用的闪剪中台ID');
            }
            $thirdAvatarId = trim((string)$anchor->anchor_id);
            if (ShanjianVideoSettingLogic::isShanjianAnchorMemberFrozen($thirdAvatarId, $userId)) {
                throw new \RuntimeException(ShanjianVideoSettingLogic::frozenAnchorTip());
            }
        } else {
            $avatarId = 0;
        }

        if (in_array($generationType, [
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
            VideoImitationTask::GENERATION_TYPE_MATERIAL,
        ], true)) {
            if ($voiceId <= 0) {
                throw new \RuntimeException('当前视频类型必须选择音色');
            }
            $voice = HumanVoice::where('id', $voiceId)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->whereIn('model_version', [8, 10, 11])
                ->where('voice_id', '<>', '')
                ->whereNull('delete_time')
                ->find();
            if (!$voice) {
                throw new \RuntimeException('音色不存在、不可用或不属于当前用户');
            }
            $thirdVoiceId = trim((string)$voice->voice_id);
            if (ShanjianVideoSettingLogic::isVoiceMemberFrozen($thirdVoiceId, $userId)) {
                throw new \RuntimeException('该音色因会员权益到期被冻结，恢复会员后可继续使用');
            }
            $provider = self::voiceProvider((int)$voice->model_version);
        } else {
            $voiceId = 0;
        }

        return [
            'generation_type' => $generationType,
            'wash_avatar_id' => $avatarId,
            'wash_voice_id' => $voiceId,
            'wash_voice_provider' => $provider,
            'wash_third_avatar_id' => $thirdAvatarId,
            'wash_third_voice_id' => $thirdVoiceId,
            'generation_config_confirmed' => 1,
            'visual_material_source' => 1,
        ];
    }

    /**
     * 超时自动确认：随机选择默认生成配置，数字人→素材→新闻体逐级降级
     *
     * @return array{generation_type:int, avatar_id:int, voice_id:int}
     */
    public static function randomSelection(VideoImitationTask $task, int $userId): array
    {
        self::assertWashVideoTask($task, $userId);

        $avatarId = 0;
        $avatarRows = DigitalHumanAnchor::where('user_id', $userId)
            ->where('status', 2)
            ->whereNull('delete_time')
            ->orderRand()
            ->select();
        foreach ($avatarRows as $row) {
            if (self::findAvailableAnchor((int)$row->id, $userId)) {
                $avatarId = (int)$row->id;
                break;
            }
        }

        $voiceId = (int)HumanVoice::where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('model_version', [8, 10, 11])
            ->where('voice_id', '<>', '')
            ->whereNull('delete_time')
            ->orderRand()
            ->value('id');

        if ($avatarId > 0 && $voiceId > 0) {
            return [
                'generation_type' => VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
                'avatar_id' => $avatarId,
                'voice_id' => $voiceId,
            ];
        }
        if ($voiceId > 0) {
            return [
                'generation_type' => VideoImitationTask::GENERATION_TYPE_MATERIAL,
                'avatar_id' => 0,
                'voice_id' => $voiceId,
            ];
        }
        return [
            'generation_type' => VideoImitationTask::GENERATION_TYPE_NEWS,
            'avatar_id' => 0,
            'voice_id' => 0,
        ];
    }

    public static function nextStep(VideoImitationTask $task): string
    {
        if ((int)$task->generation_type === VideoImitationTask::GENERATION_TYPE_NONE) {
            return 'generation_type';
        }
        if ((int)$task->generation_type === VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN
            && (int)$task->wash_avatar_id <= 0
        ) {
            return 'avatar';
        }
        if (in_array((int)$task->generation_type, [
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
            VideoImitationTask::GENERATION_TYPE_MATERIAL,
        ], true) && (int)$task->wash_voice_id <= 0) {
            return 'voice';
        }
        return (int)$task->generation_config_confirmed === 1 ? 'generate' : 'confirm';
    }

    private static function assertWashVideoTask(VideoImitationTask $task, int $userId): void
    {
        if ((int)$task->user_id !== $userId) {
            throw new \RuntimeException('任务不存在');
        }
        if ((int)$task->media_type !== VideoImitationTask::MEDIA_TYPE_VIDEO
            || (int)$task->rewrite_mode !== VideoImitationTask::REWRITE_MODE_WASH
        ) {
            throw new \RuntimeException('该接口仅用于手动抖音洗稿任务');
        }
    }

    private static function findAvailableAnchor(int $digitalHumanId, int $userId): ?ShanjianAnchor
    {
        return ShanjianAnchor::where('dh_id', $digitalHumanId)
            ->where('user_id', $userId)
            ->where('status', 6)
            ->where('anchor_id', '<>', '')
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->find();
    }

    private static function voiceProvider(int $modelVersion): string
    {
        return in_array($modelVersion, [10, 11], true)
            ? self::PROVIDER_MINIMAX
            : self::PROVIDER_SHANJIAN;
    }

    private static function fileUrl(string $url): string
    {
        return trim($url) === '' ? '' : FileService::getFileUrl($url);
    }
}
