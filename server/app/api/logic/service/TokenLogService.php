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
        } else if (in_array($scene, ['human_video_shanjian','combined_picture'])) {
            $need_token = $use_token * $num;
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
        }
        if ($userInfo['tokens'] < $need_token) {
            self::sendNotify($userInfo['id'], '用户算力不足');
            throw new \Exception('用户算力不足', 4059);
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
        $devices = \app\common\model\sv\SvDevice::where('user_id', $uid)->where('status', 'in', [1, 2])->select();
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
                'appVersion' => '2.1.1',
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
