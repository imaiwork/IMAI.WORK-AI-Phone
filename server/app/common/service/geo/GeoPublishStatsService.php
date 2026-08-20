<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoMedia;
use app\common\model\geo\GeoPublish;
use think\facade\Log;

/**
 * GEO 投稿效果回收：按 geo_publish.published_url 反查各平台互动数据。
 *
 * 由 geo_publish_sync 定时任务驱动（与发稿回执同一节奏），
 * GEO 中台密钥未配置时整体空转。
 *
 * 成本是这里的头等约束 —— 效果回收按次计费，而台账行数只增不减。
 * 三道闸把每轮花销框死：
 *   1) 冷却期 RESYNC_HOURS：一条内容最快 6 小时才回收一次
 *   2) 观察窗 MAX_AGE_DAYS：发布超过 30 天的不再回收（数据早就不动了）
 *   3) 每轮上限 MAX_PER_RUN：一次跑最多处理 N 条，超出的下一轮再说
 * 三个都可用 env 覆盖。
 */
class GeoPublishStatsService
{
    /** 同一条内容的最小回收间隔（小时） */
    protected const RESYNC_HOURS = 6;

    /** 发布多久之后不再回收（天） */
    protected const MAX_AGE_DAYS = 30;

    /** 单轮处理上限 */
    protected const MAX_PER_RUN = 200;

    /**
     * 扫一轮待回收的投稿。
     *
     * @return array{scanned:int,ok:int,failed:int,skipped:int,capped:bool}
     */
    public static function syncStats(): array
    {
        $s = ['scanned' => 0, 'ok' => 0, 'failed' => 0, 'skipped' => 0, 'capped' => false];
        if (!GeoTikhubService::enabled()) {
            return $s;
        }

        $now = time();
        $cooldown = max(1, (int)env('geo.tikhub_resync_hours', self::RESYNC_HOURS)) * 3600;
        $maxAge = max(1, (int)env('geo.tikhub_max_age_days', self::MAX_AGE_DAYS)) * 86400;
        $limit = max(1, (int)env('geo.tikhub_max_per_run', self::MAX_PER_RUN));

        $rows = GeoPublish::where('status', 'published')
            ->where('published_url', '<>', '')
            // unsupported 是"这个平台/链接根本查不了"，重试没有意义，直接排除
            ->where('stat_status', '<>', 'unsupported')
            ->where('stat_sync_time', '<', $now - $cooldown)
            ->where('create_time', '>', $now - $maxAge)
            ->order('stat_sync_time', 'asc')
            ->limit($limit + 1)
            ->select();

        if (count($rows) > $limit) {
            $s['capped'] = true;
            $rows = $rows->slice(0, $limit);
        }

        foreach ($rows as $rec) {
            $s['scanned']++;
            $platform = self::platformOf($rec);
            if ($platform === '') {
                // 媒体投递、官网站点这类非社媒渠道，标记后不再重复扫
                $rec->stat_status = 'unsupported';
                $rec->stat_error = '该渠道无数据回收通道';
                $rec->stat_sync_time = $now;
                $rec->save();
                $s['skipped']++;
                continue;
            }

            try {
                $stats = GeoTikhubService::statsByUrl(
                    (string)$rec->published_url,
                    $platform,
                    (string)($rec->media_type ?: 'article')
                );
                $rec->stat_views = $stats['views'];
                $rec->stat_likes = $stats['likes'];
                $rec->stat_comments = $stats['comments'];
                $rec->stat_collects = $stats['collects'];
                $rec->stat_shares = $stats['shares'];
                $rec->stat_status = 'ok';
                $rec->stat_error = '';
                $s['ok']++;
            } catch (\Throwable $e) {
                // 失败只标记不清零：上一轮回收到的数据仍然有展示价值，
                // 直接抹成 0 会让前端看起来像"数据掉了"。
                $rec->stat_status = 'failed';
                $rec->stat_error = mb_substr($e->getMessage(), 0, 200);
                $s['failed']++;
            }
            $rec->stat_sync_time = $now;
            $rec->save();
        }

        return $s;
    }

    /**
     * 这条投稿属于哪个社媒平台；非社媒渠道返回 ''。
     * 平台判定沿用 GeoPhonePublishService 那套 provider_code 映射，
     * 不另起一份会漂移的平台表。
     */
    protected static function platformOf(GeoPublish $rec): string
    {
        if ((int)$rec->media_id <= 0) {
            return '';
        }
        $media = GeoMedia::findOrEmpty((int)$rec->media_id);
        if ($media->isEmpty()) {
            return '';
        }
        return GeoPhonePublishService::platformCodeOf($media);
    }

    /**
     * 某项目已回收到的投稿效果汇总，供诊断报告与投稿台账展示。
     * 只统计回收成功的行，避免把"还没回收"混进分母拉低平均值。
     */
    public static function projectSummary(int $projectId): array
    {
        $rows = GeoPublish::where('project_id', $projectId)
            ->where('stat_status', 'ok')
            ->field('stat_views,stat_likes,stat_comments,stat_collects,stat_shares')
            ->select()
            ->toArray();

        $sum = ['count' => count($rows), 'views' => 0, 'likes' => 0, 'comments' => 0, 'collects' => 0, 'shares' => 0];
        foreach ($rows as $r) {
            $sum['views'] += (int)$r['stat_views'];
            $sum['likes'] += (int)$r['stat_likes'];
            $sum['comments'] += (int)$r['stat_comments'];
            $sum['collects'] += (int)$r['stat_collects'];
            $sum['shares'] += (int)$r['stat_shares'];
        }
        $sum['interactions'] = $sum['likes'] + $sum['comments'] + $sum['collects'] + $sum['shares'];
        return $sum;
    }
}
