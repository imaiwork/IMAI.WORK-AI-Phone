<?php

namespace app\common\logic;

use app\common\enum\PayEnum;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\user\User;

/**
 * 充值业绩统计逻辑
 *
 * 全站「累计充值 / 下级充值业绩 / 下级充值流水」的唯一口径：
 * 只统计算力加油包、礼包订单（la_gift_package_order）中已支付的订单。
 *
 * 「下级充值业绩」还要再按水位线过滤：la_user.recharge_stats_reset_time 记录的是
 * 「该用户作为上级，看自己下级业绩时的清零时间点」，只统计晚于该时间点支付的订单。
 * 水位线属于看业绩的上级，不属于被统计的下级，所以：
 * - 清零某个用户的下级业绩，不会改变任何下级自己的「累计充值金额」
 * - 同一个下级在不同上级视角下的业绩，各自按各上级的水位线独立计算
 *
 * 清零只推进水位线，不删除、不修改任何充值订单与账单流水，
 * 被清零的金额随时可由「支付时间 <= 水位线」的订单反推。
 *
 * Class RechargeStatsLogic
 * @package app\common\logic
 */
class RechargeStatsLogic extends BaseLogic
{
    /**
     * @notes 已支付充值订单查询
     * @param array $userIds 被统计的用户
     * @param int $sinceTime 业绩清零水位线，只取晚于该时间点支付的订单；0 表示不限
     * @return \think\db\Query
     */
    public static function orderQuery(array $userIds, int $sinceTime = 0)
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        $query = GiftPackageOrder::alias('o')
            ->where('o.pay_status', PayEnum::ISPAID)
            ->whereIn('o.user_id', $userIds ?: [0]);

        if ($sinceTime > 0) {
            // 历史脏数据缺 pay_time 时回落到下单时间
            $query->whereRaw(
                'IFNULL(NULLIF(o.pay_time, 0), o.create_time) > :since_time',
                ['since_time' => $sinceTime]
            );
        }

        return $query;
    }

    /**
     * @notes 单个用户自己的累计充值金额（不受任何业绩清零影响）
     * @param int $userId
     * @return float
     */
    public static function getUserAmount(int $userId): float
    {
        return (float)self::orderQuery([$userId])->sum('o.order_amount');
    }

    /**
     * @notes 多个用户的充值业绩，返回 user_id => ['amount' => ..., 'order_count' => ...]
     * @param array $userIds
     * @param int $sinceTime
     * @return array
     */
    public static function getAmountMap(array $userIds, int $sinceTime = 0): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        $rows = self::orderQuery($userIds, $sinceTime)
            ->field('o.user_id, SUM(o.order_amount) AS amount, COUNT(*) AS order_count')
            ->group('o.user_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($userIds as $userId) {
            $map[$userId] = ['amount' => 0.0, 'order_count' => 0];
        }
        foreach ($rows as $row) {
            $map[(int)$row['user_id']] = [
                'amount' => (float)$row['amount'],
                'order_count' => (int)$row['order_count'],
            ];
        }
        return $map;
    }

    /**
     * @notes 一批用户的充值金额与笔数合计
     * @param array $userIds
     * @param int $sinceTime
     * @return array{amount: float, order_count: int}
     */
    public static function getTotal(array $userIds, int $sinceTime = 0): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return ['amount' => 0.0, 'order_count' => 0];
        }

        $row = self::orderQuery($userIds, $sinceTime)
            ->field('SUM(o.order_amount) AS amount, COUNT(*) AS order_count')
            ->find();

        return [
            'amount' => (float)($row['amount'] ?? 0),
            'order_count' => (int)($row['order_count'] ?? 0),
        ];
    }

    /**
     * @notes 单个用户的充值流水明细，按支付时间倒序
     * @param int $userId
     * @param int $offset
     * @param int $limit
     * @param int $sinceTime
     * @return array
     */
    public static function getUserRecords(int $userId, int $offset, int $limit, int $sinceTime = 0): array
    {
        $lists = self::orderQuery([$userId], $sinceTime)
            ->field('o.id, o.sn, o.package_id, o.order_amount, o.pay_way, o.pay_time, o.create_time, o.type')
            ->order('o.pay_time', 'desc')
            ->order('o.id', 'desc')
            ->limit($offset, $limit)
            ->select()
            ->toArray();

        if ($lists === []) {
            return [];
        }

        $packages = GiftPackage::whereIn('id', array_column($lists, 'package_id'))
            ->field('id, name, package_info')
            ->json(['package_info'], true)
            ->select()
            ->toArray();
        $packageMap = array_column($packages, null, 'id');

        foreach ($lists as &$item) {
            $package = $packageMap[(int)$item['package_id']] ?? null;
            $packageInfo = $package['package_info'] ?? [];
            $item['package_name'] = $package['name'] ?? '';
            $item['package_tokens'] = is_array($packageInfo) ? (float)($packageInfo['tokens'] ?? 0) : 0;
            $item['order_amount'] = (float)$item['order_amount'];
            $item['pay_way_desc'] = PayEnum::getPayDesc((int)$item['pay_way']);
            $payTime = (int)($item['pay_time'] ?: $item['create_time']);
            $item['pay_time'] = $payTime > 0 ? date('Y-m-d H:i:s', $payTime) : '';
        }
        unset($item);

        return $lists;
    }

    /**
     * @notes 单个用户的充值流水笔数
     * @param int $userId
     * @param int $sinceTime
     * @return int
     */
    public static function getUserRecordCount(int $userId, int $sinceTime = 0): int
    {
        return (int)self::orderQuery([$userId], $sinceTime)->count();
    }

    /**
     * @notes 某个用户看下级业绩时的清零水位线
     * @param int $userId
     * @return int
     */
    public static function getResetTime(int $userId): int
    {
        return (int)User::where('id', $userId)->value('recharge_stats_reset_time');
    }

    /**
     * @notes 上级查看某个下级的充值数据时该用的水位线
     *   看自己的充值记录不受任何清零影响，所以不过滤
     * @param int $viewerUserId
     * @param int $subUserId
     * @return int
     */
    public static function getSubStatsSinceTime(int $viewerUserId, int $subUserId): int
    {
        return $viewerUserId === $subUserId ? 0 : self::getResetTime($viewerUserId);
    }

    /**
     * @notes 下级充值业绩清零：把该用户的统计水位线推到当前时间
     *   只写一个时间点，不改动任何订单、账单流水，也不影响下级自己的累计充值金额
     * @param int $userId 上级用户
     * @param array $subUserIds 该用户名下的全部下级
     * @return array{amount: float, order_count: int} 本次被清零的金额与笔数
     * @throws \Exception
     */
    public static function resetSubStats(int $userId, array $subUserIds): array
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            throw new \Exception('用户不存在');
        }
        if ($subUserIds === []) {
            throw new \Exception('该用户暂无下级，无需清零');
        }

        $cleared = self::getTotal($subUserIds, (int)$user->recharge_stats_reset_time);
        if ($cleared['order_count'] === 0) {
            throw new \Exception('该用户下级当前没有可清零的充值业绩');
        }

        $user->recharge_stats_reset_time = time();
        $user->update_time = time();
        $user->save();

        return $cleared;
    }
}
