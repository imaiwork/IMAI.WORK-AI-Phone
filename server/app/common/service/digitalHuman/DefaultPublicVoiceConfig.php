<?php

namespace app\common\service\digitalHuman;

class DefaultPublicVoiceConfig
{
    public static function get(): array
    {
        static $config = null;
        if ($config === null) {
            $all = require root_path() . 'config/api_tools.php';
            $config = $all['default_public_voice'] ?? [];
        }
        return $config;
    }

    public static function isEnabledForPersonaType(int $personaType): bool
    {
        $config = self::get();
        if (empty($config['enabled'])) {
            return false;
        }
        $types = $config['persona_types'] ?? [];
        return in_array($personaType, $types, true);
    }
}
