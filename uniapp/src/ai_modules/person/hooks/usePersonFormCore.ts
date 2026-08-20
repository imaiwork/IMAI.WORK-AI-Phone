import config from "@/config";
import { PersonTypeEnum } from "@/ai_modules/person/enums";

export interface IndividualForm {
    nickname: string;
    identity: string[];
    personality_tags: string[];
    core_value: string;
    target_audience: string;
    monetize_paths: string[];
}

export interface EnterpriseForm {
    brand_name: string;
    spokesperson: string[];
    brand_tone: string[];
    main_product: string;
    target_customer: string;
    account_goal: string[];
}

export interface LocalForm {
    store_name: string;
    store_atmosphere: string[];
    spokesperson: string[];
    signature_feature: string;
    target_customer: string;
    content_preference: string[];
}

export interface CreatePersonPayload {
    persona_name: string;
    persona_desc: string;
    persona_type: PersonTypeEnum;
    avatar_url: string;
    main_business: string;
    target_pain_points: string;
    conversion_hook: string;
    is_shopping_cart?: 0 | 1;
    goods_name?: string;
    is_store_position?: 0 | 1;
    store_position?: string;
    individual?: IndividualForm;
    enterprise?: EnterpriseForm;
    local?: LocalForm;
}

export interface PersonFormData {
    persona_name: string;
    persona_desc: string;
    persona_type: PersonTypeEnum;
    avatar_url: string;
    content_focus: string;
    account_style: string;
    hot_words: any;
    nickname: string;
    identity: string[];
    personality_tags: string[];
    core_value: string;
    target_audience: string;
    monetize_paths: string[];
    brand_name: string;
    spokesperson: string[];
    brand_tone: string[];
    main_product: string;
    target_customer: string;
    account_goal: string[];
    store_name: string;
    store_spokesperson: string[];
    store_atmosphere: string[];
    signature_feature: string;
    content_preference: string[];
    main_business: string;
    target_pain_points: string;
    conversion_hook: string;
    is_shopping_cart: 0 | 1;
    goods_name: string;
    global_option: Record<string, any> | null;
}

export interface BasicFormData {
    ai_name: string;
    intro: string;
    persona_type: PersonTypeEnum;
    industry_direction: string[];
    content_direction: string;
    target_audience: string;
    account_style: string;
    city_position: string;
    auto_location: boolean;
    action_goal: string;
}

export const OPTIONS_CONFIG = {
    identity: ["创业者", "职场精英", "全职宝妈", "自由职业", "学生党", "行业专家"],
    personality_tags: ["热情开朗", "专业严谨", "幽默风趣", "成熟稳重", "接地气"],
    monetize_paths: ["知识付费", "直播带货", "商单广告", "私域咨询", "纯分享积累"],
    spokesperson: ["老板", "技术大牛", "金牌销售", "产品经理", "官方虚拟人", "客服代表"],
    brand_tone: ["专业严谨", "成熟稳重", "靠谱放心", "创新进取", "亲切务实"],
    account_goal: ["留资获客", "品牌宣发", "展会引流", "客户教育", "招商加盟"],
    store_spokesperson: ["老板", "漂亮老板娘", "搞笑店长", "探店顾客"],
    store_atmosphere: ["市井烟火气", "网红打卡地", "高端奢华", "排队王", "温馨解压", "接地气"],
    content_preference: ["团购口播", "沉浸式制作", "探店剧情", "老板日常", "客户真实反馈"],
};

export const INDUSTRY_DIRECTION_OPTIONS = [
    { name: "美容护肤", emoji: "💄" },
    { name: "探店", emoji: "🍜" },
    { name: "房产", emoji: "🏠" },
    { name: "健身", emoji: "💪" },
    { name: "育儿", emoji: "👶" },
    { name: "宠物", emoji: "🐾" },
    { name: "餐饮美食", emoji: "🍳" },
    { name: "旅游出行", emoji: "✈️" },
    { name: "时尚穿搭", emoji: "👗" },
    { name: "数码科技", emoji: "📱" },
    { name: "教育培训", emoji: "📚" },
    { name: "电商带货", emoji: "📦" },
    { name: "个人成长", emoji: "🌱" },
    { name: "家居装修", emoji: "🛋️" },
    { name: "本地服务", emoji: "📍" },
    { name: "企业品牌", emoji: "💼" },
    { name: "医疗健康", emoji: "🏥" },
    { name: "二手闲置", emoji: "♻️" },
];

