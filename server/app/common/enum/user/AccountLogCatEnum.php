<?php

namespace app\common\enum\user;

/**
 * 小程序算力账单大类：与前端 pricing.js 的 key 对齐。
 * 分类是展示分组，不是定价属性——写死在此，不进 la_model_config。
 */
class AccountLogCatEnum
{
    public const COPY = 'copy';
    public const HUMAN = 'human';
    public const MIX = 'mix';
    public const GEN = 'gen';
    public const IMAGE = 'image';
    public const PHONE = 'phone';
    public const AGENT = 'agent';
    public const REPORT = 'report';
    public const OTHER = 'other';

    /**
     * 八大类（始终下发）+ other（仅当天有数据才下发）
     * @return list<array{key:string,name:string}>
     */
    public static function cats(): array
    {
        return [
            ['key' => self::COPY, 'name' => 'AI 文案'],
            ['key' => self::HUMAN, 'name' => '数字人'],
            ['key' => self::MIX, 'name' => '智能混剪'],
            ['key' => self::GEN, 'name' => 'AI 生成视频'],
            ['key' => self::IMAGE, 'name' => '图片与素材'],
            ['key' => self::PHONE, 'name' => '自动化获客'],
            ['key' => self::AGENT, 'name' => '智能体服务'],
            ['key' => self::REPORT, 'name' => '分析报告'],
            ['key' => self::OTHER, 'name' => '其他消耗'],
        ];
    }

    /** 首屏固定展示的八大类，不含 other */
    public static function primaryKeys(): array
    {
        return [
            self::COPY, self::HUMAN, self::MIX, self::GEN,
            self::IMAGE, self::PHONE, self::AGENT, self::REPORT,
        ];
    }

    public static function isKnownCat(string $key): bool
    {
        foreach (self::cats() as $cat) {
            if ($cat['key'] === $key) {
                return true;
            }
        }
        return false;
    }

    public static function nameOf(string $key): string
    {
        foreach (self::cats() as $cat) {
            if ($cat['key'] === $key) {
                return $cat['name'];
            }
        }
        return '';
    }

    /**
     * 充值/划拨/调账等非功能消耗，本接口不展示
     * @return int[]
     */
    public static function excludeTypes(): array
    {
        return array_values(array_unique(array_merge(
            AccountLogEnum::teamTransferTypes(),
            [
                AccountLogEnum::TOKENS_INC_REGISTER,
                AccountLogEnum::TOKENS_INC_MEMBER_GRANT,
                AccountLogEnum::TOKENS_INC_RECHARGE,
                AccountLogEnum::TOKENS_INC_CARDCODE_GIVE,
                AccountLogEnum::TOKENS_DEC_DEVICE_AUTH_PURCHASE,
                AccountLogEnum::TOKENS_DEC_DEVICE_AUTH_RENEW,
            ]
        )));
    }

    public static function catOf(int $changeType): string
    {
        return self::typeToCat()[$changeType] ?? self::OTHER;
    }

