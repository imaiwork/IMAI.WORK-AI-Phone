/** 顶层页签 */
export enum BalanceTab {
    Consume = 'consume',
    Recharge = 'recharge'
}

export interface CatMeta {
    name: string
    desc: string
    icon: string
    color: string
    bg: string
}

/**
 * 消耗记录大类的图标/配色/副标题。key 与后端 AccountLogCatEnum 对齐，
 * 大类名称以接口下发为准，这里的 name 只做兜底。
 */
export const CAT_META: Record<string, CatMeta> = {
    copy: { name: 'AI 文案', desc: '写标题、正文、仿写与提取', icon: 'edit-pen', color: '#0EA5A5', bg: '#E4F7F7' },
    human: { name: '数字人', desc: '克隆形象音色，合成音视频', icon: 'account', color: '#7B61FF', bg: '#F1EEFF' },
    mix: { name: '智能混剪', desc: '口播混剪、素材剪辑与封面', icon: 'cut', color: '#2B6EFF', bg: '#EBF2FF' },
    gen: { name: 'AI 生成视频', desc: '一句话生成视频与爆款玩法', icon: 'play-circle', color: '#E6567A', bg: '#FDE9EF' },
    image: { name: '图片与素材', desc: '出图、合图与自动找素材', icon: 'photo', color: '#F0883E', bg: '#FEF0E4' },
    phone: { name: '自动化获客', desc: '自动化获客、截流与发布', icon: 'phone', color: '#16A34A', bg: '#E6F6EC' },
    agent: { name: '智能体服务', desc: '智能体、陪练、面试与纪要', icon: 'chat', color: '#5B6CE0', bg: '#EEF0FF' },
    report: { name: '分析报告', desc: '人设分析、报表与OCR', icon: 'file-text', color: '#8B5CF6', bg: '#F3EEFF' },
    other: { name: '其他消耗', desc: '未归入以上大类的功能消耗', icon: 'more-circle', color: '#64748B', bg: '#F1F5F9' }
}
export const CAT_META_FALLBACK = CAT_META.other

/** 流水 extra 里作为逐笔标题的键，后端已放进 detail，标签里不再重复 */
export const EXTRA_BILL_ITEM_KEY = '扣费项目'

/** 充值记录时间快筛 */
export const RECHARGE_RANGES = [
    { key: 'all', label: '全部', heroLabel: '全部充值' },
    { key: 'month', label: '本月', heroLabel: '本月充值' },
    { key: 'last', label: '上月', heroLabel: '上月充值' },
    { key: 'day', label: '指定日期', heroLabel: '当日充值' }
] as const
export type RechargeRangeKey = (typeof RECHARGE_RANGES)[number]['key']

/** 流水动作：1 增加（充值 / 退费 / 补退），2 减少（扣费） */
export enum AccountLogAction {
    Inc = 1,
    Dec = 2
}

/** 充值记录查询：算力流水里的增加类（充值 / 卡密兑换 / 设备 CDK 在线支付订单） */
export const RECHARGE_LOG_QUERY = { type: 'tokens', action: AccountLogAction.Inc } as const

/** 设备 CDK 在线支付订单，金额是人民币而不是算力，不展示「剩余」 */
export const RECORD_TYPE_DEVICE_AUTH = 'device_auth'

/** 微信 / 支付宝 / 虚拟支付这类现金支付不展示「剩余」 */
export const ONLINE_PAY_KEYWORDS = ['虚拟', '微信', '支付宝']

/** 充值行图标：按备注关键字区分卡密 / 赠送 / 充值 */
export const RECHARGE_ICON_RULES: { keywords: string[]; icon: string }[] = [
    { keywords: ['卡密', '兑换', 'CDK'], icon: 'coupon' },
    { keywords: ['赠送', '奖励', '注册'], icon: 'gift' }
]
export const RECHARGE_ICON_DEFAULT = 'rmb-circle'

