<?php

namespace app\common\service\sv;

/**
 * 个微加群自定义关键词匹配（与 MessageHandler 委托共用）
 */
class GroupTriggerKeywordMatcher
{
    /**
     * @param list<string> $contents 已去掉 && 1 标记的用户文本
     * @param list<mixed> $keywords
     */
    public static function match(array $contents, array $keywords): int
    {
        $text = implode('', $contents);
        if ($text === '' || empty($keywords)) {
            return 0;
        }
        foreach ($keywords as $kw) {
            if (self::isMatched($text, (string)$kw)) {
                return 1;
            }
        }
        return 0;
    }

    public static function isMatched(string $text, string $keyword): bool
    {
        $text = trim($text);
        $keyword = trim($keyword);
        if ($text === '' || $keyword === '') {
            return false;
        }

        foreach (self::splitParts($keyword) as $part) {
            if (mb_stripos($text, $part) !== false || mb_stripos($part, $text) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function splitParts(string $keyword): array
    {
        $keyword = trim(str_replace('；', ';', $keyword));
        if ($keyword === '') {
            return [];
        }

        $parts = preg_split('/[，,、；;\s\n]+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [$keyword];
        }

        $parts = array_values(array_unique(array_filter(array_map('trim', $parts), 'strlen')));
        return $parts ?: [$keyword];
    }
}
