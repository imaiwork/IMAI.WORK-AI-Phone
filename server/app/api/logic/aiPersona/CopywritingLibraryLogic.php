<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibrary;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryPlatformUse;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryUseLog;
use app\common\service\SpreadsheetService;
use app\common\service\aiPersona\IdRoundRobinPicker;
use think\facade\Db;

class CopywritingLibraryLogic extends ApiLogic
{
    const USE_MODE_RANDOM = 1;
    const USE_MODE_SEQUENCE = 2;

    const REUSE_MODE_ONCE = 1;
    const REUSE_MODE_REPEAT = 2;

    public static function add(array $params): bool
    {
        try {
            $data = self::normalizeSaveData($params, self::$uid, AiPersonaCopywritingLibrary::SOURCE_MANUAL);
            if (self::isDuplicate($data)) {
                self::setError('相同文案已存在');
                return false;
            }

            $library = AiPersonaCopywritingLibrary::create($data);
            self::$returnData = $library->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function batchAdd(array $params): bool
    {
        try {
            $personaId = (int)($params['persona_id'] ?? 0);
            self::assertPersona($personaId, self::$uid);

            $items = $params['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                self::setError('请填写要添加的文案');
                return false;
            }

            $success = 0;
            $fail = 0;
            $failList = [];
            $insertRows = [];
            $seen = [];

            foreach (array_values($items) as $index => $item) {
                if (!is_array($item)) {
                    $fail++;
                    $failList[] = ['index' => $index, 'reason' => '文案格式不正确'];
                    continue;
                }
                try {
                    $item['persona_id'] = $personaId;
                    $item['library_type'] = $params['library_type'] ?? 0;
                    $item['driver_type'] = $params['driver_type'] ?? AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE;
                    $data = self::normalizeSaveData($item, self::$uid, AiPersonaCopywritingLibrary::SOURCE_MANUAL);
                    $fingerprint = self::fingerprint($data);
                    if (isset($seen[$fingerprint]) || self::isDuplicate($data)) {
                        throw new \Exception('相同文案已存在');
                    }
                    $seen[$fingerprint] = true;
                    $insertRows[] = $data;
                    $success++;
                } catch (\Throwable $e) {
                    $fail++;
                    $failList[] = ['index' => $index, 'reason' => $e->getMessage()];
                }
            }

            if (!empty($insertRows)) {
                AiPersonaCopywritingLibrary::insertAll($insertRows);
            }

            self::$returnData = [
                'success' => $success,
                'fail' => $fail,
                'fail_list' => $failList,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params): bool
    {
        try {
            $library = AiPersonaCopywritingLibrary::where('id', (int)$params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($library->isEmpty()) {
                self::setError('文案不存在');
                return false;
            }

            $merged = array_merge($library->toArray(), $params);
            $data = self::normalizeSaveData($merged, self::$uid, (int)$library->source);
            $data['id'] = (int)$library->id;
            if (self::isDuplicate($data, (int)$library->id)) {
                self::setError('相同文案已存在');
                return false;
            }

            unset($data['id'], $data['user_id'], $data['source'], $data['create_time'], $data['use_count'], $data['last_used_time']);
            $data['update_time'] = time();
            $library->save($data);
            self::$returnData = $library->refresh()->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $library = AiPersonaCopywritingLibrary::where('id', (int)$params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($library->isEmpty()) {
                self::setError('文案不存在');
                return false;
            }

            self::$returnData = $library->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function del(array $ids): bool
    {
        try {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
                return $id > 0;
            })));
            if (empty($ids)) {
                self::setError('请选择要删除的文案');
                return false;
            }

            $delIds = AiPersonaCopywritingLibrary::whereIn('id', $ids)
                ->where('user_id', self::$uid)
                ->column('id');
            if (empty($delIds)) {
                self::setError('文案不存在');
                return false;
            }

            AiPersonaCopywritingLibrary::destroy($delIds);
            self::$returnData = ['id' => array_values($delIds)];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function import(array $params, string $fileUrl): bool
    {
        $tempFile = '';
        try {
            self::assertPersona((int)$params['persona_id'], self::$uid);
            $tempFile = self::downloadImportFile($fileUrl);
            $spreadsheet = SpreadsheetService::load($tempFile);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $expectedHeaders = self::expectedHeaders((int)$params['library_type'], (int)$params['driver_type']);
            $headerErrors = self::validateHeaders($rows[4] ?? [], $expectedHeaders);
            $startRow = 5;
            $warnings = [];
            if (!empty($headerErrors)) {
                if ((int)$params['driver_type'] === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS
                    && !self::containsExpectedHeader($rows[4] ?? [], $expectedHeaders)) {
                    $startRow = 1;
                    $warnings[] = '未检测到模板表头，已按每行一组新闻标题导入';
                } else {
                    self::setError(implode('；', $headerErrors));
                    return false;
                }
            }

            $success = 0;
            $fail = 0;
            $failList = [];
            $insertRows = [];
            $seen = [];
            $now = time();

            foreach ($rows as $rowNo => $row) {
                if ($rowNo < $startRow) {
                    continue;
                }
                if (self::rowIsEmpty($row)) {
                    continue;
                }
                try {
                    $data = self::buildImportRow($params, $row, self::$uid, $now);
                    $fingerprint = self::fingerprint($data);
                    if (isset($seen[$fingerprint]) || self::isDuplicate($data)) {
                        throw new \Exception('相同文案已存在');
                    }
                    $seen[$fingerprint] = true;
                    $insertRows[] = $data;
                    $success++;
                } catch (\Throwable $e) {
                    $fail++;
                    $failList[] = [
                        'row' => $rowNo,
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            if (!empty($insertRows)) {
                AiPersonaCopywritingLibrary::insertAll($insertRows);
            }

            self::$returnData = [
                'success' => $success,
                'fail' => $fail,
                'fail_list' => $failList,
                'warnings' => $warnings,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        } finally {
            if ($tempFile !== '' && is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private static function downloadImportFile(string $url): string
    {
        $url = trim($url);
        $urlParts = parse_url($url);
        if (!is_array($urlParts) || empty($urlParts['scheme']) || empty($urlParts['host'])) {
            throw new \Exception('导入文件链接格式不正确');
        }

        $scheme = strtolower((string)$urlParts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \Exception('导入文件链接仅支持 HTTP 或 HTTPS');
        }
        if (isset($urlParts['user']) || isset($urlParts['pass'])) {
            throw new \Exception('导入文件链接不能包含认证信息');
        }

        $extension = strtolower(pathinfo((string)($urlParts['path'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            throw new \Exception('仅支持 xls、xlsx 格式的导入文件');
        }

        $host = trim((string)$urlParts['host'], '[]');
        $ip = self::resolvePublicIp($host);
        $port = isset($urlParts['port']) ? (int)$urlParts['port'] : ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) {
            throw new \Exception('导入文件链接端口不受支持');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'copywriting_import_');
        if ($tempFile === false) {
            throw new \Exception('创建导入临时文件失败');
        }

        $fp = fopen($tempFile, 'wb');
        if ($fp === false) {
            @unlink($tempFile);
            throw new \Exception('创建导入临时文件失败');
        }

        $maxBytes = 20 * 1024 * 1024;
        $downloadedBytes = 0;
        $fileTooLarge = false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'IMAICC-Copywriting-Importer/1.0',
            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $ip)],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $data) use ($fp, $maxBytes, &$downloadedBytes, &$fileTooLarge) {
                $length = strlen($data);
                $downloadedBytes += $length;
                if ($downloadedBytes > $maxBytes) {
                    $fileTooLarge = true;
                    return 0;
                }
                return fwrite($fp, $data);
            },
        ]);

        $success = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($fileTooLarge) {
            @unlink($tempFile);
            throw new \Exception('导入文件不能超过20MB');
        }
        if ($success === false || $httpCode !== 200) {
            @unlink($tempFile);
            throw new \Exception($httpCode >= 300 && $httpCode < 400
                ? '导入文件链接不允许重定向'
                : '下载导入文件失败' . ($curlError !== '' ? '：' . $curlError : "（HTTP {$httpCode}）"));
        }
        if (!is_file($tempFile) || filesize($tempFile) === 0) {
            @unlink($tempFile);
            throw new \Exception('导入文件为空');
        }

        return $tempFile;
    }

    private static function resolvePublicIp(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $ips = gethostbynamel($host) ?: [];
        }
        if (empty($ips)) {
            throw new \Exception('导入文件域名解析失败');
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        throw new \Exception('导入文件链接不能指向内网地址');
    }

    public static function consumeVideoCopywriting(int $userId, int $personaId, int $shanjianType, $config): ?array
    {
        $driverType = AiPersonaCopywritingLibrary::driverTypeByShanjianType($shanjianType);
        return self::consumeCopywriting(
            $userId,
            $personaId,
            AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER,
            $driverType,
            (int)self::configValue($config, 'library_use_mode', self::USE_MODE_RANDOM),
            (int)self::configValue($config, 'library_reuse_mode', self::REUSE_MODE_ONCE)
        );
    }

    public static function consumePublishCopywriting(int $userId, int $personaId, $config, int $platform): ?array
    {
        return self::consumePublishCopywritingByPlatform(
            $userId,
            $personaId,
            $platform,
            (int)self::configValue($config, 'library_use_mode', self::USE_MODE_RANDOM),
            (int)self::configValue($config, 'library_reuse_mode', self::REUSE_MODE_ONCE)
        );
    }

    /**
     * 内容发布某平台的 generate_mode / library_use_mode / library_reuse_mode 变更时，
     * 仅清零该平台的发布文案使用次数，不影响其他平台。
     */
    public static function resetPublishPlatformUseCounts(int $personaId, array $platforms): void
    {
        $platforms = array_values(array_unique(array_filter(array_map('intval', $platforms))));
        if ($personaId <= 0 || empty($platforms)) {
            return;
        }

        AiPersonaCopywritingLibraryPlatformUse::where('persona_id', $personaId)
            ->whereIn('platform', $platforms)
            ->update([
                'use_count' => 0,
                'last_used_time' => 0,
                'update_time' => time(),
            ]);
    }

    /**
     * AI合成规则文案来源/使用方式/随机规则变更时，重置该人设视频驱动文案库使用次数。
     */
    public static function resetVideoDriverUseCounts(int $personaId): void
    {
        if ($personaId <= 0) {
            return;
        }

        AiPersonaCopywritingLibrary::where('persona_id', $personaId)
            ->where('library_type', AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER)
            ->update([
                'use_count' => 0,
                'last_used_time' => 0,
                'update_time' => time(),
            ]);
    }

    /**
     * 合成配置文案库相关规则是否变更（文案来源/使用方式/随机规则）。
     */
    public static function hasVideoLibraryRuleChanged($oldConfig, $newConfig): bool
    {
        $oldSource = (int)self::configValue($oldConfig, 'copywriting_source', 0);
        $newSource = (int)self::configValue($newConfig, 'copywriting_source', 0);
        $oldUseMode = (int)self::configValue($oldConfig, 'library_use_mode', self::USE_MODE_RANDOM);
        $newUseMode = (int)self::configValue($newConfig, 'library_use_mode', self::USE_MODE_RANDOM);
        $oldReuseMode = (int)self::configValue($oldConfig, 'library_reuse_mode', self::REUSE_MODE_ONCE);
        $newReuseMode = (int)self::configValue($newConfig, 'library_reuse_mode', self::REUSE_MODE_ONCE);

        return $oldSource !== $newSource
            || $oldUseMode !== $newUseMode
            || $oldReuseMode !== $newReuseMode;
    }

    /**
     * 对比新旧内容发布配置，返回「生成方式/使用方式/随机规则」有变化的平台列表。
     */
    public static function getChangedPublishLibraryRulePlatforms($oldConfig, $newConfig): array
    {
        $oldConfig = AiPersona::normalizeContentPublishConfig($oldConfig);
        $newConfig = AiPersona::normalizeContentPublishConfig($newConfig);
        $changed = [];

        foreach (AiPersona::CONTENT_PUBLISH_PLATFORMS as $platform) {
            $old = AiPersona::getPlatformContentPublishConfig($oldConfig, (int)$platform);
            $new = AiPersona::getPlatformContentPublishConfig($newConfig, (int)$platform);
            if (
                (int)$old['generate_mode'] !== (int)$new['generate_mode']
                || (int)$old['library_use_mode'] !== (int)$new['library_use_mode']
                || (int)$old['library_reuse_mode'] !== (int)$new['library_reuse_mode']
            ) {
                $changed[] = (int)$platform;
            }
        }

        return $changed;
    }

    private static function configValue($config, string $key, $default)
    {
        if (is_array($config)) {
            return $config[$key] ?? $default;
        }
        if (is_object($config)) {
            return $config->{$key} ?? $default;
        }
        return $default;
    }

    /**
     * 任务创建失败时回滚文案占用次数（仅减少 use_count，不删除使用日志）
     * @param int $platform 发布场景传入平台；视频驱动传 0，回滚文案库全局 use_count
     */
    public static function revertCopywritingUse(int $libraryId, int $platform = 0): void
    {
        if ($libraryId <= 0) {
            return;
        }

        try {
            if ($platform > 0) {
                $stat = AiPersonaCopywritingLibraryPlatformUse::where('library_id', $libraryId)
                    ->where('platform', $platform)
                    ->lock(true)
                    ->findOrEmpty();
                if ($stat->isEmpty()) {
                    return;
                }
                $stat->use_count = max(0, (int)$stat->use_count - 1);
                if ((int)$stat->use_count === 0) {
                    $stat->last_used_time = 0;
                }
                $stat->update_time = time();
                $stat->save();
                return;
            }

            $library = AiPersonaCopywritingLibrary::where('id', $libraryId)->lock(true)->findOrEmpty();
            if ($library->isEmpty()) {
                return;
            }

            $library->use_count = max(0, (int)$library->use_count - 1);
            if ((int)$library->use_count === 0) {
                $library->last_used_time = 0;
            }
            $library->update_time = time();
            $library->save();
        } catch (\Throwable $e) {
        }
    }

    public static function recordUse(int $libraryId, int $scene, array $context = []): void
    {
        if ($libraryId <= 0) {
            return;
        }

        try {
            $library = AiPersonaCopywritingLibrary::where('id', $libraryId)->findOrEmpty();
            if ($library->isEmpty()) {
                return;
            }

            AiPersonaCopywritingLibraryUseLog::create([
                'library_id' => (int)$library->id,
                'user_id' => (int)$library->user_id,
                'persona_id' => (int)$library->persona_id,
                'scene' => $scene,
                'device_code' => (string)($context['device_code'] ?? ''),
                'related_video_task_id' => (int)($context['related_video_task_id'] ?? 0),
                'related_publish_detail_id' => (int)($context['related_publish_detail_id'] ?? 0),
                'task_id' => (string)($context['task_id'] ?? ''),
                'platform' => (int)($context['platform'] ?? 0),
                'shanjian_type' => (int)($context['shanjian_type'] ?? 0),
                'title' => (string)$library->title,
                'content' => (string)$library->content,
                'topic' => (string)$library->topic,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private static function consumeCopywriting(
        int $userId,
        int $personaId,
        int $libraryType,
        int $driverType,
        int $useMode,
        int $reuseMode
    ): ?array {
        Db::startTrans();
        try {
            $query = AiPersonaCopywritingLibrary::where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('library_type', $libraryType)
                ->where('driver_type', $driverType)
                ->where('status', AiPersonaCopywritingLibrary::STATUS_ENABLED);

            // 顺序使用：按 id 轮询（查上次用的 id，取下一条），与随机规则无关
            // 随机使用：才受「只用一次 / 可重复」约束
            if ($useMode === self::USE_MODE_SEQUENCE) {
                $items = $query->order(['id' => 'asc'])->lock(true)->select()->toArray();
                if (empty($items)) {
                    Db::rollback();
                    return null;
                }
                $lastUsedId = self::resolveLastUsedLibraryIdFromItems($items);
                $picked = IdRoundRobinPicker::pickNext($items, $lastUsedId);
                if (empty($picked)) {
                    Db::rollback();
                    return null;
                }
                $library = AiPersonaCopywritingLibrary::where('id', (int)$picked['id'])->lock(true)->findOrEmpty();
            } else {
                if ($reuseMode === self::REUSE_MODE_ONCE) {
                    $query->where('use_count', 0);
                }
                $library = $query->orderRaw('rand()')->lock(true)->findOrEmpty();
            }

            if ($library->isEmpty()) {
                Db::rollback();
                return null;
            }

            $library->use_count = (int)$library->use_count + 1;
            $library->last_used_time = time();
            $library->update_time = time();
            $library->save();
            $data = $library->toArray();
            Db::commit();
            return $data;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 发布文案按平台独立计数。
     * 顺序使用：按 id 轮询（该平台上次用的 id 的下一条）；随机使用才受「只用一次」约束。
     */
    private static function consumePublishCopywritingByPlatform(
        int $userId,
        int $personaId,
        int $platform,
        int $useMode,
        int $reuseMode
    ): ?array {
        if ($platform <= 0) {
            return null;
        }

        Db::startTrans();
        try {
            $query = AiPersonaCopywritingLibrary::where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('library_type', AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH)
                ->where('driver_type', AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE)
                ->where('status', AiPersonaCopywritingLibrary::STATUS_ENABLED);

            if ($useMode === self::USE_MODE_SEQUENCE) {
                $items = $query->order(['id' => 'asc'])->lock(true)->select()->toArray();
                if (empty($items)) {
                    Db::rollback();
                    return null;
                }
                $lastUsedId = self::getLastUsedPublishLibraryId($personaId, $platform);
                $picked = IdRoundRobinPicker::pickNext($items, $lastUsedId);
                if (empty($picked)) {
                    Db::rollback();
                    return null;
                }
                $library = AiPersonaCopywritingLibrary::where('id', (int)$picked['id'])->lock(true)->findOrEmpty();
            } else {
                if ($reuseMode === self::REUSE_MODE_ONCE) {
                    $usedIds = AiPersonaCopywritingLibraryPlatformUse::where('persona_id', $personaId)
                        ->where('platform', $platform)
                        ->where('use_count', '>', 0)
                        ->column('library_id');
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                }
                $library = $query->orderRaw('rand()')->lock(true)->findOrEmpty();
            }

            if ($library->isEmpty()) {
                Db::rollback();
                return null;
            }

            $now = time();
            $stat = AiPersonaCopywritingLibraryPlatformUse::where('library_id', (int)$library->id)
                ->where('platform', $platform)
                ->lock(true)
                ->findOrEmpty();

            $platformUseCount = 1;
            if ($stat->isEmpty()) {
                AiPersonaCopywritingLibraryPlatformUse::create([
                    'library_id' => (int)$library->id,
                    'user_id' => $userId,
                    'persona_id' => $personaId,
                    'platform' => $platform,
                    'use_count' => 1,
                    'last_used_time' => $now,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            } else {
                // 仅随机 + 只用一次时拒绝复用；顺序使用始终可继续累加
                if (
                    $useMode === self::USE_MODE_RANDOM
                    && $reuseMode === self::REUSE_MODE_ONCE
                    && (int)$stat->use_count > 0
                ) {
                    Db::rollback();
                    return null;
                }
                $platformUseCount = (int)$stat->use_count + 1;
                $stat->use_count = $platformUseCount;
                $stat->last_used_time = $now;
                $stat->update_time = $now;
                $stat->save();
            }

            $data = $library->toArray();
            $data['platform'] = $platform;
            $data['platform_use_count'] = $platformUseCount;
            Db::commit();
            return $data;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 从候选文案中解析「最近一次使用」的 id（合成视频文案库游标）
     */
    private static function resolveLastUsedLibraryIdFromItems(array $items): int
    {
        $lastUsedId = 0;
        $lastUsedTime = 0;
        foreach ($items as $item) {
            $usedTime = (int)($item['last_used_time'] ?? 0);
            $id = (int)($item['id'] ?? 0);
            if ($usedTime <= 0 || $id <= 0) {
                continue;
            }
            if ($usedTime > $lastUsedTime || ($usedTime === $lastUsedTime && $id > $lastUsedId)) {
                $lastUsedTime = $usedTime;
                $lastUsedId = $id;
            }
        }
        return $lastUsedId;
    }

    /**
     * 发布文案库：某平台最近一次使用的文案 id
     */
    private static function getLastUsedPublishLibraryId(int $personaId, int $platform): int
    {
        return (int)AiPersonaCopywritingLibraryPlatformUse::where('persona_id', $personaId)
            ->where('platform', $platform)
            ->where('use_count', '>', 0)
            ->order(['last_used_time' => 'desc', 'library_id' => 'desc'])
            ->value('library_id');
    }

    private static function normalizeSaveData(array $params, int $userId, int $source): array
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        self::assertPersona($personaId, $userId);

        $libraryType = (int)($params['library_type'] ?? 0);
        $driverType = (int)($params['driver_type'] ?? AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE);
        $title = self::cleanText($params['title'] ?? '');
        $content = self::cleanText($params['content'] ?? '');
        $topic = self::cleanText($params['topic'] ?? '');

        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            $driverType = AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE;
            if ($title === '' && $content === '' && $topic === '') {
                throw new \Exception('发布文案标题、内容、话题至少填写一项');
            }
        } elseif ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER) {
            if (!in_array($driverType, [
                AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS,
                AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL,
                AiPersonaCopywritingLibrary::DRIVER_TYPE_MATERIAL_MIXCUT,
            ], true)) {
                throw new \Exception('请选择视频驱动文案类型');
            }
            $topic = '';
            if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
                $title = self::normalizeNewsTitleGroup($title);
                $content = '';
            } elseif ($title === '' || $content === '') {
                throw new \Exception('视频制作标题和口播内容均为必填');
            }
        } else {
            throw new \Exception('文案库类型值不正确');
        }

        $now = time();
        return [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'library_type' => $libraryType,
            'driver_type' => $driverType,
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
            'source' => $source,
            'sort' => (int)($params['sort'] ?? 0),
            'status' => isset($params['status']) ? (int)$params['status'] : AiPersonaCopywritingLibrary::STATUS_ENABLED,
            'use_count' => (int)($params['use_count'] ?? 0),
            'last_used_time' => (int)($params['last_used_time'] ?? 0),
            'create_time' => (int)($params['create_time'] ?? $now),
            'update_time' => $now,
        ];
    }

    private static function buildImportRow(array $params, array $row, int $userId, int $now): array
    {
        $libraryType = (int)$params['library_type'];
        $driverType = (int)$params['driver_type'];

        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            $raw = [
                'persona_id' => $params['persona_id'],
                'library_type' => $libraryType,
                'driver_type' => AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE,
                'title' => self::cleanText($row['A'] ?? ''),
                'content' => self::cleanText($row['B'] ?? ''),
                'topic' => self::cleanText($row['C'] ?? ''),
                'create_time' => $now,
            ];
        } elseif ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            $titles = self::parseNewsTitlesFromRow($row);
            $raw = [
                'persona_id' => $params['persona_id'],
                'library_type' => $libraryType,
                'driver_type' => $driverType,
                'title' => implode("\n", $titles),
                'content' => '',
                'topic' => '',
                'create_time' => $now,
            ];
        } else {
            $raw = [
                'persona_id' => $params['persona_id'],
                'library_type' => $libraryType,
                'driver_type' => $driverType,
                'title' => self::cleanText($row['A'] ?? ''),
                'content' => self::cleanText($row['B'] ?? ''),
                'topic' => '',
                'create_time' => $now,
            ];
        }

        return self::normalizeSaveData($raw, $userId, AiPersonaCopywritingLibrary::SOURCE_IMPORT);
    }

    private static function expectedHeaders(int $libraryType, int $driverType): array
    {
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            return ['A' => '视频发布标题', 'B' => '视频发布正文内容', 'C' => '视频话题'];
        }
        if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            return ['A' => '标题1', 'B' => '标题2', 'C' => '标题3', 'D' => '标题4', 'E' => '标题5', 'F' => '标题6'];
        }
        return ['A' => '视频制作标题', 'B' => '视频制作口播内容'];
    }

    private static function validateHeaders(array $row, array $expected): array
    {
        $errors = [];
        foreach ($expected as $col => $header) {
            if (self::cleanText($row[$col] ?? '') !== $header) {
                $errors[] = '第4行' . $col . '列表头应为' . $header;
            }
        }
        return $errors;
    }

    private static function containsExpectedHeader(array $row, array $expected): bool
    {
        $actualHeaders = array_map([self::class, 'cleanText'], $row);
        foreach ($expected as $header) {
            if (in_array($header, $actualHeaders, true)) {
                return true;
            }
        }
        return false;
    }

    private static function assertPersona(int $personaId, int $userId): void
    {
        if ($personaId <= 0) {
            throw new \Exception('请输入人设ID');
        }
        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \Exception('IP人设不存在');
        }
    }

    private static function isDuplicate(array $data, int $excludeId = 0): bool
    {
        $query = AiPersonaCopywritingLibrary::where('user_id', (int)$data['user_id'])
            ->where('persona_id', (int)$data['persona_id'])
            ->where('library_type', (int)$data['library_type'])
            ->where('driver_type', (int)$data['driver_type'])
            ->where('title', (string)$data['title'])
            ->where('content', (string)$data['content'])
            ->where('topic', (string)$data['topic']);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return !$query->findOrEmpty()->isEmpty();
    }

    private static function fingerprint(array $data): string
    {
        return implode('|', [
            (int)$data['persona_id'],
            (int)$data['library_type'],
            (int)$data['driver_type'],
            (string)$data['title'],
            (string)$data['content'],
            (string)$data['topic'],
        ]);
    }

    private static function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (self::cleanText($value) !== '') {
                return false;
            }
        }
        return true;
    }

    private static function normalizeNewsTitleGroup(string $title): string
    {
        $titles = self::splitNewsTitleCell($title);
        if (empty($titles)) {
            throw new \Exception('新闻体标题必须填写');
        }
        return implode("\n", array_slice($titles, 0, 6));
    }

    private static function parseNewsTitlesFromRow(array $row): array
    {
        $columnValues = [];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $value = self::cleanText($row[$col] ?? '');
            if ($value !== '') {
                $columnValues[] = $value;
            }
        }

        if (empty($columnValues)) {
            return [];
        }

        $titles = [];
        if (count($columnValues) >= 2) {
            foreach ($columnValues as $value) {
                $titles = array_merge($titles, self::splitNewsTitleCell($value));
            }
        } else {
            $titles = self::splitNewsTitleCell($columnValues[0]);
        }

        $titles = array_values(array_filter(array_map([self::class, 'cleanText'], $titles), static fn($value) => $value !== ''));
        return array_slice($titles, 0, 6);
    }

    private static function splitNewsTitleCell(string $value): array
    {
        $value = self::cleanText($value);
        if ($value === '') {
            return [];
        }

        $titles = [];
        foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
            $line = self::cleanText($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/[,，、;；]/u', $line)) {
                foreach (preg_split('/[,，、;；]+/u', $line) as $part) {
                    $part = self::cleanText($part);
                    if ($part !== '') {
                        $titles[] = $part;
                    }
                }
                continue;
            }
            $titles[] = $line;
        }

        return $titles;
    }

  

    private static function cleanText($value): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", (string)$value));
    }
}