export const ACCOUNT_STYLE_OPTIONS = [
    {
        name: "温柔亲切",
        emoji: "🌸",
        desc: "文案温柔走心，私信回复像闺蜜，拉近距离让用户愿意主动聊",
    },
    {
        name: "专业可靠",
        emoji: "🎓",
        desc: "文案有逻辑有数据，私信回复专业解答，提升信任感和成单率",
    },
    {
        name: "活泼开朗",
        emoji: "🌈",
        desc: "文案轻快有活力，私信回复热情积极，容易吸引年轻用户互动",
    },
    {
        name: "幽默风趣",
        emoji: "😄",
        desc: "文案有梗有段子，私信回复轻松好玩，完播率高、评论区容易火",
    },
    {
        name: "接地气",
        emoji: "🤝",
        desc: "文案说大白话，私信回复直来直去，真实感强、共鸣多易转发",
    },
    {
        name: "知性优雅",
        emoji: "✨",
        desc: "文案有品位有格调，私信回复得体高雅，适合高端定位和品牌合作",
    },
];

export const ACTION_GOAL_OPTIONS = [
    {
        name: "关注我",
        emoji: "👥",
        desc: "结尾引导「点关注，下期不迷路」，持续积累账号粉丝",
    },
    {
        name: "咨询我",
        emoji: "💬",
        desc: "结尾引导「有问题私信我」，把观众变成精准客户线索",
    },
    {
        name: "买产品",
        emoji: "🛍️",
        desc: "结尾引导「戳购物车」或「评论区有链接」，直接促成购买",
    },
    {
        name: "到店",
        emoji: "📍",
        desc: "结尾引导「附近的朋友来店里找我」，吸引用户线下到访",
    },
    {
        name: "先涨粉",
        emoji: "🚀",
        desc: "结尾引导「点赞收藏转发」，快速扩大视频传播和曝光",
    },
    {
        name: "品牌曝光",
        emoji: "🌟",
        desc: "结尾强化「记住我们是XX」，建立品牌长期认知和印象",
    },
];

export const INDUSTRY_DIRECTION_OPTION_NAMES = INDUSTRY_DIRECTION_OPTIONS.map((option) => option.name);
export const ACCOUNT_STYLE_OPTION_NAMES = ACCOUNT_STYLE_OPTIONS.map((option) => option.name);
export const ACTION_GOAL_OPTION_NAMES = ACTION_GOAL_OPTIONS.map((option) => option.name);

export const PERSON_TYPE_NAME_MAP: Record<PersonTypeEnum, string> = {
    [PersonTypeEnum.PERSONAL_IP]: "个人IP",
    [PersonTypeEnum.BUSINESS_SERVICE]: "企业服务",
    [PersonTypeEnum.LOCAL_BUSINESS]: "本地商家",
};

export const BIZ_REPORT_FIELDS: (keyof PersonFormData)[] = [
    "persona_type",
    "main_business",
    "target_pain_points",
    "conversion_hook",
];

export const REPORT_RELATED_FIELDS: Record<PersonTypeEnum, (keyof PersonFormData)[]> = {
    [PersonTypeEnum.PERSONAL_IP]: [
        ...BIZ_REPORT_FIELDS,
        "nickname",
        "identity",
        "personality_tags",
        "core_value",
        "target_audience",
        "monetize_paths",
    ],
    [PersonTypeEnum.BUSINESS_SERVICE]: [
        ...BIZ_REPORT_FIELDS,
        "brand_name",
        "spokesperson",
        "brand_tone",
        "main_product",
        "target_customer",
        "account_goal",
    ],
    [PersonTypeEnum.LOCAL_BUSINESS]: [
        ...BIZ_REPORT_FIELDS,
        "store_name",
        "store_spokesperson",
        "store_atmosphere",
        "signature_feature",
        "target_customer",
        "content_preference",
    ],
};

export const PERSON_DETAIL_FORM_KEY: Record<PersonTypeEnum, "individual" | "enterprise" | "local"> = {
    [PersonTypeEnum.PERSONAL_IP]: "individual",
    [PersonTypeEnum.BUSINESS_SERVICE]: "enterprise",
    [PersonTypeEnum.LOCAL_BUSINESS]: "local",
};

export const REPORT_MODEL_MAP: Record<PersonTypeEnum, number> = {
    [PersonTypeEnum.PERSONAL_IP]: 4,
    [PersonTypeEnum.BUSINESS_SERVICE]: 5,
    [PersonTypeEnum.LOCAL_BUSINESS]: 6,
};

export const normalizeStringArray = (raw: unknown): string[] =>
    Array.isArray(raw) ? raw.map((item) => String(item ?? "").trim()).filter(Boolean) : [];

