<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
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
use app\common\model\user\User;
use app\common\service\FileService;
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

            $result = json_decode($response['data']['content'], true);
            $output = json_decode($result['output'], true);

            $personaRule->clue_acquire_keywords   = $output['video_search_keywords'];
            $personaRule->clue_intercept_keywords = $output['comment_clue_keywords'];
            $personaRule->clue_comment_scripts    = $output['comment_drainage_scripts'];
            $personaRule->clue_dm_scripts         = $output['dm_interception_scripts'];
            $personaRule->update_time             = time();
            $personaRule->save();


            \app\common\model\aiPersona\AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['id'])->select()->delete();
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

            $result = json_decode($response['data']['content'], true);
            $output = json_decode($result['output'], true);

            $personaRule->wechat_add_friend_script = $output['friend_request_scripts'];
            $personaRule->wechat_comment_speech    = $output['moments_comment_scripts'];
            $personaRule->update_time              = time();
            $personaRule->save();

            \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['id'])->select()->delete();
            self::$returnData = $personaRule->toArray();
            Db::commit();
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
                'create_time'   => time(),
                'update_time'   => time()
            ];

            $personaId = AiPersona::create($personaData)->id;

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

                default:
                    throw new Exception('无效的人设类型');
            }

            // 3. 添加人设智能体设置
            $agentConfig = [
                'user_id'              => $userId,
                'persona_id'           => $personaId,
                'comment_enabled'      => 0,
                'comment_agent_id'     => 0,
                'dm_enabled'           => 0,
                'dm_agent_id'          => 0,
                'wechat_chat_enabled'  => 0,
                'wechat_chat_agent_id' => 0,
                'moments_enabled'      => 0,
                'moments_agent_id'     => 0,
                'create_time'          => time(),
            ];
            AiPersonaAgentConfig::create($agentConfig);

            Db::commit();

            self::$returnData = ['persona_id' => $personaId];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
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
        }else if ($personaAgentConfig['comment_agent_id'] == 0 || $personaAgentConfig['dm_agent_id'] == 0 || $personaAgentConfig['wechat_chat_agent_id'] == 0 || $personaAgentConfig['moments_agent_id'] == 0) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先配置智能体', 'is_configured' => 0];
        }
        $material = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $persona['publish_mode']]])->findOrEmpty();
        if ($material->isEmpty()) {
            $persona->is_configured = 0;
            $persona->save();
            return ['res' => false, 'msg' => '请先上传素材', 'is_configured' =>0];
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
                'persona_desc' => $params['persona_desc'] ?? '',
                'industry'     => $params['industry'] ?? '',
                'status'       => $params['status'] ?? $persona['status'],
                'update_time'  => time()
            ];
            // 切换发布模式
            if (isset($params['publish_mode']) && (int)$params['publish_mode'] != $persona['publish_mode']) {
                $personaData['publish_mode'] = (int)$params['publish_mode'];
                $material                    = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $params['publish_mode']]])
                    ->findOrEmpty();
                if ($material->isEmpty()) {
                    $personaData['is_configured'] = 0;
                }
            }
            // 切换人设类型、内容变动时需重新生成报告
            if (isset($params['is_create_report']) && $params['is_create_report'] == 1){
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

            // 切换发布模式
            if (isset($params['publish_mode']) && (int)$params['publish_mode'] != $persona['publish_mode']) {
                $personaData['publish_mode'] = (int)$params['publish_mode'];
                $material                    = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $params['publish_mode']]])->findOrEmpty();
                if ($material->isEmpty()) {
                    $personaData['is_configured'] = 0;
                }
            }

            // 更新名称
            if (isset($params['persona_name']) && $params['persona_name'] !== '') {
                $personaData['persona_name'] = $params['persona_name'];
            }

            // 更新头像
            if (isset($params['avatar_url']) && $params['avatar_url'] !== '') {
                $personaData['avatar_url'] = FileService::setFileUrl($params['avatar_url']);
            }
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
                    $detail['individual'] = $subData;
                    break;
                case 2:
                    $subData              = AiPersonaEnterprise::where(['persona_id' => $id, 'delete_time' => null])->findOrEmpty()->toArray();
                    $detail['enterprise'] = $subData;
                    break;
                case 3:
                    $subData         = AiPersonaLocal::where(['persona_id' => $id, 'delete_time' => null])->findOrEmpty()->toArray();
                    $detail['local'] = $subData;
                    break;
            }

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
     * @param int $id
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
//            if ($persona['is_configured'] == 1) {
//                self::$returnData = ['persona_agent_config' => 1, 'material_config' => 1, 'traffic_config' => 1, 'wechat_interaction_config' => 1, 'digital_config' => 1];
//                return true;
//            }

            $personaAgentConfig               = AiPersonaAgentConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($personaAgentConfig->isEmpty()) {
                $res['persona_agent_config'] = 0;
            }else if ($personaAgentConfig['comment_agent_id'] == 0 || $personaAgentConfig['dm_agent_id'] == 0 || $personaAgentConfig['wechat_chat_agent_id'] == 0 || $personaAgentConfig['moments_agent_id'] == 0) {
                $res['persona_agent_config'] = 0;
            } else {
                $res['persona_agent_config'] = 1;
            }
            $material                         = Material::where([['persona_id', '=', $personaId], ['user_id', '=', $userId], ['publish_mode', '=', $persona['publish_mode']]])->findOrEmpty();
            $res['material_config']           = $material->isEmpty() ? 0 : 1;
            $trafficConfig                    = AiPersonaTrafficConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($trafficConfig->isEmpty()){
                $res['traffic_config'] = 0;
            }else if (empty($trafficConfig['acquire_keywords']) || empty($trafficConfig['intercept_keywords']) || empty($trafficConfig['comment_scripts']) || empty($trafficConfig['dm_scripts'])){
                $res['traffic_config'] = 0;
            }else{
                $res['traffic_config'] = 1;
            }
            $wechatInteractionConfig          = AiPersonaWechatInteractionConfig::where([['persona_id', '=', $personaId], ['user_id', '=', $userId]])->findOrEmpty();
            if ($wechatInteractionConfig->isEmpty()){
                $res['wechat_interaction_config'] = 0;
            }else if (empty($wechatInteractionConfig['add_friend_script']) || empty($wechatInteractionConfig['comment_robot_prompt']) || empty($wechatInteractionConfig['comment_speech'])){
                $res['wechat_interaction_config'] = 0;
            }else{
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

    public static function report($params)
    {
        try {
            $model = $params['model'] ?? 0;
            if (!in_array($model, [4, 5, 6])) {
                throw new \Exception('model参数错误');
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
}
