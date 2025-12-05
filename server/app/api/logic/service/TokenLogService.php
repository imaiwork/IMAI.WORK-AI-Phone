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
    public static function checkToken(int $uid, string $scene = ""): float
    {
        $use_token   = self::getTypeScore($scene);
        $userInfo = User::findOrEmpty($uid)->toArray();
        if (empty($userInfo)) {
            throw new \Exception('用户查询失败11');
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
        } else if (in_array($scene, ['human_video'])) {

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
            throw new \Exception('用户算力不足');
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
}
