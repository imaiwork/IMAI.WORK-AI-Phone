<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\api\logic\sv\ToolsLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;

/**
 * 视频号自动获客线索词语库：取词 / 补库 / 移除
 */
class SphClueKeywordService
{
    public const MAX_KEYWORDS_PER_SLOT = 2;

    public const REFILL_COUNT = 30;

    /**
     * 从词语库取一个可用词（不删库）；任务结束再移除。库空或全被排除时先补库再取。
     *
     * @param array<int, string> $exclude 本批/本任务已占用词，避免重复取到同一词
     */
    public static function takeKeyword(AiPersonaTrafficConfig $config, AiPersona $persona, array $exclude = []): string
    {
        $excludeMap = [];
        foreach (self::normalizeKeywords($exclude) as $keyword) {
            $excludeMap[$keyword] = true;
        }

        $keyword = self::pickAvailableKeyword(
            self::normalizeKeywords($config->clue_keywords),
            $excludeMap
        );

        if ($keyword === null) {
            $keywords = self::refill($config, $persona);
            $keyword = self::pickAvailableKeyword($keywords, $excludeMap);
        }

        if ($keyword === null || $keyword === '') {
            throw new \RuntimeException('获客线索词语库为空且补库失败');
        }

        return $keyword;
    }

    /**
     * @param array<int, string> $keywords
     * @param array<string, true> $excludeMap
     */
    private static function pickAvailableKeyword(array $keywords, array $excludeMap): ?string
    {
        foreach ($keywords as $keyword) {
            if ($keyword === '' || isset($excludeMap[$keyword])) {
                continue;
            }
            return $keyword;
        }

        return null;
    }

    /**
     * 调用 AI 生成一批线索词并写回词语库
     *
     * @return array<int, string>
     */
    public static function refill(AiPersonaTrafficConfig $config, AiPersona $persona): array
    {
        $seed = self::resolveSeedKeyword($persona);
        $payload = [
            'user_id' => (int)$config->user_id,
            'channelVersion' => 3,
            'keyword' => $seed,
            'targetCount' => self::REFILL_COUNT,
            'auto' => self::REFILL_COUNT,
        ];

        $ok = ToolsLogic::getSearchTerms($payload);
        if (!$ok) {
            throw new \RuntimeException(ToolsLogic::getError() ?: '生成获客线索词失败');
        }

        $generated = self::normalizeKeywords(ToolsLogic::getReturnData());
        if (empty($generated)) {
            throw new \RuntimeException('生成获客线索词结果为空');
        }

        $config->clue_keywords = $generated;
        $config->update_time = time();
        $config->save();

        return $generated;
    }

    /**
     * 从词语库幂等剔除指定词
     *
     * @param array<int, string> $keywords
     */
    public static function removeKeywords(AiPersonaTrafficConfig $config, array $keywords): void
    {
        $removeMap = [];
        foreach (self::normalizeKeywords($keywords) as $keyword) {
            $removeMap[$keyword] = true;
        }
        if (empty($removeMap)) {
            return;
        }

        $pool = self::normalizeKeywords($config->clue_keywords);
        $remain = [];
        foreach ($pool as $keyword) {
            if (!isset($removeMap[$keyword])) {
                $remain[] = $keyword;
            }
        }

        if (count($remain) === count($pool)) {
            return;
        }

        $config->clue_keywords = $remain;
        $config->update_time = time();
        $config->save();
    }

    /**
     * @param mixed $keywords
     * @return array<int, string>
     */
    public static function normalizeKeywords($keywords): array
    {
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            $keywords = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($keywords)) {
            return [];
        }

        $result = [];
        foreach ($keywords as $keyword) {
            if (is_array($keyword)) {
                $keyword = $keyword['keyword'] ?? $keyword['title'] ?? '';
            }
            $keyword = trim((string)$keyword);
            if ($keyword === '') {
                continue;
            }
            $result[] = $keyword;
        }

        return array_values($result);
    }

    public static function resolveSeedKeyword(AiPersona $persona): string
    {
        $industry = trim((string)($persona->industry ?? ''));
        if ($industry !== '') {
            return $industry;
        }

        $goodsName = trim((string)($persona->goods_name ?? ''));
        if ($goodsName !== '') {
            return $goodsName;
        }

        $personaName = trim((string)($persona->persona_name ?? ''));
        if ($personaName !== '') {
            return $personaName;
        }

        return '获客';
    }

    /**
     * @param mixed $keywords
     * @return array<int, string>
     */
    public static function decodeTaskKeywords($keywords): array
    {
        return self::normalizeKeywords($keywords);
    }
}
