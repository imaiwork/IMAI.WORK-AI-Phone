<?php

namespace app\common\service\geo;

use app\common\service\GeoService;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 社媒数据网关（经 GEO 中台转发，不再直连 TikHub）。
 *
 * 用途：GEO 把内容投递到小红书/抖音/快手/视频号之后，靠这里按已发布链接
 * 反查互动数据，让投稿台账从"发了"变成"发了有效果"。
 *
 * 启用条件：GEO 中台密钥配置后自动生效；未配置时 enabled() 恒 false，
 * 调用方直接跳过，不产生任何请求与费用。互动指标识别、单平台失败隔离、
 * 跨平台去重均在中台完成；五项互动指标一项都认不出时中台报
 * "互动指标识别失败"抛错，由调用方记 stat_error，绝不编数。
 */
class GeoTikhubService
{
    /** GEO 中台密钥是否已配置 */
    public static function enabled(): bool
    {
        return GeoService::enabled();
    }

    /**
     * 按平台反查一条已发布内容的互动数据。
     *
     * @param string $url        geo_publish.published_url
     * @param string $platform   provider_code：xhs|douyin|kuaishou|sph
     * @param string $mediaType  article|video
     * @return array{views:int,likes:int,comments:int,collects:int,shares:int}
     * @throws \Exception 未启用、平台不支持、请求失败或认不出指标时抛出，由调用方记 stat_error
     */
    public static function statsByUrl(string $url, string $platform, string $mediaType = 'article'): array
    {
        if (!self::enabled()) {
            throw new \Exception('GEO 中台未接入:配置中台密钥后自动启用投稿数据回收');
        }
        $url = trim($url);
        if ($url === '') {
            throw new \Exception('没有已发布链接');
        }

        $r = ToolsService::Geo()->publishStats($url, $platform, $mediaType);
        return [
            'views'    => (int)($r['views'] ?? 0),
            'likes'    => (int)($r['likes'] ?? 0),
            'comments' => (int)($r['comments'] ?? 0),
            'collects' => (int)($r['collects'] ?? 0),
            'shares'   => (int)($r['shares'] ?? 0),
        ];
    }

    // ==================== 搜索联想词 ====================

    /**
     * 取某个种子词在各平台的搜索联想词。
     *
     * 联想词就是真人正在搜的东西 —— 比让 AI 凭空编场景问题准得多。
     * 单平台失败中台已隔离（failed_platforms 回传）;整体失败(全部平台失败/
     * 未配上游)时降级为空数组 —— 联想词是增强项,退回纯 AI 生成,
     * 不阻断场景问题生成。
     *
     * @return array<int,array{term:string,platform:string}> 中台已跨平台去重,platform 为平台中文名
     */
    public static function searchSuggest(string $keyword, int $perPlatform = 10): array
    {
        if (!self::enabled() || !(bool)env('geo.tikhub_suggest', true)) {
            return [];
        }
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        try {
            $r = ToolsService::Geo()->searchSuggest($keyword, [], $perPlatform);
        } catch (\Throwable $e) {
            Log::warning("GEO 联想词({$keyword}): " . $e->getMessage());
            return [];
        }
        $failed = (array)($r['failed_platforms'] ?? []);
        if ($failed) {
            Log::warning("GEO 联想词部分平台失败({$keyword}): " . implode(',', $failed));
        }
        return array_values((array)($r['terms'] ?? []));
    }

    /** 供后台/诊断页展示接入状态 */
    public static function status(): array
    {
        return [
            'enabled' => self::enabled(),
            'hint'    => self::enabled() ? '' : '配置 GEO 中台密钥后启用投稿数据回收',
        ];
    }
}
