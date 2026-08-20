<?php


namespace app\adminapi\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;

/**
 * IP人设触达自动任务管理逻辑
 * Class ClueTouchLogic
 * @package app\adminapi\logic\aiPersona
 */
class ClueTouchLogic extends BaseLogic
{
    public static function detail($params): array
    {
        ini_set('max_execution_time', 0);
        try {
            $persona = AiPersona::where('id', $params['id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }
            // if ((int)$persona->report_status !== 2) {
            //     throw new \Exception('IP人设分析报告未分析完成');
            // }
            // if (empty($persona->report_content)) {
            //     throw new \Exception('IP人设分析报告内容为空');
            // }

            $personaRule = ApiLogic::getPersonaRule($persona);
            $config = AiPersonaTrafficConfig::where('persona_id', $params['id'])->findOrEmpty();
            if (!$config->isEmpty()) {
                $config->clue_keywords = $personaRule->is_clue_updated === 1 || empty($config->clue_keywords) ? $personaRule->clue_keywords : $config->clue_keywords;
                $config->acquire_keywords = $personaRule->is_clue_updated === 1 || empty($config->acquire_keywords) ? $personaRule->clue_acquire_keywords : $config->acquire_keywords;
                $config->intercept_keywords = $personaRule->is_clue_updated === 1 || empty($config->intercept_keywords) ? $personaRule->clue_intercept_keywords : $config->intercept_keywords;
                $config->comment_scripts = $personaRule->is_clue_updated === 1 || empty($config->comment_scripts) ? $personaRule->clue_comment_scripts : $config->comment_scripts;
                $config->dm_scripts = $personaRule->is_clue_updated === 1 || empty($config->dm_scripts) ? $personaRule->clue_dm_scripts : $config->dm_scripts;
                $config->save();
                $personaRule->is_clue_updated = 0;
                $personaRule->save();
                return $config->toArray();
            } else {

                $insertData = [
                    'user_id' => $persona->user_id,
                    'persona_id' => $params['id'],
                    'clue_keywords' => $personaRule->clue_keywords ?? [],
                    'acquire_keywords' => $personaRule->clue_acquire_keywords ?? [],
                    'intercept_keywords' => $personaRule->clue_intercept_keywords ?? [],
                    'comment_scripts' => $personaRule->clue_comment_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'dm_scripts' => $personaRule->clue_dm_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'message_number' => $params['message_number'] ?? 15,
                    'comment_number' => $params['comment_number'] ?? 15,
                    'reply_number' => $params['reply_number'] ?? 0,
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($params['content_publish_day'] ?? 0),
                    'comment_publish_day' => $params['comment_publish_day'] ?? 1,
                    'intercept_max_number' => $params['intercept_max_number'] ?? 10,
                    'intercept_keyword_used_type' => $params['intercept_keyword_used_type'] ?? 2,
                    'clue_max_number' => $params['clue_max_number'] ?? 10,
                    'clue_keyword_used_type' => $params['clue_keyword_used_type'] ?? 2,
                    'group_buy_config' => [],
                    'same_city_config' => [],
                    'video_cutoff_number' => $params['video_cutoff_number'] ?? 30,
                    'city_cutoff_number' => $params['city_cutoff_number'] ?? 30,
                    'group_cutoff_number' => $params['group_cutoff_number'] ?? 30,
                    'exec_date' => date('Y-m-d', time()),
                    'is_first' => 1,
                    'status' => 0,
                ];
                $result = AiPersonaTrafficConfig::create($insertData);
                $personaRule->is_clue_updated = 0;
                $personaRule->save();
                return $result->toArray();
            }
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return [];
        }
    }

    public static function edit(array $params): bool
    {
        try {
            $info = AiPersonaTrafficConfig::where('persona_id', $params['persona_id'])->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("配置数据不存在");
            }
            $params['content_publish_day'] = AiPersonaTrafficConfig::normalizeContentPublishDay($params['content_publish_day'] ?? 0);
            $info->save($params);
            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
