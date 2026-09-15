<?php

namespace app\common\service\videoImitation;

/**
 * 爆款视频「原文案」取值口径（手动复刻 / 自动复刻 / 爆款库手动导入共用）。
 *
 * 原文案只认视频里的口播（中台 audio_text，由作品音轨与视频文件做 ASR 得到）。
 * 识别不到就是提取失败，不拿任何东西顶替：
 *
 * - 不让大模型按分享口令兜底解析：模型读不到视频就照着口令里的标题现编，
 *   同一条链接每次重试编出一篇不同的原文案（2026-09-05 测试环境三次三个版本）。
 * - 不拿作品自带文案兜底：中台返回的 desc 是发布作品时填的标题文案，不是视频内容，
 *   用它去仿写等于「按标题写内容」（2026-09-05 用户明确否掉这条兜底）。
 *
 * 原文案是后续仿写与成片的事实来源，宁可判提取失败，也不能是别处来的文字。
 */
class OriginalCopywritingResolver
{
    /** 视频口播识别结果，唯一可用来源 */
    public const SOURCE_AUDIO_TEXT = 'audio_text';
    /** 识别不到口播 */
    public const SOURCE_NONE = 'none';

    /**
     * @param array $parseData 中台 video2text 接口返回的 data 段
     * @return array{text: string, source: string} text 为空表示该链接提取不到口播文案
     */
    public static function resolve(array $parseData): array
    {
        $audioText = trim((string)($parseData['audio_text'] ?? ''));
        if ($audioText !== '') {
            return ['text' => $audioText, 'source' => self::SOURCE_AUDIO_TEXT];
        }

        return ['text' => '', 'source' => self::SOURCE_NONE];
    }

    /**
     * 取值结果的日志描述
     */
    public static function describe(array $resolved): string
    {
        return sprintf(
            'source=%s len=%d',
            (string)($resolved['source'] ?? self::SOURCE_NONE),
            mb_strlen((string)($resolved['text'] ?? ''))
        );
    }
}
