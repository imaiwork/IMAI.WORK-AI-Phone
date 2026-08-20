<?php

namespace app\api\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoTopic;
use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoTask;
use app\common\service\geo\GeoMonitorService;

/**
 * GEO 监测洞察聚合(对标微盟星启「AI监测」五个页面 + GEO 报告)
 *
 * 统计口径(与竞品对齐):
 * - 一个「采集单元 cell」= 某场景问题 × 某AI引擎 在指定日期前的最新一条监测记录
 * - 品牌可见度 = 在线(出现)cell 数 / cell 总数
 * - 平均位置 = 出现 cell 的品牌排名均值
 * - 首推/前3/前5占比 = 出现 cell 中排名第1/≤3/≤5 的比例
 */
class GeoInsightLogic extends BaseLogic
{
    /** 解析日期(YYYY-MM-DD)为当日末秒;空=现在 */
    protected static function dayEnd(string $date): int
    {
        if ($date === '') return time();
        $ts = strtotime($date . ' 23:59:59');
        return $ts ?: time();
    }

    /**
     * 取时间戳。
     * config/database.php 开了 auto_timestamp + datetime_format,模型/toArray() 读出来的
     * create_time 已经是 'Y-m-d H:i:s' 字符串,直接 (int) 强转会得到年份(如 2026),
     * date() 出来就是 1970-01-01。这里两种形态都兼容。
     */
    protected static function ts($v): int
    {
        if (is_numeric($v)) return (int)$v;
        return (int)strtotime((string)$v);
    }

    /**
     * 采集单元:每 (keyword_id, engine) 取 $until 前最新一条监测记录。
     * @param array $f ['engine'=>string,'topic_id'=>int,'keyword_id'=>int]
     */
    protected static function cells(int $projectId, int $until, array $f = []): array
    {
        // 分母只计【启用中】的场景问题:已停用/已删除问题的历史 cell 不再计入,
        // 否则弃用问题会永久拉低可见度
        $activeIds = GeoKeyword::where('project_id', $projectId)
            ->where('type', '场景问题')->where('status', 1)->column('id');
        if (!$activeIds) return [];
        $q = GeoMonitor::where('project_id', $projectId)
            ->whereIn('keyword_id', $activeIds)
            ->where('create_time', '<=', $until)
            // 排除未接入引擎占位行,避免 max(id) 被模拟数据盖掉真实可见度
            ->where('raw_answer', 'not like', '【模拟数据】%');
        if (!empty($f['engine'])) $q->where('engine', $f['engine']);
        if (!empty($f['topic_id'])) $q->where('topic_id', (int)$f['topic_id']);
        if (!empty($f['keyword_id'])) $q->where('keyword_id', (int)$f['keyword_id']);
        $ids = $q->group('keyword_id,engine')->column('max(id)');
        if (!$ids) return [];
        $rows = GeoMonitor::whereIn('id', $ids)->select()->toArray();
        foreach ($rows as &$r) {
            $r['mentions'] = json_decode((string)($r['mentions'] ?? ''), true) ?: [];
            $r['citations_json'] = json_decode((string)($r['citations_json'] ?? ''), true) ?: [];
        }
        return $rows;
    }

    /** 本品牌指标卡(可见度/平均位置/首推/前3/前5) */
    protected static function metrics(array $cells): array
    {
        $total = count($cells);
        $appear = array_values(array_filter($cells, fn($c) => (int)$c['brand_appear'] === 1));
        $n = count($appear);
        $ranks = array_map(fn($c) => max(1, (int)$c['brand_rank']), $appear);
        $rateIn = fn($cond) => $n > 0 ? round(count(array_filter($ranks, $cond)) / $n * 100, 2) : 0.0;
        return [
            'total' => $total,
            'online' => $n,
            'visibility' => $total > 0 ? round($n / $total * 100, 2) : 0.0,
            'avg_pos' => $n > 0 ? round(array_sum($ranks) / $n, 1) : 0,
            'top1_rate' => $rateIn(fn($r) => $r === 1),
            'top3_rate' => $rateIn(fn($r) => $r <= 3),
            'top5_rate' => $rateIn(fn($r) => $r <= 5),
        ];
    }

