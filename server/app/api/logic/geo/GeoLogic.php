<?php

namespace app\api\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoKnowledge;
use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoTask;
use app\common\model\user\User;
use app\common\service\geo\GeoCredentialService;
use app\common\service\geo\GeoHelper;
use app\common\service\geo\GeoTaskService;

/**
 * GEO 业务逻辑。数据按 project 隔离,project 归属创建用户,与当前个人/企业空间无关。
 */
class GeoLogic extends BaseLogic
{
    /** 写入时带上当前 team_id 仅作记录;读写与鉴权一律按 user_id */
    protected static function scope(int $userId): array
    {
        $teamId = (int)User::where('id', $userId)->value('team_id');
        return [$userId, $teamId];
    }

    protected static function assertOwn(int $userId, int $projectId): GeoProject
    {
        $p = GeoProject::findOrEmpty($projectId);
        if ($p->isEmpty() || (int)$p->user_id !== $userId) {
            return new GeoProject();
        }
        return $p;
    }

    /**
     * 项目归属校验(供 Controller 在所有 project 级接口入口统一调用,防越权/IDOR)。
     * @return bool true=归当前用户所有
     */
    public static function userOwnsProject(int $userId, int $projectId): bool
    {
        return $projectId > 0 && !self::assertOwn($userId, $projectId)->isEmpty();
    }

    /** 内容归属校验:内容存在且其所属项目归当前用户 */
    public static function userOwnsContent(int $userId, int $contentId): bool
    {
        $c = GeoContent::findOrEmpty($contentId);
        return !$c->isEmpty() && self::userOwnsProject($userId, (int)$c->project_id);
    }

    /** 知识实体归属校验:实体存在且其所属项目归当前用户 */
    public static function userOwnsKnowledge(int $userId, int $knowledgeId): bool
    {
        $k = GeoKnowledge::findOrEmpty($knowledgeId);
        return !$k->isEmpty() && self::userOwnsProject($userId, (int)$k->project_id);
    }

    /** 站点归属校验:站点存在且归当前用户 */
    public static function userOwnsSite(int $userId, int $siteId): bool
    {
        $s = \app\common\model\geo\GeoSite::findOrEmpty($siteId);
        return !$s->isEmpty() && (int)$s->user_id === $userId;
    }

    /** 定时发布任务归属校验 */
    public static function userOwnsSiteTask(int $userId, int $taskId): bool
    {
        $t = \app\common\model\geo\GeoSiteTask::findOrEmpty($taskId);
        return !$t->isEmpty() && self::userOwnsSite($userId, (int)$t->site_id);
    }