export const createDefaultBasicForm = (): BasicFormData => ({
    ai_name: "",
    intro: "",
    persona_type: PersonTypeEnum.PERSONAL_IP,
    industry_direction: [],
    content_direction: "",
    target_audience: "",
    account_style: "",
    city_position: "",
    auto_location: true,
    action_goal: "",
});

export const createDefaultPersonFormData = (): PersonFormData => ({
    persona_name: "",
    persona_desc: "",
    persona_type: PersonTypeEnum.PERSONAL_IP,
    avatar_url: `${config.baseUrl}static/images/mp/person_default.png`,
    content_focus: "",
    account_style: "",
    hot_words: [],
    nickname: "",
    identity: [OPTIONS_CONFIG.identity[0] ?? ""],
    personality_tags: [OPTIONS_CONFIG.personality_tags[0] ?? ""],
    core_value: "",
    target_audience: "",
    monetize_paths: [OPTIONS_CONFIG.monetize_paths[0] ?? ""],
    brand_name: "",
    spokesperson: [OPTIONS_CONFIG.spokesperson[0] ?? ""],
    brand_tone: [OPTIONS_CONFIG.brand_tone[0] ?? ""],
    main_product: "",
    target_customer: "",
    account_goal: [OPTIONS_CONFIG.account_goal[0] ?? ""],
    store_name: "",
    store_spokesperson: [OPTIONS_CONFIG.store_spokesperson[0] ?? ""],
    store_atmosphere: [OPTIONS_CONFIG.store_atmosphere[0] ?? ""],
    signature_feature: "",
    content_preference: [OPTIONS_CONFIG.content_preference[0] ?? ""],
    main_business: "",
    target_pain_points: "",
    conversion_hook: "",
    is_shopping_cart: 0,
    goods_name: "",
    global_option: null,
});

export const inferPersonaTypeFromBasicInfo = (basicForm: BasicFormData): PersonTypeEnum => {
    if (basicForm.persona_type) return basicForm.persona_type;
    const direction = (basicForm.industry_direction || []).join("");
    if (/企业|B2B|软件|制造|服务|品牌/.test(direction)) return PersonTypeEnum.BUSINESS_SERVICE;
    if (/本地|门店|餐饮|美业|探店|房产|家居|宠物|到店/.test(`${direction}${basicForm.action_goal}`)) {
        return PersonTypeEnum.LOCAL_BUSINESS;
    }
    return PersonTypeEnum.PERSONAL_IP;
};

export const syncBasicInfoToPayloadFields = (basicForm: BasicFormData, formData: PersonFormData): void => {
    const industryDirections = basicForm.industry_direction || [];
    const primaryDirection = industryDirections[0] || "";
    const aiName = basicForm.ai_name.trim() || `${primaryDirection || "AI"}助手`;
    const accountStyle = basicForm.account_style.trim();
    const actionGoal = basicForm.action_goal.trim();
    const shareTopic = basicForm.content_direction.trim();
    const shareAudience = basicForm.target_audience.trim();

    formData.persona_name = aiName;
    formData.persona_desc = basicForm.intro.trim();
    formData.persona_type = inferPersonaTypeFromBasicInfo(basicForm);
    formData.content_focus = shareTopic;
    formData.account_style = accountStyle;

    if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
        formData.nickname = formData.persona_name;
        formData.identity = industryDirections.length ? [...industryDirections] : formData.identity;
        formData.personality_tags = accountStyle ? [accountStyle] : formData.personality_tags;
        formData.monetize_paths = actionGoal ? [actionGoal] : formData.monetize_paths;
        formData.core_value = shareTopic;
        formData.target_audience = shareAudience;
    } else if (formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
        formData.brand_name = formData.persona_name;
        formData.spokesperson = industryDirections.length ? [...industryDirections] : formData.spokesperson;
        formData.brand_tone = accountStyle ? [accountStyle] : formData.brand_tone;
        formData.account_goal = actionGoal ? [actionGoal] : formData.account_goal;
        formData.main_product = shareTopic;
        formData.target_customer = shareAudience;
    } else {
        formData.store_name = formData.persona_name;
        formData.store_spokesperson = industryDirections.length ? [...industryDirections] : formData.store_spokesperson;
        formData.store_atmosphere = accountStyle ? [accountStyle] : formData.store_atmosphere;
        formData.content_preference = actionGoal ? [actionGoal] : formData.content_preference;
        formData.signature_feature = shareTopic;
        formData.target_customer = shareAudience;
    }
};

export const ensurePayloadFallbacks = (basicForm: BasicFormData, formData: PersonFormData): void => {
    syncBasicInfoToPayloadFields(basicForm, formData);
    if (!formData.nickname.trim()) formData.nickname = formData.persona_name;
    if (!formData.brand_name.trim()) formData.brand_name = formData.persona_name;
    if (!formData.store_name.trim()) formData.store_name = formData.persona_name;
};

