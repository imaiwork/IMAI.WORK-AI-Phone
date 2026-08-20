<?php

namespace app\api\lists\distributionAgent;

use app\api\lists\BaseApiDataLists;
use app\api\logic\distributionAgent\DistributionAgentLogic;
use app\common\logic\RechargeStatsLogic;

/**
 * 下级充值流水明细
 *
 * 只能查看自己或直属下级的充值流水，越权时回落为查看自己。
 *
 * Class DistributionAgentRechargeLists
 * @package app\api\lists\distributionAgent
 */
class DistributionAgentRechargeLists extends BaseApiDataLists
{
    /** lists() 与 count() 都要用，避免重复查库 */
    private ?int $targetUserId = null;
    private ?int $sinceTime = null;

    /**
     * @notes 充值流水列表
     * @return array
     */
    public function lists(): array
    {
        return RechargeStatsLogic::getUserRecords(
            $this->getTargetUserId(),
            $this->limitOffset,
            $this->limitLength,
            $this->getSinceTime()
        );
    }

    /**
     * @notes 充值流水笔数
     * @return int
     */
    public function count(): int
    {
        return RechargeStatsLogic::getUserRecordCount($this->getTargetUserId(), $this->getSinceTime());
    }

    /**
     * @notes 明细与「充值业绩」同口径：按当前代理自己的业绩清零水位线过滤
     * @return int
     */
    private function getSinceTime(): int
    {
        if ($this->sinceTime === null) {
            $this->sinceTime = RechargeStatsLogic::getSubStatsSinceTime(
                $this->getCurrentUserId(),
                $this->getTargetUserId()
            );
        }
        return $this->sinceTime;
    }

    /**
     * @notes 要查看谁的充值流水；越权时回落为当前用户
     * @return int
     */
    private function getTargetUserId(): int
    {
        if ($this->targetUserId === null) {
            try {
                $this->targetUserId = DistributionAgentLogic::checkViewableUserId(
                    $this->getCurrentUserId(),
                    (int)($this->params['user_id'] ?? 0)
                );
            } catch (\Exception $e) {
                $this->targetUserId = $this->getCurrentUserId();
            }
        }
        return $this->targetUserId;
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
