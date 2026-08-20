<?php

namespace app\adminapi\lists\user;

use app\adminapi\lists\BaseAdminDataLists;
use app\api\logic\LoginLogic;
use app\common\enum\user\UserTerminalEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;
use app\common\model\user\UserLevel;

/**
 * 用户列表
 * Class UserLists
 * @package app\adminapi\lists\user
 */
class UserLists extends BaseAdminDataLists implements ListsExcelInterface
{

    /**
     * @notes 搜索条件
     * @return array
     * @author 段誉
     * @date 2022/9/22 15:50
     */
    public function setSearch(): array
    {
        $allowSearch = ['keyword', 'channel', 'level_id', 'create_time_start', 'create_time_end'];
        return array_intersect(array_keys($this->params), $allowSearch);
    }


    /**
     * @notes 获取用户列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/22 15:50
     */
    public function lists(): array
    {
        $field = "id,sn,nickname,sex,avatar,account,tokens,mobile,channel,create_time,user_type,level_id";
        $lists = $this->buildQuery()
            ->limit($this->limitOffset, $this->limitLength)
            ->field($field)
            ->order('id desc')
            ->select()
            ->toArray();

        $this->appendLevelName($lists);
        $this->appendInviteSource($lists);

        foreach ($lists as &$item) {
            $item['channel'] = UserTerminalEnum::getTermInalDesc($item['channel']);
        }
        unset($item);

        return $lists;
    }

    /**
     * @notes 构建用户列表查询（支持 only_agent=1 仅返回有效代理）
     */
    private function buildQuery()
    {
        $query = User::withSearch($this->setSearch(), $this->params);
        if ($this->isOnlyAgent()) {
            // 与邀请码校验一致：status=1 且 level>0
            $userTable = (new User())->getTable();
            $agentTable = (new DistributionAgent())->getTable();
            $query->whereExists(function ($sub) use ($userTable, $agentTable) {
                $sub->table($agentTable)
                    ->whereRaw("{$agentTable}.user_id = {$userTable}.id")
                    ->where("{$agentTable}.level", '>', 0)
                    ->where("{$agentTable}.status", 1);
            });
        }
        return $query;
    }

    private function isOnlyAgent(): bool
    {
        return isset($this->params['only_agent']) && (int)$this->params['only_agent'] === 1;
    }

    /**
     * @notes 批量补充会员等级名称，避免按行查询
     */
    private function appendLevelName(array &$lists): void
    {
        if (empty($lists)) {
            return;
        }

        $levelIds = [];
        foreach ($lists as $item) {
            $levelId = (int)($item['level_id'] ?? 0);
            if ($levelId > 0) {
                $levelIds[] = $levelId;
            }
        }
        $levelIds = array_values(array_unique($levelIds));

        $levelMap = [];
        if (!empty($levelIds)) {
            $levelLists = UserLevel::whereIn('id', $levelIds)
                ->column('level_name', 'id');
            foreach ($levelLists as $id => $name) {
                $levelMap[(int)$id] = (string)$name;
            }
        }

        foreach ($lists as &$item) {
            $levelId = (int)($item['level_id'] ?? 0);
            $item['level_name'] = $levelId > 0 ? ($levelMap[$levelId] ?? '') : '';
        }
        unset($item);
    }

    /**
     * @notes 补充邀请来源，不影响用户列表原有搜索和分页
     */
    private function appendInviteSource(array &$lists): void
    {
        if (empty($lists)) {
            return;
        }

        $defaultInviteSource = LoginLogic::getDefaultInviteSource();
        $userIds = array_column($lists, 'id');
        $agentLists = DistributionAgent::whereIn('user_id', $userIds)
            ->field('user_id,parent_id')
            ->select()
            ->toArray();

        $agentMap = [];
        $parentIds = [];
        foreach ($agentLists as $agent) {
            $userId = (int)$agent['user_id'];
            $parentId = (int)$agent['parent_id'];
            $agentMap[$userId] = $parentId;
            if ($parentId > 0) {
                $parentIds[] = $parentId;
            }
        }

        $parentMap = [];
        $parentIds = array_values(array_unique($parentIds));
        if (!empty($parentIds)) {
            $parentLists = User::whereIn('id', $parentIds)
                ->field('id,sn,nickname')
                ->select()
                ->toArray();
            foreach ($parentLists as $parent) {
                $parentMap[(int)$parent['id']] = $parent;
            }
        }

        foreach ($lists as &$item) {
            $parentId = (int)($agentMap[(int)$item['id']] ?? 0);
            $item['distribution_parent_id'] = $parentId;
            $item['distribution_parent_sn'] = '';
            $item['distribution_parent_name'] = $defaultInviteSource;
            $item['invite_source'] = $defaultInviteSource;

            if ($parentId <= 0) {
                continue;
            }

            $parent = $parentMap[$parentId] ?? [];
            $parentSn = (string)($parent['sn'] ?? '');
            $parentName = (string)($parent['nickname'] ?? '');
            $item['distribution_parent_sn'] = $parentSn;
            $item['distribution_parent_name'] = $parentName !== '' ? $parentName : ($parentSn !== '' ? $parentSn : '未知用户');
            $item['invite_source'] = $parentSn !== ''
                ? $item['distribution_parent_name'] . '(' . $parentSn . ')'
                : $item['distribution_parent_name'];
        }
        unset($item);
    }

    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2022/9/22 15:51
     */
    public function count(): int
    {
        return $this->buildQuery()->count();
    }


    /**
     * @notes 导出文件名
     * @return string
     * @author 段誉
     * @date 2022/11/24 16:17
     */
    public function setFileName(): string
    {
        return '用户列表';
    }


    /**
     * @notes 导出字段
     * @return string[]
     * @author 段誉
     * @date 2022/11/24 16:17
     */
    public function setExcelFields(): array
    {
        return [
            'sn' => '用户编号',
            'nickname' => '用户昵称',
            'account' => '账号',
            'mobile' => '手机号码',
            'channel' => '注册来源',
            'invite_source' => '邀请来源',
            'create_time' => '注册时间',
        ];
    }
}
