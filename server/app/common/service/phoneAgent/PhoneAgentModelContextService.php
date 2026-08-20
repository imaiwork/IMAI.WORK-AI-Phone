<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentAction;
use app\common\model\phoneAgent\PhoneAgentObservation;
use app\common\model\phoneAgent\PhoneAgentTask;
use app\common\model\phoneAgent\PhoneAgentTurn;
use app\common\service\FileService;

class PhoneAgentModelContextService
{
    public const COORDINATE_SYSTEM = 'normalized_1000';

    public static function formatScreenshotForModelRequest(PhoneAgentObservation $observation): string
    {
        return self::formatScreenshotForModel(self::resolveObservationScreenshot($observation));
    }

    public static function buildMessages(PhoneAgentTask $task, PhoneAgentObservation $observation): array
    {
        return PhoneAgentStateService::buildModelMessages($task, $observation);
    }

    public static function buildMessagesForLog(PhoneAgentTask $task, PhoneAgentObservation $observation): array
    {
        return PhoneAgentStateService::buildModelMessagesForLog($task, $observation);
    }

    public static function buildDebugContext(PhoneAgentTask $task, PhoneAgentObservation $observation, int $turnNo): array
    {
        return [
            'task_id' => (string)$task->task_id,
            'conversation_id' => PhoneAgentConversationService::taskConversationId($task),
            'current_device_code' => (string)$task->device_code,
            'turn_no' => $turnNo,
            'message' => (string)$task->message,
            'conversation_context' => PhoneAgentConversationService::contextForTask($task),
            'action_history' => self::actionHistoryBeforeTurn((string)$task->task_id, $turnNo),
            'coordinate_system' => self::COORDINATE_SYSTEM,
            'observation' => self::compactObservationForModel($observation),
        ];
    }

    public static function compactObservationForModel(PhoneAgentObservation $observation): array
    {
        $tree = is_array($observation->accessibility_tree ?? null) ? $observation->accessibility_tree : [];
        return [
            'ocr_text' => self::limitModelText((string)$observation->ocr_text, 500),
            'accessibility_tree' => self::compactAccessibilityTree($tree),
            'current_app' => (string)$observation->current_app,
        ];
    }

    public static function actionHistoryBeforeTurn(string $taskId, int $turnNo): array
    {
        if ($taskId === '' || $turnNo <= 1) {
            return [];
        }

        $turnIds = PhoneAgentTurn::where('task_id', $taskId)
            ->where('turn_no', '<', $turnNo)
            ->column('id');
        if (empty($turnIds)) {
            return [];
        }

        $actions = PhoneAgentAction::where('task_id', $taskId)
            ->where('turn_id', 'in', $turnIds)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return array_map(static function (array $action): array {
            $result = is_array($action['result'] ?? null) ? $action['result'] : [];
            return [
                'action_no' => (int)($action['action_no'] ?? 0),
                'action_type' => (string)($action['action_type'] ?? ''),
                'params' => is_array($action['action_payload'] ?? null) ? $action['action_payload'] : [],
                'status' => (string)($action['status'] ?? ''),
                'message' => (string)($result['message'] ?? ''),
            ];
        }, $actions);
    }

    public static function resolveObservationScreenshot(PhoneAgentObservation $observation): string
    {
        $modelScreenshot = trim((string)($observation->model_screenshot ?? ''));
        if ($modelScreenshot !== '') {
            return $modelScreenshot;
        }

        $rawData = is_array($observation->raw_data ?? null) ? $observation->raw_data : [];
        $content = is_array($rawData['content'] ?? null) ? $rawData['content'] : [];
        $screenshot = self::extractScreenshot($content);
        if ($screenshot !== '') {
            return $screenshot;
        }

        return trim((string)$observation->screenshot);
    }

    public static function extractScreenshotFromContent(array $content): string
    {
        return self::extractScreenshot($content);
    }

    public static function isLikelyBase64Image(string $value): bool
    {
        return self::looksLikeBase64Image($value);
    }

    private static function compactAccessibilityTree(array $tree, int $maxNodes = 80, int $maxJsonChars = 6000): array
    {
        if ($tree === []) {
            return [];
        }

        $nodes = [];
        self::collectAccessibilityNodes($tree, $nodes, $maxNodes);
        if ($nodes === []) {
            return [];
        }

        while (count($nodes) > 1) {
            $json = json_encode($nodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($json) && strlen($json) <= $maxJsonChars) {
                return $nodes;
            }
            array_pop($nodes);
        }

        return $nodes;
    }

