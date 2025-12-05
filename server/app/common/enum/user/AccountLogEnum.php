<?php


namespace app\common\enum\user;

use app\common\model\ModelConfig;

/**
 * 用户账户流水变动表枚举
 * Class AccountLogEnum
 * @package app\common\enum
 */
class AccountLogEnum
{
    /**
     * 变动类型命名规则：对象_动作_简洁描述
     * 动作 DEC-减少 INC-增加
     * 对象 UM-用户余额
     */

    /**
     * 变动对象
     * UM 用户余额(user_money)
     */
    const UM = 1;

    /**
     * 变动对象
     * TOKENS 用户算力(user_money)
     */
    const TOKENS = 2;

    /**
     * 动作
     * INC 增加
     * DEC 减少
     */
    const INC = 1;
    const DEC = 2;


    /**
     * 用户余额减少类型
     */
    const UM_DEC_ADMIN = 100;
    const UM_DEC_RECHARGE_REFUND = 101;

    /**
     * 用户余额增加类型
     */
    const UM_INC_ADMIN = 200;
    const UM_INC_RECHARGE = 201;

    /**
     * 用户余额（减少类型汇总）
     */
    const UM_DEC = [
        self::UM_DEC_ADMIN,
        self::UM_DEC_RECHARGE_REFUND,
    ];


    /**
     * 用户余额（增加类型汇总）
     */
    const UM_INC = [
        self::UM_INC_ADMIN,
        self::UM_INC_RECHARGE,
    ];


    /**
     * 用户算力减少类型
     */
    const TOKENS_DEC_ADMIN = 9001;
    const TOKENS_DEC_RECHARGE_REFUND = 9002;
    const TOKENS_DEC_EXPIRE = 9003;

    //通用聊天
    const TOKENS_DEC_COMMON_CHAT = 1001;
    //场景聊天
    const TOKENS_DEC_SCENE_CHAT = 1002;
    //openai聊天
    const TOKENS_DEC_OPENAI_CHAT = 1003;
    const TOKENS_DEC_GEMINI_CHAT = 1004;

    //关键词
    const KEYWORD_TO_TITLE = 1101;
    const KEYWORD_TO_SUBTITLE = 1102;
    const KEYWORD_TO_COPYWRITING = 1103;

    //文生图
    const TOKENS_DEC_TEXT_TO_IMAGE = 2001;
    //图生图
    const TOKENS_DEC_IMAGE_TO_IMAGE = 2002;
    //商品图
    const TOKENS_DEC_GOODS_IMAGE = 2003;
    //模特图
    const TOKENS_DEC_MODEL_IMAGE = 2004;
    //模特图
    const TOKENS_DEC_IMAGE_PROMPT = 2005;

    const TOKENS_DEC_VOLC_TEXT_TO_IMAGE = 2006;
    const TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE = 2007;

    //文生视频
    const TOKENS_DEC_VOLC_TEXT_TO_VIDEO = 2008;
    //图生视频
    const TOKENS_DEC_VOLC_IMAGE_TO_VIDEO = 2009;
    const TOKENS_DEC_TEXT_TO_POSTERIMAGE = 2010;
    const TOKENS_DEC_VOLC_VIDEO_PROMPT = 2011;

    const TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE = 2012;
    const TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE = 2013;
    const TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO = 2014;
    const TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO = 2015;
    const TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE = 2016;

    const TOKENS_DEC_SPH_ADD_WECHAT = 2017;
    const TOKENS_DEC_AI_REPLY_LIKE = 2018;


    //会议纪要
    const TOKENS_DEC_MEETING = 3001;

    //思维导图
    const TOKENS_DEC_MIND_MAP = 4001;

    //数字人口播文案提示词
    const TOKENS_DEC_HUMAN_PROMPT = 5001;
    //数字人 - 标准版
    const TOKENS_DEC_HUMAN_AVATAR = 5002;
    const TOKENS_DEC_HUMAN_VOICE = 5003;
    const TOKENS_DEC_HUMAN_AUDIO = 5004;
    const TOKENS_DEC_HUMAN_VIDEO = 5005;


    //数字人 - 极致版
    const TOKENS_DEC_HUMAN_AVATAR_PRO = 5006;
    const TOKENS_DEC_HUMAN_VOICE_PRO = 5007;
    const TOKENS_DEC_HUMAN_AUDIO_PRO = 5008;
    const TOKENS_DEC_HUMAN_VIDEO_PRO = 5009;

