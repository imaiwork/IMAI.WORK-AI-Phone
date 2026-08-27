<?php

namespace app\common\service\hotspot;

use app\common\enum\user\AccountLogEnum;
use app\common\service\ToolsService;

class ResearchService
{
    public static function research(string $topic, string $platform, string $category = '', int $userId = 0): array
    {
        HotspotLog::write(sprintf('联网核清开始：话题=%s 平台=%s 分类=%s', $topic, $platform, $category));
        $searchModel = (string)config('hotspot.ark_search_model');
        HotspotChargeService::precheckArk($userId, $searchModel);
        $found = ToolsService::Ark()->webSearch(PromptService::searchUser($topic, $category));
        HotspotChargeService::settleArk(
            $userId,
            (string)($found['model'] ?? $searchModel),
            is_array($found['usage'] ?? null) ? $found['usage'] : [],
            AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_SEARCH,
            HotspotChargeService::newRef('hot-ark-search'),
            '/responses',
            ['话题' => $topic]
        );
        $summary = (string)($found['text'] ?? '');
        $citations = is_array($found['citations'] ?? null) ? $found['citations'] : [];
        $queries = is_array($found['queries'] ?? null) ? $found['queries'] : [];
        $corePoints = [];
        $angle = '';
        $audience = '';
        HotspotLog::write(sprintf(
            '联网搜索完成：话题=%s 摘要长度=%d 引用数=%d 检索词=%s',
            $topic,
            mb_strlen($summary),
            count($citations),
            HotspotLog::clip($queries, 300)
        ));

        if ($summary !== '') {
            // 第一步搜索已成功并扣费；要点抽取失败时降级返回摘要，不让整单失败（否则第一步费用白扣）
            try {
                HotspotLog::write('开始抽取核心要点：话题=' . $topic);
                $writerModel = (string)config('hotspot.ark_writer_model');
                HotspotChargeService::precheckArk($userId, $writerModel);
                $chat = ToolsService::Ark()->chat(
                    PromptService::extractSystem(),
                    "热点话题：{$topic}\n\n联网搜索整理结果：\n{$summary}",
                    0.3
                );
                HotspotChargeService::settleArk(
                    $userId,
                    (string)($chat['model'] ?? $writerModel),
                    is_array($chat['usage'] ?? null) ? $chat['usage'] : [],
                    AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_CHAT,
                    HotspotChargeService::newRef('hot-ark-chat'),
                    '/chat/completions',
                    ['话题' => $topic, '用途' => '核心要点']
                );
                $raw = (string)($chat['text'] ?? '');
                $parsed = JsonBlockParser::parse($raw);
                if (!is_array($parsed)) {
                    HotspotLog::write('核心要点解析失败，原文=' . HotspotLog::clip($raw, 400));
                }
                if (is_array($parsed)) {
                    $rawPoints = is_array($parsed['core_points'] ?? null) ? $parsed['core_points'] : [];
                    foreach ($rawPoints as $p) {
                        if (!is_array($p) || empty($p['label'])) {
                            continue;
                        }
                        $corePoints[] = [
                            'label' => trim((string)$p['label']),
                            'detail' => trim((string)($p['detail'] ?? '')),
                        ];
                    }
                    $angle = trim((string)(is_scalar($parsed['angle'] ?? '') ? ($parsed['angle'] ?? '') : ''));
                    $audience = trim((string)(is_scalar($parsed['audience'] ?? '') ? ($parsed['audience'] ?? '') : ''));
                    HotspotLog::write(sprintf(
                        '核心要点抽取完成：话题=%s 要点数=%d 角度=%s 受众=%s',
                        $topic,
                        count($corePoints),
                        $angle,
                        $audience
                    ));
                }
            } catch (\Throwable $e) {
                HotspotLog::write(sprintf(
                    '核心要点抽取失败，降级返回摘要：话题=%s 原因=%s',
                    $topic,
                    $e->getMessage()
                ));
            }
        } else {
            HotspotLog::write('联网搜索摘要为空，跳过要点抽取：话题=' . $topic);
        }

        return [
            'topic' => $topic,
            'summary' => $summary,
            'core_points' => $corePoints,
            'angle' => $angle,
            'audience' => $audience,
            'citations' => $citations,
            'search_queries' => $queries,
        ];
    }
}
