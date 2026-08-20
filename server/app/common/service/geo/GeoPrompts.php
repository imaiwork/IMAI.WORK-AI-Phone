<?php

namespace app\common\service\geo;

/**
 * GEO 内置提示词库(中文,各任务类型一套,产出与数据模型匹配的 JSON)
 * 生成侧统一从这里取,便于集中调优;监测侧提示词见 GeoMonitorService。
 */
class GeoPrompts
{
    /** 通用系统人设 */
    public const SYSTEM = '你是资深 GEO(生成式引擎优化)专家。GEO 的目标是提升品牌在 AI 回答(ChatGPT/Claude/豆包/DeepSeek/通义等)中的被引用率与曝光,而非传统 SEO 网页排名。你的输出务必真实、结构化,严格按要求返回 JSON,不要加任何解释或代码围栏。';

    /** 建立品牌上下文 */
    public static function buildContext(string $ctx): string
    {
        return "根据以下品牌信息,生成一段 150 字以内的品牌上下文摘要(用于喂给后续 GEO 任务)。\n{$ctx}\n只返回 JSON:{\"summary\":\"...\"}";
    }

    /** 解析知识实体 */
    public static function parseKnowledge(string $brand, string $text): string
    {
        // 限产量:长文档不限条数时模型会吐几十条实体,输出触 max_tokens 被截断成半截
        // JSON,解析得 0 实体报"未能提取"(重试碰运气)。25 条×150 字远在输出上限内
        return "品牌:{$brand}\n从以下内容抽取知识实体,entity_type 从[品牌介绍,产品介绍,能力标签,行业标签,产品特点,用户画像,业务流程,术语]中选。\n只保留最重要的至多 25 条,每条 content 精炼在 150 字以内。\n内容:\n{$text}\n只返回 JSON:{\"entities\":[{\"entity_type\":\"...\",\"content\":\"...\"}]}";
    }

    /** 品牌分析 */
    public static function analyzeBrand(string $brand, string $know): string
    {
        return "品牌:{$brand}\n已知知识:{$know}\n分析该品牌在 AI 搜索场景下的机会:行业覆盖、竞争情况、内容缺口。\n只返回 JSON:{\"summary\":\"...\",\"opportunities\":[\"...\"]}";
    }

    /** 生成关键词/问题 */
    public static function genKeyword(string $brand, string $know): string
    {
        return "品牌:{$brand}\n已知知识:{$know}\n生成一批 GEO 关键词与问题,type 从[品牌词,行业词,产品词,长尾词,AI问题,用户问题,行业问题,竞品问题]中选,覆盖用户真实会向 AI 提的问题。\n只返回 JSON:{\"keywords\":[{\"type\":\"...\",\"value\":\"...\"}]}";
    }

    /** 生成内容 */
    public static function genContent(string $brand, string $type, string $know): string
    {
        return "品牌:{$brand}\n内容类型:{$type}\n结合以下 GEO 知识,生成一篇便于被 AI 引用的{$type}内容(结构清晰、有明确问答/要点、包含品牌名与核心能力):\n知识:{$know}\n标题不超过20个字(需适配小红书等发布平台的标题上限,精炼有力,不用副标题式长句)。\n只返回 JSON:{\"title\":\"...\",\"body\":\"markdown正文\",\"tags\":[\"...\"]}";
    }

