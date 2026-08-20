<?php

declare(strict_types=1);

namespace app\common\service\station;

use app\common\service\ConfigService;
use Exception;
use think\facade\Log;

/**
 * imai v2.10.0 站长中台迁移：旧中台导出 → 新中台导入
 */
class StationV3MigrationService
{
    private const CONFIG_TYPE = 'station_migration';
    private const CONFIG_NAME = 'v3';

    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';

    private const LEGACY_EXPORT_PATH = '/api/station/migration/v3-info';
    private const NEW_IMPORT_PATH = '/api/station/migration/v3-import';

    public static function isV300SqlFile(string $filename): bool
    {
        $filename = basename($filename);
        if (preg_match('/^v(\d+)\.(\d+)\.(\d+)(?:-\d+)?\.sql$/i', $filename, $matches)) {
            return (int)$matches[1] === 2 && (int)$matches[2] === 10 && (int)$matches[3] === 0;
        }

        return false;
    }

    public static function markSqlExecuted(): void
    {
        $state = self::loadState();
        if ((int)($state['sql_executed_at'] ?? 0) > 0) {
            return;
        }
        $state['sql_executed_at'] = time();
        self::saveState($state);
    }

    public static function isCompleted(): bool
    {
        $state = self::loadState();

        return ($state['status'] ?? '') === self::STATUS_COMPLETED;
    }

    public static function shouldShowRetry(): bool
    {
        if (self::isCompleted()) {
            return false;
        }
        $state = self::loadState();

        return ($state['status'] ?? '') === self::STATUS_FAILED
            && (int)($state['sql_executed_at'] ?? 0) > 0;
    }

    public static function getStatus(): array
    {
        $state = self::loadState();
        $status = (string)($state['status'] ?? '');

        return [
            'status'              => $status,
            'show_retry'          => self::shouldShowRetry(),
            'message'             => (string)($state['message'] ?? ''),
            'domain'              => (string)($state['domain'] ?? ''),
            'sql_executed_at'     => (int)($state['sql_executed_at'] ?? 0),
            'completed_at'        => (int)($state['completed_at'] ?? 0),
            'failed_at'           => (int)($state['failed_at'] ?? 0),
            'new_user_id'         => (int)($state['new_user_id'] ?? 0),
            'station_license_id'  => (int)($state['station_license_id'] ?? 0),
        ];
    }

