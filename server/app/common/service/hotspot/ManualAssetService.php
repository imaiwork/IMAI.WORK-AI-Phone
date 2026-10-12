<?php

namespace app\common\service\hotspot;

use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use app\common\service\FileService;

/**
 * 热点追踪「不选人设」模式专用资源查询器。
 * 与爆款复刻洗稿模式（ManualGenerationAssetService）同口径：
 * 形象取用户自有数字人 + 闪剪中台锚点，音色取用户自有克隆音色，两者独立自选。
 * 不复用人设的 availableQuery，也不改变人设链路行为。
 */
class ManualAssetService
{
    public const PROVIDER_SHANJIAN = 'shanjian';
    public const PROVIDER_MINIMAX = 'minimax';

    /** 与爆款洗稿一致：闪剪 V3/S3 + MiniMax 克隆音色可下发 */
    public const VOICE_MODEL_VERSIONS = [8, 10, 11, 15];

    /**
     * 「形象原音」音色哨兵：音色库里没有对应记录，下发时取所选形象克隆出的闪剪 speakerId。
     * 与爆款洗稿 ManualGenerationAssetService::ORIGIN_VOICE_ID 同值，两端口径一致。
     */
    public const ORIGIN_VOICE_ID = -1;

    /** 背景音乐来源：系统随机 */
    public const MUSIC_SOURCE_SYSTEM = 1;
    /** 背景音乐来源：用户自传 */
    public const MUSIC_SOURCE_UPLOAD = 2;
    /** 背景音乐来源：指定一首系统曲目 */
    public const MUSIC_SOURCE_PICK = 3;

    /** 热点视频类型 → 闪剪剪辑模板场景（与 SynthesisTemplateConfigService 的生成类型一一对应） */
    private const VIDEO_TYPE_GENERATION = [
        'digital' => SynthesisTemplateConfigService::TYPE_DIGITAL_ORAL,
        'clips' => SynthesisTemplateConfigService::TYPE_MATERIAL_MIXCUT,
    ];

    /**
     * 自选形象与音色候选（不依赖人设）
     *
     * @return array{avatars:array<int,array>, voices:array<int,array>}
     */
    public static function options(int $userId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再选择形象与音色');
        }