    /**
     * change_type → 大类。退费 INC 与对应 DEC 同 key。
     * 未登记的业务扣费走 other，避免账单丢钱。
     * @return array<int,string>
     */
    public static function typeToCat(): array
    {
        return self::assign(self::COPY, [
            AccountLogEnum::KEYWORD_TO_TITLE,
            AccountLogEnum::KEYWORD_TO_SUBTITLE,
            AccountLogEnum::KEYWORD_TO_COPYWRITING,
            AccountLogEnum::TOKENS_DEC_HUMAN_COPYWRITING,
            AccountLogEnum::TOKENS_DEC_HUMAN_PROMPT,
            AccountLogEnum::TOKENS_DEC_IMAGE_PROMPT,
            AccountLogEnum::TOKENS_DEC_MIND_MAP,
            AccountLogEnum::TOKENS_DEC_VOLC_VIDEO_PROMPT,
            AccountLogEnum::TOKENS_DEC_AI_XHS,
            AccountLogEnum::TOKENS_DEC_TEXT_TO_VECTOR,
            AccountLogEnum::TOKENS_DEC_COZE_TEXT,
            AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING,
            AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING_SENIOR,
            AccountLogEnum::TOKENS_DEC_MATRIX_COPYWRITING,
            AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_TITLE,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_ADD,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
            AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND,
            AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS,
            AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS,
            AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
        ]) + self::assign(self::HUMAN, [
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE,
            AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_PRO,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_PRO,
            AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_PRO,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YM,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM,
            AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YMT,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT,
            AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CHANJING,
            AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_CHANJING,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING,
            AccountLogEnum::TOKENS_DEC_HUMAN_EXT,
            AccountLogEnum::TOKENS_INC_HUMAN,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO,
        ]) + self::assign(self::MIX, [
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN_S3,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN,
            AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD,
            AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD,
            AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN_ADD,
            AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN_ADD,
            AccountLogEnum::TOKENS_DEC_AI_SHANJIAN_AUTHORIZED_VIDEO,
            AccountLogEnum::TOKENS_DEC_SHANJIAN_AI_COVER,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO,
            AccountLogEnum::TOKENS_DEC_VIDEO_CLIP,
            AccountLogEnum::TOKENS_DEC_MATERIAL_SLICE,
            AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO,
            AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE1,
            AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE2,
            AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE3,
            AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE4,
        ]) + self::assign(self::GEN, [
            AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_SORA_VIDEO,
            AccountLogEnum::TOKENS_DEC_SORA_PRO_VIDEO,
            AccountLogEnum::TOKENS_DEC_SORA_COPYWRITING,
            AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA,
            AccountLogEnum::TOKENS_DEC_SORA_DRAW_AVATAR,
            AccountLogEnum::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_480P,
            AccountLogEnum::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_480P,
            AccountLogEnum::TOKENS_DEC_SEEDANCE_IMAGE2VIDEO_720P,
            AccountLogEnum::TOKENS_DEC_SEEDANCE_VIDEO2VIDEO_720P,
            AccountLogEnum::TOKENS_DEC_PLAY_SPLICE,
            AccountLogEnum::TOKENS_DEC_PLAY_FACE,
            AccountLogEnum::TOKENS_DEC_PLAY_DANCE,
            AccountLogEnum::TOKENS_DEC_DRAW_VIDEO,
        ]) + self::assign(self::IMAGE, [
            AccountLogEnum::TOKENS_DEC_TEXT_TO_IMAGE,
            AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE,
            AccountLogEnum::TOKENS_DEC_GOODS_IMAGE,
            AccountLogEnum::TOKENS_DEC_MODEL_IMAGE,
            AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_IMAGE,
            AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE,
            AccountLogEnum::TOKENS_DEC_TEXT_TO_POSTERIMAGE,
            AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE,
            AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE,
            AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE,
            AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE_TITLE,
            AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE,
            AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_XHS_IMAGE,
            AccountLogEnum::TOKENS_DEC_GRAB_IMAGE,
            AccountLogEnum::TOKENS_DEC_GRAB_VIDEO,
            AccountLogEnum::TOKENS_DEC_DRAW_IMAGE,
        ]) + self::assign(self::PHONE, [
            AccountLogEnum::TOKENS_DEC_WECHAT_CREATE_GROUP,
            AccountLogEnum::TOKENS_DEC_SPH_ADD_WECHAT,
            AccountLogEnum::TOKENS_DEC_SPH_ADD_FRIENDS,
            AccountLogEnum::TOKENS_DEC_SPH_PRIVATE_CHAT,
            AccountLogEnum::TOKENS_DEC_SPH_SEARCH_TERMS,
            AccountLogEnum::TOKENS_DEC_AI_REPLY_LIKE,
            AccountLogEnum::TOKENS_DEC_DOUYIN_JS,
            AccountLogEnum::TOKENS_DEC_DY_SEARCH_CLUE,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_LOCAL,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_IMG,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_GROUP_BUY,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_CITY_EXPOSURE,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_CITY_TOUCH,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_PREISE_CLUES,
            AccountLogEnum::TOKENS_DEC_MAP_CHAT_CLUES,
        ]) + self::assign(self::AGENT, [
            AccountLogEnum::TOKENS_DEC_COMMON_CHAT,
            AccountLogEnum::TOKENS_DEC_SCENE_CHAT,
            AccountLogEnum::TOKENS_DEC_OPENAI_CHAT,
            AccountLogEnum::TOKENS_DEC_GEMINI_CHAT,
            AccountLogEnum::TOKENS_DEC_MEETING,
            AccountLogEnum::TOKENS_DEC_AI_LIANLIAN,
            AccountLogEnum::TOKENS_DEC_AI_RESUME,
            AccountLogEnum::TOKENS_DEC_AI_MARK,
            AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT,
            AccountLogEnum::TOKENS_DEC_AI_WECHAT,
            AccountLogEnum::TOKENS_DEC_KNOWLEDGE_RETRIEVE,
            AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CREATE,
            AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT,
            AccountLogEnum::TOKENS_DEC_CREATE_VECTOR_KNOWLEDGE,
            AccountLogEnum::TOKENS_DEC_COZE_AGENT_CHAT,
            AccountLogEnum::TOKENS_DEC_COZE_WORKFLOW,
            AccountLogEnum::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED,
        ]) + self::assign(self::REPORT, [
            AccountLogEnum::TOKENS_DEC_SPH_OCR,
            AccountLogEnum::TOKENS_DEC_SPH_LOCAL_OCR,
            AccountLogEnum::TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS,
            AccountLogEnum::TOKENS_DEC_AI_PERSONA_ANALYSIS,
            AccountLogEnum::TOKENS_DEC_AI_PERSONA_REPORT,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_DAY,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_WORDS,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_INSIGHT,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_CHAT,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_SEARCH,
            AccountLogEnum::TOKENS_DEC_WORK_DATA_FETCH,
        ]);
    }

    /**
     * @param int[] $types
     * @return array<int,string>
     */
    private static function assign(string $cat, array $types): array
    {
        $out = [];
        foreach ($types as $type) {
            $out[(int)$type] = $cat;
        }
        return $out;
    }
}
