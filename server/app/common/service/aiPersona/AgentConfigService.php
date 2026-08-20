<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\kb\KbRobot;
use app\common\service\TeamBillingService;
use app\common\service\TeamContextService;
use Exception;

class AgentConfigService
{
    public static function buildCustomerServiceData(AiPersonaAgentConfig $agentConfig, array $params): array
    {
        $data = [
            'comment_enabled' => self::intValue($params, 'comment_enabled', $agentConfig->comment_enabled),
            'dm_enabled' => self::intValue($params, 'dm_enabled', $agentConfig->dm_enabled),
            'wechat_chat_enabled' => self::intValue($params, 'wechat_chat_enabled', $agentConfig->wechat_chat_enabled),
            'wechat_chat_agent_id' => self::intValue($params, 'wechat_chat_agent_id', $agentConfig->wechat_chat_agent_id),
            'wechat_chat_type' => self::intValue($params, 'wechat_chat_type', $agentConfig->wechat_chat_type ?: 1),
            'wechat_chat_speech' => self::speechValue($params, 'wechat_chat_speech', $agentConfig->wechat_chat_speech),
            'moments_enabled' => self::intValue($params, 'moments_enabled', $agentConfig->moments_enabled),
            'moments_agent_id' => self::intValue($params, 'moments_agent_id', $agentConfig->moments_agent_id),
            'moments_action' => self::intValue($params, 'moments_action', $agentConfig->moments_action ?: 3),
            'moments_type' => self::intValue($params, 'moments_type', $agentConfig->moments_type ?: 1),
            'moments_speech' => self::speechValue($params, 'moments_speech', $agentConfig->moments_speech),
            'shutoff_comment_type' => self::intValue($params, 'shutoff_comment_type', $agentConfig->shutoff_comment_type ?: 1),
            'shutoff_comment_agent_id' => self::intValue($params, 'shutoff_comment_agent_id', $agentConfig->shutoff_comment_agent_id),
            'shutoff_comment_speech' => self::speechValue($params, 'shutoff_comment_speech', $agentConfig->shutoff_comment_speech),
            'shutoff_msg_type' => self::intValue($params, 'shutoff_msg_type', $agentConfig->shutoff_msg_type ?: 1),
            'shutoff_msg_agent_id' => self::intValue($params, 'shutoff_msg_agent_id', $agentConfig->shutoff_msg_agent_id),
            'shutoff_msg_speech' => self::speechValue($params, 'shutoff_msg_speech', $agentConfig->shutoff_msg_speech),
            'update_time' => time(),
        ];

        $hasPlatformAgentConfig = array_key_exists('platform_agent_config', $params);
        $hasWechatChatConfig = self::hasWechatChatConfig($params);
        if ($hasPlatformAgentConfig || $hasWechatChatConfig) {
            $incomingPlatformConfig = [];
            if ($hasPlatformAgentConfig) {
                self::validatePlatformAgentConfig($params['platform_agent_config']);
                $incomingPlatformConfig = AiPersonaAgentConfig::normalizePlatformAgentConfig($params['platform_agent_config'], false);
            }
            if ($hasWechatChatConfig) {
                $incomingPlatformConfig[AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE]['dm'] = self::buildWechatDmConfig($data);
            }

            $data['platform_agent_config'] = AiPersonaAgentConfig::mergePlatformAgentConfig(
                $agentConfig->platform_agent_config,
                $incomingPlatformConfig
            );
        }

        if (!$hasWechatChatConfig && $hasPlatformAgentConfig) {
            $incomingPlatformConfig = AiPersonaAgentConfig::normalizePlatformAgentConfig($params['platform_agent_config'], false);
            self::syncWechatChatDataFromPlatformConfig($data, $incomingPlatformConfig);
        }

        return $data;
    }

