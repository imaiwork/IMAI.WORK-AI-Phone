<?php

namespace app\common\service\phoneAgent;

use think\facade\Cache;

class PhoneAgentHintService
{
    private const CACHE_TTL = 1800;
    private const MAX_PARSE_FAIL_STREAK = 3;

    public static function takeNextUserHint(string $taskId): string
    {
        $taskId = trim($taskId);
        if ($taskId === '') {
            return '';
        }

        $state = self::readState($taskId);
        $hint = trim((string)($state['next_user_hint'] ?? ''));
        if ($hint === '') {
            return '';
        }

        $state['next_user_hint'] = '';
        self::writeState($taskId, $state);
        return $hint;
    }

    public static function getParseFailStreak(string $taskId): int
    {
        $state = self::readState($taskId);
        return max(0, (int)($state['parse_fail_streak'] ?? 0));
    }

    public static function recordParseFailure(string $taskId, string $parseError, string $rawSnippet): array
    {
        $taskId = trim($taskId);
        $state = self::readState($taskId);
        $streak = max(0, (int)($state['parse_fail_streak'] ?? 0)) + 1;
        $state['parse_fail_streak'] = $streak;
        $state['next_user_hint'] = self::buildParseFailureHint($parseError, $rawSnippet);
        self::writeState($taskId, $state);

        return [
            'streak' => $streak,
            'should_fail' => $streak >= self::MAX_PARSE_FAIL_STREAK,
            'hint' => (string)$state['next_user_hint'],
        ];
    }

    public static function resetParseFailStreak(string $taskId): void
    {
        $taskId = trim($taskId);
        if ($taskId === '') {
            return;
        }

        $state = self::readState($taskId);
        $state['parse_fail_streak'] = 0;
        self::writeState($taskId, $state);
    }

    public static function setLaunchContinueHint(string $taskId): void
    {
        $taskId = trim($taskId);
        if ($taskId === '') {
            return;
        }

        $state = self::readState($taskId);
        $state['next_user_hint'] = '【系统】应用已启动，任务尚未完成。请根据当前截图在应用内继续操作（Tap 搜索、Type 关键词、Swipe 等），不要输出空 message 的 finish 或 {"_metadata":"finish"}。';
        self::writeState($taskId, $state);
    }

    public static function setCustomHint(string $taskId, string $hint): void
    {
        $taskId = trim($taskId);
        $hint = trim($hint);
        if ($taskId === '' || $hint === '') {
            return;
        }

        $state = self::readState($taskId);
        $state['next_user_hint'] = $hint;
        self::writeState($taskId, $state);
    }

    public static function setBareCoordinateHint(string $taskId): void
    {
        self::setCustomHint(
            $taskId,
            '【系统】上一步仅返回坐标数组，任务尚未完成。必须输出：'
            . 'do(action="Tap", element=[x,y]) 或 do(action="Type", text="...")，'
            . '格式为 <think>...</think><answer>do(...)</answer>'
        );
    }

    public static function evaluateRepeatTap(string $taskId, int $x, int $y, string $currentApp): array
    {
        $taskId = trim($taskId);
        if ($taskId === '') {
            return ['streak' => 0, 'should_hint' => false, 'should_fail' => false];
        }

        $state = self::readState($taskId);
        $lastPoint = $state['repeat_tap_point'] ?? null;
        $lastApp = (string)($state['repeat_tap_app'] ?? '');
        $streak = max(0, (int)($state['repeat_tap_streak'] ?? 0));

        $samePoint = is_array($lastPoint)
            && count($lastPoint) >= 2
            && (int)$lastPoint[0] === $x
            && (int)$lastPoint[1] === $y;
        $sameApp = $lastApp === $currentApp;

        if ($samePoint && $sameApp) {
            $streak++;
        } else {
            $streak = 1;
        }

        $state['repeat_tap_point'] = [$x, $y];
        $state['repeat_tap_app'] = $currentApp;
        $state['repeat_tap_streak'] = $streak;
        self::writeState($taskId, $state);

        return [
            'streak' => $streak,
            'should_hint' => $streak >= 2 && $streak <= 3,
            'should_fail' => $streak > 3,
        ];
    }

    public static function clear(string $taskId): void
    {
        $taskId = trim($taskId);
        if ($taskId === '') {
            return;
        }

        try {
            Cache::delete(self::cacheKey($taskId));
        } catch (\Throwable) {
        }
    }

    private static function buildParseFailureHint(string $parseError, string $rawSnippet): string
    {
        $snippet = trim($rawSnippet);
        if (function_exists('mb_substr') && mb_strlen($snippet) > 300) {
            $snippet = mb_substr($snippet, 0, 300);
        } elseif (strlen($snippet) > 300) {
            $snippet = substr($snippet, 0, 300);
        }

        return '【系统】上一步输出无法解析，任务未完成。'
            . '必须输出一行：do(action="Tap", element=[x,y]) 或 do(action="Type", text="...")，'
            . '不要用 JSON、不要空 finish。'
            . ' 失败原因: ' . trim($parseError)
            . '；片段: ' . $snippet;
    }

    private static function readState(string $taskId): array
    {
        try {
            $state = Cache::get(self::cacheKey($taskId));
            return is_array($state) ? $state : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function writeState(string $taskId, array $state): void
    {
        try {
            Cache::set(self::cacheKey($taskId), $state, self::CACHE_TTL);
        } catch (\Throwable) {
        }
    }

    private static function cacheKey(string $taskId): string
    {
        return 'phone_agent:hint:' . $taskId;
    }
}