export interface PriceGroup {
    key: string
    name: string
    scenes: string[]
}

export const PRICE_GROUP_OTHER = 'other'

/**
 * 计费标准分组：与后台工作台（admin/src/views/workbench/index.vue）的页签及 scene 白名单一致。
 * 不在任何白名单里的计费项归到「其他」，保证价目表不丢项。
 */
export const PRICE_GROUPS: PriceGroup[] = [
    { key: 'chat', name: '聊天', scenes: ['coze_agent_chat', 'coze_workflow'] },
    {
        key: 'human',
        name: 'AI数字人',
        scenes: [
            'human_avatar_chanjing',
            'human_voice_chanjing',
            'human_video_chanjing',
            'human_avatar_shanjian',
            'human_voice_shanjian',
            'human_video_shanjian',
            'shanjian_realman_broadcast',
            'shanjian_broadcast_mixcut',
            'shanjian_news_mixcut',
            'ai_shanjian_authorized_video',
            'storyboard_video_create',
            'seedance2_480p_image2video_create',
            'seedance2_720p_image2video_create',
            'seedance2_480p_video2video_create',
            'seedance2_720p_video2video_create',
            'human_voice_minimax_hd',
            'human_voice_minimax_turbo',
            'human_audio_minimax_hd',
            'human_audio_minimax_turbo',
            'shanjian_ai_cover',
            'human_avatar_shanjian_pro',
            'play_face',
            'play_dance',
            'play_splice'
        ]
    },
    { key: 'draw', name: '美工设计', scenes: ['ai_draw_video_prompt', 'combined_picture_title', 'combined_picture'] },
    { key: 'meeting', name: '会议纪要', scenes: ['meeting'] },
    { key: 'train', name: 'AI陪练', scenes: ['lianlian'] },
    { key: 'interview', name: 'AI面试', scenes: ['interview_chat'] },
    { key: 'hot_write', name: '爆款仿写', scenes: ['video_imitation_copywriting_parse'] },
    { key: 'hotspot', name: '热点追踪', scenes: ['hotspot_insight'] },
    { key: 'knb', name: '知识库', scenes: ['knowledge_create', 'knowledge_chat', 'create_vector_knowledge', 'text_to_vector'] },
    {
        key: 'sph',
        name: 'AI视频获客',
        scenes: [
            'sph_add_wechat',
            'sph_add_friends',
            'sph_private_chat',
            'sph_search_terms',
            'sph_ocr',
            'sph_local_ocr',
            'dy_search_clue'
        ]
    },
    { key: 'matrix', name: '矩阵', scenes: ['keyword_to_title', 'keyword_to_subtitle', 'keyword_to_copywriting'] },
    {
        key: 'phone',
        name: 'AI手机',
        scenes: [
            'automation_social_media_released',
            'automation_shut_off_comments',
            'automation_shut_off_obtain',
            'automation_shut_off_private_letter',
            'automation_friends_circle_comments',
            'automation_friends_circle_released',
            'automation_friends_circle_praise',
            'automation_wechat_add_friend',
            'automation_social_media_obtain',
            'automation_social_media_nursing',
            'automation_ocr_local',
            'automation_ocr_img',
            'automation_city_exposure',
            'automation_city_touch',
            'automation_group_buy',
            'automation_precise_clues'
        ]
    },
    {
        key: PRICE_GROUP_OTHER,
        name: '其他',
        // 后台「其他」页签里的 video_imitation_copywriting_parse 已在「爆款仿写」，这里不重复列
        scenes: [
            'video_clip',
            'matrix_copywriting',
            'coze_copywriting',
            'douyin_js',
            'ai_persona_analysis',
            'ai_persona_report',
            'coze_copywriting_senior',
            'grab_image',
            'grab_video',
            'get_hot_words',
            'extract_keywords',
            'map_chat_clues',
            'images_explosion_rewrite',
            'material_slice_oss',
            'material_slice_local',
            'work_data_fetch'
        ]
    }
]
