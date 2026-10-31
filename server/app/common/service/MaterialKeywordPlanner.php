<?php

namespace app\common\service;

use think\facade\Config;
use think\facade\Log;

/**
 * 素材检索关键词规划器
 *
 * 背景：远程关键词提取返回的是「叙事短语」（如「温馨街边小店」「朋友简餐聚会」「一人食场景」），
 * 直接作为 searchTerm 投给素材库搜索，召回的是字面近邻而非画面语义（如 7-Eleven 门头、男子用餐特写）。
 *
 * 本服务把叙事短语改写成素材库能检索到的具体画面查询词：
 * - 去掉情绪/风格修饰词（温馨、治愈、氛围感、高级感……）；
 * - 去掉「场景/画面/氛围」等泛化后缀；
 * - 去掉菜品/内容的修饰前缀（焦边、香煎、秘制……），保留主体名词；
 * - 复合短语按连接词拆分，取中心名词作为主查询，其余片段作为备选查询。
 *
 * 优先用中台对话模型改写（保留句序、最多返回主查询+1条备选）；模型不可用时走纯规则兜底，
 * 规则兜底至少能去掉情绪词与泛化后缀，不依赖外部服务。
 */
class MaterialKeywordPlanner
{
    /** 默认对话模型：与 AI 帮写提示词同口径，中台识别为 flash 档 */
    private const DEFAULT_MODEL = 'deepseek';

    /** 输出预算：几十条查询词的 JSON 很小，留足余量 */
    private const MAX_TOKENS = 1200;

    /** 单次规划最多处理的原始关键词数量，避免文案过长把 prompt 撑爆 */
    private const MAX_KEYWORDS = 30;

    /** 参与提示词的文案长度上限 */
    private const MAX_TEXT_CHARS = 800;

    /** 单条查询词长度上限（素材库搜索词越短越稳） */
    private const MAX_QUERY_CHARS = 10;

    /** 情绪/风格/氛围修饰词：素材库检索不需要，反而污染召回 */
    private const MOOD_WORDS = [
        '氛围感', '高级感', '电影感', '生活感', '仪式感', '烟火气', '小清新',
        '温馨', '治愈', '温暖', '浪漫', '高级', '清新', '复古', '唯美', '简约', '精致',
        '舒适', '惬意', '安静', '宁静', '热闹', '欢快', '轻松', '搞笑', '感动',
        '质感', '烟火', '满满', '非常', '特别', '超级',
    ];

    /** 场景后缀：留着会把实体词变成泛场景词 */
    private const SCENE_SUFFIXES = ['场景', '画面', '氛围', '感觉', '风格', '特写', '镜头', '合集', '片段', '瞬间', '时刻'];

    /** 菜品/内容修饰前缀：检索主体名词更稳 */
    private const MODIFIER_PREFIXES = [
        '焦边', '香煎', '秘制', '家常', '手工', '自制', '网红', '同款', '爆款',
        '现做', '现烤', '刚出锅', '热气腾腾', '金黄', '酥脆',
    ];

    /** 烹饪方式前缀：作为备选查询的主体词 */
    private const COOKING_PREFIXES = ['香煎', '煎', '烤', '煮', '炒', '蒸', '炸', '焖', '炖', '拌'];

    /**
     * 关键词类型：不同类型在文本闸门/画面闸门上的判定强度不同。
     * - entity：具体物件（木门样品、手链），必须有字面证据；
     * - dish：菜品/食品（红咖喱酱、煎牛肉饼），同 entity；
     * - scene：场景/场所（街边小店、一人食），同 entity；
     * - action：动作过程（切菜、开箱），同 entity；
     * - person：人物/人群（朋友聚会、宝妈），主体允许多种呈现，阈值放宽；
     * - brand：品牌（蜜雪冰城、7-Eleven），必须原文出现品牌名，阈值最严；
     * - mood：情绪/氛围（温馨、治愈），没有可检的字面主体，跳过文本闸门交给画面闸门；
     * - abstract：抽象概念/泛话题（打工人日常、生活记录），同 mood。
     */
    public const TYPE_ENTITY = 'entity';
    public const TYPE_DISH = 'dish';
    public const TYPE_SCENE = 'scene';
    public const TYPE_ACTION = 'action';
    public const TYPE_PERSON = 'person';
    public const TYPE_BRAND = 'brand';
    public const TYPE_MOOD = 'mood';
    public const TYPE_ABSTRACT = 'abstract';

