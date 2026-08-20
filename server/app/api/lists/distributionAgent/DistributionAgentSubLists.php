<?php

namespace app\api\lists\distributionAgent;

use app\api\lists\BaseApiDataLists;
use app\api\logic\distributionAgent\DistributionAgentLogic;
use app\common\logic\RechargeStatsLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\service\FileService;

/**
 * 分销代理前端下级列表
 *
 * 不传 user_id 时查自己的直属下级；传直属下级的 user_id 时查该下级的下级（孙级），
 * 传更深层级会被鉴权挡掉，所以代理端最多看到两层。每条带上该下级的充值业绩、
 * 直属下级数（sub_count）和全部层级子孙人数（descendant_count）。
 *
 * Class DistributionAgentSubLists
 * @package app\api\lists\distributionAgent
 */
class DistributionAgentSubLists extends BaseApiDataLists
{
    /**
     * @notes 代理下级列表查询
     * @return array
     */
    public function lists(): array
    {
        $lists = $this->baseQuery()
            ->field('a.user_id, a.parent_id, a.level, a.status, a.become_time, u.nickname, u.avatar, u.mobile, u.tokens')
            // 早期后台绑定的下级 become_time 可能为 0，补 user_id 兜底排序，避免分页顺序抖动
            ->order('a.become_time desc, a.user_id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        if ($lists === []) {
            return [];
        }

        $subUserIds = array_column($lists, 'user_id');
        // 每个下级的充值业绩，按当前代理自己的业绩清零水位线统计
        $rechargeMap = RechargeStatsLogic::getAmountMap(
            $subUserIds,
            RechargeStatsLogic::getResetTime($this->getCurrentUserId())
        );
        // 直属下级数决定前端要不要给「查看下级」入口；子孙人数含全部层级，给列表展示
        $subCountRows = DistributionAgent::whereIn('parent_id', $subUserIds)
            ->field('parent_id, COUNT(*) AS sub_count')
            ->group('parent_id')
            ->select()
            ->toArray();
        $subCountMap = array_column($subCountRows, 'sub_count', 'parent_id');
        $descendantCountMap = DistributionAgent::getDescendantCountMap($subUserIds);

        foreach ($lists as &$list) {
            $userId = (int)$list['user_id'];
            $list['avatar'] = FileService::getFileUrl($list['avatar']);
            $list['become_time'] = self::formatTime($list['become_time']);
            $list['recharge_amount'] = $rechargeMap[$userId]['amount'] ?? 0;
            $list['recharge_count'] = $rechargeMap[$userId]['order_count'] ?? 0;
            $list['sub_count'] = (int)($subCountMap[$userId] ?? 0);
            $list['descendant_count'] = (int)($descendantCountMap[$userId] ?? 0);
        }
        unset($list);

        return $lists;
    }

    /**
     * @notes 时间字段统一输出为日期字符串，无值时给空串而不是 0
     *   模型对 become_time 做了 timestamp 转换，取到的可能是日期字符串，也可能是 0
     * @param mixed $value
     * @return string
     */
    private static function formatTime($value): string
    {
        if (empty($value) || $value === '0') {
            return '';
        }
        return is_numeric($value) ? date('Y-m-d H:i:s', (int)$value) : (string)$value;
    }

    /**
     * @notes 下级总人数统计
     * @return int
     */
    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @notes 列表与统计共用的查询条件
     * @return \think\db\Query
     */
    private function baseQuery()
    {
        $query = DistributionAgent::alias('a')
            ->join('user u', 'a.user_id = u.id')
            ->where('a.parent_id', $this->getParentUserId());

        $keyword = trim((string)($this->params['user_keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('u.mobile|u.nickname', 'like', '%' . $keyword . '%');
        }

        return $query;
    }

    /**
     * @notes 要查看谁的下级：只允许传自己的直属下级，越权时回落为看自己的下级
     * @return int
     */
    private function getParentUserId(): int
    {
        try {
            return DistributionAgentLogic::checkViewableUserId(
                $this->getCurrentUserId(),
                (int)($this->params['user_id'] ?? 0),
                1
            );
        } catch (\Exception $e) {
            return $this->getCurrentUserId();
        }
    }

    /**
     * @notes 当前登录用户
     * @return int
     */
    private function getCurrentUserId(): int
    {
        return (int)($this->userId ?: ($this->request->userInfo['user_id'] ?? 0));
    }
}
