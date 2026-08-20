<?php

namespace app\common\service\chat;

class ChatModelDisplayNameResolver
{
    private const DOMESTIC_PATTERN = '/^(qwen|deepseek|ernie|glm|kimi|doubao|minimax|step-|grok|baidu|cc-|coding-|aihub|hy)/i';

    /**
     * 复用站长端内置模型图标；未匹配到的渠道留空。
     */
    public static function resolveLogo(string $channel, string $alias = ''): string
    {
        $channel = strtolower(trim($channel));
        $aliasLower = strtolower(trim($alias));

        if ($channel === 'claude' || str_starts_with($aliasLower, 'claude-')) {
            return 'static/images/models/4.png';
        }
        if ($channel === 'openai' || str_starts_with($aliasLower, 'gpt-')) {
            return 'static/images/models/3.png';
        }
        if ($channel === 'google' || preg_match('/^(gemini-|gemma-)/', $aliasLower)) {
            return 'static/images/models/2.png';
        }

        return '';
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    public static function resolve(string $alias): array
    {
        $alias = trim($alias);
        if ($alias === '') {
            return self::fallback($alias);
        }

        $standards = self::getStandards();
        if (isset($standards[$alias]) && is_array($standards[$alias])) {
            return self::normalizeEntry($standards[$alias], $alias);
        }

        $lowerAlias = strtolower($alias);
        foreach ($standards as $key => $entry) {
            if (strtolower((string)$key) === $lowerAlias && is_array($entry)) {
                return self::normalizeEntry($entry, $alias);
            }
        }

        if (preg_match('/^gpt-/i', $alias)) {
            return self::resolveGpt($alias);
        }

        if (preg_match('/^claude-/i', $alias)) {
            return self::resolveClaude($alias);
        }

        if (preg_match('/^(gemini-|gemma-)/i', $alias)) {
            return self::resolveGoogle($alias);
        }

        if (preg_match(self::DOMESTIC_PATTERN, $alias)) {
            return self::resolveDomestic($alias);
        }

        return self::fallback($alias);
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function normalizeEntry(array $entry, string $alias): array
    {
        $modelName = trim((string)($entry['model_name'] ?? ''));
        $costName = trim((string)($entry['cost_name'] ?? ''));
        $channel = trim((string)($entry['channel'] ?? ''));

        if ($modelName === '') {
            $modelName = $costName !== '' ? $costName : $alias;
        }
        if ($costName === '') {
            $costName = $modelName;
        }
        if ($channel === '') {
            $channel = 'IMAI';
        }

        return [
            'model_name' => $modelName,
            'cost_name'  => $costName,
            'channel'    => $channel,
        ];
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function resolveGpt(string $alias): array
    {
        $special = [
            'gpt-4'      => 'Ai-4.0',
            'gpt-5'      => 'Ai-5.0',
            'gpt-5-mini' => 'Ai-5.0-mini',
        ];

        $lowerAlias = strtolower($alias);
        if (isset($special[$lowerAlias])) {
            $name = $special[$lowerAlias];
            return [
                'model_name' => $name,
                'cost_name'  => $name,
                'channel'    => 'openai',
            ];
        }

        $name = preg_replace('/^gpt-/i', 'Ai-', $alias);
        return [
            'model_name' => $name,
            'cost_name'  => $name,
            'channel'    => 'openai',
        ];
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function resolveClaude(string $alias): array
    {
        $suffix = preg_replace('/^claude-(?:sonnet|opus)-/i', '', $alias);
        $suffix = preg_replace('/^(\d+)-(\d+)/', '$1.$2', $suffix, 1);
        $name = '克洛德' . $suffix;

        return [
            'model_name' => $name,
            'cost_name'  => $name,
            'channel'    => 'claude',
        ];
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function resolveGoogle(string $alias): array
    {
        if (preg_match('/^gemini-([\d\.]+)-(.+)$/i', $alias, $matches)) {
            $version = $matches[1];
            $variant = strtoupper(str_replace('-', ' ', $matches[2]));
            $name = '谷歌智元' . $version . ' ' . $variant;

            return [
                'model_name' => $name,
                'cost_name'  => $name,
                'channel'    => 'google',
            ];
        }

        if (preg_match('/^gemma-(\d+)/i', $alias, $matches)) {
            $name = '谷歌智元' . $matches[1] . '.0';

            return [
                'model_name' => $name,
                'cost_name'  => $name,
                'channel'    => 'google',
            ];
        }

        $name = '谷歌智元-' . $alias;

        return [
            'model_name' => $name,
            'cost_name'  => $name,
            'channel'    => 'google',
        ];
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function resolveDomestic(string $alias): array
    {
        $channel = 'IMAI';
        if (preg_match('/^qwen/i', $alias)) {
            $channel = 'qwen';
        } elseif (preg_match('/^deepseek/i', $alias)) {
            $channel = 'IMAI';
        }

        return [
            'model_name' => self::capitalizeDomesticName($alias),
            'cost_name'  => $alias,
            'channel'    => $channel,
        ];
    }

    /**
     * @return array{model_name: string, cost_name: string, channel: string}
     */
    private static function fallback(string $alias): array
    {
        return [
            'model_name' => $alias,
            'cost_name'  => $alias,
            'channel'    => 'IMAI',
        ];
    }

    private static function capitalizeDomesticName(string $alias): string
    {
        if (strtolower($alias) === 'deepseek') {
            return 'DeepSeek';
        }

        if (str_contains($alias, '/')) {
            $parts = explode('/', $alias);
            $last = array_pop($parts);
            return $last !== '' ? $last : $alias;
        }

        if (preg_match('/^[a-z]/', $alias)) {
            return ucfirst($alias);
        }

        return $alias;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function getStandards(): array
    {
        static $standards = null;
        if (is_array($standards)) {
            return $standards;
        }

        try {
            $configured = config('ai.chat_model_standards');
            if (is_array($configured) && $configured !== []) {
                $standards = $configured;
                return $standards;
            }
        } catch (\Throwable) {
        }

        $path = dirname(__DIR__, 4) . '/config/ai/chat_model_standards.php';
        $standards = is_file($path) ? (require $path) : [];

        return is_array($standards) ? $standards : [];
    }
}
