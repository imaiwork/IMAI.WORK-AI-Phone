<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\kb\KbRobot;
use Exception;
use think\facade\Db;

class AgentConfigLogic extends ApiLogic
{
    /**
     * 编辑AI人设智能体设置
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function update(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $configId  = intval($params['id']);
            $personaId = intval($params['persona_id']);

            $agentConfigData = [
                'comment_enabled'      => $params['comment_enabled'] ?? 1,
                'comment_agent_id'     => $params['comment_agent_id'] ?? 0,
                'dm_enabled'           => $params['dm_enabled'] ?? 1,
                'dm_agent_id'          => $params['dm_agent_id'] ?? 0,
                'wechat_chat_enabled'  => $params['wechat_chat_enabled'] ?? 1,
                'wechat_chat_agent_id' => $params['wechat_chat_agent_id'] ?? 0,
                'moments_enabled'      => $params['moments_enabled'] ?? 1,
                'moments_agent_id'     => $params['moments_agent_id'] ?? 0,
                'update_time'          => time()
            ];

            AiPersonaAgentConfig::update($agentConfigData, [
                'id'         => $configId,
                'persona_id' => $personaId,
                'user_id'    => $userId
            ]);

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
     * 获取AI人设智能体设置详情
     * @param int $personaId
     * @param int $userId
     * @return bool
     */
    public static function detail(int $personaId, int $userId): bool
    {
        try {
            $agentConfig = AiPersonaAgentConfig::where([
                                                           'persona_id'  => $personaId,
                                                           'user_id'     => $userId,
                                                           'delete_time' => null
                                                       ])->findOrEmpty();

            if ($agentConfig->isEmpty()) {
                throw new Exception('人设设置不存在');
            }

            self::$returnData = [
                'id'                     => $agentConfig->id,
                'persona_id'             => $agentConfig->persona_id,
                'user_id'                => $agentConfig->user_id,
                'comment_enabled'        => $agentConfig->comment_enabled,
                'comment_agent_id'       => $agentConfig->comment_agent_id,
                'comment_agent_name'     => $agentConfig->comment_agent_id > 0 ? KbRobot::where('id', $agentConfig->comment_agent_id)->value('name') : '',
                'dm_enabled'             => $agentConfig->dm_enabled,
                'dm_agent_id'            => $agentConfig->dm_agent_id,
                'dm_agent_name'          => $agentConfig->dm_agent_id > 0 ? KbRobot::where('id', $agentConfig->dm_agent_id)->value('name') : '',
                'wechat_chat_enabled'    => $agentConfig->wechat_chat_enabled,
                'wechat_chat_agent_id'   => $agentConfig->wechat_chat_agent_id,
                'wechat_chat_agent_name' => $agentConfig->wechat_chat_agent_id > 0 ? KbRobot::where('id', $agentConfig->wechat_chat_agent_id)->value('name') : '',
                'moments_enabled'        => $agentConfig->moments_enabled,
                'moments_agent_id'       => $agentConfig->moments_agent_id,
                'moments_agent_name'     => $agentConfig->moments_agent_id > 0 ? KbRobot::where('id', $agentConfig->moments_agent_id)->value('name') : '',
            ];
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}