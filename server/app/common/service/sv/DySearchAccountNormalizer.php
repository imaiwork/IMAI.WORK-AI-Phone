<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 抖音账号名单规范化、频率解析、扫描配置组装。不依赖 Think 容器。
 */
class DySearchAccountNormalizer
{
    /**
     * @param array $items
     * @return array
     */
    public static function normalizeList(array $items): array
    {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $one = self::normalizeOne((string)$item);
            if ($one === '' || isset($seen[$one])) {
                continue;
            }
            $seen[$one] = true;
            $result[] = $one;
        }
        return $result;
    }

    /**
     * 去掉空白、前导 @；主页链接取 path 末段；self 仅保留 unique_id。
     * 创建时不剔除默认号。
     */
    public static function normalizeOne(string $raw): string
    {
        $raw = preg_replace('/\s+/u', '', trim($raw));
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        $raw = ltrim($raw, '@');
        if ($raw === '') {
            return '';
        }

        $isUrl = preg_match('#^https?://#i', $raw) === 1 || stripos($raw, 'douyin.com/') !== false;
        if (!$isUrl) {
            return $raw;
        }

        if (preg_match('#^https?://#i', $raw) !== 1) {
            $raw = 'https://' . ltrim($raw, '/');
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) {
            return '';
        }

        $path = (string)($parts['path'] ?? '');
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }
        $last = $segments !== [] ? (string)end($segments) : '';
        if ($last === '' || strcasecmp($last, 'self') === 0) {
            $query = [];
            parse_str((string)($parts['query'] ?? ''), $query);
            return isset($query['unique_id']) ? trim((string)$query['unique_id']) : '';
        }

        return $last;
    }

    /**
     * 「3天」/ 「3」/ 3 → 3
     *
     * @param mixed $raw
     */
    public static function parseTaskFrep($raw): int
    {
        if (is_int($raw)) {
            return $raw;
        }
        $s = trim((string)$raw);
        if ($s === '') {
            return 0;
        }
        if (is_numeric($s)) {
            return (int)$s;
        }
        if (preg_match('/(\d+)/', $s, $m) === 1) {
            return (int)$m[1];
        }
        return 0;
    }

    /**
     * 「不限」/ 「30 个」/ 30 → 非负整数。空值用默认；脏值抛错，避免被当成不限。
     *
     * @param mixed $raw
     */
    public static function parseCap($raw, string $label = '上限', ?int $default = null): int
    {
        if ($raw === null || $raw === '') {
            if ($default !== null) {
                return $default;
            }
            throw new \InvalidArgumentException('请填写' . $label);
        }
        if (is_int($raw)) {
            if ($raw < 0) {
                throw new \InvalidArgumentException($label . '不能为负数');
            }
            return $raw;
        }
        if (is_float($raw)) {
            if ($raw < 0 || $raw != (int)$raw) {
                throw new \InvalidArgumentException($label . '须为非负整数或不限');
            }
            return (int)$raw;
        }

        $s = trim((string)$raw);
        if ($s === '') {
            if ($default !== null) {
                return $default;
            }
            throw new \InvalidArgumentException('请填写' . $label);
        }
        if (preg_match('/^不限$/u', $s) === 1) {
            return 0;
        }
        if (preg_match('/^(\d+)\s*个?$/u', $s, $m) === 1) {
            return (int)$m[1];
        }
        throw new \InvalidArgumentException($label . '须为非负整数或不限');
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    public static function splitWechatIds($raw): array
    {
        $items = is_array($raw) ? $raw : explode(',', (string)$raw);
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $one = trim((string)$item);
            if ($one === '' || isset($seen[$one])) {
                continue;
            }
            $seen[$one] = true;
            $result[] = $one;
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultScanConfig(): array
    {
        return [
            'pre_filter' => true,
            'fans_min' => 500,
            'works_min' => 5,
            'active_days' => 90,
            'only_biz' => false,
            'scan_bio' => true,
            'scan_dyid' => true,
            'scan_cover' => true,
            'cover_count' => 3,
            'stitch' => 'h',
            'contact_phone' => true,
            'contact_wechat' => true,
            'contact_qq' => false,
            'phone_as_wechat' => true,
            'dedupe' => true,
            'skip_existing' => true,
            'miss_action' => 'dm',
            'miss_scripts' => [],
            'miss_once' => true,
        ];
    }

    /**
     * 合并默认扫描配置；account 模式强制关掉初筛。
     *
     * @param mixed $input
     * @return array<string, mixed>
     */
    public static function buildScanConfig($input, string $searchMode = 'keyword'): array
    {
        if (is_string($input) && $input !== '') {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($input)) {
            $input = [];
        }

        $defaults = self::defaultScanConfig();
        $merged = array_merge($defaults, $input);
        $config = [];
        foreach ($defaults as $key => $unused) {
            $config[$key] = $merged[$key] ?? $unused;
        }

        $config['pre_filter'] = self::toBool($config['pre_filter']);
        $config['only_biz'] = self::toBool($config['only_biz']);
        $config['scan_bio'] = self::toBool($config['scan_bio']);
        $config['scan_dyid'] = self::toBool($config['scan_dyid']);
        $config['scan_cover'] = self::toBool($config['scan_cover']);
        $config['contact_phone'] = self::toBool($config['contact_phone']);
        $config['contact_wechat'] = self::toBool($config['contact_wechat']);
        $config['contact_qq'] = self::toBool($config['contact_qq']);
        $config['phone_as_wechat'] = self::toBool($config['phone_as_wechat']);
        $config['miss_once'] = self::toBool($config['miss_once']);
        $config['fans_min'] = (int)$config['fans_min'];
        $config['works_min'] = (int)$config['works_min'];
        $config['active_days'] = (int)$config['active_days'];
        $config['cover_count'] = (int)$config['cover_count'];
        $config['stitch'] = strtolower(trim((string)$config['stitch']));
        $config['miss_action'] = strtolower(trim((string)$config['miss_action']));
        $scripts = is_array($config['miss_scripts']) ? $config['miss_scripts'] : [];
        $config['miss_scripts'] = [];
        foreach ($scripts as $script) {
            $one = trim((string)$script);
            if ($one !== '') {
                $config['miss_scripts'][] = $one;
            }
        }

        $config = self::applyScanHardRules($config, $searchMode);

        if (!$config['scan_bio'] && !$config['scan_dyid'] && !$config['scan_cover']) {
            throw new \InvalidArgumentException('请至少选择一处查看位置');
        }
        if (!$config['contact_phone'] && !$config['contact_wechat'] && !$config['contact_qq']) {
            throw new \InvalidArgumentException('请至少选择一种联系方式');
        }
        if ($config['cover_count'] < 1 || $config['cover_count'] > 5) {
            throw new \InvalidArgumentException('封面数量须在1到5之间');
        }
        if ((int)$config['cover_count'] > 1 && !in_array($config['stitch'], ['h', 'v', 'both'], true)) {
            throw new \InvalidArgumentException('封面拼接方式仅支持横向、纵向或两者');
        }
        if (!in_array($config['miss_action'], ['dm', 'skip'], true)) {
            throw new \InvalidArgumentException('未识别处理仅支持私信或跳过');
        }
        if ($config['miss_action'] === 'dm' && $config['miss_scripts'] === []) {
            throw new \InvalidArgumentException('私信主动问时请至少配置一条话术');
        }

        return $config;
    }

    /**
     * 页面锁死口径：去重恒开、关初筛清零、一张封面不拼、没勾手机则不当微信号加。
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function applyScanHardRules(array $config, string $searchMode = 'keyword'): array
    {
        if ($searchMode === 'account') {
            $config['pre_filter'] = false;
        }
        $config['pre_filter'] = self::toBool($config['pre_filter'] ?? false);
        if (!$config['pre_filter']) {
            $config['fans_min'] = 0;
            $config['works_min'] = 0;
            $config['active_days'] = 0;
            $config['only_biz'] = false;
        }
        $config['dedupe'] = true;
        $config['skip_existing'] = true;
        $config['contact_phone'] = self::toBool($config['contact_phone'] ?? false);
        if (!$config['contact_phone']) {
            $config['phone_as_wechat'] = false;
        }
        $config['cover_count'] = (int)($config['cover_count'] ?? 0);
        if ($config['cover_count'] <= 1) {
            $config['stitch'] = '';
        }

        return $config;
    }

    /**
     * 关键词模式：去空白、去重。
     */
    public static function normalizeKeywords(array $items): array
    {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $one = trim((string)$item);
            if ($one === '' || isset($seen[$one])) {
                continue;
            }
            $seen[$one] = true;
            $result[] = $one;
        }
        return $result;
    }

    /**
     * keyword 模式按提交尾部优先落任务；account 模式保持原序。
     *
     * @param list<string> $items
     * @return list<string>
     */
    public static function orderKeywordsForTask(array $items, string $searchMode): array
    {
        if ($searchMode !== 'keyword') {
            return array_values($items);
        }
        return array_values(array_reverse($items));
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int)$value) === 1;
        }
        $s = strtolower(trim((string)$value));
        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }
}
