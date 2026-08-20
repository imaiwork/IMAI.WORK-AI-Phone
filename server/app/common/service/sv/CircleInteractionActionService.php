<?php

namespace app\common\service\sv;

use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\model\auto\AutoDeviceCircleLikeReplyConfig;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;

/**
 * 朋友圈点赞/评论动作解析
 * action 口径：1仅点赞 2仅评论 3点赞+评论 0无动作
 */
class CircleInteractionActionService
{
    public const ACTION_NONE = 0;
    public const ACTION_LIKE = 1;
    public const ACTION_COMMENT = 2;
    public const ACTION_BOTH = 3;

    /**
     * @return array{is_like:int,is_comment:int}
     */
    public static function normalizeFlags(mixed $isLike, mixed $isComment): array
    {
        return [
            'is_like' => ((int)$isLike === 1) ? 1 : 0,
            'is_comment' => ((int)$isComment === 1) ? 1 : 0,
        ];
    }

    /**
     * 由开关解析 action：1仅点赞 2仅评论 3点赞+评论 0无动作
     */
    public static function resolveAction(mixed $isLike, mixed $isComment): int
    {
        $flags = self::normalizeFlags($isLike, $isComment);
        if ($flags['is_like'] === 1 && $flags['is_comment'] === 1) {
            return self::ACTION_BOTH;
        }
        if ($flags['is_like'] === 1) {
            return self::ACTION_LIKE;
        }
        if ($flags['is_comment'] === 1) {
            return self::ACTION_COMMENT;
        }
        return self::ACTION_NONE;
    }

    /**
     * moments_action → is_like/is_comment
     *
     * @return array{is_like:int,is_comment:int}
     */
    public static function actionToFlags(mixed $momentsAction): array
    {
        $device = self::toDeviceFlags($momentsAction);
        return [
            'is_like' => $device['is_like'],
            'is_comment' => $device['is_comment'],
        ];
    }

    /**
     * is_like/is_comment → moments_action
     */
    public static function flagsToMomentsAction(mixed $isLike, mixed $isComment): int
    {
        return self::resolveAction($isLike, $isComment);
    }

    /**
     * 规范化 moments_action，非法值视为无动作
     */
    public static function normalizeMomentsAction(mixed $momentsAction): int
    {
        $action = (int)$momentsAction;
        if (!in_array($action, [self::ACTION_LIKE, self::ACTION_COMMENT, self::ACTION_BOTH], true)) {
            return self::ACTION_NONE;
        }
        return $action;
    }

