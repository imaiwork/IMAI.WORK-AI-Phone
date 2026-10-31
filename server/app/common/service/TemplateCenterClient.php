<?php

declare(strict_types=1);

namespace app\common\service;

use Exception;
use think\facade\Cache;

/**
 * 模板中心客户端（2026-09-02）：直连 imai-temp.imai.work 公开分发接口。
 *
 * 缓存与韧性设计：
 *   - 结果进 Redis 缓存 5 分钟——与定时同步节奏对齐，避免缓存吃掉一整个同步
 *     周期；定时任务传 $useCache = false 直接打远端，手动重复执行仍走缓存；
 *   - 拉取失败直接抛异常，由调用方（同步任务）记录错误并跳过本轮——
 *     本地表数据保持原样继续服务，模板中心抖动不影响线上；
 *   - 空结果不进缓存，避免一次坏响应被缓存。
 */
class TemplateCenterClient
{
    private const CACHE_TTL = 300;
    private const CONNECT_TIMEOUT = 5;
    private const TIMEOUT = 10;

    /** 闪剪剪辑模板（字段与旧同步契约同构：id/name/cover_url/scene/demo_url/auto_type/sort） */
    public static function shanjianClipTemplates(bool $useCache = true): array
    {
        return self::fetchTemplates('/api/shanjian-clip-templates', 'tpl_center:shanjian_clip', 'templates', $useCache);
    }

    /** 爆款玩法模板（ref_id/play/name/category/heat/duration_sec/video_url/cover_url/face_count/allow_bg_replace/script/sort） */
    public static function playTemplates(bool $useCache = true): array
    {
        return self::fetchTemplates('/api/play-templates', 'tpl_center:play', 'templates', $useCache);
    }

    /** 系统曲库（id/name/audio_url/duration_sec/sort，只含上架） */
    public static function systemMusics(bool $useCache = true): array
    {
        return self::fetchTemplates('/api/system-musics', 'tpl_center:system_music', 'musics', $useCache);
    }

    private static function fetchTemplates(string $path, string $cacheKey, string $dataKey = 'templates', bool $useCache = true): array
    {
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        }

        $base = rtrim((string)(config('api_tools.template_center_url') ?: 'https://imai-temp.imai.work'), '/');
        $body = self::httpGet($base . $path);
        $json = json_decode($body, true);
        if (!is_array($json) || (int)($json['code'] ?? 0) !== 10000) {
            throw new Exception('模板中心返回异常: ' . mb_substr((string)($json['message'] ?? $body), 0, 200));
        }
        $templates = $json['data'][$dataKey] ?? null;
        if (!is_array($templates)) {
            throw new Exception('模板中心数据格式异常（缺 data.' . $dataKey . '）');
        }
        if ($templates !== []) {
            Cache::set($cacheKey, $templates, self::CACHE_TTL);
        }
        return $templates;
    }

    private static function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $error !== '') {
            throw new Exception('模板中心请求失败: ' . ($error ?: '未知错误'));
        }
        if ($status !== 200) {
            throw new Exception('模板中心响应 ' . $status);
        }
        return (string)$body;
    }
}