    /** 生成侧可选模型 */
    /**
     * AI 匹配品牌信息(向导第一步):仅凭品牌/公司名推导 行业+别名+简介。
     * 走新中台 /api/geo/match_brand 端点(precheck → 调用 → settle,一次计费);
     * 失败不阻断向导(可手填),也不落假数据。不依赖 project(建品牌前即可调用)。
     * @return array|false ['industry','aliases','intro','confidence','model','source']
     */
    public static function matchBrand(int $userId, string $name, string $model = '')
    {
        $name = trim($name);
        if ($name === '') { self::setError('请填写品牌/公司名称'); return false; }
        try { \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_match_brand'); }
        catch (\Throwable $e) { self::setError($e->getMessage()); return false; }

        $prompt = \app\common\service\geo\GeoPrompts::matchBrandInfo($name);
        $system = \app\common\service\geo\GeoPrompts::SYSTEM;
        $modelAlias = \app\common\service\geo\GeoAiService::matchBrandModel($model);
        try {
            // 中台 match_brand 端点(GeoAiService 按 kind 路由,max_tokens 统一 4000)
            \app\common\service\geo\GeoAiService::resetUsage();
            $res = \app\common\service\geo\GeoAiService::call($system, $prompt, true, 'match_brand', $modelAlias);
        } catch (\Throwable $e) {
            self::setError('AI 匹配失败,请稍后重试或手动填写:' . $e->getMessage());
            return false;
        }
        $industry = trim((string)($res['industry'] ?? ''));
        $aliases = array_values(array_filter(array_map('trim', (array)($res['aliases'] ?? [])), function ($a) use ($name) {
            return $a !== '' && $a !== $name;
        }));
        // AI 不了解的品牌(low)拿不到常见别名,本地按名称字面推导简称兜底:
        // 监测命中依赖简称(回答里写「爱脉」而非「爱脉网络科技」),别名区不能空白
        foreach (\app\common\service\geo\GeoHelper::deriveBrandAliases($name) as $derived) {
            if ($derived !== $name && !in_array($derived, $aliases, true)) {
                $aliases[] = $derived;
            }
        }
        $confidence = in_array(($res['confidence'] ?? ''), ['high', 'low'], true) ? $res['confidence'] : 'low';
        if ($industry === '' && !$aliases) { self::setError('AI 未能识别该品牌,请手动填写行业与别名'); return false; }
        \app\common\service\geo\GeoChargeService::settleByUsage(
            $userId,
            'geo_match_brand',
            \app\common\service\geo\GeoAiService::usage(),
            'match:' . $name . ':' . uniqid('', true)
        );
        return [
            'industry' => $industry,
            'aliases' => array_slice($aliases, 0, 5),
            'intro' => trim((string)($res['intro'] ?? '')),
            'confidence' => $confidence,
            'model' => $modelAlias,
            'source' => 'geo',
        ];
    }

    /** 「AI匹配品牌信息」的模型列表(中台化后不再本地策展,恒为空)+ 当前默认 */
    public static function matchModelOptions(): array
    {
        return [
            'list' => \app\common\service\geo\GeoAiService::systemModelOptions(),
            'default' => \app\common\service\geo\GeoAiService::matchBrandModel(),
        ];
    }

    public static function modelOptions(): array
    {
        return \app\common\service\geo\GeoAiService::modelOptions();
    }

    public static function projectList(int $userId): array
    {
        $list = GeoProject::where('user_id', $userId)->order('id desc')->select()->toArray();
        if (!$list) return [];
        $ids = array_map('intval', array_column($list, 'id'));
        $kw = GeoKeyword::whereIn('project_id', $ids)->group('project_id')->column('count(*)', 'project_id');
        $ct = GeoContent::whereIn('project_id', $ids)->group('project_id')->column('count(*)', 'project_id');
        $monRows = GeoMonitor::whereIn('project_id', $ids)
            ->field('project_id, count(*) as monitor_count, sum(brand_appear) as online_count, avg(brand_visibility) as avg_vis, max(create_time) as last_at')
            ->group('project_id')
            ->select()
            ->toArray();
        $mon = [];
        foreach ($monRows as $r) {
            $mon[(int)$r['project_id']] = $r;
        }
        foreach ($list as &$p) {
            $id = (int)$p['id'];
            $m = $mon[$id] ?? [];
            $total = (int)($m['monitor_count'] ?? 0);
            $online = (int)($m['online_count'] ?? 0);
            $p['keyword_count'] = (int)($kw[$id] ?? 0);
            $p['content_count'] = (int)($ct[$id] ?? 0);
            $p['monitor_count'] = $total;
            $p['online_count'] = $online;
            $p['online_rate'] = $total ? (int)round($online / max(1, $total) * 100) : null;
            $p['avg_visibility'] = $total ? (int)round((float)($m['avg_vis'] ?? 0)) : null;
            $p['last_monitor_at'] = $m['last_at'] ?? 0;
        }
        unset($p);
        return $list;
    }

    public static function projectDetail(int $userId, int $id): array
    {
        $p = self::assertOwn($userId, $id);
        if ($p->isEmpty()) {
            return [];
        }
        $data = $p->toArray();
        $data['keywords'] = json_decode((string)$p->keywords, true) ?: [];
        $data['competitors'] = json_decode((string)$p->competitors, true) ?: [];
        $data['aliases'] = json_decode((string)$p->aliases, true) ?: [];
        $data['stat'] = [
            'knowledge' => GeoKnowledge::where('project_id', $id)->count(),
            'keyword' => GeoKeyword::where('project_id', $id)->count(),
            'content' => GeoContent::where('project_id', $id)->count(),
            'monitor' => GeoMonitor::where('project_id', $id)->count(),
        ];
        // 最近任务动态
        $data['recent_tasks'] = GeoTask::where('project_id', $id)->order('id desc')->limit(8)
            ->field('id,task_type,status,create_time')->select()->toArray();
        // 平均可见度
        $data['avg_visibility'] = (int)round((float)GeoMonitor::where('project_id', $id)->avg('brand_visibility'));
        return $data;
    }

    public static function projectCreate(int $userId, array $p)
    {
        // 团队成员到期硬拦截
        try { \app\common\service\TeamMemberService::assertActive($userId); }
        catch (\Throwable $e) { self::setError($e->getMessage()); return false; }
        if (empty(trim((string)($p['brand_name'] ?? '')))) {
            self::setError('请填写品牌名称');
            return false;
        }
        [$uid, $teamId] = self::scope($userId);
        $project = GeoProject::create([
            'user_id' => $uid, 'team_id' => $teamId,
            'brand_name' => trim($p['brand_name']),
            'website' => (string)($p['website'] ?? ''),
            'logo' => (string)($p['logo'] ?? ''),
            'industry' => (string)($p['industry'] ?? ''),
            'intro' => (string)($p['intro'] ?? ''),
            'features' => (string)($p['features'] ?? ''),
            'target_customer' => (string)($p['target_customer'] ?? ''),
            'keywords' => json_encode($p['keywords'] ?? [], JSON_UNESCAPED_UNICODE),
            'competitors' => json_encode($p['competitors'] ?? [], JSON_UNESCAPED_UNICODE),
            'aliases' => json_encode($p['aliases'] ?? [], JSON_UNESCAPED_UNICODE),
            'country' => (string)($p['country'] ?? '中国'),
            'lang' => (string)($p['lang'] ?? 'zh'),
            'gen_model' => (string)($p['gen_model'] ?? ''),
            'create_time' => time(), 'update_time' => time(),
        ]);
        // 触发 build_context
        GeoTaskService::dispatch((int)$project->id, 'build_context', []);
        return ['id' => $project->id];
    }

    public static function projectUpdate(int $userId, array $p)
    {
        $project = self::assertOwn($userId, (int)($p['id'] ?? 0));
        if ($project->isEmpty()) {
            self::setError('项目不存在或无权限');
            return false;
        }
        foreach (['brand_name', 'website', 'logo', 'industry', 'intro', 'features', 'target_customer', 'country', 'lang', 'gen_model'] as $k) {
            if (array_key_exists($k, $p)) $project->$k = (string)$p[$k];
        }
        // 生成模型白名单:models_cost 查不到计价行的别名会被计费层按免费放行,
        // 任意别名可白嫖生成——只允许空(系统默认)或有计价行的模型
        if (array_key_exists('gen_model', $p) && (string)$project->gen_model !== ''
            && \app\common\service\geo\GeoChargeService::modelCost((string)$project->gen_model) === null) {
            self::setError('所选模型不可用(未配置计价),请重新选择');
            return false;
        }
        // 每日自动监测开关(二期):开启后每天凌晨自动全量采集一轮,与手动诊断同价
        if (array_key_exists('auto_monitor', $p)) $project->auto_monitor = (int)$p['auto_monitor'] ? 1 : 0;
        foreach (['keywords', 'competitors', 'aliases'] as $k) {
            if (array_key_exists($k, $p)) $project->$k = json_encode($p[$k], JSON_UNESCAPED_UNICODE);
        }
        $project->update_time = time();
        $project->save();
        return true;
    }

    public static function projectDelete(int $userId, int $id): void
    {
        $p = self::assertOwn($userId, $id);
        if ($p->isEmpty()) return;
        // 先把未发布投递的扣费退回原扣费人再删项目:项目软删后 assertOwn 查不到,
        // publishDelete 的退费通道会被永久堵死,资金锁死
        \think\facade\Db::transaction(function () use ($p) {
            $rows = \app\common\model\geo\GeoPublish::where('project_id', $p->id)
                ->where('status', '<>', 'published')->where('cost', '>', 0)
                ->lock(true)->select();
            foreach ($rows as $r) {
                \app\common\service\geo\GeoPublishService::refundIfUnpublished($r);
                $r->delete();
            }
            // 停掉官网 SEO 定时任务,避免项目软删后仍继续往 WP/webhook 发文
            \app\common\model\geo\GeoSiteTask::where('project_id', (int)$p->id)->update(['status' => 0, 'update_time' => time()]);
            // 终止在跑的诊断批次:项目删了 cron 仍会逐 cell 领取(项目查不到全判失败),
            // 白白占用每分钟的执行预算
            GeoTask::where('project_id', (int)$p->id)
                ->where('task_type', 'monitor_batch')->where('status', 'running')
                ->update(['status' => 'failed', 'update_time' => time()]);
            $p->delete();
        });
    }

    // 知识
    public static function knowledgeList(int $projectId): array
    {
        return GeoKnowledge::where('project_id', $projectId)->order('id desc')->select()->toArray();
    }

    public static function knowledgeImport(int $userId, int $projectId, array $input): array
    {
        $text = trim((string)($input['text'] ?? ''));
        if ($text === '') {
            throw new \Exception('请填写要导入的文本');
        }
        // 截断超长文本,避免 input JSON 超出列容量导致任务静默按空文本执行
        if (mb_strlen($text) > 200000) {
            $input['text'] = mb_substr($text, 0, 200000);
        } else {
            $input['text'] = $text;
        }
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_knowledge');
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = GeoTaskService::dispatch($projectId, 'parse_knowledge', $input);
        if (($task['status'] ?? '') === 'success') {
            \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_knowledge', $task);
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '知识解析失败'));
        }
        return $task;
    }

    /** 知识实体允许的类型,与 GeoPrompts::parseKnowledge 抽取枚举对齐 */
    public const KNOWLEDGE_TYPES = ['品牌介绍', '产品介绍', '能力标签', '行业标签', '产品特点', '用户画像', '业务流程', '术语'];

    public static function knowledgeSave(int $id, array $input): array
    {
        $row = GeoKnowledge::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \Exception('知识不存在');
        }
        $content = trim((string)($input['content'] ?? ''));
        if ($content === '') {
            throw new \Exception('请填写知识内容');
        }
        $type = trim((string)($input['entity_type'] ?? ''));
        if ($type !== '' && !in_array($type, self::KNOWLEDGE_TYPES, true)) {
            throw new \Exception('不支持的知识类型');
        }
        $row->content = $content;
        if ($type !== '') {
            $row->entity_type = $type;
        }
        $row->save();
        return $row->toArray();
    }

    public static function knowledgeDelete(int $id): void
    {
        $row = GeoKnowledge::findOrEmpty($id);
        if (!$row->isEmpty()) {
            $row->delete();
        }
    }

    // 关键词
    public static function analyze(int $userId, int $projectId): array
    {
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_analyze');
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = GeoTaskService::dispatch($projectId, 'analyze_brand', []);
        if (($task['status'] ?? '') === 'success') {
            // 用量含链式 gen_keyword 辅助调用(同一计费流程累计)
            \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_analyze', $task);
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '品牌分析失败'));
        }
        return $task;
    }

    public static function keywordList(int $projectId): array
    {
        $list = GeoKeyword::where('project_id', $projectId)->order('id desc')->select()->toArray();
        // 按类型分组
        $grouped = [];
        foreach ($list as $k) {
            $grouped[$k['type']][] = $k;
        }
        return ['list' => $list, 'grouped' => $grouped];
    }

    // 内容
    public static function genContent(int $userId, int $projectId, array $types): array
    {
        // 规范化入参,避免 (array)"faq" 被拆成单字符导致错计费
        $types = GeoHelper::normalizeStringList($types);
        $types = $types ?: ['faq'];
        // 按实际 token 计费:先做最低预检,每篇按其真实消耗结算
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_content', count($types));
        $tasks = [];
        $ok = 0;
        foreach ($types as $t) {
            \app\common\service\geo\GeoAiService::resetUsage(); // 每篇独立计量
            $task = GeoTaskService::dispatch($projectId, 'gen_content', [
                'content_type' => $t,
                'user_id' => $userId, // 封面图扣费归属操作者
            ]);
            if (($task['status'] ?? '') === 'success') {
                \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_content', $task);
                $ok++;
            }
            $tasks[] = $task;
        }
        if ($ok === 0) {
            throw new \Exception(GeoHelper::taskErrorMessage($tasks[0] ?? [], '内容生成失败'));
        }
        return ['tasks' => $tasks];
    }

    public static function contentList(int $projectId, array $filter): array
    {
        $q = GeoContent::where('project_id', $projectId)->order('id desc');
        if (!empty($filter['content_type'])) $q->where('content_type', $filter['content_type']);
        if (!empty($filter['topic_id'])) $q->where('topic_id', (int)$filter['topic_id']);
        if (!empty($filter['keyword'])) $q->where('title', 'like', '%' . $filter['keyword'] . '%');
        if (!empty($filter['unpublished'])) $q->where('status', 0);
        if (!empty($filter['unadopted'])) $q->where('adopted', 0);
        // 兼容分页:传 page 参数返回 {list,total,page,limit},不传保持旧的全量数组
        $paged = isset($filter['page']) && (int)$filter['page'] > 0;
        $total = 0;
        $page = 1;
        $limit = 20;
        if ($paged) {
            $total = (clone $q)->count();
            $page = max(1, (int)$filter['page']);
            $limit = min(100, max(1, (int)($filter['limit'] ?? 20)));
            $q->page($page, $limit);
        }
        $list = $q->select()->toArray();
        $topicNames = \app\common\model\geo\GeoTopic::where('project_id', $projectId)->column('name', 'id');
        foreach ($list as &$c) {
            $c['tags'] = json_decode((string)$c['tags'], true) ?: [];
            $c['keyword_ids'] = GeoHelper::normalizeIntList($c['keyword_ids'] ?? []);
            $c['topic_name'] = $topicNames[$c['topic_id'] ?? 0] ?? '';
        }
        return $paged ? ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit] : $list;
    }

    /**
     * 聊天式内容生成(对标微盟「聊出你的内容」):
     * 话题 + 场景问题 + 创作模板/风格 + 是否用知识库 + 补充诉求 → 一篇文章
     */
    public static function chatGenerate(int $userId, int $projectId, array $input): array
    {
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_content');
        $keywordIds = GeoHelper::normalizeIntList($input['keyword_ids'] ?? []);
        $questions = $keywordIds
            ? GeoKeyword::whereIn('id', $keywordIds)->where('project_id', $projectId)->column('value')
            : [];
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = GeoTaskService::dispatch($projectId, 'gen_content', [
            'topic_id' => (int)($input['topic_id'] ?? 0),
            'questions' => $questions,
            'keyword_id' => $keywordIds[0] ?? 0,
            'keyword_ids' => $keywordIds,
            'template' => (string)($input['template'] ?? ''),
            'style' => (string)($input['style'] ?? ''),
            'extra' => (string)($input['extra'] ?? ''),
            'use_kb' => (int)($input['use_kb'] ?? 1),
            'user_id' => $userId,
        ]);
        // result_ref 形如 content:123 → 直接带回内容详情
        $contentId = (int)str_replace('content:', '', (string)($task['result_ref'] ?? ''));
        $content = $contentId ? GeoContent::findOrEmpty($contentId)->toArray() : [];
        if ($content) {
            $content['tags'] = json_decode((string)($content['tags'] ?? ''), true) ?: [];
            $content['keyword_ids'] = GeoHelper::normalizeIntList($content['keyword_ids'] ?? []);
        }
        if (($task['status'] ?? '') === 'success') {
            \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_content', $task);
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '内容生成失败'));
        }
        return ['task' => $task, 'content' => $content];
    }

    /** 内容创作模板清单(聊天式生成第 2 步) */
    public static function contentTemplates(): array
    {
        $out = [];
        foreach (\app\common\service\geo\GeoPrompts::CONTENT_TEMPLATES as $key => $t) {
            $out[] = ['key' => $key, 'label' => $t['label'], 'desc' => $t['desc']];
        }
        return $out;
    }

    public static function contentUpdate(int $id, array $p): void
    {
        $c = GeoContent::findOrEmpty($id);
        if ($c->isEmpty()) return;
        foreach (['title', 'body', 'content_type', 'template', 'style', 'extra'] as $k) {
            if (array_key_exists($k, $p)) $c->$k = (string)$p[$k];
        }
        // status(是否已发布)由发布链路维护,不接受前端直改:
        // 否则可把没发过的内容标成已发布,污染 unpublished 筛选与官网发布去重
        if (array_key_exists('adopted', $p)) $c->adopted = (int)$p['adopted'] ? 1 : 0;
        if (array_key_exists('use_kb', $p)) $c->use_kb = (int)$p['use_kb'] ? 1 : 0;
        if (array_key_exists('keyword_ids', $p)) {
            $ids = GeoHelper::normalizeIntList($p['keyword_ids']);
            $c->keyword_ids = json_encode($ids, JSON_UNESCAPED_UNICODE);
            if ($ids) $c->keyword_id = $ids[0];
        }
        if (array_key_exists('tags', $p)) $c->tags = json_encode($p['tags'], JSON_UNESCAPED_UNICODE);
        $c->update_time = time();
        $c->save();
    }

    public static function contentRegenerate(int $userId, int $id): array
    {
        $c = GeoContent::findOrEmpty($id);
        if ($c->isEmpty()) return [];
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_content');
        // 带回原文的话题/场景问题上下文:只传 content_type 的话,"重新生成"出来的是
        // 一篇与原文毫不相干的通用文章,用户预期的是"照这个主题再写一版"。
        $questions = [];
        if ((int)$c->keyword_id > 0) {
            $questions = GeoKeyword::where('id', (int)$c->keyword_id)
                ->where('project_id', (int)$c->project_id)->column('value');
        }
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = GeoTaskService::dispatch((int)$c->project_id, 'gen_content', [
            'content_type' => $c->content_type,
            'topic_id' => (int)$c->topic_id,
            'keyword_id' => (int)$c->keyword_id,
            'questions' => $questions,
            'user_id' => $userId,
        ]);
        if (($task['status'] ?? '') === 'success') {
            \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_content', $task);
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '重新生成失败'));
        }
        return $task;
    }

    public static function contentDelete(int $id): void
    {
        $c = GeoContent::findOrEmpty($id);
        if ($c->isEmpty()) return;
        // 级联作废挂在该内容上的待发布记录:不清会留下指向已删内容的僵尸行,
        // 官网定时发布/回执同步还会继续处理它们
        \app\common\model\geo\GeoPublish::where('content_id', (int)$c->id)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error_msg' => '内容已删除,发布已取消', 'update_time' => time()]);
        $c->delete();
    }

    public static function contentExport(array $ids, string $format): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            self::setError('请选择要导出的内容');
            return [];
        }
        $res = \app\common\service\geo\GeoExportService::export($ids, $format);
        return [
            'filename' => $res['filename'],
            'mime' => $res['mime'],
            'base64' => base64_encode($res['content']),
        ];
    }

    // 监测
    public static function monitor(int $userId, int $projectId, string $query, array $engines = [], int $keywordId = 0, int $topicId = 0): array
    {
        $query = mb_substr(trim($query), 0, 500);
        // 未指定场景问题时,把本次提问登记成场景问题并绑定。
        // 否则写出的是 keyword_id=0 的监测记录:GeoInsightLogic::cells() 只统计
        // keyword_id 命中【启用中场景问题】的记录,这类记录会入库成功、任务日志也照常显示
        // "品牌已出现,可见度 xx",但看板与诊断报告永远统计不到 —— 静默丢数据。
        if ($keywordId <= 0 && $query !== '') {
            $kw = GeoKeyword::where('project_id', $projectId)
                ->where('type', '场景问题')->where('value', $query)->findOrEmpty();
            if ($kw->isEmpty()) {
                $kw = GeoKeyword::create([
                    'project_id' => $projectId, 'topic_id' => $topicId,
                    'type' => '场景问题', 'value' => $query, 'status' => 1,
                    'source' => 'monitor', 'create_time' => time(),
                ]);
            }
            $keywordId = (int)$kw->id;
            if ($topicId <= 0) {
                $topicId = (int)$kw->topic_id;
            }
        }
        // 按 问题×引擎 次数计费:预检按预计引擎数,结算按实际成功引擎数
        // 与 handleMonitor 同口径:engineList() 动态判定(中台密钥已配即接入)
        $engines = GeoHelper::normalizeStringList($engines);
        $available = array_column(array_filter(
            \app\common\service\geo\GeoMonitorService::engineList(), fn($e) => !empty($e['available'])
        ), 'key');
        $planned = $engines ? count(array_intersect($engines, $available)) : count($available);
        if ($planned <= 0) {
            throw new \Exception('没有已接入的监测引擎');
        }
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_monitor', $planned);
        \app\common\service\geo\GeoMonitorService::resetUsage();
        $task = GeoTaskService::dispatch($projectId, 'monitor', [
            'query' => $query, 'engines' => $engines,
            'keyword_id' => $keywordId, 'topic_id' => $topicId,
        ]);
        if (($task['status'] ?? '') === 'success') {
            // 占位【模拟数据】不结算;真实引擎按中台 usage 扣,失败不扣
            $ids = array_filter(explode(',', str_replace('monitor:', '', (string)($task['result_ref'] ?? ''))));
            $real = $ids ? GeoMonitor::whereIn('id', $ids)->where('raw_answer', 'not like', '【模拟数据】%')->count() : 0;
            if ($real > 0) {
                \app\common\service\geo\GeoChargeService::settleByUsage(
                    $userId,
                    'geo_monitor',
                    \app\common\service\geo\GeoMonitorService::usage(),
                    (string)($task['id'] ?? '')
                );
            }
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '监测失败'));
        }
        return $task;
    }

    /**
     * 一键诊断:落库 pending cells,立即返回。
     * 不依赖前端停留本页,也不依赖 queue:work;由 geo_monitor_cron 逐 cell 执行。
     *
     * @return array|false
     */
    public static function monitorBatch(int $userId, int $projectId, array $keywordIds = [], array $engines = [])
    {
        if (self::assertOwn($userId, $projectId)->isEmpty()) {
            self::setError('项目不存在或无权限');
            return false;
        }
        // 防重复提交:同项目已有进行中的诊断批次时拒绝再建,否则双击/多标签页/
        // 向导与工作台并发都会堆出重复批次,拖垮定时任务消费(曾积压 33 个批次)。
        // 卡死的旧批次由 GeoMonitorCronService::reconcileBatch 每分钟清算落终态,不会永久锁死
        $runningBatch = GeoTask::where('project_id', $projectId)
            ->where('task_type', 'monitor_batch')
            ->where('status', 'running')
            ->order('id', 'desc')
            ->findOrEmpty();
        if (!$runningBatch->isEmpty()) {
            $ref = json_decode((string)$runningBatch->result_ref, true) ?: [];
            $input = json_decode((string)$runningBatch->input, true) ?: [];
            $done = (int)($ref['completed'] ?? 0);
            $total = (int)($input['total'] ?? 0);
            self::setError("上一轮诊断还在进行中({$done}/{$total}),请等本轮完成后再发起");
            return false;
        }

        $keywordIds = GeoHelper::normalizeIntList($keywordIds);
        $engines = GeoHelper::normalizeStringList($engines);
        $available = array_column(array_filter(
            \app\common\service\geo\GeoMonitorService::engineList(), fn($e) => !empty($e['available'])
        ), 'key');
        $engines = $engines
            ? array_values(array_intersect($engines, $available))
            : $available;
        if (!$engines) {
            self::setError('没有已接入的监测引擎');
            return false;
        }

        $q = GeoKeyword::where('project_id', $projectId)->where('type', '场景问题')->where('status', 1);
        if ($keywordIds) {
            $q->whereIn('id', $keywordIds);
        }
        $questions = $q->field('id,value,topic_id')->select()->toArray();
        if (!$questions) {
            self::setError('请先生成场景问题');
            return false;
        }

        $total = count($questions) * count($engines);
        try {
            \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_monitor', $total);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        return \app\common\service\geo\GeoMonitorCellService::createBatch(
            $userId,
            $projectId,
            $questions,
            $engines,
            '一键诊断'
        );
    }

    /**
     * 一键诊断进度。优先按 batch_task_id 读批次 geo_task 的统计
     * (total 读 input.total,completed/success/failed/skipped 读 result_ref,
     *  终态失败/跳过已由 Job 计入 completed);旧前端只传 since 时自动取该项目
     * 最近一条 monitor_batch;仍找不到批次才退回 since 旧口径。
     */
    public static function monitorProgress(int $userId, int $projectId, int $since = 0, int $batchTaskId = 0): array
    {
        if (self::assertOwn($userId, $projectId)->isEmpty()) {
            self::setError('项目不存在或无权限');
            return [];
        }
        // 旧前端只传 since:用该项目最近批次,否则回写了 result_ref 进度条也永远 0%
        if ($batchTaskId <= 0) {
            $latest = GeoTask::where('project_id', $projectId)
                ->where('task_type', 'monitor_batch')
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$latest->isEmpty()) {
                $batchTaskId = (int)$latest->id;
            }
        }
        if ($batchTaskId > 0) {
            $batch = GeoTask::where('project_id', $projectId)
                ->where('task_type', 'monitor_batch')->findOrEmpty($batchTaskId);
            if (!$batch->isEmpty()) {
                $input = json_decode((string)$batch->input, true) ?: [];
                $ref = json_decode((string)$batch->result_ref, true) ?: [];
                $total = max(0, (int)($input['total'] ?? 0));
                $done = $total > 0 ? min((int)($ref['completed'] ?? 0), $total) : 0;
                $terminal = in_array((string)$batch->status, ['success', 'failed'], true);
                // total=0(入队全失败)或批次已终态 → 视为 finished,避免进度条永久卡住
                $finished = $terminal || ($total > 0 && $done >= $total);
                return [
                    // 带上批次 id:前端刷新后靠它区分「存在批次可恢复」与「从未诊断过」
                    'batch_task_id' => $batchTaskId,
                    'total' => $total,
                    'done' => $done,
                    'success' => (int)($ref['success'] ?? 0),
                    'failed' => (int)($ref['failed'] ?? 0),
                    'skipped' => (int)($ref['skipped'] ?? 0),
                    'percent' => $total > 0 ? (int)floor($done / $total * 100) : ($finished ? 100 : 0),
                    'finished' => $finished,
                ];
            }
        }
        $engineCount = max(1, count(array_filter(
            \app\common\service\geo\GeoMonitorService::engineList(), fn($e) => !empty($e['available'])
        )));
        $qIds = GeoKeyword::where('project_id', $projectId)
            ->where('type', '场景问题')->where('status', 1)->column('id');
        $total = count($qIds) * $engineCount;

        $done = 0;
        if ($qIds) {
            $mq = GeoMonitor::where('project_id', $projectId)->whereIn('keyword_id', $qIds);
            if ($since > 0) {
                $mq->where('create_time', '>=', $since);
            }
            $done = count($mq->group('keyword_id,engine')->column('max(id)'));
        }
        return [
            'total' => $total,
            'done' => min($done, $total),
            'percent' => $total > 0 ? (int)floor(min($done, $total) / $total * 100) : 0,
            'finished' => $total > 0 && $done >= $total,
        ];
    }

    /** 监测引擎清单 */
    public static function monitorEngines(): array
    {
        return \app\common\service\geo\GeoMonitorService::engineList();
    }

    public static function monitorList(int $projectId): array
    {
        return GeoMonitor::where('project_id', $projectId)->order('id desc')->select()->toArray();
    }

    // 建议
    public static function genSuggestion(int $userId, int $projectId): array
    {
        \app\common\service\geo\GeoChargeService::precheck($userId, 'geo_suggestion');
        \app\common\service\geo\GeoAiService::resetUsage();
        $task = GeoTaskService::dispatch($projectId, 'gen_suggestion', []);
        $task['suggestions'] = json_decode((string)($task['result_ref'] ?? ''), true) ?: [];
        if (($task['status'] ?? '') === 'success') {
            \app\common\service\geo\GeoChargeService::settleIfSucceeded($userId, 'geo_suggestion', $task);
        } else {
            throw new \Exception(GeoHelper::taskErrorMessage($task, '优化建议生成失败'));
        }
        return $task;
    }

    // ---------- 发布/分发 ----------

    /** 媒体库(带筛选) */
    public static function mediaList(array $filter): array
    {
        $q = \app\common\model\geo\GeoMedia::newQuery();
        // 媒体代发已下线:媒体库只展示可用发布渠道——AI 手机、授权直发平台、免费媒体;
        // 历史收费代发媒体(price>0 且无授权直发通道)不再展示
        $q->where(function ($query) {
            $query->where('type', 'ai_phone')
                ->whereOr('price', '<=', 0)
                ->whereOr('platform_code', 'in', ['wechat_oa', 'cnblogs', 'baijiahao', 'yuque']);
        });
        if (!empty($filter['category'])) $q->where('category', $filter['category']);
        if (!empty($filter['type'])) $q->where('type', $filter['type']);
        if (!empty($filter['can_geo_rank'])) $q->where('can_geo_rank', 1);
        // 投稿类型:content_form 存的是 'article' / 'video' / 'article,video',用 FIND_IN_SET 精确匹配
        if (!empty($filter['content_form'])) {
            $q->whereRaw('FIND_IN_SET(?, `content_form`)', [(string)$filter['content_form']]);
        }
        if (isset($filter['status']) && $filter['status'] !== '') $q->where('status', (int)$filter['status']);
        if (!empty($filter['keyword'])) $q->where('name', 'like', '%' . $filter['keyword'] . '%');
        // 价格排序优先,否则按权重(sort)
        if (($filter['price_sort'] ?? '') === 'asc') $q->order('price asc,sort desc');
        elseif (($filter['price_sort'] ?? '') === 'desc') $q->order('price desc,sort desc');
        else $q->order('sort desc,id asc');
        return $q->select()->toArray();
    }

    /** 媒体库筛选项(供前端渲染) */
    public static function mediaFilters(): array
    {
        return [
            'category' => \app\common\model\geo\GeoMedia::distinct(true)->column('category'),
            'type' => [
                ['value' => 'ai_phone', 'label' => 'AI手机发布'],
                ['value' => 'b2b', 'label' => 'B2B行业站'],
                ['value' => 'portal', 'label' => '新闻门户'],
                ['value' => 'blog', 'label' => '博客/资讯'],
                ['value' => 'media_v', 'label' => '自媒体大V'],
            ],
            'content_form' => [
                ['value' => 'article', 'label' => '图文'],
                ['value' => 'video', 'label' => '视频'],
            ],
        ];
    }

    /**
     * 为一篇内容创建到多媒体的发布任务。
     * @param string $mediaType 投稿类型 article=图文 video=视频
     * @param int $videoId 视频投稿时关联的 geo_video_task id(可选)
     *
     * 路由规则:
     * - AI手机媒体(type=ai_phone,小红书/抖音/快手/视频号)不走本接口,前端直连设备发布通道;
     * - 媒体已关联授权账号(platform_code 命中当前用户已开启的授权)→ 用商家自己账号
     *   经平台官方 API 直发,0 费用;
     * - 【媒体代发已下线】无授权直发通道的媒体不再支持投递,直接跳过。
     */
    public static function publishCreate(int $userId, int $projectId, int $contentId, array $mediaIds, string $mediaType = 'article', int $videoId = 0)
    {
        // 扣费入口,与 projectCreate 同口径做到期成员硬拦截
        try { \app\common\service\TeamMemberService::assertActive($userId); }
        catch (\Throwable $e) { self::setError($e->getMessage()); return false; }
        $p = self::assertOwn($userId, $projectId);
        if ($p->isEmpty()) { self::setError('项目不存在或无权限'); return false; }
        $mediaIds = GeoHelper::normalizeIntList($mediaIds);
        // 内容归属校验:必须属于该项目(防止用他人 content_id 跨租户读标题/挂错台账)
        $content = GeoContent::findOrEmpty($contentId);
        if ($content->isEmpty() || (int)$content->project_id !== $projectId) {
            self::setError('文章不存在或不属于该项目');
            return false;
        }
        if (!$mediaIds) { self::setError('请选择要投递的媒体'); return false; }
        $mediaType = $mediaType === 'video' ? 'video' : 'article';
        // 视频投稿归属校验
        if ($mediaType === 'video' && $videoId > 0) {
            $vt = \app\common\model\geo\GeoVideoTask::findOrEmpty($videoId);
            if ($vt->isEmpty() || (int)$vt->project_id !== $projectId) { self::setError('视频不存在或不属于该项目'); return false; }
        }

        // 拆分:授权账号直发 / 其余渠道;并按投稿类型过滤不支持的媒体。
        // 【媒体代发已下线】无授权直发通道的媒体不再支持投递。
        $authMap = GeoAuthLogic::enabledMap($userId);
        $authPublish = GeoAuthLogic::publishablePlatforms();
        $authMedias = [];
        $needAuth = []; // 已打通直发但还没授权的媒体名,拦下来引导去授权
        foreach (array_unique(array_map('intval', $mediaIds)) as $mid) {
            $media = \app\common\model\geo\GeoMedia::findOrEmpty($mid);
            if ($media->isEmpty() || (int)$media->status !== 1) continue;
            if ((string)$media->type === 'ai_phone') continue; // AI手机媒体走设备发布通道
            $forms = array_filter(explode(',', (string)($media->content_form ?: 'article')));
            if (!in_array($mediaType, $forms)) continue; // 该媒体不支持所选类型
            $pc = (string)$media->platform_code;
            // 公众号/博客园/百家号/语雀:未授权直接拦,引导去授权(媒体代发已下线)
            if ($pc !== '' && isset($authPublish[$pc]) && !isset($authMap[$pc])) {
                $needAuth[] = (string)$media->name;
                continue;
            }
            // 授权直发目前仅图文通道(视频官方通道未打通)
            if ($mediaType === 'article' && $pc !== '' && isset($authMap[$pc])) {
                $authMedias[] = ['media' => $media, 'account' => $authMap[$pc]];
            }
            // 其余媒体:媒体代发已下线,不可投递,直接跳过
        }
        if ($needAuth) {
            self::setError(implode('、', $needAuth) . ' 需要先在「设置-授权账号」完成授权,才能用你自己的账号发布');
            return false;
        }
        if (!$authMedias) { self::setError('所选媒体不可投递:媒体代发已下线,请使用已授权的直发渠道或 AI 手机发布'); return false; }

        $tasks = [];
        // 授权账号直发:0 费用,立即调平台官方 API
        foreach ($authMedias as $am) {
            $media = $am['media'];
            $accRow = \app\common\model\geo\GeoAuthAccount::findOrEmpty((int)$am['account']['id']);
            $rec = \app\common\model\geo\GeoPublish::create([
                'project_id' => $projectId, 'content_id' => $contentId,
                'media_id' => (int)$media->id, 'media_name' => (string)$media->name,
                'title' => (string)$content->title, 'status' => 'pending',
                'mode' => 'auth', 'channel' => 'auth', 'cost' => 0, 'user_id' => $userId,
                'media_type' => $mediaType, 'video_id' => $videoId,
                'account' => (string)$accRow->name,
                'create_time' => time(), 'update_time' => time(),
            ]);
            try {
                $res = \app\common\service\geo\GeoAuthPublishService::publish($accRow, $content);
                $rec->status = $res['status'];
                $rec->published_url = (string)($res['url'] ?? '');
                $rec->error_msg = (string)($res['note'] ?? '');
                if ($res['status'] === 'published') {
                    $rec->publish_time = time();
                    // 回写内容发布态:否则列表的「未发布」筛选与标记对直发渠道恒错
                    if ((int)$content->status !== 1) { $content->status = 1; $content->save(); }
                }
            } catch (\Throwable $e) {
                $rec->status = 'failed';
                $rec->error_msg = mb_substr($e->getMessage(), 0, 400);
            }
            $rec->update_time = time();
            $rec->save();
            $tasks[] = $rec->toArray();
        }
        if (!$tasks) { self::setError('没有可投递的媒体(可能未开通)'); return false; }
        return ['tasks' => $tasks, 'count' => count($tasks), 'cost' => 0,
            'auth_count' => count($authMedias), 'paid_count' => 0];
    }

    /** 发布记录 */
    public static function publishList(int $projectId): array
    {
        $list = \app\common\model\geo\GeoPublish::where('project_id', $projectId)->order('id desc')->select()->toArray();
        $total = 0;
        foreach ($list as $r) { if ($r['status'] === 'published') $total += (float)$r['cost']; }
        // 投稿效果汇总:未接 TikHub 时 count=0,前端据此隐藏整块而不是显示一排 0
        return [
            'list' => $list,
            'published_cost' => $total,
            'stats_summary' => \app\common\service\geo\GeoPublishStatsService::projectSummary($projectId),
            'stats_enabled' => \app\common\service\geo\GeoTikhubService::enabled(),
        ];
    }

    /** 回填已发布链接 */
    public static function publishConfirm(int $userId, int $publishId, string $url): bool
    {
        // URL 必填且须为 http(s):空 url 一旦标记 published,退费资格即刻丧失且不可逆
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            self::setError('请填写有效的文章链接(http/https)');
            return false;
        }
        $p = \app\common\model\geo\GeoPublish::findOrEmpty($publishId);
        if ($p->isEmpty()) { self::setError('记录不存在'); return false; }
        if (self::assertOwn($userId, (int)$p->project_id)->isEmpty()) { self::setError('无权操作该发布记录'); return false; }
        // 仅 pending 可回填(与 GeoPublishService::confirm 的 CAS 条件一致):
        // 提前给出友好提示,否则 confirm 返回 false 且无 error,前端会看到「系统错误」
        $status = (string)$p->status;
        if ($status === 'published') { self::setError('该记录已是已发布状态,无需回填'); return false; }
        if ($status !== 'pending') {
            self::setError('失败记录不可回填链接;如文章实际已发布成功,请在文章列表用「自有发布登记」录入');
            return false;
        }
        if (!\app\common\service\geo\GeoPublishService::confirm($publishId, $url)) {
            self::setError('回填失败,记录状态已变化,请刷新后重试');
            return false;
        }
        return true;
    }

    /**
     * 自有发布登记(对标微盟「登记」):把在外部渠道已发布的文章登记进发布记录,
     * 用于引用分析溯源与发布台账。不扣费。
     */
    public static function publishRegister(int $userId, int $projectId, array $p)
    {
        $proj = self::assertOwn($userId, $projectId);
        if ($proj->isEmpty()) { self::setError('项目不存在或无权限'); return false; }
        $content = GeoContent::findOrEmpty((int)($p['content_id'] ?? 0));
        if ($content->isEmpty()) { self::setError('文章不存在'); return false; }
        if ((int)$content->project_id !== $projectId) { self::setError('文章不属于该项目'); return false; }
        $url = trim((string)($p['url'] ?? ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            self::setError('请填写有效的文章链接(http/https)');
            return false;
        }
        $rec = \app\common\model\geo\GeoPublish::create([
            'project_id' => $projectId,
            'content_id' => (int)$content->id,
            'media_id' => 0,
            'media_name' => (string)($p['account'] ?? ''),
            'title' => (string)$content->title,
            'status' => 'published',
            'mode' => 'register',
            'cost' => 0,
            'published_url' => $url,
            'channel_type' => (string)($p['channel_type'] ?? 'portal'),
            'site_name' => (string)($p['site_name'] ?? ''),
            'account' => (string)($p['account'] ?? ''),
            'publish_time' => !empty($p['publish_time']) ? (strtotime((string)$p['publish_time']) ?: time()) : time(),
            'create_time' => time(), 'update_time' => time(),
        ]);
        // 文章标记为已发布
        $content->status = 1;
        $content->update_time = time();
        $content->save();
        return ['id' => (int)$rec->id];
    }

    /**
     * AI手机投稿登记:小红书/抖音/快手/视频号走 AI 真机发布(矩阵「手动发布」通道),
     * 发布动作在设备端异步执行,这里只把它挂进媒体库投稿台账,让「发布记录」能统一看到。
     * 不扣费(AI手机媒体 price=0),status=pending,链接由用户在设备发完后回填。
     */
    public static function publishPhoneRegister(int $userId, int $projectId, array $p)
    {
        $proj = self::assertOwn($userId, $projectId);
        if ($proj->isEmpty()) { self::setError('项目不存在或无权限'); return false; }
        $media = \app\common\model\geo\GeoMedia::findOrEmpty((int)($p['media_id'] ?? 0));
        if ($media->isEmpty() || (string)$media->type !== 'ai_phone') { self::setError('媒体不存在或不是AI手机媒体'); return false; }
        $mediaType = ($p['media_type'] ?? '') === 'video' ? 'video' : 'article';

        // 图文投稿关联文章、视频投稿关联短视频任务,两者都要校验归属
        $contentId = (int)($p['content_id'] ?? 0);
        $videoId = (int)($p['video_id'] ?? 0);
        $videoUrl = trim((string)($p['video_url'] ?? ''));
        $title = '';
        if ($mediaType === 'video') {
            // 新口径:直传视频素材 URL(本地上传/素材库/创作库);
            // 旧口径:video_id 关联已下线的 GEO 短视频任务,仅为历史兼容保留
            if ($videoUrl !== '') {
                if (!preg_match('#^https?://#i', $videoUrl)) { self::setError('视频素材地址无效'); return false; }
            } else {
                $vt = \app\common\model\geo\GeoVideoTask::findOrEmpty($videoId);
                if ($vt->isEmpty() || (int)$vt->project_id !== $projectId) { self::setError('请先选择视频素材'); return false; }
                $contentId = (int)$vt->content_id;
                $title = (string)$vt->title;
            }
        }
        if ($mediaType === 'video' && $videoUrl !== '' && $contentId <= 0) {
            // 直传视频素材可以不关联文章(标题从入参取,文案由前端设备链路自带)
            $title = trim((string)($p['title'] ?? '')) ?: '视频投稿';
        } else {
            $content = GeoContent::findOrEmpty($contentId);
            if ($content->isEmpty() || (int)$content->project_id !== $projectId) { self::setError('文章不存在或不属于该项目'); return false; }
            if ($title === '') $title = (string)$content->title;
        }

        $rec = \app\common\model\geo\GeoPublish::create([
            'project_id' => $projectId,
            'content_id' => $contentId,
            'media_id' => (int)$media->id,
            'media_name' => (string)$media->name,
            'title' => $title,
            'status' => 'pending',
            'mode' => 'phone',
            'channel' => 'phone',
            'cost' => 0,
            'user_id' => $userId,
            'account' => (string)($p['account'] ?? ''),
            'media_type' => $mediaType,
            'video_id' => $mediaType === 'video' ? $videoId : 0,
            // 存 sv_publish_setting.id,便于按设备发布结果回写本条状态
            'provider_order' => (string)($p['publish_setting_id'] ?? ''),
            'error_msg' => '',
            'create_time' => time(), 'update_time' => time(),
        ]);

        // 真正下发设备任务。
        // 旧逻辑到上面建完记录就 return,error_msg 却写死"已下发 AI 手机发布任务",
        // 实际后端一个任务都没发 —— 内容永远到不了真机。
        // 前端已自行建好发布计划时(传 publish_setting_id)沿用它,只做台账关联。
        if ((int)($p['publish_setting_id'] ?? 0) <= 0) {
            try {
                \app\common\service\geo\GeoPhonePublishService::dispatch(
                    $userId, $rec,
                    (array)($p['accounts'] ?? []),
                    [
                        'images' => (array)($p['images'] ?? []),
                        'video_url' => $videoUrl,
                        'video_cover' => trim((string)($p['video_cover'] ?? '')),
                        'publish_date' => (string)($p['publish_date'] ?? ''),
                        'times' => (array)($p['times'] ?? []),
                    ]
                );
            } catch (\Throwable $e) {
                // 下发失败必须让用户看见,并且不留一条永远 pending 的假记录
                $rec->delete();
                self::setError($e->getMessage());
                return false;
            }
        }
        return ['id' => (int)$rec->id];
    }

    /** 轮询文章封面图(生成侧异步提交,这里取回并回填) */
    public static function contentCover(int $userId, int $contentId): array
    {
        $c = GeoContent::findOrEmpty($contentId);
        if ($c->isEmpty() || !self::userOwnsContent($userId, $contentId)) {
            self::setError('文章不存在或无权限');
            return [];
        }
        return \app\common\service\geo\GeoCoverService::fetch($c) + ['content_id' => $contentId];
    }

    /** 手动重生成封面图(首次失败或用户想换一张) */
    public static function contentCoverRetry(int $userId, int $contentId): array
    {
        $c = GeoContent::findOrEmpty($contentId);
        if ($c->isEmpty() || !self::userOwnsContent($userId, $contentId)) {
            self::setError('文章不存在或无权限');
            return [];
        }
        if (!\app\common\service\geo\GeoCoverService::enabled()) {
            self::setError('系统未配置文生图通道,无法生成封面图');
            return [];
        }
        $prevUrl = (string)$c->cover_url;
        $prevStatus = (string)$c->cover_status;
        $prevTask = (string)$c->cover_task_id;
        try {
            $c->cover_url = '';
            $c->cover_status = '';
            $c->cover_task_id = '';
            $c->save();
            \app\common\service\geo\GeoCoverService::submit(
                $c,
                GeoProject::findOrEmpty((int)$c->project_id),
                $userId
            );
        } catch (\Throwable $e) {
            // 提交失败还原原封面,避免用户原图被清空
            $c->cover_url = $prevUrl;
            $c->cover_status = $prevStatus;
            $c->cover_task_id = $prevTask;
            $c->save();
            self::setError($e->getMessage());
            return [];
        }
        return ['status' => (string)$c->cover_status, 'content_id' => $contentId];
    }

    /** 某 AI 手机媒体下,当前用户可用的发布账号(投稿弹窗选号用) */
    public static function phoneAccounts(int $userId, int $mediaId): array
    {
        $media = \app\common\model\geo\GeoMedia::findOrEmpty($mediaId);
        if ($media->isEmpty() || (string)$media->type !== 'ai_phone') {
            self::setError('媒体不存在或不是AI手机媒体');
            return [];
        }
        $code = \app\common\service\geo\GeoPhonePublishService::platformCodeOf($media);
        if ($code === '') {
            self::setError('该媒体未配置 AI 手机平台标识');
            return [];
        }
        $meta = \app\common\service\geo\GeoPhonePublishService::PLATFORMS[$code];
        return [
            'platform' => $code,
            'label' => $meta['label'],
            'forms' => $meta['forms'],
            'accounts' => \app\common\service\geo\GeoPhonePublishService::accounts($userId, $code),
        ];
    }

    /** 删除发布记录(未发布则退费给原扣费人) */
    public static function publishDelete(int $userId, int $publishId): bool
    {
        // 事务 + 行锁:并发对同一条记录重复调删除时,后到的请求在锁上等待,
        // 提交后重查已软删 → 查不到直接返回,杜绝双重退费套利
        return \think\facade\Db::transaction(function () use ($userId, $publishId) {
            $p = \app\common\model\geo\GeoPublish::where('id', $publishId)->lock(true)->findOrEmpty();
            if ($p->isEmpty()) return true;
            // 归属校验:发布记录所属项目必须归当前用户,防止越权删除+退费
            if (self::assertOwn($userId, (int)$p->project_id)->isEmpty()) {
                self::setError('无权删除该发布记录');
                return false;
            }
            \app\common\service\geo\GeoPublishService::refundIfUnpublished($p);
            $p->delete();
            return true;
        });
    }

    // ---------- AI官网SEO:站点 ----------
    public static function siteList(int $userId): array
    {
        $list = \app\common\model\geo\GeoSite::where('user_id', $userId)->order('id desc')->select()->toArray();
        // api_key 存的是站点凭据(公众号 AppSecret/WordPress 应用密码/webhook Token),永不明文回传
        foreach ($list as &$row) {
            $row['has_key'] = (string)($row['api_key'] ?? '') !== '';
            unset($row['api_key']);
        }
        unset($row);
        return $list;
    }

    public static function siteSave(int $userId, array $p)
    {
        [$uid, $teamId] = self::scope($userId);
        if (empty(trim((string)($p['name'] ?? '')))) { self::setError('请填写站点名称'); return false; }
        // SSRF 防护:接口地址仅允许 http(s) 公网地址(manual 模式无需接口)
        $endpoint = (string)($p['api_endpoint'] ?? '');
        if ($endpoint !== '' && ($p['type'] ?? '') !== 'manual') {
            try { \app\common\service\geo\GeoSiteService::assertSafeUrl($endpoint); }
            catch (\Throwable $e) { self::setError($e->getMessage()); return false; }
        }
        $data = [
            'name' => trim((string)$p['name']),
            'url' => (string)($p['url'] ?? ''),
            'type' => in_array($p['type'] ?? '', ['wordpress', 'webhook', 'manual', 'wechat_oa']) ? $p['type'] : 'wordpress',
            'api_endpoint' => (string)($p['api_endpoint'] ?? ''),
            'api_user' => (string)($p['api_user'] ?? ''),
            'status' => isset($p['status']) ? (int)$p['status'] : 1,
            'update_time' => time(),
        ];
        // api_key 仅在传入非空时更新(避免编辑时清空);敏感凭据经 AES-GCM 加密后入库
        try {
            $plainKey = (string)($p['api_key'] ?? '');
            if ($plainKey !== '') {
                $data['api_key'] = GeoCredentialService::encrypt($plainKey);
            } elseif (empty($p['id'])) {
                $data['api_key'] = '';
            }
        } catch (\Throwable $e) {
            // 密钥未配置/长度错误:宁可保存失败,绝不落明文
            self::setError($e->getMessage());
            return false;
        }
        if (!empty($p['id'])) {
            $siteId = (int)$p['id'];
            if (!self::userOwnsSite($userId, $siteId)) {
                self::setError('无权操作该站点');
                return false;
            }
            $site = \app\common\model\geo\GeoSite::findOrEmpty($siteId);
            if ($site->isEmpty()) { self::setError('站点不存在'); return false; }
            $site->save($data);
            return ['id' => $siteId];
        }
        $data += ['user_id' => $uid, 'team_id' => $teamId, 'create_time' => time()];
        $site = \app\common\model\geo\GeoSite::create($data);
        return ['id' => (int)$site->id];
    }

    public static function siteCheck(int $siteId): array
    {
        $site = \app\common\model\geo\GeoSite::findOrEmpty($siteId);
        if ($site->isEmpty()) return ['ok' => false, 'msg' => '站点不存在'];
        $res = \app\common\service\geo\GeoSiteService::checkSite($site);
        $site->last_check = $res['msg'];
        $site->save();
        return $res;
    }

    public static function siteDelete(int $siteId): void
    {
        $s = \app\common\model\geo\GeoSite::findOrEmpty($siteId);
        if ($s->isEmpty()) {
            return;
        }
        // 停掉关联定时任务,避免站点软删后 cron 每小时刷「站点不存在」
        \app\common\model\geo\GeoSiteTask::where('site_id', $siteId)->update(['status' => 0, 'update_time' => time()]);
        $s->delete();
    }

    // ---------- AI官网SEO:定时发布任务 ----------
    public static function siteTaskList(int $userId, int $projectId): array
    {
        $q = \app\common\model\geo\GeoSiteTask::order('id desc');
        if ($projectId > 0) $q->where('project_id', $projectId); else $q->where('user_id', $userId);
        $list = $q->select()->toArray();
        $siteNames = \app\common\model\geo\GeoSite::column('name', 'id');
        foreach ($list as &$t) { $t['site_name'] = $siteNames[$t['site_id']] ?? '(站点已删)'; }
        return $list;
    }

    public static function siteTaskCreate(int $userId, array $p)
    {
        if (empty($p['site_id'])) { self::setError('请选择站点'); return false; }
        if (empty($p['project_id'])) { self::setError('缺少项目'); return false; }
        $task = \app\common\model\geo\GeoSiteTask::create([
            'user_id' => $userId,
            'site_id' => (int)$p['site_id'],
            'project_id' => (int)$p['project_id'],
            'name' => (string)(($p['name'] ?? '') ?: '官网定时发布'),
            'daily_count' => min(50, max(1, (int)($p['daily_count'] ?? 1))),
            'status' => 1,
            'create_time' => time(), 'update_time' => time(),
        ]);
        return ['id' => (int)$task->id];
    }

    public static function siteTaskToggle(int $taskId): void
    {
        $t = \app\common\model\geo\GeoSiteTask::findOrEmpty($taskId);
        if (!$t->isEmpty()) { $t->status = $t->status ? 0 : 1; $t->save(); }
    }

    public static function siteTaskDelete(int $taskId): void
    {
        $t = \app\common\model\geo\GeoSiteTask::findOrEmpty($taskId);
        if (!$t->isEmpty()) $t->delete();
    }

    /** 立即发布一篇(测试用) */
    public static function siteTaskRunOnce(int $taskId)
    {
        $task = \app\common\model\geo\GeoSiteTask::findOrEmpty($taskId);
        if ($task->isEmpty()) { self::setError('任务不存在'); return false; }
        // 与 cron(runDue)同口径的每日配额:立即发布不绕过配额,发完计入 today_count,
        // 否则连点可无限刷文且把定时任务的配额账算乱
        $today = date('Y-m-d');
        if ((string)$task->today_date !== $today) {
            $task->today_date = $today;
            $task->today_count = 0;
        }
        if (max(0, (int)$task->daily_count - (int)$task->today_count) <= 0) {
            self::setError('今日发布配额已用完,明天再试或调大每日篇数');
            return false;
        }
        $summary = ['tasks' => 1, 'published' => 0, 'failed' => 0];
        // publishBatch 内部已累加 published_count,这里不再重复加
        $done = \app\common\service\geo\GeoSiteService::publishBatch($task, 1, $summary);
        if ($done > 0) { $task->today_count = (int)$task->today_count + $done; }
        $task->last_run = time();
        $task->save();
        if ($summary['failed'] > 0 && $done === 0) { self::setError('发布失败,请检查站点配置'); return false; }
        if ($done === 0) { self::setError('没有可发布的新内容(该项目内容都已发过或为空)'); return false; }
        return ['published' => $done];
    }

    // 任务
    public static function task(int $taskId): array
    {
        $t = GeoTask::findOrEmpty($taskId);
        if ($t->isEmpty()) return [];
        $data = $t->toArray();
        $data['logs'] = json_decode((string)$t->logs, true) ?: [];
        $data['input'] = json_decode((string)$t->input, true) ?: [];
        return $data;
    }

    public static function taskList(int $projectId): array
    {
        $list = GeoTask::where('project_id', $projectId)->order('id desc')->limit(50)
            ->field('id,task_type,status,result_ref,create_time,update_time')->select()->toArray();
        return $list;
    }
}
