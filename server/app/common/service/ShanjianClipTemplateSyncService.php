<?php

namespace app\common\service;

use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\storage\Driver as StorageDriver;
use Exception;
use think\facade\Db;

class ShanjianClipTemplateSyncService
{
    private const ALLOWED_SCENES = [
        'virtualman',
        'oralMixCutting',
        'newsMixCutting',
        'realMan',
    ];

    /** 参与「内容是否变化」比对的字段；时间戳由本地维护，不参与比对 */
    private const CONTENT_FIELDS = [
        'name',
        'cover_url',
        'scene',
        'demo_url',
        'auto_type',
        'sort',
    ];

    public static function syncFromPlatform(bool $useCache = true): array
    {
        // 2026-09-02 起数据源为模板中心（imai-temp.imai.work）直连；
        // 字段与旧契约同构，后续清洗管道不变。缓存/超时/降级见 TemplateCenterClient。
        // 定时任务传 false：每轮都要拿到远端最新内容，不能被 Redis 缓存吃掉一个周期。
        $templates = TemplateCenterClient::shanjianClipTemplates($useCache);

        return self::syncFromPayload($templates);
    }

    public static function syncFromPayload(array $templates): array
    {
        $rows = [];
        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'deleted' => 0,
            'downloaded' => 0,
            'uploaded' => 0,
            'reused' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failed_items' => [],
            'total' => 0,
        ];

        // 先取本地已有行：一方面用于判断内容指纹是否变化（未变化直接跳过素材下载/探活），
        // 另一方面给后续事务里的字段比对复用，省一次全表查询。
        $remoteIds = [];
        foreach ($templates as $item) {
            $id = is_array($item) ? trim((string)($item['id'] ?? '')) : '';
            if ($id !== '') {
                $remoteIds[$id] = true;
            }
        }
        $existingMap = [];
        foreach (array_chunk(array_keys($remoteIds), 500) as $ids) {
            $existingRows = Db::name('shanjian_clip_template')
                ->whereIn('id', $ids)
                ->field('id,' . implode(',', self::CONTENT_FIELDS) . ',source_hash,create_time,update_time')
                ->select()
                ->toArray();
            foreach ($existingRows as $existingRow) {
                $existingMap[(string)$existingRow['id']] = $existingRow;
            }
        }

        // 单条资源下载失败只跳过这一条，不能让整批回滚（2026-09-24：俏皮字幕演示视频约 28MB，站上下载 22s，超过 20s 超时，virtualman 被一起挡住）
        $failedIds = [];
        foreach ($templates as $item) {
            if (!is_array($item)) {
                $stats['skipped']++;
                continue;
            }

            $id = trim((string)($item['id'] ?? ''));
            $existing = $id !== '' ? ($existingMap[$id] ?? null) : null;
            // 内容指纹一致且本地素材路径齐备时跳过下载：模板中心每 5 分钟同步一次，
            // 稳态下不该反复拉取/HEAD 同一批封面与演示视频。
            $skipAssets = $existing !== null
                && (string)($existing['source_hash'] ?? '') === self::contentHash($item)
                && self::localAssetsReady($item, $existing);

            try {
                $row = self::normalizeTemplate($item, $stats, $skipAssets ? $existing : null);
            } catch (\Throwable $e) {
                $failedId = $id;
                if ($failedId !== '') {
                    $failedIds[] = $failedId;
                }
                $stats['failed']++;
                $stats['failed_items'][] = [
                    'id' => $failedId,
                    'name' => trim((string)($item['name'] ?? '')),
                    'error' => $e->getMessage(),
                ];
                continue;
            }
            if ($row === null) {
                $stats['skipped']++;
                continue;
            }

            $rows[$row['id']] = $row;
        }

        if (empty($rows)) {
            $reason = $stats['failed'] > 0
                ? '剪辑模板资源全部转存失败(' . $stats['failed'] . '条)，首条: ' . $stats['failed_items'][0]['error']
                : '剪辑模板为空';
            throw new Exception($reason . '，已跳过清洗，避免误删本地数据');
        }