    /** 竞争格局表:聚合 cells 内所有被提及品牌 */
    protected static function competitorTable(array $cells, string $selfBrand): array
    {
        $total = count($cells);
        $stat = [];
        foreach ($cells as $c) {
            foreach ($c['mentions'] as $m) {
                $b = (string)($m['brand'] ?? '');
                if ($b === '') continue;
                $stat[$b] = $stat[$b] ?? ['online' => 0, 'ranks' => []];
                $stat[$b]['online']++;
                $stat[$b]['ranks'][] = max(1, (int)($m['rank'] ?? 1));
            }
        }
        // 本品牌兜底(哪怕从未出现也要有一行)
        if (!isset($stat[$selfBrand])) $stat[$selfBrand] = ['online' => 0, 'ranks' => []];
        $rows = [];
        foreach ($stat as $brand => $s) {
            $n = $s['online'];
            $ranks = $s['ranks'];
            $rows[] = [
                'brand' => $brand,
                'is_self' => $brand === $selfBrand ? 1 : 0,
                'visibility' => $total > 0 ? round($n / $total * 100, 2) : 0.0,
                'online_count' => $n,
                'avg_pos' => $n > 0 ? round(array_sum($ranks) / $n, 1) : 0,
                'top1_rate' => $n > 0 ? round(count(array_filter($ranks, fn($r) => $r === 1)) / $n * 100, 1) : 0,
                'top3_rate' => $n > 0 ? round(count(array_filter($ranks, fn($r) => $r <= 3)) / $n * 100, 1) : 0,
                'top5_rate' => $n > 0 ? round(count(array_filter($ranks, fn($r) => $r <= 5)) / $n * 100, 1) : 0,
            ];
        }
        usort($rows, fn($a, $b) => $b['visibility'] <=> $a['visibility'] ?: $b['online_count'] <=> $a['online_count']);
        // 从未出现的品牌(可见度 0)不参与排名,rank=0 表示「未上榜」:
        // 否则无竞品时本品牌独占一行,0% 可见度也会显示"第1名",自相矛盾
        $rank = 0;
        foreach ($rows as &$r) { $r['rank'] = $r['online_count'] > 0 ? ++$rank : 0; }
        return $rows;
    }

    // ---------- 品牌可见度页 ----------

    public static function overview(int $projectId, array $f): array
    {
        $p = GeoProject::findOrEmpty($projectId);
        $brand = (string)$p->brand_name;
        $until = self::dayEnd((string)($f['date'] ?? ''));
        $filter = ['engine' => (string)($f['engine'] ?? ''), 'topic_id' => (int)($f['topic_id'] ?? 0)];

        $cells = self::cells($projectId, $until, $filter);
        $cards = self::metrics($cells);

        // 环比:上一期 = 24h 前的最新状态
        $prevCells = self::cells($projectId, $until - 86400, $filter);
        $prev = self::metrics($prevCells);
        $delta = [];
        foreach (['visibility', 'avg_pos', 'top1_rate', 'top3_rate', 'top5_rate'] as $k) {
            $delta[$k] = $prev['total'] > 0 ? round($cards[$k] - $prev[$k], 2) : null;
        }

        // 竞争格局:整体 + 各引擎
        $competitors = ['overall' => self::competitorTable($cells, $brand)];
        foreach (GeoMonitorService::ENGINES as $key => $e) {
            if ($filter['engine'] !== '' && $filter['engine'] !== $key) continue;
            $sub = array_values(array_filter($cells, fn($c) => $c['engine'] === $key));
            $competitors[$key] = self::competitorTable($sub, $brand);
        }

        // AI平台维度 / 话题维度(本品牌)
        $engineDim = [];
        foreach (GeoMonitorService::ENGINES as $key => $e) {
            $sub = array_values(array_filter($cells, fn($c) => $c['engine'] === $key));
            if (!$sub) continue;
            $engineDim[] = ['engine' => $key, 'label' => $e['label']] + self::metrics($sub);
        }
        $topicDim = [];
        $topicNames = GeoTopic::where('project_id', $projectId)->column('name', 'id');
        foreach ($topicNames as $tid => $tname) {
            $sub = array_values(array_filter($cells, fn($c) => (int)$c['topic_id'] === (int)$tid));
            if (!$sub) continue;
            $topicDim[] = ['topic_id' => $tid, 'topic_name' => $tname] + self::metrics($sub);
        }

        // 各引擎实际使用的大模型(取该引擎最新一条监测记录的 model):
        // 模型计费口径下,竞争格局各 tab 的统计与计费主体都要能对应到具体模型
        $latestByEngine = [];
        foreach ($cells as $c) {
            $e = (string)$c['engine'];
            if (!isset($latestByEngine[$e]) || (int)$c['id'] > (int)$latestByEngine[$e]['id']) {
                $latestByEngine[$e] = $c;
            }
        }
        $engineModels = [];
        foreach ($latestByEngine as $e => $row) {
            $m = trim((string)($row['model'] ?? ''));
            if ($m !== '') $engineModels[$e] = $m;
        }

        return [
            'cards' => $cards, 'delta' => $delta,
            'competitors' => $competitors,
            'engine_models' => $engineModels,
            'engine_dim' => $engineDim, 'topic_dim' => $topicDim,
        ];
    }