    public static function formatDetailData(AiPersonaAgentConfig $agentConfig): array
    {
        $data = $agentConfig->toArray();
        $platformConfig = AiPersonaAgentConfig::normalizePlatformAgentConfig($agentConfig->platform_agent_config);
        self::fillWechatPlatformConfigFromLegacy($platformConfig, $data);
        self::syncWechatChatDataFromPlatformConfig($data, $platformConfig);
        $userId = (int)($data['user_id'] ?? 0);
        $agents = self::resolveBoundAgents($userId, $data, $platformConfig);

        foreach ($platformConfig as $accountType => $platformItem) {
            foreach (['dm', 'comment'] as $scene) {
                $agentId = (int)($platformItem[$scene]['agent_id'] ?? 0);
                $meta = self::agentMetaById($agents, $agentId);
                $platformConfig[$accountType][$scene]['agent_name'] = $meta['name'];
                $platformConfig[$accountType][$scene]['agent_status'] = $meta['status'];
                $platformConfig[$accountType][$scene]['agent_status_text'] = $meta['status_text'];
            }
        }

        $data['platform_agent_config'] = $platformConfig;
        self::attachTopLevelAgentMeta($data, $agents, 'comment_agent');
        self::attachTopLevelAgentMeta($data, $agents, 'dm_agent');
        self::attachTopLevelAgentMeta($data, $agents, 'wechat_chat_agent');
        self::attachTopLevelAgentMeta($data, $agents, 'moments_agent');
        self::attachTopLevelAgentMeta($data, $agents, 'shutoff_comment_agent');
        self::attachTopLevelAgentMeta($data, $agents, 'shutoff_msg_agent');
        $data['shutoff_commnet_agent_name'] = $data['shutoff_comment_agent_name'];
        $data['shuoff_msg_agent_name'] = $data['shutoff_msg_agent_name'];

        return $data;
    }

