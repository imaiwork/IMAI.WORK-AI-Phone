<?php


namespace app\common\enum\user;

use app\common\model\ModelConfig;
use app\common\service\UserDisplaySanitizer;

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
    const UM_DEC_GEO_PUBLISH = 102; // GEO 内容发布/分发扣费

    /**
     * 用户余额增加类型
     */
    const UM_INC_ADMIN = 200;
    const UM_INC_RECHARGE = 201;
    const UM_INC_GEO_PUBLISH_REFUND = 202; // GEO 发布任务取消退费

    /**
     * 用户余额（减少类型汇总）
     */
    const UM_DEC = [
        self::UM_DEC_ADMIN,
        self::UM_DEC_RECHARGE_REFUND,
        self::UM_DEC_GEO_PUBLISH,
    ];


    /**
     * 用户余额（增加类型汇总）
     */
    const UM_INC = [
        self::UM_INC_ADMIN,
        self::UM_INC_RECHARGE,
        self::UM_INC_GEO_PUBLISH_REFUND,
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
    const TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN = 5033;
    const TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN = 5034;
    const TOKENS_DEC_NEWS_MIXCUT_SHANJIAN = 5035;
    const TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD = 5036;
    const TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD = 5037;
    const TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN_ADD = 5038;
    const TOKENS_DEC_NEWS_MIXCUT_SHANJIAN_ADD = 5039;

    const TOKENS_DEC_AI_SHANJIAN_AUTHORIZED_VIDEO = 5040;
    const TOKENS_DEC_SHANJIAN_AI_COVER = 5041;
    const TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO = 5042;


    const TOKENS_DEC_HUMAN_EXT = 5060;



    const TOKENS_DEC_VIDEO_CLIP = 5101;
    // 素材库视频分割（本地 FFmpeg / 阿里云 OSS 共用，通道记录在流水 extra）
    const TOKENS_DEC_MATERIAL_SLICE = 5102;


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
    //创建微信群
    const TOKENS_DEC_WECHAT_CREATE_GROUP = 8002;

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

    const TOKENS_INC_CARDCODE_GIVE = 9105;  //卡密兑换赠送算力值
    const TOKENS_INC_MEMBER_GRANT = 9106;   //会员周期赠送算力

    const TOKENS_DEC_AI_XHS = 9104;
    const TOKENS_INC_SHANJIAN_TYPE1 = 9150;
    const TOKENS_INC_SHANJIAN_TYPE2 = 9151;
    const TOKENS_INC_SHANJIAN_TYPE3 = 9152;
    const TOKENS_INC_SHANJIAN_TYPE4 = 9153;
    const TOKENS_INC_VIDEO_IMITATION_REFUND = 9154;

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
    const TOKENS_DEC_COZE_WORKFLOW = 10101;
    const TOKENS_DEC_COZE_TEXT = 10102;
    const TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED = 10103;

    const TOKENS_DEC_MATRIX_COPYWRITING = 10104;
    const TOKENS_DEC_SORA_COPYWRITING = 10105;
    const TOKENS_DEC_SORA_VIDEO = 10106;
    const TOKENS_DEC_SORA_PRO_VIDEO = 10107;
    const TOKENS_DEC_HUMAN_AVATAR_SORA = 10108;
    const TOKENS_DEC_SORA_DRAW_AVATAR = 10109;
    const TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_480P = 10110;
    const TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_480P = 10111;
    const TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_720P = 10112;
    const TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_720P = 10113;



    // 分销代理
    const TOKENS_DEC_DISTRIBUTION_TRANSFER = 12000;
    const TOKENS_INC_DISTRIBUTION_TRANSFER = 12001;
    const TOKENS_DEC_DISTRIBUTION_CARD = 12002;
    const TOKENS_INC_DISTRIBUTION_CARD_REFUND = 12003;

    // 团队OEM
    const TOKENS_DEC_OEM_UPGRADE = 12010;        // 升级企业OEM预缴算力
    const TOKENS_INC_OEM_UPGRADE_REFUND = 12011; // 企业OEM审核未通过/解散退回预缴算力
    const TOKENS_DEC_TEAM_CONSUME = 12012;       // 成员在企业空间消费(扣团队主算力)
    const TOKENS_INC_TEAM_CONSUME_REFUND = 12013; // 成员在企业空间消费失败退回团队主算力
    const TOKENS_DEC_TEAM_ALLOCATE = 12014;        // 团队主划拨企业算力给成员(扣团队主个人算力)
    const TOKENS_INC_TEAM_ALLOCATE = 12015;        // 成员/散客获得企业算力划拨
    const TOKENS_INC_TEAM_ALLOCATE_REFUND = 12016; // 调减/回收成员企业算力退回团队主

    // 设备CDK
    const TOKENS_DEC_DEVICE_AUTH_PURCHASE = 13001;
    const TOKENS_DEC_DEVICE_AUTH_RENEW    = 13002;

    // GEO(生成式引擎优化)算力扣费 · 单价配置见 model_config 表 geo_* 场景
    const TOKENS_DEC_GEO_CONTENT = 14001;     // AI生成文章(按篇)
    const TOKENS_DEC_GEO_MONITOR = 14002;     // AI搜索监测(按 问题×引擎 次数)
    const TOKENS_DEC_GEO_TOPIC_AI = 14003;    // AI推荐话题(按次)
    const TOKENS_DEC_GEO_QUESTION_AI = 14004; // AI生成场景问题(按实际入库条数)
    const TOKENS_DEC_GEO_KNOWLEDGE = 14005;   // 知识解析导入(按次)
    const TOKENS_DEC_GEO_ANALYZE = 14006;     // 品牌分析(按次,含关键词链)
    const TOKENS_DEC_GEO_SUGGESTION = 14007;  // 优化建议(按次)
    const TOKENS_DEC_GEO_VIDEO = 14008;       // 文章转短视频(按条,失败自动退费)
    const TOKENS_DEC_GEO_MATCH_BRAND = 14009; // AI匹配品牌信息(按次,向导第一步行业/别名回填)
    const TOKENS_DEC_GEO_REPORT = 14010;      // GEO诊断报告(按次,含报告内AI优化建议)

    // 热点追踪 · TikHub 按次 / 方舟按模型
    const TOKENS_DEC_HOTSPOT_HOT_DAY = 15001;
    const TOKENS_DEC_HOTSPOT_HOT_WORDS = 15002;
    const TOKENS_DEC_HOTSPOT_INSIGHT = 15003;
    const TOKENS_DEC_HOTSPOT_ARK_CHAT = 15004;
    const TOKENS_DEC_HOTSPOT_ARK_SEARCH = 15005;

    // 自动化功能
    // 社媒平台
    const TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED = 10301;  // 自动化社媒平台发布
    const TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS = 10302;      // 自动化截流评论
    const TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN = 10303;        // 自动化截流私信
    const TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER = 10304; // 自动化截流触达
    const TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN = 10309;    // 自动化社媒平台私信接管
    const TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING = 10310;   // 自动化社媒平台自动养号

    // 朋友圈
    const TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS = 10305;    // 自动化朋友圈评论
    const TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED = 10306;   // 自动化朋友圈发布
    const TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE = 10307;     // 自动化朋友圈点赞
    const TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND = 10308;         // 自动化自动加微

    // OCR功能
    const TOKENS_DEC_AUTOMATION_OCR_LOCAL = 10311;  // 自动化获客视频号OCR
    const TOKENS_DEC_AUTOMATION_OCR_IMG = 10312;   // 自动化获客本地OCR

    // 账号Ip人设分析报告
    const TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS = 10313;   // 账号Ip人设分析报告
    const TOKENS_DEC_AI_PERSONA_ANALYSIS = 10314;   // Ip人设分析
    const TOKENS_DEC_AI_PERSONA_REPORT = 10315;   // Ip人设报告
    
    const TOKENS_DEC_AUTOMATION_CITY_EXPOSURE = 10316;   // 同城曝光任务
    const TOKENS_DEC_AUTOMATION_CITY_TOUCH = 10317;   // 同城截流获客任务
    const TOKENS_DEC_AUTOMATION_GROUP_BUY = 10318;   // 团购任务
    const TOKENS_DEC_AUTOMATION_PREISE_CLUES = 10319;   // 精准线索任务
    const TOKENS_DEC_MAP_CHAT_CLUES = 10320;   // 地图获客



    const TOKENS_DEC_NEWS_MIXCUT_TITLE = 10200;

    const TOKENS_DEC_COMBINED_PICTURE_TITLE = 10201;
    const TOKENS_DEC_COMBINED_PICTURE = 10202;


    const TOKENS_DEC_COZE_COPYWRITING = 10203;
    const TOKENS_DEC_DOUYIN_JS = 10204;
    const TOKENS_DEC_VIDEO_IMITATION = 10205;
    const TOKENS_DEC_VIDEO_IMITATION_ADD = 10206;
    const TOKENS_DEC_COZE_COPYWRITING_SENIOR = 10207;

    // 爆款视频提取视频文案
    const TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE = 10208;

    const TOKENS_DEC_COZE_HOT_WORDS = 10209;
    const TOKENS_DEC_EXTRACT_KEYWORDS = 10210;



    //分镜混剪
    const TOKENS_DEC_STORYBOARD_VIDEO = 10300;
    //图文爆款仿写
    const TOKENS_DEC_IMAGES_EXPLOSION_REWRITE = 10321;
    //爆款复刻小红书图文改写（按张）
    const TOKENS_DEC_VIDEO_IMITATION_XHS_IMAGE = 10330;

    //minimax
    const TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD = 10400;
    const TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO = 10401;
    const TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD = 10402;
    const TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO = 10403;

    //grab
      const TOKENS_DEC_GRAB_IMAGE = 10501;
      const TOKENS_DEC_GRAB_VIDEO = 10502;

    /** AI 生图（draw 统一链路，按 models_cost 计费） */
    const TOKENS_DEC_DRAW_IMAGE = 10601;
    /** AI 生视频（draw 统一链路，按 models_cost 计费；预扣后按实际秒数结算） */
    const TOKENS_DEC_DRAW_VIDEO = 10602;




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
        self::TOKENS_DEC_WECHAT_CREATE_GROUP,
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
        self::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
        self::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
        self::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
        self::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD,
        self::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD,
        self::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN_ADD,
        self::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN_ADD,
        self::TOKENS_DEC_AI_SHANJIAN_AUTHORIZED_VIDEO,
        self::TOKENS_DEC_SHANJIAN_AI_COVER,
        self::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO,



        self::TOKENS_DEC_HUMAN_EXT,
        self::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE,
        self::TOKENS_DEC_SPH_ADD_WECHAT,
        self::TOKENS_DEC_SPH_ADD_FRIENDS,
        self::TOKENS_DEC_SPH_PRIVATE_CHAT,
        self::TOKENS_DEC_SPH_SEARCH_TERMS,
        self::TOKENS_DEC_AI_REPLY_LIKE,
        self::TOKENS_DEC_VIDEO_CLIP,
        self::TOKENS_DEC_MATERIAL_SLICE,
        self::TOKENS_DEC_TEXT_TO_VECTOR,
        self::TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE,
        self::TOKENS_DEC_SPH_OCR,
        self::TOKENS_DEC_SPH_LOCAL_OCR,
        self::TOKENS_DEC_COZE_AGENT_CHAT,
        self::TOKENS_DEC_COZE_WORKFLOW,
        self::TOKENS_DEC_COZE_TEXT,
        self::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED,
        self::TOKENS_DEC_MATRIX_COPYWRITING,
        self::TOKENS_DEC_NEWS_MIXCUT_TITLE,
        self::TOKENS_DEC_COMBINED_PICTURE_TITLE,
        self::TOKENS_DEC_COMBINED_PICTURE,
        self::TOKENS_DEC_COZE_COPYWRITING,
        self::TOKENS_DEC_COZE_COPYWRITING_SENIOR,
        self::TOKENS_DEC_DOUYIN_JS,



        self::TOKENS_DEC_SORA_VIDEO,
        self::TOKENS_DEC_SORA_PRO_VIDEO,
        self::TOKENS_DEC_SORA_COPYWRITING,
        self::TOKENS_DEC_HUMAN_AVATAR_SORA,
        self::TOKENS_DEC_SORA_DRAW_AVATAR,
        self::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_480P,
        self::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_480P,
        self::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_720P,
        self::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_720P,

        self::TOKENS_DEC_STORYBOARD_VIDEO,
        self::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
        self::TOKENS_DEC_VIDEO_IMITATION_XHS_IMAGE,

            // 自动化功能
        self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED,
        self::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS,
        self::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN,
        self::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER,
        self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN,
        self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING,
        self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS,
        self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED,
        self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE,
        self::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND,
        self::TOKENS_DEC_AUTOMATION_OCR_LOCAL,
        self::TOKENS_DEC_AUTOMATION_OCR_IMG,
        self::TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS,
        self::TOKENS_DEC_AI_PERSONA_ANALYSIS,
        self::TOKENS_DEC_AI_PERSONA_REPORT,
        self::TOKENS_DEC_AUTOMATION_GROUP_BUY,
        self::TOKENS_DEC_AUTOMATION_CITY_EXPOSURE,
        self::TOKENS_DEC_AUTOMATION_CITY_TOUCH,
        self::TOKENS_DEC_AUTOMATION_PREISE_CLUES,
        self::TOKENS_DEC_MAP_CHAT_CLUES,

        // 分销代理
        self::TOKENS_DEC_DISTRIBUTION_TRANSFER,
        self::TOKENS_DEC_DISTRIBUTION_CARD,

        // 团队OEM
        self::TOKENS_DEC_OEM_UPGRADE,
        self::TOKENS_DEC_TEAM_CONSUME,
        self::TOKENS_DEC_TEAM_ALLOCATE,

        // 设备CDK
        self::TOKENS_DEC_DEVICE_AUTH_PURCHASE,
        self::TOKENS_DEC_DEVICE_AUTH_RENEW,

        // GEO(生成式引擎优化)
        self::TOKENS_DEC_GEO_CONTENT,
        self::TOKENS_DEC_GEO_MONITOR,
        self::TOKENS_DEC_GEO_TOPIC_AI,
        self::TOKENS_DEC_GEO_QUESTION_AI,
        self::TOKENS_DEC_GEO_KNOWLEDGE,
        self::TOKENS_DEC_GEO_ANALYZE,
        self::TOKENS_DEC_GEO_SUGGESTION,
        self::TOKENS_DEC_GEO_VIDEO,
        self::TOKENS_DEC_GEO_MATCH_BRAND,
        self::TOKENS_DEC_GEO_REPORT,

        // 热点追踪
        self::TOKENS_DEC_HOTSPOT_HOT_DAY,
        self::TOKENS_DEC_HOTSPOT_HOT_WORDS,
        self::TOKENS_DEC_HOTSPOT_INSIGHT,
        self::TOKENS_DEC_HOTSPOT_ARK_CHAT,
        self::TOKENS_DEC_HOTSPOT_ARK_SEARCH,

        // 视频复刻
        self::TOKENS_DEC_VIDEO_IMITATION,
        self::TOKENS_DEC_VIDEO_IMITATION_ADD,
        self::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
        self::TOKENS_DEC_COZE_HOT_WORDS,
        self::TOKENS_DEC_EXTRACT_KEYWORDS,


        // minimax
        self::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD,
        self::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO,
        self::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD,
        self::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO,
        //grab
        self::TOKENS_DEC_GRAB_IMAGE,
        self::TOKENS_DEC_GRAB_VIDEO,
        // draw 统一生图/生视频
        self::TOKENS_DEC_DRAW_IMAGE,
        self::TOKENS_DEC_DRAW_VIDEO,
    ];


    /**
     * 用户算力（增加类型汇总）
     */
    const TOKENS_INC = [
        self::TOKENS_INC_ADMIN,
        self::TOKENS_INC_RECHARGE,
        self::TOKENS_INC_HUMAN,
        self::TOKENS_INC_REGISTER,
        self::TOKENS_INC_SHANJIAN_TYPE1,
        self::TOKENS_INC_SHANJIAN_TYPE2,
        self::TOKENS_INC_SHANJIAN_TYPE3,
        self::TOKENS_INC_SHANJIAN_TYPE4,
        self::TOKENS_INC_CARDCODE_GIVE,
        self::TOKENS_INC_MEMBER_GRANT,
        self::TOKENS_INC_DISTRIBUTION_TRANSFER,
        self::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
        self::TOKENS_INC_VIDEO_IMITATION_REFUND,
        self::TOKENS_INC_OEM_UPGRADE_REFUND,
        self::TOKENS_INC_TEAM_CONSUME_REFUND,
        self::TOKENS_INC_TEAM_ALLOCATE,
        self::TOKENS_INC_TEAM_ALLOCATE_REFUND,
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
            self::UM_DEC_ADMIN => '平台减少余额',
            self::UM_INC_ADMIN => '平台增加余额',
            self::UM_INC_RECHARGE => '充值增加余额',
            self::UM_DEC_RECHARGE_REFUND => '充值订单退款减少余额',
            self::UM_DEC_GEO_PUBLISH => 'GEO内容发布扣费',
            self::UM_INC_GEO_PUBLISH_REFUND => 'GEO发布取消退费',


            self::TOKENS_INC_REGISTER => '注册增加算力',
            self::TOKENS_INC_MEMBER_GRANT => '会员周期赠送算力',
            self::TOKENS_INC_HUMAN => '数字人视频合成退费',
            self::TOKENS_INC_SHANJIAN_TYPE1 => '克隆数字人混剪剪辑视频预扣费超额扣费退费',
            self::TOKENS_INC_SHANJIAN_TYPE2 => '真人口播混剪视频预扣费超额扣费退费',
            self::TOKENS_INC_SHANJIAN_TYPE3 => '素材混剪视频预扣费超额扣费退费',
            self::TOKENS_INC_SHANJIAN_TYPE4 => '新闻体混剪视频预扣费超额扣费退费',
            self::TOKENS_INC_VIDEO_IMITATION_REFUND => '爆款视频复刻预扣费超额扣费退费',
            self::TOKENS_INC_ADMIN => '平台增加算力',
            self::TOKENS_INC_RECHARGE => '购买算力加油包',
            self::TOKENS_DEC_ADMIN => '平台减少算力',
            self::TOKENS_DEC_RECHARGE_REFUND => '充值订单退款减少算力',
            self::TOKENS_DEC_COMMON_CHAT => '通用聊天减少算力',
            self::TOKENS_DEC_TEXT_TO_IMAGE => '文生图减少算力',
            self::TOKENS_DEC_TEXT_TO_POSTERIMAGE => '文生海报图减少算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_IMAGE => '即梦文生图减少算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE => '即梦文生海报图减少算力',
            self::TOKENS_DEC_IMAGE_TO_IMAGE => '图生图减少算力',
            self::TOKENS_DEC_GOODS_IMAGE => '商品图减少算力',
            self::TOKENS_DEC_MODEL_IMAGE => '模特图减少算力',
            self::TOKENS_DEC_MEETING => '会议减少算力',
            self::TOKENS_DEC_MIND_MAP => '思维导图减少算力',
            self::TOKENS_DEC_SCENE_CHAT => '通用聊天减少算力',
            self::TOKENS_DEC_OPENAI_CHAT => '通用聊天减少算力',
            self::TOKENS_DEC_GEMINI_CHAT => '通用聊天减少算力',
            self::TOKENS_DEC_IMAGE_PROMPT => '生图文案减少算力',
            self::TOKENS_DEC_VOLC_VIDEO_PROMPT => '生成视频文案减少算力',
            self::TOKENS_DEC_EXPIRE => 'token 加油包过期',

            self::TOKENS_DEC_HUMAN_AVATAR => '数字人形象 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO => '数字人音频 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_VOICE => '数字人音色 - 标准版减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO => '数字人视频 - 标准版减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_PRO => '数字人形象 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_PRO => '数字人音频 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_PRO => '数字人音色 - 极致版减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_PRO => '数字人视频 - 极致版减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_YM => '数字人形象 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_YM => '数字人音频 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_YM => '数字人音色 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_YM => '数字人视频 - 优秘V5减少算力',
            self::TOKENS_DEC_HUMAN_PROMPT => '数字人口播文案提示词减少算力',
            self::TOKENS_DEC_HUMAN_COPYWRITING => '数字人口播文案减少算力',


            self::TOKENS_DEC_AI_LIANLIAN => 'AI陪练减少算力',
            self::TOKENS_DEC_AI_WECHAT => 'AI微信减少算力',
            self::TOKENS_DEC_WECHAT_CREATE_GROUP => '创建微信群减少算力',
            self::TOKENS_DEC_AI_XHS => 'AI小红书减少算力',
                // self::TOKENS_DEC_AUDIO_TEXT             => '音频转文字减少算力',
            self::TOKENS_DEC_AI_RESUME => 'AI简历分析减少算力',
            self::TOKENS_DEC_AI_MARK => 'AI面试评分减少算力',
            self::TOKENS_DEC_AI_INTERVIEW_CHAT => 'AI面试聊天减少算力',
            self::TOKENS_DEC_HUMAN_AVATAR_YMT => '数字人形象 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_AUDIO_YMT => '数字人音频 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_YMT => '数字人音色 - 优秘V7-减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_YMT => '数字人视频 - 优秘V7-减少算力',

            self::TOKENS_DEC_KNOWLEDGE_RETRIEVE => '知识库检索减少算力',
            self::TOKENS_DEC_KNOWLEDGE_CREATE => '知识库创建减少算力',
            self::TOKENS_DEC_KNOWLEDGE_CHAT => '知识库聊天减少算力',
            self::TOKENS_DEC_GEO_CONTENT => 'GEO生成文章减少算力',
            self::TOKENS_DEC_GEO_MONITOR => 'GEO搜索监测减少算力',
            self::TOKENS_DEC_GEO_TOPIC_AI => 'GEO推荐话题减少算力',
            self::TOKENS_DEC_GEO_QUESTION_AI => 'GEO生成场景问题减少算力',
            self::TOKENS_DEC_GEO_KNOWLEDGE => 'GEO知识解析减少算力',
            self::TOKENS_DEC_GEO_ANALYZE => 'GEO品牌分析减少算力',
            self::TOKENS_DEC_GEO_SUGGESTION => 'GEO优化建议减少算力',
            self::TOKENS_DEC_GEO_VIDEO => 'GEO文章转短视频减少算力',
            self::TOKENS_DEC_GEO_MATCH_BRAND => 'GEO匹配品牌信息减少算力',
            self::TOKENS_DEC_GEO_REPORT => 'GEO诊断报告减少算力',
            self::TOKENS_DEC_HOTSPOT_HOT_DAY => '热点追踪热榜拉取减少算力',
            self::TOKENS_DEC_HOTSPOT_HOT_WORDS => '热点追踪热搜词拉取减少算力',
            self::TOKENS_DEC_HOTSPOT_INSIGHT => '热点追踪话题洞察减少算力',
            self::TOKENS_DEC_HOTSPOT_ARK_CHAT => '热点追踪方舟对话减少算力',
            self::TOKENS_DEC_HOTSPOT_ARK_SEARCH => '热点追踪方舟联网检索减少算力',

            self::KEYWORD_TO_TITLE => 'Ai标题生成费用扣除减少算力',
            self::KEYWORD_TO_SUBTITLE => 'Ai正文描述生成费用扣除减少算力',
            self::KEYWORD_TO_COPYWRITING => 'Ai文案生成费用扣除减少算力',

            self::TOKENS_INC_CARDCODE_GIVE => '卡密兑换增加算力',
            self::TOKENS_DEC_VOLC_TEXT_TO_VIDEO => '即梦文生视频减少算力',
            self::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO => '即梦图生视频减少算力',
            self::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE => 'Doubao模型图生图减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE => 'Doubao模型文生图减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO => 'Seedance 1.0 pro模型文生视频减少算力',
            self::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO => 'Seedance 1.0 pro模型图生视频减少算力',
            self::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE => 'Doubao模型文生海报图减少算力',

            self::TOKENS_DEC_HUMAN_AVATAR_CHANJING => self::digitalHumanV1TokensDesc('形象'),
            self::TOKENS_DEC_HUMAN_VOICE_CHANJING => self::digitalHumanV1TokensDesc('音色'),
            self::TOKENS_DEC_HUMAN_AUDIO_CHANJING => self::digitalHumanV1TokensDesc('音频'),
            self::TOKENS_DEC_HUMAN_VIDEO_CHANJING => self::digitalHumanV1TokensDesc('视频'),

            self::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN => '口播混剪形象克隆扣费减少算力',
            self::TOKENS_DEC_HUMAN_VOICE_SHANJIAN => '极速版音色克隆扣费减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN => '口播混剪视频克隆扣费减少算力',
            self::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN => '真人口播混剪扣费减少算力',
            self::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN => '素材混剪视频扣费减少算力',
            self::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN => '新闻体视频扣费减少算力',
            self::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD => '克隆数字人混剪剪辑视频预扣费补足费用补扣',
            self::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD => '真人口播混剪视频预扣费补足费用补扣',
            self::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN_ADD => '素材混剪视频预扣费补足费用补扣',
            self::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN_ADD => '新闻体混剪视频预扣费补足费用补扣',
            self::TOKENS_DEC_AI_SHANJIAN_AUTHORIZED_VIDEO => 'AI自动生成授权形象视频扣费减少算力',
            self::TOKENS_DEC_SHANJIAN_AI_COVER => '使用调用AI自动生成视频封面图预扣费减少算力',
            self::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO => '口播混剪专业数字人克隆扣费减少算力',





            self::TOKENS_DEC_HUMAN_EXT => '数字人视频合成补扣',


            self::TOKENS_DEC_SPH_ADD_WECHAT => '视频号获客减少算力',
            self::TOKENS_DEC_SPH_ADD_FRIENDS => '视频号获客加好友话术自动去重减少算力',
            self::TOKENS_DEC_SPH_PRIVATE_CHAT => '视频号获客主动私聊话术去重减少算力',
            self::TOKENS_DEC_SPH_SEARCH_TERMS => '视频号获客检索关键词减少算力',

            self::TOKENS_DEC_AI_REPLY_LIKE => 'AI朋友圈评论点赞减少算力',
            self::TOKENS_DEC_VIDEO_CLIP => '视频剪辑减少算力',
            self::TOKENS_DEC_MATERIAL_SLICE => '素材分割减少算力',
            self::TOKENS_DEC_TEXT_TO_VECTOR => '文本转向量减少算力',
            self::TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE => '创建向量知识库减少算力',
            self::TOKENS_DEC_SPH_OCR => '视频号OCR减少算力',
            self::TOKENS_DEC_SPH_LOCAL_OCR => '本地OCR减少算力',
            self::TOKENS_DEC_COZE_AGENT_CHAT => 'Coze智能体聊天减少算力',
            self::TOKENS_DEC_COZE_WORKFLOW => 'Coze智能体工作流减少算力',
            self::TOKENS_DEC_COZE_TEXT => '口播混剪视频文案生成减少算力',
            self::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED => 'Coze发布内容生成减少算力',
            self::TOKENS_DEC_MATRIX_COPYWRITING => '矩阵文案生成减少算力',
            self::TOKENS_DEC_NEWS_MIXCUT_TITLE => '新闻体标题生成减少算力',
            self::TOKENS_DEC_COMBINED_PICTURE_TITLE => '小红书图片合成封面标题内容生成减少算力',
            self::TOKENS_DEC_COMBINED_PICTURE => '小红书图片自动合成减少算力',
            self::TOKENS_DEC_COZE_COPYWRITING => 'Coze智能体文案生成减少算力',
            self::TOKENS_DEC_COZE_COPYWRITING_SENIOR => 'Coze智能体文案(高级版)生成减少算力',
            self::TOKENS_DEC_STORYBOARD_VIDEO => '分镜生成视频任务减少算力',
            self::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE => '图文爆款提取减少算力',
            self::TOKENS_DEC_VIDEO_IMITATION_XHS_IMAGE => '爆款复刻小红书图文改写减少算力',
            self::TOKENS_DEC_DOUYIN_JS => '【抖音】扫码发布减少算力',
            self::TOKENS_DEC_VIDEO_IMITATION => '视频仿写文案生成减少算力',
            self::TOKENS_DEC_VIDEO_IMITATION_ADD => '爆款视频复刻预扣费补足费用补扣',
            self::TOKENS_DEC_SORA_VIDEO => '一句话生成视频减少算力',
            self::TOKENS_DEC_SORA_PRO_VIDEO => '一句话生成视频(pro)减少算力',
            self::TOKENS_DEC_SORA_COPYWRITING => '一句话生成视频AI优化文案减少算力',
            self::TOKENS_DEC_HUMAN_AVATAR_SORA => '一句话生成视频角色创建减少算力',
            self::TOKENS_DEC_SORA_DRAW_AVATAR => '一句话生成视频真人角色转绘减少算力',
            self::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_480P => 'Seedance 2.0模型480p参考图文生成视频减少算力',
            self::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_480P => 'Seedance 2.0模型480p参考视频生成视频减少算力',
            self::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_720P => 'Seedance 2.0模型720p参考图文生成视频减少算力',
            self::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_720P => 'Seedance 2.0模型720p参考视频生成视频减少算力',

                // 自动化功能描述
            self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED => '自动化社媒平台发布减少算力',
            self::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS => '自动化截流评论减少算力',
            self::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN => '自动化截流私信减少算力',
            self::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER => '自动化截流触达减少算力',
            self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN => '自动化社媒平台私信接管减少算力',
            self::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING => '自动化社媒平台自动养号减少算力',
            self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS => '自动化朋友圈评论减少算力',
            self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED => '自动化朋友圈发布减少算力',
            self::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE => '自动化朋友圈点赞减少算力',
            self::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND => '自动化自动加微减少算力',
            self::TOKENS_DEC_AUTOMATION_OCR_LOCAL => '自动化获客视频号OCR减少算力',
            self::TOKENS_DEC_AUTOMATION_OCR_IMG => '自动化获客本地OCR减少算力',
            self::TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS => '自动化获客账号Ip人设分析报告减少算力',
            self::TOKENS_DEC_AI_PERSONA_ANALYSIS => 'IP人设分析减少算力',
            self::TOKENS_DEC_AI_PERSONA_REPORT => 'IP人设报告减少算力',
            self::TOKENS_DEC_AUTOMATION_GROUP_BUY => '团购任务减少算力',
            self::TOKENS_DEC_AUTOMATION_CITY_EXPOSURE => '同城曝光任务减少算力',
            self::TOKENS_DEC_AUTOMATION_CITY_TOUCH => '同城视频任务减少算力',
            self::TOKENS_DEC_AUTOMATION_PREISE_CLUES => '精准获客任务减少算力',
            self::TOKENS_DEC_MAP_CHAT_CLUES => '地图获客减少算力',

            // 分销代理
            self::TOKENS_DEC_DISTRIBUTION_TRANSFER => '分销代理转赠下级减少算力',
            self::TOKENS_INC_DISTRIBUTION_TRANSFER => '上级代理转赠增加算力',
            self::TOKENS_DEC_DISTRIBUTION_CARD => '分销代理卡密制卡扣除算力',
            self::TOKENS_INC_DISTRIBUTION_CARD_REFUND => '分销代理卡密删除退回算力',

            // 团队OEM
            self::TOKENS_DEC_OEM_UPGRADE => '升级企业OEM预缴算力',
            self::TOKENS_INC_OEM_UPGRADE_REFUND => '企业OEM退回预缴算力',
            self::TOKENS_DEC_TEAM_CONSUME => '团队成员消费扣除团队算力',
            self::TOKENS_INC_TEAM_CONSUME_REFUND => '团队成员消费失败退回团队算力',
            self::TOKENS_DEC_TEAM_ALLOCATE => '划拨企业算力给成员',
            self::TOKENS_INC_TEAM_ALLOCATE => '获得企业算力划拨',
            self::TOKENS_INC_TEAM_ALLOCATE_REFUND => '回收成员企业算力退回',

            self::TOKENS_DEC_DEVICE_AUTH_PURCHASE => '购买设备CDK减少算力',
            self::TOKENS_DEC_DEVICE_AUTH_RENEW    => '设备授权续费减少算力',

            // 爆款视频仿写
            self::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE => '爆款仿写提取视频文案扣除算力',
            self::TOKENS_DEC_COZE_HOT_WORDS => '热点视频搜索词提取减少算力',
            self::TOKENS_DEC_EXTRACT_KEYWORDS => 'AI素材关键词提取扣除算力',

            // minimax
            self::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD => 'Hd音色克隆扣除算力',
            self::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO => 'Turbo音色克隆扣除算力',
            self::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD => 'Hd音频生成扣除算力',
            self::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO => 'Turbo音频生成扣除算力',
            //grab
            self::TOKENS_DEC_GRAB_IMAGE => 'AI自动找素材图片扣费',
            self::TOKENS_DEC_GRAB_VIDEO => 'AI自动找素材视频扣费',
            // draw 统一生图/生视频
            self::TOKENS_DEC_DRAW_IMAGE => 'AI生图减少算力',
            self::TOKENS_DEC_DRAW_VIDEO => 'AI生视频减少算力',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$changeType] ?? '';
    }

    private static function digitalHumanV1TokensDesc(string $type): string
    {
        $modelName = UserDisplaySanitizer::digitalHumanModelNameByVersion(7, true) ?: '数字人';
        return '数字人' . $type . ' - ' . $modelName . '-减少算力';
    }


    /**
     * @notes 获取用户余额类型描述
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 10:08
     */
    public static function getUserMoneyChangeTypeDesc()
    {
        $UMChangeType = self::getUserMoneyChangeType();
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
        $UMChangeType = self::getUserTokensChangeType();
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
     * 团队非业务消耗(转账类):划拨/回收/OEM/制卡/后台调整等
     * —— 成员「累计消耗」与消耗明细合计统一用此口径排除,保证两处数字一致
     */
    public static function teamTransferTypes(): array
    {
        return [
            self::TOKENS_DEC_TEAM_ALLOCATE,
            self::TOKENS_INC_TEAM_ALLOCATE,
            self::TOKENS_INC_TEAM_ALLOCATE_REFUND,
            self::TOKENS_DEC_TEAM_CONSUME,
            self::TOKENS_INC_TEAM_CONSUME_REFUND,
            self::TOKENS_DEC_OEM_UPGRADE,
            self::TOKENS_INC_OEM_UPGRADE_REFUND,
            self::TOKENS_DEC_DISTRIBUTION_TRANSFER,
            self::TOKENS_INC_DISTRIBUTION_TRANSFER,
            self::TOKENS_DEC_DISTRIBUTION_CARD,
            self::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
            // 后台调账属平台操作,不属于团队业务消耗/流转
            self::TOKENS_DEC_ADMIN,
            self::TOKENS_INC_ADMIN,
            self::TOKENS_DEC_RECHARGE_REFUND,
            self::TOKENS_DEC_EXPIRE,
        ];
    }

    /**
     * @notes 业务预扣结余退费等「专用 INC 类型」(非 DEC 同码退回)
     * 数字人/混剪等成功后多退少补会记这些类型,须计入消耗明细净消耗
     */
    public static function teamBizRefundIncTypes(): array
    {
        return [
            self::TOKENS_INC_HUMAN,
            self::TOKENS_INC_SHANJIAN_TYPE1,
            self::TOKENS_INC_SHANJIAN_TYPE2,
            self::TOKENS_INC_SHANJIAN_TYPE3,
            self::TOKENS_INC_SHANJIAN_TYPE4,
            self::TOKENS_INC_VIDEO_IMITATION_REFUND,
        ];
    }

    /**
     * @notes 消耗明细可抵扣净消耗的业务 INC change_type
     * = 同 DEC 码失败/超额退回 + 专用退费 INC(9100/915x 等)
     */
    public static function teamConsumeIncTypes(): array
    {
        $transfer = self::teamTransferTypes();
        return array_values(array_unique(array_merge(
            array_values(array_diff(self::TOKENS_DEC, $transfer)),
            self::teamBizRefundIncTypes()
        )));
    }

    /**
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
        // draw 统一生图/生视频不走 model_config，但仍需写入流水 task_id
        if (in_array($code, [self::TOKENS_DEC_DRAW_IMAGE, self::TOKENS_DEC_DRAW_VIDEO], true)) {
            return true;
        }
        $config = ModelConfig::where('code', $code)->findOrEmpty();
        return $config->isEmpty() ? false : true;
    }
}