    //数字人高级版 优蜜
    const TOKENS_DEC_HUMAN_AVATAR_YM = 5010;
    const TOKENS_DEC_HUMAN_VOICE_YM = 5011;
    const TOKENS_DEC_HUMAN_AUDIO_YM = 5012;
    const TOKENS_DEC_HUMAN_VIDEO_YM = 5013;

    //数字人通道六 优蜜
    const TOKENS_DEC_HUMAN_AVATAR_YMT = 5014;
    const TOKENS_DEC_HUMAN_VOICE_YMT = 5015;
    const TOKENS_DEC_HUMAN_AUDIO_YMT = 5016;
    const TOKENS_DEC_HUMAN_VIDEO_YMT = 5017;

    const TOKENS_DEC_HUMAN_COPYWRITING = 5018;

    //数字人通道七 
    const TOKENS_DEC_HUMAN_AVATAR_CHANJING = 5019;
    const TOKENS_DEC_HUMAN_VOICE_CHANJING = 5020;
    const TOKENS_DEC_HUMAN_AUDIO_CHANJING = 5021;
    const TOKENS_DEC_HUMAN_VIDEO_CHANJING = 5022;


    const TOKENS_DEC_HUMAN_AVATAR_SHANJIAN = 5030;
    const TOKENS_DEC_HUMAN_VOICE_SHANJIAN = 5031;
    const TOKENS_DEC_HUMAN_VIDEO_SHANJIAN = 5032;

    const TOKENS_DEC_HUMAN_EXT = 5060;



    const TOKENS_DEC_VIDEO_CLIP = 5101;


    //AI陪练
    const TOKENS_DEC_AI_LIANLIAN = 6001;

    //AI面试
    //简历分析
    const TOKENS_DEC_AI_RESUME = 7001;
    //面试评分
    const TOKENS_DEC_AI_MARK = 7002;
    //面试聊天
    const TOKENS_DEC_AI_INTERVIEW_CHAT = 7003;

    //AI微信
    const TOKENS_DEC_AI_WECHAT = 8001;

    // 知识库
    //检索
    const TOKENS_DEC_KNOWLEDGE_RETRIEVE = 9004;
    const TOKENS_DEC_KNOWLEDGE_CREATE = 9005;
    const TOKENS_DEC_KNOWLEDGE_CHAT = 9006;

    /**
     * 用户算力增加类型
     */
    const TOKENS_INC_HUMAN = 9100;
    const TOKENS_INC_REGISTER = 9101;
    const TOKENS_INC_ADMIN = 9102;
    const TOKENS_INC_RECHARGE = 9103;

    const TOKENS_INC_CARDCODE_GIVE  = 9105;  //卡密兑换赠送算力值

    const TOKENS_DEC_AI_XHS = 9104;


     /**
     * 短视频
     */
    const TOKENS_DEC_SPH_ADD_FRIENDS = 10001;
    const TOKENS_DEC_SPH_PRIVATE_CHAT = 10002;  
    const TOKENS_DEC_SPH_SEARCH_TERMS = 10003;

    /**
     * 向量知识库
     */
    const TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE = 11001;
    const TOKENS_DEC_TEXT_TO_VECTOR = 11002;

    const TOKENS_DEC_SPH_OCR = 11003;
    const TOKENS_DEC_SPH_LOCAL_OCR = 11004;

    const TOKENS_DEC_COZE_AGENT_CHAT = 10100;
    const TOKENS_DEC_COZE_WORKFLOW= 10101;
    const TOKENS_DEC_COZE_TEXT= 10102;
    const TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED= 10103;

    const TOKENS_DEC_MATRIX_COPYWRITING = 10104;


