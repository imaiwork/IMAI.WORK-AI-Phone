<?php

namespace app\common\service\hotspot;

use app\common\service\ToolsService;

class AnalyzeService
{
    public const GOALS = ['sell', 'leads', 'traffic', 'brand', 'engage'];
    public const DIRECTIONS = ['观点输出', '干货科普', '故事讲述', '争议讨论', '产品植入'];

    public static function analyze(string $topic, string $platform, string $summary, array $corePoints, array $persona, string $portrait = '', int $userId = 0): array
    {
        $pointsText = '（无）';
        $lines = [];
        foreach ($corePoints as $p) {
            if (!is_array($p) || empty($p['label'])) {
                continue;
            }
            $lines[] = '- ' . $p['label'] . '：' . ($p['detail'] ?? '');
        }
        if ($lines !== []) {
            $pointsText = implode("\n", $lines);
        }

        $personaText = '名称：' . ($persona['name'] ?? '');
        if (!empty($persona['tag'])) {
            $personaText .= "\n定位：" . $persona['tag'];
        }
        if (!empty($persona['tone'])) {
            $personaText .= "\n说话风格：" . $persona['tone'];
        }
        if (!empty($persona['business'])) {
            $personaText .= "\n主营业务：" . $persona['business'];
        }

        $portraitText = $portrait !== ''
            ? "\n\n该热点在平台上的真实人群画像（判断受众重叠时优先采信）：\n{$portrait}"
            : '';

        $user = '平台：' . PromptService::platformLabel($platform) . "\n"
            . "热点话题：{$topic}\n\n"
            . "热点核心要点：\n{$pointsText}\n\n"
            . '背景材料：' . "\n" . mb_substr($summary, 0, 1500)
            . $portraitText . "\n\n博主人设档案：\n{$personaText}";

        HotspotLog::write(sprintf(
            '人设契合分析开始：话题=%s 平台=%s 人设=%s 要点数=%d 摘要长度=%d 画像长度=%d',
            $topic,
            $platform,
            (string)($persona['name'] ?? ''),
            count($corePoints),
            mb_strlen($summary),
            mb_strlen($portrait)
        ));
        $writerModel = (string)config('hotspot.ark_writer_model');
        HotspotChargeService::precheckArk($userId, $writerModel);
        $chat = ToolsService::Ark()->chat(PromptService::analyzeSystem(), $user, 0.5);
        HotspotChargeService::settleArk(
            $userId,
            (string)($chat['model'] ?? $writerModel),
            is_array($chat['usage'] ?? null) ? $chat['usage'] : [],
            \app\common\enum\user\AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_CHAT,
            HotspotChargeService::newRef('hot-ark-chat'),
            '/chat/completions',
            ['话题' => $topic, '用途' => '人设契合']
        );
        $raw = (string)($chat['text'] ?? '');
        $parsed = JsonBlockParser::parse($raw);

        $res = [
            'topic' => $topic,
            'persona_name' => (string)($persona['name'] ?? ''),
            'fit_score' => 0,
            'fit_reason' => '',
            'hooks' => [],
            'risks' => [],
            'recommended_goal' => 'traffic',
            'recommended_direction' => '观点输出',
        ];

        if (!is_array($parsed)) {
            // 返回 0 分兜底会被当成真实分析落库、热榜也会误标「已分析」，明确报错让用户重试
            HotspotLog::write('人设契合解析失败，原文=' . HotspotLog::clip($raw, 400));
            throw new HotspotUpstreamException('分析结果异常，请重试');
        }

        $res['fit_score'] = max(0, min(100, (int)($parsed['fit_score'] ?? 0)));
        $res['fit_reason'] = trim((string)(is_scalar($parsed['fit_reason'] ?? '') ? ($parsed['fit_reason'] ?? '') : ''));
        $rawHooks = is_array($parsed['hooks'] ?? null) ? $parsed['hooks'] : [];
        foreach ($rawHooks as $h) {
            if (!is_array($h) || empty($h['label'])) {
                continue;
            }
            $res['hooks'][] = [
                'label' => trim((string)$h['label']),
                'detail' => trim((string)($h['detail'] ?? '')),
            ];
        }
        $rawRisks = is_array($parsed['risks'] ?? null) ? $parsed['risks'] : [];
        foreach ($rawRisks as $r) {
            if (!is_scalar($r)) {
                continue;
            }
            $r = trim((string)$r);
            if ($r !== '') {
                $res['risks'][] = $r;
            }
        }
        $goal = trim((string)($parsed['recommended_goal'] ?? ''));
        $res['recommended_goal'] = in_array($goal, self::GOALS, true) ? $goal : 'traffic';
        $direction = trim((string)($parsed['recommended_direction'] ?? ''));
        $res['recommended_direction'] = in_array($direction, self::DIRECTIONS, true) ? $direction : '观点输出';
        if ($direction !== '' && $res['recommended_direction'] !== $direction) {
            HotspotLog::write('人设契合方向非法，已回落观点输出：原文=' . $direction);
        }

        HotspotLog::write(sprintf(
            '人设契合分析完成：话题=%s 分数=%d 钩子数=%d 风险数=%d 目标=%s 方向=%s',
            $topic,
            $res['fit_score'],
            count($res['hooks']),
            count($res['risks']),
            $res['recommended_goal'],
            $res['recommended_direction']
        ));
        return $res;
    }
}
