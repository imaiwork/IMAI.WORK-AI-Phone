<?php

namespace app\common\service\recall;

/**
 * 召回工具
 */
class RecallUtils
{
    /**
     * @notes 结果融合 - RRF算法
     * @param array $recallResults
     *      [
     *          ["k" => 60, "list" => [ embeddings ]],
     *          ["k" => 60, "list" => [ fullTexts ]],
     *          ["k" => 58, "list" => [ reRankResults ]]
     *      ]
     * @return array
     * @author fzr
     */
    public static function rrfConcatResults(array $recallResults): array
    {
        // 过滤空列表
        $recallResults = array_filter($recallResults, fn($item) => !empty($item['list']));
        $recallResults = array_values($recallResults); // 重置数组键

        if (count($recallResults) === 0) {
            return [];
        }

        if (count($recallResults) === 1) {
            return $recallResults[0]['list'];
        }

        // 使用RRF处理每个召回结果
        $itemMap = [];
        foreach ($recallResults as $result) {
            $k = $result['k'];
            foreach ($result['list'] as $index => $data) {
                $uuid = (string)$data['uuid'];
                $rank = $index + 1;
                $rrfScore = 1 / ($k + $rank);

                if (array_key_exists($uuid, $itemMap)) {
                    // 如果项目存在，更新RRF分数
                    $itemMap[$uuid] = array_merge($itemMap[$uuid], $data);
                    $itemMap[$uuid]['rrf_score'] += $rrfScore;
                } else {
                    // 添加新的项目，并初始RRF
                    $itemMap[$uuid] = array_merge($data, ['rrf_score' => $rrfScore]);
                }
            }
        }

        // 按RRF分数降序排序
        usort($itemMap, fn($a, $b) => $b['rrf_score'] <=> $a['rrf_score']);

        // 从结果中删除临时rrf_score字段
        $finalResults = [];
        foreach ($itemMap as $item) {
            $finalItem = array_filter($item, fn($key) => $key !== 'rrf_score', ARRAY_FILTER_USE_KEY);
            $finalResults[] = $finalItem;
        }

        return $finalResults;
    }

    /**
     * @notes 相似度过滤
     * @param array $results
     * @param string $mode
     * @param bool $rerank
     * @param float $similar
     * @return array
     */
    public static function filterMaxScore(array $results, string $mode, bool $rerank, float $similar): array
    {
        if ($rerank) {
            return array_filter($results, fn($item) => ($item['rerank_score'] ?? 0) >= $similar);
        }

        if ($mode === 'similar') {
            return array_filter($results, fn($item) => ($item['emb_score'] ?? 0) >= $similar);
        }

        return $results;
    }

    /**
     * @notes 最大Tokens过滤
     * @param array $results
     * @param int $maxTokens
     * @return array
     * @author fzr
     */
    public static function filterMaxTokens(array $results, int $maxTokens): array
    {
        $totalTokens = 0;
        $filterLists = [];
        foreach ($results as $item) {
            $text = $item["question"].$item["answer"];
            $totalTokens += mb_strlen($text);//TODO tokens计算需要调整
            $filterLists[] = $item;
            if ($totalTokens > $maxTokens) {
                break;
            }
        }

        return $filterLists;
    }
}