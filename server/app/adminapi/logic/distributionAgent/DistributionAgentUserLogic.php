<?php

namespace app\adminapi\logic\distributionAgent;

use app\common\logic\BaseLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;

/**
 * 分销代理用户逻辑
 * Class DistributionAgentUserLogic
 * @package app\adminapi\logic\distributionAgent
 */
class DistributionAgentUserLogic extends BaseLogic
{
    /**
     * @notes 获取代理概况详情
     * @param int $userId
     * @return array
     */
    public static function detail(int $userId)
    {
        $user = User::field('id,sn,nickname,avatar,real_name')->findOrEmpty($userId);
        if ($user->isEmpty()) {
            return [];
        }

        $agent = DistributionAgent::alias('DA')
            ->leftJoin('user U', 'DA.parent_id = U.id')
            ->where('DA.user_id', $userId)
            ->field('DA.*, U.nickname as parent_nickname')
            ->findOrEmpty();


        // 代理状态相关字段
        $detail = [
            'user_id' => $user->id,
            'sn' => $user->sn,
            'nickname' => $user->nickname,
            'parent_nickname' => $agent->parent_nickname ?? '系统',
            'avatar' => $user->avatar,
            'real_name' => $user->real_name,
            'level' => $agent->level ?? 0,
            'status' => $agent->status ?? 0,
            'become_time' => $agent->become_time ?? 0,
            'qr_code' => !empty($agent->qr_code) ? \app\common\service\FileService::getFileUrl($agent->qr_code) : '',
            'downline_count' => DistributionAgent::where(['parent_id' => $userId])->count(), // 下一级人数
        ];

        return $detail;
    }
}
