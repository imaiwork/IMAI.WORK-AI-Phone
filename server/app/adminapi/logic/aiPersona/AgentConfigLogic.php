<?php

namespace app\adminapi\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\kb\KbRobot;
use app\common\service\aiPersona\AgentConfigService;
use app\common\service\sv\CircleInteractionActionService;
use Exception;
use think\facade\Db;

class AgentConfigLogic extends BaseLogic
{
    /**
     * 编辑AI人设智能体设置
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $configId  = intval($params['id']);
            $personaId = intval($params['persona_id']);

            $agentConfigData = [
                'comment_enabled'      => $params['comment_enabled'] ?? 1,
                'comment_agent_id'     => $params['comment_agent_id'] ?? 0,
                'comment_type'         => $params['comment_type'] ?? 0,
                'comment_speech'       => $params['comment_speech'] ?? [],
                'dm_enabled'           => $params['dm_enabled'] ?? 1,
                'dm_agent_id'          => $params['dm_agent_id'] ?? 0,
                'dm_type'              => $params['dm_type'] ?? 0,
                'dm_speech'            => $params['dm_speech'] ?? [],
                'wechat_chat_enabled'  => $params['wechat_chat_enabled'] ?? 1,
                'wechat_chat_agent_id' => $params['wechat_chat_agent_id'] ?? 0,
                'wechat_chat_type'     => $params['wechat_chat_type'] ?? 0,
                'wechat_chat_speech'   => $params['wechat_chat_speech'] ?? [],
                'moments_enabled'      => $params['moments_enabled'] ?? 1,
                'moments_agent_id'     => $params['moments_agent_id'] ?? 0,
                'moments_action'       => $params['moments_action'] ?? 0,
                'moments_type'         => $params['moments_type'] ?? 0,
                'moments_speech'       => $params['moments_speech'] ?? [],
                'shutoff_comment_type' => $params['shutoff_comment_type'] ?? 0,
                'shutoff_comment_agent_id' => $params['shutoff_comment_agent_id'] ?? 0,
                'shutoff_comment_speech' => $params['shutoff_comment_speech'] ?? [],
                'shutoff_msg_type'        => $params['shutoff_msg_type'] ?? 0,
                'shutoff_msg_agent_id'    => $params['shutoff_msg_agent_id'] ?? 0,
                'shutoff_msg_speech'      => $params['shutoff_msg_speech'] ?? [],
                'update_time'          => time()
            ];

            AiPersonaAgentConfig::update($agentConfigData, [
                'id'         => $configId,
                'persona_id' => $personaId,
            ]);

            $momentsAction = CircleInteractionActionService::normalizeMomentsAction($agentConfigData['moments_action'] ?? 0);
            if ((int)($agentConfigData['moments_enabled'] ?? 0) !== 1) {
                $momentsAction = CircleInteractionActionService::ACTION_NONE;
            }
            CircleInteractionActionService::syncPendingTaskActionByPersona($personaId, $momentsAction);

            Db::commit();
            self::$returnData = ['id' => $configId];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 编辑智能客服完整配置
     * @param array $params
     * @return bool
     */
    public static function updateCustomerService(array $params): bool
    {
        Db::startTrans();
        try {
            $configId = intval($params['id'] ?? 0);
            $personaId = intval($params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('人设ID不能为空');
            }

            $persona = AiPersona::where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('IP人设不存在');
            }
            $userId = (int)$persona->user_id;

            $agentConfig = self::getCustomerServiceConfig($personaId, $configId);
            if ($agentConfig->isEmpty()) {
                $agentConfig = AiPersonaAgentConfig::create(
                    AiPersonaAgentConfig::getDefaultConfigData($userId, $personaId)
                );
            }

            $agentConfigData = AgentConfigService::buildCustomerServiceData($agentConfig, $params);
            $agentConfig->save($agentConfigData);

            $momentsAction = CircleInteractionActionService::normalizeMomentsAction($agentConfigData['moments_action'] ?? $agentConfig->moments_action);
            if ((int)($agentConfigData['moments_enabled'] ?? $agentConfig->moments_enabled) !== 1) {
                $momentsAction = CircleInteractionActionService::ACTION_NONE;
            }
            CircleInteractionActionService::syncPendingTaskActionByPersona($personaId, $momentsAction);

            Db::commit();
            self::$returnData = ['id' => $agentConfig->id];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取AI人设智能体设置详情
     * @param int $personaId
     * @return bool
     */
    public static function detail(int $personaId): bool
    {
        try {
            $agentConfig = AiPersonaAgentConfig::where([
                'persona_id'  => $personaId,
                'delete_time' => null
            ])->findOrEmpty();

            if ($agentConfig->isEmpty()) {
                throw new Exception('人设设置不存在');
            }
            $userId = $agentConfig->user_id;

            $persona = AiPersona::where('user_id', $userId)->where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $personaRule = ApiLogic::getPersonaRule($persona);
            $agentConfig->shutoff_comment_speech = $personaRule->is_clue_updated == 1 || empty($agentConfig->shutoff_comment_speech) ? $personaRule->clue_comment_scripts : $agentConfig->shutoff_comment_speech;
            $agentConfig->shutoff_msg_speech = $personaRule->is_clue_updated == 1 || empty($agentConfig->shutoff_msg_speech) ? $personaRule->clue_dm_scripts : $agentConfig->shutoff_msg_speech;
            $agentConfig->save();

            self::$returnData = AgentConfigService::formatDetailData($agentConfig);

            // self::$returnData = [
            //     'id'                     => $agentConfig->id,
            //     'persona_id'             => $agentConfig->persona_id,
            //     'user_id'                => $agentConfig->user_id,
            //     'comment_enabled'        => $agentConfig->comment_enabled,
            //     'comment_agent_id'       => $agentConfig->comment_agent_id,
            //     'comment_agent_name'     => $agentConfig->comment_agent_id > 0 ? KbRobot::where('id', $agentConfig->comment_agent_id)->value('name') : '',
            //     'dm_enabled'             => $agentConfig->dm_enabled,
            //     'dm_agent_id'            => $agentConfig->dm_agent_id,
            //     'dm_agent_name'          => $agentConfig->dm_agent_id > 0 ? KbRobot::where('id', $agentConfig->dm_agent_id)->value('name') : '',
            //     'wechat_chat_enabled'    => $agentConfig->wechat_chat_enabled,
            //     'wechat_chat_agent_id'   => $agentConfig->wechat_chat_agent_id,
            //     'wechat_chat_agent_name' => $agentConfig->wechat_chat_agent_id > 0 ? KbRobot::where('id', $agentConfig->wechat_chat_agent_id)->value('name') : '',
            //     'moments_enabled'        => $agentConfig->moments_enabled,
            //     'moments_agent_id'       => $agentConfig->moments_agent_id,
            //     'moments_agent_name'     => $agentConfig->moments_agent_id > 0 ? KbRobot::where('id', $agentConfig->moments_agent_id)->value('name') : '',
            // ];
            // 检查AI人设配置状态
            AiPersonaLogic::checkAiPersonaConfigStatus($personaId, $userId);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function getCustomerServiceConfig(int $personaId, int $configId): AiPersonaAgentConfig
    {
        if ($configId > 0) {
            $agentConfig = AiPersonaAgentConfig::where([
                'id' => $configId,
                'persona_id' => $personaId,
                'delete_time' => null,
            ])->findOrEmpty();
            if (!$agentConfig->isEmpty()) {
                return $agentConfig;
            }
        }

        return AiPersonaAgentConfig::where([
            'persona_id' => $personaId,
            'delete_time' => null,
        ])->findOrEmpty();
    }

}
