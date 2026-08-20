<?php

namespace app\common\service;

use app\common\model\user\User;

/**
 * 团队授权功能网关
 *
 * OEM 站长可对本企业空间关闭部分能力(la_config: team.enabled_features,团队维度)。
 * 之前该开关只用于前端展示,后端未拦截 → 直接调 API 可绕过。此服务在业务入口做真实拦截。
 *
 * 规则:
 *  - 用户不在任何企业(team_id=0):不受限(平台/主站能力由会员/算力体系管)。
 *  - 在企业空间:未配置 enabled_features = 全部开启;配置了则仅列表内的可用。
 *  - 团队主(owner)不受该开关限制(他自己就是站长,便于自查/演示)。
 */
class TeamFeatureService
{
    /** 全部可授权功能 key(与 TeamLogic::$allFeatures / 前端保持一致) */
    public const ALL = [
        'digital_human', 'video_mix', 'gaode_lead', 'ai_phone', 'ai_draw',
        'ai_ppt', 'sph_lead', 'ai_agent', 'llm_chat',
    ];

    /** key → 中文名(异常提示用) */
    public const LABELS = [
        'digital_human' => '数字人',
        'video_mix' => '视频混剪',
        'gaode_lead' => '地图获客',
        'ai_phone' => 'AI手机',
        'ai_draw' => 'AI绘画',
        'ai_ppt' => 'AI PPT',
        'sph_lead' => '视频号获客',
        'ai_agent' => '智能体',
        'llm_chat' => 'AI对话',
    ];

    /**
     * 某功能对当前用户是否可用
     */
    public static function isEnabled(int $userId, string $key): bool
    {
        if ($userId <= 0) {
            return true;
        }
        $user = User::where('id', $userId)->field('id,team_id,team_role')->findOrEmpty();
        if ($user->isEmpty() || (int)$user->team_id <= 0) {
            return true; // 非企业空间不受限
        }
        // 团队主不受限
        if ((int)$user->team_role === 2) {
            return true;
        }
        $enabled = ConfigService::get('team', 'enabled_features', null, (int)$user->team_id);
        if (!is_array($enabled)) {
            return true; // 未配置 = 全部开启
        }
        return in_array($key, $enabled, true);
    }

    /**
     * 硬拦截:功能被企业关闭则抛异常(接入各功能控制器入口)
     * @throws \Exception
     */
    public static function assertEnabled(int $userId, string $key): void
    {
        if (!self::isEnabled($userId, $key)) {
            $label = self::LABELS[$key] ?? $key;
            throw new \Exception('【' . $label . '】功能未对你所在企业开通，请联系企业管理员');
        }
    }
}
