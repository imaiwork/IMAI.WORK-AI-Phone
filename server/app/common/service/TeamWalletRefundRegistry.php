<?php

namespace app\common\service;

/**
 * 企业钱包消费登记(进程内)
 *
 * 解决"扣费走企业钱包、失败退费却退到个人算力"的不对称问题:
 *  - dec 时若从企业钱包扣了钱,push 登记(userId → [team_id, amount, ts])
 *  - 业务成功: confirm 核销登记
 *  - 业务失败退费: take 取出对应的企业钱包份额,按原路退回企业钱包,其余退个人
 *
 * 说明:登记表在进程内存中。HTTP 每请求一进程,精确;workerman 长进程下
 * confirm/TTL 双保险防堆积。跨进程退费(如队列内退费)取不到登记时,
 * 自动落回"退个人"——不会比修复前更差。
 */
class TeamWalletRefundRegistry
{
    /** @var array<int, array<int, array{team_id:int, amount:float, ts:int}>> userId => 登记列表(FIFO) */
    protected static array $pending = [];

    /** 登记有效期(秒),过期视为业务已完结 */
    const TTL = 7200;

    /** dec 从企业钱包扣款后登记 */
    public static function push(int $userId, int $teamId, float $amount): void
    {
        if ($userId <= 0 || $teamId <= 0 || $amount <= 0) {
            return;
        }
        self::gc($userId);
        self::$pending[$userId][] = ['team_id' => $teamId, 'amount' => $amount, 'ts' => time()];
    }

    /**
     * 退费时取出企业钱包份额(FIFO,最多取 $amount)
     * @return array<int, float> team_id => 应退回该企业钱包的金额
     */
    public static function take(int $userId, float $amount): array
    {
        self::gc($userId);
        $out = [];
        $need = $amount;
        while ($need > 0 && !empty(self::$pending[$userId])) {
            $entry = array_shift(self::$pending[$userId]);
            $use = min($entry['amount'], $need);
            $out[$entry['team_id']] = ($out[$entry['team_id']] ?? 0) + $use;
            $left = (float)bcsub((string)$entry['amount'], (string)$use, 2);
            if ($left > 0) {
                array_unshift(self::$pending[$userId], ['team_id' => $entry['team_id'], 'amount' => $left, 'ts' => $entry['ts']]);
            }
            $need = (float)bcsub((string)$need, (string)$use, 2);
        }
        return $out;
    }

    /** 业务成功核销(丢弃对应额度的登记,防长进程堆积) */
    public static function confirm(int $userId, float $amount): void
    {
        self::take($userId, $amount);
    }

    /** 清理过期登记 */
    protected static function gc(int $userId): void
    {
        if (empty(self::$pending[$userId])) {
            return;
        }
        $now = time();
        self::$pending[$userId] = array_values(array_filter(
            self::$pending[$userId],
            fn($e) => ($now - $e['ts']) <= self::TTL
        ));
        if (empty(self::$pending[$userId])) {
            unset(self::$pending[$userId]);
        }
    }
}
