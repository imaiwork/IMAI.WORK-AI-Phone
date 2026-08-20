<?php

namespace app\common\service;

use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use think\facade\Log;

/**
 * 素材就绪检查
 * 用于生成视频前判断素材是否完成转码,避免下发后被第三方判定"素材不合格"
 */
class MaterialReadinessService
{
    /**
     * 转码状态最长等待时间默认值:5 天。
     */
    public const DEFAULT_TRANSCODE_STALE_SECONDS = 432000;

    /**
     * 生成视频下发前等待转码的默认最长时间:30 分钟。
     */
    public const DEFAULT_SUBMIT_TRANSCODE_STALE_SECONDS = 1800;

    /**
     * 检查指定人设可用于"生成视频"的视频素材是否全部完成转码
     *
     * @param int $personaId
     * @param int $userId
     * @return array{ready: bool, pending_uris: array<int, string>, failed_uris: array<int, string>}
     */
    public static function checkPersonaMaterials(int $personaId, int $userId): array
    {
        // 仅视频素材(material_type=1)需要转码;图片直接放行
        $fileUrls = Material::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->where('use_status', 1)
            ->where('publish_mode', 1)
            ->where('material_type', 1)
            ->column('file_url');

        if (empty($fileUrls)) {
            return ['ready' => true, 'pending_uris' => [], 'failed_uris' => []];
        }

        // 去重 + 过滤空值
        $fileUris = self::normalizeUris($fileUrls);
        if (empty($fileUris)) {
            return ['ready' => true, 'pending_uris' => [], 'failed_uris' => []];
        }

        self::markStaleTranscodesFailed($fileUris, self::getSubmitTranscodeStaleSeconds());

        $rows = self::latestRowsByUri($fileUris);

        $pending = [];
        $failed = [];
        foreach ($rows as $row) {
            $status = (int)$row['transcode_status'];
            if ($status === 4) {
                $failed[] = $row['uri'];
            } elseif (in_array($status, [1, 2], true)) {
                $pending[] = $row['uri'];
            }
        }

        // 语义:只要还在转码中(1/2)就阻塞;转码已彻底结束(3 成功 / 4 失败)都视为 ready
        // failed_uris 只是信息透出,下游业务决定怎么用(可能跳过坏素材、可能给清晰失败原因)
        return [
            'ready' => empty($pending),
            'pending_uris' => $pending,
            'failed_uris' => $failed,
        ];
    }

    /**
     * 给一组任意来源的 file_url 集合,返回其中"已就绪可用"的 uri set(关联数组,uri 为 key)
     * 含义:transcode_status ∈ {0,3} 视为可用 (0=无需转码也是合法的旧数据)
     * 未命中 file 表的 uri 默认放行(可能不是转码场景产生的素材)
     *
     * @param array<int, string> $fileUrls
     * @return array<string, bool>
     */
    public static function readyUriSet(array $fileUrls): array
    {
        $fileUris = self::normalizeUris($fileUrls);
        if (empty($fileUris)) {
            return [];
        }
        self::markStaleTranscodesFailed($fileUris, self::getSubmitTranscodeStaleSeconds());

        $ready = [];
        $rows = self::latestRowsByUri($fileUris);
        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[$row['uri']] = (int)$row['transcode_status'];
        }

