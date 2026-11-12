<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class SynthesisConfig extends BaseModel
{
    protected $name = 'ai_persona_synthesis_config';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //'画面素材: 1-纯AI, 2-AI+素材库, 3-纯素材库, 4-素材库+广场
    const VISUAL_MATERIAL_SOURCE_AI = 1;
    const VISUAL_MATERIAL_SOURCE_AI_MATERIAL = 2;
    const VISUAL_MATERIAL_SOURCE_MATERIAL = 3;
    const VISUAL_MATERIAL_SOURCE_SQUARE = 4;

    //闪剪素材使用方式: 1-切片 2-原片
    const MATERIAL_USE_MODE_SLICE = 1;
    const MATERIAL_USE_MODE_ORIGINAL = 2;
   //'文案来源: 1-仿写, 2-AI生成, 3-无需, 4-文案库, 5-热点生成'
    const COPYWRITING_SOURCE_IMITATE = 1;
    const COPYWRITING_SOURCE_AI = 2;
    const COPYWRITING_SOURCE_NONE = 3;
    const COPYWRITING_SOURCE_LIBRARY = 4;
    const COPYWRITING_SOURCE_HOTSPOT = 5;
    const HOTSPOT_PICK_MODE_LATEST = 1; // 本设备未用过的最新优先（id 降序）
    const HOTSPOT_PICK_MODE_ONCE_DEVICE = 2; // 全局每条只用一次，按入库顺序（id 升序）
    const LIBRARY_USE_MODE_RANDOM = 1;
    const LIBRARY_USE_MODE_SEQUENCE = 2;
    const LIBRARY_REUSE_MODE_ONCE = 1;
    const LIBRARY_REUSE_MODE_REPEAT = 2;

    //工作方式: 1-AI合成视频, 2-成品库直发
    const WORK_MODE_AI_SYNTHESIS = 1;
    const WORK_MODE_PRODUCT_DIRECT = 2;

    //成品库使用方式: 1-随机, 2-顺序
    const PRODUCT_USE_MODE_RANDOM = 1;
    const PRODUCT_USE_MODE_SEQUENCE = 2;

    //成品库随机规则: 1-每个成品只用一次, 2-可重复使用
    const PRODUCT_REUSE_MODE_ONCE = 1;
    const PRODUCT_REUSE_MODE_REPEAT = 2;
    //'视频封面: 1-默认, 2-AI自动, 3-手动'
    const VIDEO_COVER_TYPE_DEFAULT = 1;
    const VIDEO_COVER_TYPE_AI = 2;
    const VIDEO_COVER_TYPE_MANUAL = 3;
    const NEWS_MIXCUT_DURATION_DEFAULT = 60;
    const NEWS_MIXCUT_DURATION_MIN = 5;
    const NEWS_MIXCUT_DURATION_MAX = 300;

    //背景音乐来源: 1-系统音乐库, 2-人设音乐库, 3-不使用背景音乐
    const MUSIC_SOURCE_SYSTEM = 1;
    const MUSIC_SOURCE_PERSONA = 2;
    const MUSIC_SOURCE_NONE = 3;
    const MUSIC_VOLUME_DEFAULT = 0.3;
    const SPEECH_RATE_DEFAULT = 1.0;
    const SPEECH_RATE_MIN = 0.5;
    const SPEECH_RATE_MAX = 2.0;

    //文案生成类型: 1干货科普 2带货种草 3观点评论 4剧情段子 5情感共鸣 6揭秘避坑 7自定义
    const COPYWRITING_GENERATION_TYPE_KNOWLEDGE = 1;
    const COPYWRITING_GENERATION_TYPE_SELL = 2;
    const COPYWRITING_GENERATION_TYPE_OPINION = 3;
    const COPYWRITING_GENERATION_TYPE_SKIT = 4;
    const COPYWRITING_GENERATION_TYPE_EMOTION = 5;
    const COPYWRITING_GENERATION_TYPE_PITFALL = 6;
    const COPYWRITING_GENERATION_TYPE_CUSTOM = 7;
    const COPYWRITING_GENERATION_CUSTOM_MAX = 500;

    /** 一镜到底：仅新闻体混剪支持，开启后该类型每条只用 1 条视频素材 */
    const SINGLE_SHOT_OFF = 0;
    const SINGLE_SHOT_ON = 1;

    /** 指定素材分组：开启后该生成类型只从所选素材分组取素材 */
    const MATERIAL_GROUP_OFF = 0;
    const MATERIAL_GROUP_ON = 1;


    protected $json = ['generation_types', 'template_config', 'square_group_ids', 'type_config'];
    protected $jsonAssoc = true;

    /** 每日出片条数：0=未配置。仅无手机模式消费，手机模式仍按发布时段数出片 */
    const DAILY_VIDEO_COUNT_UNSET = 0;
    /** 无手机模式未配置条数时的兜底，取 1 而不是 0，避免云端人设一条都不出 */
    const DAILY_VIDEO_COUNT_CLOUD_DEFAULT = 1;
    const DAILY_VIDEO_COUNT_MAX = 20;

    // 生成类型：1-数字人口播 3-纯素材混剪 4-新闻体混剪（与闪剪 shanjian_type 同值）
    const GENERATION_TYPE_DIGITAL_HUMAN = 1;
    const GENERATION_TYPE_MATERIAL = 3;
    const GENERATION_TYPE_NEWS = 4;
    const TYPE_CONFIG_KEYS = [
        self::GENERATION_TYPE_DIGITAL_HUMAN,
        self::GENERATION_TYPE_MATERIAL,
        self::GENERATION_TYPE_NEWS,
    ];
    const TYPE_VIDEO_COUNT_MAX = 20;

    /** 归一每日出片条数：非法或越界回落到未配置(0)，由调用方决定兜底 */
    public static function normalizeDailyVideoCount($value): int
    {
        $count = (int)$value;
        if ($count < 0 || $count > self::DAILY_VIDEO_COUNT_MAX) {
            return self::DAILY_VIDEO_COUNT_UNSET;
        }
        return $count;
    }

    public static function normalizeNewsMixcutDuration($duration): int
    {
        $duration = (int)$duration;
        if ($duration < self::NEWS_MIXCUT_DURATION_MIN || $duration > self::NEWS_MIXCUT_DURATION_MAX) {
            return self::NEWS_MIXCUT_DURATION_DEFAULT;
        }

        return $duration;
    }

    public static function normalizeMusicSource($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            self::MUSIC_SOURCE_SYSTEM,
            self::MUSIC_SOURCE_PERSONA,
            self::MUSIC_SOURCE_NONE,
        ], true) ? $value : self::MUSIC_SOURCE_SYSTEM;
    }

    public static function normalizeMusicVolume($value): float
    {
        if (!is_numeric($value)) {
            return self::MUSIC_VOLUME_DEFAULT;
        }
        $value = round((float)$value, 1);
        if ($value < 0 || $value > 1) {
            return self::MUSIC_VOLUME_DEFAULT;
        }
        return $value;
    }

    public static function normalizeSpeechRate($value): float
    {
        if (!is_numeric($value)) {
            return self::SPEECH_RATE_DEFAULT;
        }
        $value = round((float)$value, 1);
        if ($value < self::SPEECH_RATE_MIN || $value > self::SPEECH_RATE_MAX) {
            return self::SPEECH_RATE_DEFAULT;
        }
        return $value;
    }

    public function getNewsMixcutDurationAttr($value): int
    {
        return self::normalizeNewsMixcutDuration($value);
    }

    public function getPicAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setPicAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public static function copywritingGenerationTypeMap(): array
    {
        return AiPersonaSynthesisConfig::copywritingGenerationTypeMap();
    }

    public static function normalizeCopywritingGenerationType($value): int
    {
        return AiPersonaSynthesisConfig::normalizeCopywritingGenerationType($value);
    }

    public static function normalizeCopywritingGenerationCustom($value): string
    {
        return AiPersonaSynthesisConfig::normalizeCopywritingGenerationCustom($value);
    }

    public static function buildCopywritingGenerationVoice(int $type, string $custom): string
    {
        return AiPersonaSynthesisConfig::buildCopywritingGenerationVoice($type, $custom);
    }

    public static function requireValidHotspotPickMode($value): int
    {
        return AiPersonaSynthesisConfig::requireValidHotspotPickMode($value);
    }

    // ------------------------------------------------------------------
    // 分类型配置（type_config）：统一委托给 AiPersonaSynthesisConfig，避免两份口径
    // ------------------------------------------------------------------

    public static function normalizeTypeVideoCount($value): int
    {
        return AiPersonaSynthesisConfig::normalizeTypeVideoCount($value);
    }

    public static function normalizeSingleShot($value): int
    {
        return AiPersonaSynthesisConfig::normalizeSingleShot($value);
    }

    public static function resolveTypeSingleShot($config, int $type): bool
    {
        return AiPersonaSynthesisConfig::resolveTypeSingleShot($config, $type);
    }

    public static function normalizeMaterialGroupEnabled($value): int
    {
        return AiPersonaSynthesisConfig::normalizeMaterialGroupEnabled($value);
    }

    public static function normalizeMaterialGroupIds($value): array
    {
        return AiPersonaSynthesisConfig::normalizeMaterialGroupIds($value);
    }

    public static function resolveTypeMaterialGroupEnabled($config, int $type): bool
    {
        return AiPersonaSynthesisConfig::resolveTypeMaterialGroupEnabled($config, $type);
    }

    public static function resolveTypeMaterialGroupIds($config, int $type): array
    {
        return AiPersonaSynthesisConfig::resolveTypeMaterialGroupIds($config, $type);
    }

    public static function resolveTypeMaterialGroupIdsForDisplay($config, int $type): array
    {
        return AiPersonaSynthesisConfig::resolveTypeMaterialGroupIdsForDisplay($config, $type);
    }

    public static function normalizeTypeConfig($raw): array
    {
        return AiPersonaSynthesisConfig::normalizeTypeConfig($raw);
    }

    public static function resolveGenerationOrder($config): array
    {
        return AiPersonaSynthesisConfig::resolveGenerationOrder($config);
    }

    public static function hasExplicitTypeConfig($config): bool
    {
        return AiPersonaSynthesisConfig::hasExplicitTypeConfig($config);
    }

    public static function splitLegacyVideoCount(int $total, array $order): array
    {
        return AiPersonaSynthesisConfig::splitLegacyVideoCount($total, $order);
    }

    public static function resolveCloudTypeCounts($config): array
    {
        return AiPersonaSynthesisConfig::resolveCloudTypeCounts($config);
    }

    public static function resolveCloudTypeCount($config, int $type): int
    {
        return AiPersonaSynthesisConfig::resolveCloudTypeCount($config, $type);
    }

    public static function buildCloudTypePlan(array $counts, array $order): array
    {
        return AiPersonaSynthesisConfig::buildCloudTypePlan($counts, $order);
    }

    public static function resolveTypeMusic($config, int $type): array
    {
        return AiPersonaSynthesisConfig::resolveTypeMusic($config, $type);
    }

    public static function resolveTypeSpeechRate($config, int $type): float
    {
        return AiPersonaSynthesisConfig::resolveTypeSpeechRate($config, $type);
    }

    /** 读取配置字段：模型实例、数组（toArray 结果）都吃 */
    public static function configField($config, string $field, $default = null)
    {
        if (is_object($config)) {
            return $config->$field ?? $default;
        }
        if (is_array($config)) {
            return $config[$field] ?? $default;
        }
        return $default;
    }
}
