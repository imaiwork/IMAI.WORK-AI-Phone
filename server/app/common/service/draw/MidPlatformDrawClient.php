<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\draw\DrawEnum;
use app\common\service\ToolsService;

/**
 * 中台生图/生视频 Client
 *
 * 真实接口返回示例（同步出图，无 task_id）：
 * {
 *   "code": 10000,
 *   "message": "操作成功",
 *   "data": {
 *     "created": 1783924956,
 *     "data": [{"size":"1024x1024","url":"https://..."}],
 *     "model": "doubao-seedream-4-0-250828",
 *     "usage": {"generated_images":1,"output_tokens":4096,"total_tokens":4096}
 *   }
 * }
 *
 * 视频 generations 以 station-video-generations 文档为准：
 * { model, prompt, seconds:"5", image?, images?, metadata:{ resolution, ratio, ... } }
 * 不要传 size / 数字 duration。
 */
class MidPlatformDrawClient
{
    /** 中台 generations 成功码 */
    private const SUCCESS_CODES = [0, 10000, 20000];

    /** 允许透传给中台的字段（避免把前端展示字段原样扔过去） */
    private const ALLOWED_BODY_KEYS = [
        'model', 'prompt', 'n', 'width', 'height', 'size',
        'image', 'images', 'image_url', 'mask',
        'negative_prompt', 'seed', 'steps', 'cfg_scale', 'guidance_scale',
        // 视频：seconds + metadata（见 station-video-generations-apipost.md）
        'seconds', 'metadata',
        'notify_url',
    ];

    /** 视频 metadata 可透传键（火山 Seedance 1.0 Pro 适用子集） */
    private const VIDEO_METADATA_KEYS = [
        'resolution', 'ratio', 'seed', 'watermark', 'camera_fixed',
        'return_last_frame', 'frames', 'service_tier', 'execution_expires_after',
        'callback_url', 'content',
    ];

    /** 视频缺省时长（秒），与 DrawBillingService 一致 */
    private const DEFAULT_VIDEO_SECONDS = 5;

    private bool $usePlaceholder;
    private array $conf;

    public function __construct()
    {
        $this->conf = config('api_tools.draw') ?: [];
        $this->usePlaceholder = (bool)($this->conf['use_placeholder'] ?? true);
    }

    public function createImageGeneration(array $payload): array
    {
        if ($this->usePlaceholder) {
            return $this->placeholderSubmit(DrawEnum::MEDIA_IMAGE);
        }

        return $this->submit(
            $this->conf['image_generate_url'] ?? '/api/images/generations',
            $payload
        );
    }

    public function createVideoGeneration(array $payload): array
    {
        $payload = $this->normalizeVideoPayload($payload);

        if ($this->usePlaceholder) {
            return $this->placeholderSubmit(DrawEnum::MEDIA_VIDEO);
        }

        return $this->submit(
            $this->conf['video_generate_url'] ?? '/api/videos/generations',
            $payload,
            true
        );
    }

    /**
     * 对齐中台 POST /api/videos/generations：
     * { model, prompt, seconds:"5", image?, images?, metadata:{ resolution, ratio, ... } }
     * 兼容旧前端：aspect_ratio / size / image_url / duration → 新字段
     */
    private function normalizeVideoPayload(array $payload): array
    {
        $seconds = $payload['seconds'] ?? $payload['duration'] ?? null;
        if ($seconds === null || $seconds === '') {
            $def = (float)($this->conf['video_default_duration'] ?? self::DEFAULT_VIDEO_SECONDS);
            $seconds = $def > 0 ? $def : self::DEFAULT_VIDEO_SECONDS;
        }
        // Seedance 1.0 Pro 时长 [2, 12]，且必须是字符串
        $secondsInt = (int)(is_numeric($seconds) ? $seconds : self::DEFAULT_VIDEO_SECONDS);
        $secondsInt = max(2, min(12, $secondsInt > 0 ? $secondsInt : self::DEFAULT_VIDEO_SECONDS));

        $image = $this->resolveVideoImage($payload);
        $images = $this->resolveVideoImages($payload);
        $hasImage = $image !== '' || $images !== [];

        $metadata = [];
        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $metadata = $payload['metadata'];
        }