    /**
     * 用户算力（减少类型汇总）
     */
    const TOKENS_DEC = [
        self::TOKENS_DEC_ADMIN,
        self::TOKENS_DEC_RECHARGE_REFUND,
        self::TOKENS_DEC_COMMON_CHAT,
        self::TOKENS_DEC_TEXT_TO_IMAGE,
        self::TOKENS_DEC_TEXT_TO_POSTERIMAGE,
        self::TOKENS_DEC_VOLC_TEXT_TO_IMAGE,
        self::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE,
        self::TOKENS_DEC_IMAGE_TO_IMAGE,
        self::TOKENS_DEC_GOODS_IMAGE,
        self::TOKENS_DEC_MODEL_IMAGE,
        self::TOKENS_DEC_MEETING,
        self::TOKENS_DEC_MIND_MAP,
        self::TOKENS_DEC_SCENE_CHAT,
        self::TOKENS_DEC_OPENAI_CHAT,
        self::TOKENS_DEC_GEMINI_CHAT,
        self::TOKENS_DEC_IMAGE_PROMPT,
        self::TOKENS_DEC_EXPIRE,
        self::TOKENS_DEC_HUMAN_VIDEO,
        self::TOKENS_DEC_HUMAN_AUDIO,
        self::TOKENS_DEC_HUMAN_VOICE,
        self::TOKENS_DEC_HUMAN_AVATAR,
        self::TOKENS_DEC_HUMAN_VIDEO_PRO,
        self::TOKENS_DEC_HUMAN_AUDIO_PRO,
        self::TOKENS_DEC_HUMAN_VOICE_PRO,
        self::TOKENS_DEC_HUMAN_AVATAR_PRO,
        self::TOKENS_DEC_HUMAN_PROMPT,
        self::TOKENS_DEC_HUMAN_COPYWRITING,
        self::TOKENS_DEC_AI_LIANLIAN,
        self::TOKENS_DEC_AI_WECHAT,
        self::TOKENS_DEC_AI_XHS,
        self::TOKENS_DEC_AI_RESUME,
        self::TOKENS_DEC_AI_MARK,
        self::TOKENS_DEC_AI_INTERVIEW_CHAT,
        self::TOKENS_DEC_HUMAN_AVATAR_YM,
        self::TOKENS_DEC_HUMAN_VIDEO_YM,
        self::TOKENS_DEC_HUMAN_AUDIO_YM,
        self::TOKENS_DEC_HUMAN_VOICE_YM,
        self::TOKENS_DEC_HUMAN_AVATAR_YMT,
        self::TOKENS_DEC_HUMAN_VIDEO_YMT,
        self::TOKENS_DEC_HUMAN_AUDIO_YMT,
        self::TOKENS_DEC_HUMAN_VOICE_YMT,
        self::TOKENS_DEC_KNOWLEDGE_RETRIEVE,
        self::TOKENS_DEC_KNOWLEDGE_CREATE,
        self::TOKENS_DEC_KNOWLEDGE_CHAT,
        self::KEYWORD_TO_TITLE,
        self::KEYWORD_TO_SUBTITLE,
        self::KEYWORD_TO_COPYWRITING,
        self::TOKENS_DEC_VOLC_TEXT_TO_VIDEO,
        self::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO,
        self::TOKENS_DEC_VOLC_VIDEO_PROMPT,
        self::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE,
        self::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE,
        self::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO,
        self::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO,
        self::TOKENS_DEC_HUMAN_AVATAR_CHANJING,
        self::TOKENS_DEC_HUMAN_VOICE_CHANJING,
        self::TOKENS_DEC_HUMAN_AUDIO_CHANJING,
        self::TOKENS_DEC_HUMAN_VIDEO_CHANJING,
        self::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN,
        self::TOKENS_DEC_HUMAN_VOICE_SHANJIAN,
        self::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
        self::TOKENS_DEC_HUMAN_EXT,
        self::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE,
        self::TOKENS_DEC_SPH_ADD_WECHAT,
        self::TOKENS_DEC_SPH_ADD_FRIENDS,
        self::TOKENS_DEC_SPH_PRIVATE_CHAT,
        self::TOKENS_DEC_SPH_SEARCH_TERMS,
        self::TOKENS_DEC_AI_REPLY_LIKE,
        self::TOKENS_DEC_VIDEO_CLIP,
        self::TOKENS_DEC_TEXT_TO_VECTOR,
        self::TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE,
        self::TOKENS_DEC_SPH_OCR,
        self::TOKENS_DEC_SPH_LOCAL_OCR,
        self::TOKENS_DEC_COZE_AGENT_CHAT,
        self::TOKENS_DEC_COZE_WORKFLOW,
        self::TOKENS_DEC_COZE_TEXT,
        self::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED,
        self::TOKENS_DEC_MATRIX_COPYWRITING,
    ];


    /**
     * 用户算力（增加类型汇总）
     */
    const TOKENS_INC = [
        self::TOKENS_INC_ADMIN,
        self::TOKENS_INC_RECHARGE,
        self::TOKENS_INC_HUMAN,
        self::TOKENS_INC_CARDCODE_GIVE,
    ];


    /**
     * @notes 动作描述
     * @param $action
     * @param false $flag
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 10:07
     */
    public static function getActionDesc($action, $flag = false)
    {
        $desc = [
            self::DEC => '减少',
            self::INC => '增加',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$action] ?? '';
    }


