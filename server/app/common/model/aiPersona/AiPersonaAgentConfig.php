<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;

class AiPersonaAgentConfig extends BaseModel
{
    public const WECHAT_PLATFORM_TYPE = 1;
    public const PLATFORM_ACCOUNT_TYPES = [1, 3, 4, 5];

    public static function getDefaultPlatformAgentConfig(): array
    {
        $config = [];
        foreach (self::PLATFORM_ACCOUNT_TYPES as $accountType) {
            $config[$accountType] = [
                'dm' => self::getDefaultPlatformReplyItem(),
                'comment' => self::getDefaultPlatformReplyItem(true),
            ];
        }
        return $config;
    }

    private static function getDefaultPlatformReplyItem(bool $withCommentOnlyLike = false): array
    {
        $item = [
            'type' => 1,
            'agent_id' => 0,
            'speech' => [],
        ];

        if ($withCommentOnlyLike) {
            $item['comment_only_like'] = 0;
        }

        return $item;
    }

    public static function getPlatformAgentConfigByAgentIds(int $dmAgentId, int $commentAgentId, int $wechatChatAgentId = 0): array
    {
        $config = self::getDefaultPlatformAgentConfig();
        foreach (self::PLATFORM_ACCOUNT_TYPES as $accountType) {
            $config[$accountType]['dm'] = [
                'type' => 1,
                'agent_id' => $accountType === self::WECHAT_PLATFORM_TYPE && $wechatChatAgentId > 0 ? $wechatChatAgentId : $dmAgentId,
                'speech' => [],
            ];
            $config[$accountType]['comment'] = [
                'type' => 1,
                'agent_id' => $commentAgentId,
                'speech' => [],
                'comment_only_like' => 0,
            ];
        }
        return $config;
    }

    public static function syncAutoCreatedAgentConfig(int $userId, int $personaId, array $agentIds): void
    {
        $agentConfig = self::where([
            'user_id' => $userId,
            'persona_id' => $personaId,
        ])->findOrEmpty();

        if ($agentConfig->isEmpty()) {
            self::create(self::getAutoCreatedAgentConfigData($userId, $personaId, $agentIds));
            return;
        }

        $agentConfig->save(self::getAutoCreatedAgentConfigUpdateData($agentIds));
    }

    public static function getAutoCreatedAgentConfigData(int $userId, int $personaId, array $agentIds): array
    {
        return array_replace(
            self::getDefaultConfigData($userId, $personaId),
            self::getAutoCreatedAgentConfigUpdateData($agentIds)
        );
    }

    public static function getAutoCreatedAgentConfigUpdateData(array $agentIds): array
    {
        $commentAgentId = (int)($agentIds[0] ?? 0);
        $dmAgentId = (int)($agentIds[1] ?? 0);
        $momentsAgentId = (int)($agentIds[2] ?? 0);
        $wechatChatAgentId = (int)($agentIds[3] ?? 0);

        return [
            'comment_enabled' => 1,
            'comment_agent_id' => $commentAgentId,
            'dm_enabled' => 1,
            'dm_agent_id' => $dmAgentId,
            'wechat_chat_enabled' => 1,
            'wechat_chat_agent_id' => $wechatChatAgentId,
            'moments_enabled' => 1,
            'moments_agent_id' => $momentsAgentId,
            'platform_agent_config' => self::getPlatformAgentConfigByAgentIds($dmAgentId, $commentAgentId, $wechatChatAgentId),
            'update_time' => time(),
        ];
    }

    public static function getDefaultConfigData(int $userId, int $personaId): array
    {
        return [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'comment_enabled' => 0,
            'comment_agent_id' => 0,
            'comment_type' => 1,
            'comment_speech' => [],
            'dm_enabled' => 0,
            'dm_agent_id' => 0,
            'dm_type' => 1,
            'dm_speech' => [],
            'wechat_chat_enabled' => 0,
            'wechat_chat_agent_id' => 0,
            'wechat_chat_type' => 1,
            'wechat_chat_speech' => [],
            'moments_enabled' => 0,
            'moments_agent_id' => 0,
            'moments_action' => 3,
            'moments_type' => 1,
            'moments_speech' => [],
            'shutoff_comment_type' => 1,
            'shutoff_comment_agent_id' => 0,
            'shutoff_comment_speech' => [],
            'shutoff_msg_type' => 1,
            'shutoff_msg_agent_id' => 0,
            'shutoff_msg_speech' => [],
            'platform_agent_config' => self::getDefaultPlatformAgentConfig(),
            'create_time' => time(),
            'update_time' => time(),
        ];
    }