        // 顶层扩展参数并入 metadata（前端可能扁平传）
        foreach (self::VIDEO_METADATA_KEYS as $key) {
            if (!array_key_exists($key, $payload) || array_key_exists($key, $metadata)) {
                continue;
            }
            $val = $payload[$key];
            if ($val === null || $val === '') {
                continue;
            }
            $metadata[$key] = $val;
        }

        $ratio = trim((string)($metadata['ratio'] ?? $payload['ratio'] ?? $payload['aspect_ratio'] ?? ''));
        if ($ratio === '') {
            $ratio = $hasImage ? 'adaptive' : '16:9';
        }
        $metadata['ratio'] = $ratio;

        $resolution = trim((string)($metadata['resolution'] ?? ''));
        if ($resolution === '' || preg_match('/^\d+\s*[x×X*]\s*\d+$/u', $resolution)) {
            $pixelHint = trim((string)(
                $payload['resolution']
                ?? $payload['size']
                ?? $resolution
            ));
            $resolution = $this->pixelToResolutionTier($pixelHint);
        }
        $resolution = strtolower($resolution);
        if (!in_array($resolution, ['480p', '720p', '1080p'], true)) {
            $resolution = '720p';
        }
        $metadata['resolution'] = $resolution;

        // 仅保留文档允许的 metadata 键，避免上游强校验报错
        $cleanMeta = [];
        foreach (self::VIDEO_METADATA_KEYS as $key) {
            if (!array_key_exists($key, $metadata)) {
                continue;
            }
            $val = $metadata[$key];
            if ($val === null || $val === '') {
                continue;
            }
            $cleanMeta[$key] = $val;
        }

        $clean = [
            'model'    => $payload['model'] ?? '',
            'prompt'   => $payload['prompt'] ?? '',
            'seconds'  => (string)$secondsInt,
            'task_no'  => $payload['task_no'] ?? '',
            'metadata' => $cleanMeta,
        ];

        // 首尾帧走 metadata.content 时，不要再传顶层 image/images
        $hasContent = !empty($cleanMeta['content']) && is_array($cleanMeta['content']);
        if (!$hasContent) {
            if ($image !== '') {
                $clean['image'] = $image;
            } elseif ($images !== []) {
                $clean['images'] = $images;
            }
        }

