<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\shanjian\ShanjianClipTemplate;
use think\facade\Log;

class SynthesisTemplateConfigService
{
    public const MODE_AUTO = 1;
    public const MODE_CUSTOM = 2;

    public const TYPE_DIGITAL_ORAL = 1;
    public const TYPE_MATERIAL_MIXCUT = 3;
    public const TYPE_NEWS_MIXCUT = 4;

    public static function defaultConfig(): array
    {
        $config = [];
        foreach (self::typeSceneMap() as $type => $scene) {
            $config[(string)$type] = [
                'mode' => self::MODE_AUTO,
                'template_ids' => [],
            ];
        }

        return $config;
    }

    public static function typeSceneMap(): array
    {
        return [
            self::TYPE_DIGITAL_ORAL => 'virtualman',
            self::TYPE_MATERIAL_MIXCUT => 'oralMixCutting',
            self::TYPE_NEWS_MIXCUT => 'newsMixCutting',
        ];
    }

    public static function normalize($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            $value = [];
        }

        $normalized = self::defaultConfig();
        foreach (self::typeSceneMap() as $type => $scene) {
            $key = (string)$type;
            $item = $value[$key] ?? $value[$type] ?? [];
            if (!is_array($item)) {
                continue;
            }

            $mode = (int)($item['mode'] ?? self::MODE_AUTO);
            if (!in_array($mode, [self::MODE_AUTO, self::MODE_CUSTOM], true)) {
                $mode = self::MODE_AUTO;
            }

            $templateIds = $item['template_ids'] ?? [];
            if (!is_array($templateIds)) {
                $templateIds = [];
            }
            $templateIds = array_values(array_unique(array_filter(array_map(static function ($id) {
                return trim((string)$id);
            }, $templateIds), static function ($id) {
                return $id !== '';
            })));

            $normalized[$key] = [
                'mode' => $mode,
                'template_ids' => $templateIds,
            ];
        }

        return $normalized;
    }

    public static function enrich($value): array
    {
        $config = self::normalize($value);
        foreach (self::typeSceneMap() as $type => $scene) {
            $key = (string)$type;
            $config[$key]['scene'] = $scene;
            $config[$key]['selected_count'] = count($config[$key]['template_ids']);
        }

        return $config;
    }

    public static function validateForSave($value, array $enabledGenerationTypes = []): array
    {
        $config = self::normalize($value);
        $enabledGenerationTypes = array_values(array_unique(array_map('intval', $enabledGenerationTypes)));

        foreach (self::typeSceneMap() as $type => $scene) {
            $key = (string)$type;
            $item = $config[$key];
            $isEnabled = in_array((int)$type, $enabledGenerationTypes, true);

            if ((int)$item['mode'] === self::MODE_CUSTOM && $isEnabled && empty($item['template_ids'])) {
                throw new \Exception(self::typeName((int)$type) . '请至少选择1个模板');
            }

            if (empty($item['template_ids'])) {
                continue;
            }

            $validIds = ShanjianClipTemplate::whereIn('id', $item['template_ids'])
                ->where('scene', $scene)
                ->where('auto_type', 1)
                ->column('id');
            $validIds = array_values(array_map('strval', $validIds));
            $invalidIds = array_values(array_diff($item['template_ids'], $validIds));
            if (!empty($invalidIds)) {
                Log::channel('ipVideoSynthesis')->warning(self::typeName((int)$type) . '模板不存在或类型不匹配，已自动过滤：' . implode(',', $invalidIds));
                $config[$key]['template_ids'] = $validIds;
                if (empty($validIds)) {
                    throw new \Exception(self::typeName((int)$type) . '当前选择模板调整中，暂停开放');
                }
            }
        }

        return $config;
    }

    /**
     * 按人设合成配置选取模板。无配置记录时按自动模式从对应 scene 抽取。
     */
    public static function pickForPersona(int $userId, int $personaId, int $generationType, int $autoType = 1): string
    {
        $config = AiPersonaSynthesisConfig::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->findOrEmpty();
        $templateConfig = $config->isEmpty() ? [] : ($config->template_config ?? []);
        return self::pickTemplateId($templateConfig, $generationType, $autoType);
    }

    /**
     * 闪剪 add/addType3 识别的 clip 参数结构。
     */
    public static function toClipPayload(string $clipId): array
    {
        return [['clip_template_id' => trim($clipId)]];
    }

    public static function templateMode($config, int $generationType): int
    {
        $normalized = self::normalize($config);
        return (int)($normalized[(string)$generationType]['mode'] ?? self::MODE_AUTO);
    }

    public static function pickTemplateId($config, int $generationType, int $autoType = 1): string
    {
        $scene = self::sceneByType($generationType);
        if ($scene === '') {
            throw new \Exception('不支持的生成类型模板：' . $generationType);
        }

        $templateConfig = self::normalize($config);
        $item = $templateConfig[(string)$generationType] ?? [
            'mode' => self::MODE_AUTO,
            'template_ids' => [],
        ];

        if ((int)$item['mode'] === self::MODE_CUSTOM && !empty($item['template_ids'])) {
            $customIds = ShanjianClipTemplate::whereIn('id', $item['template_ids'])
                ->where('scene', $scene)
                ->where('auto_type', $autoType)
                ->column('id');
            $customIds = array_values(array_map('strval', $customIds));
            if (!empty($customIds)) {
                Log::channel('ipVideoSynthesis')->write('随机模板前候选ID：' . json_encode([
                    'mode' => 'custom',
                    'generation_type' => $generationType,
                    'scene' => $scene,
                    'auto_type' => $autoType,
                    'config_template_ids' => $item['template_ids'],
                    'candidate_template_ids' => $customIds,
                    'candidate_count' => count($customIds),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return self::randomId($customIds);
            }

            Log::channel('ipVideoSynthesis')->write('自定义视频模板已失效，降级自动随机：' . json_encode([
                'generation_type' => $generationType,
                'scene' => $scene,
                'template_ids' => $item['template_ids'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $templateIds = ShanjianClipTemplate::where('scene', $scene)
            ->where('auto_type', $autoType)
            ->column('id');
        $templateIds = array_values(array_map('strval', $templateIds));
        if (empty($templateIds)) {
            throw new \Exception(self::typeName($generationType) . '暂无可用模板');
        }

        Log::channel('ipVideoSynthesis')->write('随机模板前候选ID：' . json_encode([
            'mode' => 'auto',
            'generation_type' => $generationType,
            'scene' => $scene,
            'auto_type' => $autoType,
            'candidate_template_ids' => $templateIds,
            'candidate_count' => count($templateIds),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::randomId($templateIds);
    }

    public static function sceneByType(int $type): string
    {
        return self::typeSceneMap()[$type] ?? '';
    }

    public static function typeName(int $type): string
    {
        $names = [
            self::TYPE_DIGITAL_ORAL => '数字人口播',
            self::TYPE_MATERIAL_MIXCUT => '纯素材混剪',
            self::TYPE_NEWS_MIXCUT => '新闻体',
        ];

        return $names[$type] ?? ('生成类型' . $type);
    }

    private static function randomId(array $ids): string
    {
        return (string)$ids[random_int(0, count($ids) - 1)];
    }
}
