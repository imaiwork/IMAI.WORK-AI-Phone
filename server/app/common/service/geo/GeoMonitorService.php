<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoMonitor;
use app\common\service\GeoService;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * GEO 监测侧:以真实用户身份向各 AI 引擎提问,检测品牌在回答中的可见度。
 *
 * 引擎调用统一走 GEO 中台(ToolsService::Geo()->monitorQuery):
 *  - 四引擎(deepseek/doubao/tongyi/yuanbao)的上游密钥与通道全部由中台维护,
 *    本地不再读取任何引擎 key;某引擎未在中台配置上游时中台返回"配置缺失",
 *    按未接入引擎写占位记录(不算失败);
 *  - 监测口径 env [GEO] MONITOR_MODE=web(联网检索,默认)/model(模型直答);
 *    实际生效的 model/search_mode 以中台返回为准(如 DeepSeek 未配检索代理时
 *    web 会降级为 model),每条快照如实记录 search_mode 区分口径;
 *  - 中台调用失败(截断/上游失败/超时)必须抛出而非落一条"未出现"假记录:
 *    该假记录会成为 (问题×引擎) 的最新 cell,一次上游抖动即拉低可见度。
 */
class GeoMonitorService
{
    /** 监测引擎清单(上游密钥在中台配置,接入状态由 engineList 依据中台密钥动态判定) */
    public const ENGINES = [
        'deepseek' => ['label' => 'DeepSeek'],
        'doubao'   => ['label' => '豆包'],
        'tongyi'   => ['label' => '通义千问'],
        'yuanbao'  => ['label' => '元宝'],
    ];

    /**
     * 本次请求周期的监测 token 消耗累计(中台 monitor 响应的 usage),供按实际 token 计费。
     * 计费入口在流程开始前 resetUsage(),结束后取 usage() 结算;多引擎为累计值。
     */
    protected static array $usageAcc = ['prompt' => 0, 'completion' => 0, 'total' => 0, 'model' => ''];

    /** 重置监测 token 累计(每个计费业务流程开始时调用) */
    public static function resetUsage(): void
    {
        self::$usageAcc = ['prompt' => 0, 'completion' => 0, 'total' => 0, 'model' => ''];
    }

    /** 当前监测 token 累计(供 GeoChargeService::settleByUsage 结算) */
    public static function usage(): array
    {
        return self::$usageAcc;
    }

    /** 前端可展示的引擎列表(available=中台已接入;web_available=支持联网检索口径) */
    public static function engineList(): array
    {
        $enabled = GeoService::enabled();
        $out = [];
        foreach (self::ENGINES as $key => $e) {
            $out[] = [
                'key' => $key,
                'label' => $e['label'],
                'available' => $enabled,
                'web_available' => $enabled,
            ];
        }
        return $out;
    }

