<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentObservation;
use app\common\model\phoneAgent\PhoneAgentTask;
use think\facade\Log;

class PhoneAgentActionParserService
{
    public static function parse(array $response): array
    {
        $message = $response['choices'][0]['message'] ?? null;
        if (!is_array($message)) {
            return ['success' => false, 'message' => '模型响应缺少 choices[0].message'];
        }

        $toolCalls = $message['tool_calls'] ?? [];
        if (is_array($toolCalls) && !empty($toolCalls)) {
            $first = $toolCalls[0];
            $function = $first['function'] ?? [];
            $actionType = (string)($function['name'] ?? ($first['name'] ?? ''));
            $arguments = $function['arguments'] ?? ($first['arguments'] ?? []);
            $params = is_array($arguments) ? $arguments : json_decode((string)$arguments, true);
            if ($actionType !== '') {
                return self::normalizeParsedAction([
                    'action_type' => $actionType,
                    'params' => is_array($params) ? $params : [],
                    'timeout' => $first['timeout'] ?? 60,
                ]);
            }
        }

        $content = $message['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $decoded = json_decode((string)$content, true);
        if (is_array($decoded)) {
            if (self::isBareCoordinatePair($decoded)) {
                Log::channel('glm')->write(json_encode([
                    'event' => 'bare_coordinate_response',
                    'parse_mode' => 'bare_coordinate',
                    'content' => (string)$content,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warning');
                $result = self::normalizeParsedAction([
                    'action_type' => 'tap',
                    'params' => [
                        'element' => [(int)$decoded[0], (int)$decoded[1]],
                        'x' => (int)$decoded[0],
                        'y' => (int)$decoded[1],
                    ],
                    'timeout' => 10,
                ]);
                if ($result['success'] ?? false) {
                    $result['bare_coordinate'] = true;
                }

                return $result;
            }
            return self::normalizeParsedAction($decoded);
        }

        $textActions = self::parseAllTextActions((string)$content);
        if ($textActions !== []) {
            return self::normalizeParsedActions($textActions);
        }
        if (self::isTruncatedFinishFragment((string)$content)) {
            Log::channel('glm')->write(json_encode([
                'event' => 'truncated_finish_fragment',
                'content' => (string)$content,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warning');
            return ['success' => false, 'message' => '模型返回疑似截断的 finish 片段'];
        }

        return ['success' => false, 'message' => '模型响应不是可解析 JSON'];
    }

    public static function shouldAcceptFinish(PhoneAgentTask $task, PhoneAgentObservation $observation, int $turnNo, string $finishMessage): array
    {
        $goal = trim((string)$task->message);
        $actionHistory = self::actionHistoryBeforeTurn((string)$task->task_id, $turnNo);
        $observationData = PhoneAgentModelContextService::compactObservationForModel($observation);

        if ($goal !== '' && str_contains($goal, '搜索')) {
            if (self::actionHistoryHasType($actionHistory)) {
                return ['accept' => true, 'reason' => ''];
            }

            $searchTerms = self::extractSearchTermsFromGoal($goal);
            if (self::observationContainsSearchEvidence($observationData, $searchTerms)) {
                return ['accept' => true, 'reason' => ''];
            }

            if (self::actionHistoryHasOnlyLaunch($actionHistory)) {
                return [
                    'accept' => false,
                    'reason' => '目标尚未完成：已打开应用但尚未执行搜索，禁止 finish',
                ];
            }
        }

        return ['accept' => true, 'reason' => ''];
    }

    private static function isBareCoordinatePair(array $payload): bool
    {
        return array_keys($payload) === [0, 1]
            && is_numeric($payload[0])
            && is_numeric($payload[1]);
    }

    private static function parseAllTextActions(string $content): array
    {
        if ($content === '') {
            return [];
        }

        if (preg_match('/<answer>\s*(.*?)\s*<\/answer>/is', $content, $match)) {
            $answerActions = self::parseAllTextActions(trim((string)$match[1]));
            if ($answerActions !== []) {
                return $answerActions;
            }
        }

        $finishCall = self::extractBalancedCall($content, 'finish(');
        if ($finishCall !== '') {
            $arguments = self::parseDirectArguments(substr($finishCall, strlen('finish('), -1), 'finish');
            return [[
                'finish' => true,
                'message' => (string)($arguments['message'] ?? '任务完成'),
            ]];
        }

        if (self::isTruncatedFinishFragment($content)) {
            return [];
        }

        $doActions = self::parseAllDoActions($content);
        if ($doActions !== []) {
            return $doActions;
        }

        $directActions = self::parseAllDirectActions($content);
        if ($directActions !== []) {
            return $directActions;
        }

        if (preg_match('/\b(Wait|Sleep)\s*\(\s*(\d+)\s*\)/i', $content, $match)) {
            $seconds = max(1, (int)$match[2]);
            return [[
                'action_type' => 'wait',
                'params' => ['seconds' => $seconds],
                'timeout' => $seconds + 5,
            ]];
        }

        if (preg_match('/\b(Tap|Click)\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)/i', $content, $match)) {
            return [[
                'action_type' => 'tap',
                'params' => [
                    'x' => (int)$match[2],
                    'y' => (int)$match[3],
                ],
                'timeout' => 10,
            ]];
        }

        if (preg_match('/\bInput\s*\(\s*[\'"](.+?)[\'"]\s*\)/is', $content, $match)) {
            return [[
                'action_type' => 'input',
                'params' => ['text' => $match[1]],
                'timeout' => 15,
            ]];
        }

        if (preg_match('/\b(Finish|Done|Complete)\s*\((.*?)\)/is', $content, $match)) {
            $arguments = self::parseDoArguments((string)$match[2]);
            return [[
                'finish' => true,
                'message' => (string)($arguments['message'] ?? 'task completed'),
            ]];
        }

        return [];
    }

    private static function parseTextAction(string $content): ?array
    {
        $actions = self::parseAllTextActions($content);
        return $actions === [] ? null : $actions[0];
    }

    private static function extractBalancedCall(string $content, string $needle): string
    {
        $start = stripos($content, $needle);
        if ($start === false) {
            return '';
        }

        $openPos = strpos($content, '(', $start);
        if ($openPos === false) {
            return '';
        }

        $depth = 0;
        $quote = '';
        $length = strlen($content);
        for ($i = $openPos; $i < $length; $i++) {
            $char = $content[$i];
            if ($quote !== '') {
                if ($char === '\\' && $i + 1 < $length) {
                    $i++;
                    continue;
                }
                if ($char === $quote) {
                    $quote = '';
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return trim(substr($content, $start, $i - $start + 1));
                }
            }
        }

        return '';
    }

    private static function isTruncatedFinishFragment(string $content): bool
    {
        $content = trim($content);
        if ($content === '') {
            return false;
        }
        if (stripos($content, 'finish(message=') !== false || stripos($content, 'finish(') !== false) {
            return false;
        }
        if (preg_match('/<answer>\s*finish/is', $content)) {
            return false;
        }

        return str_contains($content, '任务已完成')
            || str_contains($content, '任务完成')
            || (bool)preg_match('/任务已完成[)"\']?\s*\)?$/u', $content);
    }

    private static function parseAllDirectActions(string $content): array
    {
        if (!preg_match_all('/\b(Launch|Tap|Click|Type|Input|Swipe|Back|Home|Long\s+Press|LongPress|Double\s+Tap|DoubleTap|Wait|Sleep|Take_over|TakeOver|Finish|Done|Complete)\s*\((.*?)\)/is', $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $actions = [];
        foreach ($matches as $match) {
            $actionType = self::normalizeActionType((string)$match[1]);
            $arguments = self::parseDirectArguments((string)$match[2], $actionType);

            if (in_array($actionType, ['finish', 'done', 'complete', 'completed'], true)) {
                return [[
                    'finish' => true,
                    'message' => (string)($arguments['message'] ?? 'task completed'),
                ]];
            }

            $params = self::normalizeDoParams($actionType, $arguments);
            $actions[] = [
                'action_type' => $actionType,
                'params' => $params,
                'timeout' => self::resolveActionTimeout($actionType, $params),
            ];
        }

        return $actions;
    }

    private static function parseDirectAction(string $content): ?array
    {
        $actions = self::parseAllDirectActions($content);
        return $actions === [] ? null : $actions[0];
    }

    private static function parseDirectArguments(string $argumentText, string $actionType): array
    {
        $items = self::splitTopLevel($argumentText);
        $arguments = [];
        $positional = [];
        foreach ($items as $index => $item) {
            if (str_contains($item, '=')) {
                [$key, $value] = explode('=', $item, 2);
                $key = trim($key);
                if ($key !== '') {
                    $arguments[$key] = self::parseDoValue(trim($value));
                }
                continue;
            }

            $positional[$index] = self::parseDoValue(trim($item));
        }

        switch ($actionType) {
            case 'launch':
                if (isset($positional[0]) && is_scalar($positional[0])) {
                    $arguments['app_name'] = (string)$positional[0];
                }
                break;
            case 'type':
                if (isset($positional[0]) && is_scalar($positional[0])) {
                    $arguments['text'] = (string)$positional[0];
                }
                break;
            case 'wait':
                if (isset($positional[0]) && is_numeric($positional[0])) {
                    $arguments['seconds'] = (int)$positional[0];
                }
                break;
            case 'tap':
            case 'double_tap':
            case 'long_press':
                if (isset($positional[0]) && is_array($positional[0]) && count($positional[0]) >= 2) {
                    $arguments['element'] = $positional[0];
                } elseif (isset($positional[0], $positional[1]) && is_numeric($positional[0]) && is_numeric($positional[1])) {
                    $arguments['element'] = [(int)$positional[0], (int)$positional[1]];
                }
                break;
            case 'swipe':
                if (isset($positional[0], $positional[1]) && is_array($positional[0]) && is_array($positional[1])) {
                    $arguments['start'] = $positional[0];
                    $arguments['end'] = $positional[1];
                } elseif (count($positional) >= 4) {
                    $arguments['element'] = array_values(array_slice($positional, 0, 4));
                } elseif (isset($positional[0]) && is_array($positional[0])) {
                    $arguments['element'] = $positional[0];
                }
                break;
            case 'take_over':
                if (isset($positional[0]) && is_scalar($positional[0])) {
                    $arguments['reason'] = (string)$positional[0];
                }
                break;
        }

        return $arguments;
    }

    private static function parseAllDoActions(string $content): array
    {
        if (!preg_match_all('/\bdo\s*\((.*?)\)/is', $content, $matches)) {
            return [];
        }

        $actions = [];
        foreach ($matches[1] as $argumentText) {
            $arguments = self::parseDoArguments($argumentText);
            $actionType = self::normalizeActionType((string)($arguments['action'] ?? ($arguments['type'] ?? ($arguments['name'] ?? ''))));
            if ($actionType === '') {
                continue;
            }

            if (in_array($actionType, ['finish', 'done', 'complete', 'completed'], true)) {
                return [[
                    'finish' => true,
                    'message' => (string)($arguments['message'] ?? 'task completed'),
                ]];
            }

            $params = self::normalizeDoParams($actionType, $arguments);
            $actions[] = [
                'action_type' => $actionType,
                'params' => $params,
                'timeout' => self::resolveActionTimeout($actionType, $params),
            ];
        }

        return $actions;
    }

    private static function parseDoAction(string $content): ?array
    {
        $actions = self::parseAllDoActions($content);
        return $actions === [] ? null : $actions[0];
    }

    private static function parseDoArguments(string $argumentText): array
    {
        $arguments = [];
        foreach (self::splitTopLevel($argumentText) as $part) {
            if (!str_contains($part, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $part, 2);
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $arguments[$key] = self::parseDoValue(trim($value));
        }

        return $arguments;
    }

    private static function splitTopLevel(string $text): array
    {
        $items = [];
        $buffer = '';
        $depth = 0;
        $quote = '';
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            if ($quote !== '') {
                $buffer .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $buffer .= $text[++$i];
                    continue;
                }
                if ($char === $quote) {
                    $quote = '';
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '[' || $char === '{' || $char === '(') {
                $depth++;
                $buffer .= $char;
                continue;
            }

            if ($char === ']' || $char === '}' || $char === ')') {
                $depth = max(0, $depth - 1);
                $buffer .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $item = trim($buffer);
                if ($item !== '') {
                    $items[] = $item;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $item = trim($buffer);
        if ($item !== '') {
            $items[] = $item;
        }

        return $items;
    }

    private static function parseDoValue(string $value): mixed
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return stripcslashes(substr($value, 1, -1));
        }

        if ($first === '[' && $last === ']') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            $items = [];
            foreach (self::splitTopLevel(substr($value, 1, -1)) as $item) {
                $items[] = self::parseDoValue(trim($item));
            }
            return $items;
        }

        $lower = strtolower($value);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        if ($lower === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }

    private static function normalizeDoParams(string $actionType, array $arguments): array
    {
        foreach (['action', 'type', 'name'] as $field) {
            unset($arguments[$field]);
        }
        foreach (array_keys($arguments) as $field) {
            if (is_int($field)) {
                unset($arguments[$field]);
            }
        }

        $params = $arguments;
        switch ($actionType) {
            case 'tap':
            case 'double_tap':
            case 'long_press':
                $point = self::extractPoint($arguments);
                if ($point !== null) {
                    $params['element'] = [$point[0], $point[1]];
                    $params['x'] = $point[0];
                    $params['y'] = $point[1];
                }
                if ($actionType === 'long_press' && isset($arguments['duration'])) {
                    $params['duration'] = max(1, (float)$arguments['duration']);
                }
                break;
            case 'type':
                $text = $arguments['text'] ?? ($arguments['content'] ?? ($arguments['value'] ?? ($arguments['input'] ?? '')));
                $params['text'] = (string)$text;
                break;
            case 'swipe':
                $swipe = self::extractSwipe($arguments);
                if ($swipe !== null) {
                    $params['start'] = [$swipe[0], $swipe[1]];
                    $params['end'] = [$swipe[2], $swipe[3]];
                    $params['start_x'] = $swipe[0];
                    $params['start_y'] = $swipe[1];
                    $params['end_x'] = $swipe[2];
                    $params['end_y'] = $swipe[3];
                }
                break;
            case 'wait':
                $seconds = $arguments['seconds'] ?? ($arguments['time'] ?? ($arguments['duration'] ?? 2));
                if (is_string($seconds) && preg_match('/(\d+)/', $seconds, $match)) {
                    $seconds = (int)$match[1];
                }
                $params['seconds'] = max(1, (int)$seconds);
                break;
            case 'launch':
                $appName = $arguments['app_name'] ?? ($arguments['app'] ?? ($arguments['package'] ?? ''));
                if ($appName !== '') {
                    $params['app_name'] = (string)$appName;
                }
                break;
            case 'take_over':
                $params['reason'] = (string)($arguments['reason'] ?? ($arguments['message'] ?? ''));
                break;
        }

        return $params;
    }

    private static function extractPoint(array $arguments): ?array
    {
        foreach (['element', 'point', 'position', 'coordinate', 'coordinates'] as $field) {
            if (!isset($arguments[$field]) || !is_array($arguments[$field]) || count($arguments[$field]) < 2) {
                continue;
            }
            if (is_numeric($arguments[$field][0]) && is_numeric($arguments[$field][1])) {
                return [(int)$arguments[$field][0], (int)$arguments[$field][1]];
            }
        }

        if (isset($arguments['x'], $arguments['y']) && is_numeric($arguments['x']) && is_numeric($arguments['y'])) {
            return [(int)$arguments['x'], (int)$arguments['y']];
        }

        return null;
    }

    private static function extractSwipe(array $arguments): ?array
    {
        foreach (['element', 'points', 'path'] as $field) {
            if (!isset($arguments[$field]) || !is_array($arguments[$field])) {
                continue;
            }
            $value = $arguments[$field];
            if (count($value) >= 4 && is_numeric($value[0]) && is_numeric($value[1]) && is_numeric($value[2]) && is_numeric($value[3])) {
                return [(int)$value[0], (int)$value[1], (int)$value[2], (int)$value[3]];
            }
            if (isset($value[0], $value[1]) && is_array($value[0]) && is_array($value[1]) && count($value[0]) >= 2 && count($value[1]) >= 2) {
                return [(int)$value[0][0], (int)$value[0][1], (int)$value[1][0], (int)$value[1][1]];
            }
        }

        foreach ([['start', 'end'], ['from', 'to']] as [$startField, $endField]) {
            if (!isset($arguments[$startField], $arguments[$endField]) || !is_array($arguments[$startField]) || !is_array($arguments[$endField])) {
                continue;
            }
            if (count($arguments[$startField]) >= 2 && count($arguments[$endField]) >= 2) {
                return [
                    (int)$arguments[$startField][0],
                    (int)$arguments[$startField][1],
                    (int)$arguments[$endField][0],
                    (int)$arguments[$endField][1],
                ];
            }
        }

        if (isset($arguments['start_x'], $arguments['start_y'], $arguments['end_x'], $arguments['end_y'])) {
            return [
                (int)$arguments['start_x'],
                (int)$arguments['start_y'],
                (int)$arguments['end_x'],
                (int)$arguments['end_y'],
            ];
        }

        return null;
    }

    private static function resolveActionTimeout(string $actionType, array $params): int
    {
        if ($actionType === 'wait') {
            return max(1, (int)($params['seconds'] ?? 2)) + 5;
        }

        return match ($actionType) {
            'launch' => 20,
            'tap', 'double_tap', 'back', 'home' => 10,
            'type', 'swipe', 'long_press' => 15,
            'take_over' => 60,
            default => 60,
        };
    }

    private static function normalizeActionType(string $actionType): string
    {
        $normalized = strtolower(trim($actionType));
        $normalized = str_replace(['-', ' '], '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);

        $aliases = [
            'click' => 'tap',
            'input' => 'type',
            'type_name' => 'type',
            'longpress' => 'long_press',
            'long_tap' => 'long_press',
            'doubletap' => 'double_tap',
            'takeover' => 'take_over',
            'take_over' => 'take_over',
            'sleep' => 'wait',
            'done' => 'finish',
            'complete' => 'finish',
            'completed' => 'finish',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    private static function normalizeParsedActions(array $rawActions): array
    {
        $normalizedActions = [];
        foreach ($rawActions as $rawAction) {
            if (!is_array($rawAction)) {
                continue;
            }

            $parsed = self::normalizeParsedAction($rawAction);
            if (!($parsed['success'] ?? false)) {
                return $parsed;
            }

            if (!empty($parsed['finish'])) {
                return $parsed;
            }

            $normalizedActions[] = $parsed;
        }

        if ($normalizedActions === []) {
            return ['success' => false, 'message' => '模型响应不是可解析 JSON'];
        }

        $first = $normalizedActions[0];
        $result = [
            'success' => true,
            'finish' => false,
            'message' => '',
            'actions' => $normalizedActions,
            'dispatched_count' => 0,
            'action_type' => (string)($first['action_type'] ?? ''),
            'params' => is_array($first['params'] ?? null) ? $first['params'] : [],
            'timeout' => max(1, (int)($first['timeout'] ?? 60)),
        ];

        if (count($normalizedActions) > 1) {
            $result['multi_action'] = true;
        }

        return $result;
    }

    private static function normalizeParsedAction(array $payload): array
    {
        $finish = (bool)($payload['finish'] ?? false);
        $actionType = self::normalizeActionType((string)($payload['action_type'] ?? ($payload['type'] ?? ($payload['name'] ?? ''))));
        if (in_array(strtolower($actionType), ['finish', 'done', 'complete', 'completed'], true)) {
            $finish = true;
        }

        if ($finish) {
            return [
                'success' => true,
                'finish' => true,
                'message' => (string)($payload['message'] ?? '任务完成'),
                'action_type' => 'finish',
                'params' => [],
                'timeout' => 0,
            ];
        }

        $params = $payload['params'] ?? ($payload['arguments'] ?? ($payload['payload'] ?? []));
        if (!is_array($params)) {
            $params = json_decode((string)$params, true);
        }

        if ($actionType === '') {
            return ['success' => false, 'message' => '模型响应缺少 action_type'];
        }

        if (!PhoneAgentActionPolicy::isAllowed($actionType)) {
            return ['success' => false, 'message' => '不支持的手机动作:' . $actionType];
        }

        $params = is_array($params) ? $params : [];
        $validationError = self::validateActionParams($actionType, $params);
        if ($validationError !== '') {
            return ['success' => false, 'message' => $validationError];
        }

        return [
            'success' => true,
            'finish' => false,
            'message' => '',
            'action_type' => $actionType,
            'params' => $params,
            'timeout' => max(1, (int)($payload['timeout'] ?? 60)),
        ];
    }

    private static function validateActionParams(string $actionType, array $params): string
    {
        return match ($actionType) {
            'tap', 'double_tap', 'long_press' => self::validatePointParams($params, $actionType),
            'swipe' => self::validateSwipeParams($params),
            default => '',
        };
    }

    private static function validatePointParams(array $params, string $actionType): string
    {
        $point = self::extractPoint($params);
        if ($point === null) {
            return $actionType . ' missing coordinate';
        }
        return self::validatePoint($point) ? '' : $actionType . ' coordinate must be between 0 and 999';
    }

    private static function validateSwipeParams(array $params): string
    {
        $swipe = self::extractSwipe($params);
        if ($swipe === null) {
            return 'swipe missing coordinate';
        }
        if (!self::validatePoint([$swipe[0], $swipe[1]]) || !self::validatePoint([$swipe[2], $swipe[3]])) {
            return 'swipe coordinate must be between 0 and 999';
        }
        return '';
    }

    private static function validatePoint(array $point): bool
    {
        if (count($point) < 2 || !is_numeric($point[0]) || !is_numeric($point[1])) {
            return false;
        }
        return (int)$point[0] >= 0 && (int)$point[0] <= 999 && (int)$point[1] >= 0 && (int)$point[1] <= 999;
    }

    private static function actionHistoryBeforeTurn(string $taskId, int $turnNo): array
    {
        return PhoneAgentModelContextService::actionHistoryBeforeTurn($taskId, $turnNo);
    }

    private static function actionHistoryHasType(array $actionHistory): bool
    {
        foreach ($actionHistory as $action) {
            if (!is_array($action)) {
                continue;
            }
            if (in_array((string)($action['action_type'] ?? ''), ['type', 'input'], true)) {
                return true;
            }
        }
        return false;
    }

    private static function actionHistoryHasOnlyLaunch(array $actionHistory): bool
    {
        if ($actionHistory === []) {
            return false;
        }

        $hasLaunch = false;
        foreach ($actionHistory as $action) {
            if (!is_array($action)) {
                continue;
            }
            $actionType = (string)($action['action_type'] ?? '');
            if ($actionType === 'launch') {
                $hasLaunch = true;
                continue;
            }
            if (in_array($actionType, ['type', 'input', 'tap', 'double_tap', 'long_press', 'swipe'], true)) {
                return false;
            }
        }

        return $hasLaunch;
    }

    private static function extractSearchTermsFromGoal(string $goal): array
    {
        $terms = [];
        if (preg_match('/搜索(.+)$/u', $goal, $match)) {
            $term = trim((string)$match[1]);
            if ($term !== '') {
                $terms[] = $term;
            }
        }
        return $terms;
    }

    private static function observationContainsSearchEvidence(array $observation, array $searchTerms): bool
    {
        if ($searchTerms === []) {
            return false;
        }

        $ocrText = (string)($observation['ocr_text'] ?? '');
        $treeJson = json_encode($observation['accessibility_tree'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $haystack = $ocrText . "\n" . (is_string($treeJson) ? $treeJson : '');
        foreach ($searchTerms as $term) {
            $term = trim((string)$term);
            if ($term !== '' && str_contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }
}