    /** 品牌词表：命中即按品牌类处理，文本闸门要求品牌名原文出现 */
    private const BRAND_WORDS = [
        '7-eleven', 'seven eleven', '711', '全家', '罗森', '便利蜂',
        '蜜雪冰城', '茶百道', '古茗', '沪上阿姨', '喜茶', '奈雪的茶', '瑞幸', '库迪',
        '麦当劳', '肯德基', '汉堡王', '必胜客', '海底捞', '西贝', '老乡鸡', '太二',
        '可口可乐', '百事可乐', '农夫山泉', '元气森林', '伊利', '蒙牛',
        '三只松鼠', '良品铺子', '百草味',
        '华为', '小米', '苹果', '三星', 'oppo', 'vivo', '荣耀', '一加',
        '百度', '腾讯', '阿里巴巴', '淘宝', '天猫', '京东', '拼多多', '抖音', '快手', '微信', '支付宝',
        '美团', '饿了么', '大众点评', '携程', '滴滴', '顺丰', '中国移动', '中国联通', '中国电信',
        '比亚迪', '特斯拉', '蔚来', '理想', '小鹏', '问界',
        '耐克', '阿迪达斯', '李宁', '安踏', '优衣库', 'zara', '宜家', '无印良品', '名创优品',
        '兰蔻', '雅诗兰黛', '欧莱雅', '完美日记', '花西子', '大疆',
    ];

    /** 抽象概念/泛话题后缀：以这些词收尾的关键词没有可检画面主体 */
    private const ABSTRACT_MARKERS = [
        '日常', '生活', '故事', '记录', '分享', '体验', '攻略', '干货', '教程', '清单', '指南',
        '心得', '感悟', '成长', '坚持', '梦想', '心情', '情绪', '状态', '方式', '方法', '技巧',
        '理念', '价值', '意义', '创意', '灵感', '职场', '打工', '上班', '下班', '周末', '假期',
        '季节', '人生', '陪伴', '幸福', '美好', '真实', '自然', '简单', '自由', '快乐',
    ];

    /** 人物/人群词：主体是人时允许多种呈现，文本闸门放宽 */
    private const PERSON_WORDS = [
        '朋友', '闺蜜', '情侣', '夫妻', '家人', '亲人', '孩子', '宝宝', '妈妈', '爸爸',
        '奶奶', '爷爷', '姐姐', '哥哥', '弟弟', '妹妹', '同事', '同学', '老师', '医生',
        '厨师', '农民', '工人', '老板', '员工', '主播', '博主', '达人', '模特', '男子',
        '女子', '男生', '女生', '男孩', '女孩', '老人', '大爷', '大妈', '打工人',
        '新郎', '新娘', '顾客', '客户', '路人', '司机', '服务员', '运动员', '健身教练',
        '宝妈', '留学生', '旅行者', '登山者', '铲屎官',
    ];

    /** 动作/过程词：画面主体是一个动作过程（切菜、开箱、测评） */
    private const ACTION_WORDS = [
        '切菜', '炒菜', '做饭', '烹饪', '烘焙', '搅拌', '揉面', '装盘', '摆盘', '下锅',
        '翻炒', '倒入', '开箱', '拆箱', '试吃', '试用', '试穿', '测评', '对比', '演示',
        '展示', '讲解', '教程', '步骤', '过程', '打包', '拿起', '打开', '走进', '跑步',
        '跳舞', '唱歌', '弹琴', '写字', '画画', '打字', '开车', '骑行', '健身', '运动',
        '拍照', '自拍', '种草', '推荐', '分享',
    ];

