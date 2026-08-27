<?php

namespace app\api\logic\qwen;

use app\api\logic\ApiLogic;
use app\common\service\ToolsService;

class QwenToolsLogic extends ApiLogic
{
    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    private const VIDEO_EXTS = ['mp4', 'avi', 'mkv', 'mov', 'flv', 'wmv'];

    public static function isImageAttachment(array $fileInfo): bool
    {
        $name = (string)($fileInfo['name'] ?? $fileInfo['uri'] ?? $fileInfo['url'] ?? '');
        $path = parse_url((string)($fileInfo['url'] ?? $name), PHP_URL_PATH) ?: $name;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::IMAGE_EXTS, true) || in_array($ext, self::VIDEO_EXTS, true);
    }

    public static function fileParse(string $fileUrl, string $question = ''): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->fileParse($fileUrl, $question),
            '文件解析失败',
            '文件解析内容为空'
        );
    }

    public static function imageParse(string $fileUrl, string $question = ''): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->imageParse($fileUrl, $question),
            '图片解析失败',
            '图片解析内容为空'
        );
    }

    public static function networkSearch(string $prompt): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->networkSearch($prompt),
            '网络搜索失败',
            '网络搜索内容为空'
        );
    }

    public static function fileParseStream(string $fileUrl, string $question, callable $onEvent): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->fileParseStream($fileUrl, $question, $onEvent),
            '文件解析失败',
            '文件解析内容为空'
        );
    }

    public static function imageParseStream(string $fileUrl, string $question, callable $onEvent): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->imageParseStream($fileUrl, $question, $onEvent),
            '图片解析失败',
            '图片解析内容为空'
        );
    }

    public static function networkSearchStream(string $prompt, callable $onEvent): array
    {
        return self::callPreprocess(
            fn () => ToolsService::QWen()->networkSearchStream($prompt, $onEvent),
            '网络搜索失败',
            '网络搜索内容为空'
        );
    }

    public static function emptyUsage(): array
    {
        return [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];
    }

    public static function mergeUsage(array $left, array $right): array
    {
        $prompt = (int)($left['prompt_tokens'] ?? 0) + (int)($right['prompt_tokens'] ?? 0);
        $completion = (int)($left['completion_tokens'] ?? 0) + (int)($right['completion_tokens'] ?? 0);
        return [
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $prompt + $completion,
        ];
    }

    /**
     * @return array{content:string,usage:array{prompt_tokens:int,completion_tokens:int,total_tokens:int}}
     */
    private static function callPreprocess(callable $sender, string $failMessage, string $emptyMessage): array
    {
        try {
            $response = $sender();
            if ((int)($response['code'] ?? 0) !== 10000) {
                throw new \Exception($response['msg'] ?? $response['message'] ?? $failMessage);
            }
            $content = (string)($response['data']['output'] ?? '');
            if ($content === '') {
                throw new \Exception($emptyMessage);
            }
            $usage = $response['data']['usage'] ?? [];
            $prompt = (int)($usage['prompt_tokens'] ?? 0);
            $completion = (int)($usage['completion_tokens'] ?? 0);
            return [
                'content' => $content,
                'usage' => [
                    'prompt_tokens' => $prompt,
                    'completion_tokens' => $completion,
                    'total_tokens' => (int)($usage['total_tokens'] ?? ($prompt + $completion)),
                ],
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }
}
