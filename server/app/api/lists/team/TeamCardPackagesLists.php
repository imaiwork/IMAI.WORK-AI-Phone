<?php

namespace app\api\lists\team;

use app\api\lists\BaseApiDataLists;
use app\common\model\cardcode\CardPackage;
use app\common\model\user\User;

/**
 * 团队自有卡密套餐列表(团队主管理与制卡下拉共用)
 * Class TeamCardPackagesLists
 * @package app\api\lists\team
 */
class TeamCardPackagesLists extends BaseApiDataLists
{
    private function teamId(): int
    {
        $userId = $this->userId ?: ($this->request->userInfo['user_id'] ?? 0);
        return (int)User::where('id', $userId)->value('team_id');
    }

    public function lists(): array
    {
        $tid = $this->teamId();
        if ($tid <= 0) {
            return [];
        }
        $lists = CardPackage::where('team_id', $tid)
            ->where('delete_time', null)
            ->order('sort desc, id desc')
            ->field('id, name, tokens, expire_time, status')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($lists as &$item) {
            $item['expire_time_desc'] = $item['expire_time'] > 0 ? date('Y-m-d', $item['expire_time']) : '永久';
        }
        return $lists;
    }

    public function count(): int
    {
        $tid = $this->teamId();
        if ($tid <= 0) {
            return 0;
        }
        return CardPackage::where('team_id', $tid)->where('delete_time', null)->count();
    }
}
