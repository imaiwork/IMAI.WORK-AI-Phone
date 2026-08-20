<?php

namespace app\adminapi\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoPublish;
use app\common\model\geo\GeoTask;
use app\common\model\user\UserTokensLog;

/**
 * GEO 概览看板:指标卡 / 30日趋势 / 场景算力消耗 / 定时任务健康
 * 全部为只读聚合查询,不做写操作
 */
class GeoOverviewLogic extends BaseLogic
{
    /** 账变编号 → 场景名(14001-14010,与 AccountLogEnum TOKENS_DEC_GEO_* 对齐) */
    public const SCENE_LABELS = [
        14001 => 'AI生成文章',
        14002 => 'AI搜索监测',
        14003 => 'AI推荐话题',
        14004 => 'AI生成场景问题',
        14005 => '知识解析导入',
        14006 => '品牌分析',
        14007 => '优化建议',
        14008 => '文章转短视频',
        14009 => 'AI匹配品牌信息',
        14010 => 'GEO诊断报告',
    ];

    /** 概览关注的 GEO 定时任务及其周期(秒),超 2 倍周期未跑由前端标红 */
    public const CRON_COMMANDS = [
        'geo_monitor_cron'  => 60,
        'geo_daily_monitor' => 86400,
        'geo_publish_sync'  => 600,
        'geo_site_publish'  => 3600,
    ];

    public static function index(): bool
    {
        try {
            $now = time();
            $since30 = $now - 30 * 86400;
            $since7 = $now - 7 * 86400;

            self::$returnData = [
                'stats' => [
                    'project_total' => GeoProject::count(),
                    'project_active_7d' => (int)GeoMonitor::where('create_time', '>=', $since7)
                        ->count('DISTINCT project_id'),
                    'monitor_total' => GeoMonitor::count(),
                    'content_total' => GeoContent::count(),
                    'content_adopted' => GeoContent::where('adopted', 1)->count(),
                    'publish_total' => GeoPublish::count(),
                    'publish_published' => GeoPublish::where('status', 'published')->count(),
                    'publish_failed' => GeoPublish::where('status', 'failed')->count(),
                ],
                'trend' => [
                    'monitor' => self::dailyTrend(new GeoMonitor(), $since30),
                    'content' => self::dailyTrend(new GeoContent(), $since30),
                ],
                'consume' => self::consume($since30),
                'cron' => self::cronHealth(),
                'running_batch' => GeoTask::where('task_type', 'monitor_batch')
                    ->where('status', 'running')->count(),
                'pending_batch' => GeoTask::where('task_type', 'monitor_batch')
                    ->where('status', 'pending')->count(),
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /** 近 30 日按天计数,返回 [{date, count}] 升序 */
    protected static function dailyTrend($model, int $since): array
    {
        return $model->where('create_time', '>=', $since)
            ->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') AS date, COUNT(*) AS count")
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();
    }

    /** 近 30 日 GEO 场景算力消耗(按账变编号分组;历史流水,含切模型计费前的场景扣费) */
    protected static function consume(int $since): array
    {
        $rows = UserTokensLog::whereBetween('change_type', [14001, 14010])
            ->where('create_time', '>=', $since)
            ->field('change_type, SUM(change_amount) AS total, COUNT(*) AS count')
            ->group('change_type')
            ->select()
            ->toArray();
        foreach ($rows as &$r) {
            $r['label'] = self::SCENE_LABELS[(int)$r['change_type']] ?? (string)$r['change_type'];
            $r['total'] = round((float)$r['total'], 2);
        }
        return $rows;
    }

    /**
     * GEO 定时任务健康:最近执行时间 + 周期,判断是否超期。
     * 直查表取原始 int 时间戳:Crontab 模型的 getLastTimeAttr 会把 last_time
     * 格式化成字符串,导致超期判断被字符串强转污染
     */
    protected static function cronHealth(): array
    {
        $rows = \think\facade\Db::name('dev_crontab')
            ->whereIn('command', array_keys(self::CRON_COMMANDS))
            ->whereNull('delete_time')
            ->field('id, name, command, expression, status, last_time')
            ->select()
            ->toArray();
        $now = time();
        foreach ($rows as &$r) {
            $period = self::CRON_COMMANDS[$r['command']] ?? 3600;
            $r['period'] = $period;
            $r['last_time'] = (int)$r['last_time'];
            $r['last_time_text'] = $r['last_time'] ? date('Y-m-d H:i:s', $r['last_time']) : '';
            $r['stale'] = (int)($r['status'] == 1 && ($r['last_time'] === 0 || $now - $r['last_time'] > 2 * $period));
        }
        return $rows;
    }
}
