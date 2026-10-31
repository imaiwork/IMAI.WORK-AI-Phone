<?php

namespace app\common\service;

use think\facade\Config;

/**
 * AI 找素材候选相关性闸门
 *
 * 背景：中台按搜索词抓素材（如「美容」），返回结果可能混入近邻行业内容（如美发素材），
 * 且抓取结果此前不做任何内容相关性校验即下载入库，用户侧表现为「搜美容抓到美发」。
 *
 * 两道闸门：
 * - passes() 文本闸门（保守负向过滤）：只在有文字证据时拒绝。关键词与候选标题的二元组命中数
 *   需达到类型阈值且不低于类型比例，长关键词按比例提高门槛，只有单个二元组零散交集
 *   不再放行；证据不足（无标题、非中文无法判定）一律放行，只做本地字符串运算。
 *   阈值按关键词类型区分（实体/菜品/场景/动作/人物/品牌/氛围/抽象），氛围与抽象类直接跳过文本闸门。
 * - passesVisual() 画面闸门：文本闸门对无标题候选必然放行，无法识别「搜木门样品抓到手链图」
 *   这类画面不精准素材；画面闸门调用视觉模型直接看图，要求「画面主体就是关键词」，近似品类、
 *   相近氛围、未指定品牌的品牌门头都判「否」；回答模糊或视觉服务异常时放行并记日志。
 * - candidateScore() 不参与是否通过，仅用于同一批搜索结果排序，避免「首条通过即用」。
 */
class AiMaterialRelevanceService
{
    /** 候选素材默认收集的文本字段（覆盖驼峰/下划线常见命名，字段缺失时自动跳过） */
    private const DEFAULT_TEXT_FIELDS = [
        'title',
        'desc',
        'description',
        'keywords',
        'keyword',
        'tags',
        'tag',
        'title_text',
        'search_text',
    ];

    /** 单个参与匹配的字段最大长度，超长截断 */
    private const MAX_FIELD_LENGTH = 200;

    /** @var callable|null 测试钩子：接管 candidate_eval 事件输出（设置后不再写日志，仅回传事件载荷） */
    private static $candidateEvalHook = null;

    /** @var callable|null 测试钩子：接管画面闸门判定（设置后跳过视觉模型调用），返回 bool 生效、null 回退真实调用 */
    private static $visualHook = null;

    /**
     * 设置画面闸门测试钩子：仅 tests/ 注入，生产勿用。
     * @param callable|null $hook function(string $visualUrl, string $keyword): ?bool
     */
    public static function setVisualHook(?callable $hook): void
    {
        self::$visualHook = $hook;
    }

