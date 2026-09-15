<?php

namespace app\adminapi\lists\distributionAgent;

use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\setting\DistributionAgentConfigLogic;
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
        $userId = (int)($this->params['user_id'] ?? 0);
        $currentUserLevel = $userId ? self::getUserLevel($userId) : 0;
        // 普通用户不发展下级
        if (!$userId || $currentUserLevel <= 0) {
            return ['level1_count' => 0, 'level2_count' => 0, 'level1_agent_count' => 0, 'level2_agent_count' => 0];
        }

        $lowerLevels = self::getLowerLevels($currentUserLevel);
        $nextLevel = $lowerLevels[0] ?? null;
        $secondLevel = $lowerLevels[1] ?? null;

        // 下一级：直属下级中的普通用户 + 低一档的代理
        $level1Count = DistributionAgent::where('parent_id', $userId)
            ->whereIn('level', $nextLevel === null ? [0] : [0, $nextLevel])
            ->count();
        // 下级分销商：仅低一档的代理
        $level1AgentCount = $nextLevel === null ? 0 : DistributionAgent::where('parent_id', $userId)
            ->where('level', $nextLevel)
            ->count();
        // 下二级：直属下级中低两档的代理
        $level2Count = $secondLevel === null ? 0 : DistributionAgent::where('parent_id', $userId)
            ->where('level', $secondLevel)
            ->count();

        return [
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
            'level1_agent_count' => $level1AgentCount,
            'level2_agent_count' => $level2Count,
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
            $hierarchy = $this->params['hierarchy'] ?? 'all'; // all, level1, level2

            // 1、仅查询亲直属下级
            $where[] = ['DA.parent_id', '=', $userId];

            // 2、根据上一级代理级别区分下级：低一档算下一级，低两档算下二级
            if ($hierarchy === 'level1' || $hierarchy === 'level2') {
                $lowerLevels = self::getLowerLevels(self::getUserLevel($userId));
                $targetLevel = $hierarchy === 'level1' ? ($lowerLevels[0] ?? null) : ($lowerLevels[1] ?? null);
                // 没有对应层级时用查不到的等级，保持空结果
                $where[] = ['DA.level', '=', $targetLevel ?? -1];
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

    /**
     * @notes 用户当前代理等级，非代理为 0
     * @param int $userId
     * @return int
     */
    private static function getUserLevel(int $userId): int
    {
        return (int)(DistributionAgent::where('user_id', $userId)->value('level') ?: 0);
    }

    /**
     * @notes 比当前等级更低的等级值，升序（level 数值越大等级越低）
     *   等级清单由后台「代理等级」配置，数量可增删，不能按固定 1/2/3 推算
     * @param int $currentLevel
     * @return array
     */
    private static function getLowerLevels(int $currentLevel): array
    {
        if ($currentLevel <= 0) {
            return [];
        }

        return array_values(array_filter(
            DistributionAgentConfigLogic::getLevelValues(),
            static function ($level) use ($currentLevel) {
                return $level > $currentLevel;
            }
        ));
    }
}
