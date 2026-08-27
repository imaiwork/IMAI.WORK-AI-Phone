<?php

namespace app\common\service\hotspot;

use app\common\service\ToolsService;
use think\facade\Cache;

class InsightService
{
    public static function getInsight(string $topic, int $userId = 0): array
    {
        $empty = [
            'topic' => $topic,
            'found' => false,
            'error' => '',
            'trend' => [],
            'videos' => [],
            'gender' => [],
            'age' => [],
            'province' => [],
        ];

        $ttl = (int)config('hotspot.hot_cache_ttl', 600);
        $cacheKey = 'hotspot:insight:' . md5($topic);
        $cached = Cache::store('redis')->get($cacheKey);
        if (is_array($cached)) {
            $cached['topic'] = $topic;
            HotspotLog::write('洞察缓存命中：话题=' . $topic . ' 命中=' . (!empty($cached['found']) ? '是' : '否'));
            return $cached;
        }

        HotspotLog::write('洞察缓存未命中，开始拉取上游：话题=' . $topic);
        try {
            HotspotChargeService::precheckTikhub($userId, HotspotChargeService::SCENE_TIKHUB_DETAIL);
            $payload = ToolsService::TikHub()->fetchDouyinHotDetail($topic);
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('洞察上游失败：' . $e->getMessage());
            throw $e;
        }

        $data = $payload['data'] ?? null;
        if (!is_array($data) || $data === []) {
            HotspotLog::write('洞察上游无数据：话题=' . $topic);
            $empty['error'] = 'not_found';
            // 负缓存：不存在的话题短期内不再打按次付费的上游
            Cache::store('redis')->set($cacheKey, $empty, min($ttl, 300));
            return $empty;
        }

        $trend = [];
        foreach ($data['trend_item'] ?? [] as $p) {
            if (!is_array($p)) {
                continue;
            }
            $trend[] = [
                'time' => mb_substr((string)($p['date_time'] ?? ''), 0, 16),
                'value' => HeatFormatter::toInt($p['index'] ?? 0),
            ];
        }

        $videos = [];
        foreach (array_slice($data['content_item'] ?? [], 0, 10) as $v) {
            if (!is_array($v) || empty($v['item_title'])) {
                continue;
            }
            $videos[] = [
                'title' => trim((string)$v['item_title']),
                'url' => (string)($v['item_url'] ?? ''),
                'cover' => (string)($v['item_image'] ?? ''),
                'author' => (string)($v['author_name'] ?? ''),
                'digg_cnt' => HeatFormatter::toInt($v['digg_cnt'] ?? 0),
                'comment_cnt' => HeatFormatter::toInt($v['comment_cnt'] ?? 0),
            ];
        }

        $portrait = ['gender' => [], 'age' => [], 'province' => []];
        foreach ($data['portrait'] ?? [] as $dim) {
            if (!is_array($dim)) {
                continue;
            }
            $key = (string)($dim['name_en'] ?? '');
            if (!isset($portrait[$key])) {
                continue;
            }
            foreach ($dim['label_list'] ?? [] as $x) {
                if (!is_array($x)) {
                    continue;
                }
                $portrait[$key][] = [
                    'name' => (string)($x['name_zh'] ?? ''),
                    'value' => (float)($x['value'] ?? 0),
                ];
            }
        }

        $res = [
            'topic' => $topic,
            'found' => true,
            'trend' => $trend,
            'videos' => $videos,
            'gender' => $portrait['gender'],
            'age' => $portrait['age'],
            'province' => $portrait['province'],
        ];
        // 先扣费后写缓存：扣费失败时不落缓存，重试仍会走扣费路径；
        // ref 与「话题+缓存窗口」绑定，hasSettled 幂等去重，并发重复请求只扣一次
        HotspotChargeService::settleTikhub(
            $userId,
            HotspotChargeService::SCENE_TIKHUB_DETAIL,
            HotspotChargeService::windowRef('hotspot_insight', md5($topic), $ttl),
            ['话题' => $topic]
        );
        Cache::store('redis')->set($cacheKey, $res, $ttl);
        HotspotLog::write(sprintf(
            '洞察解析完成：话题=%s 趋势点数=%d 视频数=%d 性别维度=%d 年龄维度=%d 省份维度=%d',
            $topic,
            count($trend),
            count($videos),
            count($portrait['gender']),
            count($portrait['age']),
            count($portrait['province'])
        ));
        return $res;
    }

    public static function hotWords(string $appName, int $userId = 0): array
    {
        $ttl = (int)config('hotspot.hot_cache_ttl', 600);
        $cacheKey = 'hotspot:hotwords:' . $appName;
        $cached = Cache::store('redis')->get($cacheKey);
        if (is_array($cached)) {
            HotspotLog::write('热搜词缓存命中：应用=' . $appName . ' 数量=' . count($cached));
            return $cached;
        }

        HotspotLog::write('拉取热搜词：应用=' . $appName);
        HotspotChargeService::precheckTikhub($userId, HotspotChargeService::SCENE_TIKHUB_WORDS);
        $words = ToolsService::TikHub()->fetchDouyinHotWords($appName);
        $words = is_array($words) ? $words : [];
        HotspotLog::write('热搜词上游返回：应用=' . $appName . ' 数量=' . count($words));
        if ($words === []) {
            // 空结果不扣费，短负缓存防反复打上游
            Cache::store('redis')->set($cacheKey, $words, min($ttl, 60));
            return $words;
        }
        HotspotChargeService::settleTikhub(
            $userId,
            HotspotChargeService::SCENE_TIKHUB_WORDS,
            HotspotChargeService::windowRef('hotspot_hot_words', $appName, $ttl),
            ['应用' => $appName, '数量' => count($words)]
        );
        Cache::store('redis')->set($cacheKey, $words, $ttl);
        return $words;
    }
}