    /**
     * 优化建议(诊断报告第 7 节)。
     *
     * 旧版只喂"最近 10 条监测的 query/是否出现/可见度",模型看不到各平台的排名分布,
     * 生成不出「DeepSeek 排名 18 相对领先,优先突破」这类有依据的分层结论,
     * 只能出泛泛的"多发内容"。这里把平台维度、话题维度、竞品、信源全喂进去,
     * 并要求按三层结构产出(对齐竞品报告口径):分平台策略 / 内容构建 / 长期机制。
     *
     * @param string $facts 由 GeoTaskService 组装的结构化事实
     */
    public static function genSuggestion(string $brand, string $facts): string
    {
        return "品牌:{$brand}\n"
            . "以下是该品牌的 AI 搜索监测事实数据:\n{$facts}\n\n"
            . "请基于上述【真实数据】给出 GEO 优化策略,严禁编造数据中不存在的平台、话题或竞品。\n"
            . "要求分三部分:\n"
            . "1) platform 分平台运营策略:把已监测平台按表现分成 标杆/潜力/薄弱 三档(可见度与竞品排名综合判断;"
            . "全部为 0 时按排名相对靠前者列为潜力),每档给出该档平台该怎么打,必须点名具体平台并引用其排名或可见度数字。\n"
            . "2) content 全域内容构建策略:固定三类——权威背书类、差异竞争类、实用场景类,"
            . "每类结合本品牌所在行业与竞品差距给出具体选题方向。\n"
            . "3) ops 长期运营与检测机制:定期数据监测、竞品对标分析、内容迭代更新三条,写清频次与观测指标。\n"
            . "4) suggestions 另给 3~5 条可直接落地的内容生产建议,每条含标题、说明、建议生成的内容类型。\n\n"
            . "只返回 JSON:{\"platform\":[{\"tier\":\"标杆|潜力|薄弱\",\"engines\":[\"平台名\"],\"action\":\"...\"}],"
            . "\"content\":[{\"type\":\"权威背书类|差异竞争类|实用场景类\",\"action\":\"...\"}],"
            . "\"ops\":[{\"title\":\"...\",\"action\":\"...\"}],"
            . "\"suggestions\":[{\"title\":\"...\",\"desc\":\"...\",\"content_type\":\"faq\"}]}";
    }

    /**
     * 匹配品牌信息(向导第一步:仅凭品牌/公司名推导 行业+别名+简介)。
     * 事实型任务:要求模型「不确定就承认」(confidence=low),避免小众品牌被编造行业
     * 污染下游话题/监测;别名参与监测命中,宁缺毋滥。
     */
    public static function matchBrandInfo(string $name): string
    {
        return "品牌/公司名称:{$name}\n"
            . "根据你所了解的该品牌公开信息,返回:\n"
            . "- industry:所属行业,简短表述(如「软件工具 / 办公工具」「餐饮连锁 / 茶饮」)\n"
            . "- aliases:该品牌的常见别名,最多 5 个(官方简称、英文名/中文名互译、旗下核心产品名、大众惯用叫法),不含品牌名本身;仅收录你确定真实存在的叫法,以及由名称字面可靠推导的简称(如去掉地域前缀和「网络科技」「有限公司」等通用后缀),严禁编造其他叫法,推导不出就给空数组\n"
            . "- intro:一句话业务介绍,50 字以内\n"
            . "- confidence:你确切了解该品牌填 high;不了解、仅凭名称字面推测填 low(此时 industry 给最可能的推测,aliases 只保留由名称字面推导的简称)\n"
            . "只返回 JSON:{\"industry\":\"...\",\"aliases\":[\"...\"],\"intro\":\"...\",\"confidence\":\"high|low\"}";
    }

    /** 推荐品牌定位话题(初始化引导用) */
    public static function recommendTopics(string $ctx, int $count = 3): string
    {
        return "根据以下品牌信息,推荐 {$count} 个该品牌应重点布局的「品牌定位关键话题」。"
            . "话题是用户会向 AI 咨询的需求领域(如「全流程私域运营」「企业AI获客系统」),8 字以内,彼此不重叠,贴合品牌核心业务。\n"
            . "{$ctx}\n只返回 JSON:{\"topics\":[\"...\"]}";
    }

