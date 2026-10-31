<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\shanjian\SystemMusic;
use Exception;
use think\facade\Db;

class SystemMusicSyncService
{
    /**
     * 定时任务传 $useCache = false：每轮打远端拿最新曲库，缓存只保护手动重复执行。
     */
    public static function syncFromTemplateCenter(bool $useCache = true): array
    {
        $rows = TemplateCenterClient::systemMusics($useCache);
        return self::syncFromPayload($rows);
    }

    /**
     * 只同步模板中心下发的内容字段（name/audio_url/duration_sec/sort/enabled），
     * 本站覆盖位 status（显隐）与 sort_override（本站排序）不在这里写，避免把站长设置顶掉。
     * source=local 的曲目（站长后台上传）完全不参与：既不会被下架，也不会被改名。
     */
    public static function syncFromPayload(array $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'disabled' => 0, 'total' => 0];
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
                'source' => SystemMusic::SOURCE_CENTER,
            ];
        }
        if ($normalized === []) {
            throw new Exception('系统曲库为空，已跳过同步，避免误下架本地曲目');
        }

        $now = time();
        Db::startTrans();
        try {
            $existingRows = SystemMusic::field('id,name,audio_url,duration_sec,sort,enabled')->select()->toArray();
            $existingMap = [];
            foreach ($existingRows as $existingRow) {
                $existingMap[(string)$existingRow['id']] = $existingRow;
            }

            foreach ($normalized as $id => $row) {
                $current = $existingMap[$id] ?? null;
                if ($current === null) {
                    $row['create_time'] = $now;
                    $row['update_time'] = $now;
                    SystemMusic::create($row);
                    $stats['created']++;
                    continue;
                }

                // 内容没变就不写库：5 分钟一轮，全量重写会让 update_time 失去「内容更新时间」的含义
                $changed = false;
                foreach (['name', 'audio_url', 'duration_sec', 'sort', 'enabled'] as $field) {
                    if ((string)$current[$field] !== (string)$row[$field]) {
                        $changed = true;
                        break;
                    }
                }
                if (!$changed) {
                    $stats['unchanged']++;
                    continue;
                }

                $row['update_time'] = $now;
                // 来源在创建时定死：更新只写内容字段，不碰 source / status / sort_override
                unset($row['source']);
                SystemMusic::where('id', $id)->update($row);
                $stats['updated']++;
            }

            $remoteIds = array_keys($normalized);
            // 只下架「模板中心同步来的」曲目：本站上传（source=local）的不在远端列表里，
            // 不加这个条件会每 5 分钟把它们整批置成 enabled=0，前台直接消失。
            $stats['disabled'] = (int)SystemMusic::where('source', SystemMusic::SOURCE_CENTER)
                ->where('enabled', 1)
                ->whereNotIn('id', $remoteIds)
                ->update([
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
