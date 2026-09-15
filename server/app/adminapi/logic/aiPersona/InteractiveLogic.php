<?php


namespace app\adminapi\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\sv\CircleInteractionActionService;

use app\common\model\sv\SvAddWechatRecord;

/**
 * IP人设互动互动管家任务管理逻辑
 * Class InteractiveLogic
 * @package app\adminapi\logic\aiPersona
 */
class InteractiveLogic extends BaseLogic
{
    public static function detail(array $params): array
    {
        ini_set('max_execution_time', 0);
        try {
            $config = AiPersona::where('id', $params['id'])->findOrEmpty();
            if ($config->isEmpty()) {
                throw new \Exception('设备自动化配置不存在');
            }

            $personaRule = ApiLogic::getPersonaRule($config);
            $isAutoGroup = self::getAutoGroupStatus($config);
            $agentConfig = self::ensureAgentConfig((int)$params['id'], (int)$config->user_id);

            if ($personaRule->is_wechat_updated === 1) {
                $agentConfig->moments_speech = $personaRule->wechat_comment_speech ?? [];
                $agentConfig->update_time = time();
                $agentConfig->save();
            }

            $find = AiPersonaWechatInteractionConfig::where('persona_id', $params['id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->add_friend_script = $personaRule->is_wechat_updated === 1
                    ? implode("\n", is_array($personaRule->wechat_add_friend_script) ? $personaRule->wechat_add_friend_script : [])
                    : $find->add_friend_script;
                $find->is_auto_group = $isAutoGroup;
                if (is_null($find->getData('group_trigger_keywords'))) {
                    $find->group_trigger_keywords = AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords();
                }
                $find->save();
                $find->clue_count = self::getClues((int)$config->user_id);
                $personaRule->is_wechat_updated = 0;
                $personaRule->save();
                return self::mergeMomentsIntoInteractionResponse($find->toArray(), $agentConfig);
            }

            $insertData = [
                'user_id' => $config->user_id,
                'persona_id' => $params['id'],
                'add_friend_enabled' => $params['add_friend_enabled'] ?? 1,
                'add_friend_source' => $params['add_friend_source'] ?? 1,
                'add_friend_script' => implode("\n", is_array($personaRule->wechat_add_friend_script) ? $personaRule->wechat_add_friend_script : []),
                'number' => $params['number'] ?? 15,
                'is_auto_group' => $isAutoGroup,
                'status' => 0,
                'exec_time' =>  [],
                'exec_date' => $params['exec_date'] ?? date('Y-m-d', time()),
                'group_trigger_mode' => AiPersonaWechatInteractionConfig::GROUP_TRIGGER_MODE_AI,
                'group_trigger_keywords' => AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords(),
            ];
            $interaction = AiPersonaWechatInteractionConfig::create($insertData);
            $interaction->clue_count = self::getClues((int)$config->user_id);
            $personaRule->is_wechat_updated = 0;
            $personaRule->save();
            return self::mergeMomentsIntoInteractionResponse($interaction->toArray(), $agentConfig);
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return [];
        }
    }

    public static function edit(array $params): bool
    {
        try {
            $info = AiPersonaWechatInteractionConfig::where('persona_id', $params['persona_id'])->findOrEmpty();
            if ($info->isEmpty()) {
                throw new \Exception("配置数据不存在");
            }
            if (isset($params['is_auto_group'])) {
                unset($params['is_auto_group']);
            }

            $flags = CircleInteractionActionService::normalizeFlags($params['is_like'] ?? 0, $params['is_comment'] ?? 0);
            if ($flags['is_like'] === 0 && $flags['is_comment'] === 0) {
                throw new \Exception('请至少开启点赞或评论其中一项');
            }

            $persona = AiPersona::where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }

            $agentConfig = self::ensureAgentConfig((int)$params['persona_id'], (int)$persona->user_id);
            $momentsAction = array_key_exists('moments_action', $params)
                ? CircleInteractionActionService::normalizeMomentsAction($params['moments_action'])
                : CircleInteractionActionService::flagsToMomentsAction($flags['is_like'], $flags['is_comment']);
            if ($momentsAction === CircleInteractionActionService::ACTION_NONE) {
                throw new \Exception('请至少开启点赞或评论其中一项');
            }

            $momentsType = (int)($params['moments_type'] ?? $params['comment_method'] ?? $agentConfig->moments_type ?: 1);
            $momentsType = $momentsType === 2 ? 2 : 1;
            $momentsSpeech = array_key_exists('moments_speech', $params)
                ? $params['moments_speech']
                : ($params['comment_speech'] ?? $agentConfig->moments_speech);
            $momentsSpeech = CircleInteractionActionService::normalizeMomentsSpeech($momentsSpeech);

            $agentConfig->moments_enabled = (int)($params['moments_enabled'] ?? 1);
            $agentConfig->moments_action = $momentsAction;
            $agentConfig->moments_type = $momentsType;
            if (array_key_exists('moments_agent_id', $params)) {
                $agentConfig->moments_agent_id = (int)$params['moments_agent_id'];
            }
            $agentConfig->moments_speech = $momentsSpeech;
            $agentConfig->update_time = time();
            $agentConfig->save();

            // interaction 表只保留私域调度字段
            unset(
                $params['is_like'],
                $params['is_comment'],
                $params['comment_method'],
                $params['comment_speech'],
                $params['comment_robot_prompt'],
                $params['moments_enabled'],
                $params['moments_action'],
                $params['moments_type'],
                $params['moments_agent_id'],
                $params['moments_speech']
            );
            $params['is_auto_group'] = self::getAutoGroupStatusByPersonaId((int)$params['persona_id']);
            $info->save($params);

            CircleInteractionActionService::syncPendingTaskActionByPersona(
                (int)$params['persona_id'],
                $momentsAction
            );
            self::$returnData = self::mergeMomentsIntoInteractionResponse($info->toArray(), $agentConfig);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    private static function ensureAgentConfig(int $personaId, int $userId): AiPersonaAgentConfig
    {
        $agentConfig = CircleInteractionActionService::loadPersonaMomentsConfig($personaId, $userId);
        if ($agentConfig !== null) {
            return $agentConfig;
        }

        return AiPersonaAgentConfig::create(AiPersonaAgentConfig::getDefaultConfigData($userId, $personaId));
    }

    /**
     * @param array<string,mixed> $interactionData
     * @return array<string,mixed>
     */
    private static function mergeMomentsIntoInteractionResponse(array $interactionData, AiPersonaAgentConfig $agentConfig): array
    {
        $flags = CircleInteractionActionService::actionToFlags(
            CircleInteractionActionService::normalizeMomentsAction($agentConfig->moments_action)
        );
        $interactionData['is_like'] = $flags['is_like'];
        $interactionData['is_comment'] = $flags['is_comment'];
        $interactionData['comment_method'] = (int)$agentConfig->moments_type === 2 ? 2 : 1;
        $interactionData['comment_speech'] = CircleInteractionActionService::normalizeMomentsSpeech($agentConfig->moments_speech);
        $interactionData['moments_enabled'] = (int)$agentConfig->moments_enabled;
        $interactionData['moments_action'] = CircleInteractionActionService::normalizeMomentsAction($agentConfig->moments_action);
        $interactionData['moments_type'] = (int)$agentConfig->moments_type === 2 ? 2 : 1;
        $interactionData['moments_agent_id'] = (int)$agentConfig->moments_agent_id;
        $interactionData['moments_speech'] = $interactionData['comment_speech'];
        return $interactionData;
    }


    private static function getClues(int $user_id): int
    {
        $count = SvAddWechatRecord::where('user_id', $user_id)->where('status', 4)->group('reg_wechat')->count();
        return $count;
    }

    private static function getAutoGroupStatus(AiPersona $persona): int
    {
        $options = AiPersonaOptionService::getOptionsByPersona($persona);
        return AiPersonaOptionService::isEnabled($options, 'private_operation.options.auto_add_group') ? 1 : 0;
    }

    private static function getAutoGroupStatusByPersonaId(int $personaId): int
    {
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return 1;
        }

        return self::getAutoGroupStatus($persona);
    }
}