    private static function collectAccessibilityNodes(array $node, array &$nodes, int $maxNodes): void
    {
        if (count($nodes) >= $maxNodes) {
            return;
        }

        $text = trim((string)($node['text'] ?? ''));
        $clickable = (bool)($node['clickable'] ?? false);
        if ($text !== '' || $clickable) {
            $compact = [];
            if ($text !== '') {
                $compact['text'] = self::limitModelText($text, 120);
            }
            if (isset($node['bound'])) {
                $compact['bound'] = (string)$node['bound'];
            }
            if ($clickable) {
                $compact['clickable'] = true;
            }
            $resourceId = trim((string)($node['resourceId'] ?? ''));
            if ($resourceId !== '') {
                $compact['resourceId'] = self::limitModelText($resourceId, 80);
            }
            if ($compact !== []) {
                $nodes[] = $compact;
            }
        }

        $children = $node['children'] ?? [];
        if (!is_array($children)) {
            return;
        }
        foreach ($children as $child) {
            if (!is_array($child) || count($nodes) >= $maxNodes) {
                break;
            }
            self::collectAccessibilityNodes($child, $nodes, $maxNodes);
        }
    }

    private static function limitModelText(string $text, int $limit = 500): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
            return strlen($text) <= $limit ? $text : substr($text, 0, $limit) . '...';
        }
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . '...';
    }

    private static function extractScreenshot(array $content): string
    {
        foreach (['screenshot', 'screenshot_url', 'image', 'base64'] as $field) {
            $value = trim((string)($content[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private static function formatScreenshotForModel(string $screenshot): string
    {
        $screenshot = trim($screenshot);
        if ($screenshot === '' || str_starts_with($screenshot, 'data:image/')) {
            return $screenshot;
        }
        if (self::looksLikeBase64Image($screenshot)) {
            return 'data:image/png;base64,' . $screenshot;
        }

        $imageData = self::readScreenshotBinary($screenshot);
        if ($imageData === null) {
            return $screenshot;
        }

        return 'data:' . self::detectImageMime($imageData, $screenshot) . ';base64,' . base64_encode($imageData);
    }

    private static function readScreenshotBinary(string $screenshot): ?string
    {
        if (str_starts_with($screenshot, 'http://') || str_starts_with($screenshot, 'https://')) {
            return self::fetchRemoteBinary($screenshot);
        }

        $path = self::resolveScreenshotPath($screenshot);
        if ($path !== null && is_file($path) && is_readable($path)) {
            $data = @file_get_contents($path);
            if (is_string($data) && $data !== '') {
                return $data;
            }
        }

        // 本地不存在时（如已上传 OSS 并删除本地），通过存储域名补全后远程拉取
        $remoteUrl = FileService::getFileUrl(ltrim(str_replace('\\', '/', $screenshot), '/'), '', true);
        if ($remoteUrl !== ''
            && $remoteUrl !== $screenshot
            && (str_starts_with($remoteUrl, 'http://') || str_starts_with($remoteUrl, 'https://'))
        ) {
            return self::fetchRemoteBinary($remoteUrl);
        }

        return null;
    }

    private static function fetchRemoteBinary(string $url): ?string
    {
        $context = stream_context_create([
            'http' => ['timeout' => 8],
            'https' => ['timeout' => 8],
        ]);
        $data = @file_get_contents($url, false, $context);
        return is_string($data) && $data !== '' ? $data : null;
    }

    private static function resolveScreenshotPath(string $screenshot): ?string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($screenshot));
        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $relative = ltrim($path, DIRECTORY_SEPARATOR);
        $candidates = [
            app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $relative,
            app()->getRootPath() . $relative,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function detectImageMime(string $imageData, string $source = ''): string
    {
        $info = @getimagesizefromstring($imageData);
        if (is_array($info) && is_string($info['mime'] ?? null) && str_starts_with($info['mime'], 'image/')) {
            return $info['mime'];
        }

        return match (strtolower(pathinfo(parse_url($source, PHP_URL_PATH) ?: $source, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    private static function looksLikeBase64Image(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, '://')) {
            return false;
        }
        if (strlen($value) < 12 || strlen($value) % 4 !== 0) {
            return false;
        }
        return (bool)preg_match('/^[A-Za-z0-9+\/=]+$/', $value);
    }
}
