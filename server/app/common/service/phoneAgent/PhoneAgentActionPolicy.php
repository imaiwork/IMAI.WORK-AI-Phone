<?php

namespace app\common\service\phoneAgent;

class PhoneAgentActionPolicy
{
    private const ALLOWED_ACTIONS = [
        'launch',
        'tap',
        'double_tap',
        'long_press',
        'type',
        'swipe',
        'wait',
        'back',
        'home',
        'take_over',
        'finish',
    ];

    public static function isAllowed(string $actionType): bool
    {
        return in_array($actionType, self::ALLOWED_ACTIONS, true);
    }
}