        return $clean;
    }

    private function resolveVideoImage(array $payload): string
    {
        foreach (['image', 'image_url'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $val = $payload[$key];
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
            if (is_array($val)) {
                foreach ($val as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        return trim($item);
                    }
                }
            }
        }
        return '';
    }

    /**
     * @return string[]
     */
    private function resolveVideoImages(array $payload): array
    {
        if (!isset($payload['images']) || !is_array($payload['images'])) {
            return [];
        }
        $out = [];
        foreach ($payload['images'] as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }
        return $out;
    }

    /**
     * 像素尺寸 / 文案 → 480p|720p|1080p
     */
    private function pixelToResolutionTier(string $hint): string
    {
        $hint = trim($hint);
        if ($hint === '') {
            return '720p';
        }
        $lower = strtolower($hint);
        if (in_array($lower, ['480p', '720p', '1080p'], true)) {
            return $lower;
        }
        if (preg_match('/(\d+)\s*[x×X*]\s*(\d+)/u', $hint, $m)) {
            $maxEdge = max((int)$m[1], (int)$m[2]);
            if ($maxEdge <= 854) {
                return '480p';
            }
            if ($maxEdge <= 1280) {
                return '720p';
            }
            return '1080p';
        }
        if (str_contains($lower, '1080') || str_contains($lower, '4k')) {
            return '1080p';
        }
        if (str_contains($lower, '480')) {
            return '480p';
        }
        return '720p';
    }

    public function getGenerationStatus(string $mediaType, string $midTaskId): array
    {
        if ($this->usePlaceholder) {
            return [
                'code' => 10000,
                'data' => [
                    'task_id'     => $midTaskId,
                    'status'      => 'succeeded',
                    'assets'      => [],
                    'placeholder' => true,
                ],
            ];
        }

        $base = $mediaType === DrawEnum::MEDIA_VIDEO
            ? ($this->conf['video_status_url'] ?? '/api/videos/generations')
            : ($this->conf['image_status_url'] ?? '/api/images/generations');

        $endpoint = rtrim($base, '/') . '/' . rawurlencode($midTaskId);

        $response = app(ToolsService::class)
            ->setApiUrl($endpoint)
            ->setRequest([])
            ->setMethod('GET')
            ->sendWithoutThrow()
            ->response;

        $this->assertSuccess($response);
        return $response;
    }

    /**
     * 真实提交：手动拼 notify_url，避免 ThinkPHP url() 对 draw.draw 路由产生异常
     */
    private function submit(string $endpoint, array $payload, bool $isVideo = false): array
    {
        $body = $this->buildRequestBody($payload, $isVideo);

        $response = app(ToolsService::class)
            ->setApiUrl($endpoint)
            ->setRequest($body)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;

        $this->assertSuccess($response);
        return $response;
    }

    private function buildRequestBody(array $payload, bool $isVideo = false): array
    {
        $body = [];
        foreach (self::ALLOWED_BODY_KEYS as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }
            // 丢弃数组型展示字段，避免中台/签名链路异常；images / metadata 例外
            if (is_array($value) && !in_array($key, ['images', 'image', 'metadata'], true)) {
                continue;
            }
            $body[$key] = $value;
        }

        // 生图：size 优先；同时有 width/height 时也保留（中台实测可接受）
        if (!$isVideo && !isset($body['size']) && isset($body['width'], $body['height'])) {
            $body['size'] = (int)$body['width'] . 'x' . (int)$body['height'];
        }

        // 视频禁止 size（会被静默忽略，且易误导）；尺寸走 metadata
        if ($isVideo) {
            unset($body['size'], $body['width'], $body['height'], $body['image_url'], $body['notify_url']);
            if (!isset($body['metadata']) || !is_array($body['metadata'])) {
                $body['metadata'] = [];
            }
        }

        $taskNo = trim((string)($payload['task_no'] ?? ''));
        $notifyUrl = $this->buildNotifyUrl($taskNo);

        if ($isVideo) {
            if (empty($body['metadata']['callback_url'])) {
                $body['metadata']['callback_url'] = $notifyUrl;
            }
        } else {
            $body['notify_url'] = $notifyUrl;
        }

        return $body;
    }

    private function buildNotifyUrl(string $taskNo): string
    {
        $host = trim((string)config('app.app_host', ''));
        if ($host === '') {
            throw new \Exception('当前未配置为外网站点, 请联系站长配置');
        }
        $path = (string)($this->conf['notify_url'] ?? '/api/draw.draw/notify');
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        $url = rtrim($host, '/') . $path;
        if ($taskNo !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'task_no=' . rawurlencode($taskNo);
        }
        return $url;
    }

    private function assertSuccess(array $response): void
    {
        $code = $response['code'] ?? null;
        if ($code === null || $code === '') {
            return;
        }
        if (in_array((int)$code, self::SUCCESS_CODES, true)) {
            return;
        }

        $msg = $this->stringifyResponseMessage($response);
        throw new \Exception($msg !== '' ? $msg : '中台请求失败');
    }

    private function stringifyResponseMessage(array $response): string
    {
        foreach (['message', 'msg', 'error', 'error_msg'] as $key) {
            if (!array_key_exists($key, $response)) {
                continue;
            }
            $value = $response[$key];
            if (is_string($value) || is_int($value) || is_float($value)) {
                $text = trim((string)$value);
                if ($text !== '') {
                    return $text;
                }
            }
            if (is_array($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
                if (is_string($encoded) && $encoded !== '' && $encoded !== '[]' && $encoded !== '{}') {
                    return $encoded;
                }
            }
        }
        return '';
    }

    private function placeholderSubmit(string $mediaType): array
    {
        return [
            'code' => 10000,
            'data' => [
                'task_id'     => 'ph_' . $mediaType . '_' . uniqid('', true),
                'request_id'  => 'ph_req_' . uniqid('', true),
                'status'      => 'submitted',
                'placeholder' => true,
            ],
        ];
    }
}
