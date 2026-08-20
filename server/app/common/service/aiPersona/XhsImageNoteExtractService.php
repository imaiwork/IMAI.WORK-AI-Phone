<?php

namespace app\common\service\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\ToolsService;
use app\common\service\videoImitation\VideoImitationImageRewriteService;
use think\facade\Log;

/**
 * 小红书图文笔记提取（公共 API）
 */
class XhsImageNoteExtractService
{
    private const REMARK_NO_LINK = '未从分享内容中提取到小红书链接';
    private const REMARK_PARSE_FAIL = '小红书笔记解析失败';
    private const REMARK_DOWNLOAD_FAIL = '小红书原图下载失败';
    private const REMARK_DOWNLOAD_TIMEOUT = '小红书原图下载超时';
    private const REMARK_VIDEO_UNSUPPORTED = '暂不支持小红书视频分享链接';
    private const IMAGE_DOWNLOAD_MAX_ATTEMPT = 3;
    /** 整笔记原图下载墙钟上限（秒） */
    private const IMAGE_DOWNLOAD_BUDGET_SECONDS = 480;

    /**
     * 从分享文案/链接提取小红书图文笔记
     *
     * @return array{
     *     type: string,
     *     title: string,
     *     desc: string,
     *     tags?: array<int, string>,
     *     images: array<int, string>,
     *     likes: int,
     *     comments: int,
     *     tikhub_raw: array,
     *     error?: string
     * }
     */
    public static function extract(string $shareContent): array
    {
        try {
            $shareUrl = self::extractShareUrl($shareContent);
            self::log('【小红书】提取分享链接', [
                'share_url' => $shareUrl,
                'share_content_preview' => mb_substr($shareContent, 0, 300),
            ]);
            if ($shareUrl === '') {
                throw new \RuntimeException(self::REMARK_NO_LINK);
            }

            $shareCandidates = array_values(array_unique(array_filter([
                $shareUrl,
                trim($shareContent) !== $shareUrl ? trim($shareContent) : '',
            ])));
            $response = [];
            $lastApiMsg = '';
            foreach ($shareCandidates as $idx => $shareText) {
                self::log('【小红书】请求 TikHub 笔记详情', [
                    'attempt' => $idx + 1,
                    'share_url' => $shareUrl,
                    'share_text_len' => mb_strlen($shareText),
                    'share_text_preview' => mb_substr($shareText, 0, 120),
                ]);
                $response = ToolsService::TikHub()->getXhsImageNoteDetail($shareText);
                $code = $response['code'] ?? $response['status_code'] ?? 0;
                $lastApiMsg = trim((string)($response['msg'] ?? $response['message'] ?? ''));
                $hasData = !empty($response['data']);
                $ok = in_array((int)$code, [0, 1, 200, 10000], true) || $hasData;
                if ($ok && $hasData) {
                    break;
                }
                self::log('【小红书】TikHub 本次入参失败，尝试下一候选', [
                    'code' => $code,
                    'msg' => $lastApiMsg,
                    'has_data' => $hasData,
                ]);
                $response = [];
            }
            if (empty($response) || empty($response['data'])) {
                throw new \RuntimeException(
                    self::REMARK_PARSE_FAIL . '：' . ($lastApiMsg !== '' ? $lastApiMsg : '接口无数据')
                );
            }

            $code = $response['code'] ?? $response['status_code'] ?? 0;
            $payload = self::resolveNotePayload($response);
            $noteType = strtolower(trim((string)($payload['type'] ?? $payload['note_type'] ?? '')));
            $imagesList = self::resolveImagesList($payload, $response);
            self::log('【小红书】TikHub 响应摘要', [
                'code' => $code,
                'has_data' => !empty($response['data']),
                'note_type' => $noteType,
                'images_list_count' => is_array($imagesList) ? count($imagesList) : 0,
                'payload_keys' => is_array($payload) ? array_slice(array_keys($payload), 0, 30) : [],
                'msg' => $response['msg'] ?? ($response['message'] ?? ''),
            ]);
            if (!in_array((int)$code, [0, 1, 200, 10000], true) && empty($response['data'])) {
                $apiMsg = trim((string)($response['msg'] ?? $response['message'] ?? ''));
                throw new \RuntimeException(
                    self::REMARK_PARSE_FAIL . '：' . ($apiMsg !== '' ? $apiMsg : '接口无数据')
                );
            }

            $imagesListCount = is_array($imagesList) ? count($imagesList) : 0;
            if ($noteType === 'video' && $imagesListCount <= 1) {
                self::log('【小红书】TikHub 判定为视频笔记', [
                    'share_url' => $shareUrl,
                    'images_list_count' => $imagesListCount,
                ]);
                return [
                    'type' => 'video',
                    'title' => self::pickScalarField($payload, ['title', 'note_title', 'display_title']),
                    'desc' => self::pickScalarField($payload, ['desc', 'description', 'content', 'note_desc', 'text']),
                    'images' => [],
                    'likes' => (int)($payload['liked_count'] ?? $payload['likes'] ?? 0),
                    'comments' => (int)($payload['comment_count'] ?? $payload['comments'] ?? 0),
                    'tikhub_raw' => $response,
                    'error' => self::REMARK_VIDEO_UNSUPPORTED,
                ];
            }

            if (!is_array($imagesList) || $imagesListCount <= 0) {
                self::log('【小红书】无图片列表', ['note_type' => $noteType]);
                throw new \RuntimeException(self::REMARK_PARSE_FAIL . '：无图片列表');
            }

            $images = [];
            $downloadStartedAt = microtime(true);
            $downloadBudgetHit = false;
            foreach ($imagesList as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $elapsed = microtime(true) - $downloadStartedAt;
                if ($elapsed >= self::IMAGE_DOWNLOAD_BUDGET_SECONDS) {
                    $downloadBudgetHit = true;
                    self::log('【小红书】原图下载达到墙钟上限，停止后续下载', [
                        'elapsed_s' => (int)round($elapsed),
                        'budget_s' => self::IMAGE_DOWNLOAD_BUDGET_SECONDS,
                        'downloaded' => count($images),
                        'total' => $imagesListCount,
                        'stop_index' => $idx,
                    ]);
                    break;
                }
                $downloaded = false;
                foreach (self::buildImageDownloadCandidates($item) as $url) {
                    $elapsed = microtime(true) - $downloadStartedAt;
                    if ($elapsed >= self::IMAGE_DOWNLOAD_BUDGET_SECONDS) {
                        $downloadBudgetHit = true;
                        break 2;
                    }
                    $stored = self::downloadImageToLocal($url);
                    if ($stored !== '') {
                        $images[] = $stored;
                        $downloaded = true;
                        self::log('【小红书】图片下载成功', [
                            'index' => $idx,
                            'stored' => $stored,
                            'url' => mb_substr($url, 0, 200),
                        ]);
                        break;
                    }
                    self::log('【小红书】图片下载失败，尝试下一候选', [
                        'index' => $idx,
                        'url' => mb_substr($url, 0, 200),
                    ]);
                }
                if (!$downloaded) {
                    self::log('【小红书】该图片所有候选 URL 均下载失败', ['index' => $idx]);
                }
            }
            $images = array_values(array_unique($images));
            if (empty($images)) {
                self::log('【小红书】图片全部下载失败', [
                    'budget_hit' => $downloadBudgetHit ? 1 : 0,
                ]);
                throw new \RuntimeException(
                    $downloadBudgetHit ? self::REMARK_DOWNLOAD_TIMEOUT : self::REMARK_DOWNLOAD_FAIL
                );
            }
            if ($downloadBudgetHit) {
                self::log('【小红书】下载超时但已有部分原图，继续后续流程', [
                    'downloaded' => count($images),
                    'total' => $imagesListCount,
                ]);
            }

            if ($noteType === 'video') {
                self::log('【小红书】type=video 但多图可用，按图文继续', [
                    'image_count' => count($images),
                ]);
                $noteType = 'normal';
            }

            $title = self::pickScalarField($payload, ['title', 'note_title', 'display_title']);
            $desc = self::pickScalarField($payload, ['desc', 'description', 'content', 'note_desc', 'text']);
            $tags = self::extractTags($payload);

            $note = [
                'type' => $noteType !== '' ? $noteType : 'normal',
                'title' => $title,
                'desc' => $desc,
                'images' => $images,
                'likes' => (int)($payload['liked_count'] ?? $payload['likes'] ?? 0),
                'comments' => (int)($payload['comment_count'] ?? $payload['comments'] ?? 0),
                'tikhub_raw' => $response,
            ];
            if (!empty($tags)) {
                $note['tags'] = $tags;
            }

            self::log('【小红书】笔记提取成功', [
                'type' => $note['type'],
                'title' => $title,
                'desc_len' => mb_strlen($desc),
                'image_count' => count($images),
                'likes' => $note['likes'],
                'comments' => $note['comments'],
            ]);

            return $note;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $th) {
            self::log('【小红书】提取异常：' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            throw new \RuntimeException(self::REMARK_PARSE_FAIL . '：' . $th->getMessage(), 0, $th);
        }
    }

    private static function extractShareUrl(string $shareContent): string
    {
        $shareContent = trim($shareContent);
        if ($shareContent === '') {
            return '';
        }
        try {
            $detected = ViralSharePlatformDetector::detect($shareContent);
            if ((int)$detected['platform'] === DeviceEnum::ACCOUNT_TYPE_XHS) {
                return (string)$detected['url'];
            }
        } catch (\Throwable $th) {
            // 回落正则
        }
        if (!preg_match('/https?:\/\/(?:www\.)?(?:xiaohongshu\.com|xhslink\.com|xhslink\.cn|xhsurl\.com)\/[^\s]+/iu', $shareContent, $matches)) {
            return '';
        }
        return rtrim(html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
    }

    private static function resolveNotePayload(array $response): array
    {
        $candidates = [
            $response['data']['data'][0]['note_list'][0] ?? null,
            $response['data']['data']['note_list'][0] ?? null,
            $response['data']['data']['note_card'] ?? null,
            $response['data']['data']['note'] ?? null,
            $response['data']['note_list'][0] ?? null,
            $response['data']['note_card'] ?? null,
            $response['data']['note'] ?? null,
            $response['data']['items'][0]['note_card'] ?? null,
            $response['data']['item'] ?? null,
            $response['data']['data'] ?? null,
            $response['data'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate) || empty($candidate)) {
                continue;
            }
            if (!empty(self::pickImagesListFromArray($candidate))) {
                return $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return $candidate;
            }
        }
        return [];
    }

    private static function resolveImagesList(array $payload, array $response): array
    {
        $fromPayload = self::pickImagesListFromArray($payload);
        if (!empty($fromPayload)) {
            return $fromPayload;
        }
        $fallback = $response['data']['data'][0]['note_list'][0]['images_list'] ?? [];
        return is_array($fallback) ? $fallback : [];
    }

    private static function pickImagesListFromArray(array $data): array
    {
        foreach (['images_list', 'image_list', 'images', 'imageList', 'pictures', 'pics'] as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        return [];
    }

    /**
     * @return list<string>
     */
    private static function buildImageDownloadCandidates(array $item): array
    {
        $candidates = [];
        foreach (['original', 'url_size_large', 'url_8k', 'url', 'url_default', 'url_pre', 'origin_url'] as $key) {
            if (empty($item[$key]) || !is_scalar($item[$key])) {
                continue;
            }
            $url = self::normalizeImageUrl((string)$item[$key]);
            if ($url !== '' && !in_array($url, $candidates, true)) {
                $candidates[] = $url;
            }
        }
        return $candidates;
    }

    private static function normalizeImageUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = rtrim($url, " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
        return preg_match('/^https?:\/\//i', $url) ? $url : '';
    }

    private static function downloadImageToLocal(string $url): string
    {
        $url = self::normalizeImageUrl($url);
        if ($url === '') {
            return '';
        }

        for ($attempt = 1; $attempt <= self::IMAGE_DOWNLOAD_MAX_ATTEMPT; $attempt++) {
            $stored = self::downloadImageToLocalOnce($url);
            if ($stored !== '') {
                if ($attempt > 1) {
                    self::log('【小红书】原图下载重试成功', [
                        'attempt' => $attempt,
                        'url' => mb_substr($url, 0, 200),
                        'stored' => $stored,
                    ]);
                }
                return $stored;
            }
            if ($attempt < self::IMAGE_DOWNLOAD_MAX_ATTEMPT) {
                usleep(500000 * $attempt);
            }
        }
        self::log('【小红书】原图下载全部重试失败', ['url' => mb_substr($url, 0, 200)]);
        return '';
    }

    private static function downloadImageToLocalOnce(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/jpeg,image/png,image/jpg,image/*,*/*;q=0.8',
                'Referer: https://www.xiaohongshu.com/',
            ],
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($errno !== 0 || $statusCode < 200 || $statusCode >= 300 || !is_string($body) || $body === '') {
            self::log('【小红书】原图下载 HTTP 失败', [
                'status' => $statusCode,
                'errno' => $errno,
                'error' => $error,
                'content_type' => $contentType,
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            self::log('【小红书】原图保存失败：缺少 GD 扩展');
            return '';
        }
        $image = @imagecreatefromstring($body);
        if ($image === false) {
            self::log('【小红书】原图 GD 解码失败', [
                'content_type' => $contentType,
                'body_len' => strlen($body),
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }
        $date = date('Ymd');
        $directory = public_path() . 'uploads' . DIRECTORY_SEPARATOR . 'rewrite' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $date;
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            imagedestroy($image);
            self::log('【小红书】原图目录创建失败：' . $th->getMessage());
            return '';
        }
        $filename = date('YmdHis') . md5($url . microtime(true) . mt_rand()) . '.png';
        $absolutePath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $saved = imagepng($image, $absolutePath);
        imagedestroy($image);
        if (!$saved || !is_file($absolutePath) || filesize($absolutePath) <= 0) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            self::log('【小红书】原图 PNG 写入失败', ['path' => $absolutePath]);
            return '';
        }
        FileService::ensureWritableFile($absolutePath);
        $relativeUri = 'uploads/rewrite/images/' . $date . '/' . $filename;
        // 先压缩再上传/入库，避免 OSS 场景本地删除后改写前无法再压
        $compressedUri = VideoImitationImageRewriteService::compressStoredImage($relativeUri);
        if ($compressedUri !== $relativeUri) {
            $relativeUri = $compressedUri;
            $absolutePath = rtrim(public_path(), '/\\')
                . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $relativeUri);
            self::log('【小红书】原图已压缩', ['path' => $relativeUri]);
        }

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return $relativeUri;
        }

        try {
            self::uploadLocalImageToRemote($absolutePath, $relativeUri, $storageDefault);
        } catch (\Throwable $th) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            self::log('【小红书】原图上传 OSS 失败', [
                'path' => $relativeUri,
                'error' => $th->getMessage(),
                'url' => mb_substr($url, 0, 200),
            ]);
            return '';
        }

        if (is_file($absolutePath) && !@unlink($absolutePath)) {
            self::log('【小红书】原图上传 OSS 成功但本地删除失败', [
                'path' => $relativeUri,
                'absolute' => $absolutePath,
            ]);
        } else {
            self::log('【小红书】原图上传 OSS 成功并已删除本地', ['path' => $relativeUri]);
        }

        return $relativeUri;
    }

