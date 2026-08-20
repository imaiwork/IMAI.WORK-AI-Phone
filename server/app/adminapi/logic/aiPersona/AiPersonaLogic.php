<?php

namespace app\adminapi\logic\aiPersona;

use app\adminapi\logic\kb\KbRobotLogic;
use app\api\logic\aiPersona\CopywritingLibraryLogic;
use app\api\logic\ApiLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\AiPersonaReport;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\model\aiPersona\Material;
use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\aiPersona\AiPersonaTextService;
use app\common\service\aiPersona\PersonaWorkflowService;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\MemberService;
use Exception;
use GuzzleHttp\Client;
use think\facade\Db;
use think\facade\Log;

class AiPersonaLogic extends BaseLogic
{
    public static function clue(array $params)
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['id']])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $personaRule = ApiLogic::getPersonaRule($persona);
            $payload     = array(
                'keywords' => $personaRule->clue_content,
            );

            $response = \app\common\service\ToolsService::Coze()->clue($payload);
            // continue;
            if ((int)$response['code'] !== 10000 || !isset($response['data']['content'])) {
                self::setError($response['msg'] ?? '获取线索词失败');
                return false;
            }

            $result = $response['data']['content'];
            $output = json_decode($result['output'], true);

            $personaRule->clue_keywords = $output['target_industry'] ?? [];
            $personaRule->clue_acquire_keywords   = $output['video_search_keywords'] ?? [];
            $personaRule->clue_intercept_keywords = $output['comment_clue_keywords'] ?? [];
            $personaRule->clue_comment_scripts    = $output['comment_drainage_scripts'] ?? [];
            $personaRule->clue_dm_scripts         = $output['dm_interception_scripts'] ?? [];
            $personaRule->update_time             = time();
            $personaRule->is_clue_updated = 1;
            $personaRule->save();
            \app\common\model\aiPersona\AiPersonaTrafficConfig::where('user_id', $persona->user_id)->where('persona_id', $params['id'])->select()->delete();
            sleep(1);
            ClueTouchLogic::detail([
                'id' => $params['id']
            ]);
            $agentConfig = AiPersonaAgentConfig::where('persona_id', $params['id'])->findOrEmpty();
            if (!$agentConfig->isEmpty()) {
                $agentConfig->shutoff_comment_speech =  $personaRule->clue_comment_scripts;
                $agentConfig->shutoff_msg_speech = $personaRule->clue_dm_scripts;
                $agentConfig->save();
            }
            self::$returnData = $personaRule->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function wechat(array $params)
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['id']])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $personaRule = ApiLogic::getPersonaRule($persona);
            $payload     = array(
                'keywords' => $personaRule->clue_content,
            );
            $response    = \app\common\service\ToolsService::Coze()->wechatTouch($payload);
            // continue;
            if ((int)$response['code'] !== 10000 || !isset($response['data']['content'])) {
                self::setError($response['msg'] ?? '获取微信话术失败');
                return false;
            }

            $result = $response['data']['content'];
            $output = json_decode($result['output'], true);

            $personaRule->wechat_add_friend_script = $output['friend_request_scripts'];
            $personaRule->wechat_comment_speech    = $output['moments_comment_scripts'];
            $personaRule->update_time              = time();
            $personaRule->is_wechat_updated = 1;
            $personaRule->save();
            \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('user_id', $persona->user_id)->where('persona_id', $params['id'])->select()->delete();
            sleep(1);
            InteractiveLogic::detail([
                'id' => $params['id']
            ]);
            self::$returnData = $personaRule->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function hotWords(array $params)
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id'] ?? $params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('AI人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $tokenScene = 'get_hot_words';
            $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS;
            $unit = \app\api\logic\service\TokenLogService::checkToken((int)$persona->user_id, $tokenScene);

            $personaRule = ApiLogic::getPersonaRule($persona);
            if ($personaRule === false || $personaRule->isEmpty()) {
                throw new Exception(self::getError() ?: '人设配置不存在');
            }

            $response = \app\common\service\ToolsService::Coze()->getHotWords([
                'keywords' => $personaRule->clue_content,
            ]);
            if ((int)($response['code'] ?? 0) !== 10000 || !isset($response['data']['content'])) {
                throw new Exception($response['msg'] ?? '获取爆款关键词失败');
            }

            $keywords = self::normalizeHotWords($response['data']['content'] ?? []);
            $points = $unit;
            if ($points > 0) {
                $extra = [
                    '生成关键词数' => count($keywords),
                    '算力单价' => $unit,
                    '实际消耗算力' => $points,
                    '描述' => '根据输入内容提取短视频热点搜索关键词-admin重新生成',
                ];
                $taskId = generate_unique_task_id();
                \app\common\model\user\User::userTokensChange((int)$persona->user_id, $points);
                \app\common\logic\AccountLogLogic::recordUserTokensLog(true, (int)$persona->user_id, $tokenCode, $points, $taskId, $extra);
            }

            $personaRule->hot_words = $keywords;
            $personaRule->update_time = time();
            $personaRule->save();

            self::$returnData = $personaRule->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::channel('device')->write($e->__toString());
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateHotWords(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id'] ?? $params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('AI人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            $hotWords = self::getHotWordsParam($params, (int)$persona->persona_type);
            $trackingData = AiPersona::buildTrackingConfigData($params);
            if ($hotWords === null && empty($trackingData)) {
                throw new Exception('爆款关键词或追踪配置参数不能为空');
            }

            $personaRule = self::getPersonaRuleModel($persona);
            if ($personaRule->isEmpty()) {
                throw new Exception('人设配置不存在');
            }

            if ($hotWords !== null) {
                $personaRule->hot_words = self::normalizeHotWords($hotWords);
                $personaRule->update_time = time();
                $personaRule->save();
            }

            if (!empty($trackingData)) {
                $trackingData['update_time'] = time();
                $persona->save($trackingData);
                $persona = $persona->refresh();
            }

            Db::commit();
            self::$returnData = [
                'persona_id' => $personaId,
                'hot_words' => $personaRule->hot_words,
                'tracking_mode' => (int)$persona->tracking_mode,
                'duration' => (int)$persona->duration,
                'publish_day' => (int)$persona->publish_day,
                'tracking_account_config' => $persona->tracking_account_config,
            ];
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateOption(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id'] ?? $params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('AI人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            $personaRule = self::getPersonaRuleModel($persona);
            if ($personaRule->isEmpty()) {
                throw new Exception('人设配置不存在');
            }

            $global_option = AiPersonaOptionService::normalize($params['global_option'] ?? null);
            $personaRule->global_option = $global_option;
            $personaRule->update_time = time();
            $personaRule->save();

            $config = AiPersonaWechatInteractionConfig::where('persona_id', $personaId)->findOrEmpty();
            if ($config->isEmpty()) {
                throw new Exception('互动管家配置不存在');
            }
            $config->is_auto_group = AiPersonaOptionService::isEnabled($global_option, 'private_operation.options.auto_add_group') ? 1 : 0;
            $config->update_time = time();
            $config->save();

            Db::commit();
            self::$returnData = [
                'persona_id' => $personaId,
                'global_option' => $personaRule->global_option,
            ];
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function getHotWordsParam(array $params, int $personaType)
    {
        if (array_key_exists('hot_words', $params)) {
            return $params['hot_words'];
        }

        $ruleKey = match ($personaType) {
            1 => 'individual',
            2 => 'enterprise',
            3 => 'local',
            default => '',
        };

        if ($ruleKey !== '' && isset($params[$ruleKey]) && is_array($params[$ruleKey]) && array_key_exists('hot_words', $params[$ruleKey])) {
            return $params[$ruleKey]['hot_words'];
        }

        return null;
    }

    private static function getPersonaRuleModel(AiPersona $persona)
    {
        $where = [
            'persona_id' => (int)$persona->id,
            'user_id' => (int)$persona->user_id,
            'delete_time' => null,
        ];

        return match ((int)$persona->persona_type) {
            1 => AiPersonaIndividual::where($where)->findOrEmpty(),
            2 => AiPersonaEnterprise::where($where)->findOrEmpty(),
            3 => AiPersonaLocal::where($where)->findOrEmpty(),
            default => throw new Exception('IP人设类型错误'),
        };
    }

    private static function normalizeHotWords($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }

            return array_values(array_filter(array_map('trim', explode(',', $value)), fn($word) => $word !== ''));
        }

        throw new Exception('爆款关键词格式错误');
    }

    public static function checkAiPersonaConfigStatus(int $personaId, int $userId)
    {
        $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
        if ($persona->isEmpty()) {
            return ['res' => false, 'msg' => '人设不存在或无操作权限'];
        }
        //        if ($persona['is_configured'] == 1) {
        //            return ['res' => true, 'msg' => '已配置完成', 'is_configured' => 1];
        //        }
        if ($persona['report_status'] != 2 || empty($persona['report_content'])) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请生成人设报告', 'is_configured' => 0];
        }
        $personaAgentConfig = AiPersonaAgentConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
        if ($personaAgentConfig->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置智能体', 'is_configured' => 0];
        } else if ((!$personaAgentConfig->hasAnyEffectivePlatformAgentConfig() && ($personaAgentConfig['comment_agent_id'] == 0 || $personaAgentConfig['dm_agent_id'] == 0)) || $personaAgentConfig['wechat_chat_agent_id'] == 0 || $personaAgentConfig['moments_agent_id'] == 0) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置智能体', 'is_configured' => 0];
        }
        $material = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $persona['publish_mode']]])->findOrEmpty();
        if ($material->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先上传素材', 'is_configured' => 0];
        }
        $trafficConfig = AiPersonaTrafficConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
        if ($trafficConfig->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置获客截流设置', 'is_configured' => 0];
        }
        $wechatInteractionConfig = AiPersonaWechatInteractionConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
        if ($wechatInteractionConfig->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置微信私域互动管家设置', 'is_configured' => 0];
        }
        $digitalVoice = AiPersonaDigitalVoice::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
        if ($digitalVoice->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置人设音色', 'is_configured' => 0];
        }
        $persona->is_configured = 1;
        $persona->save();
        return ['res' => true, 'msg' => '已配置完成', 'is_configured' => 1];
    }

    /**
     * 编辑AI人设（主表+对应子表）
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id']);
            // 1. 验证主表数据归属
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }
            $userId                 = $persona['user_id'];
            // 2. 更新主表
            $personaData = [
                'persona_name' => $params['persona_name'],
                'avatar_url'   => !empty($params['avatar_url']) ? FileService::getFileUrl($params['avatar_url']) : $persona['avatar_url'],
                'quick_desc'   => $params['quick_desc'] ?? $persona['quick_desc'],
                'persona_desc' => $params['persona_desc'] ?? $persona['persona_desc'],
                'industry'     => $params['industry'] ?? $persona['industry'],
                'status'       => $params['status'] ?? $persona['status'],
                'main_business'      => $params['main_business'] ?? $persona['main_business'],
                'target_pain_points' => $params['target_pain_points'] ?? $persona['target_pain_points'],
                'conversion_hook'    => $params['conversion_hook'] ?? $persona['conversion_hook'],
                'is_shopping_cart'   => $params['is_shopping_cart'] ?? $persona['is_shopping_cart'],
                'goods_name'         => $params['goods_name'] ?? $persona['goods_name'],    
                'is_store_position'  => $params['is_store_position'] ?? $persona['is_store_position'],
                'store_position'     => $params['store_position'] ?? $persona['store_position'],
                'update_time'  => time()
            ];
            $personaData = array_merge($personaData, AiPersona::buildTrackingConfigData($params));
            $personaData = array_merge($personaData, AiPersonaTextService::buildPersonaMainData($params, $persona));
            // 切换发布模式
            if (isset($params['publish_mode']) && (int)$params['publish_mode'] != $persona['publish_mode']) {
                $personaData['publish_mode'] = (int)$params['publish_mode'];
                $material                    = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $params['publish_mode']]])
                    ->findOrEmpty();
                if ($material->isEmpty()) {
                    $personaData['is_configured'] = 0;
                }
                \app\api\logic\aiPersona\AiPersonaLogic::syncSynthesisWorkModeByPublishMode($personaId, (int)$params['publish_mode']);
            }

            // 切换人设类型、内容变动时需重新生成报告
            if (isset($params['is_create_report']) && $params['is_create_report'] == 1) {
                $personaData['is_configured']   = 0;
                $personaData['report_status']   = 0;
                $personaData['report_gen_time'] = null;
                $personaData['report_content']  = null;
            }

            if (isset($params['persona_type']) && (int)$params['persona_type'] != (int)$persona['persona_type']) {
                $personaData['persona_type'] = (int)$params['persona_type'];
            } else {
                $personaData['persona_type'] = $persona['persona_type'];
            }
            AiPersona::update($personaData, ['id' => $personaId]);

            // 3. 更新对应子表
            $personaType = $persona['persona_type'];
            if ($personaType != $params['persona_type']) {
                switch ($personaType) {
                    case 1:
                        AiPersonaIndividual::where('persona_id', $personaId)->update(['delete_time' => time()]);
                        break;
                    case 2:
                        AiPersonaEnterprise::where('persona_id', $personaId)->update(['delete_time' => time()]);
                        break;
                    case 3:
                        AiPersonaLocal::where('persona_id', $personaId)->update(['delete_time' => time()]);
                        break;
                }
                switch ($params['persona_type']) {
                    case 1: // 个人IP
                        $individualData = [
                            'persona_id'       => $personaId,
                            'user_id'          => $userId,
                            'nickname'         => $params['individual']['nickname'] ?? '',
                            'identity'         => $params['individual']['identity'] ?? [],
                            'personality_tags' => $params['individual']['personality_tags'] ?? [],
                            'core_value'       => $params['individual']['core_value'] ?? '',
                            'highlight_story'  => $params['individual']['highlight_story'] ?? '',
                            'target_audience'  => $params['individual']['target_audience'] ?? '',
                            'monetize_paths'   => $params['individual']['monetize_paths'] ?? [],
                            'create_time'      => time(),
                            'update_time'      => time()
                        ];
                        AiPersonaIndividual::create($individualData);
                        break;

                    case 2: // 企业服务
                        $enterpriseData = [
                            'persona_id'      => $personaId,
                            'user_id'         => $userId,
                            'brand_name'      => $params['enterprise']['brand_name'] ?? '',
                            'spokesperson'    => $params['enterprise']['spokesperson'] ?? [],
                            'brand_tone'      => $params['enterprise']['brand_tone'] ?? [],
                            'main_product'    => $params['enterprise']['main_product'] ?? '',
                            'industry_case'   => $params['enterprise']['industry_case'] ?? '',
                            'target_customer' => $params['enterprise']['target_customer'] ?? '',
                            'account_goal'    => $params['enterprise']['account_goal'] ?? [],
                            'create_time'     => time(),
                            'update_time'     => time()
                        ];
                        AiPersonaEnterprise::create($enterpriseData);
                        break;

                    case 3: // 本地商家
                        $localData = [
                            'persona_id'         => $personaId,
                            'user_id'            => $userId,
                            'store_name'         => $params['local']['store_name'] ?? '',
                            'spokesperson'       => $params['local']['spokesperson'] ?? [],
                            'store_atmosphere'   => $params['local']['store_atmosphere'] ?? [],
                            'signature_feature'  => $params['local']['signature_feature'] ?? '',
                            'open_story'         => $params['local']['open_story'] ?? '',
                            'target_customer'    => $params['local']['target_customer'] ?? '',
                            'content_preference' => $params['local']['content_preference'] ?? [],
                            'create_time'        => time(),
                            'update_time'        => time()
                        ];
                        AiPersonaLocal::create($localData);
                        break;
                }
            } else {
                switch ($params['persona_type']) {
                    case 1: // 个人IP
                        $individualData = [
                            'nickname'         => $params['individual']['nickname'] ?? '',
                            'identity'         => $params['individual']['identity'] ?? [],
                            'personality_tags' => $params['individual']['personality_tags'] ?? [],
                            'core_value'       => $params['individual']['core_value'] ?? '',
                            'highlight_story'  => $params['individual']['highlight_story'] ?? '',
                            'target_audience'  => $params['individual']['target_audience'] ?? '',
                            'monetize_paths'   => $params['individual']['monetize_paths'] ?? [],
                            'update_time'      => time()
                        ];
                        if (!array_key_exists('highlight_story', $params['individual'] ?? [])) {
                            $oldIndividual = AiPersonaIndividual::where(['persona_id' => $personaId, 'delete_time' => null])->findOrEmpty();
                            $individualData['highlight_story'] = $oldIndividual['highlight_story'] ?? '';
                        }
                        AiPersonaIndividual::update($individualData, ['persona_id' => $personaId]);
                        break;

                    case 2: // 企业服务
                        $enterpriseData = [
                            'brand_name'      => $params['enterprise']['brand_name'] ?? '',
                            'spokesperson'    => $params['enterprise']['spokesperson'] ?? [],
                            'brand_tone'      => $params['enterprise']['brand_tone'] ?? [],
                            'main_product'    => $params['enterprise']['main_product'] ?? '',
                            'industry_case'   => $params['enterprise']['industry_case'] ?? '',
                            'target_customer' => $params['enterprise']['target_customer'] ?? '',
                            'account_goal'    => $params['enterprise']['account_goal'] ?? [],
                            'update_time'     => time()
                        ];
                        if (!array_key_exists('industry_case', $params['enterprise'] ?? [])) {
                            $oldEnterprise = AiPersonaEnterprise::where(['persona_id' => $personaId, 'delete_time' => null])->findOrEmpty();
                            $enterpriseData['industry_case'] = $oldEnterprise['industry_case'] ?? '';
                        }
                        AiPersonaEnterprise::update($enterpriseData, ['persona_id' => $personaId]);
                        break;

                    case 3: // 本地商家
                        $localData = [
                            'store_name'         => $params['local']['store_name'] ?? '',
                            'spokesperson'       => $params['local']['spokesperson'] ?? [],
                            'store_atmosphere'   => $params['local']['store_atmosphere'] ?? [],
                            'signature_feature'  => $params['local']['signature_feature'] ?? '',
                            'open_story'         => $params['local']['open_story'] ?? '',
                            'target_customer'    => $params['local']['target_customer'] ?? '',
                            'content_preference' => $params['local']['content_preference'] ?? [],
                            'update_time'        => time()
                        ];
                        if (!array_key_exists('open_story', $params['local'] ?? [])) {
                            $oldLocal = AiPersonaLocal::where(['persona_id' => $personaId, 'delete_time' => null])->findOrEmpty();
                            $localData['open_story'] = $oldLocal['open_story'] ?? '';
                        }
                        AiPersonaLocal::update($localData, ['persona_id' => $personaId]);
                        break;
                }
            }

            if ($personaType != $params['persona_type']) {
                $devices = \app\common\model\sv\SvDevice::where('auto_type', 1)->where('persona_id', $personaId)->select();
                foreach ($devices as $device) {
                    \app\api\logic\ApiLogic::deleteOldPersonaTask($device, '人设角色修改，任务取消重置');
                    $device->is_first = 1;
                    $device->save();
                }
            }
            Db::commit();

            self::$returnData = ['persona_id' => $personaId];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 编辑AI人设发布模式
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id']);
            // 1. 验证主表数据归属
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }
            $userId = $persona['user_id'];

            $personaData = [];
            // 切换发布模式
            if (isset($params['publish_mode']) && (int)$params['publish_mode'] != $persona['publish_mode']) {
                $personaData['publish_mode'] = (int)$params['publish_mode'];
                $material                    = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $params['publish_mode']]])->findOrEmpty();
                if ($material->isEmpty()) {
                    $personaData['is_configured'] = 0;
                }
                \app\api\logic\aiPersona\AiPersonaLogic::syncSynthesisWorkModeByPublishMode($personaId, (int)$params['publish_mode']);
            }

            // 更新名称
            if (isset($params['persona_name']) && $params['persona_name'] !== '') {
                $personaData['persona_name'] = $params['persona_name'];
            }

            // 更新头像
            if (isset($params['avatar_url']) && $params['avatar_url'] !== '') {
                $personaData['avatar_url'] = FileService::setFileUrl($params['avatar_url']);
            }

            // 更新简介
            if (isset($params['persona_desc']) && $params['persona_desc'] !== '') {
                $personaData['persona_desc'] = $params['persona_desc'];
            }

            if (!empty($personaData)) {
                $personaData['update_time'] = time();
                AiPersona::update($personaData, ['id' => $personaId]);
            }

            Db::commit();
            self::$returnData = ['persona_id' => $personaId];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除AI人设（主表+对应子表）
     * @param array $ids
     * @return bool
     */
    public static function delete(array $ids): bool
    {
        Db::startTrans();
        try {
            // 1. 验证归属
            $persona = AiPersona::where('id', 'in', $ids)->select();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }
            $res = [];
            foreach ($persona as $item) {
                // 2. 删除主表（软删除）
                AiPersona::destroy($item['id']);

                // 3. 删除对应子表（软删除）
                $personaType = $item['persona_type'];
                switch ($personaType) {
                    case 1:
                        AiPersonaIndividual::destroy(['persona_id' => $item['id']]);
                        break;
                    case 2:
                        AiPersonaEnterprise::destroy(['persona_id' => $item['id']]);
                        break;
                    case 3:
                        AiPersonaLocal::destroy(['persona_id' => $item['id']]);
                        break;
                }
                $res[] = $item['id'];
            }
            $devices = SvDevice::where('persona_id', 'in', $ids)->select();
            foreach ($devices as $device) {
                $device->persona_id = 0;
                $device->save();
                \app\common\model\sv\SvDeviceTask::where('device_code', $device->device_code)
                    ->where('auto_type', 1)
                    ->where('day', date('Y-m-d'))
                    ->select()->delete();
            }

            Db::commit();

            self::$returnData = ['persona_id' => $res];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取AI人设详情（主表+对应子表）
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            // 1. 查询主表
            $persona = AiPersona::where(['id' => $id])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }
            if ((int)$persona->workflow_template_id === 0) {
                $persona = self::createPersonaExclusiveWorkflow($persona);
            }

            $userId               = $persona['user_id'];
            $detail               = $persona->toArray();
            $detail['avatar_url'] = FileService::getFileUrl($persona['avatar_url']);

            // 2. 查询对应子表
            $personaType = $detail['persona_type'];
            switch ($personaType) {
                case 1:
                    $subData              = AiPersonaIndividual::where(['persona_id' => $id, 'delete_time' => null])->findOrEmpty()->toArray();
                    $subData['global_option'] = AiPersonaOptionService::normalize($subData['global_option'] ?? null);
                    $detail['individual'] = $subData;
                    break;
                case 2:
                    $subData              = AiPersonaEnterprise::where(['persona_id' => $id, 'delete_time' => null])->findOrEmpty()->toArray();
                    $subData['global_option'] = AiPersonaOptionService::normalize($subData['global_option'] ?? null);
                    $detail['enterprise'] = $subData;
                    break;
                case 3:
                    $subData         = AiPersonaLocal::where(['persona_id' => $id, 'delete_time' => null])->findOrEmpty()->toArray();
                    $subData['global_option'] = AiPersonaOptionService::normalize($subData['global_option'] ?? null);
                    $detail['local'] = $subData;
                    break;
            }

            $detail['report_new_version'] = !empty($persona['report_gen_time']) && $persona['report_gen_time'] > 1776360000 ? 1 : 0;

            // 检查AI人设配置状态
            //            $result = AiPersonaLogic::checkAiPersonaConfigStatus($id, $userId);
            //            $detail['is_configured'] = $result['is_configured'] ?? 0;




            $detail['template_info'] = \app\common\model\marketing\MarketingTemplate::where('id', $detail['workflow_template_id'])->findOrEmpty()->toArray();
            $detail['schedule_info'] = \app\common\model\marketing\MarketingTemplateSchedule::where('template_id', $detail['workflow_template_id'])->order('start_time', 'asc')->select()->toArray();
            self::$returnData        = $detail;
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createPersonaExclusiveWorkflow(AiPersona $persona): AiPersona
    {
        return PersonaWorkflowService::ensureExclusiveCustomWorkflow($persona);
    }

    private static function getDefaultPlatform(array $platform): string
    {
        $account = [];
        foreach ($platform as $key => $item) {
            array_push($account, [
                'account_type' => $item,
                'order' => $key + 1,
            ]);
        }
        return json_encode($account, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 编辑AI人设的知识库
     * @param array $params
     * @return bool
     */
    public static function knowledgeUpdate(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['persona_id']);
            $persona   = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }
            $userId      = $persona['user_id'];
            $personaData = [
                'main_business'      => $params['main_business'] ?? $persona['main_business'],
                'target_pain_points' => $params['target_pain_points'] ?? $persona['target_pain_points'],
                'conversion_hook'    => $params['conversion_hook'] ?? $persona['conversion_hook'],
                'is_shopping_cart'   => $params['is_shopping_cart'] ?? $persona['is_shopping_cart'],
                'goods_name'         => $params['goods_name'] ?? $persona['goods_name'],
                'is_store_position'  => $params['is_store_position'] ?? $persona['is_store_position'],
                'store_position'     => $params['store_position'] ?? $persona['store_position'],
            ];
            AiPersona::update($personaData, ['id' => $personaId]);

            Db::commit();
            self::$returnData = ['persona_id' => $personaId];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 根据人设ID获取关联的设备列表
     * @param int $personaId
     * @return bool
     */
    public static function getDevicesByPersonaId(int $personaId): bool
    {
        try {
            $persona = AiPersona::where('id', $personaId)->findOrEmpty();

            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }

            $devices          = \app\common\model\sv\SvDevice::where('persona_id', $personaId)->select()->toArray();
            self::$returnData = [
                'devices' => $devices,
                'count'   => count($devices),
            ];
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function analysis($params)
    {
        try {
            if (empty($params['contents']) || empty($params['model'])) {
                throw new Exception('参数错误');
            }
            $request['Content'] = json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request['Model']   = $params['model'];
            $params['model']    = (int)$params['model'];
            if (!isset($params['model']) || !in_array($params['model'], [1, 2, 3])) {
                throw new \Exception('model参数错误');
            }
            //站长扣费
            $aiPersonaService = \app\common\service\ToolsService::AiPersona();
            $center           = $aiPersonaService->analysis($params);
            if ($center['code'] !== 10000) {
                throw new \Exception($center['msg']);
            }

            $res = self::flowRequest($request);
            if ($params['model'] == 1 && !empty($res['ai_persona_individual'])) {
                self::$returnData = json_decode($res['ai_persona_individual'], true);
                return true;
            } else if ($params['model'] == 2 && !empty($res['ai_persona_enterprise'])) {
                self::$returnData = json_decode($res['ai_persona_enterprise'], true);
                return true;
            } else if ($params['model'] == 3 && !empty($res['ai_persona_local'])) {
                self::$returnData = json_decode($res['ai_persona_local'], true);
                return true;
            } else {
                throw new \Exception('分析失败');
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function report($params)
    {
        try {
            $model = $params['model'] ?? 0;
            if (!in_array($model, [4, 5, 6])) {
                throw new \Exception('model参数错误');
            }
            //站长扣费
            $aiPersonaService = \app\common\service\ToolsService::AiPersona();
            $center            = $aiPersonaService->report($params);
            if ($center['code'] !== 10000) {
                throw new \Exception($center['message']);
            }

            $request = [
                'Content' => json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'Model'   => $model
            ];
            $res     = self::flowRequest($request);

            $resultKey = match ((int)$model) {
                4       => 'individual',
                5       => 'enterprise',
                6       => 'local',
                default => null
            };

            if (empty($resultKey) || empty($res[$resultKey])) {
                throw new \Exception('报告生成失败');
            }
            $report   = AiPersonaReport::where(['persona_id' => $params['persona_id']])->findOrEmpty();
            $userId   = AiPersona::where('id', $params['persona_id'])->value('user_id');
            $saveData = [
                'user_id'     => $userId,
                'contents'    => $request['Content'],
                'result'      => json_encode([$resultKey => json_decode($res[$resultKey], true)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_draft'    => 1,
                'persona_id'  => $params['persona_id'],
                'create_time' => time(),
            ];
            if ($report->isEmpty()) {
                $report = AiPersonaReport::create($saveData);
            } else {
                $report->save([
                    'result'   => $saveData['result'],
                    'is_draft' => 1
                ]);
            }

            // 更新人设主表报告相关字段
            $personaData = [
                'report_content'  => $saveData['result'],
                'report_status'   => 2,
                'report_gen_time' => time(),
                'update_time'     => time()
            ];
            AiPersona::where('id', $params['persona_id'])->update($personaData);

            $result             = $report->refresh()->toArray();
            $result['contents'] = json_decode($result['contents'], true);
            $result['result']   = json_decode($result['result'], true);
            self::$returnData   = $result;

            $devices = \app\common\model\sv\SvDevice::where('auto_type', 1)->where('persona_id',  $params['persona_id'])->select();
            foreach ($devices as $device) {
                \app\api\logic\ApiLogic::deleteOldPersonaTask($device, '人设报告修改，任务取消重置');
                $device->is_first = 1;
                $device->save();
            }

            $agentModel = [
                4 => '个人IP',
                5 => '企业IP',
                6 => '本地IP'
            ];
            $agentRequest = [
                'Content' => $res[$resultKey],
                'Model'   => $agentModel[$model]
            ];
            self::autoCreateAgent($agentRequest, $params['persona_id'], $userId);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * coze工作流请求
     */
    private static function flowRequest($params): array
    {
        $automationService = \app\common\service\ToolsService::Automation();
        $url               = $automationService::URL;
        $workflow_id       = $automationService::WORKFLOW_ID;
        Log::channel('ipPersona')->write(
            'coze工作流请求：' . json_encode([
                'url' => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $body     = [
            'workflow_id' => $workflow_id,
            'parameters'  => $params,
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $automationService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client   = new Client(['timeout' => 600, 'verify' => false]);
        $rsp      = $client->post($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        Log::channel('ipPersona')->write('coze工作流请求结果' . $contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (($data['code'] ?? -1) !== 0) {
            return [];
        }
        $data['data'] = json_decode($data['data'], true);
        if (!empty($data['data'])) {
            return $data['data'];
        }
        return [];
    }

    /**
     * autoCreateAgent工作流请求
     */
    private static function agentFlowRequest($params): array
    {
        $automationService = \app\common\service\ToolsService::AiPersona();
        $url               = $automationService::URL;
        $workflow_id       = $automationService::AGENT_WORKFLOW_ID;
        Log::channel('ipPersona')->write(
            '自动创建智能体请求：' . json_encode([
                'url' => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $body     = [
            'workflow_id' => $workflow_id,
            'parameters'  => $params,
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $automationService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client   = new Client(['timeout' => 600, 'verify' => false]);
        $rsp      = $client->post($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        Log::channel('ipPersona')->write('自动创建智能体请求结果' . $contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (($data['code'] ?? -1) !== 0) {
            return [];
        }
        $data['data'] = json_decode($data['data'], true);
        if (!empty($data['data'])) {
            return $data['data'];
        }
        return [];
    }

    public static function autoCreateAgent($params, $personaId, $userId)
    {
        $createdIds = [];
        $userId = (int)$userId;
        try {
            $params['Content'] = json_encode($params['Content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $res = self::agentFlowRequest($params);
            if (empty($res)) {
                return true;
            }

            $modelPair = MemberService::pickRandomChatModelPair($userId);
            if ($modelPair === null) {
                self::setError('当前等级暂无可用对话模型，请联系站长开启');
                return false;
            }

            $commentAgentRule = $res['Comment_agent']; //社媒评论区智能体
            $dmAgentRule = $res['Reply_agent']; //社媒私信智能体
            $momentsAgentRule = $res['Wechat_Moment_agent']; //微信朋友圈互动智能体
            $wechatChatAgentRule = $res['Wechat_agent']; //微信1V1私聊智能体
            $agentRuleArray = [$commentAgentRule, $dmAgentRule, $momentsAgentRule, $wechatChatAgentRule];
            $ids = [];
            foreach ($agentRuleArray as $key => $agent) {
                $name = match ($key) {
                    0       => '社媒评论区智能体',
                    1       => '社媒私信智能体',
                    2       => '微信朋友圈互动智能体',
                    3       => '微信私聊智能体',
                    default => ''
                };
                $robotAdd = [
                    'context_num' => 3,
                    'kb_type' => 2,
                    'quota_exempt' => 1,
                ];
                $robot = KbRobotLogic::add($robotAdd, $userId);
                if ($robot === false || empty($robot['id'])) {
                    self::rollbackAutoCreatedRobots($createdIds);
                    self::setError(KbRobotLogic::getError() ?: '创建智能体失败');
                    return false;
                }
                $createdIds[] = (int)$robot['id'];
                $ids[$key] = $robot['id'];

                $robotEdit = [
                    "id" => $robot['id'],
                    "roles_prompt" => $agent,
                    "kb_type" => 2,
                    "kb_ids" => [],
                    "icons" => "",
                    "image" => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')) ?? '',
                    "bg_image" => "",
                    "name" => $params['Model'] . ' - ' . $name,
                    "intro" => "默认助理简介",
                    "model_id" => $modelPair['model_id'],
                    "model_sub_id" => $modelPair['model_sub_id'],
                    "search_mode" => "similar",
                    "search_tokens" => 3000,
                    "search_similar" => 0.5,
                    "ranking_status" => 0,
                    "ranking_score" => 0.5,
                    "context_num" => 3,
                    "is_public" => 0,
                    "is_enable" => 1,
                    "optimize_ask" => 0,
                    "optimize_m_id" => "",
                    "optimize_s_id" => "",
                    "search_empty_type" => 1,
                    "search_empty_text" => "",
                    "top_p" => 0.8,
                    "temperature" => 0.3,
                    "presence_penalty" => 0,
                    "frequency_penalty" => 0,
                    "logprobs" => 0,
                    "top_logprobs" => 0,
                    "welcome_introducer" => "",
                    "copyright" => "",
                    "menus" => [],
                    "flow_status" => 0,
                    "flow_config" => [
                        "workflow_id" => "",
                        "bot_id" => "",
                        "app_id" => "",
                        "api_token" => ""
                    ],
                    "threshold" => 0.7,
                    "mode_type" => 3,
                    "max_tokens" => 4096
                ];
                if (!KbRobotLogic::edit($robotEdit, $userId)) {
                    self::rollbackAutoCreatedRobots($createdIds);
                    self::setError(KbRobotLogic::getError() ?: '配置智能体失败');
                    return false;
                }
            }
            AiPersonaAgentConfig::syncAutoCreatedAgentConfig($userId, (int)$personaId, $ids);
            return true;
        } catch (\Exception $e) {
            self::rollbackAutoCreatedRobots($createdIds);
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 回滚本轮自动创建失败留下的智能体空壳
     */
    private static function rollbackAutoCreatedRobots(array $robotIds): void
    {
        $robotIds = array_values(array_filter(array_map('intval', $robotIds)));
        if (empty($robotIds)) {
            return;
        }
        try {
            KbRobotLogic::del($robotIds);
        } catch (\Throwable $e) {
            Log::channel('ipPersona')->write('回滚自动创建智能体失败 ids=' . json_encode($robotIds) . ' err=' . $e->getMessage());
        }
    }

    public static function publishConfigDetail(int $personaId): bool
    {
        try {
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            self::$returnData = self::formatPublishConfigReturn($persona);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function publishConfigUpdate(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['persona_id'] ?? $params['id'] ?? 0);
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            $oldConfig = $persona['content_publish_config'];
            $config = AiPersona::mergeContentPublishConfigOverrides(
                $params['content_publish_config'] ?? $persona['content_publish_config'],
                $params
            );
            $configError = AiPersona::validateContentPublishConfig($config);
            if ($configError !== '') {
                throw new Exception($configError);
            }

            $isShoppingCart = isset($params['is_shopping_cart']) ? (int)$params['is_shopping_cart'] : (int)($persona['is_shopping_cart'] ?? 0);
            $isStorePosition = isset($params['is_store_position']) ? (int)$params['is_store_position'] : (int)($persona['is_store_position'] ?? 0);
            $storePosition = array_key_exists('store_position', $params)
                ? self::normalizePublishConfigText($params['store_position'])
                : (string)($persona['store_position'] ?? '');
            if ($isStorePosition === 1 && $storePosition === '') {
                throw new Exception('定位地址不能为空');
            }

            $personaData = [
                'content_publish_config' => $config,
                'is_shopping_cart' => $isShoppingCart,
                'goods_name' => array_key_exists('goods_name', $params)
                    ? self::normalizePublishConfigText($params['goods_name'])
                    : (string)($persona['goods_name'] ?? ''),
                'is_store_position' => $isStorePosition,
                'store_position' => $storePosition,
                'update_time' => time(),
            ];
            AiPersona::update($personaData, ['id' => $personaId]);

            $changedPlatforms = CopywritingLibraryLogic::getChangedPublishLibraryRulePlatforms($oldConfig, $config);
            if (!empty($changedPlatforms)) {
                CopywritingLibraryLogic::resetPublishPlatformUseCounts($personaId, $changedPlatforms);
            }

            Db::commit();
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            self::$returnData = self::formatPublishConfigReturn($persona);
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function formatPublishConfigReturn(AiPersona $persona): array
    {
        $config = AiPersona::normalizeContentPublishConfig($persona['content_publish_config']);
        return [
            'persona_id' => (int)$persona['id'],
            'content_publish_config' => $config,
            'is_content_location' => (int)$config['is_content_location'],
            'content_location' => (string)$config['content_location'],
            'is_shopping_cart' => (int)($persona['is_shopping_cart'] ?? 0),
            'goods_name' => (string)($persona['goods_name'] ?? ''),
            'is_store_position' => (int)($persona['is_store_position'] ?? 0),
            'store_position' => (string)($persona['store_position'] ?? ''),
        ];
    }

    private static function normalizePublishConfigText($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$value);
    }
}
