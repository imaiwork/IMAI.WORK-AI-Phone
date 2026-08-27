<?php

namespace app\adminapi\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\service\hotspot\HistoryService;
use app\common\service\hotspot\HotListService;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\HotspotUpstreamException;

class HotLogic extends BaseLogic
{
    public static function lists(array $params): array|false
    {
        $platform = (string)($params['platform'] ?? '');
        $period = (string)($params['period'] ?? 'day');
        $day = (string)($params['day'] ?? '');
        $limit = (int)($params['limit'] ?? 30);
        try {
            $result = HotListService::getHot($platform, $period, $day, $limit);
            HotspotLog::write(sprintf(
                '后台热榜成功：平台=%s 周期=%s 日期=%s 条数=%d',
                $result['platform'] ?? $platform,
                $result['period'] ?? $period,
                $result['date'] ?? $day,
                count($result['topics'] ?? [])
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('后台热榜失败：' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            HotspotLog::exception('后台热榜异常', $e);
            self::setError('服务异常，请稍后再试');
            return false;
        }
    }

    public static function historyDates(array $params): array
    {
        $platform = (string)($params['platform'] ?? '');
        $dates = HistoryService::availableDates($platform);
        HotspotLog::write(sprintf('后台历史日期查询：平台=%s 数量=%d', $platform, count($dates)));
        return [
            'platform' => $platform,
            'dates' => $dates,
            'note' => $dates === []
                ? '还没有历史快照。上游不提供往期热榜，历史从本服务开始运行后逐日累积。'
                : '',
        ];
    }
}
