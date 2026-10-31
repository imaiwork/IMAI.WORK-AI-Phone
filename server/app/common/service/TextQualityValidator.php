<?php

namespace app\common\service;

/**
 * TextQualityValidator
 * 文案质量校验工具
 *
 * 解决两类问题：
 *   1. 英文：AI 返回单词拼接、缺少空格（如 "HelloWorldThisIsATest"）
 *   2. 其他外文（日/韩/法/西等）：乱码、非法 UTF-8 序列、替换字符 U+FFFD
 */
class TextQualityValidator
{
    /**
     * 语言检测结果
     */
    const LANG_ENGLISH         = 'english';          // 纯英文（ASCII A-Za-z ≥ 60%）
    const LANG_SPACE_SEPARATED = 'space_separated';  // 所有空格分词语系（拉丁系/西里尔/希腊等）
    const LANG_CJK             = 'cjk';              // 中日韩（非空格分词）
    const LANG_MIXED           = 'mixed';
    const LANG_UNKNOWN         = 'unknown';

    /**
     * UTF-8 替换字符（乱码占位符）
     */
    const REPLACEMENT_CHAR = "\xEF\xBF\xBD"; // U+FFFD

    // ─── 公共入口 ────────────────────────────────────────────────

    /**
     * 批量校验并清理文案数组
     *
     * 必须保留入参原有的键名：批量标题类场景 content 是「字符串列表」，
     * 而 cozeCopywriting 等场景 content 是「具名字段对象」
     * （rewritten_text / title / word_count / analysis_tags / compliance_status ...）。
     * 一旦用 array_values() 重建为纯列表，下游按字段名取 rewritten_text 就永远为空，
     * 会被判定为「文案仿写失败」。同理，数组类字段（如 analysis_tags）也要原样保留，
     * 不能抹成空串。
     *
     * @param array<int|string, mixed> $texts
     * @return array{content: array<int|string, mixed>, warnings: array<int, array{index:int|string, type:string, original:string, fixed:string}>}
     */
    public static function sanitizeArray(array $texts): array
    {
        $warnings = [];
        $cleaned = [];

        foreach ($texts as $index => $text) {
            // 非字符串值（数组/数字/null 等）不做文案校验，原样保留
            if (!is_string($text) || $text === '') {
                $cleaned[$index] = $text;
                continue;
            }

            // 先做 UTF-8 安全清理
            $safe = self::ensureValidUtf8($text);

            // 校验并记录问题
            $result = self::validate($safe);
            if ($result['status'] === 'warning' || $result['status'] === 'error') {
                $warnings[] = [
                    'index'    => $index,
                    'type'     => $result['type'],
                    'original' => $safe,
                    'fixed'    => $result['suggested_fix'] ?? $safe,
                ];
                // 如果有自动修复建议，返回修复后的版本（前端可对比）
                $cleaned[$index] = $result['suggested_fix'] ?? $safe;
            } else {
                $cleaned[$index] = $safe;
            }
        }

        return [
            'content'  => $cleaned,
            'warnings' => $warnings,
        ];
    }

    /**
     * 单条文案校验
     *
     * @return array{status: string, type: string|null, details: array, suggested_fix: string|null}
     */
    public static function validate(string $text): array
    {
        // 空文本跳过
        $trimmed = trim($text);
        if ($trimmed === '') {
            return ['status' => 'ok', 'type' => null, 'details' => [], 'suggested_fix' => null];
        }

        // 1. 先检测乱码（任何语言都可能有）
        $mojibake = self::detectMojibake($trimmed);
        if ($mojibake['has_garbage']) {
            return [
                'status'         => 'error',
                'type'           => 'mojibake',
                'details'        => $mojibake['details'],
                'suggested_fix'  => self::stripGarbage($trimmed),
            ];
        }

        // 2. 语言检测
        $lang = self::detectLanguage($trimmed);

        // 3. 所有空格分词语种（英文 / 拉丁系法西德意葡 / 西里尔俄乌保 / 希腊等）→ 检测单词拼接/缺空格
        $isSpaceSeparated = ($lang === self::LANG_ENGLISH || $lang === self::LANG_SPACE_SEPARATED);
        if ($isSpaceSeparated) {
            $space = self::detectLatinNoSpace($trimmed);
            if ($space['has_issue']) {
                return [
                    'status'         => 'warning',
                    'type'           => 'latin_no_space',
                    'details'        => $space['details'] ?? ['空格分词语种疑似单词间缺空格'],
                    'suggested_fix'  => $space['suggested_fix'],
                ];
            }
        }

        return ['status' => 'ok', 'type' => null, 'details' => [], 'suggested_fix' => null];
    }

    // ─── UTF-8 安全 ──────────────────────────────────────────────

