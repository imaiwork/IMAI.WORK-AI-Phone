<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoPublish;
use app\common\model\user\User;
use app\common\logic\AccountLogLogic;
use app\common\enum\user\AccountLogEnum;

/**
 * GEO 投稿台账工具(退费/回填)。
 *
 * 【媒体代发功能已下线】付费代发(人民币扣费)、聚合平台自动送稿(order submit)、
 * 回执轮询(order status)与中台退款(order refund)已全部移除,仅保留本类对
 * 历史扣费记录的退费与已发布链接回填能力。
 * 现行发布渠道:授权账号直发(GeoAuthPublishService)、AI 手机(GeoPhonePublishService)、
 * 官网/公众号(GeoSiteService)、自有发布登记(GeoLogic::publishRegister)。
 */
class GeoPublishService
{
    /**
     * 退费(任务未发布时删除):把已扣的费用退回【原扣费人】余额。
     * 退款对象取发布记录上记录的扣费人 user_id,而非删除操作的发起人,避免退款进错账户。
     * 代发下线后不会再产生新的扣费记录,本方法仅用于清理历史记录。
     */
    public static function refundIfUnpublished(GeoPublish $p): void
    {
        $payerId = (int)$p->user_id;
        $cost = (float)$p->cost;
        if ($payerId <= 0 || $cost <= 0 || $p->status === 'published') {
            return;
        }
        // 条件清零 cost 抢闸:防跨路径双退(cron 判失败退一次 × 用户删除记录再退一次);
        // status 条件兜底并发:回执确认已发布与删除退费同刻时,已发布订单不退
        $claimed = GeoPublish::where('id', (int)$p->id)->where('cost', '>', 0)
            ->where('status', '<>', 'published')->update(['cost' => 0]);
        if (!$claimed) {
            return;
        }
        $p->cost = 0;
        User::where('id', $payerId)->inc('user_money', $cost)->update();
        AccountLogLogic::add(
            $payerId,
            AccountLogEnum::UM_INC_GEO_PUBLISH_REFUND,
            AccountLogEnum::INC,
            $cost,
            1,
            (string)$p->id,
            'GEO发布取消退费·' . $p->media_name
        );
    }

    /**
     * 回填已发布链接(半自动核心):用户在目标平台发完,把文章URL填回来 → 标记 published。
     */
    public static function confirm(int $publishId, string $url): bool
    {
        // 仅 pending 可确认发布:failed 且已退费后再标 published 会造成「已退费且已发布」
        $claimed = GeoPublish::where('id', $publishId)->where('status', 'pending')->update([
            'published_url' => $url,
            'status' => 'published',
            'error_msg' => '',
            'publish_time' => time(),
            'update_time' => time(),
        ]);
        return (bool)$claimed;
    }
}