    /**
     * 当前空间下智能体是否可用(绑定保留,仅判定可见性)。
     * 系统预置(user_id=0,team_id=0)始终可用;
     * 本人创建始终可用(切换团队后IP人设绑定的智能体不失效);
     * 企业空间他人共享:创建者仍为当前企业有效成员,且资源归属匹配
     *   (正数 team_id / 退团回收负标记 -team_id / 历史回收成 0 后创建者已回团)。
     */
    public static function isAgentUsableInCurrentSpace(int $userId, array $robot): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $robotUserId = (int)($robot['user_id'] ?? 0);
        $robotTeamId = (int)($robot['team_id'] ?? 0);
        // 系统预置
        if ($robotUserId === 0 && $robotTeamId === 0) {
            return true;
        }
        // 本人创建的智能体:切换团队后仍可用(与列表「本人创建始终可见」对齐;IP人设绑定不丢)
        if ($robotUserId === $userId) {
            return true;
        }
        $teamId = TeamContextService::currentTeamId($userId);
        if ($teamId > 0) {
            // 资源跟人:创建者是本企业有效成员即可用(不限创建时空间;退团/移除/到期自动失效)
            return $robotUserId > 0
                && in_array($robotUserId, TeamBillingService::activeMemberUserIds($teamId), true);
        }
        // 个人空间下他人资源不可用
        return false;
    }

    /**
     * @return array{usable:bool,status:string,status_text:string,name:string}
     */
    public static function resolveAgentUsability(int $userId, int $agentId): array
    {
        if ($agentId <= 0) {
            return [
                'usable' => false,
                'status' => 'ok',
                'status_text' => '',
                'name' => '',
            ];
        }
        $robot = KbRobot::withTrashed()
            ->where('id', $agentId)
            ->field('id,name,user_id,team_id,delete_time')
            ->findOrEmpty();
        if ($robot->isEmpty()) {
            return [
                'usable' => false,
                'status' => 'deleted',
                'status_text' => '智能体已被删除，请重新绑定',
                'name' => '',
            ];
        }
        $name = (string)($robot->name ?? '');
        if (!empty($robot->delete_time)) {
            return [
                'usable' => false,
                'status' => 'deleted',
                'status_text' => '智能体已被删除，请重新绑定',
                'name' => $name,
            ];
        }
        if (!self::isAgentUsableInCurrentSpace($userId, $robot->toArray())) {
            return [
                'usable' => false,
                'status' => 'unavailable',
                'status_text' => '当前空间不可用，请重新绑定（切回原团队可自动恢复）',
                'name' => $name,
            ];
        }
        return [
            'usable' => true,
            'status' => 'ok',
            'status_text' => '',
            'name' => $name,
        ];
    }

    private static function syncWechatChatDataFromPlatformConfig(array &$data, array $platformConfig): void
    {
        if (!isset($platformConfig[AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE]['dm'])) {
            return;
        }

        $wechatDmConfig = $platformConfig[AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE]['dm'];
        $data['wechat_chat_type'] = (int)($wechatDmConfig['type'] ?? 1);
        $data['wechat_chat_agent_id'] = (int)($wechatDmConfig['agent_id'] ?? 0);
        $data['wechat_chat_speech'] = self::speechValue($wechatDmConfig, 'speech', []);
    }

    private static function hasWechatChatConfig(array $params): bool
    {
        return array_key_exists('wechat_chat_type', $params)
            || array_key_exists('wechat_chat_agent_id', $params)
            || array_key_exists('wechat_chat_speech', $params);
    }

    private static function buildWechatDmConfig(array $data): array
    {
        $type = (int)($data['wechat_chat_type'] ?? 1);
        if (!in_array($type, [1, 2], true)) {
            $type = 1;
        }

        return [
            'type' => $type,
            'agent_id' => (int)($data['wechat_chat_agent_id'] ?? 0),
            'speech' => self::speechValue($data, 'wechat_chat_speech', []),
        ];
    }

    private static function fillWechatPlatformConfigFromLegacy(array &$platformConfig, array $data): void
    {
        $currentConfig = $platformConfig[AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE]['dm'] ?? [];
        if (self::hasReplyContent($currentConfig)) {
            return;
        }

        $legacyConfig = [
            'type' => (int)($data['wechat_chat_type'] ?? 1),
            'agent_id' => (int)($data['wechat_chat_agent_id'] ?? 0),
            'speech' => self::speechValue($data, 'wechat_chat_speech', []),
        ];
        if (!self::hasReplyContent($legacyConfig)) {
            return;
        }

        $platformConfig[AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE]['dm'] = $legacyConfig;
    }

    private static function hasReplyContent(array $item): bool
    {
        return ((int)($item['type'] ?? 1) === 1 && (int)($item['agent_id'] ?? 0) > 0)
            || ((int)($item['type'] ?? 1) === 2 && !empty($item['speech']));
    }

    private static function validatePlatformAgentConfig($config): void
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (!is_array($decoded)) {
                throw new Exception('社媒平台配置格式错误');
            }
            $config = $decoded;
        }

        if (!is_array($config)) {
            throw new Exception('社媒平台配置格式错误');
        }

        foreach ($config as $accountType => $item) {
            if (!in_array((int)$accountType, AiPersonaAgentConfig::PLATFORM_ACCOUNT_TYPES, true)) {
                throw new Exception('社媒平台类型错误');
            }
            if (!is_array($item)) {
                throw new Exception('社媒平台配置项格式错误');
            }
            if (isset($item['type'])) {
                self::validatePlatformReplyItem($item);
                continue;
            }
            foreach (['dm', 'comment'] as $scene) {
                if (isset($item[$scene])) {
                    self::validatePlatformReplyItem($item[$scene]);
                }
            }
        }
    }

    private static function validatePlatformReplyItem(array $item): void
    {
        if (isset($item['type']) && !in_array((int)$item['type'], [1, 2], true)) {
            throw new Exception('社媒平台回复方式错误');
        }
    }

    private static function intValue(array $params, string $key, $default): int
    {
        return array_key_exists($key, $params) ? (int)$params[$key] : (int)$default;
    }

    private static function speechValue(array $params, string $key, $default): array
    {
        $value = array_key_exists($key, $params) ? $params[$key] : $default;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [$value];
        }
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @return array<int, array{name:string,status:string,status_text:string,usable:bool}>
     */
    private static function resolveBoundAgents(int $userId, array $data, array $platformConfig): array
    {
        $ids = [
            (int)($data['comment_agent_id'] ?? 0),
            (int)($data['dm_agent_id'] ?? 0),
            (int)($data['wechat_chat_agent_id'] ?? 0),
            (int)($data['moments_agent_id'] ?? 0),
            (int)($data['shutoff_comment_agent_id'] ?? 0),
            (int)($data['shutoff_msg_agent_id'] ?? 0),
        ];

        foreach ($platformConfig as $platformItem) {
            foreach (['dm', 'comment'] as $scene) {
                $ids[] = (int)($platformItem[$scene]['agent_id'] ?? 0);
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        $result = [];
        foreach ($ids as $id) {
            $result[(int)$id] = self::resolveAgentUsability($userId, (int)$id);
        }
        return $result;
    }

    /**
     * @param array<int, array{name:string,status:string,status_text:string,usable:bool}> $agents
     * @return array{name:string,status:string,status_text:string,usable:bool}
     */
    private static function agentMetaById(array $agents, $id): array
    {
        $id = (int)$id;
        if ($id <= 0) {
            return [
                'name' => '',
                'status' => 'ok',
                'status_text' => '',
                'usable' => true,
            ];
        }
        return $agents[$id] ?? [
            'name' => '',
            'status' => 'deleted',
            'status_text' => '智能体已被删除，请重新绑定',
            'usable' => false,
        ];
    }

    /**
     * @param array<int, array{name:string,status:string,status_text:string,usable:bool}> $agents
     */
    private static function attachTopLevelAgentMeta(array &$data, array $agents, string $prefix): void
    {
        $idKey = $prefix . '_id';
        $meta = self::agentMetaById($agents, $data[$idKey] ?? 0);
        $data[$prefix . '_name'] = $meta['name'];
        $data[$prefix . '_status'] = $meta['status'];
        $data[$prefix . '_status_text'] = $meta['status_text'];
    }
}