    /** 菜品/食品词：按食品类处理，主体名词必须命中 */
    private const DISH_WORDS = [
        '咖喱', '牛肉', '猪肉', '鸡肉', '羊肉', '鱼肉', '排骨', '鸡翅', '牛排', '烤肉',
        '汉堡', '披萨', '寿司', '沙拉', '三明治', '薯条', '薯片', '蛋糕', '面包', '饼干',
        '甜甜圈', '冰淇淋', '雪糕', '奶茶', '咖啡', '果汁', '豆浆', '酸奶', '牛奶',
        '火锅', '烧烤', '串串', '烤串', '饺子', '包子', '馒头', '面条', '拉面', '意面',
        '米饭', '炒饭', '盖饭', '便当', '简餐', '早餐', '午餐', '晚餐', '汤', '粥',
        '酱', '辣椒酱', '番茄酱', '奶油', '芝士', '奶酪', '巧克力', '糖果', '水果',
        '豆腐', '鸡蛋', '坚果', '零食', '甜品', '点心', '月饼', '粽子', '布丁', '果冻',
        '饮料', '汽水', '啤酒', '红酒', '白酒', '蜂蜜',
    ];

    /** 场景/场所词：画面主体是一个具体空间或生活场景 */
    private const SCENE_WORDS = [
        '街边', '街头', '街角', '小店', '店铺', '门店', '门头', '店面', '便利店', '超市',
        '商场', '菜市场', '夜市', '集市', '地摊', '路边摊', '餐厅', '咖啡厅', '咖啡馆',
        '奶茶店', '厨房', '客厅', '卧室', '阳台', '办公室', '工位', '教室', '医院',
        '工地', '车间', '仓库', '展厅', '工作室', '直播间', '演播室', '地铁', '公交',
        '车站', '机场', '公园', '海边', '沙滩', '山顶', '森林', '草原', '城市', '夜景',
        '清晨', '午后', '傍晚', '夜晚', '雨天', '雪天', '家里', '房间', '婚礼', '聚会',
        '聚餐', '派对', '露营', '旅行', '逛街', '通勤', '一人食', '下午茶', '早午餐',
        '操场', '球场', '健身房', '泳池', '图书馆', '书店', '花园', '农场', '果园', '牧场',
    ];

    /**
     * 规划关键词。
     *
     * @param array<int, string> $keywords 远程提取出的原始关键词，顺序即文案顺序
     * @param string $text 改编后的文案，用于模型理解上下文与定位句序
     * @param float $deadline 本轮抓取的整体截止时间（microtime），剩余不足 3 秒时跳过模型调用
     * @return array{items: array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}>, source: string}
     */
    public static function plan(array $keywords, string $text = '', float $deadline = 0.0): array
    {
        $keywords = self::uniqueKeywords($keywords);
        if ($keywords === []) {
            return ['items' => [], 'source' => 'empty'];
        }

        $config = self::config();
        $template = self::planByRules($keywords, $text);
        $source = 'rules';
        if ($config['enabled'] && self::canCallLlm($deadline)) {
            try {
                $llmItems = self::planByLlm($keywords, $text, $config['timeout'], $config['max_keywords']);
                if ($llmItems !== []) {
                    $template = self::mergeLlmItems($template, $llmItems);
                    $source = 'llm';
                }
            } catch (\Throwable $e) {
                Log::channel('shanjian')->write(sprintf(
                    '[手动纯AI关键词规划] 模型改写失败，回退规则改写：%s',
                    mb_substr($e->getMessage(), 0, 120, 'UTF-8')
                ));
            }
        }

        return ['items' => $template, 'source' => $source];
    }

    /**
     * 把规划结果拍平成搜索用的查询词列表：先全部主查询（按句序），再逐轮补备选查询。
     *
     * @param array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}> $items
     * @return array<int, string>
     */
    public static function flatten(array $items, ?int $maxAlternates = null): array
    {
        $planned = self::plannedQueries($items, $maxAlternates);
        return array_map(static function (array $row): string {
            return $row['query'];
        }, $planned);
    }