    /**
     * 规范化 moments_speech
     *
     * @return list<string>
     */
    public static function normalizeMomentsSpeech(mixed $speech): array
    {
        if (is_string($speech)) {
            $decoded = json_decode($speech, true);
            $speech = is_array($decoded) ? $decoded : [$speech];
        }
        if (!is_array($speech)) {
            return [];
        }

        $result = [];
        foreach ($speech as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return array_values($result);
    }

    /**
     * 加载人设朋友圈智能体配置
     */
    public static function loadPersonaMomentsConfig(int $personaId, int $userId = 0): ?AiPersonaAgentConfig
    {
        if ($personaId <= 0) {
            return null;
        }

        $query = AiPersonaAgentConfig::where('persona_id', $personaId)->whereNull('delete_time');
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        $agentConfig = $query->findOrEmpty();
        if ($agentConfig->isEmpty()) {
            return null;
        }

        return $agentConfig;
    }

    /**
     * 从 agent_config 解析 live 开关；moments_enabled=0 视为无动作
     *
     * @return array{is_like:int,is_comment:int}|null null=无 agent_config
     */
    public static function loadLiveFlagsFromAgentConfig(int $personaId, int $userId = 0): ?array
    {
        $agentConfig = self::loadPersonaMomentsConfig($personaId, $userId);
        if ($agentConfig === null) {
            return null;
        }

        if ((int)$agentConfig->moments_enabled !== 1) {
            return [
                'is_like' => 0,
                'is_comment' => 0,
            ];
        }

        return self::actionToFlags(self::normalizeMomentsAction($agentConfig->moments_action));
    }

    /**
     * 组装朋友圈任务快照字段（供任务生成与冒烟复用）
     *
     * @return array{
     *   ok:bool,
     *   skip_reason?:string,
     *   action?:int,
     *   robot_id?:int,
     *   comment_type?:int,
     *   comment?:string,
     *   moments_enabled?:int,
     *   moments_speech?:list<string>
     * }
     */
    public static function buildMomentsTaskSnapshot(AiPersonaAgentConfig $agentConfig, int $userId = 0): array
    {
        if ((int)$agentConfig->moments_enabled !== 1) {
            return [
                'ok' => false,
                'skip_reason' => '朋友圈互动未开启(moments_enabled=0)',
                'moments_enabled' => 0,
            ];
        }

        $action = self::normalizeMomentsAction($agentConfig->moments_action);
        if ($action === self::ACTION_NONE) {
            return [
                'ok' => false,
                'skip_reason' => '朋友圈点赞评论未开启任何动作',
                'moments_enabled' => 1,
                'action' => self::ACTION_NONE,
            ];
        }

        $commentType = (int)$agentConfig->moments_type === 2 ? 2 : 1;
        $flags = self::actionToFlags($action);
        $speech = self::normalizeMomentsSpeech($agentConfig->moments_speech);
        $robotId = 0;
        $comment = '';

        if ($flags['is_comment'] === 1) {
            if ($commentType === 1) {
                $robotId = (int)$agentConfig->moments_agent_id;
                if ($robotId <= 0) {
                    return [
                        'ok' => false,
                        'skip_reason' => '朋友圈评论智能体未配置',
                        'moments_enabled' => 1,
                        'action' => $action,
                        'comment_type' => $commentType,
                    ];
                }
                $uid = $userId > 0 ? $userId : (int)$agentConfig->user_id;
                $usability = \app\common\service\aiPersona\AgentConfigService::resolveAgentUsability($uid, $robotId);
                if (!(bool)($usability['usable'] ?? false)) {
                    return [
                        'ok' => false,
                        'skip_reason' => '朋友圈评论智能体不可用：' . (string)($usability['status_text'] ?? '请重新绑定'),
                        'moments_enabled' => 1,
                        'action' => $action,
                        'comment_type' => $commentType,
                        'robot_id' => $robotId,
                    ];
                }
            } else {
                if (empty($speech)) {
                    return [
                        'ok' => false,
                        'skip_reason' => '朋友圈固定话术为空',
                        'moments_enabled' => 1,
                        'action' => $action,
                        'comment_type' => $commentType,
                    ];
                }
                $comment = json_encode($speech, JSON_UNESCAPED_UNICODE);
            }
        } elseif ($commentType === 1) {
            // 仅点赞时仍可带上 agent_id，执行侧不走评论
            $robotId = (int)$agentConfig->moments_agent_id;
        } else {
            $comment = !empty($speech) ? json_encode($speech, JSON_UNESCAPED_UNICODE) : '';
        }

        return [
            'ok' => true,
            'action' => $action,
            'robot_id' => $robotId,
            'comment_type' => $commentType,
            'comment' => $comment,
            'moments_enabled' => 1,
            'moments_speech' => $speech,
        ];
    }

    /**
     * @return array{hasLiked:int,hasComment:int,is_like:int,is_comment:int}
     */
    public static function toDeviceFlags(mixed $action): array
    {
        $action = (int)$action;
        $hasLiked = in_array($action, [self::ACTION_LIKE, self::ACTION_BOTH], true) ? 1 : 0;
        $hasComment = in_array($action, [self::ACTION_COMMENT, self::ACTION_BOTH], true) ? 1 : 0;

        return [
            'hasLiked' => $hasLiked,
            'hasComment' => $hasComment,
            'is_like' => $hasLiked,
            'is_comment' => $hasComment,
        ];
    }

    /**
     * @return array{hasLiked:int,hasComment:int,is_like:int,is_comment:int,action:int}
     */
    public static function flagsToDevice(mixed $isLike, mixed $isComment): array
    {
        $flags = self::normalizeFlags($isLike, $isComment);
        $action = self::resolveAction($flags['is_like'], $flags['is_comment']);
        $device = self::toDeviceFlags($action);

        return array_merge($device, ['action' => $action]);
    }

    /**
     * 优先用 live 配置开关，否则回退任务快照 action
     *
     * @return array{hasLiked:int,hasComment:int,is_like:int,is_comment:int,action:int}
     */
    public static function resolveDeviceFlagsFromOption(SvDeviceCircleLikeReply $option): array
    {
        $live = self::loadLiveFlags(
            (int)($option->auto_reply_config_id ?? 0),
            (int)($option->persona_id ?? 0)
        );
        if ($live !== null) {
            return self::flagsToDevice($live['is_like'], $live['is_comment']);
        }

        $action = (int)($option->action ?? self::ACTION_NONE);
        $device = self::toDeviceFlags($action);

        return array_merge($device, ['action' => $action]);
    }

    /**
     * 读取 live 开关。
     * personaId>0：优先 agent_config.moments_*，找不到再回退旧 interaction 表；
     * 无 persona_id：设备自动化配置表。
     *
     * @return array{is_like:int,is_comment:int}|null
     */
    public static function loadLiveFlags(int $autoReplyConfigId, int $personaId = 0): ?array
    {
        if ($personaId > 0) {
            $fromAgent = self::loadLiveFlagsFromAgentConfig($personaId);
            if ($fromAgent !== null) {
                return $fromAgent;
            }

            // 兼容历史：agent_config 尚未迁移时回退 interaction
            if ($autoReplyConfigId > 0) {
                $personaConfig = AiPersonaWechatInteractionConfig::where('id', $autoReplyConfigId)->findOrEmpty();
                if (!$personaConfig->isEmpty()) {
                    return self::normalizeFlags($personaConfig->is_like, $personaConfig->is_comment);
                }
            }
            return null;
        }

        if ($autoReplyConfigId <= 0) {
            return null;
        }

        // 无 persona_id：先设备自动化，再人设（兼容历史脏数据）
        $autoConfig = AutoDeviceCircleLikeReplyConfig::where('id', $autoReplyConfigId)->findOrEmpty();
        if (!$autoConfig->isEmpty()) {
            return self::normalizeFlags($autoConfig->is_like, $autoConfig->is_comment);
        }

        $personaConfig = AiPersonaWechatInteractionConfig::where('id', $autoReplyConfigId)->findOrEmpty();
        if (!$personaConfig->isEmpty()) {
            return self::normalizeFlags($personaConfig->is_like, $personaConfig->is_comment);
        }

        return null;
    }

    /**
     * 配置变更后，同步仍待执行子任务对应的主任务 action
     */
    public static function syncPendingTaskAction(int $autoReplyConfigId, int $action): int
    {
        if ($autoReplyConfigId <= 0) {
            return 0;
        }

        $taskIds = SvDeviceCircleLikeReply::where('auto_reply_config_id', $autoReplyConfigId)
            ->whereNull('delete_time')
            ->column('id');
        if (empty($taskIds)) {
            return 0;
        }

        return self::syncPendingTaskActionByIds($taskIds, $action);
    }

    /**
     * 按人设同步待执行朋友圈任务 action
     */
    public static function syncPendingTaskActionByPersona(int $personaId, int $action): int
    {
        if ($personaId <= 0) {
            return 0;
        }

        $taskIds = SvDeviceCircleLikeReply::where('persona_id', $personaId)
            ->whereNull('delete_time')
            ->column('id');
        if (empty($taskIds)) {
            return 0;
        }

        return self::syncPendingTaskActionByIds($taskIds, $action);
    }

    /**
     * @param list<int|string> $taskIds
     */
    private static function syncPendingTaskActionByIds(array $taskIds, int $action): int
    {
        $pendingTaskIds = SvDeviceCircleLikeReplyAccount::whereIn('circle_like_reply_id', $taskIds)
            ->where('status', 0)
            ->whereNull('delete_time')
            ->column('circle_like_reply_id');
        $pendingTaskIds = array_values(array_unique(array_map('intval', $pendingTaskIds)));
        if (empty($pendingTaskIds)) {
            return 0;
        }

        return (int)SvDeviceCircleLikeReply::whereIn('id', $pendingTaskIds)
            ->update([
                'action' => (int)$action,
                'update_time' => time(),
            ]);
    }
}
