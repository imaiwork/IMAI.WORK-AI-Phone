<?php


namespace app\adminapi\logic\aiPersona;

use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;

use app\common\model\sv\SvAddWechatRecord;

/**
 * IP人设互动互动管家任务管理逻辑
 * Class InteractiveLogic
 * @package app\adminapi\logic\aiPersona
 */
class InteractiveLogic extends BaseLogic
{
    public static function detail($params): array
    {
        ini_set('max_execution_time', 0);
        try {
            $config = AiPersona::where('id', $params['id'])->findOrEmpty();
            if ($config->isEmpty()) {
                throw new \Exception('设备自动化配置不存在');
            }

            $personaRule = self::getPersonaRule($config);

            $find = AiPersonaWechatInteractionConfig::where('persona_id', $params['id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->clue_count = self::getClues($config->user_id);
                return $find->toArray();
            } else {
                $insertData = [
                    'user_id' => $config->user_id,
                    'persona_id' => $params['id'],
                    'add_friend_enabled' => $params['add_friend_enabled'] ?? 1,
                    'add_friend_source' => $params['add_friend_source'] ?? 1,
                    'add_friend_script' => implode("\n", $personaRule->wechat_add_friend_script ?? ''),
                    'is_like' => $params['is_like'] ?? 0,
                    'is_comment' => $params['is_comment'] ?? 0,
                    'comment_method' => $params['comment_method'] ?? 1,
                    'comment_robot_prompt' => $params['comment_robot_prompt'] ?? AiPersonaWechatInteractionConfig::getCommentRobotPrompt(),
                    'robot_params' => [
                        'model' => 'gpt-4o',
                        'temperature' => 0.3,
                        'top_p' => 0.8,
                        'presence_penalty' => 0,
                        'frequency_penalty' => 0,
                        'max_tokens' => 4096,
                        'context_num' => 0,
                        'stream' => false,
                    ],
                    'number' => $params['number'] ?? 15,
                    'comment_speech' => $personaRule->wechat_comment_speech,
                    'status' => 0,
                    'exec_time' =>  [],
                    'exec_date' => $params['exec_date'] ?? date('Y-m-d', time()),
                ];
                $config = AiPersonaWechatInteractionConfig::create($insertData);
                $config->clue_count = self::getClues($config->user_id);
                return $config->toArray();
            }
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

            $info->save($params);
            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    private static function getClues($user_id)
    {
        $count = SvAddWechatRecord::where('user_id', $user_id)->where('status', 4)->group('reg_wechat')->count();
        return $count;
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
