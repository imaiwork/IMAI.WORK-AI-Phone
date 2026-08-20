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

    //'画面素材: 1-纯AI, 2-AI+素材库, 3-纯素材库
    const VISUAL_MATERIAL_SOURCE_AI = 1;
    const VISUAL_MATERIAL_SOURCE_AI_MATERIAL = 2;
    const VISUAL_MATERIAL_SOURCE_MATERIAL = 3;
   //'文案来源: 1-仿写, 2-AI生成, 3-无需, 4-文案库'
    const COPYWRITING_SOURCE_IMITATE = 1;
    const COPYWRITING_SOURCE_AI = 2;
    const COPYWRITING_SOURCE_NONE = 3;
    const COPYWRITING_SOURCE_LIBRARY = 4;
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

    public function getPicAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setPicAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
