<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

/**
 * 爆款分享文案清洗：去 URL / 口令噪声 / 话题 / @ / 平台引导语，只保留中文标题
 */
class ViralShareTextNormalizer
{
    /**
     * 从分享文案中提取首个 URL（若有）
     */
    public static function extractUrl(string $shareContent): string
    {
        if ($shareContent === '') {
            return '';
        }
        if (preg_match('#https?://[^\s]+#iu', $shareContent, $matches)) {
            return rtrim((string)$matches[0], ".,;:!?，。；：！？）)」』】\"'");
        }
        return '';
    }

    /**
     * 清洗为纯中文标题（含常用中文标点）
     */
    public static function normalize(string $shareContent): string
    {
        $text = trim($shareContent);
        if ($text === '') {
            return '';
        }

        // 1. 去除 URL / 短链
        $text = preg_replace('#https?://[^\s]+#iu', ' ', $text) ?? $text;

        // 2. 去除固定模板话术（先于噪声清洗，避免破坏匹配）
        $text = self::stripPlatformTemplates($text);

        // 3. 去除话题标签、@提及
        $text = preg_replace('/#[\s\x{00a0}]*[^\s#@]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/@[\s\x{00a0}]*[^\s#@]+/u', ' ', $text) ?? $text;

        // 4. 去除英文口令噪声：时间戳、口令码等英数符号混合串
        $text = self::stripEnglishNoiseTokens($text);

        // 5. 只保留中文字符与常用中文标点
        $text = preg_replace('/[^\x{4e00}-\x{9fff}\x{3000}-\x{303f}\x{ff00}-\x{ffef}，。！？、；：“”‘’（）【】《》…—·\s]/u', '', $text) ?? $text;

        // 压空白
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return trim($text);
    }

    private static function stripPlatformTemplates(string $text): string
    {
        $patterns = [
            '/复制此链接[，,]*打开Dou音搜索[，,]*直接观看视频[！!]*/ui',
            '/复制打开抖音[，,]*/ui',
            '/看看【[^】]*作品】/u',
            '/打开Dou音搜索[，,]*直接观看视频[！!]*/ui',
            '/直接观看视频[！!]*/u',
        ];
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, ' ', $text) ?? $text;
        }
        return $text;
    }

    private static function stripEnglishNoiseTokens(string $text): string
    {
        // 日期/时间碎片：05/12、:5pm、9.71
        $text = preg_replace('/\b\d{1,2}\/\d{1,2}\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/:?\d{1,2}\s*(am|pm)\b/iu', ' ', $text) ?? $text;
        $text = preg_replace('/\b\d+\.\d+\b/u', ' ', $text) ?? $text;

        // 口令码类：u@S.yt、cAG:/、pQX:/、w@S.lC、字母数字.@:/ 混合短串
        $text = preg_replace('/\b[A-Za-z0-9][A-Za-z0-9@.:\/_-]{1,20}\b/u', ' ', $text) ?? $text;

        // 残留孤立英文/数字/符号块
        $text = preg_replace('/[A-Za-z0-9@.:\/_-]+/u', ' ', $text) ?? $text;

        return $text;
    }
}
