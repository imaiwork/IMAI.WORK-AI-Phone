<?php

namespace app\api\lists\team;

use app\api\lists\BaseApiDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;

/**
 * 团队成员算力消耗明细(团队主查看本团队某成员)
 * Class TeamMemberConsumptionLists
 * @package app\api\lists\team
 */
class TeamMemberConsumptionLists extends BaseApiDataLists
{
    /**
     * @notes 校验目标成员是否属于当前团队主的团队
     */
    private function targetUserId(): int
    {
        $operatorId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);
        $operator = User::findOrEmpty($operatorId);
        $targetId = (int)($this->params['user_id'] ?? 0);
        // 创始人/管理员可查看
        if ($operator->isEmpty() || !in_array((int)$operator->team_role, [2, 3], true) || $targetId <= 0) {
            return 0;
        }
        // 目标须为本企业成员(成员关系表)或散客归属
        $isMember = \app\common\model\team\TeamMember::where('team_id', $operator->team_id)
            ->where('user_id', $targetId)->count() > 0;
        $isAttributed = User::where('id', $targetId)->where('team_id', $operator->team_id)->count() > 0;
        return ($isMember || $isAttributed) ? $targetId : 0;
    }

    /** 当前操作者所在企业id(用于按企业隔离明细) */
    private function ownerTeamId(): int
    {
        $operatorId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);
        return (int)User::where('id', $operatorId)->value('team_id');
    }

    public function lists(): array
    {
        $targetId = $this->targetUserId();
        if ($targetId === 0) {
            return [];
        }
        // 仅本企业空间内的消耗(team_id=当前企业);成员在别企业/个人空间的消费不可见
        $teamId = $this->ownerTeamId();
        $lists = UserTokensLog::where('user_id', $targetId)
            ->where('team_id', $teamId)
            ->field('id,change_type,action,change_amount,left_tokens,remark,create_time')
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($lists as &$item) {
            $item['change_type_desc'] = AccountLogEnum::getChangeTypeDesc($item['change_type']);
            $item['action_desc'] = AccountLogEnum::getActionDesc($item['action']);
        }
        return $lists;
    }

    public function count(): int
    {
        $targetId = $this->targetUserId();
        if ($targetId === 0) {
            return 0;
        }
        return UserTokensLog::where('user_id', $targetId)->where('team_id', $this->ownerTeamId())->count();
    }
}
