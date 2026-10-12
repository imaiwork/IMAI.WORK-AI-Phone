<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\shanjian\SystemMusic;
use Exception;
use think\facade\Db;

class SystemMusicSyncService
{
    public static function syncFromTemplateCenter(): array
    {
        $rows = TemplateCenterClient::systemMusics();
        return self::syncFromPayload($rows);
    }

    public static function syncFromPayload(array $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'disabled' => 0, 'total' => 0];
        $normalized = [];
        foreach ($rows as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string)($item['id'] ?? ''));
            $name = trim((string)($item['name'] ?? ''));
            $url = trim((string)($item['audio_url'] ?? ''));
            if ($id === '' || $name === '' || $url === '') {
                continue;
            }
            $normalized[$id] = [
                'id' => $id,
                'name' => mb_substr($name, 0, 128),
                'audio_url' => mb_substr($url, 0, 512),
                'duration_sec' => max(0, (int)($item['duration_sec'] ?? 0)),
                'sort' => (int)($item['sort'] ?? 0),
                'enabled' => 1,
            ];
        }
        if ($normalized === []) {
            throw new Exception('系统曲库为空，已跳过同步，避免误下架本地曲目');
        }

        $now = time();
        Db::startTrans();
        try {
            $existing = SystemMusic::column('id');
            $existing = array_fill_keys(array_map('strval', $existing), true);
            foreach ($normalized as $id => $row) {
                $row['update_time'] = $now;
                if (isset($existing[$id])) {
                    SystemMusic::where('id', $id)->update($row);
                    $stats['updated']++;
                } else {
                    $row['create_time'] = $now;
                    SystemMusic::create($row);
                    $stats['created']++;
                }
            }
            $remoteIds = array_keys($normalized);
            $stats['disabled'] = (int)SystemMusic::where('enabled', 1)->whereNotIn('id', $remoteIds)->update([
                'enabled' => 0,
                'update_time' => $now,
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        $stats['total'] = count($normalized);
        return $stats;
    }
}