    private static function uploadLocalImageToRemote(
        string $absolutePath,
        string $relativeUri,
        string $storageDefault
    ): void {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        $filename = basename($relativeUri);
        $saveDir = dirname($relativeUri);
        if ($saveDir === '.' || $saveDir === '\\') {
            $saveDir = '';
        }

        $storageConfig = [
            'default' => $storageDefault,
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ];
        $storageDriver = new StorageDriver($storageConfig);
        $storageDriver->setUploadFileByFileName($absolutePath, $filename);
        if (!$storageDriver->upload($saveDir)) {
            throw new \RuntimeException($storageDriver->getError() ?: '上传失败');
        }
    }

    /**
     * @param array<int, string> $keys
     */
    private static function pickScalarField(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!empty($payload[$key]) && is_scalar($payload[$key])) {
                return trim((string)$payload[$key]);
            }
        }
        return '';
    }

    /**
     * @return list<string>
     */
    private static function extractTags(array $payload): array
    {
        $tags = [];
        foreach (['tag_list', 'tags', 'hash_tag', 'hash_tags'] as $key) {
            if (empty($payload[$key]) || !is_array($payload[$key])) {
                continue;
            }
            foreach ($payload[$key] as $item) {
                if (is_scalar($item)) {
                    $tag = trim((string)$item);
                } elseif (is_array($item)) {
                    $tag = trim((string)($item['name'] ?? $item['tag'] ?? $item['title'] ?? ''));
                } else {
                    continue;
                }
                if ($tag !== '') {
                    $tags[] = ltrim($tag, '#');
                }
            }
        }
        return array_values(array_unique($tags));
    }

    private static function log(string $message, array $context = []): void
    {
        if (!empty($context)) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('manual_2img')->write($message);
    }
}
