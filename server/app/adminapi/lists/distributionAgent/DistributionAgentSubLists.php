<?php

namespace app\adminapi\lists\distributionAgent;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\distribution\DistributionAgent;
use app\common\service\FileService;

/**
 * 代理用户下级列表
 * Class DistributionAgentSubLists
 * @package app\adminapi\lists\distributionAgent
 */
class DistributionAgentSubLists extends BaseAdminDataLists implements ListsExtendInterface
{
    /**
     * @notes 代理记录
     * @return array
     */
    public function lists(): array
    {
        $lists = DistributionAgent::alias('DA')
            ->leftJoin('user U', 'DA.user_id = U.id')
            ->leftJoin('distribution_agent PA', 'DA.parent_id = PA.user_id')
            ->leftJoin('user PU', 'PA.user_id = PU.id')
            ->field('DA.id, DA.user_id, DA.level, DA.status, DA.parent_id, DA.become_time, DA.create_time, U.sn, U.nickname, U.avatar, PU.nickname as parent_nickname')
            ->limit($this->limitOffset, $this->limitLength)
            ->where($this->setSearch())
            ->order('DA.become_time desc, DA.id desc')
            ->select()
            ->toArray();

        foreach ($lists as &$list) {
            $list['avatar'] = FileService::getFileUrl($list['avatar']);
            $list['parent_nickname'] = $list['parent_id'] == 0 ? '系统' : $list['parent_nickname'];
        }
        return $lists;
    }

    /**
     * @notes 获取总数量及下级人数统计
     * @return int
     */
    public function count(): int
    {
        return DistributionAgent::alias('DA')
            ->leftJoin('user U', 'DA.user_id = U.id')
            ->where($this->setSearch())
            ->count();
    }

    /**
     * @notes 拓展返回内容（返回下一级总人数，下二级总人数）
     * @return array
     */
    public function extend()
    {
        $userId = $this->params['user_id'] ?? 0;
        if (!$userId) {
            return ['level1_count' => 0, 'level2_count' => 0, 'level1_agent_count' => 0, 'level2_agent_count' => 0];
        }

        $currentUserLevel = DistributionAgent::where('user_id', $userId)->value('level') ?: 0;

        $level1Count = 0;
        $level2Count = 0;
        $level1AgentCount = 0;
        $level2AgentCount = 0;

        if ($currentUserLevel == 1) {
            // 下一级：包含level=0的普通用户 + level=2的代理
            $level1Count = DistributionAgent::where('parent_id', $userId)->whereIn('level', [0, 2])->count();
            // 下级分销商：仅level=2的代理
            $level1AgentCount = DistributionAgent::where('parent_id', $userId)->where('level', 2)->count();

            // 下二级：包含level=3的代理
            $level2Count = DistributionAgent::where('parent_id', $userId)->where('level', 3)->count();
            // 下二级分销商：仅level=3的代理
            $level2AgentCount = $level2Count;
        } elseif ($currentUserLevel == 2) {
            // 下一级：包含level=0的普通用户 + level=3的代理
            $level1Count = DistributionAgent::where('parent_id', $userId)->whereIn('level', [0, 3])->count();
            $level1AgentCount = DistributionAgent::where('parent_id', $userId)->where('level', 3)->count();
        } elseif ($currentUserLevel == 3) {
            // 下一级：仅普通用户
            $level1Count = DistributionAgent::where('parent_id', $userId)->where('level', 0)->count();
            $level1AgentCount = 0;
        }

        return [
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
            'level1_agent_count' => $level1AgentCount,
            'level2_agent_count' => $level2AgentCount,
        ];
    }

    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch()
    {
        $where = [];

        // 必须要有个根查询人，不然就是查全站了
        if (isset($this->params['user_id']) && $this->params['user_id']) {
            $userId = (int) $this->params['user_id'];
            $currentUserLevel = DistributionAgent::where('user_id', $userId)->value('level') ?: 0;
            $hierarchy = $this->params['hierarchy'] ?? 'all'; // all, level1, level2

            // 1、仅查询亲直属下级
            $where[] = ['DA.parent_id', '=', $userId];

            // 2、根据上一级代理级别区分下级
            if ($hierarchy === 'level1') {
                if ($currentUserLevel == 1) {
                    $where[] = ['DA.level', '=', 2];
                } elseif ($currentUserLevel == 2) {
                    $where[] = ['DA.level', '=', 3];
                } elseif ($currentUserLevel == 3) {
                    $where[] = ['DA.level', '=', -1];
                } else {
                    $where[] = ['DA.level', '=', -1]; // 其他级别没有下级
                }
            } elseif ($hierarchy === 'level2') {
                if ($currentUserLevel == 1) {
                    $where[] = ['DA.level', '=', 3];
                } else {
                    $where[] = ['DA.level', '=', -1]; // 其他级别没有下二级
                }
            }
        }

        // 关键词：用户信息（id、sn，昵称合并）
        if (isset($this->params['user_keyword']) && $this->params['user_keyword'] !== '') {
            $keyword = $this->params['user_keyword'];
            $where[] = ['U.id|U.sn|U.nickname', 'like', '%' . $keyword . '%'];
        }

        // 分销资格过滤（普通用户=0，代理用户反之为非0）
        if (isset($this->params['is_agent']) && $this->params['is_agent'] !== '') {
            if ($this->params['is_agent'] == 0) {
                // 仅查询代理用户的情况下，查普通用户直接查不到
                $where[] = ['DA.level', '=', 0];
            } else {
                $where[] = ['DA.level', '>', 0];
            }
        }

        // 分销状态过滤
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['DA.status', '=', $this->params['status']];
        }

        return $where;
    }
}