        Db::startTrans();
        try {
            $keepIds = array_values(array_unique(array_merge(array_keys($rows), $failedIds)));

            $now = time();
            foreach ($rows as $id => $row) {
                $existing = $existingMap[$id] ?? null;

                if ($existing === null) {
                    $row['create_time'] = $row['create_time'] ?: $now;
                    $row['update_time'] = $now;
                    // status 走列默认值 1；sort_override 保持 NULL（跟随模板中心）
                    ShanjianClipTemplate::create($row);
                    $stats['created']++;
                    continue;
                }

                $existingCreateTime = (int)($existing['create_time'] ?? 0);
                $existingUpdateTime = (int)($existing['update_time'] ?? 0);
                $changed = self::hasContentChanged($existing, $row);
                // 历史数据时间戳为 0（中台就返回 '0'）时补一次当前时间，否则将永远停留在 0
                $needsTimeBackfill = $existingCreateTime <= 0 || $existingUpdateTime <= 0;
                // 升级后首次同步需要把指纹落库，否则下一轮仍会走素材探活
                $needsHashBackfill = (string)($existing['source_hash'] ?? '') !== (string)($row['source_hash'] ?? '');

                if (!$changed && !$needsTimeBackfill && !$needsHashBackfill) {
                    $stats['unchanged']++;
                    continue;
                }

                // create_time 只认第一次：中台有值优先，其次沿用本地已有值，最后才补当前时间
                $row['create_time'] = $row['create_time'] ?: ($existingCreateTime ?: $now);
                // 内容有变化才刷新 update_time；仅补空值/指纹时沿用已有值
                $row['update_time'] = $changed ? $now : ($existingUpdateTime ?: $now);

                // 只写内容字段与指纹，绝不带 status / sort_override——这两个是本站覆盖位
                ShanjianClipTemplate::where('id', $id)->update($row);
                if ($changed) {
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            }

            // 模板中心只下发在架行，远端缺失即视为彻底下架，连本站覆盖位一起清掉
            $stats['deleted'] = ShanjianClipTemplate::whereNotIn('id', $keepIds)->delete();
            $stats['total'] = count($rows);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $stats;
    }

    /** 远端内容指纹：只算模板中心下发的字段，不含本地转存后的素材路径 */
    private static function contentHash(array $item): string
    {
        $payload = [];
        foreach (['name', 'cover_url', 'scene', 'demo_url', 'auto_type', 'sort'] as $field) {
            $payload[$field] = (string)($item[$field] ?? '');
        }

        return md5(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 本地已转过存的行，素材路径必须齐备，否则远端有地址时仍需重新下载。
     * 本地存储再确认一次文件真实存在（is_file 不产生网络开销）——素材文件被清理掉时
     * 指纹虽然没变，也必须重下一份，不能让模板一直指向空路径。
     */
    private static function localAssetsReady(array $item, array $existing): bool
    {
        $localStorage = self::getStorageDefault() === 'local';

        foreach (['cover_url', 'demo_url'] as $field) {
            $remote = trim((string)($item[$field] ?? ''));
            if ($remote === '') {
                continue;
            }

            $localPath = trim((string)($existing[$field] ?? ''));
            if ($localPath === '') {
                return false;
            }

            // 远端存储只信任库里已转存的路径，探活（HEAD）留给指纹变化时做，别每轮白跑
            if ($localStorage) {
                // public_path($path) 会带结尾分隔符，is_file 必须用无尾分隔符的路径
                $absolutePath = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($localPath, '/\\');
                if (!is_file($absolutePath) || filesize($absolutePath) <= 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /** 只比对业务内容字段，时间戳不参与，避免中台返回 '0' 导致每轮都判定为「有变化」 */
    private static function hasContentChanged(array $existing, array $row): bool
    {
        foreach (self::CONTENT_FIELDS as $field) {
            if ((string)($existing[$field] ?? '') !== (string)($row[$field] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array|null $reuseRow 内容指纹未变时的本地行，直接沿用其已转存的素材路径
     */
    private static function normalizeTemplate(array $item, array &$stats, ?array $reuseRow = null): ?array
    {
        $id = trim((string)($item['id'] ?? ''));
        $name = trim((string)($item['name'] ?? ''));
        $scene = trim((string)($item['scene'] ?? ''));

        if ($id === '' || $name === '' || $scene === '' || !in_array($scene, self::ALLOWED_SCENES, true)) {
            return null;
        }

        $coverUrl = trim((string)($item['cover_url'] ?? ''));
        $demoUrl = trim((string)($item['demo_url'] ?? ''));

        return [
            'id' => $id,
            'name' => $name,
            'cover_url' => $reuseRow !== null
                ? (string)($reuseRow['cover_url'] ?? '')
                : self::downloadTemplateAsset($coverUrl, 'image', $stats),
            'scene' => $scene,
            'demo_url' => $reuseRow !== null
                ? (string)($reuseRow['demo_url'] ?? '')
                : self::downloadTemplateAsset($demoUrl, 'video', $stats),
            'auto_type' => (int)($item['auto_type'] ?? 0),
            // 中台对部分模板不下发 sort（键直接缺失），归 0 排在最后
            'sort' => max(0, (int)($item['sort'] ?? 0)),
            'source_hash' => self::contentHash($item),
            // 原样带出中台值(缺失/为 '0' 都归零)，由 syncFromPayload 统一决定最终写入的时间戳
            'create_time' => max(0, (int)($item['create_time'] ?? 0)),
            'update_time' => max(0, (int)($item['update_time'] ?? 0)),
        ];
    }

    /** 资源本地化（玩法模板同步复用，传自己的 $baseDir 落到独立目录） */
    public static function downloadTemplateAsset(string $url, string $type, array &$stats, string $baseDir = 'uploads/clip_template'): string
    {
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        $extension = self::resolveExtension($url, $type);
        $typePath = $type === 'video' ? 'videos' : 'images';
        $relativeDir = $baseDir . '/' . $typePath;
        $filename = sha1($url) . '.' . $extension;
        $relativePath = $relativeDir . '/' . $filename;
        $absoluteDir = rtrim(public_path($relativeDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $absolutePath = $absoluteDir . $filename;
        $storageDefault = self::getStorageDefault();

        if ($storageDefault !== 'local' && self::remoteAssetExists($relativePath)) {
            $stats['reused']++;
            return $relativePath;
        }

        if ($storageDefault === 'local' && is_file($absolutePath) && filesize($absolutePath) > 0) {
            $stats['reused']++;
            return $relativePath;
        }

        if (!is_file($absolutePath) || filesize($absolutePath) <= 0) {
            if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                throw new Exception('无法创建闪剪模板资源目录: ' . $absoluteDir);
            }

            $content = self::fetchRemoteContentWithRetry($url);
            if ($content === '') {
                throw new Exception('闪剪模板资源下载失败: ' . $url);
            }

            if (file_put_contents($absolutePath, $content) === false || !is_file($absolutePath) || filesize($absolutePath) <= 0) {
                @unlink($absolutePath);
                throw new Exception('闪剪模板资源保存失败: ' . $url);
            }

            $stats['downloaded']++;
        }

        if ($storageDefault !== 'local') {
            self::uploadAssetToStorage($absolutePath, $relativeDir, $filename, $relativePath);
            $stats['uploaded']++;
            @unlink($absolutePath);
        }

        return $relativePath;
    }

    private static function getStorageDefault(): string
    {
        return (string)(ConfigService::get('storage', 'default', 'local') ?: 'local');
    }

    private static function makeStorageDriver(): StorageDriver
    {
        return new StorageDriver([
            'default' => self::getStorageDefault(),
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ]);
    }

    private static function uploadAssetToStorage(string $absolutePath, string $relativeDir, string $filename, string $relativePath): void
    {
        $storage = self::makeStorageDriver();
        $storage->setUploadFileByFileName($absolutePath, $filename);
        if (!$storage->upload($relativeDir)) {
            throw new Exception('闪剪模板资源上传存储失败: ' . $relativePath . ' error=' . $storage->getError());
        }
    }

    private static function remoteAssetExists(string $relativePath): bool
    {
        $url = FileService::getFileUrl($relativePath, '', true);
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $statusCode >= 200 && $statusCode < 400;
    }

    private static function resolveExtension(string $url, string $type): string
    {
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: '';

        $allowed = $type === 'video'
            ? ['mp4', 'mov']
            : ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowed, true)) {
            return $type === 'video' ? 'mp4' : 'jpg';
        }

        return $extension;
    }

    private static function fetchRemoteContentWithRetry(string $url): string
    {
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $content = self::fetchRemoteContent($url);
            if ($content !== '') {
                return $content;
            }

            if ($attempt < $maxAttempts) {
                usleep(300000);
            }
        }

        return '';
    }

    private static function fetchRemoteContent(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $content = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || ($statusCode >= 400 && $statusCode !== 0)) {
            return '';
        }

        return (string)$content;
    }
}
