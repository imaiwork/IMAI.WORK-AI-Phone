<?php

namespace app\common\service;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Overtrue\Pinyin\Pinyin;

class FuzzyMatcherService
{
    private float $threshold = 0.7;
    private array $keywords = [];

    public function __construct()
    {
        Jieba::init(['dict' => 'small']); // 使用小词典
        Finalseg::init();
    }

    /**
     * 计算两个字符串的相似度（0-1之间）
     */
    public function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normalizeText($str1);
        $str2 = $this->normalizeText($str2);

        $len1 = mb_strlen($str1, 'UTF-8');
        $len2 = mb_strlen($str2, 'UTF-8');

        if ($len1 == 0 || $len2 == 0) {
            return 0;
        }

        // if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
        //     return 1;
        // }

        if ($this->has_pinyin($str1) || $this->has_pinyin($str2)) {
            $converter = new ProfessionalPinyinConverter();
            $str1 = $converter->convert($str1);
            $str2 = $converter->convert($str2);
        }


        $similar = $this->similarText($str1, $str2);
        if ($similar >= $this->threshold) {
            return $similar;
        }

        $wordSimilarity = $this->calculateWordSimilarity($str1, $str2);
        if ($wordSimilarity >= $this->threshold) {
            return $wordSimilarity;
        }

        // 计算Levenshtein距离
        $distance = $this->levenshteinUtf8($str1, $str2);

        // 计算相似度：1 - (距离 / 最大长度)
        $maxLength = max($len1, $len2);
        $similarity = 1 - ($distance / $maxLength);

        return $similarity;
    }

    /**
     * 标准化文本：去除标点、转换为小写等
     */
    private function normalizeText(string $text): string
    {
        // 转换为小写
        $text = mb_strtolower($text, 'UTF-8');

        // 去除标点符号
        //$text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);

        // 去除多余空格
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * 支持UTF-8的Levenshtein距离计算
     */
    private function levenshteinUtf8(string $str1, string $str2): int
    {
        $char1 = $this->splitUtf8($str1);
        $char2 = $this->splitUtf8($str2);

        $len1 = count($char1);
        $len2 = count($char2);

        if ($len1 == 0) {
            return $len2;
        }
        if ($len2 == 0) {
            return $len1;
        }

        // 初始化矩阵
        $matrix = [];
        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i] = [$i];
        }
        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        // 填充矩阵
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($char1[$i - 1] == $char2[$j - 1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,      // 删除
                    $matrix[$i][$j - 1] + 1,      // 插入
                    $matrix[$i - 1][$j - 1] + $cost // 替换
                );
            }
        }

        return $matrix[$len1][$len2];
    }

    /**
     * 使用similar_text计算相似度
     */
    private function similarText(string $str1, string $str2): float
    {
        $str1 = $this->normalizeText($str1);
        $str2 = $this->normalizeText($str2);

        similar_text($str1, $str2, $percent);

        return $percent / 100;
    }

    /**
     * 分词相似度计算
     */
    private function calculateWordSimilarity1(string $text1, string $text2): float
    {
        $words1 = Jieba::cut($text1);
        $words2 = Jieba::cut($text2);

        if (empty($words1) || empty($words2)) {
            return 0;
        }
        // 计算Jaccard相似度
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        if (count($union) == 0) {
            return 0;
        }
        return count($intersection) / count($union);
    }

    /**
     * 分词相似度计算 - 优化版本
     * 添加了词频加权、缓存、停用词过滤等功能
     */
    private function calculateWordSimilarity(string $text1, string $text2): float
    {
        // 输入验证
        if (!is_string($text1) || !is_string($text2)) {
            return 0;
        }

        // 构建缓存键
        $cacheKey1 = md5($text1);
        $cacheKey2 = md5($text2);
        $cacheKey = "word_sim_{$cacheKey1}_{$cacheKey2}";

        // 检查是否有缓存结果
        static $cache = [];
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        // 分词并过滤停用词
        $words1 = $this->filterStopWords(Jieba::cut($text1));
        $words2 = $this->filterStopWords(Jieba::cut($text2));

        if (empty($words1) || empty($words2)) {
            return 0;
        }

        // 计算词频
        $freq1 = array_count_values($words1);
        $freq2 = array_count_values($words2);

        // 计算加权Jaccard相似度
        $intersectionScore = 0;
        $unionScore = 0;

        // 计算交集部分的加权和
        foreach ($freq1 as $word => $count1) {
            if (isset($freq2[$word])) {
                // 使用最小频率作为交集的权重
                $intersectionScore += min($count1, $freq2[$word]);
            }
            // 累加第一个文本的词频
            $unionScore += $count1;
        }

        // 累加第二个文本中不在第一个文本中的词频
        foreach ($freq2 as $word => $count2) {
            if (!isset($freq1[$word])) {
                $unionScore += $count2;
            }
        }

        if ($unionScore == 0) {
            return 0;
        }

        $similarity = $intersectionScore / $unionScore;

        // 缓存结果
        $cache[$cacheKey] = $similarity;

        return $similarity;
    }

    /**
     * 过滤停用词
     */
    private function filterStopWords(array $words): array
    {
        // 常见中文停用词列表
        $stopWords = [
            '的',
            '了',
            '在',
            '是',
            '我',
            '有',
            '和',
            '就',
            '不',
            '人',
            '都',
            '一',
            '一个',
            '上',
            '也',
            '很',
            '到',
            '说',
            '要',
            '去',
            '你',
            '会',
            '着',
            '没有',
            '看',
            '好',
            '自己',
            '这',
            '请问',
            '大概',
            '呢',
            '啊',
            '想'
        ];

        return array_filter($words, function ($word) use ($stopWords) {
            // 过滤停用词和单字符
            return !in_array($word, $stopWords) && mb_strlen($word, 'UTF-8') > 1;
        });
    }

    private function has_pinyin(string $text): bool
    {
        return preg_match('/\b[a-z]+[1-5]?\b/i', $text) === 1;
    }

    /**
     * 将UTF-8字符串分割为字符数组
     */
    private function splitUtf8(string $str): array
    {
        preg_match_all('/./u', $str, $matches);
        return $matches[0];
    }

    /**
     * 检查用户输入是否匹配关键词
     */
    public function isMatch(string $userInput): array
    {
        foreach ($this->keywords as $keyword) {
            $similarity = $this->calculateSimilarity($userInput, $keyword);

            if ($similarity >= $this->threshold) {
                return [
                    'matched' => true,
                    'keyword' => $keyword,
                    'similarity' => $similarity,
                    'input' => $userInput
                ];
            }
        }

        return [
            'matched' => false,
            'similarity' => 0,
            'input' => $userInput
        ];
    }

    /**
     * 设置关键词
     */
    public function setKeywords(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * 设置阈值
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    public function destroy(): void
    {
        // Jieba::destroy();
        // Finalseg::destroy();
        $this->keywords = [];
        $this->threshold = 0.0;

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}


class ProfessionalPinyinConverter
{
    private $pinyin = null;

    public function __construct()
    {
        if ($this->pinyin === null) {
            $this->pinyin = new Pinyin();
        }
    }

    public function convert(string $chineseText, string $options = 'none'): string
    {
        return $this->pinyin->fullSentence($chineseText, $options);
    }

    public function convertToInitials(string $chineseText, string $separator = ''): string
    {
        return $this->pinyin->abbr($chineseText, $separator);
    }
}
