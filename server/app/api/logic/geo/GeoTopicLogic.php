<?php

namespace app\api\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoTopic;
use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoTask;
use app\common\service\geo\GeoAiService;
use app\common\service\geo\GeoMonitorService;
use app\common\service\geo\GeoPrompts;

/**
 * 话题 / 场景问题管理(对标微盟星启「设置-话题」与初始化引导)
 * 场景问题 = geo_keyword 中 type='场景问题' 且挂 topic_id 的记录。
 */
class GeoTopicLogic extends BaseLogic
{
    /** 每品牌可用话题上限(对标竞品配额 3 个,可用 env 覆盖) */
    public static function maxTopics(): int
    {
        return max(1, (int)(env('geo.max_topics', 3)));
    }

    protected static function brandContext(GeoProject $p): string
    {
        $aliases = implode('、', json_decode((string)$p->aliases, true) ?: []);
        $base = "品牌:{$p->brand_name} 行业:{$p->industry}\n品牌别名:{$aliases}\n"
            . "产品介绍:{$p->intro}\n产品特点:{$p->features}\n目标客户:{$p->target_customer}";
        // 带上品牌知识库:话题与场景问题必须由用户导入的官网/文档语料驱动,
        // 只喂 project 表那几个字段的话,AI 只能凭品牌名泛泛发挥,
        // 用户辛苦导入的知识对话题/问题毫无影响(下游内容生成一直是带知识库的)。
        $know = GeoAiService::knowledgeDigest((int)$p->id);
        return $know === '' ? $base : ($base . "\n品牌知识:{$know}");
    }

    // ---------- 初始化状态 ----------

    /** 引导向导的步骤序号,与前端 init-wizard.vue 的 STEPS 下标一一对应 */
    public const STEP_BRAND     = 0;   // 设定品牌信息
    public const STEP_TOPIC     = 1;   // 确定话题
    public const STEP_QUESTION  = 2;   // 设置场景问题
    public const STEP_DIAGNOSE  = 3;   // AI诊断报告
    public const STEP_DONE      = -1;  // 全部完成

    public const STEP_LABELS = [
        self::STEP_BRAND    => '设定品牌信息',
        self::STEP_TOPIC    => '确定话题',
        self::STEP_QUESTION => '设置场景问题',
        self::STEP_DIAGNOSE => 'AI诊断报告',
    ];

