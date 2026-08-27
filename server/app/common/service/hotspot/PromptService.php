<?php

namespace app\common\service\hotspot;

class PromptService
{
    public static function searchUser(string $topic, string $category = ''): string
    {
        $hint = $category !== ''
            ? "这个话题在平台上的分类是「{$category}」，可作为理解语境的参考。"
            : '';
        return str_replace(
            ['{topic}', '{category_hint}'],
            [$topic, $hint],
            (string)config('hotspot_prompts.search_user')
        );
    }

    public static function extractSystem(): string
    {
        return (string)config('hotspot_prompts.extract_system');
    }

    public static function analyzeSystem(): string
    {
        return (string)config('hotspot_prompts.analyze_system');
    }

    public static function platformLabel(string $platform): string
    {
        $map = config('hotspot_prompts.platform_labels') ?: [];
        return (string)($map[$platform] ?? $platform);
    }

    public static function goalLabel(string $goal): string
    {
        $map = config('hotspot_prompts.goal_labels') ?: [];
        return (string)($map[$goal] ?? $goal);
    }

    public static function materialLabel(string $mode): string
    {
        $map = config('hotspot_prompts.material_labels') ?: [];
        return (string)($map[$mode] ?? $mode);
    }

    public static function goalRule(string $goal): string
    {
        $map = config('hotspot_prompts.goal_rules') ?: [];
        return (string)($map[$goal] ?? '');
    }

    public static function materialRule(string $mode): string
    {
        $map = config('hotspot_prompts.material_rules') ?: [];
        return (string)($map[$mode] ?? '');
    }
}
