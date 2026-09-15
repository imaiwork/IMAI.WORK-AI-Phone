<?php

namespace app\common\service\videoImitation;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianClipTemplate;
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

    /**
     * 形象原音在接口上的音色ID哨兵。原音没有 human_voice 记录，
     * 用的是形象克隆完成时闪剪回传的 speakerId(shanjian_anchor.voice_id)，
     * 落库时 wash_voice_id 记 0、由 wash_voice_is_original 标记。
     */
    public const ORIGIN_VOICE_ID = -1;

    /** 洗稿背景音乐来源：1 系统音乐随机（默认），2 用户自传 */
    public const MUSIC_SOURCE_SYSTEM = 1;
    public const MUSIC_SOURCE_UPLOAD = 2;

    /** 生成类型 → 闪剪剪辑模板 scene（与 TaskLogic::resolveStyleScene 同一套映射） */
    public static function sceneByGenerationType(int $generationType): string
    {
        return match ($generationType) {
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN => 'virtualman',
            VideoImitationTask::GENERATION_TYPE_MATERIAL => 'oralMixCutting',
            VideoImitationTask::GENERATION_TYPE_NEWS => 'newsMixCutting',
            default => '',
        };
    }

    /**
     * 按生成类型分组的可选剪辑模板。只取 auto_type=1，与人设模式的模板池同一口径。
     */
    public static function clipTemplateGroups(): array
    {
        $groups = [];
        foreach ([
            VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN,
            VideoImitationTask::GENERATION_TYPE_MATERIAL,
            VideoImitationTask::GENERATION_TYPE_NEWS,
        ] as $type) {
            $rows = ShanjianClipTemplate::where('scene', self::sceneByGenerationType($type))
                ->where('auto_type', 1)
                ->order(['sort' => 'desc', 'id' => 'desc'])
                ->select();
            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id' => (string)$row->id,
                    'name' => (string)$row->name,
                    'cover_url' => self::fileUrl((string)$row->cover_url),
                    'demo_url' => self::fileUrl((string)$row->demo_url),
                ];
            }
            $groups[(string)$type] = $items;
        }

        return $groups;
    }

    /** 洗稿未设置时的展示默认值（与 TaskLogic 的硬编码回落保持一致） */
    public const DEFAULT_MUSIC_VOLUME = 0.5;
    public const DEFAULT_SPEECH_RATE = 1.0;
    public const SPEECH_RATE_MIN = 0.5;
    public const SPEECH_RATE_MAX = 2.0;

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
            $originVoiceId = trim((string)$anchor->voice_id);
            $avatars[] = [
                'id' => (int)$row->id,
                'name' => (string)$row->name,
                'image' => self::fileUrl((string)($row->image ?: $row->avatar)),
                'preview_url' => self::fileUrl((string)$row->result_url),
                'remark' => (string)$row->remark,
                'is_system_default' => (string)$row->remark === 'system_default',
                // 该形象克隆时是否顺带出了原音；没有则前端不展示「形象原音」选项
                'origin_voice_available' => $originVoiceId !== '',
                'origin_voice_preview_url' => self::fileUrl((string)$anchor->voice_url),
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
                // MiniMax 走 audioUrl 音频驱动，闪剪忽略 speedRatio，前端据此禁用语速滑块
                'speech_rate_supported' => $provider === self::PROVIDER_SHANJIAN,
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
            'selected_voice_id' => (int)$task->wash_voice_is_original === 1
                ? self::ORIGIN_VOICE_ID
                : (int)$task->wash_voice_id,
            'origin_voice_id' => self::ORIGIN_VOICE_ID,
            // -1 表示用户未设置，前端应显示 default_* 作为初始值
            'music_volume' => self::normalizeWashParam($task->wash_music_volume),
            'speech_rate' => self::normalizeWashParam($task->wash_speech_rate),
            'default_music_volume' => self::DEFAULT_MUSIC_VOLUME,
            'default_speech_rate' => self::DEFAULT_SPEECH_RATE,
            'music_volume_range' => ['min' => 0, 'max' => 1, 'step' => 0.1],
            'speech_rate_range' => ['min' => self::SPEECH_RATE_MIN, 'max' => self::SPEECH_RATE_MAX, 'step' => 0.1],
            'avatars' => $avatars,
            'voices' => $voices,
            // 剪辑模板按生成类型分组下发，前端切类型不必重新拉接口；空字符串=自动随机
            'clip_templates' => self::clipTemplateGroups(),
            'selected_clip_template_id' => (string)$task->wash_clip_template_id,
            // 背景音乐：没传过自选地址就是系统随机
            'music_source' => trim((string)$task->wash_music_url) === ''
                ? self::MUSIC_SOURCE_SYSTEM
                : self::MUSIC_SOURCE_UPLOAD,
            'selected_music_url' => self::fileUrl((string)$task->wash_music_url),
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
        int $voiceId,
        ?float $musicVolume = null,
        ?float $speechRate = null,
        ?string $clipTemplateId = null,
        ?string $musicUrl = null
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
        $isOriginVoice = false;
        $anchor = null;

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
            if ($voiceId === self::ORIGIN_VOICE_ID) {
                // 形象原音跟着形象走：没有出镜形象就没有原音可取
                if ($generationType !== VideoImitationTask::GENERATION_TYPE_DIGITAL_HUMAN || !$anchor) {
                    throw new \RuntimeException('形象原音仅数字人口播可用，请先选择出镜形象');
                }
                $thirdVoiceId = trim((string)$anchor->voice_id);
                if ($thirdVoiceId === '') {
                    throw new \RuntimeException('所选形象暂无可用原音，请改选已克隆的音色');
                }
                // 原音就是该形象的闪剪 speakerId，形象冻结校验已在上面做过，这里不再重复查
                $isOriginVoice = true;
                $voiceId = 0;
                $provider = self::PROVIDER_SHANJIAN;
            } elseif ($voiceId <= 0) {
                throw new \RuntimeException('当前视频类型必须选择音色');
            } else {
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
            }
        } else {
            $voiceId = 0;
        }

        // 未传即保留任务上已有值(重新确认时不清空)；传了就按范围校验
        $volumeValue = $musicVolume === null
            ? self::normalizeWashParam($task->wash_music_volume)
            : self::assertWashMusicVolume($musicVolume);
        $rateValue = $speechRate === null
            ? self::normalizeWashParam($task->wash_speech_rate)
            : self::assertWashSpeechRate($speechRate);

        // 语速对本次选择无意义时不落库，避免详情页显示一个不会生效的值
        if (!self::speechRateApplies($generationType, $provider)) {
            $rateValue = VideoImitationTask::WASH_PARAM_UNSET;
        }

        // 未传保留任务上已有值；传空串表示改回「自动随机」
        $templateValue = $clipTemplateId === null
            ? self::assertClipTemplate((string)$task->wash_clip_template_id, $generationType, true)
            : self::assertClipTemplate($clipTemplateId, $generationType, false);
        $musicValue = $musicUrl === null
            ? trim((string)$task->wash_music_url)
            : self::assertMusicUrl($musicUrl);

        return [
            'generation_type' => $generationType,
            'wash_avatar_id' => $avatarId,
            'wash_voice_id' => $voiceId,
            'wash_voice_is_original' => $isOriginVoice ? 1 : 0,
            'wash_voice_provider' => $provider,
            'wash_third_avatar_id' => $thirdAvatarId,
            'wash_third_voice_id' => $thirdVoiceId,
            'wash_clip_template_id' => $templateValue,
            'wash_music_url' => $musicValue,
            'wash_music_volume' => $volumeValue,
            'wash_speech_rate' => $rateValue,
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

        // 一条克隆音色都没有时，若所选形象自带原音就用原音出数字人口播，不必降到新闻体
        if ($avatarId > 0 && $voiceId <= 0) {
            $originAnchor = self::findAvailableAnchor($avatarId, $userId);
            if ($originAnchor && trim((string)$originAnchor->voice_id) !== '') {
                $voiceId = self::ORIGIN_VOICE_ID;
            }
        }

        if ($avatarId > 0 && $voiceId !== 0) {
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
        ], true) && (int)$task->wash_voice_id <= 0 && (int)$task->wash_voice_is_original !== 1) {
            return 'voice';
        }
        return (int)$task->generation_config_confirmed === 1 ? 'generate' : 'confirm';
    }

    /**
     * 语速是否对本次选择有意义：仅闪剪音色的口播类视频可用。
     * 新闻体无口播；MiniMax 走 audioUrl，闪剪按设计忽略 speedRatio。
     */
    public static function speechRateApplies(int $generationType, string $provider): bool
    {
        if ($generationType === VideoImitationTask::GENERATION_TYPE_NEWS) {
            return false;
        }

        return $provider === self::PROVIDER_SHANJIAN;
    }

    /**
     * 读库值归一：非数字或越界一律视为未设置
     */
    private static function normalizeWashParam($value): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return VideoImitationTask::WASH_PARAM_UNSET;
        }
        $value = round((float)$value, 2);

        return $value < 0 ? VideoImitationTask::WASH_PARAM_UNSET : $value;
    }

    /**
     * 校验自选剪辑模板：空串=自动随机；非空必须是该生成类型 scene 下 auto_type=1 的模板。
     * $lenient=true 用于沿用任务上已存的值——换了视频类型后旧模板 scene 不匹配，
     * 静默清空回落自动随机即可，不该因此把整次确认打回。
     */
    private static function assertClipTemplate(string $clipTemplateId, int $generationType, bool $lenient): string
    {
        $clipTemplateId = trim($clipTemplateId);
        if ($clipTemplateId === '') {
            return '';
        }
        $scene = self::sceneByGenerationType($generationType);
        $exists = $scene !== '' && ShanjianClipTemplate::where('id', $clipTemplateId)
            ->where('scene', $scene)
            ->where('auto_type', 1)
            ->count() > 0;
        if ($exists) {
            return $clipTemplateId;
        }
        if ($lenient) {
            return '';
        }

        throw new \RuntimeException('所选视频模板不存在或与当前视频类型不匹配');
    }

    /**
     * 校验自选背景音乐：空串=系统音乐随机。
     * 只收本站上传后的地址——setFileUrl 会把本站域名剥成相对路径，
     * 剥不掉说明是外部直链，直接拒掉，避免把任意外链下发给闪剪。
     */
    private static function assertMusicUrl(string $musicUrl): string
    {
        $musicUrl = trim($musicUrl);
        if ($musicUrl === '') {
            return '';
        }
        $uri = trim((string)FileService::setFileUrl($musicUrl));
        if ($uri === '' || preg_match('#^https?://#i', $uri)) {
            throw new \RuntimeException('背景音乐地址不合法，请重新上传');
        }
        if (mb_strlen($uri, 'UTF-8') > 500) {
            throw new \RuntimeException('背景音乐地址过长，请重新上传');
        }

        return $uri;
    }

    private static function assertWashMusicVolume(float $volume): float
    {
        $volume = round($volume, 2);
        if ($volume < 0 || $volume > 1) {
            throw new \RuntimeException('背景音乐音量需在 0~1 之间');
        }

        return $volume;
    }

    private static function assertWashSpeechRate(float $rate): float
    {
        $rate = round($rate, 2);
        if ($rate < self::SPEECH_RATE_MIN || $rate > self::SPEECH_RATE_MAX) {
            throw new \RuntimeException('口播语速需在 ' . self::SPEECH_RATE_MIN . '~' . self::SPEECH_RATE_MAX . ' 之间');
        }

        return $rate;
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