    /**
     * 引导进度 / 断点续跑状态。
     *
     * 用各步的【实际产物】反推进度,不额外落库进度字段——避免多标签页、
     * 进程被杀、请求超时这些情况下"进度字段"与真实数据脱节。
     *
     * 前端据此把用户带回中断那一步并要求手动确认,而不是每次都从第 1 步重来
     * (旧行为:initialized 只看有无 topic/question,中断后一律回到第 0 步,
     *  已经花钱生成的话题/问题用户看不到,还可能被重复生成一遍)。
     */
    public static function initState(int $projectId): array
    {
        $topicCount = GeoTopic::where('project_id', $projectId)->count();
        $questions = GeoKeyword::where('project_id', $projectId)
            ->where('type', '场景问题')->field('id,status')->select()->toArray();
        $questionCount = count($questions);
        $enabledIds = array_column(array_filter($questions, fn($q) => (int)$q['status'] === 1), 'id');

        // 诊断进度:与洞察页同口径,一个 cell = 场景问题 × 引擎
        $engineKeys = array_column(array_filter(
            GeoMonitorService::engineList(), fn($e) => !empty($e['available'])
        ), 'key');
        $engineCount = max(1, count($engineKeys));
        $cellExpect = count($enabledIds) * $engineCount;
        $cellDone = 0;
        if ($enabledIds) {
            $cellDone = count(GeoMonitor::where('project_id', $projectId)
                ->whereIn('keyword_id', $enabledIds)
                ->group('keyword_id,engine')->column('max(id)'));
        }

        // 卡住/失败的任务:10 分钟还在 running 的按"已中断"处理,与
        // GeoTaskService::run() 的防重入超时保持一致;
        // 排除 monitor_batch 批次行:它不是向导生成任务,没有重试入口
        $stuck = GeoTask::where('project_id', $projectId)->where('status', 'running')
            ->where('task_type', '<>', 'monitor_batch')
            ->where('update_time', '<=', time() - 600)->order('id desc')->findOrEmpty();
        $lastFailed = GeoTask::where('project_id', $projectId)->where('status', 'failed')
            ->where('task_type', '<>', 'monitor_batch')
            ->order('id desc')->findOrEmpty();

        // 首轮诊断是否已出结果:批次已成功,或至少跑完一个 cell。
        // 仅"提交过批次"不算——用户停在诊断步(进度 0%)时从列表点进来,
        // 应回到向导诊断步看进度,而不是被直接放进空数据的工作台
        $hasFinishedBatch = GeoTask::where('project_id', $projectId)
            ->where('task_type', 'monitor_batch')
            ->where('status', 'success')
            ->count() > 0;
        // 进行中的批次:向导诊断步据此恢复进度轮询
        $runningBatch = GeoTask::where('project_id', $projectId)
            ->where('task_type', 'monitor_batch')
            ->where('status', 'running')
            ->order('id', 'desc')
            ->findOrEmpty();

        // 反推该回到哪一步
        if ($topicCount === 0) {
            $step = self::STEP_TOPIC;
        } elseif ($questionCount === 0) {
            $step = self::STEP_QUESTION;
        } elseif ($cellDone === 0 && !$hasFinishedBatch) {
            $step = self::STEP_DIAGNOSE;
        } else {
            $step = self::STEP_DONE;
        }

        // initialized 决定「是否还要强制走向导」。判定为"首轮诊断已出结果"
        // (批次成功或至少一个 cell 完成),而不是"监测全覆盖"——后者会让用户
        // 以后每加一个场景问题就被打回向导。部分覆盖属于日常运营,归工作台管。
        $initialized = $topicCount > 0 && $questionCount > 0 && ($cellDone > 0 || $hasFinishedBatch);
        // 有产物但没走完 = 上次中途离开了。全新项目(什么都没有)不算中断,
        // 走正常的从头引导。
        $hasProgress = $topicCount > 0 || $questionCount > 0;
        $interrupted = !$initialized && $hasProgress;

        $hint = '';
        if ($interrupted) {
            $label = self::STEP_LABELS[$step] ?? '';
            $hint = "上次在「{$label}」中断";
            if ($step === self::STEP_DIAGNOSE && $cellDone > 0) {
                $hint .= "(已完成 {$cellDone}/{$cellExpect} 项监测)";
            } elseif ($step === self::STEP_QUESTION && $topicCount > 0) {
                $hint .= "(已有 {$topicCount} 个话题)";
            }
        }

        return [
            'topic_count' => $topicCount,
            'question_count' => $questionCount,
            'initialized' => $initialized,
            'max_topics' => self::maxTopics(),

            // ---- 断点续跑 ----
            'resume_step' => $step,
            'interrupted' => $interrupted,
            'hint' => $hint,
            // 进行中的诊断批次 id(0=无):向导诊断步恢复进度轮询用
            'running_batch_id' => $runningBatch->isEmpty() ? 0 : (int)$runningBatch->id,
            'progress' => [
                'topic_count' => $topicCount,
                'question_count' => $questionCount,
                'question_enabled' => count($enabledIds),
                'engine_count' => count($engineKeys),
                'cell_done' => $cellDone,
                'cell_expect' => $cellExpect,
            ],
            // 任务侧异常:前端可提示"上次的生成任务失败了,要不要重试"
            'stuck_task' => $stuck->isEmpty() ? null
                : ['id' => (int)$stuck->id, 'task_type' => (string)$stuck->task_type],
            'last_failed_task' => $lastFailed->isEmpty() ? null
                : ['id' => (int)$lastFailed->id, 'task_type' => (string)$lastFailed->task_type],
        ];
    }

    // ---------- 话题 ----------

    public static function topicList(int $projectId): array
    {
        $list = GeoTopic::where('project_id', $projectId)->order('id asc')->select()->toArray();
        foreach ($list as &$t) {
            $t['question_count'] = GeoKeyword::where('topic_id', $t['id'])->where('type', '场景问题')->count();
        }
        return ['list' => $list, 'max' => self::maxTopics(), 'remaining' => max(0, self::maxTopics() - count($list))];
    }