    /**
     * 与 flatten 顺序一致，但保留每个查询词所属的关键词类型，供闸门按类型取判定强度。
     *
     * @param array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}> $items
     * @return array<int, array{query: string, type: string}>
     */
    public static function plannedQueries(array $items, ?int $maxAlternates = null): array
    {
        if ($maxAlternates === null) {
            $maxAlternates = self::config()['max_alternates'];
        }
        $queries = [];
        foreach ($items as $item) {
            $primary = trim((string)($item['primary'] ?? ''));
            if ($primary !== '') {
                $queries[] = ['query' => $primary, 'type' => (string)($item['type'] ?? '')];
            }
        }
        for ($round = 0; $round < $maxAlternates; $round++) {
            foreach ($items as $item) {
                $alternate = trim((string)($item['alternates'][$round] ?? ''));
                if ($alternate !== '') {
                    $queries[] = ['query' => $alternate, 'type' => (string)($item['type'] ?? '')];
                }
            }
        }

        $unique = [];
        foreach ($queries as $row) {
            $key = mb_strtolower($row['query'], 'UTF-8');
            if (!isset($unique[$key])) {
                $unique[$key] = $row;
            }
        }
        return array_values($unique);
    }

    /**
     * 纯规则改写单个关键词，供模型不可用时兜底，也供回归脚本直接断言。
     *
     * @return array{primary: string, alternates: array<int, string>}
     */
    public static function rewriteByRules(string $keyword): array
    {
        $original = trim($keyword);
        if ($original === '') {
            return ['primary' => '', 'alternates' => []];
        }
        if (!self::containsCjk($original)) {
            // 非中文关键词不做中文规则改写，保持原样
            return ['primary' => self::clampQuery($original), 'alternates' => []];
        }

        $text = self::cleanText($original);
        $text = self::stripWords($text, self::MOOD_WORDS);
        $text = self::stripSuffixes($text, self::SCENE_SUFFIXES);
        $text = self::stripPrefixes($text, self::MODIFIER_PREFIXES);

        $segments = preg_split('/[的之与和及、,，\/]+/u', $text) ?: [];
        $candidates = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            $segment = self::stripWords($segment, self::MOOD_WORDS);
            $segment = self::stripSuffixes($segment, self::SCENE_SUFFIXES);
            $segment = self::stripPrefixes($segment, self::MODIFIER_PREFIXES);
            if (mb_strlen($segment, 'UTF-8') >= 2) {
                $candidates[] = $segment;
            }
        }

        if ($candidates === []) {
            $fallback = $text !== '' ? $text : $original;
            return ['primary' => self::clampQuery($fallback), 'alternates' => []];
        }

        // 中文中心词通常在短语尾部：取最后一个片段作为主查询
        $primary = self::clampQuery((string)end($candidates));
        $alternates = [];
        foreach ($candidates as $candidate) {
            $candidate = self::clampQuery($candidate);
            if ($candidate !== $primary) {
                $alternates[] = $candidate;
            }
        }

        // 去掉烹饪方式前缀作为备选词（如「煎牛肉饼」→「牛肉饼」）
        $entity = self::stripCookingPrefix($primary);
        if ($entity !== '' && $entity !== $primary) {
            $alternates[] = self::clampQuery($entity);
        }

        $alternates = array_values(array_unique(array_filter($alternates, static function (string $value): bool {
            return mb_strlen($value, 'UTF-8') >= 2;
        })));