        // 只把最新记录还在转码中的 uri 视为阻塞;未命中、成功或失败都放行
        foreach ($fileUris as $uri) {
            $status = $rowMap[$uri] ?? 0;
            if (!in_array($status, [1, 2], true)) {
                $ready[$uri] = true;
            }
        }
        return $ready;
    }

    /**
     * 第三方下发前严格检查:仍在转码则等待,转码失败则阻断。
     *
     * @param array<int, string> $fileUrls
     * @return array{ready: bool, pending_uris: array<int, string>, failed_uris: array<int, string>}
     */
    public static function checkFileUrlsForSubmit(array $fileUrls): array
    {
        $fileUris = self::normalizeUris($fileUrls);
        if (empty($fileUris)) {
            return ['ready' => true, 'pending_uris' => [], 'failed_uris' => []];
        }

        self::markStaleTranscodesFailed($fileUris, self::getSubmitTranscodeStaleSeconds());

        $rows = self::latestRowsByUri($fileUris);
        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[$row['uri']] = (int)$row['transcode_status'];
        }

        $pending = [];
        $failed = [];
        foreach ($fileUris as $uri) {
            if (!isset($rowMap[$uri])) {
                continue;
            }
            $status = $rowMap[$uri];
            if (in_array($status, [1, 2], true)) {
                $pending[] = $uri;
            } elseif ($status === 4) {
                $failed[] = $uri;
            } elseif (!in_array($status, [0, 3], true)) {
                $failed[] = $uri;
            }
        }

        return [
            'ready' => empty($pending) && empty($failed),
            'pending_uris' => $pending,
            'failed_uris' => $failed,
        ];
    }

    /**
     * 将长时间停留在待转码/转码中的素材收敛为失败,避免生成任务永久等待。
     *
     * @param array<int, string> $fileUrls
     * @return array<int, string>
     */
    public static function markStaleTranscodesFailed(array $fileUrls, ?int $staleSeconds = null): array
    {
        $uris = self::normalizeUris($fileUrls);
        if (empty($uris)) {
            return [];
        }

        $staleSeconds = $staleSeconds !== null && $staleSeconds > 0
            ? $staleSeconds
            : self::getTranscodeStaleSeconds();
        $deadline = time() - $staleSeconds;
        $staleRows = File::whereIn('uri', $uris)
            ->whereIn('transcode_status', [1, 2])
            ->where('update_time', '<=', $deadline)
            ->field('id,uri,transcode_status,update_time')
            ->select()
            ->toArray();
        if (empty($staleRows)) {
            return [];
        }

        $ids = array_column($staleRows, 'id');
        File::whereIn('id', $ids)->update([
            'transcode_status' => 4,
            'update_time' => time(),
        ]);

        $staleUris = array_column($staleRows, 'uri');
        Log::channel('ffmpeg')->write(
            "[素材就绪] 转码状态超时未收敛({$staleSeconds}s),已标记失败: "
            . json_encode($staleUris, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $staleUris;
    }

    private static function getTranscodeStaleSeconds(): int
    {
        $seconds = (int)config('transcoding.stale_seconds', self::DEFAULT_TRANSCODE_STALE_SECONDS);
        return $seconds > 0 ? $seconds : self::DEFAULT_TRANSCODE_STALE_SECONDS;
    }

    private static function getSubmitTranscodeStaleSeconds(): int
    {
        $seconds = (int)config('transcoding.submit_stale_seconds', self::DEFAULT_SUBMIT_TRANSCODE_STALE_SECONDS);
        return $seconds > 0 ? $seconds : self::DEFAULT_SUBMIT_TRANSCODE_STALE_SECONDS;
    }

    /**
     * @param array<int, string> $fileUrls
     * @return array<int, string>
     */
    private static function normalizeUris(array $fileUrls): array
    {
        $uris = [];
        foreach ($fileUrls as $url) {
            $uri = (string)$url;
            if (str_starts_with($uri, 'http')) {
                $path = parse_url($uri, PHP_URL_PATH) ?: '';
                $uri = ltrim($path, '/');
            } else {
                $uri = ltrim($uri, '/');
            }
            if ($uri !== '') {
                $uris[] = $uri;
            }
        }
        return array_values(array_unique($uris));
    }

    /**
     * 同一个 uri 可能因 AI 找素材重复入库多条 iw_file 记录。
     * 就绪判断只看最新记录,避免旧的待转码记录拖住已成功的新素材。
     *
     * @param array<int, string> $uris
     * @return array<int, array<string, mixed>>
     */
    private static function latestRowsByUri(array $uris): array
    {
        if (empty($uris)) {
            return [];
        }

        $rows = File::whereIn('uri', $uris)
            ->field('id,uri,transcode_status,update_time')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $latest = [];
        foreach ($rows as $row) {
            $uri = (string)($row['uri'] ?? '');
            if ($uri !== '' && !isset($latest[$uri])) {
                $latest[$uri] = $row;
            }
        }

        return array_values($latest);
    }
}
