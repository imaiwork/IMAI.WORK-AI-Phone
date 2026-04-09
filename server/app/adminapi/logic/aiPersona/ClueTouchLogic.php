<?php


namespace app\adminapi\logic\aiPersona;

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

            $personaRule = self::getPersonaRule($persona);
            $config = AiPersonaTrafficConfig::where('persona_id', $params['id'])->findOrEmpty();
            if (!$config->isEmpty()) {
                return $config->toArray();
            } else {

                $insertData = [
                    'user_id' => $persona->user_id,
                    'persona_id' => $params['id'],
                    'acquire_keywords' => $personaRule->clue_acquire_keywords ?? [],
                    'intercept_keywords' => $personaRule->clue_intercept_keywords ?? [],
                    'comment_scripts' => $personaRule->clue_comment_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'dm_scripts' => $personaRule->clue_dm_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'message_number' => $params['message_number'] ?? 15,
                    'comment_number' => $params['comment_number'] ?? 15,
                    'reply_number' => $params['reply_number'] ?? 0,
                    'content_publish_day' => $params['content_publish_day'] ?? 1,
                    'comment_publish_day' => $params['comment_publish_day'] ?? 1,
                    'exec_date' => date('Y-m-d', time()),
                    'is_first' => 1,
                    'status' => 0,
                ];
                $result = AiPersonaTrafficConfig::create($insertData);
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
            $info->save($params);
            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    private static function getPersonaRule(AiPersona $persona)
    {
        if ($persona->persona_type == 1) {
            $rule = \app\common\model\aiPersona\AiPersonaIndividual::where('persona_id', $persona->id)->findOrEmpty();
            $personality_tags = implode(',', $rule->personality_tags);
            $monetize_paths = implode(',', $rule->monetize_paths);
            $identity = implode(',', $rule->identity);
            $rule->clue_content = "\"我的昵称/网名是{$rule->nickname}，真实身份/职业是{$identity}，希望以{$personality_tags}的性格标签语气生成内容。

            我能提供的核心价值如下：
            {$rule->core_value}

            想吸引的粉丝是{$rule->target_audience}，主要变现路径：{$monetize_paths}。

            个人高光/逆袭故事：{$rule->highlight_story}。\"

            我的产品内容：{$persona->main_business}";
        } elseif ($persona->persona_type == 2) {
            $rule = \app\common\model\aiPersona\AiPersonaEnterprise::where('persona_id', $persona->id)->findOrEmpty();
            $brand_tone = implode(',', $rule->brand_tone);
            $account_goal = implode(',', $rule->account_goal);
            $spokesperson = implode(',', $rule->spokesperson);
            $rule->clue_content = "我的企业/品牌名称是{$rule->brand_name}，由{$spokesperson}代表公司出镜，希望以{$brand_tone}的品牌调性生成内容。

            主打的产品/解决方案如下：

            {$rule->main_product}

            目标客户画像是{$rule->target_customer}，账号核心目的：{$account_goal}。

            行业背书/标杆案例：{$rule->industry_case}。";
        } elseif ($persona->persona_type == 3) {
            $rule = \app\common\model\aiPersona\AiPersonaLocal::where('persona_id', $persona->id)->findOrEmpty();
            $store_atmosphere = implode(',', $rule->store_atmosphere);
            $content_preference = implode(',', $rule->content_preference);
            $spokesperson = implode(',', $rule->spokesperson);
            $rule->clue_content = "我的门店及所在商圈是{$rule->store_name}，由{$spokesperson}出镜揽客，希望以{$store_atmosphere}的门店氛围感生成内容。

            我们的招牌特色如下：

            {$rule->signature_feature}

            主要想吸引进店的客户是{$rule->target_customer}，偏好的引流内容：{$content_preference}。

            开店初衷/门店优势：{$rule->open_story}。";
        } else {
            self::setError('IP人设类型错误');
            return false;
        }
        return $rule;
    }
}
