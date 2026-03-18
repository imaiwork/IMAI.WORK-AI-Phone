<?php

namespace app\api\lists\distributionAgent;

use app\api\lists\BaseApiDataLists;
use app\common\model\distribution\DistributionAgent;
use app\common\service\FileService;
use think\facade\Db;

/**
 * 分销代理前端下级列表
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
        // 确保能安全拿到 userId
        $userId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);

        $query = DistributionAgent::alias('a')
            ->join('user u', 'a.user_id = u.id')
            ->where('a.parent_id', $userId)
            ->field('a.user_id, a.parent_id, a.level, a.status, a.become_time, u.nickname, u.avatar, u.mobile, u.tokens');

        if (!empty($this->params['user_keyword']) && $this->params['user_keyword'] != '') {
            $query->where('u.mobile|u.nickname', 'like', '%' . $this->params['user_keyword'] . '%');
        }

        $lists = $query->order('a.become_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$list) {
            $list['avatar'] = FileService::getFileUrl($list['avatar']);
        }
        return $lists;
    }

    /**
     * @notes 下级总人数统计
     * @return int
     */
    public function count(): int
    {
        $userId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);

        $query = DistributionAgent::alias('a')
            ->join('user u', 'a.user_id = u.id')
            ->where('a.parent_id', $userId);

        if (!empty($this->params['mobile']) && $this->params['mobile'] != '') {
            $query->where('u.mobile', $this->params['mobile']);
        }

        return $query->count();
    }
}
