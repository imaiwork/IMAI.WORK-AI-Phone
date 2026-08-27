<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaSynthesisConfig extends BaseModel
{
    protected $name = 'ai_persona_synthesis_config';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    //画面素材: 1-纯AI, 2-AI+素材库, 3-纯素材库
    const VISUAL_MATERIAL_SOURCE_AI = 1;
    const VISUAL_MATERIAL_SOURCE_AI_MATERIAL = 2;
    const VISUAL_MATERIAL_SOURCE_MATERIAL = 3;

    //文案来源: 1-仿写, 2-AI生成, 3-无需, 4-文案库
    const COPYWRITING_SOURCE_IMITATE = 1;
    const COPYWRITING_SOURCE_AI = 2;
    const COPYWRITING_SOURCE_NONE = 3;
    const COPYWRITING_SOURCE_LIBRARY = 4;

    //文案库使用方式: 1-随机, 2-顺序
    const LIBRARY_USE_MODE_RANDOM = 1;
    const LIBRARY_USE_MODE_SEQUENCE = 2;

    //文案库重复规则: 1-每条仅用一次, 2-可重复使用
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

    //视频封面: 1-默认, 2-AI自动, 3-手动
    const VIDEO_COVER_TYPE_DEFAULT = 1;
    const VIDEO_COVER_TYPE_AI = 2;
    const VIDEO_COVER_TYPE_MANUAL = 3;
    const NEWS_MIXCUT_DURATION_DEFAULT = 10;
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

    protected $json = ['generation_types', 'template_config'];
    protected $jsonAssoc = true;

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

    public static function copywritingGenerationTypeMap(): array
    {
        return [
            self::COPYWRITING_GENERATION_TYPE_KNOWLEDGE => [
                'label' => '干货科普',
                'desc' => '把卖点讲成知识点，先给结论再讲原因，适合建立专业信任。',
            ],
            self::COPYWRITING_GENERATION_TYPE_SELL => [
                'label' => '带货种草',
                'desc' => '突出使用场景与前后对比，结尾直接引导下单或进店。',
            ],
            self::COPYWRITING_GENERATION_TYPE_OPINION => [
                'label' => '观点评论',
                'desc' => '对行业现象亮明态度，争议感强，评论区互动率高。',
            ],
            self::COPYWRITING_GENERATION_TYPE_SKIT => [
                'label' => '剧情段子',
                'desc' => '小剧情加反转带出产品，完播率高，适合泛流量涨粉。',
            ],
            self::COPYWRITING_GENERATION_TYPE_EMOTION => [
                'label' => '情感共鸣',
                'desc' => '讲人和故事，唤起共鸣后自然带出品牌，转化路径更软。',
            ],
            self::COPYWRITING_GENERATION_TYPE_PITFALL => [
                'label' => '揭秘避坑',
                'desc' => '用「内行人才知道」的口吻拆行业内幕，信任感与收藏率高。',
            ],
            self::COPYWRITING_GENERATION_TYPE_CUSTOM => [
                'label' => '自定义',
                'desc' => '',
            ],
        ];
    }

    public static function normalizeCopywritingGenerationType($value): int
    {
        $value = (int)$value;
        return array_key_exists($value, self::copywritingGenerationTypeMap())
            ? $value
            : self::COPYWRITING_GENERATION_TYPE_KNOWLEDGE;
    }

    public static function normalizeCopywritingGenerationCustom($value): string
    {
        $value = trim((string)$value);
        if (mb_strlen($value, 'UTF-8') > self::COPYWRITING_GENERATION_CUSTOM_MAX) {
            return mb_substr($value, 0, self::COPYWRITING_GENERATION_CUSTOM_MAX, 'UTF-8');
        }
        return $value;
    }

    public static function buildCopywritingGenerationVoice(int $type, string $custom): string
    {
        $type = self::normalizeCopywritingGenerationType($type);
        if ($type === self::COPYWRITING_GENERATION_TYPE_CUSTOM) {
            return self::normalizeCopywritingGenerationCustom($custom);
        }
        return (string)(self::copywritingGenerationTypeMap()[$type]['label'] ?? '');
    }
}
