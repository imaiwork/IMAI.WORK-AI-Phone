<?php

namespace app\common\service;

use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use app\common\model\ModelConfig;

class DigitalHumanModelConfigService
{
    private const V1_MODEL_VERSION = 7;
    private const V1_MODEL_ID = 7;
    private const V1_SCENE_NAMES = [
        'human_avatar_chanjing' => '数字人形象',
        'human_voice_chanjing' => '数字人音色',
        'human_audio_chanjing' => '数字人音频',
        'human_video_chanjing' => '数字人视频',
    ];

    public static function syncV1ModelConfigNames(?string $modelName = null): void
    {
        $modelName = trim((string)($modelName ?? ''));
        if ($modelName === '') {
            $modelName = self::getV1ModelName();
        }
        if ($modelName === '') {
            return;
        }

        foreach (self::V1_SCENE_NAMES as $scene => $prefix) {
            ModelConfig::where('scene', $scene)->update([
                'name' => $prefix . '-' . $modelName . '通道',
            ]);
        }
    }

    private static function getV1ModelName(): string
    {
        $name = Models::where([
            'type' => ChatEnum::MODEL_TYPE_HUMAN,
            'model_version' => self::V1_MODEL_VERSION,
        ])->value('name');

        if (trim((string)$name) !== '') {
            return trim((string)$name);
        }

        return trim((string)Models::where('id', self::V1_MODEL_ID)->value('name'));
    }
}