    /**
     * 确保字符串是合法 UTF-8，非法序列替换为 U+FFFD 后再剥离
     */
    public static function ensureValidUtf8(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        // 尝试自动转换
        $encoding = mb_detect_encoding($text, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ASCII', 'CP1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }
        // 兜底：用 FILTER_FLAG_STRIP_HIGH 风格的清理
        return (string)mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    /**
     * 检测乱码：替换字符、非法控制字符、过高比例的不可打印字符
     *
     * @return array{has_garbage: bool, details: array<string>}
     */
    public static function detectMojibake(string $text): array
    {
        $details = [];

        // 1. 替换字符 U+FFFD
        if (strpos($text, self::REPLACEMENT_CHAR) !== false) {
            $count = substr_count($text, self::REPLACEMENT_CHAR);
            $details[] = "包含 {$count} 个 Unicode 替换字符 (U+FFFD)";
        }

        // 2. 非法 UTF-8 序列（mb_check_encoding 检测）
        if (!mb_check_encoding($text, 'UTF-8')) {
            $details[] = '包含非法 UTF-8 字节序列';
        }

        // 3. 控制字符（允许 \t \n \r）
        $stripped = preg_replace('/[\t\n\r]/u', '', $text);
        if ($stripped !== null && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $stripped)) {
            $details[] = '包含 ASCII 控制字符';
        }

        // 4. 连续异常高的"非目标语言字符"比例（如纯日文中出现大量阿拉伯文/希伯来文）
        // 简化处理：如果总字符数 > 10 且可打印字符占比 < 70%，视为异常
        $total = mb_strlen($text, 'UTF-8');
        if ($total > 10) {
            $printable = preg_match_all('/[\p{L}\p{N}\p{P}\p{S}\s]/u', $text);
            if ($printable > 0 && $printable / $total < 0.7) {
                $details[] = '可打印字符占比异常偏低';
            }
        }

        return [
            'has_garbage' => !empty($details),
            'details'     => $details,
        ];
    }

    /**
     * 剥离乱码字符
     */
    public static function stripGarbage(string $text): string
    {
        // 移除替换字符
        $clean = str_replace(self::REPLACEMENT_CHAR, '', $text);
        // 移除控制字符（保留 \t \n \r）
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
        // 确保 UTF-8 合法
        $clean = self::ensureValidUtf8($clean);
        return trim($clean);
    }

    // ─── 空格分词语种的空格/拼接检测 ──────────────────────────────

    /**
     * 空格分词语种（英文 / 法文 / 西班牙文 / 德文 / 意大利文 / 葡文等拉丁系）
     * 检测文案是否存在"单词拼接/缺空格"问题
     *
     * 判定规则（满足任一即视为异常）：
     *   a) 存在 ≥15 个连续 Unicode 字母类字符不含任何空格
     *   b) 拉丁系文本中单词平均长度（字母数 / (空格数+1)）≥ 12
     *   c) 存在 ≥2 处驼峰式大小写拼接（小写→大写边界）
     *
     * @return array{has_issue: bool, suggested_fix: string}
     */
    public static function detectLatinNoSpace(string $text): array
    {
        $issues = [];

        // 规则 a：连续 Unicode 字母类字符 ≥15 且不含空格
        // \p{L} 匹配任何语言的字母字符（包括带变音的 é ü ñ ç ö 等）
        if (preg_match_all('/[\p{L}]{15,}/u', $text, $matches)) {
            foreach ($matches[0] as $segment) {
                $upperCount = preg_match_all('/\p{Lu}/u', $segment);
                $totalLen = mb_strlen($segment, 'UTF-8');
                // 全大写缩写（≥70%）跳过，如 "CNNABC"
                if ($upperCount >= $totalLen * 0.7) {
                    continue;
                }
                $issues[] = "连续 {$totalLen} 个字母字符无空格（疑似单词拼接）";
                break;
            }
        }

        // 规则 b：单词平均长度（使用 \p{L} 覆盖拉丁扩展字母）
        $spaceCount = substr_count($text, ' ');
        $letterCount = preg_match_all('/\p{L}/u', $text);
        if ($spaceCount > 0 && $letterCount > 0) {
            $avgWordLen = $letterCount / ($spaceCount + 1);
            if ($avgWordLen >= 12) {
                $issues[] = "单词平均长度 {$avgWordLen} 字符，明显偏高（正常拉丁系 4-7）";
            }
        }

        // 规则 c：驼峰拼接（小写 → 大写边界，支持带变音字符如 é → É）
        $camelHits = preg_match_all('/\p{Ll}\p{Lu}/u', $text);
        if ($camelHits >= 2) {
            $issues[] = "发现 {$camelHits} 处大小写拼接（疑似单词间缺空格）";
        }

        $suggestedFix = !empty($issues) ? self::fixLatinSpaces($text) : $text;

        return [
            'has_issue'     => !empty($issues),
            'suggested_fix' => $suggestedFix,
        ];
    }