    /**
     * 对某问题在指定引擎上运行监测,写入一条 GeoMonitor 记录。
     * @param array $extra 额外维度 ['keyword_id'=>int,'topic_id'=>int]
     * @return array 该条记录数组
     */
    public static function run(GeoProject $p, string $query, string $engine, array $extra = []): array
    {
        if (!GeoService::enabled()) {
            throw new \Exception('GEO 中台未接入:配置中台密钥后启用监测');
        }
        $eng = self::ENGINES[$engine] ?? null;
        if (!$eng) {
            throw new \Exception("未知监测引擎:{$engine}");
        }
        $brand = (string)$p->brand_name;
        $aliases = json_decode((string)$p->aliases, true) ?: [];
        $competitors = json_decode((string)$p->competitors, true) ?: [];

        // 监测口径:MONITOR_MODE=web(默认)联网检索 / model 模型直答;
        // max_tokens 统一 4000(与生成类一致):中台按实际 token 计费,上限只是防截断,
        // DeepSeek 直答长回答不再被 800 上限截断。
        $searchMode = (string)($extra['mode'] ?? env('geo.monitor_mode', 'web'));
        $searchMode = $searchMode === 'model' ? 'model' : 'web';

        // 取得 AI 回答:中台真实调用;引擎未在中台配置上游("配置缺失")→ 未接入占位记录。
        // 其余失败必须抛出而非落一条"未出现"假记录:该假记录会成为 (问题×引擎)
        // 的最新 cell,一次 API 抖动即拉低可见度且被伪装成"未接入"。
        $answer = '';
        $realMode = false;
        $webCitations = []; // 中台返回的结构化引用,并入引用统计
        $model = '';        // 中台实际生效的模型
        try {
            // max_tokens 对齐中台契约(接入清单 §5.1:默认 800,联网口径 1500);
            // 超出契约上限可能触发中台"请求参数格式错误"整次拒绝
            $r = ToolsService::Geo()->monitorQuery($engine, $query, $searchMode, $searchMode === 'web' ? 1500 : 800);
            $answer = (string)($r['answer'] ?? '');
            $webCitations = (array)($r['citations'] ?? []);
            // 实际生效口径以中台返回为准(DeepSeek 未配检索代理时 web 降级为 model)
            $sm = (string)($r['search_mode'] ?? '');
            if (in_array($sm, ['web', 'model'], true)) $searchMode = $sm;
            $model = (string)($r['model'] ?? '');
            // 累计本次监测的 token 消耗(供按实际 token 计费;占位/失败不累计)
            $u = (array)($r['usage'] ?? []);
            self::$usageAcc['prompt'] += (int)($u['prompt_tokens'] ?? 0);
            self::$usageAcc['completion'] += (int)($u['completion_tokens'] ?? 0);
            self::$usageAcc['total'] += (int)($u['total_tokens'] ?? 0) ?: ((int)($u['prompt_tokens'] ?? 0) + (int)($u['completion_tokens'] ?? 0));
            if ($model !== '') {
                self::$usageAcc['model'] = $model;
            }
            $realMode = true;
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), '配置缺失')) {
                // 带上 query 原文:排查"请求参数格式错误"这类问题必须知道是哪条问题触发
                Log::error("GeoMonitor engine={$engine} mode={$searchMode} query failed: " . $e->getMessage()
                    . ' | query=' . mb_substr($query, 0, 120));
                throw new \Exception("引擎「{$eng['label']}」调用失败:" . $e->getMessage());
            }
            // 引擎未在中台配置上游 → 落到下方"未接入"占位分支
        }

        if ($realMode) {
            $metrics = self::analyze($brand, $answer, $aliases, $competitors, $webCitations);
        } else {
            // 未接入引擎(中台未配该引擎上游)→ 统一"未出现"占位记录,引导用户接入,
            // 不算失败;raw_answer 带【模拟数据】前缀,不可当真实数据交付
            $metrics = self::mockMetrics($brand, "该引擎尚未在中台配置上游,无法获取「{$brand}」在其中的真实曝光,请在 GEO 中台为该引擎配置上游后启用。");
            $answer = $metrics['raw_answer'];
        }

        $m = GeoMonitor::create([
            'project_id' => $p->id,
            'engine' => $engine,
            'keyword_id' => (int)($extra['keyword_id'] ?? 0),
            'topic_id' => (int)($extra['topic_id'] ?? 0),
            'query' => $query,
            'brand_appear' => $metrics['brand_appear'],
            'brand_rank' => $metrics['brand_rank'],
            'citation' => $metrics['citation'],
            'mentions' => json_encode($metrics['mentions'] ?? [], JSON_UNESCAPED_UNICODE),
            'citations_json' => json_encode($metrics['citations'] ?? [], JSON_UNESCAPED_UNICODE),
            'sentiment' => (int)($metrics['sentiment'] ?? 0),
            'geo_score' => $metrics['geo_score'],
            'exposure_score' => $metrics['exposure_score'],
            'citation_score' => $metrics['citation_score'],
            'brand_visibility' => $metrics['brand_visibility'],
            'model' => $model, // 中台实际生效的上游模型名
            'raw_answer' => ($realMode ? '' : '【模拟数据】') . $answer,
            'search_mode' => $searchMode, // 实际生效口径:model=模型直答 / web=联网检索
            'create_time' => time(),
        ]);
        return $m->toArray();
    }

    /**
     * 从真实回答中检测品牌可见度并打分。
     * 品牌命中 = 品牌名或任一别名出现;竞争格局 = 竞品名单在回答中的出现顺序。
     * @param array $extraCitations 引擎侧结构化引用(联网口径 url_citation),优先于正文正则提取
     */
    protected static function analyze(string $brand, string $answer, array $aliases = [], array $competitors = [], array $extraCitations = []): array
    {
        // ---- 品牌命中(含别名) ----
        // 排版归一兜底:AI 回答习惯在中英文间加空格、给品牌加粗("AI 时代"/"**AI时代**"),
        // 精确子串会漏判。含中文的品牌词在"去空白/去强调符"的归一文本上再匹配一次;
        // 纯 ASCII 词不做去空格兜底,避免跨词边界误命中(如 "im ai" 被拼成 "imai")。
        // 归一文本上的位置仅用于提及排序,量级与原文单调一致,精度损失可接受。
        $normAnswer = preg_replace('/[\s*_`~]+/u', '', $answer);
        $findPos = function (string $needle) use ($answer, $normAnswer): ?int {
            $p = mb_stripos($answer, $needle);
            if ($p !== false) return $p;
            if (preg_match('/[\x{4e00}-\x{9fff}]/u', $needle)) {
                $np = mb_stripos($normAnswer, (string)preg_replace('/\s+/u', '', $needle));
                if ($np !== false) return $np;
            }
            return null;
        };
        $needles = array_values(array_filter(array_unique(array_merge([$brand], $aliases))));
        $pos = null;
        foreach ($needles as $n) {
            $p = $findPos($n);
            if ($p !== null && ($pos === null || $p < $pos)) $pos = $p;
        }
        $appear = $pos !== null ? 1 : 0;

        // ---- 品牌提及序列(本品牌 + 竞品,按首次出现位置排序) ----
        // 本品牌用"品牌名或任一别名"的最早出现位置(与 brand_appear 口径一致,
        // 否则回答只提别名时竞争格局表里自家品牌会"消失")
        $mentions = [];
        if ($pos !== null) $mentions[] = ['brand' => $brand, 'pos' => $pos];
        foreach (array_values(array_filter(array_unique($competitors))) as $c) {
            if ($c === $brand) continue;
            $cp = $findPos($c);
            if ($cp !== null) $mentions[] = ['brand' => $c, 'pos' => $cp];
        }
        usort($mentions, fn($a, $b) => $a['pos'] <=> $b['pos']);
        foreach ($mentions as $i => &$mm) { $mm['rank'] = $i + 1; unset($mm['pos']); }
        unset($mm);

        // ---- 排名:优先用提及序列中的名次,否则按位置粗估 ----
        // 精确匹配本品牌条目;双向子串匹配会在品牌与竞品互为子串时错拿竞品排名
        $rank = 0;
        if ($appear) {
            foreach ($mentions as $mm2) {
                if ($mm2['brand'] === $brand) { $rank = $mm2['rank']; break; }
            }
            if ($rank === 0) {
                $len = max(1, mb_strlen($answer));
                $ratio = $pos / $len;              // 越靠前越好
                $rank = $ratio < 0.15 ? 1 : ($ratio < 0.35 ? 2 : ($ratio < 0.6 ? 3 : 4));
            }
        }

        // ---- 结构化引用来源:引擎侧结构化引用(联网口径)优先,再补 markdown 链接 + 裸链接 ----
        $citations = [];
        foreach ($extraCitations as $ec) {
            $u = (string)($ec['url'] ?? '');
            if ($u === '' || array_search($u, array_column($citations, 'url')) !== false) continue;
            $citations[] = [
                'title' => (string)($ec['title'] ?? '') ?: (string)($ec['site'] ?? ''),
                'site' => (string)($ec['site'] ?? (parse_url($u, PHP_URL_HOST) ?: '')),
                'url' => $u,
            ];
        }
        if (preg_match_all('/\[([^\]]{2,80})\]\((https?:\/\/[^\s)]+)\)/u', $answer, $mLinks, PREG_SET_ORDER)) {
            foreach ($mLinks as $lk) {
                $host = parse_url($lk[2], PHP_URL_HOST) ?: '';
                if (array_search($lk[2], array_column($citations, 'url')) !== false) continue;
                $citations[] = ['title' => $lk[1], 'site' => $host, 'url' => $lk[2]];
            }
        }
        if (preg_match_all('/(?<!\()(https?:\/\/[^\s\]\)<>"\'，。;；]+)/u', $answer, $mUrls)) {
            foreach ($mUrls[1] as $u) {
                if (array_search($u, array_column($citations, 'url')) !== false) continue;
                $host = parse_url($u, PHP_URL_HOST) ?: '';
                if ($host === '') continue;
                $citations[] = ['title' => $host, 'site' => $host, 'url' => $u];
            }
        }
        $citations = array_slice($citations, 0, 20);

        // ---- 情绪:品牌名上下文窗口词表打分 ----
        $sentiment = 0;
        if ($appear) {
            $win = mb_substr($answer, max(0, $pos - 60), 150);
            $posWords = ['推荐', '优秀', '领先', '强大', '好用', '首选', '值得', '出色', '成熟', '高效', '优势'];
            $negWords = ['不推荐', '较差', '缺点', '劣势', '不足', '落后', '避免', '谨慎', '问题较多'];
            $score = 0;
            foreach ($posWords as $w) { if (mb_stripos($win, $w) !== false) $score++; }
            foreach ($negWords as $w) { if (mb_stripos($win, $w) !== false) $score -= 2; }
            $sentiment = $score > 0 ? 1 : ($score < 0 ? -1 : 0);
        }

        // ---- 打分(沿用原有口径) ----
        $count = 0;
        if ($appear) { foreach ($needles as $n) { $count += mb_substr_count($answer, $n); } }
        $exposure = $appear ? min(95, 45 + $count * 12 + max(0, 5 - $rank) * 5) : 0;
        // 联网口径下引用在 url_citation 注解里,正文可能不含链接,故注解非空也算引用信号
        $hasCite = $appear && (!empty($extraCitations)
            || mb_stripos($answer, 'http') !== false
            || mb_stripos($answer, '官网') !== false
            || mb_stripos($answer, '.com') !== false || mb_stripos($answer, '.cn') !== false);
        $citation = $hasCite ? min(90, 40 + $count * 15) : ($appear ? 30 : 0);
        $visibility = (int)round($appear ? ($exposure * 0.6 + $citation * 0.4) : 0);

        return [
            'brand_appear' => $appear,
            'brand_rank' => $rank,
            'citation' => $hasCite ? '回答中提及品牌并含链接/官网等引用信号' : ($appear ? '回答提及品牌但无引用链接' : ''),
            'mentions' => $mentions,
            'citations' => $citations,
            'sentiment' => $sentiment,
            'geo_score' => $visibility,
            'exposure_score' => (int)$exposure,
            'citation_score' => (int)$citation,
            'brand_visibility' => $visibility,
            'raw_answer' => $answer,
        ];
    }

    protected static function mockMetrics(string $brand, string $reason = ''): array
    {
        $reason = $reason ?: "该引擎尚未接入真实 API,无法获取「{$brand}」在其中的真实曝光。";
        // 无随机源保持可复现:未接入引擎统一给"未出现",引导用户去接入
        return [
            'brand_appear' => 0,
            'brand_rank' => 0,
            'citation' => '',
            'mentions' => [],
            'citations' => [],
            'sentiment' => 0,
            'geo_score' => 0,
            'exposure_score' => 0,
            'citation_score' => 0,
            'brand_visibility' => 0,
            'raw_answer' => "（{$reason}）",
        ];
    }
}