    /**
     * @notes 变动类型描述
     * @param $changeType
     * @param false $flag
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 10:07
     */
    public static function getChangeTypeDesc($changeType, $flag = false)
    {
        $desc = [
            self::UM_DEC_ADMIN           => '平台减少余额',
            self::UM_INC_ADMIN           => '平台增加余额',
            self::UM_INC_RECHARGE        => '充值增加余额',
            self::UM_DEC_RECHARGE_REFUND => '充值订单退款减少余额',


            self::TOKENS_INC_REGISTER               => '注册增加算力',
            self::TOKENS_INC_HUMAN                  => '数字人视频合成退费',
            self::TOKENS_INC_ADMIN                  => '平台增加算力',
            self::TOKENS_INC_RECHARGE               => '购买算力加油包',
            self::TOKENS_DEC_ADMIN                  => '平台减少算力',
            self::TOKENS_DEC_RECHARGE_REFUND        => '充值订单退款减少算力',
            self::TOKENS_DEC_COMMON_CHAT            => '通用聊天减少算力',
            self::TOKENS_DEC_TEXT_TO_IMAGE          => '文生图减少算力',
            self::TOKENS_DEC_TEXT_TO_POSTERIMAGE    => '文生海报图减少算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_IMAGE     => '即梦文生图减少算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE => '即梦文生海报图减少算力',
            self::TOKENS_DEC_IMAGE_TO_IMAGE         => '图生图减少算力',
            self::TOKENS_DEC_GOODS_IMAGE            => '商品图减少算力',
            self::TOKENS_DEC_MODEL_IMAGE            => '模特图减少算力',
            self::TOKENS_DEC_MEETING                => '会议减少算力',
            self::TOKENS_DEC_MIND_MAP               => '思维导图减少算力',
            self::TOKENS_DEC_SCENE_CHAT             => '场景聊天减少算力',
            self::TOKENS_DEC_OPENAI_CHAT            => 'OpenAI聊天减少算力',
            self::TOKENS_DEC_GEMINI_CHAT            => 'Jemini聊天减少算力',
            self::TOKENS_DEC_IMAGE_PROMPT           => '生图文案减少算力',
            self::TOKENS_DEC_VOLC_VIDEO_PROMPT      => '生成视频文案减少算力',
            self::TOKENS_DEC_EXPIRE                 => 'token 加油包过期',

            self::TOKENS_DEC_HUMAN_AVATAR           => '数字人形象 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO            => '数字人音频 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_VOICE            => '数字人音色 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO            => '数字人视频 - 标准版减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_PRO       => '数字人形象 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_PRO        => '数字人音频 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_PRO        => '数字人音色 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_PRO        => '数字人视频 - 极致版减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_YM       => '数字人形象 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_YM        => '数字人音频 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_YM        => '数字人音色 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_YM        => '数字人视频 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_PROMPT          => '数字人口播文案提示词减少算力',
            self::TOKENS_DEC_HUMAN_COPYWRITING     => '数字人口播文案减少算力',


            self::TOKENS_DEC_AI_LIANLIAN           => 'AI陪练减少算力',
            self::TOKENS_DEC_AI_WECHAT             => 'AI微信减少算力',
            self::TOKENS_DEC_AI_XHS                => 'AI小红书减少算力',
            // self::TOKENS_DEC_AUDIO_TEXT             => '音频转文字减少算力',
            self::TOKENS_DEC_AI_RESUME             => 'AI简历分析减少算力',
            self::TOKENS_DEC_AI_MARK               => 'AI面试评分减少算力',
            self::TOKENS_DEC_AI_INTERVIEW_CHAT     => 'AI面试聊天减少算力',
            self::TOKENS_DEC_HUMAN_AVATAR_YMT      => '数字人形象 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_YMT       => '数字人音频 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_YMT       => '数字人音色 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_YMT       => '数字人视频 - 优秘V7-减少算力',

            self::TOKENS_DEC_KNOWLEDGE_RETRIEVE    => '知识库检索减少算力',
            self::TOKENS_DEC_KNOWLEDGE_CREATE      => '知识库创建减少算力',
            self::TOKENS_DEC_KNOWLEDGE_CHAT        => '知识库聊天减少算力',

            self::KEYWORD_TO_TITLE                 => 'Ai标题生成费用扣除减少算力',
            self::KEYWORD_TO_SUBTITLE              => 'Ai正文描述生成费用扣除减少算力',
            self::KEYWORD_TO_COPYWRITING           => 'Ai文案生成费用扣除减少算力',

            self::TOKENS_INC_CARDCODE_GIVE         => '卡密兑换增加算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_VIDEO    => '即梦文生视频减少算力',
            self::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO   => '即梦图生视频减少算力',
            self::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE => 'Doubao模型图生图减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE  => 'Doubao模型文生图减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO  => 'Seedance 1.0 pro模型文生视频减少算力',
            self::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO => 'Seedance 1.0 pro模型图生视频减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE => 'Doubao模型文生海报图减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_CHANJING => '数字人形象 - 蝉镜-减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_CHANJING  => '数字人音色 - 蝉镜-减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_CHANJING  => '数字人音频 - 蝉镜-减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_CHANJING  => '数字人视频 - 蝉镜-减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN => '口播混剪形象克隆扣费减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_SHANJIAN  => '口播混剪音色克隆扣费减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN  => '口播混剪视频克隆扣费减少算力',
            self::TOKENS_DEC_HUMAN_EXT             => '数字人视频合成补扣',


            self::TOKENS_DEC_SPH_ADD_WECHAT        => '视频号获客减少算力',
            self::TOKENS_DEC_SPH_ADD_FRIENDS       => '视频号获客加好友话术自动去重减少算力',
            self::TOKENS_DEC_SPH_PRIVATE_CHAT      => '视频号获客主动私聊话术去重减少算力',
            self::TOKENS_DEC_SPH_SEARCH_TERMS      => '视频号获客检索关键词减少算力',
            
            self::TOKENS_DEC_AI_REPLY_LIKE         => 'AI朋友圈评论点赞减少算力',
            self::TOKENS_DEC_VIDEO_CLIP            => '视频剪辑减少算力',
            self::TOKENS_DEC_TEXT_TO_VECTOR        => '文本转向量减少算力',
            self::TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE => '创建向量知识库减少算力',
            self::TOKENS_DEC_SPH_OCR               => '视频号OCR减少算力',
            self::TOKENS_DEC_SPH_LOCAL_OCR         => '本地OCR减少算力',
            self::TOKENS_DEC_COZE_AGENT_CHAT       => 'Coze智能体聊天减少算力',
            self::TOKENS_DEC_COZE_WORKFLOW         => 'Coze智能体工作流减少算力',
            self::TOKENS_DEC_COZE_TEXT             => '口播混剪视频文案生成减少算力',
            self::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED => 'Coze发布内容生成减少算力',
            self::TOKENS_DEC_MATRIX_COPYWRITING => '矩阵文案生成减少算力',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$changeType] ?? '';
    }


