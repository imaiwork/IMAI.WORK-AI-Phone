<?php

namespace app\common\service\phoneAgent;

use app\common\service\ToolsService;

class PhoneAgentModelRequestService
{
    private const DEFAULT_MODEL = 'autoglm-phone';

    public static function buildRequest(string $model, array $messages): array
    {
        return [
            'model' => trim($model) !== '' ? trim($model) : self::DEFAULT_MODEL,
            'messages' => $messages,
            'stream' => false,
            'thinking' => [
                'type' => 'enabled',
                'clear_thinking' => true,
            ],
            'do_sample' => true,
            'temperature' => 0.8,
            'top_p' => 0.6,
        ];
    }

    public static function call(array $request): array
    {
        return ToolsService::AutoGlm()->phone($request);
    }
}