    public static function topicSave(int $projectId, array $p)
    {
        $name = trim((string)($p['name'] ?? ''));
        if ($name === '') { self::setError('请填写话题名称'); return false; }
        if (!empty($p['id'])) {
            $t = GeoTopic::where('project_id', $projectId)->findOrEmpty((int)$p['id']);
            if ($t->isEmpty()) { self::setError('话题不存在'); return false; }
            $t->name = $name;
            if (isset($p['question_target'])) $t->question_target = max(1, (int)$p['question_target']);
            $t->update_time = time();
            $t->save();
            return ['id' => (int)$t->id];
        }
        $count = GeoTopic::where('project_id', $projectId)->count();
        if ($count >= self::maxTopics()) { self::setError('话题数量已达上限(' . self::maxTopics() . '个)'); return false; }
        $dup = GeoTopic::where('project_id', $projectId)->where('name', $name)->count();
        if ($dup) { self::setError('话题已存在'); return false; }
        $t = GeoTopic::create([
            'project_id' => $projectId, 'name' => $name, 'status' => 1,
            'question_target' => max(1, (int)($p['question_target'] ?? 10)),
            'create_time' => time(), 'update_time' => time(),
        ]);
        return ['id' => (int)$t->id];
    }

    public static function topicToggle(int $projectId, int $id): void
    {
        $t = GeoTopic::where('project_id', $projectId)->findOrEmpty($id);
        if ($t->isEmpty()) return;
        $t->status = $t->status ? 0 : 1;
        $t->update_time = time();
        $t->save();
        // 级联到其下场景问题:话题停用/启用后,问题列表与监测分母要同步,
        // 否则出现"话题已停用、其下问题仍启用中"的矛盾,停用话题还会继续参与诊断计费。
        // 注意:启用话题会把其下问题整体置为启用,单题的手动停用状态不保留(简单可预期)
        GeoKeyword::where('project_id', $projectId)
            ->where('topic_id', $id)
            ->where('type', '场景问题')
            ->update(['status' => (int)$t->status]);
    }

    public static function topicDelete(int $projectId, int $id): void
    {
        $t = GeoTopic::where('project_id', $projectId)->findOrEmpty($id);
        if ($t->isEmpty()) return;
        // 连带软删该话题下的场景问题
        foreach (GeoKeyword::where('topic_id', $id)->select() as $k) { $k->delete(); }
        $t->delete();
    }

    // ---------- 场景问题 ----------