    // ---------- 场景问题分析页 ----------

    public static function sceneAnalysis(int $projectId, array $f): array
    {
        $until = self::dayEnd((string)($f['date'] ?? ''));
        $cells = self::cells($projectId, $until, [
            'engine' => (string)($f['engine'] ?? ''),
            'topic_id' => (int)($f['topic_id'] ?? 0),
        ]);
        // 在线状态筛选
        if (isset($f['online']) && $f['online'] !== '') {
            $want = (int)$f['online'];
            $cells = array_values(array_filter($cells, fn($c) => (int)$c['brand_appear'] === $want));
        }
        $questions = GeoKeyword::where('project_id', $projectId)->column('value', 'id');
        $topicNames = GeoTopic::where('project_id', $projectId)->column('name', 'id');
        $out = [];
        foreach ($cells as $c) {
            $out[] = [
                'monitor_id' => (int)$c['id'],
                'keyword_id' => (int)$c['keyword_id'],
                'time' => date('Y-m-d', self::ts($c['create_time'])),
                'question' => $questions[$c['keyword_id']] ?? $c['query'],
                'topic_name' => $topicNames[$c['topic_id']] ?? '',
                'engine' => $c['engine'],
                'engine_label' => GeoMonitorService::ENGINES[$c['engine']]['label'] ?? $c['engine'],
                'online' => (int)$c['brand_appear'],
                'brand_rank' => (int)$c['brand_rank'],
            ];
        }
        usort($out, fn($a, $b) => $b['monitor_id'] <=> $a['monitor_id']);
        return $out;
    }

    // ---------- 问答快照页 ----------

    public static function snapshots(int $projectId, array $f): array
    {
        $until = self::dayEnd((string)($f['date'] ?? ''));
        $cells = self::cells($projectId, $until, [
            'engine' => (string)($f['engine'] ?? ''),
            'topic_id' => (int)($f['topic_id'] ?? 0),
            'keyword_id' => (int)($f['keyword_id'] ?? 0),
        ]);
        $questions = GeoKeyword::where('project_id', $projectId)->column('value', 'id');
        $topicNames = GeoTopic::where('project_id', $projectId)->column('name', 'id');
        $out = [];
        foreach ($cells as $c) {
            $out[] = [
                'id' => (int)$c['id'],
                'keyword_id' => (int)$c['keyword_id'],
                'time' => date('Y/m/d', self::ts($c['create_time'])),
                'question' => $questions[$c['keyword_id']] ?? $c['query'],
                'topic_name' => $topicNames[$c['topic_id']] ?? '',
                'engine' => $c['engine'],
                'engine_label' => GeoMonitorService::ENGINES[$c['engine']]['label'] ?? $c['engine'],
                'online' => (int)$c['brand_appear'],
                'brand_rank' => (int)$c['brand_rank'],
                'raw_answer' => (string)$c['raw_answer'],
                'citations' => $c['citations_json'],
                'search_mode' => (string)($c['search_mode'] ?? 'model'), // web=联网检索口径(二期)
            ];
        }
        usort($out, fn($a, $b) => $b['id'] <=> $a['id']);
        return $out;
    }

