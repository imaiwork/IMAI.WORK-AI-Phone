<?php


namespace app\api\logic\service;


use app\common\model\ModelConfig;
use app\common\model\user\User;

/**
 * TokenLogService
 * @desc 用户token操作
 * @author dagouzi
 */
class TokenLogService
{

    /**
     * 获取任务需要的算力
     * @param string $scene
     * @return float
     * @author L
     * @data 2024/7/31 16:34
     */
    public static function getTypeScore(string $scene = ""): float
    {
        return ModelConfig::where('scene', $scene)->value('score', 0);
    }

    /**
     * @desc 检查用户token是否足够
     * @param int $uid
     * @param string $scene
     * @return float
     * @date 2024/7/29 16:15
     * @throws \Exception
     * @author dagouzi
     */
    public static function checkToken(int $uid, string $scene = "", $num = 0): float
    {
        $use_token   = self::getTypeScore($scene);
        $userInfo = User::findOrEmpty($uid)->toArray();
        if (empty($userInfo)) {
            throw new \Exception('用户查询失败');
        }
        // 团队被停用 / 成员到期 → 拦截(消费前校验)
        \app\common\service\TeamMemberService::assertActive($uid);
        // AI聊天 - 1算力
        // AI美工 
        // - 文生图、图生图  - 40算力
        // - 商品图、模特图  - 80算力
        // 数字人
        // - 形象、音色、音频 - 20算力
        // - 合成 - 50算力
        // - 快速 - 80算力

        // AI陪练  - 100算力
        // AI会议纪要 - 50算力
        $need_token = 1;
        if (in_array($scene, ['text_to_image', 'image_to_image'])) {

            $need_token = 40;
        } else if (in_array($scene, ['goods_image', 'model_image'])) {

            $need_token = 80;
        } else if (in_array($scene, ['meeting'])) {

            $need_token = 50;
        } else if (in_array($scene, ['lianlian'])) {

            $need_token = 20;
        }  else if (in_array($scene, ['human_voice_ym'])) {
            $need_token = 1100;
        }else if (in_array($scene, ['human_voice_ymt'])) {
            $need_token = 1800;
        } else if (in_array($scene, ['human_avatar', 'human_audio', 'human_voice'])) {

            $need_token = 20;
        } else if (in_array($scene, ['human_video_shanjian', 'shanjian_realman_broadcast', 'shanjian_broadcast_mixcut', 'shanjian_news_mixcut', 'combined_picture'])) {
            $need_token = $use_token * $num;
        } else if (in_array($scene, ['human_voice_chanjing'])) {
            $need_token = $use_token;
        }else if (in_array($scene, ['human_video'])) {
            $need_token = 50;
        } else if(in_array($scene, ['knowledge_create','create_vector_knowledge'])) {
            $need_token = 20;
        } else if(in_array($scene, ['knowledge_retrieve'])) {
            $need_token = 10;
        } else if(in_array($scene, ['knowledge_chat'])) {
            $need_token = 10;
        } else if(in_array($scene, ['keyword_to_title','keyword_to_subtitle','keyword_to_copywriting'])) {
            $need_token = 10;
        }else if(in_array($scene, ['volc_text_to_video','volc_image_to_video'])) {
            $need_token = 325;
        }else if(in_array($scene, ['doubao_txt_to_video','doubao_image_to_video'])) {
            $need_token = 100;
        }else if(in_array($scene, ['volc_img_to_img_v2','volc_txt_to_img_v2', 'volc_txt_to_posterimg_v2'])) {
            $need_token = 30;
        }else if(in_array($scene, ['sora_video_create','seedance2_480p_image2video_create','seedance2_480p_video2video_create','seedance2_720p_image2video_create','seedance2_720p_video2video_create'])) {
            $need_token = $use_token * $num;
        }else if(in_array($scene, ['storyboard_video_create','human_audio_minimax_hd','human_audio_minimax_turbo'])) {
            $need_token = $use_token * $num;
        }else if(in_array($scene, ['ai_persona_analysis','ai_persona_report','human_voice_minimax_hd','human_voice_minimax_turbo', 'images_explosion_rewrite', 'human_avatar_shanjian_pro'])) {
            $need_token = $use_token;
        }
        // 配置了单价的场景:预检至少按单价,避免「有1点算力就放行、执行后再按单价扣失败」
        // 自动化等大量场景此前落在默认 need_token=1,与真实扣费脱节
        if ($use_token > 0) {
            $need_token = max((float)$need_token, (float)$use_token);
        }
        if ($num > 0 && $use_token > 0) {
            $need_token = max((float)$need_token, (float)$use_token * (float)$num);
        }
        // 企业空间内成员可用算力=企业钱包;团队主/个人=个人算力
        $spendable = \app\common\service\TeamBillingService::spendableTokens((int)$userInfo['id']);
        if ($spendable < $need_token) {
            self::sendNotify($userInfo['id'], '用户算力不足;当前算力:' . $spendable . ';需要算力:' . $need_token . ';任务场景:' . $scene);
            $msg = \app\common\service\TeamBillingService::resolveSpender((int)$userInfo['id']) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }
        //
        //        AccountLogLogic::add(
        //            $userInfo['id'],
        //            AccountLogEnum::TOKENS_DEC_MEETING_REFUND,
        //            AccountLogEnum::INC,
        //            $use_token,
        //            "",
        //            $tokenNumber[$type]['desc']
        //        );

        return $use_token;
    }

    private static function sendNotify(int $uid, string $msg )
    {
        $devices = \app\common\model\sv\SvDevice::where('user_id', $uid)->select();
        foreach ($devices as $device) {
            $payload = array(
                'type' => \app\common\enum\DeviceEnum::TASK_TOKEN_NOTIFY, // 接管任务启动
                'appType' => 0,
                'content' => json_encode(array(
                    'deviceId' => $device->device_code,
                    'code' => \app\common\enum\DeviceEnum::TASK_TOKEN_ERROR,
                    'msg' => $msg
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $device->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );

            \think\facade\Log::channel('device')->info(json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $channel = "device.{$device->device_code}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => json_encode($payload)
            ]);
        }
    }
}
