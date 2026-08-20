<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;

class AiPersonaOptionService
{
    public static function defaultOptions(): array
    {
        return [
            'hot_words' => 1,
            'video_clip' => 1,
            'content_publish' => 1,
            'customer_service' => 1,
            'auto_clues' => [
                'status' => 1,
                'options' => [
                    'sph_clues' => 1,
                    'video_shutoff' => 1,
                    'group_clues' => 1,
                    'city_clues' => 1,
                ],
            ],
            'private_operation' => [
                'status' => 1,
                'options' => [
                    'add_friend' => 1,
                    'auto_add_group' => 1,
                    'circle_config' => 1,
                ],
            ],
        ];
    }

    public static function normalize($options = null): array
    {
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            $options = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        if (!is_array($options) || empty($options)) {
            return self::defaultOptions();
        }

        return self::mergeDefaults(self::defaultOptions(), $options);
    }

    public static function getOptionsByPersonaId(int $personaId): array
    {
        if ($personaId <= 0) {
            return self::defaultOptions();
        }

        $persona = AiPersona::where(['id' => $personaId, 'delete_time' => null])->findOrEmpty();
        if ($persona->isEmpty()) {
            return self::defaultOptions();
        }

        return self::getOptionsByPersona($persona);
    }

    public static function getOptionsByPersona(AiPersona $persona): array
    {
        $personaRule = self::getPersonaRuleModel($persona);
        if (!$personaRule || $personaRule->isEmpty()) {
            return self::defaultOptions();
        }

        return self::normalize($personaRule->global_option ?? null);
    }

    public static function isEnabledForPersonaId(int $personaId, string $path): bool
    {
        return self::isEnabled(self::getOptionsByPersonaId($personaId), $path);
    }

    public static function isEnabled(array $options, string $path): bool
    {
        $options = self::normalize($options);

        if (strpos($path, 'auto_clues.options.') === 0 && !self::toBool($options['auto_clues']['status'] ?? 1)) {
            return false;
        }

        if (strpos($path, 'private_operation.options.') === 0 && !self::toBool($options['private_operation']['status'] ?? 1)) {
            return false;
        }

        return self::toBool(self::getByPath($options, $path));
    }

    private static function mergeDefaults(array $defaults, array $options): array
    {
        $normalized = $defaults;

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $defaults) && is_array($defaults[$key])) {
                $normalized[$key] = is_array($value)
                    ? self::mergeDefaults($defaults[$key], $value)
                    : $defaults[$key];
                continue;
            }

            if (array_key_exists($key, $defaults)) {
                $normalized[$key] = self::normalizeSwitchValue($value, $defaults[$key]);
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private static function normalizeSwitchValue(mixed $value, mixed $default): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int)$value === 0 ? 0 : 1;
        }

        return (int)$default === 0 ? 0 : 1;
    }

    private static function getByPath(array $options, string $path)
    {
        $value = $options;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return 1;
            }
            $value = $value[$key];
        }

        return $value;
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value !== 0;
        }

        return true;
    }

    private static function getPersonaRuleModel(AiPersona $persona)
    {
        $where = [
            'persona_id' => (int)$persona->id,
            'user_id' => (int)$persona->user_id,
            'delete_time' => null,
        ];

        return match ((int)$persona->persona_type) {
            1 => AiPersonaIndividual::where($where)->findOrEmpty(),
            2 => AiPersonaEnterprise::where($where)->findOrEmpty(),
            3 => AiPersonaLocal::where($where)->findOrEmpty(),
            default => null,
        };
    }
}
