<?php

declare(strict_types=1);

namespace app\common\service\draw;

/**
 * PPT 幻灯片生图 prompt（迁自 web-dev-version welcome-hero buildSlidePrompt）
 */
class PptPromptService
{
    /** 用户主题 / 单页正文写入生图 prompt 的上限(10000 字需完整参与生成) */
    private const USER_INPUT_MAX_CHARS = 10000;

    private const STYLE_EN = [
        '简洁专业'   => 'clean professional minimalist business design',
        '数据图表驱动' => 'data-driven design featuring charts, graphs, and infographics',
        '现代创意'   => 'modern bold creative design with vivid colors',
        '沉稳大气'   => 'elegant sophisticated formal corporate design',
        '沿用公司模板' => 'corporate template style, balanced',
    ];

    /**
     * @param array{
     *   is_cover?: bool,
     *   page_num?: int,
     *   total?: int,
     *   title?: string,
     *   content?: string,
     *   topic?: string,
     *   ppt_type?: string,
     *   audience?: string,
     *   style?: string
     * } $args
     */
    public function buildSlidePrompt(array $args): string
    {
        $styleKey = (string)($args['style'] ?? '');
        $styleEn = self::STYLE_EN[$styleKey] ?? 'clean professional design';
        $safeContent = preg_replace('/\s+/u', ' ', (string)($args['content'] ?? '')) ?? '';
        $safeContent = mb_substr(trim($safeContent), 0, self::USER_INPUT_MAX_CHARS);
        $title = mb_substr(trim((string)($args['title'] ?? '')), 0, self::USER_INPUT_MAX_CHARS);
        $topic = mb_substr(trim((string)($args['topic'] ?? '')), 0, self::USER_INPUT_MAX_CHARS);
        $pptType = trim((string)($args['ppt_type'] ?? ''));
        $audience = trim((string)($args['audience'] ?? ''));
        $pageNum = (int)($args['page_num'] ?? 1);
        $total = (int)($args['total'] ?? 1);

        if (!empty($args['is_cover'])) {
            $parts = [
                "A presentation cover slide, 16:9 widescreen layout, {$styleEn}.",
                "Main title: \"{$title}\".",
                "Topic: \"{$topic}\".",
            ];
            if ($pptType !== '') {
                $parts[] = "Type: \"{$pptType}\".";
            }
            if ($audience !== '') {
                $parts[] = "Audience: {$audience}.";
            }
            if ($safeContent !== '') {
                $parts[] = "Subtitle / description: {$safeContent}.";
            }
            $parts[] = 'Visual: clean composition, large title in modern sans-serif, ample white space,';
            $parts[] = 'business presentation aesthetic, photorealistic high-quality rendering.';
            return implode(' ', $parts);
        }

        return implode(' ', [
            "A PowerPoint slide titled \"{$title}\", page {$pageNum} of {$total},",
            "for a presentation about \"{$topic}\". 16:9 widescreen layout, {$styleEn}.",
            "Slide content: {$safeContent}.",
            'Visual: title at top with accent bar, body content visualized through icons / charts / illustrations as appropriate,',
            'business presentation aesthetic, modern sans-serif typography, balanced layout with white space, photorealistic clean rendering.',
        ]);
    }
}