    /**
     * @throws Exception
     */
    public static function run(): array
    {
        if (self::isCompleted()) {
            return array_merge(self::getStatus(), [
                'skipped' => true,
            ]);
        }

        try {
            $payload = self::exportFromLegacy();
            $import = self::importToNew($payload);
            self::markCompleted($payload, $import);

            return array_merge(self::getStatus(), [
                'skipped' => false,
            ]);
        } catch (Exception $e) {
            self::markFailed($e->getMessage());
            Log::error('站长 v3 中台迁移失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private static function exportFromLegacy(): array
    {
        $apiKey = trim((string)env('PROJECT_KEY.API_KEY', ''));
        $signKey = trim((string)env('PROJECT_KEY.SIGN_KEY', ''));
        $legacyApiUrl = self::legacyApiUrl();

        if ($legacyApiUrl === '') {
            throw new Exception('旧中台地址未配置');
        }
        if ($apiKey === '') {
            throw new Exception('站长 API 密钥未配置');
        }

        $body = ['key' => $apiKey];
        $headers = ['Key' => $apiKey];
        if ($signKey !== '') {
            $headers['Sign'] = hash_hmac('sha256', json_encode($body), $signKey);
        }

        $url = rtrim($legacyApiUrl, '/') . self::LEGACY_EXPORT_PATH;
        $response = self::ToolsCurlPostRequest($url, $body, 'post', $headers);
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new Exception('旧中台导出失败：' . (string)($response['message'] ?? '未知错误'));
        }
        if (!is_array($response['data'] ?? null) || $response['data'] === []) {
            throw new Exception('旧中台导出失败：响应数据为空');
        }

        return $response['data'];
    }

    /**
     * @throws Exception
     */
    private static function importToNew(array $payload): array
    {
        $newApiUrl = self::newApiUrl();
        if ($newApiUrl === '') {
            throw new Exception('新中台地址未配置');
        }

        $url = rtrim($newApiUrl, '/') . self::NEW_IMPORT_PATH;
        $response = self::ToolsCurlPostRequest($url, $payload, 'post', []);
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new Exception('新中台导入失败：' . (string)($response['message'] ?? '未知错误'));
        }
        if (!is_array($response['data'] ?? null)) {
            throw new Exception('新中台导入失败：响应数据为空');
        }

        return $response['data'];
    }

    private static function markCompleted(array $payload, array $import): void
    {
        $state = self::loadState();
        $now = time();
        $state['status'] = self::STATUS_COMPLETED;
        $state['completed_at'] = $now;
        $state['failed_at'] = 0;
        $state['message'] = '';
        $state['domain'] = (string)($payload['ym'] ?? $import['domain'] ?? $import['username'] ?? '');
        $state['new_user_id'] = (int)($import['user_id'] ?? 0);
        $state['station_license_id'] = (int)($import['station_license_id'] ?? 0);
        self::saveState($state);
    }

    private static function markFailed(string $message): void
    {
        $state = self::loadState();
        $state['status'] = self::STATUS_FAILED;
        $state['failed_at'] = time();
        $state['message'] = $message;
        self::saveState($state);
    }

    private static function loadState(): array
    {
        $state = ConfigService::get(self::CONFIG_TYPE, self::CONFIG_NAME, []);
        if (!is_array($state)) {
            return [];
        }

        return $state;
    }

    private static function saveState(array $state): void
    {
        ConfigService::set(self::CONFIG_TYPE, self::CONFIG_NAME, $state);
    }

    private static function legacyApiUrl(): string
    {
        $config = require root_path() . 'config/api_tools.php';

        return trim((string)($config['legacy_api_url'] ?? $config['api_url'] ?? ''));
    }

    private static function newApiUrl(): string
    {
        $config = require root_path() . 'config/api_tools.php';

        return trim((string)($config['new_api_url'] ?? ''));
    }

    /**
     * CURL 请求
     * @param string $url 请求地址
     * @param array $data 请求参数
     * @param string $method 请求类型
     * @param array $headers 请求头
     * @return array
     */
    private  static function ToolsCurlPostRequest(string $url, array $data = [], string $method = 'post', array $headers = []): array
    {

        $ch = curl_init();

        // 设置基本选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // 设置较长的连接超时，单位为秒
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // 设置为 0 表示不限制超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);

        // 规范化请求头，统一转换为标准格式（首字母大写）
        $normalizedHeaders = [];
        foreach ($headers as $key => $value) {
            $normalizedKey = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));
            $normalizedHeaders[$normalizedKey] = $value;
        }

        // 设置默认请求头
        $defaultHeaders = [
            'Content-Type' => 'application/json',
        ];

        // 合并请求头，确保用户设置的头信息优先
        $finalHeaders = array_merge($defaultHeaders, $normalizedHeaders);

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::ToolsFormatHeaders($finalHeaders));

        // 根据 Content-Type 处理请求数据
        $contentType = $finalHeaders['Content-Type'];
        if (
            stripos($contentType, 'multipart/form-data') !== false ||
            stripos($contentType, 'application/x-www-form-urlencoded') !== false
        ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data === [] ? '{}' : json_encode($data));
        }

        // Log::channel('human')->write('参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'/n 头部'.json_encode($contentType,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // 发送请求
        $response = curl_exec($ch);
        if (curl_errno($ch)) {

            // 打印错误信息
            return ['code' => 10001, 'message' => '您提交的信息似乎不太对哦', 'data' => []];
        }
        curl_close($ch);
        $responseJson = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if ($responseJson === null) {
                return ['code' => 10001, 'message' => '您提交的信息似乎不太对哦', 'data' => []];
            }
            return $responseJson;
        }
        // 打印错误信息
        return ['code' => 10001, 'message' => '您提交的信息似乎不太对哦', 'data' => []];
    }

    /**
     * 将 headers 格式化为 cURL 可以接受的格式
     */
    private static function ToolsFormatHeaders(array $headers): array
    {
        return array_map(
            fn($key, $value) => "$key: $value",
            array_keys($headers),
            $headers
        );
    }
}