    public static function normalizePlatformAgentConfig($config, bool $withDefault = true): array
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($config)) {
            $config = [];
        }

        $result = $withDefault ? self::getDefaultPlatformAgentConfig() : [];
        foreach (self::PLATFORM_ACCOUNT_TYPES as $accountType) {
            $key = (string)$accountType;
            if (!array_key_exists($key, $config) && !array_key_exists($accountType, $config)) {
                continue;
            }
            $item = $config[$key] ?? $config[$accountType];
            $normalized = self::normalizePlatformAgentItem($item, !$withDefault);
            if ($withDefault) {
                $result[$accountType] = array_replace_recursive($result[$accountType], $normalized);
            } else {
                $result[$accountType] = $normalized;
            }
        }

        return $result;
    }

    public static function mergePlatformAgentConfig($current, $incoming): array
    {
        $result = self::normalizePlatformAgentConfig($current, true);
        $incoming = self::normalizePlatformAgentConfig($incoming, false);

        foreach ($incoming as $accountType => $item) {
            $accountType = (int)$accountType;
            foreach (['dm', 'comment'] as $scene) {
                if (isset($item[$scene])) {
                    $result[$accountType][$scene] = $item[$scene];
                }
            }
        }

        return $result;
    }

    public function getSocialReplyConfig(int $accountType, string $scene = 'dm'): array
    {
        $scene = $scene === 'comment' ? 'comment' : 'dm';
        $platformConfig = self::normalizePlatformAgentConfig($this->platform_agent_config, false);

        if (isset($platformConfig[$accountType][$scene]) && self::hasEffectivePlatformAgentConfig($platformConfig[$accountType][$scene], $scene)) {
            $item = $platformConfig[$accountType][$scene];
            $type = $scene === 'comment' && (int)($item['comment_only_like'] ?? 0) === 1 ? 3 : (int)$item['type'];
            return [
                'enabled' => (int)($scene === 'comment' ? $this->comment_enabled : $this->dm_enabled) === 1,
                'type' => $type,
                'agent_id' => $type === 3 ? 0 : (int)$item['agent_id'],
                'speech' => $type === 3 ? [] : $item['speech'],
                'comment_only_like' => (int)($item['comment_only_like'] ?? 0),
                'source' => 'platform',
            ];
        }

        return $this->getLegacySocialReplyConfig($accountType, $scene);
    }

    public function hasAnyEffectivePlatformAgentConfig(): bool
    {
        $platformConfig = self::normalizePlatformAgentConfig($this->platform_agent_config, false);
        foreach ($platformConfig as $platformItem) {
            foreach (['dm', 'comment'] as $scene) {
                if (isset($platformItem[$scene]) && self::hasEffectivePlatformAgentConfig($platformItem[$scene], $scene)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function normalizePlatformAgentItem($item, bool $onlyProvidedScenes = false): array
    {
        $item = is_array($item) ? $item : [];

        if (array_key_exists('dm', $item) || array_key_exists('comment', $item)) {
            $result = [];
            if (!$onlyProvidedScenes || array_key_exists('dm', $item)) {
                $result['dm'] = self::normalizePlatformReplyItem($item['dm'] ?? [], 'dm');
            }
            if (!$onlyProvidedScenes || array_key_exists('comment', $item)) {
                $result['comment'] = self::normalizePlatformReplyItem($item['comment'] ?? [], 'comment');
            }
            return $result;
        }

        $dm = self::normalizePlatformReplyItem($item, 'dm');
        $comment = self::normalizePlatformReplyItem($item, 'comment');
        return [
            'dm' => $dm,
            'comment' => $comment,
        ];
    }

    private static function normalizePlatformReplyItem($item, string $scene): array
    {
        $item = is_array($item) ? $item : [];
        $type = (int)($item['type'] ?? 1);
        if (!in_array($type, [1, 2], true)) {
            $type = 1;
        }

        $result = [
            'type' => $type,
            'agent_id' => (int)($item['agent_id'] ?? $item['agentId'] ?? 0),
            'speech' => self::normalizeSpeech($item['speech'] ?? []),
        ];

        if ($scene === 'comment') {
            $result['comment_only_like'] = empty($item['comment_only_like']) ? 0 : 1;
        }

        return $result;
    }

    private static function normalizeSpeech($speech): array
    {
        if (is_string($speech)) {
            $decoded = json_decode($speech, true);
            $speech = is_array($decoded) ? $decoded : [$speech];
        }

        if (!is_array($speech)) {
            return [];
        }

        $result = [];
        foreach ($speech as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return $result;
    }

    private static function hasEffectivePlatformAgentConfig(array $item, string $scene): bool
    {
        return ($scene === 'comment' && (int)($item['comment_only_like'] ?? 0) === 1)
            || ((int)($item['type'] ?? 1) === 1 && (int)($item['agent_id'] ?? 0) > 0)
            || ((int)($item['type'] ?? 1) === 2 && !empty($item['speech']));
    }

    private function getLegacySocialReplyConfig(int $accountType, string $scene): array
    {
        if ($scene === 'comment') {
            return [
                'enabled' => (int)$this->comment_enabled === 1,
                'type' => (int)$this->comment_type,
                'agent_id' => (int)$this->comment_agent_id,
                'speech' => $this->comment_speech,
                'comment_only_like' => (int)$this->comment_type === 3 ? 1 : 0,
                'source' => 'legacy',
            ];
        }

        if ($accountType === 1) {
            return [
                'enabled' => (int)$this->wechat_chat_enabled === 1,
                'type' => (int)$this->wechat_chat_type,
                'agent_id' => (int)$this->wechat_chat_agent_id,
                'speech' => $this->wechat_chat_speech,
                'comment_only_like' => 0,
                'source' => 'legacy',
            ];
        }

        return [
            'enabled' => (int)$this->dm_enabled === 1,
            'type' => (int)$this->dm_type,
            'agent_id' => (int)$this->dm_agent_id,
            'speech' => $this->dm_speech,
            'comment_only_like' => 0,
            'source' => 'legacy',
        ];
    }

    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                1 => [
                    '07:10-07:30,2',
                    '07:40-07:50,2',
                    '09:00-09:30,2',
                    '10:30-11:00,2',
                    '14:00-14:30,2',
                    '22:45-23:30,2'
                ],
                3 => [
                    '06:00-06:10,2',
                    '13:10-13:20,2',
                    '17:30-17:40,2',
                    '20:00-20:10,2',
                    '21:10-21:20,2',

                    '06:10-06:20,1',
                    '13:20-13:30,1',
                    '17:40-17:50,1',
                    '20:10-20:20,1',
                    '21:20-21:30,1'
                ],
                4 => [
                    '06:20-06:30,2',
                    '17:10-17:20,2',
                    '19:15-19:30,2',
                    '20:40-20:50,2',

                    '06:30-06:40,1',
                    '17:20-17:30,1',
                    '19:30-19:40,1',
                    '20:50-21:00,1'
                ],
                5 => [
                    '06:40-06:50,2',
                    '17:50-18:00,2',
                    '21:40-21:50,2',

                    '06:50-07:00,1',
                    '18:00-18:10,1',
                    '21:50-22:00,1'
                ],
            ],
            2 => [
                1 => [
                    '09:00-09:30,2',
                    '14:30-14:45,2',
                    '16:15-16:30,2',
                    '17:30-18:00,2',
                    '21:00-21:30,2',
                    '21:40-23:00,2'
                ],
                3 => [
                    '12:30-12:50,2',
                    '17:00-17:15,2',
                    '20:30-20:45,2',

                    '12:50-13:00,1',
                    '17:15-17:30,1',
                    '20:15-20:30,1',
                    '20:45-21:00,1'
                ],
                4 => [
                    '11:00-11:20,2',
                    '15:20-15:35,2',
                    '20:00-20:15,2',

                    '11:20-11:30,1',
                    '15:35-15:45,1'
                ]
            ],
            3 => [
                1 => [
                    '09:00-09:15,2',
                    '11:00-11:15,2',
                    '12:30-13:15,2',
                ],
                3 => [
                    '11:45-12:00,2',
                    '13:50-14:00,2',
                    '17:20-17:30,2',
                    '19:30-19:45,2',
                    '20:50-21:00,2',

                    '12:00-12:15,1',
                    '14:00-14:10,1',
                    '17:30-17:40,1',
                    '19:45-20:00,1',
                    '21:00-21:10,1'
                ],
                4 => [
                    '10:30-10:45,2',
                    '13:30-13:40,2',
                    '14:45-15:30,2',
                    '17:00-17:10,2',
                    '18:30-18:40,2',
                    '20:30-20:40,2',

                    '10:45-11:00,1',
                    '13:40-13:50,1',
                    '15:30-15:45,1',
                    '17:10-17:20,1',
                    '18:40-18:50,1',
                    '20:40-20:50,1'
                ],
                5 => [
                    '21:10-21:20,2',

                    '21:20-21:30,1'
                ],
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }


    public static function getSphTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                1 => [
                    '07:00-07:10,1',
                    '18:10-18:20,1',
                    '22:00-22:10,1'
                ]
            ],
            3 => [
                1 => [
                    '14:10-14:20,1',
                    '17:40-17:50,1',
                    '21:30-21:40,1'
                ]
            ],
        ];
        return $maps[$personaType][$accountType] ?? [];
    }

    public function setCommentSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCommentSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setDmSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getDmSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setWechatChatSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getWechatChatSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setMomentsSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMomentsSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setShutoffCommentSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getShutoffCommentSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setShutoffMsgSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getShutoffMsgSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setPlatformAgentConfigAttr($value)
    {
        return is_array($value) ? json_encode(self::normalizePlatformAgentConfig($value), JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getPlatformAgentConfigAttr($value)
    {
        return self::normalizePlatformAgentConfig($value);
    }
}