    public static function questionList(int $projectId, array $f): array
    {
        $q = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题');
        if (!empty($f['topic_id'])) $q->where('topic_id', (int)$f['topic_id']);
        if (isset($f['status']) && $f['status'] !== '') $q->where('status', (int)$f['status']);
        if (!empty($f['keyword'])) $q->where('value', 'like', '%' . $f['keyword'] . '%');
        // 兼容分页:传 page 参数返回 {list,total,page,limit},不传保持旧的全量数组
        // (向导/诊断/内容生成等消费方仍按全量取)
        $paged = isset($f['page']) && (int)$f['page'] > 0;
        $total = 0;
        $page = 1;
        $limit = 20;
        if ($paged) {
            $total = (clone $q)->count();
            $page = max(1, (int)$f['page']);
            $limit = min(100, max(1, (int)($f['limit'] ?? 20)));
            $q->page($page, $limit);
        }
        $list = $q->order('id desc')->select()->toArray();
        $topicNames = GeoTopic::where('project_id', $projectId)->column('name', 'id');

        // 每题在线率:与洞察页同口径,取该题在每个引擎上的最新一条监测(问题×引擎 cell)
        $stats = [];
        $qids = array_column($list, 'id');
        if ($qids) {
            $maxIds = \app\common\model\geo\GeoMonitor::where('project_id', $projectId)
                ->whereIn('keyword_id', $qids)
                ->group('keyword_id,engine')->column('max(id)');
            if ($maxIds) {
                $rows = \app\common\model\geo\GeoMonitor::whereIn('id', $maxIds)
                    ->field('id,keyword_id,brand_appear')->select()->toArray();
                foreach ($rows as $r) {
                    $kid = (int)$r['keyword_id'];
                    $stats[$kid]['total'] = ($stats[$kid]['total'] ?? 0) + 1;
                    $stats[$kid]['online'] = ($stats[$kid]['online'] ?? 0) + ((int)$r['brand_appear'] ? 1 : 0);
                }
            }
        }
        foreach ($list as &$k) {
            $k['topic_name'] = $topicNames[$k['topic_id']] ?? '';
            $s = $stats[(int)$k['id']] ?? null;
            // null = 还没测过,前端显示"未监测";0 = 测过但全离线(内容缺口)
            $k['online_rate'] = $s ? (int)round($s['online'] / max(1, $s['total']) * 100) : null;
            $k['cell_total'] = $s['total'] ?? 0;
            $k['cell_online'] = $s['online'] ?? 0;
        }
        return $paged ? ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit] : $list;
    }

    public static function questionSave(int $projectId, array $p)
    {
        $value = trim((string)($p['value'] ?? ''));
        if ($value === '') { self::setError('请填写场景问题'); return false; }
        // topic_id 传入时须属于本项目(建/改共用)
        if (!empty($p['topic_id'])
            && GeoTopic::where('project_id', $projectId)->findOrEmpty((int)$p['topic_id'])->isEmpty()) {
            self::setError('所属话题不存在');
            return false;
        }
        if (!empty($p['id'])) {
            // 与 questionBatch 同口径:仅限本项目的场景问题,避免改写普通关键词
            $k = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->findOrEmpty((int)$p['id']);
            if ($k->isEmpty()) { self::setError('问题不存在'); return false; }
            $dup = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')
                ->where('value', $value)->where('id', '<>', (int)$k->id)->count();
            if ($dup) { self::setError('该场景问题已存在'); return false; }
            $k->value = $value;
            if (isset($p['topic_id'])) $k->topic_id = (int)$p['topic_id'];
            $k->save();
            return ['id' => (int)$k->id];
        }
        if (empty($p['topic_id'])) { self::setError('请选择所属话题'); return false; }
        $dup = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->where('value', $value)->count();
        if ($dup) { self::setError('该场景问题已存在'); return false; }
        $k = GeoKeyword::create([
            'project_id' => $projectId, 'topic_id' => (int)$p['topic_id'],
            'type' => '场景问题', 'value' => $value, 'status' => 1, 'source' => 'manual',
            'create_time' => time(),
        ]);
        return ['id' => (int)$k->id];
    }

    /** 批量操作:enable | disable | delete */
    public static function questionBatch(int $projectId, array $ids, string $action)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) { self::setError('请选择场景问题'); return false; }
        if (!in_array($action, ['enable', 'disable', 'delete'], true)) { self::setError('不支持的操作'); return false; }
        // 仅限本项目的场景问题,避免误伤普通关键词
        $rows = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->whereIn('id', $ids)->select();
        foreach ($rows as $k) {
            if ($action === 'delete') { $k->delete(); continue; }
            $k->status = $action === 'enable' ? 1 : 0;
            $k->save();
        }
        return ['count' => count($rows)];
    }

    // ---------- AI 推荐 ----------

    /** AI 推荐话题(初始化引导 · 智能推荐话题);失败返回 false + 错误信息 */
    public static function aiTopics(int $userId, int $projectId, int $count = 3)
    {
        $count = max(1, min(20, $count));
        // 按实际 token 计费:预检不扣,推荐成功后按中台 usage 结算
        try { \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_topic_ai'); }
        catch (\Throwable $e) { self::setError($e->getMessage()); return false; }
        $p = GeoProject::findOrEmpty($projectId);
        try {
            GeoAiService::resetUsage();
            $res = GeoAiService::call(
                GeoPrompts::SYSTEM,
                GeoPrompts::recommendTopics(self::brandContext($p), $count),
                true, 'recommend_topics', (string)$p->gen_model
            );
        } catch (\Throwable $e) {
            self::setError('AI 推荐失败,请稍后重试:' . $e->getMessage());
            return false;
        }
        $topics = array_values(array_filter(array_map('trim', (array)($res['topics'] ?? []))));
        // 只要中台产生了真实调用(有 usage)就结算:按"是否有产物"决定扣费会被
        // 反复点击白嫖(每次都是真实模型调用,结果为空/重复也有成本)
        if ($topics || (int)(GeoAiService::usage()['total'] ?? 0) > 0) {
            // 每次成功调用单独记账:ref 不能用固定 project:id,否则第二次点击中台已扣、站长端被幂等跳过
            \app\common\service\geo\GeoChargeService::settleByUsage(
                $userId,
                'geo_topic_ai',
                GeoAiService::usage(),
                'topic_ai:' . $projectId . ':' . uniqid('', true)
            );
        }
        return ['topics' => array_slice($topics, 0, $count)];
    }

    /**
     * AI 为话题生成场景问题并入库(去重)。
     * @param string $extraInfo 补充产品特性、服务优势、特点区域等
     */
    public static function aiQuestions(int $userId, int $projectId, int $topicId, int $count = 10, string $extraInfo = '')
    {
        $count = max(1, min(50, $count));
        // 按实际 token 计费:按典型单次消耗预检(一次中台调用,与目标条数无关),
        // 生成成功且有新增入库后按中台 usage 结算
        try { \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_question_ai'); }
        catch (\Throwable $e) { self::setError($e->getMessage()); return false; }
        $p = GeoProject::findOrEmpty($projectId);
        $topic = GeoTopic::where('project_id', $projectId)->findOrEmpty($topicId);
        if ($topic->isEmpty()) { self::setError('话题不存在'); return false; }
        $ctx = self::brandContext($p) . ($extraInfo !== '' ? "\n补充:{$extraInfo}" : '');
        // 用话题名当种子拉一轮真实搜索联想词(中台 /api/geo/search_suggest)喂给模型。
        // 拉取失败仅告警降级为纯 AI 生成,不阻断主流程,也不改变计费口径。
        $realTerms = [];
        try {
            // 走 GeoTikhubService:TIKHUB_SUGGEST 开关/中台可用性闸与失败降级都在里面,
            // 避免绕过开关直调中台(中台免费场景,但每次都产生中台调用)
            $sg = \app\common\service\geo\GeoTikhubService::searchSuggest((string)$topic->name);
            $realTerms = array_values(array_filter((array)$sg, function ($t) {
                return trim((string)($t['term'] ?? '')) !== '';
            }));
        } catch (\Throwable $e) {
            \think\facade\Log::warning('GEO 搜索联想词拉取失败,降级为纯 AI 生成: ' . $e->getMessage());
        }
        try {
            // 中台 question 端点:返回 questions 已清洗去重,另透出 question_count
            GeoAiService::resetUsage();
            $res = GeoAiService::call(
                GeoPrompts::SYSTEM,
                GeoPrompts::genSceneQuestions((string)$p->brand_name, (string)$topic->name, $ctx, $count, $realTerms),
                true, 'gen_scene_questions', (string)$p->gen_model
            );
        } catch (\Throwable $e) {
            self::setError('AI 生成失败,请稍后重试:' . $e->getMessage());
            return false;
        }
        $questions = array_values(array_filter(array_map('trim', (array)($res['questions'] ?? []))));
        $exists = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->column('value');
        $created = 0;
        foreach (array_slice($questions, 0, max(1, $count)) as $qv) {
            if (in_array($qv, $exists)) continue;
            GeoKeyword::create([
                'project_id' => $projectId, 'topic_id' => $topicId,
                'type' => '场景问题', 'value' => $qv, 'status' => 1,
                // 区分"AI 凭空生成"与"基于真实搜索需求生成",报告里能据此说明问题来源
                'source' => $realTerms ? 'ai_search' : 'ai',
                'create_time' => time(),
            ]);
            $created++;
        }
        // 只要有真实模型调用就结算(created=0 只是全部重复,成本已发生),防反复点击白嫖
        if ($created > 0 || (int)(GeoAiService::usage()['total'] ?? 0) > 0) {
            \app\common\service\geo\GeoChargeService::settleByUsage(
                $userId,
                'geo_question_ai',
                GeoAiService::usage(),
                'question_ai:' . $topicId . ':' . uniqid('', true)
            );
        }
        // real_terms 回给前端展示"这批问题是基于哪些真实搜索需求生成的",
        // 否则这个增强对用户完全不可见,等于白做。
        // 只回实际参考过的词(AI 的 used_terms 逐字匹配候选词),而不是喂给 AI 的全部
        // 候选:补 3 条问题却展示 20 个词,数量对不上会被用户当成 bug。
        // AI 未按约定返回时,兜底按热度序取与生成条数相同数量的词。
        $used = array_values(array_filter(array_map('trim', (array)($res['used_terms'] ?? []))));
        $shownTerms = $used
            ? array_values(array_filter($realTerms, fn($t) => in_array(trim((string)$t['term']), $used, true)))
            : [];
        if (!$shownTerms) {
            $shownTerms = array_slice($realTerms, 0, max(1, $created));
        }
        // 全是重复、一条没入库时不展示"基于真实搜索需求生成"面板,避免 0 新增却弹参考词
        if ($created === 0) {
            $shownTerms = [];
        }
        return [
            'created' => $created,
            'total' => count($questions),
            'real_terms' => array_slice($shownTerms, 0, 20),
        ];
    }
}
