<template>
    <view class="min-h-screen bg-[#F4F6F8] pb-[180rpx]">
        <u-navbar
            :border-bottom="false"
            :background="{ background: navBgColor }"
            title="创建人设"
            title-bold
            :custom-back="back">
        </u-navbar>

        <view v-if="pageLoading" class="px-[30rpx] pt-4 animate-pulse">
            <view class="flex flex-col items-center justify-center mb-6">
                <view class="w-[160rpx] h-[160rpx] rounded-full bg-[#E5E7EB]"></view>
                <view class="w-[120rpx] h-[24rpx] bg-[#E5E7EB] rounded mt-2"></view>
            </view>

            <view class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                <view class="flex items-center mb-3 gap-x-2">
                    <view class="w-[8rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[140rpx] h-[30rpx] bg-[#E5E7EB] rounded"></view>
                </view>
                <view class="bg-[#F7F8FA] rounded-[16rpx] px-3 py-1.5 h-[80rpx]">
                    <view class="w-[60%] h-[28rpx] bg-[#E5E7EB] rounded mt-[16rpx]"></view>
                </view>
            </view>

            <view class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                <view class="flex items-center mb-1 gap-x-2">
                    <view class="w-[8rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                    <view class="w-[140rpx] h-[30rpx] bg-[#E5E7EB] rounded"></view>
                </view>
                <view class="w-[240rpx] h-[24rpx] bg-[#E5E7EB] rounded mt-2 mb-4"></view>
                <view class="grid grid-cols-3 gap-3">
                    <view v-for="n in 3" :key="n" class="h-[220rpx] rounded-[24rpx] bg-[#E5E7EB]"> </view>
                </view>
            </view>

            <view class="flex items-center justify-center my-6">
                <view class="h-[1px] w-12 bg-[#E5E7EB]"></view>
                <view class="w-[200rpx] h-[26rpx] bg-[#E5E7EB] rounded mx-4"></view>
                <view class="h-[1px] w-12 bg-[#E5E7EB]"></view>
            </view>

            <view v-for="n in 3" :key="n" class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                <!-- 板块标题 -->
                <view class="flex items-center gap-x-2 mb-5">
                    <view class="h-4 w-1 bg-[#E5E7EB] rounded-full"></view>
                    <view class="w-[180rpx] h-[32rpx] bg-[#E5E7EB] rounded"></view>
                </view>
                <view class="mb-5">
                    <view class="flex items-center mb-2 gap-x-1">
                        <view class="w-[8rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                        <view class="w-[160rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                    </view>
                    <view class="bg-[#F7F8FA] rounded-[16rpx] h-[80rpx]">
                        <view class="w-[50%] h-[28rpx] bg-[#E5E7EB] rounded mx-3 mt-[26rpx]"></view>
                    </view>
                </view>
                <view>
                    <view class="flex items-center mb-2 gap-x-1">
                        <view class="w-[8rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                        <view class="w-[120rpx] h-[28rpx] bg-[#E5E7EB] rounded"></view>
                    </view>
                    <view class="flex flex-wrap gap-2.5">
                        <view
                            v-for="m in 4"
                            :key="m"
                            class="h-[56rpx] rounded-full bg-[#E5E7EB]"
                            :style="{ width: `${80 + m * 16}rpx` }">
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view v-else class="px-[30rpx] pt-4">
            <view class="flex flex-col items-center justify-center mb-6">
                <avatar-upload :avatar="formData.avatar_url" @update:avatar="handleAvatarUpdate" />
                <text class="text-[24rpx] text-[#b4b4b4] mt-2">点击更换头像</text>
            </view>

            <view class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                <view class="flex items-center mb-3">
                    <text class="text-error text-[28rpx] mr-1">*</text>
                    <text class="text-[30rpx] font-bold text-gray-800">人设名称</text>
                </view>
                <view class="bg-[#F7F8FA] rounded-[16rpx] px-3 py-1.5">
                    <u-input
                        v-model="formData.persona_name"
                        maxlength="30"
                        clearable
                        placeholder="请输入人设名称"
                        :custom-style="{ fontSize: '28rpx' }"
                        placeholder-style="color: #9CA3AF; font-size: 28rpx;"
                        :border="false" />
                </view>
            </view>

            <view class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                <view class="mb-3">
                    <view class="flex items-center">
                        <text class="text-error text-[28rpx] mr-1">*</text>
                        <text class="text-[30rpx] font-bold text-gray-800">人设类型</text>
                    </view>
                    <text class="text-[24rpx] text-gray-400 mt-1 block">根据类型AI将为您匹配对应的运营报告</text>
                </view>
                <view class="grid grid-cols-3 gap-3">
                    <view
                        v-for="(item, index) in personTypes"
                        :key="index"
                        class="h-[220rpx] rounded-[24rpx] flex flex-col items-center justify-center relative transition-all duration-300 border-[2rpx]"
                        :class="
                            formData.persona_type === item.type
                                ? 'border-primary bg-[#F2F7FF]'
                                : 'border-transparent bg-[#F7F8FA]'
                        "
                        @click="formData.persona_type = item.type">
                        <image
                            :src="formData.persona_type === item.type ? item.activeIcon : item.icon"
                            class="w-[64rpx] h-[64rpx] mb-2" />
                        <text
                            class="text-[28rpx] font-bold"
                            :class="formData.persona_type === item.type ? 'text-primary' : 'text-[#212121]'">
                            {{ item.name }}
                        </text>
                        <text
                            class="text-[22rpx] mt-1"
                            :class="formData.persona_type === item.type ? 'text-[#0065fb]/70' : 'text-[#b4b4b4]'">
                            {{ item.desc }}
                        </text>
                        <view
                            v-if="formData.persona_type === item.type"
                            class="absolute top-[-2rpx] right-[-2rpx] w-[48rpx] h-[48rpx] bg-primary rounded-tr-[24rpx] rounded-bl-[24rpx] flex items-center justify-center">
                            <u-icon name="checkmark" color="#ffffff" size="24"></u-icon>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="currentPlate">
                <view class="flex items-center justify-center my-6">
                    <view class="h-[1px] w-12 bg-[#cdcdcd]"></view>
                    <text class="mx-4 text-[26rpx] text-[#b4b4b4] font-medium">完善详细信息</text>
                    <view class="h-[1px] w-12 bg-[#cdcdcd]"></view>
                </view>

                <view
                    v-for="(plate, pIndex) in currentPlate.plates"
                    :key="pIndex"
                    class="bg-white rounded-[32rpx] p-5 shadow-sm mb-4">
                    <view class="flex items-center gap-x-2 mb-5">
                        <view class="h-4 w-1 bg-primary rounded-full"></view>
                        <view class="text-[32rpx] font-bold text-[#212121]">{{ plate.title }}</view>
                    </view>

                    <view v-for="field in plate.fields" :key="field.key" class="mb-5 last:mb-0">
                        <view class="flex items-center mb-2">
                            <text class="text-error text-[28rpx] mr-1">*</text>
                            <text class="text-[28rpx] font-medium text-gray-700">{{ field.label }}</text>
                        </view>

                        <view v-if="field.type === FieldType.INPUT" class="bg-[#F7F8FA] rounded-[16rpx] px-3 py-1.5">
                            <u-input
                                v-model="currentSubForm[field.key as string]"
                                maxlength="50"
                                :placeholder="field.placeholder"
                                clearable
                                :custom-style="{ fontSize: '28rpx' }"
                                placeholder-style="color: #9CA3AF; font-size: 28rpx;"
                                :border="false" />
                        </view>

                        <view
                            v-else-if="field.type === FieldType.TEXTAREA"
                            class="bg-[#F7F8FA] rounded-[16rpx] px-3 py-2">
                            <u-input
                                v-model="currentSubForm[field.key as string]"
                                type="textarea"
                                height="160"
                                :placeholder="field.placeholder"
                                :custom-style="{ fontSize: '28rpx', lineHeight: '1.5' }"
                                placeholder-style="color: #9CA3AF; font-size: 28rpx;"
                                :border="false" />
                        </view>

                        <view v-else-if="field.type === FieldType.TAGS" class="flex flex-wrap gap-2.5">
                            <view
                                v-for="option in field.options"
                                :key="option"
                                class="px-4 py-1.5 rounded-full border-[2rpx] text-[26rpx] transition-all duration-300"
                                :class="
                                    isActive(field.key, option)
                                        ? 'bg-[#E6F0FF] text-primary border-primary font-medium'
                                        : 'bg-[#F7F8FA] text-[#676767] border-[transparent]'
                                "
                                @click="toggleOption(field.key, option)">
                                {{ option }}
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-4 pt-3 shadow-[0_-4rpx_20rpx_rgba(0,0,0,0.05)] z-50"
            style="padding-bottom: calc(12px + env(safe-area-inset-bottom))">
            <view v-if="pageLoading" class="animate-pulse">
                <view class="h-[96rpx] rounded-full bg-[#E5E7EB]"></view>
            </view>
            <u-button
                v-else
                type="primary"
                shape="circle"
                :loading="submitting"
                :ripple="true"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                }"
                @click="handleSubmit">
                {{ createMode === "add" ? "创建人设并生成报告" : "保存人设"
                }}<template v-if="isAddMode && getTokenScore > 0">(消耗{{ getTokenScore }}算力)</template>
            </u-button>
        </view>

        <dragon-button :x-edge="-20" :y-edge="100" v-if="isAddMode">
            <view
                class="w-[100rpx] h-[100rpx] rounded-full flex items-center justify-center"
                style="background: linear-gradient(180deg, rgba(77, 163, 255, 1) 0%, rgba(0, 122, 255, 1) 100%)"
                @click="openVoicePop">
                <u-icon name="mic" color="#ffffff" size="48"></u-icon>
            </view>
        </dragon-button>
    </view>

    <recorder-control
        v-model="showRecorder"
        ref="recorderRef"
        @close="showRecorder = false"
        @success="recorderSuccess" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import config from "@/config";
import {
    getPersonDetail,
    createPerson,
    editPerson,
    createPersonAnalysis,
    generatePersonAnalysisReport,
    getPersonClueWords,
    getPersonInteractionWords,
} from "@/api/person";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { PersonTypeEnum } from "@/ai_modules/person/enums";
import AvatarUpload from "@/ai_modules/person/components/avatar-upload/avatar-upload.vue";
import UserEditIcon from "@/ai_modules/person/static/icons/user_edit.svg";
import UserEditPrimaryIcon from "@/ai_modules/person/static/icons/user_edit_primary.svg";
import ServiceIcon from "@/ai_modules/person/static/icons/service.svg";
import ServicePrimaryIcon from "@/ai_modules/person/static/icons/service_primary.svg";
import ShopIcon from "@/ai_modules/person/static/icons/shop.svg";
import ShopPrimaryIcon from "@/ai_modules/person/static/icons/shop_primary.svg";
import RecorderControl from "@/ai_modules/person/components/recorder-control/recorder-control.vue";
import { setFormData } from "@/utils/util";

// ─── 字段配置类型 ─────────────────────────────────────────────────
type CreateMode = "add" | "edit";
enum FieldType {
    INPUT = "input",
    TEXTAREA = "textarea",
    TAGS = "tags",
}

interface BaseField {
    key: string;
    label: string;
    placeholder?: string;
}
interface InputField extends BaseField {
    type: FieldType.INPUT | FieldType.TEXTAREA;
}
interface TagsField extends BaseField {
    type: FieldType.TAGS;
    options: string[];
}
type FormField = InputField | TagsField;

interface Plate {
    title: string;
    fields: FormField[];
}
interface PersonTypeConfig {
    type: PersonTypeEnum;
    name: string;
    desc: string;
    icon: string;
    activeIcon: string;
    plates: Plate[];
}

// ─── 接口提交类型 ─────────────────────────────────────────────────
interface IndividualForm {
    nickname: string;
    identity: string[];
    personality_tags: string[];
    core_value: string;
    highlight_story: string;
    target_audience: string;
    monetize_paths: string[];
}

interface EnterpriseForm {
    brand_name: string;
    spokesperson: string[];
    brand_tone: string[];
    main_product: string;
    industry_case: string;
    target_customer: string;
    account_goal: string[];
}

interface LocalForm {
    store_name: string;
    store_atmosphere: string[];
    spokesperson: string[];
    signature_feature: string;
    open_story: string;
    target_customer: string;
    content_preference: string[];
}

interface CreatePersonPayload {
    persona_name: string;
    persona_type: PersonTypeEnum;
    avatar_url: string;
    individual?: IndividualForm;
    enterprise?: EnterpriseForm;
    local?: LocalForm;
}

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// ─── 表单数据 ────────────────────────────────────────────────────
interface FormData {
    persona_name: string;
    persona_type: PersonTypeEnum;
    avatar_url: string;
    nickname: string;
    identity: string[];
    personality_tags: string[];
    core_value: string;
    highlight_story: string;
    target_audience: string;
    monetize_paths: string[];
    brand_name: string;
    spokesperson: string[];
    brand_tone: string[];
    main_product: string;
    industry_case: string;
    target_customer: string;
    account_goal: string[];
    store_name: string;
    store_spokesperson: string[];
    store_atmosphere: string[];
    signature_feature: string;
    open_story: string;
    content_preference: string[];
}

const OPTIONS_CONFIG = {
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

const source = ref<string>("");
const personId = ref<string>("");
const showRecorder = ref(false);
const submitting = ref(false);
const createMode = ref<CreateMode>("add");
const navBgColor = ref<string>("transparent");

const pageLoading = ref(false);

const formData = reactive<FormData>({
    persona_name: "",
    persona_type: PersonTypeEnum.PERSONAL_IP,
    avatar_url: `${config.baseUrl}static/images/mp/person_default.png`,
    nickname: "",
    identity: [OPTIONS_CONFIG.identity[0] ?? ""],
    personality_tags: [OPTIONS_CONFIG.personality_tags[0] ?? ""],
    core_value: "",
    highlight_story: "",
    target_audience: "",
    monetize_paths: [OPTIONS_CONFIG.monetize_paths[0] ?? ""],
    brand_name: "",
    spokesperson: [OPTIONS_CONFIG.spokesperson[0] ?? ""],
    brand_tone: [OPTIONS_CONFIG.brand_tone[0] ?? ""],
    main_product: "",
    industry_case: "",
    target_customer: "",
    account_goal: [OPTIONS_CONFIG.account_goal[0] ?? ""],
    store_name: "",
    store_spokesperson: [OPTIONS_CONFIG.store_spokesperson[0] ?? ""],
    store_atmosphere: [OPTIONS_CONFIG.store_atmosphere[0] ?? ""],
    signature_feature: "",
    open_story: "",
    content_preference: [OPTIONS_CONFIG.content_preference[0] ?? ""],
});

const REPORT_RELATED_FIELDS: Record<PersonTypeEnum, (keyof FormData)[]> = {
    [PersonTypeEnum.PERSONAL_IP]: [
        "persona_type",
        "nickname",
        "identity",
        "personality_tags",
        "core_value",
        "highlight_story",
        "target_audience",
        "monetize_paths",
    ],
    [PersonTypeEnum.BUSINESS_SERVICE]: [
        "persona_type",
        "brand_name",
        "spokesperson",
        "brand_tone",
        "main_product",
        "industry_case",
        "target_customer",
        "account_goal",
    ],
    [PersonTypeEnum.LOCAL_BUSINESS]: [
        "persona_type",
        "store_name",
        "store_spokesperson",
        "store_atmosphere",
        "signature_feature",
        "open_story",
        "target_customer",
        "content_preference",
    ],
};

const originSnapshot = ref<Partial<FormData> | null>(null);

const isEqual = (a: unknown, b: unknown): boolean => {
    if (Array.isArray(a) && Array.isArray(b)) {
        if (a.length !== b.length) return false;
        return [...a].sort().join(",") === [...b].sort().join(",");
    }
    return a === b;
};

const hasFormChanged = (): boolean => {
    if (createMode.value === "add") return true;
    if (!originSnapshot.value) return true;
    const fields = REPORT_RELATED_FIELDS[formData.persona_type] ?? [];
    const snapshot = originSnapshot.value as Record<string, unknown>;
    const current = formData as unknown as Record<string, unknown>;
    return fields.some((key) => !isEqual(current[key], snapshot[key]));
};

const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();
const rechargePopupRef = shallowRef();

const currentSubForm = computed<Record<string, any>>(() => formData as unknown as Record<string, any>);
const isAddMode = computed(() => createMode.value === "add");

const getTokenScore = computed(() => {
    const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_REPORT);
    return Number(tokenInfo?.score || 0);
});

const getAnalysisTokenScore = computed(() => {
    const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_ANALYSIS);
    return Number(tokenInfo?.score || 0);
});

const personTypes = ref<PersonTypeConfig[]>([
    {
        type: PersonTypeEnum.PERSONAL_IP,
        name: "个人IP",
        desc: "创作达人/自媒体",
        icon: UserEditIcon,
        activeIcon: UserEditPrimaryIcon,
        plates: [
            {
                title: "打造个人魅力",
                fields: [
                    { key: "nickname", type: FieldType.INPUT, label: "昵称/网名", placeholder: "请输入昵称/网名" },
                    { key: "identity", type: FieldType.TAGS, label: "真实身份/职业", options: OPTIONS_CONFIG.identity },
                    {
                        key: "personality_tags",
                        type: FieldType.TAGS,
                        label: "性格标签",
                        options: OPTIONS_CONFIG.personality_tags,
                    },
                ],
            },
            {
                title: "核心与价值故事",
                fields: [
                    {
                        key: "core_value",
                        type: FieldType.TEXTAREA,
                        label: "我能提供的核心价值",
                        placeholder: "如：搞钱思路、穿搭技巧、情感咨询",
                    },
                    {
                        key: "highlight_story",
                        type: FieldType.TEXTAREA,
                        label: "个人高光/逆袭故事",
                        placeholder: "如：从月薪三千到大厂总监、踩坑无数、现在想把经验分享给新人",
                    },
                ],
            },
            {
                title: "粉丝与变现",
                fields: [
                    {
                        key: "target_audience",
                        type: FieldType.TEXTAREA,
                        label: "想吸引什么样的粉丝",
                        placeholder: "如：想吸引创业者、想吸引宝妈、想吸引年轻人",
                    },
                    {
                        key: "monetize_paths",
                        type: FieldType.TAGS,
                        label: "主要变现路径",
                        options: OPTIONS_CONFIG.monetize_paths,
                    },
                ],
            },
        ],
    },
    {
        type: PersonTypeEnum.BUSINESS_SERVICE,
        name: "企业服务",
        desc: "B2B/软件/制造",
        icon: ServiceIcon,
        activeIcon: ServicePrimaryIcon,
        plates: [
            {
                title: "塑造专业形象",
                fields: [
                    {
                        key: "brand_name",
                        type: FieldType.INPUT,
                        label: "企业/品牌名称",
                        placeholder: "请输入企业/品牌名称",
                    },
                    {
                        key: "spokesperson",
                        type: FieldType.TAGS,
                        label: "谁代表公司出镜",
                        options: OPTIONS_CONFIG.spokesperson,
                    },
                    { key: "brand_tone", type: FieldType.TAGS, label: "品牌调性", options: OPTIONS_CONFIG.brand_tone },
                ],
            },
            {
                title: "核心业务与壁垒",
                fields: [
                    {
                        key: "main_product",
                        type: FieldType.TEXTAREA,
                        label: "主打的产品/解决方案",
                        placeholder: "如：财税一体化、获客系统、法律咨询",
                    },
                    {
                        key: "industry_case",
                        type: FieldType.TEXTAREA,
                        label: "行业案例/优势",
                        placeholder: "如：服务过500强企业、拥有核心专利",
                    },
                ],
            },
            {
                title: "获客与转化",
                fields: [
                    {
                        key: "target_customer",
                        type: FieldType.TEXTAREA,
                        label: "目标客户画像",
                        placeholder: "如：中小微企业主、创业公司创始人",
                    },
                    {
                        key: "account_goal",
                        type: FieldType.TAGS,
                        label: "账号核心目的",
                        options: OPTIONS_CONFIG.account_goal,
                    },
                ],
            },
        ],
    },
    {
        type: PersonTypeEnum.LOCAL_BUSINESS,
        name: "本地商家",
        desc: "餐饮/美业/门店",
        icon: ShopIcon,
        activeIcon: ShopPrimaryIcon,
        plates: [
            {
                title: "门店招牌与人设",
                fields: [
                    {
                        key: "store_name",
                        type: FieldType.INPUT,
                        label: "门店名称+所在商圈",
                        placeholder: "请输入店铺名称",
                    },
                    {
                        key: "store_spokesperson",
                        type: FieldType.TAGS,
                        label: "谁出镜揽客",
                        options: OPTIONS_CONFIG.store_spokesperson,
                    },
                    {
                        key: "store_atmosphere",
                        type: FieldType.TAGS,
                        label: "门店氛围感",
                        options: OPTIONS_CONFIG.store_atmosphere,
                    },
                ],
            },
            {
                title: "爆款与进店理由",
                fields: [
                    {
                        key: "signature_feature",
                        type: FieldType.TEXTAREA,
                        label: "招牌特色",
                        placeholder: "如：招牌脆皮烤鸭、无痛清痘",
                    },
                    {
                        key: "open_story",
                        type: FieldType.TEXTAREA,
                        label: "开店初衷/门店优势",
                        placeholder: "如：为了让大家吃到正宗家乡味、十年老手艺",
                    },
                ],
            },
            {
                title: "拓客引流",
                fields: [
                    {
                        key: "target_customer",
                        type: FieldType.TEXTAREA,
                        label: "主要想吸引谁进店？",
                        placeholder: "如：想吸引年轻人、想吸引宝妈、想吸引职场精英",
                    },
                    {
                        key: "content_preference",
                        type: FieldType.TAGS,
                        label: "内容偏好",
                        options: OPTIONS_CONFIG.content_preference,
                    },
                ],
            },
        ],
    },
]);

const currentPlate = computed<PersonTypeConfig | undefined>(() =>
    personTypes.value.find((item) => item.type === formData.persona_type)
);

const openVoicePop = async () => {
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    const { message } = res;

    if (userTokens.value <= getAnalysisTokenScore.value) {
        rechargePopupRef.value?.open();
        return;
    }

    uni.showLoading({ title: "AI分析中...", mask: true });

    try {
        const analysisResult = await createPersonAnalysis({
            contents: message,
            model: formData.persona_type,
        });

        const parseAndMatchOptions = (value: string | undefined, availableOptions: readonly string[]): string[] => {
            if (!value) return [];
            const items = value
                .split(/[,，;；、\s]+/)
                .map((item) => item.trim())
                .filter((item) => item.length > 0);
            const matched: string[] = [];
            items.forEach((item) => {
                const exactMatch = availableOptions.find(
                    (option) => option === item || option.includes(item) || item.includes(option)
                );
                if (exactMatch && !matched.includes(exactMatch)) matched.push(exactMatch);
            });
            return matched;
        };

        if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
            const {
                core_value,
                highlight_story,
                identity,
                monetize_paths,
                nickname,
                personality_tags,
                target_audience,
            } = analysisResult;
            if (nickname) formData.nickname = nickname;
            if (core_value) formData.core_value = core_value;
            if (highlight_story) formData.highlight_story = highlight_story;
            if (target_audience) formData.target_audience = target_audience;
            if (identity) formData.identity = parseAndMatchOptions(identity, OPTIONS_CONFIG.identity);
            if (personality_tags)
                formData.personality_tags = parseAndMatchOptions(personality_tags, OPTIONS_CONFIG.personality_tags);
            if (monetize_paths)
                formData.monetize_paths = parseAndMatchOptions(monetize_paths, OPTIONS_CONFIG.monetize_paths);
        } else if (formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
            const { account_goal, brand_name, brand_tone, industry_case, main_product, spokesperson, target_customer } =
                analysisResult;
            if (brand_name) formData.brand_name = brand_name;
            if (main_product) formData.main_product = main_product;
            if (industry_case) formData.industry_case = industry_case;
            if (target_customer) formData.target_customer = target_customer;
            if (account_goal) formData.account_goal = parseAndMatchOptions(account_goal, OPTIONS_CONFIG.account_goal);
            if (spokesperson) formData.spokesperson = parseAndMatchOptions(spokesperson, OPTIONS_CONFIG.spokesperson);
            if (brand_tone) formData.brand_tone = parseAndMatchOptions(brand_tone, OPTIONS_CONFIG.brand_tone);
        } else if (formData.persona_type === PersonTypeEnum.LOCAL_BUSINESS) {
            const {
                content_preference,
                open_story,
                signature_feature,
                spokesperson,
                store_atmosphere,
                store_name,
                target_customer,
            } = analysisResult;
            if (store_name) formData.store_name = store_name;
            if (signature_feature) formData.signature_feature = signature_feature;
            if (open_story) formData.open_story = open_story;
            if (target_customer) formData.target_customer = target_customer;
            if (spokesperson)
                formData.store_spokesperson = parseAndMatchOptions(spokesperson, OPTIONS_CONFIG.store_spokesperson);
            if (store_atmosphere)
                formData.store_atmosphere = parseAndMatchOptions(store_atmosphere, OPTIONS_CONFIG.store_atmosphere);
            if (content_preference)
                formData.content_preference = parseAndMatchOptions(
                    content_preference,
                    OPTIONS_CONFIG.content_preference
                );
        }

        uni.hideLoading();
        uni.showToast({ title: "AI分析完成", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showModal({
            title: "分析失败",
            content: error?.message || "语音分析失败，请检查网络后重试",
            showCancel: false,
            confirmText: "我知道了",
        });
    }
};

const handleAvatarUpdate = (imageUrl: string): void => {
    formData.avatar_url = imageUrl;
};

const SINGLE_SELECT_KEYS = new Set<string>([]);

const isActive = (key: string, item: string): boolean => {
    const value = (formData as unknown as Record<string, any>)[key];
    if (Array.isArray(value)) return value.includes(item);
    return value === item;
};

const toggleOption = (key: string, item: string): void => {
    const data = formData as unknown as Record<string, any>;
    if (SINGLE_SELECT_KEYS.has(key)) {
        data[key] = data[key] === item ? "" : item;
    } else {
        const arr: string[] = data[key] ?? [];
        const index = arr.indexOf(item);
        data[key] = index > -1 ? arr.filter((v) => v !== item) : [...arr, item];
    }
};

const buildPayload = (): CreatePersonPayload => {
    const base = {
        persona_name: formData.persona_name,
        persona_type: formData.persona_type,
        avatar_url: formData.avatar_url,
    };

    if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
        return {
            ...base,
            individual: {
                nickname: formData.nickname,
                identity: formData.identity,
                personality_tags: formData.personality_tags,
                core_value: formData.core_value,
                highlight_story: formData.highlight_story,
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
                industry_case: formData.industry_case,
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
            open_story: formData.open_story,
            target_customer: formData.target_customer,
            content_preference: formData.content_preference,
        },
    };
};

const validate = (): boolean => {
    if (!formData.persona_name.trim()) {
        uni.showToast({ title: "请输入人设名称", icon: "none" });
        return false;
    }
    const plateConfig = currentPlate.value;
    if (!plateConfig) return false;

    for (const plate of plateConfig.plates) {
        for (const field of plate.fields) {
            const val = (formData as unknown as Record<string, any>)[field.key];
            if (val === undefined || val === "" || (Array.isArray(val) && val.length === 0)) {
                uni.showToast({ title: `请完善「${field.label}」`, icon: "none" });
                return false;
            }
        }
    }
    return true;
};

const checkContentLength = (): Promise<boolean> => {
    const MIN_TOTAL_LENGTH = 300;
    const plateConfig = currentPlate.value;
    if (!plateConfig) return Promise.resolve(true);

    let totalLength = 0;
    for (const plate of plateConfig.plates) {
        for (const field of plate.fields) {
            if (field.type === FieldType.INPUT || field.type === FieldType.TEXTAREA) {
                const val = (formData as unknown as Record<string, any>)[field.key];
                if (typeof val === "string") totalLength += val.trim().length;
            }
        }
    }

    if (totalLength >= MIN_TOTAL_LENGTH) return Promise.resolve(true);

    return new Promise((resolve) => {
        if (isAddMode.value) {
            uni.showModal({
                title: "内容过少",
                content: "当前内容过少，如果生成报告准确性将不确定，是否继续生成？",
                confirmText: "确定生成",
                cancelText: "继续修改",
                success: ({ confirm }) => resolve(confirm),
            });
        } else {
            uni.showModal({
                title: "内容变更",
                content: "检测到您的人设内容有变更，如继续保存将为您重新生成运营报告。",
                confirmText: "确定生成",
                cancelText: "取消",
                success: ({ confirm }) => resolve(confirm),
            });
        }
    });
};

const handleSubmit = async (): Promise<void> => {
    if (!validate()) return;

    const needGenerateReport = hasFormChanged();

    if (needGenerateReport) {
        const shouldContinue = await checkContentLength();
        if (!shouldContinue) return;
    }

    if (needGenerateReport && userTokens.value <= getTokenScore.value) {
        rechargePopupRef.value?.open();
        return;
    }

    uni.showLoading({ title: "保存中...", mask: true });
    try {
        submitting.value = true;
        const payload = buildPayload();
        const res = personId.value
            ? await editPerson({ ...payload, id: personId.value, is_create_report: needGenerateReport ? 1 : 0 })
            : await createPerson(payload);
        uni.hideLoading();

        if (needGenerateReport) {
            const contents = {
                1: {
                    ai_persona_individual: {
                        nickname: formData.nickname,
                        identity: formData.identity,
                        personality_tags: formData.personality_tags,
                        core_value: formData.core_value,
                        highlight_story: formData.highlight_story,
                        target_audience: formData.target_audience,
                        monetize_paths: formData.monetize_paths,
                    },
                },
                2: {
                    ai_persona_enterprise: {
                        brand_name: formData.brand_name,
                        spokesperson: formData.spokesperson,
                        brand_tone: formData.brand_tone,
                        main_product: formData.main_product,
                        industry_case: formData.industry_case,
                        target_customer: formData.target_customer,
                    },
                },
                3: {
                    ai_persona_local: {
                        store_name: formData.store_name,
                        spokesperson: formData.store_spokesperson,
                        store_atmosphere: formData.store_atmosphere,
                        signature_feature: formData.signature_feature,
                        open_story: formData.open_story,
                        target_customer: formData.target_customer,
                        content_preference: formData.content_preference,
                    },
                },
            };

            generatePersonAnalysisReport({
                persona_id: res.persona_id,
                model: { 1: 4, 2: 5, 3: 6 }[formData.persona_type],
                contents: contents[formData.persona_type],
            });
            getPersonClueWords({ id: res.persona_id });
            getPersonInteractionWords({ id: res.persona_id });

            uni.showModal({
                title: "提示",
                content: "IP运营报告生成中,您可以继续等待，或可先配置其他内容",
                cancelText: "稍后查看",
                confirmText: "立即前往",
                success: ({ confirm }) => {
                    if (confirm) {
                        uni.redirectTo({
                            url: `/ai_modules/person/pages/analysis/analysis?id=${res.persona_id}&mode=${
                                needGenerateReport ? "add" : createMode.value
                            }&type=${formData.persona_type}`,
                        });
                    } else {
                        if (source.value === "back") {
                            uni.navigateBack();
                            return;
                        }
                        uni.redirectTo({
                            url: `/ai_modules/person/pages/detail/detail?id=${res.persona_id}&mode=${createMode.value}&type=${formData.persona_type}`,
                        });
                    }
                },
            });
        } else {
            uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
            setTimeout(() => uni.navigateBack(), 1000);
        }
    } catch (e) {
        uni.hideLoading();
        uni.showToast({ title: "保存失败，请重试", icon: "none" });
    } finally {
        submitting.value = false;
    }
};

const back = (): void => {
    uni.showModal({
        title: "提示",
        content: "退出后，您填写的信息将不会被保存",
        success: (res) => {
            if (res.confirm) uni.navigateBack();
        },
    });
};

const init = async (): Promise<void> => {
    try {
        pageLoading.value = true;
        const data = await getPersonDetail({ id: personId.value });
        const formKey: any = { 1: "individual", 2: "enterprise", 3: "local" }[data.persona_type as PersonTypeEnum];
        const mergedData = { ...data, ...data[formKey] };
        setFormData(mergedData, formData);
        if (formData.persona_type === PersonTypeEnum.LOCAL_BUSINESS) {
            formData.store_spokesperson = data.local?.spokesperson;
        }

        const fields = REPORT_RELATED_FIELDS[data.persona_type as PersonTypeEnum] ?? [];
        const snapshot: Partial<FormData> = {};
        fields.forEach((key) => {
            const val = (formData as unknown as Record<string, unknown>)[key];
            (snapshot as Record<string, unknown>)[key] = Array.isArray(val) ? [...val] : val;
        });
        originSnapshot.value = snapshot;
    } finally {
        pageLoading.value = false;
    }
};

onLoad((options: any) => {
    if (options.type) formData.persona_type = Number(options.type) as PersonTypeEnum;
    if (options.mode) createMode.value = options.mode as CreateMode;
    if (options.id) {
        personId.value = options.id;
    }
    if (options.source) source.value = options.source;
    if (createMode.value === "edit" && personId.value) {
        pageLoading.value = true;
        init();
    }
});

onPageScroll(({ scrollTop }: { scrollTop: number }) => {
    navBgColor.value = scrollTop > 100 ? "#ffffff" : "transparent";
});
</script>
