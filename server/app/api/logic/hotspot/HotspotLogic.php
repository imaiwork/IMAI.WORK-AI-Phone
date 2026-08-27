<?php

namespace app\api\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\service\hotspot\AnalyzeService;
use app\common\service\hotspot\HistoryService;
use app\common\service\hotspot\HotListService;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\InsightService;
use app\common\service\hotspot\PersonaService;
use app\common\service\hotspot\RecordService;
use app\common\service\hotspot\ResearchService;
use app\common\service\hotspot\ScriptService;
use app\common\service\hotspot\TaskService;
use app\common\service\hotspot\VideoService;

class HotspotLogic extends BaseLogic
{
    public static function health(): array
    {
        // 免登录接口，不对外暴露内部密钥配置状态（配置详情记日志即可）
        HotspotLog::write(sprintf(
            '健康检查：tikhub_key=%s ark_key=%s',
            (string)config('hotspot.tikhub_api_key', '') !== '' ? '已配置' : '未配置',
            (string)config('hotspot.ark_api_key', '') !== '' ? '已配置' : '未配置'
        ));
        return [
            'ok' => true,
            'video_provider' => 'shanjian',
        ];
    }

    public static function platforms(): array
    {
        return HotListService::PLATFORMS;
    }

    public static function hot(array $params, int $userId = 0): array|false
    {
        $platform = (string)($params['platform'] ?? '');
        $period = (string)($params['period'] ?? 'day');
        $day = (string)($params['day'] ?? '');
        $limit = (int)($params['limit'] ?? 30);
        HotspotLog::write(sprintf(
            '热榜请求：平台=%s 周期=%s 日期=%s 条数=%d 用户=%d 不扣算力',
            $platform,
            $period,
            $day !== '' ? $day : '当天',
            $limit,
            $userId
        ));
        try {
            $result = HotListService::getHot($platform, $period, $day, $limit);
            $topics = is_array($result['topics'] ?? null) ? $result['topics'] : [];
            $result['topics'] = RecordService::attachAnalyzed($topics, $userId, $platform);
            HotspotLog::write(sprintf(
                '热榜成功：平台=%s 周期=%s 日期=%s 条数=%d 缓存=%s 实时=%s 不扣算力',
                $result['platform'] ?? $platform,
                $result['period'] ?? $period,
                $result['date'] ?? $day,
                count($result['topics'] ?? []),
                !empty($result['cached']) ? '是' : '否',
                !empty($result['live']) ? '是' : '否'
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('热榜业务失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点热榜异常', $e);
        }
    }

    public static function historyDates(array $params): array|false
    {
        $platform = (string)($params['platform'] ?? '');
        $dates = HistoryService::availableDates($platform);
        HotspotLog::write(sprintf('历史日期查询：平台=%s 数量=%d', $platform, count($dates)));
        return [
            'platform' => $platform,
            'dates' => $dates,
            'note' => $dates === []
                ? '还没有历史快照。上游不提供往期热榜，历史从本服务开始运行后逐日累积。'
                : '',
        ];
    }

    public static function insight(array $params, int $userId = 0): array|false
    {
        $topic = (string)($params['topic'] ?? '');
        HotspotLog::write('洞察请求：话题=' . $topic . ' 用户=' . $userId);
        try {
            $result = InsightService::getInsight($topic, $userId);
            HotspotLog::write(sprintf(
                '洞察成功：话题=%s 命中=%s 趋势点数=%d 视频数=%d',
                $result['topic'] ?? $topic,
                !empty($result['found']) ? '是' : '否',
                count($result['trend'] ?? []),
                count($result['videos'] ?? [])
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('洞察业务失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点洞察异常', $e);
        }
    }

    public static function hotWords(array $params, int $userId = 0): array|false
    {
        $appName = (string)($params['app_name'] ?? 'aweme');
        HotspotLog::write('热搜词请求：应用=' . $appName . ' 用户=' . $userId);
        try {
            $words = InsightService::hotWords($appName, $userId);
            $words = is_array($words) ? $words : [];
            HotspotLog::write('热搜词成功：应用=' . $appName . ' 数量=' . count($words));
            return $words;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('热搜词业务失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点热搜词异常', $e);
        }
    }

    public static function research(array $params, int $userId = 0): array|false
    {
        $topic = (string)($params['topic'] ?? '');
        $platform = (string)($params['platform'] ?? 'douyin');
        $category = (string)($params['category'] ?? '');
        HotspotLog::write(sprintf('联网核清请求：用户=%d 话题=%s 平台=%s 分类=%s', $userId, $topic, $platform, $category));
        try {
            $result = ResearchService::research($topic, $platform, $category, $userId);
            HotspotLog::write(sprintf(
                '联网核清成功：话题=%s 摘要长度=%d 要点数=%d 引用数=%d 检索词数=%d',
                $result['topic'] ?? $topic,
                mb_strlen((string)($result['summary'] ?? '')),
                count($result['core_points'] ?? []),
                count($result['citations'] ?? []),
                count($result['search_queries'] ?? [])
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('联网核清业务失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点联网核清异常', $e);
        }
    }

    public static function analyze(array $params, int $userId): array|false
    {
        $topic = (string)($params['topic'] ?? '');
        $platform = (string)($params['platform'] ?? 'douyin');
        $persona = PersonaService::fromRequest($params);
        HotspotLog::write(sprintf(
            '人设契合请求：用户=%d 话题=%s 平台=%s 人设id=%s 人设名=%s',
            $userId,
            $topic,
            $platform,
            (string)($persona['id'] ?? ''),
            (string)($persona['name'] ?? '')
        ));
        try {
            if (!PersonaService::hasIdentity($persona)) {
                HotspotLog::write('人设契合失败：人设不能为空');
                return self::fail('人设不能为空');
            }
            $resolved = PersonaService::resolve($userId, $persona);
            $result = AnalyzeService::analyze(
                $topic,
                $platform,
                (string)($params['summary'] ?? ''),
                is_array($params['core_points'] ?? null) ? $params['core_points'] : [],
                $resolved,
                (string)($params['portrait'] ?? ''),
                $userId
            );
            HotspotLog::write(sprintf(
                '人设契合成功：话题=%s 人设=%s 分数=%d 目标=%s 方向=%s',
                $result['topic'] ?? $topic,
                $result['persona_name'] ?? '',
                (int)($result['fit_score'] ?? 0),
                (string)($result['recommended_goal'] ?? ''),
                (string)($result['recommended_direction'] ?? '')
            ));
            RecordService::recordAnalysis($userId, array_merge($params, ['persona' => $resolved]), $result);
            return $result;
        } catch (HotspotUpstreamException $e) {
            $msg = $e->getMessage();
            HotspotLog::write('人设契合业务失败：' . $msg);
            if (str_contains($msg, '人设不存在')) {
                return self::fail('人设不存在');
            }
            return self::fail($msg);
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点人设契合异常', $e);
        }
    }

    public static function script(array $params, int $userId): array|false
    {
        $topic = (string)($params['topic'] ?? '');
        $platform = (string)($params['platform'] ?? 'douyin');
        $persona = PersonaService::fromRequest($params);
        $options = is_array($params['options'] ?? null) ? $params['options'] : [];
        HotspotLog::write(sprintf(
            '口播文案请求：用户=%d 话题=%s 平台=%s 人设id=%s 选项=%s',
            $userId,
            $topic,
            $platform,
            (string)($persona['id'] ?? ''),
            HotspotLog::clip($options, 300)
        ));
        try {
            $resolved = [];
            if (PersonaService::hasIdentity($persona)) {
                $resolved = PersonaService::resolve($userId, $persona);
            }
            $result = ScriptService::generate(
                $topic,
                $platform,
                is_array($params['core_points'] ?? null) ? $params['core_points'] : [],
                (string)($params['summary'] ?? ''),
                $resolved,
                is_array($params['analysis'] ?? null) ? $params['analysis'] : [],
                $options,
                $userId
            );
            HotspotLog::write(sprintf(
                '口播文案成功：话题=%s 标题=%s 字数=%d 预估秒数=%d',
                $result['topic'] ?? $topic,
                (string)($result['title'] ?? ''),
                (int)($result['word_count'] ?? 0),
                (int)($result['est_duration_sec'] ?? 0)
            ));
            RecordService::recordCreation($userId, array_merge($params, ['persona' => $resolved]), $result);
            return $result;
        } catch (HotspotUpstreamException $e) {
            $msg = $e->getMessage();
            HotspotLog::write('口播文案业务失败：' . $msg);
            if (str_contains($msg, '人设不存在')) {
                return self::fail('人设不存在');
            }
            return self::fail($msg);
        } catch (\Throwable $e) {
            return self::failChargeOrGeneric('热点口播文案异常', $e);
        }
    }

    public static function options(): array
    {
        return ScriptService::options();
    }

    public static function personas(int $userId): array
    {
        HotspotLog::write('人设列表请求：用户=' . $userId);
        $list = PersonaService::lists($userId);
        HotspotLog::write('人设列表成功：用户=' . $userId . ' 数量=' . count($list));
        return $list;
    }

    public static function avatars(array $params, int $userId): array|false
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        HotspotLog::write(sprintf('数字人形象列表请求：用户=%d 人设id=%d', $userId, $personaId));
        try {
            $list = PersonaService::avatars($userId, $personaId);
            HotspotLog::write(sprintf('数字人形象列表成功：用户=%d 人设id=%d 数量=%d', $userId, $personaId, count($list)));
            return $list;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('数字人形象列表失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            HotspotLog::exception('热点数字人形象列表异常', $e);
            return self::fail('服务异常，请稍后再试');
        }
    }

    public static function clipMaterials(array $params, int $userId): array|false
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        $pageNo = (int)($params['page_no'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 10);
        HotspotLog::write(sprintf(
            '混剪素材列表请求：用户=%d 人设id=%d 页=%d 每页=%d',
            $userId,
            $personaId,
            $pageNo,
            $pageSize
        ));
        try {
            $list = PersonaService::clipMaterials($userId, $personaId, $pageNo, $pageSize);
            HotspotLog::write(sprintf(
                '混剪素材列表成功：用户=%d 人设id=%d 数量=%d',
                $userId,
                $personaId,
                count($list)
            ));
            return $list;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('混剪素材列表失败：' . $e->getMessage());
            return self::fail($e->getMessage());
        } catch (\Throwable $e) {
            HotspotLog::exception('热点混剪素材列表异常', $e);
            return self::fail('服务异常，请稍后再试');
        }
    }

    public static function tasks(int $userId, array $params = []): array
    {
        $pageNo = (int)($params['page_no'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 25);
        $status = trim((string)($params['status'] ?? ''));
        if ($pageSize <= 0) {
            $pageSize = 25;
        }
        HotspotLog::write(sprintf(
            '任务列表请求：用户=%d 页=%d 每页=%d 状态=%s',
            $userId,
            $pageNo,
            $pageSize,
            $status === '' ? '全部' : $status
        ));
        $result = TaskService::lists($userId, $pageNo, $pageSize, $status);
        HotspotLog::write(sprintf(
            '任务列表成功：用户=%d 状态=%s 数量=%d 总数=%d',
            $userId,
            $status === '' ? '全部' : $status,
            count($result['lists'] ?? []),
            (int)($result['count'] ?? 0)
        ));
        return $result;
    }

    public static function add(array $params, int $userId): array|false
    {
        HotspotLog::write(sprintf(
            '任务创建请求：用户=%d 话题=%s 平台=%s 标题=%s',
            $userId,
            (string)($params['topic'] ?? ''),
            (string)($params['platform'] ?? ''),
            (string)($params['title'] ?? '')
        ));
        if ($userId <= 0) {
            HotspotLog::write('任务创建失败：未登录');
            return self::fail('请先登录后再生成视频');
        }
        $taskNo = '';
        try {
            if (PersonaService::hasIdentity(PersonaService::fromRequest($params))) {
                $params['persona'] = PersonaService::resolve($userId, PersonaService::fromRequest($params));
            }
            $result = TaskService::create($params, $userId);
            $taskNo = (string)($result['id'] ?? '');
            HotspotLog::write('任务入库成功：用户=' . $userId . ' 任务号=' . $taskNo);
            $result['user_id'] = $userId;
            RecordService::attachTask($result);
            unset($result['user_id']);
            VideoService::enqueue($result, $userId);
            HotspotLog::write('任务已入库待后台下发：任务号=' . $taskNo);
            return $result;
        } catch (HotspotUpstreamException $e) {
            $msg = $e->getMessage();
            HotspotLog::write('任务创建失败：' . $msg);
            if ($taskNo !== '') {
                TaskService::markFailed($taskNo, $msg);
            }
            if (str_contains($msg, '人设不存在')) {
                return self::fail('人设不存在');
            }
            return self::fail($msg);
        } catch (\Throwable $e) {
            HotspotLog::exception('热点任务创建异常', $e);
            if ($taskNo !== '') {
                TaskService::markFailed($taskNo, '服务异常，请稍后再试');
            }
            return self::fail('服务异常，请稍后再试');
        }
    }

    /**
     * 按话题取该用户最近一次任务的完整现场（要点/引用/分析/人设/设置/文案），
     * 供前端把「已分析」热点还原到上次步骤，避免重复扣费重跑搜索与分析
     */
    public static function lastFlow(array $params, int $userId): array
    {
        $topic = (string)($params['topic'] ?? '');
        $platform = (string)($params['platform'] ?? 'douyin');
        HotspotLog::write(sprintf('上次现场请求：用户=%d 话题=%s 平台=%s', $userId, $topic, $platform));
        if ($userId <= 0 || $topic === '') {
            return ['found' => false];
        }
        $row = \app\common\model\hotspot\HotspotTask::where('user_id', $userId)
            ->where('topic', $topic)
            ->where('platform', $platform)
            ->order('id', 'desc')
            ->findOrEmpty();
        if ($row->isEmpty()) {
            HotspotLog::write('上次现场未命中：话题=' . $topic);
            return ['found' => false];
        }
        $task = TaskService::detail((string)$row->task_no, $userId);
        if ($task === null) {
            return ['found' => false];
        }
        HotspotLog::write(sprintf('上次现场命中：话题=%s 任务号=%s', $topic, $task['id'] ?? ''));
        return ['found' => true] + $task;
    }

    public static function detail(array $params, int $userId = 0): array|false
    {
        $taskNo = (string)($params['id'] ?? '');
        HotspotLog::write('任务详情请求：任务号=' . $taskNo . ' 用户=' . $userId);
        $task = TaskService::detail($taskNo, $userId);
        if ($task === null) {
            HotspotLog::write('任务详情失败：任务不存在 任务号=' . $taskNo);
            return self::fail('任务不存在');
        }
        $task = VideoService::compensate($task);
        HotspotLog::write(sprintf(
            '任务详情成功：任务号=%s 状态=%s',
            $task['id'] ?? $taskNo,
            $task['status'] ?? ''
        ));
        return $task;
    }

    public static function delete(array $params, int $userId = 0): bool
    {
        $taskNo = (string)($params['id'] ?? '');
        HotspotLog::write('任务删除请求：任务号=' . $taskNo . ' 用户=' . $userId);
        try {
            if (!TaskService::delete($taskNo, $userId)) {
                HotspotLog::write('任务删除失败：任务不存在 任务号=' . $taskNo);
                return self::fail('任务不存在');
            }
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('任务删除失败：' . $e->getMessage() . ' 任务号=' . $taskNo);
            return self::fail($e->getMessage());
        }
        HotspotLog::write('任务删除成功：任务号=' . $taskNo);
        return true;
    }

    public static function retry(array $params, int $userId): array|false
    {
        $taskNo = (string)($params['id'] ?? '');
        HotspotLog::write('任务重试请求：任务号=' . $taskNo . ' 用户=' . $userId);
        if ($userId <= 0) {
            HotspotLog::write('任务重试失败：未登录');
            return self::fail('请先登录后再生成视频');
        }
        try {
            $result = TaskService::resetForRetry($taskNo, $userId);
            $result = VideoService::retryOrEnqueue($result, $userId);
            HotspotLog::write(sprintf(
                '任务已重新入队：任务号=%s 状态=%s 重试序号=%d',
                $result['id'] ?? $taskNo,
                $result['status'] ?? '',
                (int)(($result['options']['retry_seq'] ?? 0))
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            $msg = $e->getMessage();
            HotspotLog::write('任务重试失败：' . $msg);
            return self::fail($msg);
        } catch (\Throwable $e) {
            HotspotLog::exception('热点任务重试异常', $e);
            return self::fail('服务异常，请稍后再试');
        }
    }

    private static function failChargeOrGeneric(string $logPrefix, \Throwable $e): bool
    {
        $code = (int)$e->getCode();
        $msg = $e->getMessage();
        if ($code === 4059 || str_contains($msg, '请先登录') || str_contains($msg, '算力不足') || str_contains($msg, '对话模型')) {
            HotspotLog::write($logPrefix . '：' . $msg);
            return self::fail($msg);
        }
        HotspotLog::exception($logPrefix, $e);
        return self::fail('服务异常，请稍后再试');
    }

    private static function fail(string $msg): bool
    {
        self::setError($msg);
        return false;
    }
}
