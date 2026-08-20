<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\common\enum\DeviceEnum;

/**
 * 爆款分享链接平台识别
 */
class ViralSharePlatformDetector
{
    /**
     * @return array{
     *   platform: int,
     *   platform_name: string,
     *   url: string,
     *   supported_manual: bool
     * }
     */
    public static function detect(string $shareContent): array
    {
        $url = ViralShareTextNormalizer::extractUrl($shareContent);
        if ($url === '') {
            throw new \RuntimeException('无法识别分享链接所属平台');
        }

        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        if (self::matchHost($host, [
            'douyin.com',
            'iesdouyin.com',
            'v.douyin.com',
            'www.douyin.com',
        ])) {
            return self::result(DeviceEnum::ACCOUNT_TYPE_DY, $url, true);
        }

        if (self::matchHost($host, [
            'xiaohongshu.com',
            'xhslink.com',
            'xhslink.cn',
            'xhsurl.com',
        ])) {
            return self::result(DeviceEnum::ACCOUNT_TYPE_XHS, $url, true);
        }

        if (self::matchHost($host, [
            'kuaishou.com',
            'v.kuaishou.com',
            'gifshow.com',
            'chenzhongtech.com',
        ])) {
            return self::result(DeviceEnum::ACCOUNT_TYPE_KS, $url, false);
        }

        if (self::matchHost($host, [
            'channels.weixin.qq.com',
            'weixin.qq.com',
        ]) || str_contains($host, 'channels.weixin')) {
            return self::result(DeviceEnum::ACCOUNT_TYPE_SPH, $url, false);
        }

        throw new \RuntimeException('无法识别分享链接所属平台');
    }

    private static function matchHost(string $host, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = strtolower($needle);
            if ($host === $needle || str_ends_with($host, '.' . $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function result(int $platform, string $url, bool $supportedManual): array
    {
        return [
            'platform' => $platform,
            'platform_name' => DeviceEnum::getAccountTypeDesc($platform),
            'url' => $url,
            'supported_manual' => $supportedManual,
        ];
    }
}