    /** 单个场景问题在某引擎的在线趋势(历史监测点) */
    public static function onlineTrend(int $projectId, int $keywordId, string $engine = ''): array
    {
        $q = GeoMonitor::where('project_id', $projectId)->where('keyword_id', $keywordId);
        if ($engine !== '') $q->where('engine', $engine);
        // 取最近 60 条(desc 取新)再按时间正序还原,保证趋势图始终展示最新数据
        $rows = $q->order('id desc')->limit(60)
            ->field('id,engine,brand_appear,brand_rank,brand_visibility,create_time')->select()->toArray();
        $rows = array_reverse($rows);
        foreach ($rows as &$r) { $r['date'] = date('m-d H:i', self::ts($r['create_time'])); }
        return $rows;
    }

    /**
     * 品牌可见度按天趋势(二期,配合每日自动监测的趋势曲线)。
     * 口径:每天新增的全部监测记录,按天算 在线率 与 平均可见度分;
     * 当天没有采集则该天为 null(前端断点显示),不做插值。
     */
    public static function visibilityTrend(int $projectId, int $days = 30): array
    {
        $days = min(90, max(7, $days));
        $start = strtotime(date('Y-m-d')) - ($days - 1) * 86400;
        $rows = GeoMonitor::where('project_id', $projectId)
            ->where('create_time', '>=', $start)
            ->field('brand_appear,brand_visibility,create_time')->select()->toArray();
        $byDay = [];
        foreach ($rows as $r) {
            $d = date('m-d', self::ts($r['create_time']));
            $byDay[$d]['total'] = ($byDay[$d]['total'] ?? 0) + 1;
            $byDay[$d]['online'] = ($byDay[$d]['online'] ?? 0) + ((int)$r['brand_appear'] ? 1 : 0);
            $byDay[$d]['vis_sum'] = ($byDay[$d]['vis_sum'] ?? 0) + (float)$r['brand_visibility'];
        }
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = date('m-d', $start + $i * 86400);
            $s = $byDay[$d] ?? null;
            $out[] = [
                'date' => $d,
                'total' => $s['total'] ?? 0,
                'online' => $s['online'] ?? 0,
                'online_rate' => $s ? (int)round($s['online'] / max(1, $s['total']) * 100) : null,
                'visibility' => $s ? (int)round($s['vis_sum'] / max(1, $s['total'])) : null,
            ];
        }
        return $out;
    }

    // ---------- 引用分析页 ----------

    public static function quotes(int $projectId, array $f): array
    {
        $from = !empty($f['date_from']) ? (strtotime($f['date_from'] . ' 00:00:00') ?: 0) : 0;
        $to = self::dayEnd((string)($f['date_to'] ?? ''));
        $q = GeoMonitor::where('project_id', $projectId)
            ->where('create_time', '>=', $from)->where('create_time', '<=', $to);
        if (!empty($f['engine'])) $q->where('engine', $f['engine']);
        if (!empty($f['topic_id'])) $q->where('topic_id', (int)$f['topic_id']);
        $rows = $q->field('id,citations_json')->select()->toArray();

        $sites = [];   // site => [cite_count, urls]
        $articles = []; // url => [title, site, count]
        foreach ($rows as $r) {
            $cs = json_decode((string)($r['citations_json'] ?? ''), true) ?: [];
            foreach ($cs as $c) {
                $site = (string)($c['site'] ?? '');
                $url = (string)($c['url'] ?? '');
                if ($site === '' && $url === '') continue;
                $site = $site ?: parse_url($url, PHP_URL_HOST) ?: '未知来源';
                $sites[$site] = $sites[$site] ?? ['cite_count' => 0, 'urls' => []];
                $sites[$site]['cite_count']++;
                if ($url !== '') $sites[$site]['urls'][$url] = 1;
                if ($url !== '') {
                    $articles[$url] = $articles[$url] ?? ['title' => (string)($c['title'] ?? $url), 'site' => $site, 'cite_count' => 0];
                    $articles[$url]['cite_count']++;
                }
            }
        }
        $topSites = [];
        foreach ($sites as $site => $s) {
            $topSites[] = ['site' => $site, 'cite_count' => $s['cite_count'], 'article_count' => count($s['urls'])];
        }
        usort($topSites, fn($a, $b) => $b['cite_count'] <=> $a['cite_count']);
        $topArticles = [];
        foreach ($articles as $url => $a) {
            $topArticles[] = ['url' => $url] + $a;
        }
        usort($topArticles, fn($a, $b) => $b['cite_count'] <=> $a['cite_count']);
        return [
            'top_sites' => array_slice($topSites, 0, 15),
            'top_articles' => array_slice($topArticles, 0, 50),
        ];
    }

    // ---------- 舆情指数页 ----------

    public static function sentimentStats(int $projectId, array $f): array
    {
        $until = self::dayEnd((string)($f['date'] ?? ''));
        $from = $until - 30 * 86400;
        $q = GeoMonitor::where('project_id', $projectId)
            ->where('create_time', '>', $from)->where('create_time', '<=', $until)
            ->where('brand_appear', 1);
        if (!empty($f['engine'])) $q->where('engine', $f['engine']);
        if (!empty($f['topic_id'])) $q->where('topic_id', (int)$f['topic_id']);
        $rows = $q->field('id,sentiment,create_time')->select()->toArray();

        $pos = $neu = $neg = 0;
        $daily = [];
        foreach ($rows as $r) {
            $s = (int)$r['sentiment'];
            if ($s > 0) $pos++; elseif ($s < 0) $neg++; else $neu++;
            $d = date('m-d', self::ts($r['create_time']));
            $daily[$d] = $daily[$d] ?? ['date' => $d, 'pos' => 0, 'neu' => 0, 'neg' => 0];
            $daily[$d][$s > 0 ? 'pos' : ($s < 0 ? 'neg' : 'neu')]++;
        }
        $total = $pos + $neu + $neg;
        $pct = fn($x) => $total > 0 ? round($x / $total * 100, 1) : 0;
        return [
            'total' => $total,
            'positive' => ['count' => $pos, 'rate' => $pct($pos)],
            'neutral' => ['count' => $neu, 'rate' => $pct($neu)],
            'negative' => ['count' => $neg, 'rate' => $pct($neg)],
            'trend' => array_values($daily),
        ];
    }

    // ---------- GEO 报告 ----------

    public static function report(int $projectId): array
    {
        $p = GeoProject::findOrEmpty($projectId);
        $brand = (string)$p->brand_name;
        $until = time();
        $cells = self::cells($projectId, $until, []);
        $cards = self::metrics($cells);
        $competitors = self::competitorTable($cells, $brand);
        $selfOverall = current(array_filter($competitors, fn($r) => $r['is_self']));
        $selfRank = $selfOverall ? (int)$selfOverall['rank'] : count($competitors);

        // 各AI平台概览(本品牌整体在各引擎的可见度与竞品排名)
        $engineDim = [];
        foreach (GeoMonitorService::ENGINES as $key => $e) {
            $sub = array_values(array_filter($cells, fn($c) => $c['engine'] === $key));
            if (!$sub) continue;
            $tbl = self::competitorTable($sub, $brand);
            $selfRow = current(array_filter($tbl, fn($r) => $r['is_self']));
            $engineDim[] = ['engine' => $key, 'label' => $e['label'],
                'visibility' => $selfRow ? $selfRow['visibility'] : 0,
                'rank' => $selfRow ? $selfRow['rank'] : 0,
            ] + self::metrics($sub);
        }

        // 分话题(整体 + 各引擎的本品牌排名 + 该话题头部品牌)
        $topicNames = GeoTopic::where('project_id', $projectId)->column('name', 'id');
        $topics = [];
        $topicTables = []; // tid => 该话题竞争表(竞争矩阵复用)
        foreach ($topicNames as $tid => $tname) {
            $sub = array_values(array_filter($cells, fn($c) => (int)$c['topic_id'] === (int)$tid));
            $byEngine = [];
            foreach (GeoMonitorService::ENGINES as $key => $e) {
                $esub = array_values(array_filter($sub, fn($c) => $c['engine'] === $key));
                if (!$esub) continue;
                $tbl = self::competitorTable($esub, $brand);
                $selfRow = current(array_filter($tbl, fn($r) => $r['is_self']));
                $byEngine[] = ['engine' => $key, 'label' => $e['label'],
                    'visibility' => $selfRow ? $selfRow['visibility'] : 0,
                    'rank' => $selfRow ? $selfRow['rank'] : 0];
            }
            $tbl = self::competitorTable($sub, $brand);
            $topicTables[$tid] = $tbl;
            $selfRow = current(array_filter($tbl, fn($r) => $r['is_self']));
            $topics[] = [
                'topic_id' => $tid, 'topic_name' => $tname,
                'visibility' => $selfRow ? $selfRow['visibility'] : 0,
                'rank' => $selfRow ? $selfRow['rank'] : 0,
                'competitor_total' => count($tbl),
                'by_engine' => $byEngine,
                // 该话题头部品牌(分话题竞争分析用)
                'top_brands' => array_map(fn($r) => ['brand' => $r['brand'], 'visibility' => $r['visibility'], 'rank' => $r['rank']],
                    array_slice(array_values(array_filter($tbl, fn($r) => !$r['is_self'])), 0, 4)),
            ] + self::metrics($sub);
        }

        // 竞争矩阵:整体 TOP9 品牌 + 本品牌 × 各话题(可见度/排名)
        $matrixRows = array_slice($competitors, 0, 9);
        if (!array_filter($matrixRows, fn($r) => $r['is_self']) && $selfOverall) $matrixRows[] = $selfOverall;
        $matrix = [];
        foreach ($matrixRows as $r) {
            $byTopic = [];
            foreach ($topicNames as $tid => $tname) {
                $row = current(array_filter($topicTables[$tid] ?? [], fn($x) => $x['brand'] === $r['brand']));
                $byTopic[] = ['topic_id' => $tid, 'topic_name' => $tname,
                    'visibility' => $row ? $row['visibility'] : 0,
                    'rank' => $row ? $row['rank'] : 0];
            }
            $matrix[] = ['brand' => $r['brand'], 'is_self' => $r['is_self'],
                'visibility' => $r['visibility'], 'rank' => $r['rank'], 'by_topic' => $byTopic];
        }

        // 最佳话题
        $sorted = $topics;
        usort($sorted, fn($a, $b) => $b['visibility'] <=> $a['visibility'] ?: $a['rank'] <=> $b['rank']);
        $bestTopic = $sorted[0] ?? null;

        // 竞品数 / 信源数
        $brands = [];
        $sources = [];
        foreach ($cells as $c) {
            foreach ($c['mentions'] as $m) { $brands[(string)($m['brand'] ?? '')] = 1; }
            foreach ($c['citations_json'] as $cit) { if (!empty($cit['site'])) $sources[$cit['site']] = 1; }
        }

        // 结论性文案(诊断概览三段,对齐微盟报告口径)
        $overview = self::overviewTexts($brand, $cards, $selfRank, count($competitors), $engineDim, $topics);

        // 最近一次优化建议
        $sugTask = GeoTask::where('project_id', $projectId)->where('task_type', 'gen_suggestion')
            ->where('status', 'success')->order('id desc')->findOrEmpty();
        $sugRaw = $sugTask->isEmpty() ? [] : (json_decode((string)$sugTask->result_ref, true) ?: []);
        // 新版存 {suggestions, strategy};历史任务存的是纯建议数组,两种都要能读
        $suggestions = $sugRaw['suggestions'] ?? (isset($sugRaw[0]) ? $sugRaw : []);
        $strategy = $sugRaw['strategy'] ?? null;

        // 引擎口径必须与 cell 分母一致:只算真正跑出数据的引擎。
        // 无数据时退回"已接入"引擎——直接用 ENGINES 常量会把未接入的引擎(如没配 key 的
        // 豆包)也算进 engine_count,导致报告标称 4 个引擎、分母却只有 3 个引擎的量。
        $usedEngines = array_values(array_unique(array_column($cells, 'engine')));
        if (!$usedEngines) {
            $usedEngines = array_column(array_filter(
                GeoMonitorService::engineList(), fn($e) => !empty($e['available'])
            ), 'key');
        }
        $engineLabels = array_values(array_map(
            fn($k) => GeoMonitorService::ENGINES[$k]['label'] ?? $k, $usedEngines
        ));

        return [
            'brand' => $brand,
            'date' => date('Y-m-d'),
            'meta' => [
                'cell_total' => count($cells),
                'question_count' => GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->count(),
                'engine_count' => count($usedEngines),
                'engine_labels' => $engineLabels,
                // 直接数竞品表里的非本品牌行，与第 4 节竞争分析同源。
                // 旧写法 count($brands)-1 默认"本品牌一定在 mentions 里"，
                // 可见度为 0（一次都没被提及）时会把真实竞品数少算 1，
                // 出现"竞品品牌数量 0"却在竞争分析里列着竞品的自相矛盾。
                'competitor_count' => count(array_filter($competitors, fn($r) => empty($r['is_self']))),
                'source_count' => count($sources),
                'topic_names' => array_values($topicNames),
            ],
            'cards' => $cards,
            'self_rank' => $selfRank,
            'competitor_total' => count($competitors),
            'engine_dim' => $engineDim,
            'overview' => $overview,
            'best_topic' => $bestTopic ? ['topic_name' => $bestTopic['topic_name'], 'visibility' => $bestTopic['visibility'], 'rank' => $bestTopic['rank']] : null,
            'topics' => $topics,
            'competitors' => array_slice($competitors, 0, 30),
            'matrix' => $matrix,
            'quotes' => self::quotes($projectId, []),
            'sentiment' => self::sentimentStats($projectId, []),
            'suggestions' => $suggestions,
            // 三层策略:分平台运营 / 全域内容构建 / 长期运营机制。
            // 历史报告没有这一段,前端需按 null 兜底回落到平铺建议列表。
            'strategy' => $strategy,
        ];
    }

    /** 诊断概览三段结论文案(整体定位/平台分化/场景覆盖),纯模板拼装不耗AI */
    protected static function overviewTexts(string $brand, array $cards, int $selfRank, int $total, array $engineDim, array $topics): array
    {
        $vis = (float)$cards['visibility'];
        // 零数据 / 无竞品时不得给出"行业头部"这类结论:$total 含品牌自身,
        // 竞品为空时 $total=1、$selfRank=1,旧逻辑会输出"在1个竞品中排名第1位,处于行业
        // 头部位置,已建立较强的AI认知基础"——可见度 0% 也照说,严重误导。
        if ((int)$cards['total'] === 0) {
            $position = '尚无有效监测数据(统计口径仅含已绑定「场景问题」的监测记录),'
                . '暂无法评估品牌在AI平台的定位,请先为场景问题完成一轮监测。';
        } elseif ($vis <= 0) {
            // 可见度为 0 时，名次毫无意义：所有品牌都是 0，排名只是并列里的先后。
            // 旧逻辑会走到下面按 selfRank<=3 判「处于行业头部位置,已建立较强的AI认知基础」，
            // 而实际是"一次都没被 AI 提到过"——这是最严重的一种误导。
            $position = "当前品牌在AI平台的整体可见度为0%,在本轮 {$cards['total']} 个采集单元中"
                . '一次都未被AI提及,尚未建立AI认知基础。建议先补齐品牌词条与FAQ内容,再复测。';
        } elseif ($total <= 1) {
            $position = "当前品牌在AI平台的整体可见度为{$vis}%。本次监测未识别到行业竞品,"
                . '暂无法给出相对排名,建议在项目设置中补充竞品或扩大监测问题覆盖。';
        } else {
            $pos = $selfRank <= 3 ? '处于行业头部位置,已建立较强的AI认知基础'
                : ($selfRank <= 10 ? '处于行业中上游,与头部品牌仍有差距'
                : ($selfRank <= ceil($total / 2) ? '处于行业中游位置,竞争优势尚不明显'
                : '处于行业尾部位置,与其他竞品相比竞争优势缺失,AI领域的基础存在感亟待建立'));
            // $total 含品牌自身,对外表述要减掉,否则会出现"识别竞品 0 个"却说"在2个竞品中"
            $rivals = max(0, $total - 1);
            $position = "当前品牌在AI平台的整体可见度为{$vis}%,在 {$rivals} 个同类竞品中排名第{$selfRank}位,{$pos}。";
        }

        if ($engineDim) {
            $byVis = $engineDim;
            usort($byVis, fn($a, $b) => $b['visibility'] <=> $a['visibility'] ?: $a['rank'] <=> $b['rank']);
            $best = $byVis[0]; $worst = end($byVis);
            $allZero = !array_filter($engineDim, fn($e) => (float)$e['visibility'] > 0);
            $engines = $allZero
                ? '品牌在' . implode('、', array_column($engineDim, 'label')) . '等所有已监测AI平台的可见度均为0.00%,无突出表现的平台,各平台均存在严重的可见度短板,整体在AI平台端的布局几乎空白。'
                : "从各AI平台表现来看,品牌在{$best['label']}表现最佳(可见度{$best['visibility']}%、竞品排名第{$best['rank']}名),在{$worst['label']}相对薄弱("
                    . ((float)$worst['visibility'] > 0 ? "可见度{$worst['visibility']}%、排名第{$worst['rank']}名" : '可见度0%、未被AI提及')
                    . "),各平台表现分化,需针对薄弱平台补齐内容布局。";
        } else {
            $engines = '尚未产生监测数据,暂无法评估各AI平台表现,请先完成一轮AI搜索监测。';
        }

        if ($topics) {
            $names = implode('、', array_column($topics, 'topic_name'));
            $covered = array_filter($topics, fn($t) => (float)$t['visibility'] > 0);
            $scene = $covered
                ? "品牌在{$names}等话题中已有一定信息覆盖,其中可见度大于0的话题" . count($covered) . '个,建议持续扩大优势话题覆盖并补齐薄弱话题。'
                : "品牌在{$names}等话题的总可见度均为0.00%,所有涉及的场景话题可见度都极低,场景覆盖度严重不足,尚未在任何目标场景中建立信息覆盖基础。";
        } else {
            $scene = '尚未配置监测话题,请先在「设置-话题」中添加话题与场景问题。';
        }

        return ['position' => $position, 'engines' => $engines, 'scene' => $scene];
    }

    /** 最新一份已生成的报告(查看报告用,不计费) */
    public static function reportLatest(int $projectId): array
    {
        $row = \app\common\model\geo\GeoReport::where('project_id', $projectId)->order('id desc')->findOrEmpty();
        if ($row->isEmpty()) return ['exists' => false];
        return [
            'exists' => true,
            'id' => (int)$row->id,
            'generated_at' => date('Y-m-d H:i', self::ts($row->create_time)),
            'report' => json_decode((string)$row->report, true) ?: [],
        ];
    }

    /**
     * 生成(重新生成)报告:计费 geo_report,内嵌的AI优化建议一并现场刷新
     * (费用含在报告价内,不再单独收 geo_suggestion)。成功后落库,查看不再收费。
     */
    public static function reportGenerate(int $userId, int $projectId): array
    {
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_report');
        // 刷新AI优化建议。via=report 让建议走 /api/geo/report 端点:整份报告
        // (含内嵌建议)只产生一次 report 调用,费用含在报告价内,不再单独收 geo_suggestion。
        // 失败不再静默降级:计费按 usage 结算,降级会产出免费报告且用户毫无感知
        // (「成功却没扣费」),统一口径为 成功=扣费 / 失败=报错不扣费
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = \app\common\service\geo\GeoTaskService::dispatch($projectId, 'gen_suggestion', ['via' => 'report']);
        if (($task['status'] ?? '') !== 'success') {
            throw new \Exception(\app\common\service\geo\GeoHelper::taskErrorMessage($task, '报告内AI优化建议生成失败') . ',本次未扣费,请稍后重试');
        }
        $data = self::report($projectId);
        $cellTotal = (int)($data['meta']['cell_total'] ?? ($data['cards']['total'] ?? 0));
        if ($cellTotal <= 0) {
            throw new \Exception('暂无有效监测数据,请先完成一键诊断后再生成报告');
        }
        $row = \app\common\model\geo\GeoReport::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'report' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ]);
        try {
            \app\common\service\geo\GeoChargeService::settleByUsage($userId, 'geo_report', (array)($task['usage_tokens'] ?? []), (string)$row->id);
        } catch (\Throwable $e) {
            // 结算失败不留免费报告
            $row->delete();
            throw $e;
        }
        return [
            'exists' => true,
            'id' => (int)$row->id,
            'generated_at' => date('Y-m-d H:i', self::ts($row->create_time)),
            'report' => $data,
        ];
    }
}
