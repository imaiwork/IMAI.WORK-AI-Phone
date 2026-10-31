<?php

declare(strict_types=1);

namespace app\common\service\sv;

use think\facade\Config;

/**
 * 官方服务号过滤：判定一个联系方式是否属于运营商/政务/金融官方服务号、
 * 企业 400/800 热线或微信官方服务号。
 *
 * 名单统一从 config/service_contact_filter.php 读取，配置文件缺失或某段
 * 非数组时用内置默认值兜底；抖音搜寻获客与视频号获客两条链路共用本服务，
 * 避免黑名单在提取层与加微写入层各自硬编码。
 */
class ServiceContactFilter
{
    /** @var array{exact: list<string>, regex: list<string>, wechat_official: list<string>}|null */
    private static ?array $configCache = null;

    /** @var array{exact: list<string>, regex: list<string>, wechat_official: list<string>} */
    private const DEFAULT_CONFIG = [
        'exact' => [
            '10000', '10001', '10010', '10011', '10086', '1008611', '10099',
            '12306', '12315', '12345', '12333', '12366', '12369',
            '95500', '95519', '95528', '95533', '95555', '95558', '95566', '95568', '95577', '95588', '95598', '95599',
        ],
        'regex' => [
            '^100\d{2}$',
            '^123\d{2}$',
            '^955\d{2}$',
            '^400\d{7}$',
            '^800\d{7}$',
        ],
        'wechat_official' => [
            'wechat', 'wechatteam', 'weixinteam',
        ],
    ];

    /**
     * 是否为官方服务号/官方热线。空串、个人微信号、个人 QQ、正规手机号均返回 false。
     */
    public static function isServiceContact(string $contact): bool
    {
        $contact = trim($contact);
        if ($contact === '') {
            return false;
        }

        $config = self::config();
        $lower = strtolower($contact);

        if (in_array($lower, $config['exact'], true) || in_array($lower, $config['wechat_official'], true)) {
            return true;
        }

        // 号段规则只对纯数字联系人生效，避免误杀字母开头的微信号
        if (preg_match('/^\d+$/', $contact) === 1) {
            foreach ($config['regex'] as $pattern) {
                if (preg_match('#' . str_replace('#', '\#', $pattern) . '#', $contact) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 批量过滤联系方式中的官方服务号（保序、去空）。
     *
     * @param list<string> $contacts
     * @return list<string>
     */
    public static function filterContacts(array $contacts): array
    {
        $out = [];
        foreach ($contacts as $contact) {
            $contact = trim((string)$contact);
            if ($contact === '' || self::isServiceContact($contact)) {
                continue;
            }
            $out[] = $contact;
        }
        return $out;
    }

    /**
     * 重置配置缓存（测试用）。
     */
    public static function resetCache(): void
    {
        self::$configCache = null;
    }

    /**
     * @return array{exact: list<string>, regex: list<string>, wechat_official: list<string>}
     */
    private static function config(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $loaded = [];
        try {
            $raw = Config::get('service_contact_filter', []);
            if (is_array($raw)) {
                $loaded = $raw;
            }
        } catch (\Throwable $e) {
            // 配置加载异常时降级使用内置默认值
            $loaded = [];
        }

        $config = array_merge(self::DEFAULT_CONFIG, $loaded);
        foreach (['exact', 'regex', 'wechat_official'] as $section) {
            if (!is_array($config[$section] ?? null)) {
                $config[$section] = self::DEFAULT_CONFIG[$section];
            }
            $config[$section] = array_values(array_filter(
                array_map(static fn ($item): string => (string)$item, $config[$section]),
                static fn ($item): bool => trim($item) !== ''
            ));
        }

        // 丢弃无法编译的模式（不限定界符、语法错误等），避免每次判定刷 preg 警告
        $config['regex'] = array_values(array_filter(
            $config['regex'],
            static function (string $pattern): bool {
                return @preg_match('#' . str_replace('#', '\#', $pattern) . '#', '0') !== false;
            }
        ));

        self::$configCache = $config;
        return self::$configCache;
    }
}