    /**
     * 为某话题生成场景问题(初始化引导/话题管理用)。
     *
     * $realTerms 是各平台搜索联想词,即真人正在搜的词。给了就把它摆在最前面
     * 当作事实约束 —— 场景问题最怕的就是 AI 编出一堆没人会问的话,
     * 有真实搜索需求兜底,生成的问题才可能真的被用户问到。
     * @param array<int,array{term:string,platform:string}> $realTerms
     */
    public static function genSceneQuestions(string $brand, string $topic, string $ctx, int $count = 10, array $realTerms = []): string
    {
        $real = '';
        if ($realTerms) {
            $lines = [];
            foreach (array_slice($realTerms, 0, 30) as $r) {
                $lines[] = "- {$r['term']}({$r['platform']})";
            }
            $real = "以下是真人正在各平台搜索的相关词(按平台热度排序),请优先覆盖其中的真实需求,"
                . "把它们改写成完整的口语化提问,不要脱离这些词凭空想象:\n" . implode("\n", $lines) . "\n\n";
        }
        // used_terms 用于告诉用户「这批问题参考了哪些真实搜索词」:生成 3 条时若把
        // 全部候选词都展示出来,数量对不上会被当成 bug;必须逐字复制才能与候选词匹配
        $usedTermsRule = $realTerms
            ? ",\"used_terms\" 列出生成时实际参考过的上述搜索词(逐字复制原词,未参考的不要列,一个都没参考就给空数组)"
            : '';
        return "品牌:{$brand}\n话题:{$topic}\n补充信息:{$ctx}\n"
            . $real
            . "模拟真实用户视角,围绕该话题生成 {$count} 个用户会直接向 AI(豆包/DeepSeek/元宝等)提出的「场景问题」。"
            . "要求:口语化、有场景带入(如预算有限/新手/中小商家/电商行业),以问号结尾,不出现品牌名(问题应是中立的选型/求推荐类提问)。\n"
            . "只返回 JSON:{\"questions\":[\"...\"]" . ($realTerms ? ",\"used_terms\":[\"...\"]" : '') . "}" . $usedTermsRule;
    }

    /**
     * 文章封面图提示词(喂给系统文生图:HdLogic::txt2img)。
     * 约束的目的很具体:小红书/抖音图文封面若出现文字,平台端极易压字重叠、
     * 且 AI 生图的中文字形普遍不可用,所以明确要求【画面内不要任何文字】,
     * 文案统一由投稿时的 caption 承载。
     */
    public static function coverImage(string $brand, string $title, string $industry = '', array $tags = []): string
    {
        $kw = $tags ? ('画面元素参考:' . implode('、', array_slice($tags, 0, 5)) . '。') : '';
        $ind = $industry !== '' ? "行业:{$industry}。" : '';
        return "为一篇标题为《{$title}》的商业文章生成封面配图。{$ind}{$kw}"
            . '要求:现代简洁的商业插画风格,主体清晰、构图居中留白,'
            . '色调专业干净(蓝/白/浅灰为主),适合社交媒体信息流展示;'
            . '画面内不要出现任何文字、字母、数字、logo 或水印。';
    }

    /** 内容创作模板(聊天式内容生成 · 创作方式) */
    public const CONTENT_TEMPLATES = [
        'xuanxing' => ['label' => '选型指南', 'desc' => '从核心指标、使用需求与预算范围切入,拆解用户选型决策路径'],
        'kepu'     => ['label' => '科普类', 'desc' => '围绕行业概念、技术原理或使用知识进行通俗解读'],
        'pinxuan'  => ['label' => '品宣类', 'desc' => '以品牌为核心,展示品牌理念、产品亮点、创新能力'],
        'bangdan'  => ['label' => '推荐榜单', 'desc' => '从用户需求场景切入,结合预算、功能偏好输出推荐榜单'],
        'ceping'   => ['label' => '测评对比', 'desc' => '通过深度体验或多维参数对比,对产品功能、性能进行评测'],
    ];

    /** 聊天式内容生成(话题 + 场景问题 + 模板/风格 + 知识 + 补充诉求) */
    public static function chatGenContent(string $brand, string $topic, array $questions, string $templateLabel, string $templateDesc, string $style, string $know, string $extra): string
    {
        $qs = $questions ? ("需要覆盖的用户场景问题:\n- " . implode("\n- ", $questions) . "\n") : '';
        $tpl = $templateLabel !== ''
            ? "创作方式:{$templateLabel}({$templateDesc})\n"
            : ($style !== '' ? "创作风格要求:{$style}\n" : '');
        $ex = $extra !== '' ? "补充诉求:{$extra}\n" : '';
        return "品牌:{$brand}\n围绕话题「{$topic}」写一篇便于被 AI 搜索引用的文章。\n{$qs}{$tpl}{$ex}"
            . "品牌知识(自然融入,不要生硬堆砌):{$know}\n"
            . "要求:标题吸引且含话题关键词,不超过20个字(需适配小红书等发布平台的标题上限,精炼有力,不用副标题式长句);结构化小标题;正文自然提及品牌「{$brand}」及其优势但保持客观中立口吻;适合发布到新闻门户/自媒体;1200 字左右。\n"
            . "只返回 JSON:{\"title\":\"...\",\"body\":\"markdown正文\",\"tags\":[\"...\"]}";
    }
}