        return ['primary' => $primary, 'alternates' => $alternates];
    }

    /**
     * 识别关键词类型，供文本闸门/画面闸门按类型取判定强度。
     *
     * 判定顺序按「误判代价」排序：品牌名一旦漏判会让品牌关键词放宽，排最前；
     * 以「日常/生活/记录」等收尾的泛话题没有可检画面主体，先判抽象；
     * 人物与动作优先于菜品与场景（「朋友简餐聚会」应判人物而非菜品）；
     * 情绪词放最后，只有去掉所有具体主体后仍剩情绪词才按氛围处理。
     * 非中文关键词不套中文词表，默认按具体实体的阈值处理。
     */
    public static function classify(string $keyword): string
    {
        $original = trim($keyword);
        if ($original === '') {
            return self::TYPE_ENTITY;
        }
        $normalized = self::normalizeForMatch(mb_strtolower($original, 'UTF-8'));

        if (self::matchesAny($normalized, self::BRAND_WORDS)) {
            return self::TYPE_BRAND;
        }
        if (self::endsWithAny($normalized, self::ABSTRACT_MARKERS)) {
            return self::TYPE_ABSTRACT;
        }
        if (self::matchesAny($normalized, self::PERSON_WORDS)) {
            return self::TYPE_PERSON;
        }
        if (self::matchesAny($normalized, self::ACTION_WORDS)) {
            return self::TYPE_ACTION;
        }
        if (self::matchesAny($normalized, self::DISH_WORDS)) {
            return self::TYPE_DISH;
        }
        if (self::matchesAny($normalized, self::SCENE_WORDS)) {
            return self::TYPE_SCENE;
        }
        if (self::matchesAny($normalized, self::MOOD_WORDS)) {
            return self::TYPE_MOOD;
        }
        return self::TYPE_ENTITY;
    }

    /**
     * @param array<int, string> $words
     */
    private static function matchesAny(string $text, array $words): bool
    {
        if ($text === '') {
            return false;
        }
        foreach ($words as $word) {
            $needle = self::normalizeForMatch($word);
            if ($needle !== '' && mb_strpos($text, $needle, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, string> $words
     */
    private static function endsWithAny(string $text, array $words): bool
    {
        if ($text === '') {
            return false;
        }
        foreach ($words as $word) {
            $needle = self::normalizeForMatch($word);
            $length = mb_strlen($needle, 'UTF-8');
            if ($length > 0 && mb_substr($text, -$length, null, 'UTF-8') === $needle) {
                return true;
            }
        }
        return false;
    }

    /** 类型匹配用的归一化：小写、去空白与连字符（品牌名如 7-Eleven 常被写成 711/7eleven） */
    private static function normalizeForMatch(string $text): string
    {
        $cleaned = preg_replace('/[\s\-_·]+/u', '', $text);
        return is_string($cleaned) ? $cleaned : $text;
    }

    /**
     * @param array<int, string> $keywords
     * @return array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}>
     */
    private static function planByRules(array $keywords, string $text): array
    {
        $items = [];
        foreach ($keywords as $keyword) {
            $rewritten = self::rewriteByRules($keyword);
            $primary = $rewritten['primary'] !== '' ? $rewritten['primary'] : self::clampQuery($keyword);
            if ($primary === '') {
                continue;
            }
            $items[] = [
                'original' => $keyword,
                'primary' => $primary,
                'alternates' => $rewritten['alternates'],
                'sentence_index' => self::sentenceIndexOf($text, $keyword, count($items)),
                'type' => self::classify($keyword),
            ];
        }
        return $items;
    }

    /**
     * 调中台对话模型改写关键词，失败返回空数组由规则兜底。
     *
     * @param array<int, string> $keywords
     * @return array<string, array{primary: string, alternates: array<int, string>}> 以原始关键词小写为键
     */
    private static function planByLlm(array $keywords, string $text, int $timeout, int $maxKeywords): array
    {
        $limited = array_slice($keywords, 0, $maxKeywords);
        $numbered = [];
        foreach ($limited as $index => $keyword) {
            $numbered[] = ($index + 1) . '. ' . $keyword;
        }

        $system = '你是短视频素材检索关键词规划器，负责把叙事型关键词改写为素材库能检索到的具体画面查询词。'
            . '规则：1. 去掉情绪、风格、氛围修饰词（温馨、治愈、氛围感、高级感等）；'
            . '2. 去掉「场景/画面/氛围/感觉/风格」等泛化后缀；'
            . '3. 复合短语拆出具体主体，例如「朋友简餐聚会」改写为「朋友聚餐」「餐桌」；'
            . '4. 每条查询 2~6 个汉字，最多 2 条，主查询必须是画面中能直接看到的具体主体；'
            . '5. 严禁输出文案原句、形容词堆砌或无法拍到的抽象概念；'
            . '6. 严格按输入顺序输出 JSON 数组，格式：[{"original":"原始关键词","queries":["主查询","备选查询"]}]，不要输出任何其它文字。';

        $user = '文案：' . mb_substr($text, 0, self::MAX_TEXT_CHARS, 'UTF-8') . "\n"
            . "关键词：\n" . implode("\n", $numbered) . "\n\n只输出 JSON 数组。";

        $body = [
            'model' => self::preferredModel(),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.2,
            'max_tokens' => self::MAX_TOKENS,
            'stream' => false,
            'enable_thinking' => false,
        ];

        $response = app(ToolsService::class)
            ->setRequest($body)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setTimeout(3, $timeout)
            ->send()
            ->response;
        if ((int)($response['code'] ?? 0) !== 10000) {
            return [];
        }
        $data = (array)($response['data'] ?? []);
        $content = trim((string)($data['choices'][0]['message']['content'] ?? ''));
        $decoded = self::decodeJsonArray($content);
        if ($decoded === []) {
            return [];
        }

        $result = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $original = trim((string)($entry['original'] ?? ''));
            $queries = $entry['queries'] ?? ($entry['query'] ?? []);
            $queries = is_array($queries) ? $queries : [$queries];
            $clean = [];
            foreach ($queries as $query) {
                if (!is_string($query) && !is_numeric($query)) {
                    continue;
                }
                $query = self::clampQuery((string)$query);
                if (mb_strlen($query, 'UTF-8') >= 2) {
                    $clean[] = $query;
                }
            }
            $clean = array_values(array_unique($clean));
            if ($original === '' || $clean === []) {
                continue;
            }
            $result[mb_strtolower($original, 'UTF-8')] = [
                'primary' => $clean[0],
                'alternates' => array_slice($clean, 1),
            ];
        }
        return $result;
    }

    /**
     * 用模型结果覆盖规则结果；模型漏掉的关键词保留规则改写，保证每个原始关键词都有查询词。
     *
     * @param array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}> $rules
     * @param array<string, array{primary: string, alternates: array<int, string>}> $llm
     * @return array<int, array{original: string, primary: string, alternates: array<int, string>, sentence_index: int, type: string}>
     */
    private static function mergeLlmItems(array $rules, array $llm): array
    {
        $items = [];
        foreach ($rules as $rule) {
            $key = mb_strtolower((string)$rule['original'], 'UTF-8');
            if (isset($llm[$key])) {
                $rule['primary'] = $llm[$key]['primary'];
                if ($llm[$key]['alternates'] !== []) {
                    $rule['alternates'] = array_values(array_unique(array_merge(
                        $rule['alternates'],
                        $llm[$key]['alternates']
                    )));
                }
            }
            $items[] = $rule;
        }
        return $items;
    }

    /**
     * 解析模型返回的 JSON 数组，兼容 ```json 包裹与前后说明文字。
     *
     * @return array<int, mixed>
     */
    private static function decodeJsonArray(string $content): array
    {
        if ($content === '') {
            return [];
        }
        $start = strpos($content, '[');
        $end = strrpos($content, ']');
        if ($start === false || $end === false || $end <= $start) {
            return [];
        }
        $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, string> $keywords
     * @return array<int, string>
     */
    private static function uniqueKeywords(array $keywords): array
    {
        $unique = [];
        foreach ($keywords as $keyword) {
            if (!is_string($keyword) && !is_numeric($keyword)) {
                continue;
            }
            $keyword = trim((string)$keyword);
            if ($keyword === '') {
                continue;
            }
            $key = mb_strtolower($keyword, 'UTF-8');
            if (!isset($unique[$key])) {
                $unique[$key] = $keyword;
            }
        }
        return array_values($unique);
    }

    private static function sentenceIndexOf(string $text, string $keyword, int $fallback): int
    {
        $text = trim($text);
        if ($text === '' || $keyword === '') {
            return $fallback;
        }
        $position = mb_strpos($text, $keyword, 0, 'UTF-8');
        if ($position === false) {
            return $fallback;
        }
        $prefix = mb_substr($text, 0, $position, 'UTF-8');
        $count = preg_match_all('/[。！？!?；;\n]+/u', $prefix);
        return $count === false ? $fallback : (int)$count;
    }

    private static function cleanText(string $text): string
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\-]+/u', '', trim($text));
        return is_string($cleaned) ? $cleaned : trim($text);
    }

    /**
     * @param array<int, string> $words
     */
    private static function stripWords(string $text, array $words): string
    {
        return str_replace(self::sortByLengthDesc($words), '', $text);
    }

    /**
     * @param array<int, string> $suffixes
     */
    private static function stripSuffixes(string $text, array $suffixes): string
    {
        $suffixes = self::sortByLengthDesc($suffixes);
        for ($round = 0; $round < 2; $round++) {
            foreach ($suffixes as $suffix) {
                $length = mb_strlen($suffix, 'UTF-8');
                if ($length > 0 && mb_substr($text, -$length, null, 'UTF-8') === $suffix) {
                    $text = mb_substr($text, 0, -$length, 'UTF-8');
                    break;
                }
            }
        }
        return $text;
    }

    /**
     * @param array<int, string> $prefixes
     */
    private static function stripPrefixes(string $text, array $prefixes): string
    {
        $prefixes = self::sortByLengthDesc($prefixes);
        for ($round = 0; $round < 2; $round++) {
            foreach ($prefixes as $prefix) {
                $length = mb_strlen($prefix, 'UTF-8');
                if (mb_strpos($text, $prefix, 0, 'UTF-8') === 0) {
                    $text = mb_substr($text, $length, null, 'UTF-8');
                    break;
                }
            }
        }
        return $text;
    }

    private static function stripCookingPrefix(string $text): string
    {
        $prefixes = self::sortByLengthDesc(self::COOKING_PREFIXES);
        foreach ($prefixes as $prefix) {
            $length = mb_strlen($prefix, 'UTF-8');
            if (mb_strpos($text, $prefix, 0, 'UTF-8') === 0) {
                $remain = mb_substr($text, $length, null, 'UTF-8');
                if (mb_strlen($remain, 'UTF-8') >= 2) {
                    return $remain;
                }
            }
        }
        return '';
    }

    /**
     * @param array<int, string> $words
     * @return array<int, string>
     */
    private static function sortByLengthDesc(array $words): array
    {
        usort($words, static function (string $a, string $b): int {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });
        return $words;
    }

    private static function clampQuery(string $query): string
    {
        $query = trim($query);
        if (!self::containsCjk($query)) {
            // 英文查询保留空格与连字符，也不按汉字长度截断，避免把 door sample 拼坏
            return $query;
        }
        $query = self::cleanText($query);
        if (mb_strlen($query, 'UTF-8') > self::MAX_QUERY_CHARS) {
            $query = mb_substr($query, -self::MAX_QUERY_CHARS, null, 'UTF-8');
        }
        return $query;
    }

    private static function containsCjk(string $text): bool
    {
        return (bool)preg_match('/[\x{4e00}-\x{9fff}]/u', $text);
    }

    private static function canCallLlm(float $deadline): bool
    {
        return $deadline <= 0 || ($deadline - microtime(true)) >= 3;
    }

    /**
     * @return array{enabled: bool, timeout: int, max_alternates: int, max_keywords: int}
     */
    private static function config(): array
    {
        $config = Config::get('project.material_keyword_plan');
        $config = is_array($config) ? $config : [];
        return [
            'enabled' => ($config['enabled'] ?? true) !== false,
            'timeout' => max(3, (int)($config['timeout'] ?? 10)),
            'max_alternates' => max(0, min(2, (int)($config['max_alternates'] ?? 1))),
            'max_keywords' => max(1, min(self::MAX_KEYWORDS, (int)($config['max_keywords'] ?? self::MAX_KEYWORDS))),
        ];
    }

    private static function preferredModel(): string
    {
        try {
            $configured = trim((string)ConfigService::get('material_keyword', 'model', ''));
        } catch (\Throwable $e) {
            $configured = '';
        }
        return $configured !== '' ? $configured : self::DEFAULT_MODEL;
    }
}