export const isSameReportValue = (a: unknown, b: unknown): boolean => {
    if (Array.isArray(a) && Array.isArray(b)) {
        if (a.length !== b.length) return false;
        return [...a].sort().join(",") === [...b].sort().join(",");
    }
    return a === b;
};

const normalizeReportValue = (value: unknown): unknown => {
    if (Array.isArray(value)) {
        return value.map((item) => String(item ?? "").trim()).filter(Boolean).sort();
    }
    if (typeof value === "string") return value.trim();
    return value;
};

export const createReportSnapshot = (formData: PersonFormData): Partial<PersonFormData> => {
    const fields = REPORT_RELATED_FIELDS[formData.persona_type] ?? [];
    const snapshot: Partial<PersonFormData> = {};
    fields.forEach((key) => {
        const val = (formData as unknown as Record<string, unknown>)[key];
        (snapshot as Record<string, unknown>)[key] = normalizeReportValue(val);
    });
    return snapshot;
};

export const hasReportFieldsChanged = (
    formData: PersonFormData,
    originSnapshot: Partial<PersonFormData> | null,
): boolean => {
    if (!originSnapshot) return true;
    const currentSnapshot = createReportSnapshot(formData);
    const fields = Object.keys(currentSnapshot);
    const snapshot = originSnapshot as Record<string, unknown>;
    const current = currentSnapshot as Record<string, unknown>;
    return fields.some((key) => !isSameReportValue(current[key], snapshot[key]));
};

export const buildPersonPayload = (
    basicForm: BasicFormData,
    formData: PersonFormData,
    options: { includeStoreAndShoppingFields?: boolean } = {},
): CreatePersonPayload => {
    ensurePayloadFallbacks(basicForm, formData);
    const base: CreatePersonPayload = {
        persona_name: formData.persona_name,
        persona_desc: formData.persona_desc,
        persona_type: formData.persona_type,
        avatar_url: formData.avatar_url,
        main_business: formData.main_business.trim(),
        target_pain_points: formData.target_pain_points.trim(),
        conversion_hook: formData.conversion_hook.trim(),
    };

    if (options.includeStoreAndShoppingFields) {
        base.is_shopping_cart = formData.is_shopping_cart;
        base.goods_name = formData.is_shopping_cart === 1 ? formData.goods_name.trim() : "";
        base.is_store_position = (basicForm.auto_location ? 1 : 0) as 0 | 1;
        base.store_position = basicForm.city_position.trim();
    }

    if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
        return {
            ...base,
            individual: {
                nickname: formData.nickname,
                identity: formData.identity,
                personality_tags: formData.personality_tags,
                core_value: formData.core_value,
                target_audience: formData.target_audience,
                monetize_paths: formData.monetize_paths,
            },
        };
    }

    if (formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
        return {
            ...base,
            enterprise: {
                brand_name: formData.brand_name,
                spokesperson: formData.spokesperson,
                brand_tone: formData.brand_tone,
                main_product: formData.main_product,
                target_customer: formData.target_customer,
                account_goal: formData.account_goal,
            },
        };
    }

    return {
        ...base,
        local: {
            store_name: formData.store_name,
            spokesperson: formData.store_spokesperson,
            store_atmosphere: formData.store_atmosphere,
            signature_feature: formData.signature_feature,
            target_customer: formData.target_customer,
            content_preference: formData.content_preference,
        },
    };
};

export const buildReportContents = (formData: PersonFormData): Record<string, any> => {
    if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
        return {
            ai_persona_individual: {
                nickname: formData.nickname,
                identity: formData.identity,
                personality_tags: formData.personality_tags,
                core_value: formData.main_business,
                highlight_story: formData.conversion_hook,
                target_audience: formData.target_pain_points,
                monetize_paths: formData.monetize_paths,
            },
        };
    }

    if (formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
        return {
            ai_persona_enterprise: {
                brand_name: formData.brand_name,
                spokesperson: formData.spokesperson,
                brand_tone: formData.brand_tone,
                main_product: formData.main_business,
                industry_case: formData.conversion_hook,
                target_customer: formData.target_pain_points,
            },
        };
    }

    return {
        ai_persona_local: {
            store_name: formData.store_name,
            spokesperson: formData.store_spokesperson,
            store_atmosphere: formData.store_atmosphere,
            signature_feature: formData.main_business,
            open_story: formData.conversion_hook,
            target_customer: formData.target_pain_points,
            content_preference: formData.content_preference,
        },
    };
};