        $avatars = self::avatars($userId);
        $voices = self::voices($userId);
        $clipTemplates = self::clipTemplates();
        HotspotLog::write(sprintf(
            '自选形象音色查询完成：用户=%d 形象数=%d 音色数=%d 模板数=%s',
            $userId,
            count($avatars),
            count($voices),
            implode('/', array_map('count', $clipTemplates))
        ));
        return [
            'avatars' => $avatars,
            'voices' => $voices,
            // 剪辑模板按视频类型分组，前端切类型不必重新拉接口；空 id = 自动随机
            'clip_templates' => $clipTemplates,
            'origin_voice_id' => self::ORIGIN_VOICE_ID,
        ];
    }

    /**
     * 用户自有数字人形象（须已生成可用的闪剪中台ID，否则下发必失败，列表里就不给选）
     */
    public static function avatars(int $userId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再选择数字人形象');
        }
        $rows = DigitalHumanAnchor::where('user_id', $userId)
            ->where('status', 2)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->limit(100)
            ->select();

        $out = [];
        foreach ($rows as $row) {
            $anchor = self::findAvailableAnchor((int)$row->id, $userId);
            if (!$anchor) {
                continue;
            }
            $out[] = [
                'id' => (int)$row->id,
                'name' => (string)$row->name,
                // 与人设形象列表字段对齐（img），前端两套模式共用同一渲染
                'img' => self::fileUrl((string)($row->image ?: $row->avatar)),
                'preview_url' => self::fileUrl((string)$row->result_url),
                'is_system_default' => (string)$row->remark === 'system_default',
                // 该形象克隆时是否顺带出了原音；没有则前端不展示「形象原音」选项
                'origin_voice_available' => trim((string)$anchor->voice_id) !== '',
                'origin_voice_preview_url' => self::fileUrl((string)$anchor->voice_url),
            ];
        }
        return $out;
    }

    /**
     * 用户自有克隆音色
     */
    public static function voices(int $userId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再选择音色');
        }
        $rows = HumanVoice::where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('model_version', self::VOICE_MODEL_VERSIONS)
            ->where('voice_id', '<>', '')
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->limit(200)
            ->select();

        $out = [];
        foreach ($rows as $row) {
            $provider = self::voiceProvider((int)$row->model_version);
            $out[] = [
                'id' => (int)$row->id,
                'name' => (string)$row->name,
                'provider' => $provider,
                // MiniMax 走音频驱动，闪剪忽略 speedRatio，前端据此禁用语速滑块
                'speech_rate_supported' => $provider === self::PROVIDER_SHANJIAN,
                'preview_url' => self::fileUrl((string)$row->voice_urls),
                'is_system_default' => (string)$row->remark === 'system_default_voice',
            ];
        }
        return $out;
    }

    /**
     * 可选剪辑模板，按热点视频类型分组（前端切类型不必重新拉接口）。
     * 空字符串 id 代表「自动随机」，不在列表里，由前端固定放首位。
     *
     * @return array<string, array<int, array>>
     */
    public static function clipTemplates(): array
    {
        $groups = [];
        foreach (self::VIDEO_TYPE_GENERATION as $videoType => $generationType) {
            $rows = ShanjianClipTemplate::where('scene', SynthesisTemplateConfigService::sceneByType($generationType))
                ->where('auto_type', 1)
                ->order(['sort' => 'desc', 'id' => 'desc'])
                ->select();
            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id' => (string)$row->id,
                    'name' => (string)$row->name,
                    'cover_url' => self::fileUrl((string)$row->cover_url),
                    // 封面看不出剪辑风格，前端要能点开样片看
                    'demo_url' => self::fileUrl((string)$row->demo_url),
                ];
            }
            $groups[$videoType] = $items;
        }
        return $groups;
    }

    /**
     * 下发前解析用户自选的剪辑模板：空串或不属于该视频类型的一律回落自动随机，
     * 不抛错——模板只影响剪辑风格，为它整单失败不值当。
     */
    public static function resolveClipTemplateId(string $clipTemplateId, string $videoType): string
    {
        $clipTemplateId = trim($clipTemplateId);
        $generationType = self::VIDEO_TYPE_GENERATION[$videoType] ?? 0;
        if ($clipTemplateId === '' || $generationType === 0) {
            return '';
        }
        $scene = SynthesisTemplateConfigService::sceneByType($generationType);
        $hit = ShanjianClipTemplate::where('id', $clipTemplateId)
            ->where('scene', $scene)
            ->where('auto_type', 1)
            ->findOrEmpty();
        if ($hit->isEmpty()) {
            HotspotLog::write(sprintf(
                '自选剪辑模板不可用，回落自动随机：模板id=%s 视频类型=%s',
                $clipTemplateId,
                $videoType
            ));
            return '';
        }
        return (string)$hit->id;
    }

    /**
     * 不选人设的背景音乐。
     * 指定系统曲目在提交时必须仍上架；自传优先；否则系统随机。
     */
    public static function resolveMusicUrl(string $musicUrl, string $systemMusicId = '', int $musicSource = self::MUSIC_SOURCE_SYSTEM): string
    {
        $musicUrl = trim($musicUrl);
        if ($musicUrl !== '') {
            return self::fileUrl($musicUrl);
        }
        $systemMusicId = trim($systemMusicId);
        if ($musicSource === self::MUSIC_SOURCE_PICK || $systemMusicId !== '') {
            return \app\common\service\SystemMusicService::resolve($systemMusicId, true);
        }
        return CopywritingImitationLogic::resolveSystemMusicUrl();
    }

    /**
     * 下发前解析所选形象，返回闪剪需要的 anchor_id / 封面图
     *
     * @return array{id:int,name:string,anchor_id:string,voice_id:string,pic:string}
     */
    public static function resolveAvatar(int $userId, int $avatarId): array
    {
        if ($userId <= 0 || $avatarId <= 0) {
            throw new HotspotUpstreamException('数字人口播混剪需要选择数字人形象');
        }
        $row = DigitalHumanAnchor::where('id', $avatarId)
            ->where('user_id', $userId)
            ->where('status', 2)
            ->whereNull('delete_time')
            ->find();
        if (!$row) {
            throw new HotspotUpstreamException('所选数字人形象不存在或不可用，请重新选择');
        }
        $anchor = self::findAvailableAnchor($avatarId, $userId);
        if (!$anchor) {
            throw new HotspotUpstreamException('所选形象尚未生成可用的闪剪中台ID，请重新选择');
        }
        $anchorId = trim((string)$anchor->anchor_id);
        if (ShanjianVideoSettingLogic::isShanjianAnchorMemberFrozen($anchorId, $userId)) {
            throw new HotspotUpstreamException(ShanjianVideoSettingLogic::frozenAnchorTip());
        }
        return [
            'id' => (int)$row->id,
            'name' => (string)$row->name,
            'anchor_id' => $anchorId,
            // 音色在本模式下由用户单独选，形象不再捆绑音色
            'voice_id' => '',
            'pic' => self::fileUrl((string)($row->image ?: $row->avatar)),
        ];
    }

    /**
     * 下发前解析所选音色，返回闪剪需要的 voice_id 与模型版本。
     * $voiceId = ORIGIN_VOICE_ID 时取 $avatarId 那个形象克隆出的原音。
     *
     * @return array{id:int,name:string,voice_id:string,model_version:int,provider:string}
     */
    public static function resolveVoice(int $userId, int $voiceId, int $avatarId = 0): array
    {
        if ($userId > 0 && $voiceId === self::ORIGIN_VOICE_ID) {
            return self::resolveOriginVoice($userId, $avatarId);
        }
        if ($userId <= 0 || $voiceId <= 0) {
            throw new HotspotUpstreamException('请选择音色');
        }
        $row = HumanVoice::where('id', $voiceId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('model_version', self::VOICE_MODEL_VERSIONS)
            ->where('voice_id', '<>', '')
            ->whereNull('delete_time')
            ->find();
        if (!$row) {
            throw new HotspotUpstreamException('所选音色不存在或不可用，请重新选择');
        }
        $thirdVoiceId = trim((string)$row->voice_id);
        if (ShanjianVideoSettingLogic::isVoiceMemberFrozen($thirdVoiceId, $userId)) {
            throw new HotspotUpstreamException('该音色因会员权益到期被冻结，恢复会员后可继续使用');
        }
        return [
            'id' => (int)$row->id,
            'name' => (string)$row->name,
            'voice_id' => $thirdVoiceId,
            'model_version' => (int)$row->model_version,
            'provider' => self::voiceProvider((int)$row->model_version),
        ];
    }

    /**
     * 形象原音：就是该形象克隆出的闪剪 speakerId，没有出镜形象就没有原音可取。
     * 形象本身的可用性与冻结校验已在 resolveAvatar 做过，这里只取音色不重复查。
     */
    private static function resolveOriginVoice(int $userId, int $avatarId): array
    {
        if ($avatarId <= 0) {
            throw new HotspotUpstreamException('形象原音仅数字人口播混剪可用，请先选择数字人形象');
        }
        $anchor = self::findAvailableAnchor($avatarId, $userId);
        $thirdVoiceId = $anchor ? trim((string)$anchor->voice_id) : '';
        if ($thirdVoiceId === '') {
            throw new HotspotUpstreamException('所选形象暂无可用原音，请改选已克隆的音色');
        }
        return [
            'id' => self::ORIGIN_VOICE_ID,
            'name' => '形象原音',
            'voice_id' => $thirdVoiceId,
            'model_version' => 0,
            'provider' => self::PROVIDER_SHANJIAN,
        ];
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
