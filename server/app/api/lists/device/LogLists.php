<?php

namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\enum\ExportEnum;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\sv\SvDeviceLog;
use app\common\service\FileService;
use app\common\workerman\rpa\WorkerEnum;

/**
 * 设备运行日志列表
 */
class LogLists extends BaseApiDataLists implements ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    private const CONTENT_TAG_JSON_PATHS = [
        '$.tag',
        '$.title',
        '$[0].tag',
        '$[0].title',
    ];

    private const CONTENT_KEYWORD_JSON_PATHS = [
        '$.log',
        '$.msg',
        '$.message',
        '$.tag',
        '$.title',
        '$[0].log',
        '$[0].msg',
        '$[0].message',
        '$[0].tag',
        '$[0].title',
    ];

    public function setSearch(): array
    {
        return [
            '=' => ['device_code', 'app_type', 'app_version', 'day'],
        ];
    }

    public function setSortFields(): array
    {
        return [
            'id' => 'id',
            'create_time' => 'create_time',
            'day' => 'day',
        ];
    }

    public function setDefaultOrder(): array
    {
        return ['id' => 'desc'];
    }

    public function lists(): array
    {
        $lists = $this->baseQuery()
            ->field('id,user_id,device_code,app_type,content,app_version,day,create_time')
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $forExport = (int)$this->export === ExportEnum::EXPORT;
        foreach ($lists as &$item) {
            $item = self::formatLogItem($item, $forExport);
        }
        unset($item);

        return $lists;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    public function setFileName(): string
    {
        return '设备运行日志';
    }

    public function setExcelFields(): array
    {
        return [
            'device_code' => '设备号',
            'app_type_desc' => '平台',
            'app_version' => '版本',
            'day' => '日期',
            'tag' => '标签',
            'log' => '日志',
            'image' => '图片',
            'create_time' => '创建时间',
        ];
    }

    private function baseQuery()
    {
        $query = SvDeviceLog::where($this->searchWhere);
            //->where('user_id', $this->getCurrentUserId());

        if ($this->request->get('start_time') && $this->request->get('end_time')) {
            $query->whereBetween('create_time', [
                strtotime($this->request->get('start_time')),
                strtotime($this->request->get('end_time')),
            ]);
        }

        $tag = $this->request->get('tag', '');
        if ($tag !== '') {
            $this->whereContentJsonEquals($query, self::CONTENT_TAG_JSON_PATHS, $tag);
        }

        $keyword = $this->request->get('keyword', '');
        if ($keyword !== '') {
            $keyword = '%' . $keyword . '%';
            $this->whereContentJsonLike($query, self::CONTENT_KEYWORD_JSON_PATHS, $keyword);
        }

        return $query;
    }

    /**
     * @param array|\ArrayAccess $item
     */
    public static function formatLogItem(array|\ArrayAccess $item, bool $forExport = false): array
    {
        $content = self::normalizeContent($item['content'] ?? []);
        $log = self::parseLogValue(self::getContentValue($content, ['msg', 'message', 'log']));
        $log = self::completeLogImageUrls($log);
        $tag = self::formatContentValue(self::getContentValue($content, ['title', 'tag']));
        $image = self::formatImageValue(self::getContentValue($content, ['image', 'imageUrl', 'image_url', 'img', 'pic']));
        $msg = self::formatExportLogValue($log);

        return [
            'id' => $item['id'],
            'device_code' => $item['device_code'],
            'app_type' => $item['app_type'],
            'app_type_desc' => WorkerEnum::getAccountTypeDesc($item['app_type']),
            'app_version' => $item['app_version'],
            'day' => $item['day'],
            'log' => $forExport ? self::formatExportLogValue($log) : $log,
            'tag' => $tag,
            'title' => $tag,
            'msg' => $msg,
            'image' => $image,
            'create_time' => self::formatTime($item['create_time']),
        ];
    }

    private function getCurrentUserId(): int
    {
        if ($this->userId > 0) {
            return $this->userId;
        }

        return (int)(request()->userId ?? request()->userInfo['user_id'] ?? 0);
    }

    private function whereContentJsonEquals($query, array $jsonPaths, string $value): void
    {
        $query->whereRaw(
            $this->buildJsonPathWhere($jsonPaths, '='),
            array_fill(0, count($jsonPaths), $value)
        );
    }

    private function whereContentJsonLike($query, array $jsonPaths, string $value): void
    {
        $query->whereRaw(
            $this->buildJsonPathWhere($jsonPaths, 'LIKE'),
            array_fill(0, count($jsonPaths), $value)
        );
    }

    private function buildJsonPathWhere(array $jsonPaths, string $operator): string
    {
        $conditions = [];
        foreach ($jsonPaths as $jsonPath) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '{$jsonPath}')) {$operator} ?";
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    private static function normalizeContent(mixed $content): array
    {
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $content = $decoded;
            } else {
                return ['msg' => $content];
            }
        }

        if (!is_array($content)) {
            return [];
        }

        if ($content === []) {
            return [];
        }

        $first = $content[0] ?? null;
        if (is_array($first)) {
            foreach ($content as $key => $value) {
                if (!is_int($key) && !array_key_exists($key, $first)) {
                    $first[$key] = $value;
                }
            }

            return $first;
        }

        if (self::isListArray($content)) {
            return ['msg' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
        }

        return $content;
    }

    private static function isListArray(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private static function getContentValue(array $content, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $content) && $content[$key] !== null && $content[$key] !== '') {
                return $content[$key];
            }
        }

        return '';
    }

    private static function formatContentValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string)$value;
    }

    private static function formatImageValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(self::completeLogImageUrls($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return self::completeFileUrl((string)$value);
    }

    private static function parseLogValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return self::normalizeParsedLogValue($value);
        }

        if (!is_string($value)) {
            return is_null($value) ? '' : $value;
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return self::normalizeParsedLogValue($decoded);
    }

    private static function normalizeParsedLogValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::parseLogValue($value);
        }

        return $value;
    }

    private static function completeLogImageUrls(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => &$item) {
            if (is_array($item)) {
                $item = self::completeLogImageUrls($item);
                continue;
            }

            if (self::isImageUrlKey((string)$key)) {
                $item = self::completeFileUrl((string)$item);
            }
        }
        unset($item);

        return $value;
    }

    private static function isImageUrlKey(string $key): bool
    {
        $keys = [
            'image',
            'imageurl',
            'image_url',
            'img',
            'pic',
            'cover',
            'cover_url',
            'thumbnail',
            'thumbnail_url',
            'avatar',
        ];

        return in_array(strtolower($key), $keys, true);
    }

    private static function completeFileUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return FileService::getFileUrl(ltrim($url, '/'));
    }

    private static function formatExportLogValue(mixed $value): string
    {
        if (is_array($value)) {
            foreach (['msg', 'message', 'log'] as $key) {
                if (isset($value[$key]) && !is_array($value[$key])) {
                    return (string)$value[$key];
                }
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string)$value;
    }

    private static function formatTime(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        return is_numeric($value) ? date('Y-m-d H:i:s', (int)$value) : (string)$value;
    }
}
