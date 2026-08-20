<?php

namespace app\api\controller\geo;

use app\api\controller\BaseApiController;
use app\api\logic\geo\GeoLogic;
use app\api\logic\geo\GeoTopicLogic;
use app\api\logic\geo\GeoInsightLogic;
use app\api\logic\geo\GeoAuthLogic;

/**
 * GEO 中心 - 生成式引擎优化工作台
 * 路由前缀 /api/geo.geo/*
 */
class GeoController extends BaseApiController
{
    /** cronTick 为测试触发接口:无登录态,凭 env 密钥校验(见方法内注释) */
    public array $notNeedLogin = ['cronTick'];

    /** project 级归属校验:非本人项目一律拒绝(防越权/IDOR) */
    private function ownProject(int $projectId): bool
    {
        return GeoLogic::userOwnsProject($this->userId, $projectId);
    }

    /**
     * 测试辅助:外部调用立即触发一轮监测 cell 消费(等价于 geo_monitor_cron 跑一轮),
     * 免去等每分钟调度。GET /api/geo.geo/cronTick?key=xxx
     * 防护:env [GEO] CRON_TEST_KEY 未配置时接口关闭;key 用 hash_equals 恒时比较。
     * 幂等安全:runDue 自带行锁抢占/单轮上限/时间预算,重复调用无副作用。
     */
    public function cronTick()
    {
        $key = (string)env('geo.cron_test_key', '');
        if ($key === '' || !hash_equals($key, (string)$this->request->get('key'))) {
            return $this->fail('接口未开放');
        }
        $s = \app\common\service\geo\GeoMonitorCronService::runDue();
        return $this->data($s);
    }

    // ---------- 通用 ----------
    public function models()
    {
        return $this->data(GeoLogic::modelOptions());
    }

    /** GEO 各 AI 功能的算力单价(演示模式全为 0,前端据此展示"预计消耗") */
    public function chargeConfig()
    {
        return $this->data([
            'enabled' => \app\common\service\geo\GeoChargeService::enabled(), // false = 演示模式(未配 AI Key),前端显示【模拟数据】标注
            'list' => \app\common\service\geo\GeoChargeService::priceList(),
        ]);
    }

    /**
     * AI 匹配品牌信息(向导第一步):输入品牌/公司名 → 回填 行业+别名+简介。
     * 建品牌前即可调用(不做 project 归属校验);model 可显式指定中台模型(缺省由中台兜底)。
     */
    public function aiMatchBrand()
    {
        $res = GeoLogic::matchBrand(
            $this->userId,
            (string)$this->request->post('name'),
            (string)$this->request->post('model', '')
        );
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->data($res);
    }

    /** 「AI匹配品牌信息」的模型列表(中台化后为空)+ 默认模型 */
    public function matchModels()
    {
        return $this->data(GeoLogic::matchModelOptions());
    }

    // ---------- 项目 ----------
    public function projects()
    {
        return $this->data(GeoLogic::projectList($this->userId));
    }

    public function projectDetail()
    {
        return $this->data(GeoLogic::projectDetail($this->userId, (int)$this->request->get('id')));
    }

    public function projectCreate()
    {
        $res = GeoLogic::projectCreate($this->userId, $this->request->post());
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('创建成功', $res);
    }

    public function projectUpdate()
    {
        $res = GeoLogic::projectUpdate($this->userId, $this->request->post());
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('保存成功', []);
    }

    public function projectDelete()
    {
        GeoLogic::projectDelete($this->userId, (int)$this->request->post('id'));
        return $this->success('已删除', []);
    }

