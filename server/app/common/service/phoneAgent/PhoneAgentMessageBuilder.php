<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentObservation;
use app\common\model\phoneAgent\PhoneAgentTask;
use think\facade\Log;

class PhoneAgentMessageBuilder
{
    private const CONTROL_PROMPT_FILE = 'prompts/autoglm_phone_zh.php';

    public static function buildSystemPrompt(): string
    {
        return self::loadControlPromptBody();
    }

    private static function loadControlPromptBody(): string
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . self::CONTROL_PROMPT_FILE;
        if (!is_file($path)) {
            throw new \RuntimeException('autoglm_phone_zh.php 不存在: ' . $path);
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $body = include $path;
        if (!is_string($body) || trim($body) === '') {
            $type = is_object($body) ? get_class($body) : gettype($body);
            throw new \RuntimeException('autoglm_phone_zh.php 返回内容无效: ' . $path . ' type=' . $type);
        }

        if (str_contains($body, '任务规划助手')) {
            throw new \RuntimeException(
                'autoglm_phone_zh.php 混入了规划 prompt，请从仓库恢复该文件并重启 workerman: ' . $path
            );
        }

        if (!str_contains($body, 'do(action="Launch"') && !str_contains($body, '你是一个智能体分析专家')) {
            $preview = function_exists('mb_substr') ? mb_substr(trim($body), 0, 120) : substr(trim($body), 0, 120);
            Log::channel('glm')->write(json_encode([
                'event' => 'invalid_control_prompt',
                'path' => $path,
                'preview' => $preview,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'error');
            throw new \RuntimeException(
                'autoglm_phone_zh.php prompt 异常，可能混入了规划 prompt: ' . $path . ' head=' . $preview
            );
        }

        return $body;
    }

    public static function buildScreenInfoString(string $currentApp): string
    {
        $json = json_encode(['current_app' => $currentApp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '{"current_app":""}';
    }

    public static function buildScreenInfo(PhoneAgentObservation $observation): array
    {
        return [
            'current_app' => (string)$observation->current_app,
        ];
    }

    public static function resolveTaskMessage(PhoneAgentTask $task): string
    {
        $executionMessage = trim((string)($task->execution_message ?? ''));
        if ($executionMessage !== '') {
            return $executionMessage;
        }

        return trim((string)$task->message);
    }

    public static function buildUserText(int $turnNo, string $taskMessage, string $screenInfoJson, ?string $hint = null): string
    {
        $screenInfoJson = trim($screenInfoJson) !== '' ? trim($screenInfoJson) : '{"current_app":""}';

        if ($turnNo <= 1) {
            $message = trim($taskMessage);
            $text = $message !== '' ? ($message . "\n\n" . $screenInfoJson) : $screenInfoJson;
        } else {
            $text = "** Screen Info **\n\n" . $screenInfoJson;
        }

        $hint = trim((string)$hint);
        if ($hint !== '') {
            $text .= "\n\n" . $hint;
        }

        return $text;
    }

    public static function createUserMessage(string $text, string $screenshotUrl = '', bool $includeImage = true): array
    {
        return [
            'role' => 'user',
            'content' => self::buildUserMessageContent($text, $screenshotUrl, $includeImage),
        ];
    }

    public static function buildHistoryUserMessage(string $userText): array
    {
        return self::stripImagesFromUserMessage([
            'role' => 'user',
            'content' => self::buildUserMessageContent($userText, '', false),
        ]);
    }

    public static function buildUserMessageContent(
        string $text,
        string $screenshotUrl = '',
        bool $includeImage = true
    ): array {
        $content = [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];

        $screenshotUrl = trim($screenshotUrl);
        if ($includeImage && $screenshotUrl !== '') {
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $screenshotUrl],
            ];
        }

        return $content;
    }

    public static function stripImagesFromUserMessage(array $message): array
    {
        $content = $message['content'] ?? '';
        if (!is_array($content)) {
            return $message;
        }

        $message['content'] = array_values(array_filter(
            $content,
            static function ($item): bool {
                return is_array($item) && ($item['type'] ?? '') === 'text';
            }
        ));

        return $message;
    }

    public static function splitModelThinkingAndAction(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['thinking' => '（无）', 'action' => ''];
        }

        if (preg_match('/<answer>\s*(.*?)\s*<\/answer>/is', $raw, $match)) {
            $action = trim((string)$match[1]);
            $thinking = trim((string)preg_replace('/<\/?redacted_thinking>/i', '', preg_replace('/<answer>.*?<\/answer>/is', '', $raw)));
            return [
                'thinking' => $thinking !== '' ? $thinking : '（无）',
                'action' => $action,
            ];
        }

        foreach (['finish(message=', 'do(action='] as $marker) {
            $pos = stripos($raw, $marker);
            if ($pos === false) {
                continue;
            }
            $thinking = trim(substr($raw, 0, $pos));
            $action = trim(substr($raw, $pos));
            return [
                'thinking' => $thinking !== '' ? $thinking : '（无）',
                'action' => $action,
            ];
        }

        $fallback = $raw;
        if (function_exists('mb_substr') && mb_strlen($fallback) > 800) {
            $fallback = mb_substr($fallback, 0, 800);
        } elseif (strlen($fallback) > 800) {
            $fallback = substr($fallback, 0, 800);
        }

        return ['thinking' => '（无）', 'action' => $fallback];
    }

    public static function normalizeActionForContext(string $action): string
    {
        $action = trim($action);
        if ($action === '') {
            return '';
        }

        $lower = strtolower($action);
        if (str_starts_with($lower, 'do(action=') || str_starts_with($lower, 'finish(message=')) {
            return $action;
        }

        if (preg_match('/^\[\s*(\d+)\s*,\s*(\d+)\s*\]$/', $action, $match)) {
            return sprintf('do(action="Tap", element=[%d, %d])', (int)$match[1], (int)$match[2]);
        }

        $decoded = json_decode($action, true);
        if (
            is_array($decoded)
            && array_keys($decoded) === [0, 1]
            && is_numeric($decoded[0])
            && is_numeric($decoded[1])
        ) {
            return sprintf('do(action="Tap", element=[%d, %d])', (int)$decoded[0], (int)$decoded[1]);
        }

        return $action;
    }

    public static function buildAssistantMessageContent(string $thinking, string $action): string
    {
        $thinking = trim($thinking) !== '' ? trim($thinking) : '（无）';
        $action = trim($action);
        if ($action === '') {
            return '';
        }

        return '<think>' . $thinking . '</think><answer>' . $action . '</answer>';
    }

    public static function createAssistantContextContent(array $modelResponse): string
    {
        $raw = self::extractModelRawContent($modelResponse);
        if ($raw === '') {
            return '';
        }

        $parts = self::splitModelThinkingAndAction($raw);
        $action = trim($parts['action']);
        if ($action === '') {
            $action = $raw;
            if (function_exists('mb_substr') && mb_strlen($action) > 800) {
                $action = mb_substr($action, 0, 800);
            } elseif (strlen($action) > 800) {
                $action = substr($action, 0, 800);
            }
        }

        $action = self::normalizeActionForContext($action);

        return self::buildAssistantMessageContent($parts['thinking'], $action);
    }

    public static function buildAssistantMessage(array $modelResponse): ?array
    {
        $content = self::createAssistantContextContent($modelResponse);
        if ($content === '') {
            return null;
        }

        return [
            'role' => 'assistant',
            'content' => $content,
        ];
    }

    public static function extractModelRawContent(array $response): string
    {
        $message = $response['choices'][0]['message'] ?? [];
        if (!is_array($message)) {
            return '';
        }

        $content = $message['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return trim((string)$content);
    }

    public static function createPersistableAssistantContent(array $modelResponse): string
    {
        $raw = self::extractModelRawContent($modelResponse);
        if ($raw === '') {
            return '';
        }

        $normalized = self::normalizeActionForContext($raw);
        if (
            $normalized !== $raw
            && stripos($raw, 'do(action=') === false
            && stripos($raw, 'finish(message=') === false
        ) {
            return $normalized;
        }

        return $raw;
    }
}
