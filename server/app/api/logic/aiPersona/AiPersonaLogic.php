<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\kb\KbRobotLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\AiPersonaReport;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\model\aiPersona\Material;
use app\common\model\user\User;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\aiPersona\AiPersonaTextService;
use app\common\service\ConfigService;
use app\common\service\digitalHuman\DefaultPublicAnchorProvisionService;
use app\common\service\digitalHuman\DefaultPublicVoiceProvisionService;
use app\common\service\FileService;
use app\common\service\MemberService;
use Exception;
use GuzzleHttp\Client;
use think\facade\Db;
use think\facade\Log;

class AiPersonaLogic extends ApiLogic
{
    public static function clue(array $params)
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['id'], 'user_id' => self::$uid])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $personaRule = self::getPersonaRule($persona);
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
            $personaRule->is_clue_updated              = 1;
            $personaRule->save();

            $config = \app\common\model\aiPersona\AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['id'])->findOrEmpty();
            if (!$config->isEmpty()) {
                \think\facade\Log::channel('auto')->write('clue 存在 -- 客户触达自动任务配置' . json_encode($config->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'clue');
            }else{
                \think\facade\Log::channel('auto')->write('clue 不存在 -- 客户触达自动任务配置' . json_encode($personaRule->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'clue');
            }

            \app\common\model\aiPersona\AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['id'])->select()->delete();
            sleep(1);
            ClueTouchLogic::detail([
                'persona_id' => $params['id']
            ]);
            $agentConfig = AiPersonaAgentConfig::where([
                'persona_id'  => $params['id'],
                'user_id'     => self::$uid,
                'delete_time' => null
            ])->findOrEmpty();
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
            Log::channel('device')->write($e->__toString());
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function wechat(array $params)
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['id'], 'user_id' => self::$uid])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $personaRule = self::getPersonaRule($persona);
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

            $personaRule->wechat_add_friend_script = $output['friend_request_scripts'] ?? [];
            $personaRule->wechat_comment_speech    = $output['moments_comment_scripts'] ?? [];
            $personaRule->update_time              = time();
            $personaRule->is_wechat_updated              = 1;
            $personaRule->save();

            \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['id'])->select()->delete();
            sleep(1);
            InteractiveLogic::detail([
                'persona_id' => $params['id']
            ]);
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

    public static function hotWords(array $params)
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['id'], 'user_id' => self::$uid])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $tokenScene = 'get_hot_words';
            $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS;
            $unit = \app\api\logic\service\TokenLogService::checkToken($persona->user_id, $tokenScene); // 添加辅助参数

            $personaRule = self::getPersonaRule($persona);
            $response = \app\common\service\ToolsService::Coze()->getHotWords([
                'keywords' => $personaRule->clue_content,
            ]);

            if ((int)$response['code'] !== 10000 || !isset($response['data']['content'])) {
                self::setError($response['msg'] ?? '获取爆款关键词失败');
                return false;
            }

            $points = $unit;
            $keywords = $response['data']['content'] ?? [];
            if ($points > 0) {
                $extra = ['生成关键词数' => count($keywords), '算力单价' => $unit, '实际消耗算力' => $points, '描述' => '根据输入内容提取短视频热点搜索关键词-重新生成'];
                $taskId = generate_unique_task_id();
                //token扣除
                \app\common\model\user\User::userTokensChange($persona->user_id, $points);
                //记录日志
                \app\common\logic\AccountLogLogic::recordUserTokensLog(true, $persona->user_id, $tokenCode, $points, $taskId, $extra);
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

    public static function updateHotWords(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id'] ?? $params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('AI人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
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
                'hot_words'  => $personaRule->hot_words,
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
        return match ((int)$persona->persona_type) {
            1 => AiPersonaIndividual::where('persona_id', $persona->id)->where('user_id', $persona->user_id)->findOrEmpty(),
            2 => AiPersonaEnterprise::where('persona_id', $persona->id)->where('user_id', $persona->user_id)->findOrEmpty(),
            3 => AiPersonaLocal::where('persona_id', $persona->id)->where('user_id', $persona->user_id)->findOrEmpty(),
            default => throw new Exception('IP人设类型错误'),
        };
    }

    private static function normalizeHotWords(mixed $value): array
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

    public static function updateOption(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id'] ?? $params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('AI人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
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
            $config = AiPersonaWechatInteractionConfig::where('user_id', $userId)->where('persona_id', $personaId)->findOrEmpty();
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

    /**
     * 新增AI人设（主表+对应子表）
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function add(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            // 企业空间内成员/管理员已到期 → 禁止创建 IP
            \app\common\service\TeamMemberService::assertActive($userId);
            // 1. 新增主表
            $personaData = [
                'user_id'       => $userId,
                'persona_name'  => $params['persona_name'],
                'persona_type'  => $params['persona_type'],
                'avatar_url'    => !empty($params['avatar_url']) ? FileService::setFileUrl($params['avatar_url']) : 'static/images/person_avatar.png',
                'quick_desc'    => $params['quick_desc'] ?? '',
                'persona_desc'  => $params['persona_desc'] ?? '',
                'industry'      => $params['industry'] ?? '',
                'is_configured' => 0,
                'status'        => $params['status'] ?? 1,
                'report_status' => 0,
                'main_business'      => $params['main_business'] ?? '',
                'target_pain_points' => $params['target_pain_points'] ?? '',
                'conversion_hook'    => $params['conversion_hook'] ?? '',
                'is_shopping_cart'   => $params['is_shopping_cart'] ?? 0,
                'goods_name'         => $params['goods_name'] ?? '',
                'is_store_position'  => $params['is_store_position'] ?? 0,
                'store_position'     => $params['store_position'] ?? '',
                'create_time'   => time(),
                'update_time'   => time()
            ];
            $personaData = array_merge($personaData, AiPersona::buildTrackingConfigData($params));
            $personaData = array_merge($personaData, AiPersonaTextService::buildPersonaMainData($params));
            if (!array_key_exists('duration', $personaData)) {
                $personaData['duration'] = AiPersona::TRACKING_DURATION_DEFAULT;
            }

            $personaId = AiPersona::create($personaData)->id;
            $synthesisData = [
                'user_id'    => $userId,
                'persona_id' => $personaId,
                'work_mode' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS,
                'product_use_mode' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM,
                'product_reuse_mode' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE,
                'news_mixcut_duration' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_DEFAULT,
                'library_use_mode' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::LIBRARY_USE_MODE_RANDOM,
                'library_reuse_mode' => \app\common\model\aiPersona\AiPersonaSynthesisConfig::LIBRARY_REUSE_MODE_ONCE,
                'create_time' => time(),
                'update_time' => time(),
            ];
            // 2. 根据类型新增对应子表
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
                    //                     本地商家：纯素材 纯AI生成文案  默认新闻体+素材混剪
                    // 个人IP：AI+素材  爆款仿写  默认数字人口播混剪+素材混剪
                    // 企业服务：AI+素材   爆款仿写  默认数字人口播混剪+素材混剪
                    AiPersonaIndividual::create($individualData);
                    $synthesisData['generation_types']      = json_encode([1, 3]);
                    $synthesisData['visual_material_source'] = 2; // AI+素材库
                    $synthesisData['copywriting_source']     = 1; // 仿写
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
                    $synthesisData['generation_types']      = json_encode([1, 3]);
                    $synthesisData['visual_material_source'] = 2; // AI+素材库
                    $synthesisData['copywriting_source']     = 1; // 仿写
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
                    $synthesisData['generation_types']      = json_encode([3, 4]);
                    $synthesisData['visual_material_source'] = 3; // 纯素材库
                    $synthesisData['copywriting_source']     = 2; // AI生成
                    break;

                default:
                    throw new Exception('无效的人设类型');
            }
            \app\common\model\aiPersona\AiPersonaSynthesisConfig::create($synthesisData);
            // 3. 添加人设智能体设置
            self::initCustomerServiceConfig($userId, $personaId);
            // 4. 添加人设默认形象、音色
            DefaultPublicAnchorProvisionService::provisionForPersona(
                $userId,
                $personaId,
                (int)$params['persona_type']
            );
            DefaultPublicVoiceProvisionService::provisionForPersona(
                $userId,
                $personaId,
                (int)$params['persona_type']
            );

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
     * 初始化智能客服配置
     * @param int $userId
     * @param int $personaId
     * @return void
     */
    private static function initCustomerServiceConfig(int $userId, int $personaId): void
    {
        AiPersonaAgentConfig::create([
            'user_id' => $userId,
            'persona_id' => $personaId,
            'comment_enabled' => 0,
            'comment_agent_id' => 0,
            'comment_type' => 1,
            'comment_speech' => [],
            'dm_enabled' => 0,
            'dm_agent_id' => 0,
            'dm_type' => 1,
            'dm_speech' => [],
            'wechat_chat_enabled' => 0,
            'wechat_chat_agent_id' => 0,
            'wechat_chat_type' => 1,
            'wechat_chat_speech' => [],
            'moments_enabled' => 0,
            'moments_agent_id' => 0,
            'moments_action' => 3,
            'moments_type' => 1,
            'moments_speech' => [],
            'shutoff_comment_type' => 1,
            'shutoff_comment_agent_id' => 0,
            'shutoff_comment_speech' => [],
            'shutoff_msg_type' => 1,
            'shutoff_msg_agent_id' => 0,
            'shutoff_msg_speech' => [],
            'platform_agent_config' => AiPersonaAgentConfig::getDefaultPlatformAgentConfig(),
            'create_time' => time(),
            'update_time' => time(),
        ]);
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
     * @param int $userId
     * @return bool
     */
    public static function edit(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id']);
            // 1. 验证主表数据归属
            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

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
                self::syncSynthesisWorkModeByPublishMode($personaId, (int)$params['publish_mode']);
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
                    self::deleteOldPersonaTask($device, '人设角色修改，任务取消重置');
                    $device->is_first = 1;
                    $device->save();
                }
                AiPersona::where('id', $personaId)->update(['workflow_template_id' => 0]);
                Log::channel('ipPersona')->write('人设id' . $personaId . '已切换类型，24h任务已重置');
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
    public static function update(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['id']);
            // 1. 验证主表数据归属
            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            $personaData = [];
            // 切换发布模式
            if (isset($params['publish_mode']) && (int)$params['publish_mode'] != $persona['publish_mode']) {
                $personaData['publish_mode'] = (int)$params['publish_mode'];
                $material                    = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $params['publish_mode']]])->findOrEmpty();
                if ($material->isEmpty()) {
                    $personaData['is_configured'] = 0;
                }
                self::syncSynthesisWorkModeByPublishMode($personaId, (int)$params['publish_mode']);
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
     * 人设发布模式同步视频剪辑工作方式
     */
    public static function syncSynthesisWorkModeByPublishMode(int $personaId, int $publishMode): void
    {
        $workMode = $publishMode === 2
            ? \app\common\model\aiPersona\AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT
            : \app\common\model\aiPersona\AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS;
        \app\common\model\aiPersona\AiPersonaSynthesisConfig::where('persona_id', $personaId)->update([
            'work_mode' => $workMode,
            'update_time' => time(),
        ]);
    }

    /**
     * 删除AI人设（主表+对应子表）
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function delete(int $id, int $userId): bool
    {
        Db::startTrans();
        try {
            // 1. 验证归属
            $persona = AiPersona::where(['id' => $id, 'user_id' => $userId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            // 2. 删除主表（软删除）
            AiPersona::destroy($id);

            // 3. 删除对应子表（软删除）
            $personaType = $persona['persona_type'];
            switch ($personaType) {
                case 1:
                    AiPersonaIndividual::destroy(['persona_id' => $id]);
                    break;
                case 2:
                    AiPersonaEnterprise::destroy(['persona_id' => $id]);
                    break;
                case 3:
                    AiPersonaLocal::destroy(['persona_id' => $id]);
                    break;
            }

            Db::commit();

            self::$returnData = ['persona_id' => $id];
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
     * @param int $userId
     * @return bool
     */
    public static function detail(int $id, int $userId): bool
    {
        try {
            // 1. 查询主表
            $persona = AiPersona::where(['id' => $id, 'user_id' => $userId, 'delete_time' => null])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }
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
            self::$returnData        = $detail;
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取AI人设详情（主表+对应子表）
     * @param int $personaId
     * @param int $userId
     * @return bool
     */
    public static function configStatus(int $personaId, int $userId): bool
    {
        try {
            $persona = AiPersona::where(['id' => $personaId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在');
            }

            $personaAgentConfig               = AiPersonaAgentConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($personaAgentConfig->isEmpty()) {
                $res['persona_agent_config'] = 0;
            } else if ((!$personaAgentConfig->hasAnyEffectivePlatformAgentConfig() && ($personaAgentConfig['comment_agent_id'] == 0 || $personaAgentConfig['dm_agent_id'] == 0)) || $personaAgentConfig['wechat_chat_agent_id'] == 0 || $personaAgentConfig['moments_agent_id'] == 0) {
                $res['persona_agent_config'] = 0;
            } else {
                $res['persona_agent_config'] = 1;
            }
            $material                         = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $persona['publish_mode']]])->findOrEmpty();
            $res['material_config']           = $material->isEmpty() ? 0 : 1;
            $trafficConfig                    = AiPersonaTrafficConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($trafficConfig->isEmpty()) {
                $res['traffic_config'] = 0;
            } else {
                $res['traffic_config'] = 1;
            }
            $wechatInteractionConfig          = AiPersonaWechatInteractionConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($wechatInteractionConfig->isEmpty()) {
                $res['wechat_interaction_config'] = 0;
            } else {
                $res['wechat_interaction_config'] = 1;
            }
            $digitalVoice                     = AiPersonaDigitalVoice::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            $res['digital_config']            = $digitalVoice->isEmpty() ? 0 : 1;

            self::$returnData = $res;
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 爆款复刻：校验人设是否具备可用形象/音色
     * 前端规则：has_avatar 或 has_voice 至少一个为 1 才可勾选
     */
    public static function checkViralAssets(int $personaId, int $userId): bool
    {
        try {
            if ($personaId <= 0) {
                throw new Exception('人设ID不能为空');
            }

            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

            $hasAvatar = !AiPersonaDigitalAvatar::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', $userId)
                ->findOrEmpty()
                ->isEmpty();

            $hasVoice = !AiPersonaDigitalVoice::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', $userId)
                ->findOrEmpty()
                ->isEmpty();

            self::$returnData = [
                'has_avatar' => $hasAvatar ? 1 : 0,
                'has_voice'  => $hasVoice ? 1 : 0,
            ];
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 编辑AI人设的知识库
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function knowledgeUpdate(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['persona_id']);
            $persona   = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('人设不存在或无操作权限');
            }

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

    public static function publishConfigDetail(int $personaId, int $userId): bool
    {
        try {
            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
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

    public static function publishConfigUpdate(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['persona_id'] ?? $params['id'] ?? 0);
            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
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
            $persona = AiPersona::where(['id' => $personaId, 'user_id' => $userId])->findOrEmpty();
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

    private static function normalizePublishConfigText(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$value);
    }

    /**
     * 更新报告生成状态
     * @param int $personaId
     * @param int $status
     * @param string $reportContent
     * @return bool
     */
    public static function updateReportStatus(int $personaId, int $status, string $reportContent = ''): bool
    {
        try {
            $data = [
                'report_status' => $status,
                'update_time'   => time()
            ];
            if ($status == 2 && $reportContent) { // 生成成功
                $data['report_content']  = $reportContent;
                $data['report_gen_time'] = time();
            }
            AiPersona::update($data, ['id' => $personaId]);

            self::$returnData = [
                'persona_id'    => $personaId,
                'report_status' => $status
            ];
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 根据人设ID获取关联的设备列表
     * @param int $personaId
     * @param int $userId
     * @return bool
     */
    public static function getDevicesByPersonaId(int $personaId, int $userId): bool
    {
        try {
            $persona = AiPersona::where('id', $personaId)
                ->where('user_id', $userId)
                ->findOrEmpty();

            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }

            $devices = \app\common\model\sv\SvDevice::where('persona_id', $personaId)
                ->where('user_id', $userId)
                ->select()
                ->toArray();

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

    public static function analysis(array $params): bool
    {
        try {
            if (empty($params['contents']) || empty($params['model'])) {
                throw new Exception('参数错误');
            }
            $params['model'] = (int)$params['model'];
            if (!in_array($params['model'], [1, 2, 3], true)) {
                throw new \Exception('model参数错误');
            }
            // 先校验到期/余额,再调分析(避免过期成员白嫖生成报告)
            TokenLogService::checkToken((int)self::$uid, 'ai_persona_analysis');
            $request['Content'] = json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request['Model']   = $params['model'];
            $res = self::flowRequest($request);
            if ($params['model'] == 1 && !empty($res['ai_persona_individual'])) {
                self::$returnData = json_decode($res['ai_persona_individual'], true);
                //扣费
                $aiPersonaService = \app\common\service\ToolsService::AiPersona();
                $center           = $aiPersonaService->analysis($params);
                if ($center['code'] == 10000) {
                    $unit  = TokenLogService::checkToken(self::$uid, 'ai_persona_analysis');
                    $extra = [
                        '扣费项目'     => '账号Ip人设分析',
                        '算力单价'     => $unit . '算力/次',
                        '实际消耗算力' => $unit
                    ];
                    User::userTokensChange(self::$uid, $unit);
                    AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AI_PERSONA_ANALYSIS, $unit, '', $extra);
                }
                return true;
            } else if ($params['model'] == 2 && !empty($res['ai_persona_enterprise'])) {
                self::$returnData = json_decode($res['ai_persona_enterprise'], true);
                //扣费
                $aiPersonaService = \app\common\service\ToolsService::AiPersona();
                $center           = $aiPersonaService->analysis($params);
                if ($center['code'] == 10000) {
                    $unit  = TokenLogService::checkToken(self::$uid, 'ai_persona_analysis');
                    $extra = [
                        '扣费项目'     => '账号Ip人设分析',
                        '算力单价'     => $unit . '算力/次',
                        '实际消耗算力' => $unit
                    ];
                    User::userTokensChange(self::$uid, $unit);
                    AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AI_PERSONA_ANALYSIS, $unit, '', $extra);
                }
                return true;
            } else if ($params['model'] == 3 && !empty($res['ai_persona_local'])) {
                self::$returnData = json_decode($res['ai_persona_local'], true);
                //扣费
                $aiPersonaService = \app\common\service\ToolsService::AiPersona();
                $center           = $aiPersonaService->analysis($params);
                if ($center['code'] == 10000) {
                    $unit  = TokenLogService::checkToken(self::$uid, 'ai_persona_analysis');
                    $extra = [
                        '扣费项目'     => '账号Ip人设分析',
                        '算力单价'     => $unit . '算力/次',
                        '实际消耗算力' => $unit
                    ];
                    User::userTokensChange(self::$uid, $unit);
                    AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AI_PERSONA_ANALYSIS, $unit, '', $extra);
                }
                return true;
            } else {
                throw new \Exception('分析失败');
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function report(array $params): bool
    {
        try {
            $model = $params['model'] ?? 0;
            if (!in_array($model, [4, 5, 6])) {
                throw new \Exception('model参数错误');
            }
            // 先校验到期/余额,再生成报告
            TokenLogService::checkToken((int)self::$uid, 'ai_persona_report');
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
            $report   = AiPersonaReport::where(['persona_id' => $params['persona_id'], 'user_id' => self::$uid])->findOrEmpty();
            $saveData = [
                'user_id'     => self::$uid,
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

            //扣费
            $aiPersonaService = \app\common\service\ToolsService::AiPersona();
            $center           = $aiPersonaService->report($params);

            if ($center['code'] === 10000) {
                $unit  = TokenLogService::checkToken(self::$uid, 'ai_persona_report');
                $extra = [
                    '扣费项目'     => 'Ip人设报告',
                    '算力单价'     => $unit . '算力/次',
                    '实际消耗算力' => $unit
                ];
                User::userTokensChange(self::$uid, $unit);
                AccountLogLogic::recordUserTokensLog(
                    true,
                    self::$uid,
                    AccountLogEnum::TOKENS_DEC_AI_PERSONA_REPORT,
                    $unit,
                    '',
                    $extra
                );
            }

            $devices = \app\common\model\sv\SvDevice::where('auto_type', 1)->where('persona_id',  $params['persona_id'])->select();
            foreach ($devices as $device) {
                self::deleteOldPersonaTask($device, '人设报告修改，任务取消重置');
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
            self::autoCreateAgent($agentRequest, (int)$params['persona_id']);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function autoCreateAgent(array $params, int $personaId): bool
    {
        $createdIds = [];
        $userId = (int)self::$uid;
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
                    self::rollbackAutoCreatedRobots($createdIds, $userId);
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
                    self::rollbackAutoCreatedRobots($createdIds, $userId);
                    self::setError(KbRobotLogic::getError() ?: '配置智能体失败');
                    return false;
                }
            }
            AiPersonaAgentConfig::syncAutoCreatedAgentConfig($userId, (int)$personaId, $ids);
            return true;
        } catch (\Exception $e) {
            self::rollbackAutoCreatedRobots($createdIds, $userId);
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 回滚本轮自动创建失败留下的智能体空壳，避免「默认助理」无提示词残留
     */
    private static function rollbackAutoCreatedRobots(array $robotIds, int $userId): void
    {
        foreach ($robotIds as $robotId) {
            $robotId = (int)$robotId;
            if ($robotId <= 0) {
                continue;
            }
            try {
                KbRobotLogic::del($robotId, $userId);
            } catch (\Throwable $e) {
                Log::channel('ipPersona')->write('回滚自动创建智能体失败 id=' . $robotId . ' err=' . $e->getMessage());
            }
        }
    }

    /**
     * coze工作流请求
     */
    private static function flowRequest(array $params): array
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
    private static function agentFlowRequest(array $params): array
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
    /**
     * 更新微信发布模式
     */
    public static function updateWechatPublishMode(array $params, int $userId): bool
    {
        try {
            $personaId = $params['id'] ?? 0;
            if ($personaId <= 0) {
                self::setError('AI人设ID不能为空');
                return false;
            }
            $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('AI人设不存在');
                return false;
            }
            $wechatPublishMode = $params['wechat_publish_mode'] ?? 3;
            if (!in_array($wechatPublishMode, [1, 2, 3])) {
                self::setError('微信发布模式错误');
                return false;
            }
            AiPersona::where('id', $personaId)->update([
                'wechat_publish_mode' => $wechatPublishMode,
            ]);
            self::$returnData = $persona->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