    /**
     * 拉丁系拼接修复（保守策略）
     *   1. 小写字母 → 大写字母 边界插入空格（camelCase → camel Case）
     *   2. 连续大写后接小写的边界插入空格（HTMLParser → HTML Parser）
     *   3. 数字与字母间插入空格
     *   4. 合并多余空格 + trim
     */
    public static function fixLatinSpaces(string $text): string
    {
        $fixed = $text;
        // 小写 → 大写 边界（\p{Ll} 含带变音小写如 é，\p{Lu} 含 É）
        $fixed = preg_replace('/(\p{Ll})(\p{Lu})/u', '$1 $2', $fixed);
        // 连续大写后接小写：HELLOWorld → HELLO World
        $fixed = preg_replace('/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '$1 $2', $fixed);
        // 数字与字母间
        $fixed = preg_replace('/(\p{L})(\p{N})/u', '$1 $2', $fixed);
        $fixed = preg_replace('/(\p{N})(\p{L})/u', '$1 $2', $fixed);
        // 合并多余空格 + trim
        $fixed = preg_replace('/\s{2,}/u', ' ', $fixed);
        return trim($fixed ?? $text);
    }

    // 兼容旧方法名（保留给外部）
    public static function detectEnglishNoSpace(string $text): array { return self::detectLatinNoSpace($text); }
    public static function fixEnglishSpaces(string $text): string       { return self::fixLatinSpaces($text); }

    // ─── 语言检测 ────────────────────────────────────────────────

    /**
     * 粗粒度语言检测
     *
     * 目标：判断文本是否属于「空格分词」语系，从而决定是否检查单词拼接/缺空格
     *
     * 判定顺序（从高到低）：
     *   1. CJK 中日韩字符占比 ≥ 20%  → LANG_CJK（非空格分词，跳过空格检测）
     *   2. 西里尔字母 ≥ 1            → LANG_SPACE_SEPARATED（俄/乌/保等）
     *   3. 希腊字母 ≥ 1              → LANG_SPACE_SEPARATED
     *   4. 拉丁扩展字母 ≥ 1          → LANG_SPACE_SEPARATED（法/西/德等带变音）
     *   5. 纯英文字母 ≥ 60%          → LANG_ENGLISH（纯英文）
     *   6. 拉丁扩展 + 英文 ≥ 50%     → LANG_SPACE_SEPARATED（拉丁系兜底）
     *   7. 其他含大量 \p{L}           → LANG_SPACE_SEPARATED（其他空格分词语系）
     *   8. 都不满足                   → LANG_MIXED
     *
     * @return string LANG_* 常量
     */
    public static function detectLanguage(string $text): string
    {
        // trim 后再算，纯空白直接返回 UNKNOWN
        $text = trim($text);
        $total = mb_strlen($text, 'UTF-8');
        if ($total === 0) {
            return self::LANG_UNKNOWN;
        }

        // 各类字符计数（全部用 Unicode 码点 + u 修饰符，避免多字节 UTF-8 误匹配）
        $cjk      = preg_match_all('/[\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}\x{AC00}-\x{D7AF}\x{3130}-\x{318F}]/u', $text);
        $en       = preg_match_all('/[A-Za-z]/', $text);                             // 纯 ASCII 英文字母
        $latinExt = preg_match_all('/[\x{00C0}-\x{00FF}]/u', $text);                 // 拉丁扩展（é ç ñ ö 等）
        $cyrillic = preg_match_all('/[\x{0400}-\x{04FF}]/u', $text);                 // 西里尔（俄文 Украина Россия）
        $greek    = preg_match_all('/[\x{0370}-\x{03FF}]/u', $text);                 // 希腊字母
        $allLetters = preg_match_all('/\p{L}/u', $text);                             // 所有 Unicode 字母

        // 优先 1：CJK 中日韩（中文/日文/韩文）— 这类语言天然不用空格分词
        if ($cjk / $total >= 0.2) {
            return self::LANG_CJK;
        }

        // 优先 2：西里尔字母（俄文/乌克兰/保加利亚/白俄/塞尔维亚等）
        if ($cyrillic >= 1) {
            return self::LANG_SPACE_SEPARATED;
        }

        // 优先 3：希腊字母
        if ($greek >= 1) {
            return self::LANG_SPACE_SEPARATED;
        }

        // 优先 4：拉丁扩展字母（带变音 → 法文/西文/德文/意大利文/葡萄牙文/荷兰文等）
        if ($latinExt >= 1) {
            return self::LANG_SPACE_SEPARATED;
        }

        // 优先 5：纯英文（ASCII A-Za-z ≥ 60%）
        if ($en / $total >= 0.6) {
            return self::LANG_ENGLISH;
        }

        // 优先 6：拉丁扩展 + 英文兜底（拉丁系但英文占比不够高）
        if (($latinExt + $en) / $total >= 0.5) {
            return self::LANG_SPACE_SEPARATED;
        }

        // 优先 7：所有 Unicode 字母占比 ≥ 60% 且不是 CJK → 视为空格分词（覆盖其他小众空格分词语系）
        if ($allLetters / $total >= 0.6) {
            return self::LANG_SPACE_SEPARATED;
        }

        return self::LANG_MIXED;
    }
}
