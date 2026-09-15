<?php

declare(strict_types=1);

namespace app\common\service;

use Exception;
use think\facade\Db;

/**
 * 爆款玩法模板同步（2026-09-02）：模板中心 → 本地表 la_play_video_template。
 *
 * 与剪辑模板同款管道（同一个定时任务触发）：
 *   - 内容逐字段比对，无变化不写；
 *   - 封面/模板视频下载转存到本站存储（uploads/play_template/，C 端与生成链
 *     都用本站直链，不依赖模板中心带宽）；
 *   - 远端缺失即删（模板中心只下发上架行，下架即从本地消失）；
 *   - 空列表保护：拒绝清库，防模板中心异常导致误删；
 *   - `status` 为本地显隐开关（后台「爆款模板」页控制），不参与比对也不被
 *     同步覆盖——update 只写内容字段，insert 走列默认值 1。
 */
class PlayVideoTemplateSyncService
{
    private const PLAYS = ['clip', 'face', 'dance'];

    /** 参与「内容是否变化」比对的字段 */
    private const CONTENT_FIELDS = [
        'play', 'name', 'category', 'heat', 'duration_sec',
        'video_url', 'cover_url', 'face_count', 'allow_bg_replace', 'script', 'sort',
    ];

    public static function syncFromTemplateCenter(): array
    {
        $templates = TemplateCenterClient::playTemplates();

        $stats = [
            'created' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0,
            'downloaded' => 0, 'uploaded' => 0, 'reused' => 0, 'skipped' => 0, 'total' => 0,
        ];

        $rows = [];
        foreach ($templates as $item) {
            if (!is_array($item)) {
                $stats['skipped']++;
                continue;
            }
            $row = self::normalizeTemplate($item, $stats);
            if ($row === null) {
                $stats['skipped']++;
                continue;
            }
            $rows[$row['id']] = $row;
        }

        if (empty($rows)) {
            throw new Exception('玩法模板为空，已跳过清洗，避免误删本地数据');
        }

        Db::startTrans();
        try {
            $remoteIds = array_keys($rows);
            $existingRows = Db::name('play_video_template')
                ->whereIn('id', $remoteIds)
                ->select()
                ->toArray();
            $existingMap = [];
            foreach ($existingRows as $existingRow) {
                $existingMap[(string)$existingRow['id']] = $existingRow;
            }

            $now = time();
            foreach ($rows as $id => $row) {
                $existing = $existingMap[$id] ?? null;
                if ($existing === null) {
                    $row['create_time'] = $now;
                    $row['update_time'] = $now;
                    Db::name('play_video_template')->insert($row);
                    $stats['created']++;
                    continue;
                }
                if (!self::hasContentChanged($existing, $row)) {
                    $stats['unchanged']++;
                    continue;
                }
                $row['update_time'] = $now;
                Db::name('play_video_template')->where('id', $id)->update($row);
                $stats['updated']++;
            }

            $stats['deleted'] = Db::name('play_video_template')->whereNotIn('id', $remoteIds)->delete();
            $stats['total'] = count($rows);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $stats;
    }

    private static function hasContentChanged(array $existing, array $row): bool
    {
        foreach (self::CONTENT_FIELDS as $field) {
            if ((string)($existing[$field] ?? '') !== (string)($row[$field] ?? '')) {
                return true;
            }
        }
        return false;
    }

    private static function normalizeTemplate(array $item, array &$stats): ?array
    {
        $id = trim((string)($item['ref_id'] ?? $item['id'] ?? ''));
        $play = trim((string)($item['play'] ?? ''));
        $name = trim((string)($item['name'] ?? ''));
        $videoUrl = trim((string)($item['video_url'] ?? ''));
        if ($id === '' || $name === '' || $videoUrl === '' || !in_array($play, self::PLAYS, true)) {
            return null;
        }

        $script = $item['script'] ?? [];
        if (!is_array($script)) {
            $script = [];
        }

        $videoUri = ShanjianClipTemplateSyncService::downloadTemplateAsset($videoUrl, 'video', $stats, 'uploads/play_template');
        $coverUri = ShanjianClipTemplateSyncService::downloadTemplateAsset(trim((string)($item['cover_url'] ?? '')), 'image', $stats, 'uploads/play_template');
        if ($coverUri === '') {
            // 模板中心没配封面：用转存后的模板视频 OSS 截帧兜底（零转码），前台与创作记录都不留白
            $coverUri = \app\common\service\playVideo\PlayVideoCoverService::snapshotUri($videoUri);
        }

        return [
            'id'               => $id,
            'play'             => $play,
            'name'             => $name,
            'category'         => trim((string)($item['category'] ?? '')),
            'heat'             => trim((string)($item['heat'] ?? '')),
            'duration_sec'     => max(0, (int)($item['duration_sec'] ?? 0)),
            'video_url'        => $videoUri,
            'cover_url'        => $coverUri,
            'face_count'       => max(0, (int)($item['face_count'] ?? 0)),
            'allow_bg_replace' => !empty($item['allow_bg_replace']) ? 1 : 0,
            'script'           => json_encode($script, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sort'             => (int)($item['sort'] ?? 0),
        ];
    }
}