    // ---------- 知识 ----------
    public function knowledge()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::knowledgeList($pid));
    }

    public function knowledgeImport()
    {
        // 统一入口:文本或URL文本(MVP直接接收文本)
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::knowledgeImport($this->userId, $pid, $this->request->post());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已提交解析', $res);
    }

    /** 知识库文档导入(二期):前端先走 /upload/file 拿 URL,这里解析 PDF/Word 后入库 */
    public function knowledgeImportFile()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $fileUrl = (string)$this->request->post('file_url');
        $name = (string)$this->request->post('name', '');
        if ($fileUrl === '') return $this->fail('缺少文件地址');
        try {
            $ext = strtolower((string)$this->request->post('ext', ''));
            $text = \app\common\service\geo\GeoFetchService::parseFileText($fileUrl, $ext);
            $res = GeoLogic::knowledgeImport($this->userId, $pid, [
                'text' => $text,
                'source' => $name !== '' ? $name : '文档导入',
                'source_type' => $ext === 'pdf' ? 'pdf' : 'word',
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已提交解析', $res);
    }

    /** 知识库网址导入(二期):抓取官网页面正文后入库 */
    public function knowledgeImportUrl()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $url = trim((string)$this->request->post('url'));
        if ($url === '') return $this->fail('请填写要抓取的网址');
        try {
            $text = \app\common\service\geo\GeoFetchService::fetchUrlText($url);
            $res = GeoLogic::knowledgeImport($this->userId, $pid, [
                'text' => $text,
                'source' => $url,
                'source_type' => 'url',
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已提交解析', $res);
    }

    public function knowledgeSave()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsKnowledge($this->userId, $id)) return $this->fail('无权操作该知识');
        try {
            $res = GeoLogic::knowledgeSave($id, $this->request->post());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已保存', $res);
    }

    public function knowledgeDelete()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsKnowledge($this->userId, $id)) return $this->fail('无权操作该知识');
        GeoLogic::knowledgeDelete($id);
        return $this->success('已删除');
    }

    // ---------- 关键词 ----------
    public function analyze()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::analyze($this->userId, $pid);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已开始分析', $res);
    }

    public function keywords()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::keywordList($pid));
    }

    // ---------- 内容 ----------
    public function genContent()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::genContent(
                $this->userId,
                $pid,
                \app\common\service\geo\GeoHelper::normalizeStringList($this->request->post('content_types', []))
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已开始生成', $res);
    }

    public function contents()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::contentList($pid, $this->request->get()));
    }

    public function contentUpdate()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsContent($this->userId, $id)) return $this->fail('无权操作该内容');
        GeoLogic::contentUpdate($id, $this->request->post());
        return $this->success('已保存', []);
    }

    public function contentRegenerate()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsContent($this->userId, $id)) return $this->fail('无权操作该内容');
        try {
            $res = GeoLogic::contentRegenerate($this->userId, $id);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已重新生成', $res);
    }

    public function contentDelete()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsContent($this->userId, $id)) return $this->fail('无权操作该内容');
        GeoLogic::contentDelete($id);
        return $this->success('已删除', []);
    }

    public function contentExport()
    {
        $ids = \app\common\service\geo\GeoHelper::normalizeIntList($this->request->post('ids', []));
        foreach ($ids as $cid) {
            if (!GeoLogic::userOwnsContent($this->userId, $cid)) return $this->fail('包含无权导出的内容');
        }
        $res = GeoLogic::contentExport($ids, (string)$this->request->post('format', 'md'));
        if (!$res) return $this->fail(GeoLogic::getError());
        return $this->data($res);
    }

    // ---------- 监测 ----------
    public function monitor()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::monitor(
                $this->userId,
                $pid,
                (string)$this->request->post('query'),
                \app\common\service\geo\GeoHelper::normalizeStringList($this->request->post('engines', [])),
                (int)$this->request->post('keyword_id', 0),
                (int)$this->request->post('topic_id', 0)
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已开始监测', $res);
    }

    /** 一键诊断:落库 pending cells,立即返回,前端轮询 monitorProgress,由 geo_monitor_cron 执行 */
    public function monitorBatch()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoLogic::monitorBatch(
            $this->userId, $pid,
            \app\common\service\geo\GeoHelper::normalizeIntList($this->request->post('keyword_ids', [])),
            \app\common\service\geo\GeoHelper::normalizeStringList($this->request->post('engines', []))
        );
        if ($res === false) return $this->fail(GeoLogic::getError());
        $msg = !empty($res['partial']) ? (string)($res['warning'] ?? '诊断已部分提交') : '已提交诊断';
        return $this->success($msg, $res);
    }

    /** 一键诊断进度(优先按 batch_task_id 读批次统计,否则退回 since 旧口径) */
    public function monitorProgress()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoLogic::monitorProgress(
            $this->userId,
            $pid,
            (int)$this->request->get('since', 0),
            (int)$this->request->get('batch_task_id', 0)
        );
        if (!$res) return $this->fail(GeoLogic::getError());
        return $this->data($res);
    }

    public function monitorEngines()
    {
        return $this->data(GeoLogic::monitorEngines());
    }

    public function monitorList()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::monitorList($pid));
    }

    // ---------- 建议 ----------
    public function suggestions()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::genSuggestion($this->userId, $pid);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已生成建议', $res);
    }

    // ---------- 发布/分发 ----------
    public function media()
    {
        return $this->data(GeoLogic::mediaList($this->request->get()));
    }

    public function mediaFilters()
    {
        return $this->data(GeoLogic::mediaFilters());
    }

    public function publishCreate()
    {
        $res = GeoLogic::publishCreate(
            $this->userId,
            (int)$this->request->post('project_id'),
            (int)$this->request->post('content_id'),
            \app\common\service\geo\GeoHelper::normalizeIntList($this->request->post('media_ids', [])),
            (string)$this->request->post('media_type', 'article'),
            (int)$this->request->post('video_id', 0)
        );
        if ($res === false) return $this->fail(GeoLogic::getError());
        $msg = !empty($res['partial']) ? (string)($res['warning'] ?? '投递已部分完成') : '已创建投递任务';
        return $this->success($msg, $res);
    }

    public function publishList()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::publishList($pid));
    }

    public function publishConfirm()
    {
        $ok = GeoLogic::publishConfirm($this->userId, (int)$this->request->post('id'), (string)$this->request->post('url'));
        if (!$ok) return $this->fail(GeoLogic::getError());
        return $this->success('已标记为已发布', []);
    }

    public function publishDelete()
    {
        $ok = GeoLogic::publishDelete($this->userId, (int)$this->request->post('id'));
        if (!$ok) return $this->fail(GeoLogic::getError());
        return $this->success('已删除', []);
    }

    // ---------- AI官网SEO:站点 ----------
    public function sites()
    {
        return $this->data(GeoLogic::siteList($this->userId));
    }

    public function siteSave()
    {
        $id = (int)$this->request->post('id');
        // 编辑已有站点须校验归属;新建(id=0)不校验
        if ($id > 0 && !GeoLogic::userOwnsSite($this->userId, $id)) return $this->fail('无权操作该站点');
        $res = GeoLogic::siteSave($this->userId, $this->request->post());
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('已保存', $res);
    }

    public function siteCheck()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsSite($this->userId, $id)) return $this->fail('无权操作该站点');
        return $this->data(GeoLogic::siteCheck($id));
    }

    public function siteDelete()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsSite($this->userId, $id)) return $this->fail('无权操作该站点');
        GeoLogic::siteDelete($id);
        return $this->success('已删除', []);
    }

    // ---------- AI官网SEO:定时发布任务 ----------
    public function siteTasks()
    {
        // project_id 为 0 时列出用户自己全部任务;>0 时校验项目归属
        $pid = (int)$this->request->get('project_id');
        if ($pid > 0 && !$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::siteTaskList($this->userId, $pid));
    }

    public function siteTaskCreate()
    {
        $pid = (int)$this->request->post('project_id');
        $siteId = (int)$this->request->post('site_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        if (!GeoLogic::userOwnsSite($this->userId, $siteId)) return $this->fail('无权使用该站点');
        $res = GeoLogic::siteTaskCreate($this->userId, $this->request->post());
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('已创建定时任务', $res);
    }

    public function siteTaskToggle()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsSiteTask($this->userId, $id)) return $this->fail('无权操作该任务');
        GeoLogic::siteTaskToggle($id);
        return $this->success('已切换', []);
    }

    public function siteTaskDelete()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsSiteTask($this->userId, $id)) return $this->fail('无权操作该任务');
        GeoLogic::siteTaskDelete($id);
        return $this->success('已删除', []);
    }

    public function siteTaskRunOnce()
    {
        $id = (int)$this->request->post('id');
        if (!GeoLogic::userOwnsSiteTask($this->userId, $id)) return $this->fail('无权操作该任务');
        $res = GeoLogic::siteTaskRunOnce($id);
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('已发布 ' . $res['published'] . ' 篇', $res);
    }

    // ---------- 任务 ----------
    public function task()
    {
        $data = GeoLogic::task((int)$this->request->get('task_id'));
        if ($data && !$this->ownProject((int)($data['project_id'] ?? 0))) return $this->fail('无权访问该任务');
        return $this->data($data);
    }

    public function tasks()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoLogic::taskList($pid));
    }

    // ========== 以下为微盟星启功能移植(话题/场景问题/监测洞察/聊天生成) ==========

    /** 轮询文章封面图(生成侧异步出图) */
    public function contentCover()
    {
        $res = GeoLogic::contentCover($this->userId, (int)$this->request->get('content_id'));
        if (!$res) return $this->fail(GeoLogic::getError());
        return $this->data($res);
    }

    /** 重新生成文章封面图 */
    public function contentCoverRetry()
    {
        $res = GeoLogic::contentCoverRetry($this->userId, (int)$this->request->post('content_id'));
        if (!$res) return $this->fail(GeoLogic::getError());
        return $this->success('已重新提交', $res);
    }

    /** AI手机媒体可用的发布账号(投稿弹窗选号) */
    public function phoneAccounts()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoLogic::phoneAccounts($this->userId, (int)$this->request->get('media_id'));
        if (!$res) return $this->fail(GeoLogic::getError());
        return $this->data($res);
    }

    // ---------- 初始化引导 ----------
    public function initState()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoTopicLogic::initState($pid));
    }

    // ---------- 话题 ----------
    public function topics()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoTopicLogic::topicList($pid));
    }

    public function topicSave()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoTopicLogic::topicSave($pid, $this->request->post());
        if ($res === false) return $this->fail(GeoTopicLogic::getError());
        return $this->success('已保存', $res);
    }

    public function topicToggle()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        GeoTopicLogic::topicToggle($pid, (int)$this->request->post('id'));
        return $this->success('已切换', []);
    }

    public function topicDelete()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        GeoTopicLogic::topicDelete($pid, (int)$this->request->post('id'));
        return $this->success('已删除', []);
    }

    // ---------- 场景问题 ----------
    public function questions()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoTopicLogic::questionList($pid, $this->request->get()));
    }

    public function questionSave()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoTopicLogic::questionSave($pid, $this->request->post());
        if ($res === false) return $this->fail(GeoTopicLogic::getError());
        return $this->success('已保存', $res);
    }

    public function questionBatch()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoTopicLogic::questionBatch(
            $pid,
            (array)$this->request->post('ids', []),
            (string)$this->request->post('action')
        );
        if ($res === false) return $this->fail(GeoTopicLogic::getError());
        return $this->success('已处理 ' . $res['count'] . ' 条', $res);
    }

    // ---------- AI 推荐(初始化引导用) ----------
    public function aiTopics()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoTopicLogic::aiTopics($this->userId, $pid, max(1, (int)$this->request->post('count', 3)));
        if ($res === false) return $this->fail(GeoTopicLogic::getError());
        return $this->data($res);
    }

    public function aiQuestions()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        $res = GeoTopicLogic::aiQuestions(
            $this->userId,
            $pid,
            (int)$this->request->post('topic_id'),
            max(1, (int)$this->request->post('count', 10)),
            (string)$this->request->post('extra_info', '')
        );
        if ($res === false) return $this->fail(GeoTopicLogic::getError());
        return $this->success('已生成 ' . $res['created'] . ' 个场景问题', $res);
    }

    // 「文章转短视频」已下线(对齐 product):GEO 只产口播稿,由数字人模块出片并按其口径计费;
    // geo_video_task 表保留,发布台账仍要反查历史任务

    // ---------- 监测洞察(AI监测五页 + 报告) ----------
    public function insightOverview()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::overview($pid, $this->request->get()));
    }

    public function insightScene()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::sceneAnalysis($pid, $this->request->get()));
    }

    public function insightSnapshots()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::snapshots($pid, $this->request->get()));
    }

    public function insightTrend()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::onlineTrend(
            $pid,
            (int)$this->request->get('keyword_id'),
            (string)$this->request->get('engine', '')
        ));
    }

    /** 可见度按天趋势曲线(二期) */
    public function insightVisibilityTrend()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::visibilityTrend($pid, (int)$this->request->get('days', 30)));
    }

    public function insightQuotes()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::quotes($pid, $this->request->get()));
    }

    public function insightSentiment()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::sentimentStats($pid, $this->request->get()));
    }

    public function insightReport()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::report($pid));
    }

    // ---------- GEO 报告(落库:查看免费,生成/重新生成计费 geo_report) ----------
    public function reportLatest()
    {
        $pid = (int)$this->request->get('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        return $this->data(GeoInsightLogic::reportLatest($pid));
    }

    public function reportGenerate()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoInsightLogic::reportGenerate($this->userId, $pid);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('报告已生成', $res);
    }

    // ---------- 设置:授权账号(Web API 发布通道) ----------
    public function authPlatforms()
    {
        return $this->data(GeoAuthLogic::platforms($this->userId));
    }

    public function authAccountSave()
    {
        $res = GeoAuthLogic::save($this->userId, $this->request->post());
        if ($res === false) return $this->fail(GeoAuthLogic::getError());
        return $this->success('已保存授权', $res);
    }

    public function authAccountToggle()
    {
        $res = GeoAuthLogic::toggle($this->userId, (int)$this->request->post('id'));
        if ($res === false) return $this->fail(GeoAuthLogic::getError());
        return $this->success('已切换', $res);
    }

    public function authAccountDelete()
    {
        $ok = GeoAuthLogic::delete($this->userId, (int)$this->request->post('id'));
        if (!$ok) return $this->fail(GeoAuthLogic::getError());
        return $this->success('已解除授权', []);
    }

    public function authAccountCheck()
    {
        return $this->data(GeoAuthLogic::check($this->userId, (int)$this->request->post('id')));
    }

    // ---------- 聊天式内容生成 ----------
    public function contentTemplates()
    {
        return $this->data(GeoLogic::contentTemplates());
    }

    public function chatGenerate()
    {
        $pid = (int)$this->request->post('project_id');
        if (!$this->ownProject($pid)) return $this->fail('无权访问该项目');
        try {
            $res = GeoLogic::chatGenerate($this->userId, $pid, $this->request->post());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('已生成', $res);
    }

    // ---------- 自有发布登记 ----------
    public function publishRegister()
    {
        $res = GeoLogic::publishRegister(
            $this->userId,
            (int)$this->request->post('project_id'),
            $this->request->post()
        );
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('已登记', $res);
    }

    // ---------- AI手机投稿登记(设备端发布任务已下发,挂进投稿台账) ----------
    public function publishPhoneRegister()
    {
        $res = GeoLogic::publishPhoneRegister(
            $this->userId,
            (int)$this->request->post('project_id'),
            $this->request->post()
        );
        if ($res === false) return $this->fail(GeoLogic::getError());
        return $this->success('已记录投稿', $res);
    }
}