    /**
     * 分类型文本闸门阈值（可在 config/project.php 的 keyword_type_thresholds 覆盖）：
     * - 具体主体类（entity/dish/scene/action）要求至少 2 个二元组、40% 命中，避免近邻品类混入；
     * - 人物类允许多种呈现，放宽到 1 个二元组、30%，否则「朋友聚会」会被「闺蜜聚餐」误杀；
     * - 品牌类最严，要求品牌名全部二元组命中（等价于原文包含），避免同行业其他品牌蒙混；
     * - 氛围/抽象类没有可检画面主体，跳过文本闸门，完全交给画面闸门判图。
     */
    private const KEYWORD_TYPE_THRESHOLDS = [
        'entity' => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],
        'dish' => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],
        'scene' => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],
        'action' => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],
        'person' => ['min_hits' => 1, 'min_ratio' => 0.3, 'text_enabled' => true],
        'brand' => ['min_hits' => 1, 'min_ratio' => 1.0, 'text_enabled' => true],
        'mood' => ['min_hits' => 0, 'min_ratio' => 0.0, 'text_enabled' => false],
        'abstract' => ['min_hits' => 0, 'min_ratio' => 0.0, 'text_enabled' => false],
    ];

    /**
     * 候选是否与搜索关键词相关（通过者可继续转码/入库）
     *
     * @param array<string, mixed> $item 中台抓取返回的单条候选
     * @param string $keyword 本次抓取使用的搜索关键词
     * @param string $logChannel 拒绝日志写入的日志通道，为空时不写日志
     * @param string $logContext 日志补充说明（如路径分支），用于区分调用方
     * @param string $keywordType 关键词类型（见 MaterialKeywordPlanner::TYPE_*），为空时按关键词自动识别
     */
    public static function passes(
        array $item,
        string $keyword,
        string $logChannel = '',
        string $logContext = '',
        string $keywordType = ''
    ): bool {
        $relevanceConfig = Config::get('project.ai_material_relevance');
        if (is_array($relevanceConfig) && ($relevanceConfig['enabled'] ?? true) === false) {
            return true;
        }
        $keyword = trim($keyword);
        if ($keyword === '') {
            return true;
        }
        $keywordType = self::resolveKeywordType($keyword, $keywordType);
        $thresholds = self::thresholdsFor($keywordType);
        if (($thresholds['text_enabled'] ?? true) === false) {
            // 氛围/抽象类关键词没有可检的字面主体，文本闸门直接放行，交给画面闸门
            self::emitGateEval('text_gate', true, '类型不参与文本闸门', $item, $keyword, $keywordType, $logChannel, $logContext);
            return true;
        }
        $text = self::extractItemText($item);
        if ($text === '') {
            // 证据不足：候选无标题类文本，放行
            self::emitGateEval('text_gate', true, '候选无标题类文本，证据不足', $item, $keyword, $keywordType, $logChannel, $logContext);
            return true;
        }

        $passed = false;
        $reason = '二元组命中未达标';
        // 品牌类要求品牌名原文出现（忽略空格与连字符），不走二元组统计：
        // 「便利店」这种品类后缀的二元组命中不能证明是该品牌
        if ($keywordType === MaterialKeywordPlanner::TYPE_BRAND) {
            if (self::containsRawKeyword($keyword, $text)) {
                $passed = true;
                $reason = '品牌原文命中';
            } else {
                $reason = '品牌原文缺失';
            }
        } elseif (self::containsCjk($keyword)) {
            // 标题非中文（如英文标题）时无法与中文关键词比对，证据不足：放行
            if (!self::containsCjk($text)) {
                $passed = true;
                $reason = '标题非中文，证据不足';
            } else {
                // 中文关键词：标题全量包含，或命中足够的二元组且满足连续性要求
                $stats = self::textStats($keyword, $text);
                if ($stats['contains']) {
                    $passed = true;
                    $reason = '标题全量包含关键词';
                } elseif ($stats['hits'] >= self::requiredHits($stats['grams'], $thresholds)
                    && $stats['run'] >= self::minConsecutive($stats['grams'])) {
                    $passed = true;
                    $reason = '二元组命中达标';
                }
            }
        } elseif (self::containsCjk($text)) {
            // 关键词非中文、标题为中文，无法直接比对，证据不足：放行
            $passed = true;
            $reason = '关键词非中文，证据不足';
        } elseif (mb_stripos($text, $keyword, 0, 'UTF-8') !== false) {
            // 双方都是非中文：小写包含即通过
            $passed = true;
            $reason = '非中文小写包含';
        }

        if (!$passed) {
            self::logRejection($item, $keyword, $text, $logChannel, $logContext, $keywordType);
        }
        self::emitGateEval('text_gate', $passed, $reason, $item, $keyword, $keywordType, $logChannel, $logContext);
        return $passed;
    }

    /**
     * 从候选中提取可用于相关性判断的文本（多个可用字段时用空格连接）
     *
     * @param array<string, mixed> $item
     * @param array<int, string>|null $fields 指定字段列表，为 null 时使用默认字段
     */
    public static function extractItemText(array $item, ?array $fields = null): string
    {
        $fields ??= self::DEFAULT_TEXT_FIELDS;
        $parts = [];
        foreach ($fields as $field) {
            $value = $item[$field] ?? null;
            if (is_array($value)) {
                $value = implode(' ', array_map('strval', array_filter($value, 'is_scalar')));
            }
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            $text = trim((string)$value);
            if ($text === '') {
                continue;
            }
            $parts[] = mb_substr($text, 0, self::MAX_FIELD_LENGTH, 'UTF-8');
        }
        return implode(' ', array_unique($parts));
    }

    /**
     * 搜索关键词在文本中命中的中文二元组数量（按去重后的二元组计）
     */
    public static function countGramHits(string $keyword, string $text): int
    {
        $needle = self::normalizeCjkGramSource($keyword);
        $haystack = self::normalizeCjkGramSource($text);
        if ($needle === '' || $haystack === '') {
            return 0;
        }
        $gramCount = mb_strlen($needle, 'UTF-8') - 1;
        if ($gramCount < 1) {
            // 单字关键词退化为单字包含
            return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false ? 1 : 0;
        }
        $hits = 0;
        for ($offset = 0; $offset < $gramCount; $offset++) {
            $gram = mb_substr($needle, $offset, 2, 'UTF-8');
            if (mb_strpos($haystack, $gram, 0, 'UTF-8') !== false) {
                $hits++;
            }
        }
        return $hits;
    }

    /**
     * 关键词在候选文本中的二元组命中统计（供文本闸门与候选排序共用）
     *
     * @return array{contains: bool, hits: int, grams: int, run: int}
     */
    public static function textStats(string $keyword, string $text): array
    {
        $needle = self::normalizeCjkGramSource($keyword);
        $haystack = self::normalizeCjkGramSource($text);
        $contains = $needle !== '' && $haystack !== ''
            && mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
        $grams = max(0, mb_strlen($needle, 'UTF-8') - 1);
        if ($needle === '' || $haystack === '') {
            return ['contains' => false, 'hits' => 0, 'grams' => $grams, 'run' => 0];
        }
        if ($grams < 1) {
            return ['contains' => $contains, 'hits' => $contains ? 1 : 0, 'grams' => 0, 'run' => $contains ? 1 : 0];
        }

        $hits = 0;
        $run = 0;
        $currentRun = 0;
        for ($offset = 0; $offset < $grams; $offset++) {
            $gram = mb_substr($needle, $offset, 2, 'UTF-8');
            if (mb_strpos($haystack, $gram, 0, 'UTF-8') !== false) {
                $hits++;
                $currentRun++;
                $run = max($run, $currentRun);
            } else {
                $currentRun = 0;
            }
        }
        return ['contains' => $contains, 'hits' => $hits, 'grams' => $grams, 'run' => $run];
    }

    /**
     * 最低二元组命中数：短词至少 2 个二元组有交集，长词按比例递增
     *
     * @param array{min_hits: int, min_ratio: float, text_enabled: bool} $thresholds
     */
    private static function requiredHits(int $grams, array $thresholds): int
    {
        if ($grams <= 1) {
            return 1;
        }
        $minHits = max(1, (int)($thresholds['min_hits'] ?? 2));
        $ratio = (float)($thresholds['min_ratio'] ?? 0.4);
        $ratio = max(0.0, min(1.0, $ratio));
        $required = max($minHits, (int)ceil($grams * $ratio));
        return max(1, min($grams, $required));
    }

    /**
     * 取关键词类型对应的阈值；未识别的类型按具体实体处理。
     * 具体主体类（entity/dish/scene/action）未单独配置时沿用全局 min_hits/min_ratio，
     * 保持老站点调过的全局值继续生效；品牌/人物/氛围等类型只认类型配置，避免全局值把收紧的阈值放宽。
     *
     * @return array{min_hits: int, min_ratio: float, text_enabled: bool}
     */
    private static function thresholdsFor(string $keywordType): array
    {
        $type = $keywordType !== '' ? $keywordType : 'entity';
        $thresholds = self::KEYWORD_TYPE_THRESHOLDS[$type] ?? self::KEYWORD_TYPE_THRESHOLDS['entity'];

        $configured = Config::get('project.ai_material_relevance.keyword_type_thresholds');
        if (is_array($configured) && isset($configured[$type]) && is_array($configured[$type])) {
            $thresholds = array_merge($thresholds, $configured[$type]);
        } elseif (in_array($type, ['entity', 'dish', 'scene', 'action'], true)) {
            $global = Config::get('project.ai_material_relevance');
            if (is_array($global)) {
                if (array_key_exists('min_hits', $global)) {
                    $thresholds['min_hits'] = $global['min_hits'];
                }
                if (array_key_exists('min_ratio', $global)) {
                    $thresholds['min_ratio'] = $global['min_ratio'];
                }
            }
        }

        return [
            'min_hits' => max(0, (int)($thresholds['min_hits'] ?? 2)),
            'min_ratio' => max(0.0, min(1.0, (float)($thresholds['min_ratio'] ?? 0.4))),
            'text_enabled' => ($thresholds['text_enabled'] ?? true) !== false,
        ];
    }

    /** 未显式传类型时按关键词自动识别，保证所有调用方都吃分类型阈值 */
    private static function resolveKeywordType(string $keyword, string $keywordType): string
    {
        $keywordType = trim($keywordType);
        return $keywordType !== '' ? $keywordType : MaterialKeywordPlanner::classify($keyword);
    }

    /** 原文包含判定：两侧小写并去掉空白/连字符/下划线后再比对（7-Eleven 常被写成 7Eleven 或 7-ELEVEN） */
    public static function containsRawKeyword(string $keyword, string $text): bool
    {
        $normalize = static function (string $value): string {
            $value = mb_strtolower($value, 'UTF-8');
            $cleaned = preg_replace('/[\s\-_·]+/u', '', $value);
            return is_string($cleaned) ? $cleaned : $value;
        };
        $keyword = $normalize($keyword);
        $text = $normalize($text);
        if ($keyword === '' || $text === '') {
            return false;
        }
        return mb_strpos($text, $keyword, 0, 'UTF-8') !== false;
    }

    /**
     * 连续命中要求：0 表示只要总命中数达标即可；调高可减少「零散字面交集」误放行
     */
    private static function minConsecutive(int $grams): int
    {
        if ($grams < 2) {
            return 0;
        }
        return max(0, (int)Config::get('project.ai_material_relevance.min_consecutive', 0));
    }

    /**
     * 候选排序分：同一批搜索结果里优先选最贴合关键词、带封面、时长适中的候选。
     * 只用于排序，不参与通过/拒绝判定。
     *
     * @param array<string, mixed> $item
     */
    public static function candidateScore(array $item, string $keyword, string $type = 'video'): float
    {
        $score = 0.0;
        $keyword = trim($keyword);
        $text = self::extractItemText($item);
        if ($keyword !== '' && $text !== '') {
            if (self::containsCjk($keyword) && self::containsCjk($text)) {
                $stats = self::textStats($keyword, $text);
                $score += $stats['contains'] ? 100.0 : min(60.0, $stats['hits'] * 12.0 + $stats['run'] * 8.0);
            } elseif (mb_stripos($text, $keyword, 0, 'UTF-8') !== false) {
                $score += 60.0;
            }
        }
        if (trim((string)($item['image'] ?? '')) !== '') {
            $score += 8.0;
        }
        if ($type === 'video') {
            $duration = (float)($item['duration'] ?? 0);
            if ($duration >= 5 && $duration <= 15) {
                $score += 10.0;
            } elseif ($duration >= 3 && $duration <= 30) {
                $score += 4.0;
            }
        }
        return $score;
    }

    /**
     * 画面相关性闸门：视觉模型判断素材画面是否真的是关键词所指的事物/场景。
     *
     * 调用时机：素材转存成功后、入库前（图片传文件地址，视频传封面地址）。
     * 判定口径：模型明确回答「否」类结论才拒绝；空回答、模糊回答、视觉服务异常一律放行，
     * 避免视觉服务故障掐断找素材主流程。
     *
     * @param string $visualUrl 用于判图的画面地址（必须是中台可访问的 http(s) 地址）
     * @param string $keyword 本次抓取使用的搜索关键词
     * @param string $logChannel 日志通道，为空不写日志
     * @param string $logContext 日志补充说明（如路径分支）
     * @param string $keywordType 关键词类型（见 MaterialKeywordPlanner::TYPE_*），为空时按关键词自动识别
     */
    public static function passesVisual(
        string $visualUrl,
        string $keyword,
        string $logChannel = '',
        string $logContext = '',
        string $keywordType = ''
    ): bool {
        $relevanceConfig = Config::get('project.ai_material_relevance');
        if (is_array($relevanceConfig) && ($relevanceConfig['vision_enabled'] ?? true) === false) {
            return true;
        }
        $visualUrl = trim($visualUrl);
        $keyword = trim($keyword);
        if ($visualUrl === '' || $keyword === '') {
            // 视频无封面等无画面可判的场景：证据不足，放行
            self::emitGateEval('visual_gate', true, '无画面可判，证据不足', [], $keyword, $keywordType, $logChannel, $logContext, '', $visualUrl);
            return true;
        }

        if (self::$visualHook !== null) {
            $verdict = null;
            try {
                $verdict = (self::$visualHook)($visualUrl, $keyword);
            } catch (\Throwable $e) {
                $verdict = null;
            }
            if (is_bool($verdict)) {
                self::emitGateEval('visual_gate', $verdict, $verdict ? '测试钩子判定通过' : '测试钩子判定拒绝', [], $keyword, $keywordType, $logChannel, $logContext, '', $visualUrl);
                return $verdict;
            }
        }

        $timeout = is_array($relevanceConfig) ? max(3, (int)($relevanceConfig['vision_timeout'] ?? 8)) : 8;
        $question = self::visualQuestion($keyword, self::resolveKeywordType($keyword, $keywordType));
        $answer = '';
        try {
            $response = app(ToolsService::class)
                ->setApiUrl('/api/qwen/image/parse')
                ->setRequest(['file_url' => $visualUrl, 'question' => $question])
                ->setMethod('POST')
                ->setTimeout(3, $timeout)
                ->send()
                ->response;
            if ((int)($response['code'] ?? 0) === 10000) {
                $answer = trim((string)($response['data']['output'] ?? ''));
            }
        } catch (\Throwable $e) {
            self::logVisual($logChannel, $logContext, $keyword, $visualUrl, '视觉校验异常放行：' . $e->getMessage());
            self::emitGateEval('visual_gate', true, '视觉服务异常放行', [], $keyword, $keywordType, $logChannel, $logContext, $answer, $visualUrl);
            return true;
        }

        if ($answer === '') {
            self::logVisual($logChannel, $logContext, $keyword, $visualUrl, '视觉校验无结果放行');
            self::emitGateEval('visual_gate', true, '视觉无结果放行', [], $keyword, $keywordType, $logChannel, $logContext, $answer, $visualUrl);
            return true;
        }
        if (self::isNegativeVisualAnswer($answer)) {
            self::logVisual($logChannel, $logContext, $keyword, $visualUrl, '画面与关键词无关，拒绝', $answer);
            self::emitGateEval('visual_gate', false, '画面与关键词无关', [], $keyword, $keywordType, $logChannel, $logContext, $answer, $visualUrl);
            return false;
        }
        self::emitGateEval('visual_gate', true, '画面判定通过', [], $keyword, $keywordType, $logChannel, $logContext, $answer, $visualUrl);
        return true;
    }

    /**
     * 画面闸门提问文案：要求模型按「画面主体就是该词」判定，
     * 近似品类/相近氛围/未指定品牌的品牌门头都算不符，避免「同行业近邻」被放行。
     * 品牌类改为判「画面是否出现该品牌标识」，人物类改为判「画面主体是否是该人物/人群」，
     * 否则品牌词与人物词会被「主体就是」句式误杀。
     *
     * @param string $keywordType 关键词类型，为空时按关键词自动识别
     */
    public static function visualQuestion(string $keyword, string $keywordType = ''): string
    {
        $type = self::resolveKeywordType($keyword, $keywordType);
        if ($type === MaterialKeywordPlanner::TYPE_BRAND) {
            return sprintf(
                '请判断这张图片中是否出现「%s」的品牌标识、门店门头或产品包装。'
                . '如果只是同行业、相似品类或者其他品牌，请回答"否"。只回答"是"或"否"。',
                $keyword
            );
        }
        if ($type === MaterialKeywordPlanner::TYPE_PERSON) {
            return sprintf(
                '请判断这张图片的画面主体是否是「%s」，即以该人物或人群作为画面主要对象。'
                . '如果只是同类人群但并非该描述，或者画面主体不是人物，请回答"否"。只回答"是"或"否"。',
                $keyword
            );
        }
        return sprintf(
            '请判断这张图片的画面主体是否就是「%s」。'
            . '如果只是同行业、相似品类或相近氛围的场景，或者画面主体是品牌门店/品牌logo而关键词并未指定该品牌，请回答"否"。'
            . '只回答"是"或"否"。',
            $keyword
        );
    }

    /**
     * 视觉模型回答是否为明确否定。模型按提示只答"是/否"，这里对常见否定措辞做兜底识别。
     *
     * 边界口径：
     * - 「是」开头一律放行：肯定结论的解释文字可能含「无关/不是」等否定词
     *   （如"是，画面中没有无关物品"），不能误判为拒绝；「是否…」设问式回答同理按模糊放行。
     * - 否定词只在回答头部 30 字窗口内扫描，窗口外（长回答尾部）的否定词不触发拒绝。
     */
    public static function isNegativeVisualAnswer(string $answer): bool
    {
        $head = mb_substr(trim($answer), 0, 30, 'UTF-8');
        if ($head === '') {
            return false;
        }
        if (mb_substr($head, 0, 1, 'UTF-8') === '是') {
            return false;
        }
        if (mb_strpos($head, '否', 0, 'UTF-8') !== false) {
            return true;
        }
        foreach (['不相关', '不是', '不符合', '无关'] as $negativeWord) {
            if (mb_strpos($head, $negativeWord, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return (bool)preg_match('/^no\b/i', $head);
    }

    /**
     * 画面闸门日志，失败时静默（日志不可用不能影响找素材主流程）
     */
    private static function logVisual(string $logChannel, string $logContext, string $keyword, string $visualUrl, string $result, string $answer = ''): void
    {
        if ($logChannel === '') {
            return;
        }
        try {
            \think\facade\Log::channel($logChannel)->write(sprintf(
                '[AI素材画面相关性] %s%s：关键词=%s 画面=%s 模型回答=%s',
                $result,
                $logContext !== '' ? '(' . $logContext . ')' : '',
                mb_substr($keyword, 0, 50, 'UTF-8'),
                mb_substr($visualUrl, 0, 180, 'UTF-8'),
                mb_substr($answer, 0, 60, 'UTF-8')
            ));
        } catch (\Throwable $e) {
        }
    }

    /**
     * 归一化用于二元组匹配的文本：小写、去空白与常见中英文标点，仅保留参与匹配的字面
     */
    private static function normalizeCjkGramSource(string $text): string
    {
        $lower = mb_strtolower(trim($text), 'UTF-8');
        $cleaned = preg_replace('/[\s]+|\P{L}\P{N}+/u', '', $lower) ?? '';
        return $cleaned;
    }

    /**
     * 文本是否包含中日韩字符
     */
    private static function containsCjk(string $text): bool
    {
        return (bool)preg_match('/[\x{4e00}-\x{9fff}\x{3040}-\x{30ff}\x{ac00}-\x{d7af}]/u', $text);
    }

    /**
     * 设置 candidate_eval 测试钩子：设置后事件只回传给钩子、不写日志，便于用例断言决策链。
     *
     * @param callable|null $hook function(array $payload): void
     */
    public static function setCandidateEvalHook(?callable $hook): void
    {
        self::$candidateEvalHook = $hook;
    }

    /**
     * candidate_eval 事件统一出口（闸门与服务调用方共用）。
     *
     * 仅当 ai_material_grab.enabled 开启时才输出，保证「开关关闭=旧行为」不新增日志；
     * 日志失败静默，不影响找素材主流程。
     *
     * @param array<string, mixed> $payload
     */
    public static function logCandidateEval(string $logChannel, array $payload): void
    {
        $grabConfig = Config::get('project.ai_material_grab');
        if (!is_array($grabConfig) || (bool)($grabConfig['enabled'] ?? false) !== true) {
            return;
        }
        if (self::$candidateEvalHook !== null) {
            try {
                (self::$candidateEvalHook)($payload);
            } catch (\Throwable $e) {
            }
            return;
        }
        if ($logChannel === '') {
            return;
        }
        try {
            \think\facade\Log::channel($logChannel)->write('getAiMaterials监控: ' . json_encode(
                array_merge(['stage' => 'candidate_eval'], $payload),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
        } catch (\Throwable $e) {
        }
    }

    /**
     * 闸门级 candidate_eval 事件：通过也记，便于按关键词还原每条候选的判定原因。
     *
     * @param array<string, mixed> $item
     */
    private static function emitGateEval(
        string $gate,
        bool $passed,
        string $reason,
        array $item,
        string $keyword,
        string $keywordType,
        string $logChannel,
        string $logContext,
        string $answer = '',
        string $visualUrl = ''
    ): void {
        $url = $visualUrl !== '' ? $visualUrl : (string)($item['link'] ?? '');
        $score = null;
        if ($item !== [] && $keyword !== '') {
            $score = round(self::candidateScore($item, $keyword, 'video'), 2);
        }
        self::logCandidateEval($logChannel, [
            'gate' => $gate,
            'decision' => $passed ? '通过' : '拒绝',
            'reason' => $reason,
            'keyword' => mb_substr($keyword, 0, 50, 'UTF-8'),
            'keyword_type' => $keywordType,
            'log_context' => $logContext,
            'score' => $score,
            'url' => mb_substr($url, 0, 180, 'UTF-8'),
            'answer' => mb_substr($answer, 0, 60, 'UTF-8'),
        ]);
    }

    /**
     * 写拒绝日志，失败时静默（日志不可用不能影响找素材主流程）
     *
     * @param array<string, mixed> $item
     */
    private static function logRejection(
        array $item,
        string $keyword,
        string $text,
        string $logChannel,
        string $logContext,
        string $keywordType = ''
    ): void {
        if ($logChannel === '') {
            return;
        }
        try {
            \think\facade\Log::channel($logChannel)->write(sprintf(
                '[AI素材相关性] 拒绝无关候选%s：关键词=%s 类型=%s 标题文本=%s 链接=%s',
                $logContext !== '' ? '(' . $logContext . ')' : '',
                mb_substr($keyword, 0, 50, 'UTF-8'),
                $keywordType !== '' ? $keywordType : '-',
                mb_substr($text, 0, 100, 'UTF-8'),
                mb_substr((string)($item['link'] ?? ''), 0, 180, 'UTF-8')
            ));
        } catch (\Throwable $e) {
        }
    }
}
