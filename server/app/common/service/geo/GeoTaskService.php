<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoKnowledge;
use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoTask;
use app\common\service\geo\GeoPrompts;
use app\common\service\geo\GeoMonitorService;

/**
 * GEO 异步任务执行器
 * MVP:创建即同步执行(AI mock 很快);每步写入人类可读日志;可查看。
 * 生产异步化:把 run() 丢进 think-queue 即可,接口/日志不变。
 */
class GeoTaskService
{
    /** 创建任务并立即执行(返回 task 数组) */
    public static function dispatch(int $projectId, string $taskType, array $input = []): array
    {
        $task = GeoTask::create([
            'project_id' => $projectId,
            'task_type' => $taskType,
            'status' => 'pending',
            'input' => json_encode($input, JSON_UNESCAPED_UNICODE),
            'logs' => json_encode([], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ]);
        self::run((int)$task->id);
        $row = GeoTask::findOrEmpty($task->id)->toArray();
        // 本次任务的中台 token 消耗快照(供按实际 token 计费;链式调用为累计值)
        $row['usage_tokens'] = GeoAiService::usage();
        return $row;
    }

    /** 执行任务 */
    public static function run(int $taskId): void
    {
        $task = GeoTask::findOrEmpty($taskId);
        if ($task->isEmpty()) {
            return;
        }
        // 防重入:已成功的任务不重跑(避免重复调 AI、重复写 content/keyword);
        // 10 分钟内仍在 running 的视为执行中不抢跑(进程被杀的尸留任务超时后允许重试)
        if ($task->status === 'success') {
            return;
        }
        if ($task->status === 'running' && GeoHelper::ts($task->update_time) > time() - 600) {
            return;
        }
        $task->status = 'running';
        $task->update_time = time();
        $task->save();
        $input = json_decode((string)$task->input, true) ?: [];
        try {
            $method = 'handle' . str_replace('_', '', ucwords((string)$task->task_type, '_'));
            if (!method_exists(self::class, $method)) {
                throw new \Exception('未知任务类型: ' . $task->task_type);
            }
            $ref = self::$method($task, $input);
            $task->status = 'success';
            $task->result_ref = (string)$ref;
        } catch (\Throwable $e) {
            self::log($task, '错误', $e->getMessage());
            $task->status = 'failed';
        }
        $task->update_time = time();
        $task->save();
    }

    /** 追加分步日志 */
    protected static function log(GeoTask $task, string $step, string $message): void
    {
        $logs = json_decode((string)$task->logs, true) ?: [];
        $logs[] = ['ts' => date('Y-m-d H:i:s'), 'step' => $step, 'message' => $message];
        $task->logs = json_encode($logs, JSON_UNESCAPED_UNICODE);
        $task->save();
    }

    protected static function project(int $id): GeoProject
    {
        return GeoProject::findOrEmpty($id);
    }

    /** 品牌上下文字符串(供各任务喂给 AI) */
    protected static function brandContext(GeoProject $p): string
    {
        return "品牌:{$p->brand_name} 行业:{$p->industry} 官网:{$p->website}\n"
            . "产品介绍:{$p->intro}\n产品特点:{$p->features}\n目标客户:{$p->target_customer}";
    }

    // ---------- 处理器 ----------

    protected static function handleBuildContext(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        self::log($task, '读取项目', "加载品牌「{$p->brand_name}」基础信息");
        $ctx = self::brandContext($p);
        self::log($task, '生成上下文', '调用 AI 生成品牌上下文摘要');
        $res = GeoAiService::call(GeoPrompts::SYSTEM, GeoPrompts::buildContext($ctx), true, 'build_context');
        $summary = $res['summary'] ?? '';
        GeoKnowledge::create([
            'project_id' => $p->id, 'source' => '项目信息', 'source_type' => 'kb',
            'entity_type' => '品牌介绍', 'content' => $summary, 'create_time' => time(),
        ]);
        self::log($task, '写入知识库', '已保存品牌介绍知识实体');
        return 'knowledge';
    }

    protected static function handleParseKnowledge(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        $source = (string)($input['source'] ?? '导入内容');
        $text = (string)($input['text'] ?? '');
        if (mb_strlen($text) > 50000) {
            $text = mb_substr($text, 0, 50000);
            self::log($task, '截断提示', '文档过长,仅取前 5 万字符参与实体抽取');
        }
        self::log($task, '读取来源', "来源:{$source},文本长度 " . mb_strlen($text));
        self::log($task, '抽取实体', '调用 AI 从内容中抽取知识实体');
        $res = GeoAiService::call(GeoPrompts::SYSTEM, GeoPrompts::parseKnowledge((string)$p->brand_name, $text), true, 'parse_knowledge', (string)$p->gen_model);
        $entities = $res['entities'] ?? [];
        // 0 实体按失败处理(不扣费):正常文本几乎必有产出,空结果基本是导入了
        // 二进制/乱码等不可读内容,报成功会让用户看到"已解析入库"却一条数据没有
        if (!$entities) {
            throw new \Exception('未能从内容中提取到知识实体,请确认导入的是可读的文本内容');
        }
        foreach ($entities as $e) {
            GeoKnowledge::create([
                'project_id' => $p->id, 'source' => $source,
                'source_type' => (string)($input['source_type'] ?? 'txt'),
                'entity_type' => (string)($e['entity_type'] ?? '术语'),
                'content' => (string)($e['content'] ?? ''), 'create_time' => time(),
            ]);
        }
        self::log($task, '完成', '已从内容提取 ' . count($entities) . ' 个知识实体');
        return 'knowledge:' . count($entities);
    }

    protected static function handleAnalyzeBrand(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        self::log($task, '汇总知识', '读取项目知识库用于分析');
        $know = GeoAiService::knowledgeDigest((int)$p->id);
        self::log($task, '品牌分析', '调用 AI 分析行业覆盖/竞争/AI搜索机会');
        $res = GeoAiService::call(GeoPrompts::SYSTEM, GeoPrompts::analyzeBrand((string)$p->brand_name, $know), true, 'analyze_brand', (string)$p->gen_model);
        GeoKnowledge::create([
            'project_id' => $p->id, 'source' => '品牌分析', 'source_type' => 'kb',
            'entity_type' => '行业标签', 'content' => (string)($res['summary'] ?? ''), 'create_time' => time(),
        ]);
        self::log($task, '链式触发', '分析完成,自动触发关键词生成');
        // 链式:生成关键词
        $sub = self::dispatch($p->id, 'gen_keyword', []);
        return 'task:' . ($sub['id'] ?? 0);
    }

    protected static function handleGenKeyword(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        self::log($task, '读取知识', '基于品牌知识生成关键词/问题');
        $know = GeoAiService::knowledgeDigest((int)$p->id);
        $res = GeoAiService::call(GeoPrompts::SYSTEM, GeoPrompts::genKeyword((string)$p->brand_name, $know), true, 'gen_keyword');
        $kws = $res['keywords'] ?? [];
        foreach ($kws as $k) {
            GeoKeyword::create([
                'project_id' => $p->id, 'type' => (string)($k['type'] ?? '长尾词'),
                'value' => (string)($k['value'] ?? ''), 'create_time' => time(),
            ]);
        }
        self::log($task, '完成', '已生成 ' . count($kws) . ' 个关键词/问题');
        return 'keyword:' . count($kws);
    }

    protected static function handleGenContent(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        $type = (string)($input['content_type'] ?? 'faq');
        $know = GeoAiService::knowledgeDigest((int)$p->id);

        // 聊天式生成(话题 + 场景问题 + 模板/风格 + 补充诉求)优先
        $topicId = (int)($input['topic_id'] ?? 0);
        $topicName = (string)($input['topic_name'] ?? '');
        if ($topicName !== '' || $topicId > 0) {
            if ($topicName === '' && $topicId > 0) {
                // 限定本项目,防止传他人 topic_id 把别家话题名带进提示词/日志
                $topicName = (string)\app\common\model\geo\GeoTopic::where('id', $topicId)
                    ->where('project_id', $p->id)->value('name');
            }
            $questions = array_values(array_filter((array)($input['questions'] ?? [])));
            $tplKey = (string)($input['template'] ?? '');
            $tpl = GeoPrompts::CONTENT_TEMPLATES[$tplKey] ?? null;
            $style = (string)($input['style'] ?? '');
            $extra = (string)($input['extra'] ?? '');
            $useKb = (int)($input['use_kb'] ?? 1);
            self::log($task, '准备生成', "话题:{$topicName}" . ($tpl ? ",模板:{$tpl['label']}" : '') . ',场景问题 ' . count($questions) . ' 个');
            $res = GeoAiService::call(
                GeoPrompts::SYSTEM,
                GeoPrompts::chatGenContent(
                    (string)$p->brand_name, $topicName, $questions,
                    $tpl['label'] ?? '', $tpl['desc'] ?? '', $style,
                    $useKb ? $know : '',
                    $extra
                ),
                true, 'gen_content', (string)$p->gen_model
            );
            $type = $tpl['label'] ?? ($type ?: '文章');
        } else {
            self::log($task, '准备生成', "内容类型:{$type},结合 GEO 知识");
            $res = GeoAiService::call(GeoPrompts::SYSTEM, GeoPrompts::genContent((string)$p->brand_name, $type, $know), true, 'gen_content', (string)$p->gen_model);
        }

        // 产出为空必须判失败:模型返回的 JSON 解析不出来时($res 为空数组),
        // 旧逻辑会写一条 body='' 的空文章、任务标 success,调用方照常扣费,
        // 用户拿到一篇空文章还被扣了算力。任务失败不扣费,交给用户重试。
        if (trim((string)($res['body'] ?? '')) === '') {
            throw new \Exception('AI 未返回可用正文,请重试');
        }

        $kwIds = GeoHelper::normalizeIntList($input['keyword_ids'] ?? []);
        if (!$kwIds && (int)($input['keyword_id'] ?? 0) > 0) {
            $kwIds = [(int)$input['keyword_id']];
        }
        $c = GeoContent::create([
            'project_id' => $p->id, 'content_type' => $type,
            'topic_id' => $topicId,
            'keyword_id' => (int)($input['keyword_id'] ?? ($kwIds[0] ?? 0)),
            'keyword_ids' => json_encode($kwIds, JSON_UNESCAPED_UNICODE),
            'template' => mb_substr((string)($input['template'] ?? ''), 0, 50),
            'style' => mb_substr((string)($input['style'] ?? ''), 0, 500),
            'extra' => mb_substr((string)($input['extra'] ?? ''), 0, 500),
            'use_kb' => (int)($input['use_kb'] ?? 1) ? 1 : 0,
            'title' => (string)($res['title'] ?? ($p->brand_name . ' ' . $type)),
            'body' => (string)($res['body'] ?? ''),
            'tags' => json_encode($res['tags'] ?? [], JSON_UNESCAPED_UNICODE),
            'status' => 0, 'adopted' => 0, 'source_task_id' => $task->id,
            'create_time' => time(), 'update_time' => time(),
        ]);
        // 顺带提交封面图(异步,不等待出图)。图文投稿必须有图,而 geo_content
        // 本身只有正文;这里提交后由前端轮询 contentCover 取回。失败不影响文章。
        if (GeoCoverService::enabled()) {
            GeoCoverService::submit($c, $p, (int)($input['user_id'] ?? 0));
            self::log($task, '封面图', $c->cover_status === 'pending' ? '已提交 AI 封面图生成任务' : '封面图提交失败,可稍后手动重试');
        }

        self::log($task, '完成', "已生成内容《{$c->title}》");
        return 'content:' . $c->id;
    }

    protected static function handleMonitor(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        $query = (string)($input['query'] ?? $p->brand_name);
        // 场景问题维度(可选):关联 keyword/topic,供可见度聚合
        $extra = [
            'keyword_id' => (int)($input['keyword_id'] ?? 0),
            'topic_id' => (int)($input['topic_id'] ?? 0),
        ];
        // 引擎白名单 + 只跑【已接入】引擎:显式传参也不允许绕过
        // (未接入引擎恒"未出现"的占位记录会成为新 (问题×引擎) cell 拉低可见度)
        // 用 engineList() 动态判定(中台密钥已配即接入,单引擎上游缺失按占位处理),
        // 不能用静态 ENGINES 常量
        $available = array_column(array_filter(GeoMonitorService::engineList(), fn($e) => !empty($e['available'])), 'key');
        $engines = array_values(array_intersect(array_map('strval', (array)($input['engines'] ?? [])), $available));
        if (!$engines) {
            $requested = (array)($input['engines'] ?? []);
            if ($requested) {
                // 显式点名的引擎都未接入:直接失败,不能替用户改跑全部引擎(会造成计划外扣费)
                self::log($task, '引擎过滤', '所选引擎均未接入,任务终止');
                throw new \Exception('所选引擎均未接入');
            }
            $engines = $available;
        }
        self::log($task, '模拟提问', "以用户身份提问:{$query}");
        $ids = [];
        $failed = 0;
        foreach ($engines as $eng) {
            $label = GeoMonitorService::ENGINES[$eng]['label'] ?? $eng;
            self::log($task, "引擎·{$label}", "向 {$label} 提问并检测品牌可见度");
            // 单引擎失败只记日志继续跑其余引擎,不写假记录、不中断整次监测
            try {
                $m = GeoMonitorService::run($p, $query, (string)$eng, $extra);
            } catch (\Throwable $e) {
                $failed++;
                self::log($task, "失败·{$label}", $e->getMessage());
                continue;
            }
            $ids[] = $m['id'] ?? 0;
            $appear = (int)($m['brand_appear'] ?? 0);
            self::log($task, "结果·{$label}", ($appear ? "品牌已出现,排名第 {$m['brand_rank']}" : '品牌未出现') . ",可见度 {$m['brand_visibility']}");
        }
        if (!$ids) {
            throw new \Exception('全部引擎监测失败,请稍后重试');
        }
        self::log($task, '完成', '已在 ' . count($ids) . ' 个引擎完成监测' . ($failed ? ",{$failed} 个引擎失败" : ''));
        return 'monitor:' . implode(',', $ids);
    }

    protected static function handleGenSuggestion(GeoTask $task, array $input): string
    {
        $p = self::project($task->project_id);
        self::log($task, '读取监测', '汇总平台/话题/竞品/信源维度的监测事实');
        $facts = self::suggestionFacts((int)$p->id);
        // via=report(报告内嵌刷新):GeoAiService 改走 /api/geo/report 端点,
        // 保证一次报告只产生一次 report 调用,且不再单独结算 geo_suggestion
        $res = GeoAiService::call(
            GeoPrompts::SYSTEM,
            GeoPrompts::genSuggestion((string)$p->brand_name, $facts),
            true, 'gen_suggestion', (string)$p->gen_model, ['via' => (string)($input['via'] ?? '')]
        );

        // 存整个对象(含三层策略);历史数据是纯数组,report() 两种形态都兼容
        $payload = [
            'suggestions' => $res['suggestions'] ?? [],
            'strategy' => [
                'platform' => $res['platform'] ?? [],
                'content'  => $res['content'] ?? [],
                'ops'      => $res['ops'] ?? [],
            ],
        ];
        $task->result_ref = json_encode($payload, JSON_UNESCAPED_UNICODE);
        self::log($task, '完成', sprintf('已生成 %d 条落地建议 + 分平台策略 %d 档 / 内容策略 %d 类 / 长期机制 %d 条',
            count($payload['suggestions']), count($payload['strategy']['platform']),
            count($payload['strategy']['content']), count($payload['strategy']['ops'])));
        return $task->result_ref;
    }

    /**
     * 组装喂给建议生成的结构化事实。
     * 与诊断报告同口径(cell = 场景问题 × 引擎),让模型看到平台间的排名差异,
     * 而不是只看到一堆"未出现"。
     */
    protected static function suggestionFacts(int $projectId): string
    {
        $rep = \app\api\logic\geo\GeoInsightLogic::report($projectId);
        $lines = [];

        $c = $rep['cards'];
        $lines[] = "整体:可见度 {$c['visibility']}%,采集单元 {$c['total']} 个(在线 {$c['online']}),"
            . "平均位置 {$c['avg_pos']},首推率 {$c['top1_rate']}%,行业排名第 {$rep['self_rank']} 位";

        $lines[] = '各平台表现:';
        foreach ($rep['engine_dim'] as $d) {
            $lines[] = "  - {$d['label']}:可见度 {$d['visibility']}%,竞品排名第 {$d['rank']} 名,采集 {$d['total']} 个单元";
        }

        if ($rep['topics']) {
            $lines[] = '各话题表现:';
            foreach ($rep['topics'] as $t) {
                $per = [];
                foreach ($t['by_engine'] as $b) { $per[] = "{$b['label']}第{$b['rank']}名"; }
                $lines[] = "  - {$t['topic_name']}:可见度 {$t['visibility']}%,排名第 {$t['rank']}/{$t['competitor_total']}"
                    . ($per ? '(' . implode('、', $per) . ')' : '');
            }
        }

        $rivals = array_slice(array_filter($rep['competitors'], fn($r) => empty($r['is_self'])), 0, 8);
        if ($rivals) {
            $lines[] = '竞品可见度:';
            foreach ($rivals as $r) { $lines[] = "  - {$r['brand']}:{$r['visibility']}%,排名第 {$r['rank']}"; }
        } else {
            $lines[] = '竞品:本轮监测未识别到竞品品牌';
        }

        $sites = $rep['quotes']['top_sites'] ?? [];
        if ($sites) {
            $lines[] = 'AI 引用信源(GEO 内容该铺的地方):';
            foreach (array_slice($sites, 0, 8) as $s) { $lines[] = "  - {$s['site']}:被引 {$s['cite_count']} 次"; }
        }

        return implode("\n", $lines);
    }
}