    /**
     * @notes 获取用户余额类型描述
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function getUserMoneyChangeTypeDesc()
    {
        $UMChangeType   = self::getUserMoneyChangeType();
        $changeTypeDesc = self::getChangeTypeDesc('', true);
        return array_filter($changeTypeDesc, function ($key) use ($UMChangeType) {
            return in_array($key, $UMChangeType);
        }, ARRAY_FILTER_USE_KEY);
    }


    /**
     * @notes 获取用户算力类型描述
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function getUserTokensChangeTypeDesc()
    {
        $UMChangeType   = self::getUserTokensChangeType();
        $changeTypeDesc = self::getChangeTypeDesc('', true);
        return array_filter($changeTypeDesc, function ($key) use ($UMChangeType) {
            return in_array($key, $UMChangeType);
        }, ARRAY_FILTER_USE_KEY);
    }


    /**
     * @notes 获取用户余额变动类型
     * @return int[]
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function getUserMoneyChangeType(): array
    {
        return array_merge(self::UM_DEC, self::UM_INC);
    }

    /**
     * @notes 获取用户算力变动类型
     * @return int[]
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function getUserTokensChangeType(): array
    {
        return array_merge(self::TOKENS_DEC, self::TOKENS_INC);
    }


    /**
     * @notes 获取变动对象
     * @param $changeType
     * @return false
     * @author 段誉
     * @date 2023/2/23 10:10
     */
    public static function getChangeObject($changeType)
    {
        // 用户余额
        $um = self::getUserMoneyChangeType();
        if (in_array($changeType, $um)) {
            return self::UM;
        }

        $tokens = self::getUserTokensChangeType();
        if (in_array($changeType, $tokens)) {
            return self::TOKENS;
        }

        // 其他...

        return false;
    }

    /**
     * @notes 检查code是否存在
     * @param int $code
     * @return bool
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function checkCode(int $code): bool
    {
        $config = ModelConfig::where('code', $code)->findOrEmpty();
        return $config->isEmpty() ? false : true;
    }
}
