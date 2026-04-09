<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="编辑IP类型"
            :async="true"
            width="660px"
            @confirm="handleSubmit"
            @close="handleClose">
            <div class="py-2">
                <div class="bg-white rounded-xl p-4 mb-4 border border-[#f3f4f6]">
                    <div class="mb-3">
                        <div class="flex items-center gap-1 mb-1">
                            <span class="text-[#dc2626] text-xs">*</span>
                            <span class="text-sm font-bold text-[#4b5563]">人设类型</span>
                        </div>
                        <span class="text-xs text-[#9ca3af]">根据类型AI将为您匹配对应的运营报告</span>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="(item, index) in personTypes"
                            :key="index"
                            class="relative h-28 rounded-2xl flex flex-col items-center justify-center cursor-pointer border-2 transition-all duration-300 select-none"
                            :class="
                                formData.persona_type === item.type
                                    ? 'border-primary bg-[#eff6ff]'
                                    : 'border-[transparent] bg-[#f9fafb] hover:border-[#bfdbfe]'
                            "
                            @click="handleTypeChange(item.type)">
                            <span
                                class="text-sm font-bold"
                                :class="formData.persona_type === item.type ? 'text-[#3b82f6]' : 'text-[#4b5563]'">
                                {{ item.name }}
                            </span>

                            <span
                                class="text-xs mt-0.5"
                                :class="formData.persona_type === item.type ? 'text-[#60a5fa]' : 'text-[#9ca3af]'">
                                {{ item.desc }}
                            </span>

                            <div
                                v-if="formData.persona_type === item.type"
                                class="absolute top-0 right-0 w-6 h-6 bg-primary rounded-tr-2xl rounded-bl-2xl flex items-center justify-center">
                                <el-icon class="text-white" :size="12"><Check /></el-icon>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="currentPlate">
                    <div class="flex items-center justify-center my-4">
                        <div class="h-px w-10 bg-gray-200"></div>
                        <span class="mx-3 text-xs text-gray-400 font-medium">完善详细信息</span>
                        <div class="h-px w-10 bg-gray-200"></div>
                    </div>

                    <div
                        v-for="(plate, pIndex) in currentPlate.plates"
                        :key="pIndex"
                        class="bg-[#f9fafb] rounded-xl p-4 mb-3 last:mb-0">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-4 w-1 bg-primary rounded-full"></div>
                            <span class="text-sm font-bold text-[#4b5563]">{{ plate.title }}</span>
                        </div>

                        <div v-for="field in plate.fields" :key="field.key" class="mb-4 last:mb-0">
                            <div class="flex items-center mb-1.5">
                                <span class="text-[#dc2626] text-xs mr-1">*</span>
                                <span class="text-sm font-medium text-[#4b5563]">{{ field.label }}</span>
                            </div>

                            <el-input
                                v-if="field.type === FieldType.INPUT"
                                v-model="currentSubForm[field.key]"
                                :maxlength="50"
                                :placeholder="field.placeholder"
                                clearable
                                class="!rounded-lg" />

                            <el-input
                                v-else-if="field.type === FieldType.TEXTAREA"
                                v-model="currentSubForm[field.key]"
                                type="textarea"
                                :rows="3"
                                :placeholder="field.placeholder"
                                class="!rounded-lg" />

                            <div v-else-if="field.type === FieldType.TAGS" class="flex flex-wrap gap-2">
                                <div
                                    v-for="option in field.options"
                                    :key="option"
                                    class="px-3 py-1 rounded-full text-xs border cursor-pointer transition-all duration-200 select-none"
                                    :class="
                                        isActive(field.key, option)
                                            ? 'bg-[#eff6ff] text-primary border-primary font-medium'
                                            : 'bg-[#f9fafb] text-[#9ca3af] border-[#e5e7eb] hover:border-primary hover:text-primary'
                                    "
                                    @click="toggleOption(field.key, option)">
                                    {{ option }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import { Check } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import {
    editPersona,
    getClueTouch,
    getWechatConfig,
    generatePersonAnalysisReport,
} from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";
import feedback from "@/utils/feedback";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

// ─── 字段类型枚举 ────────────────────────────────────────────────
enum FieldType {
    INPUT = "input",
    TEXTAREA = "textarea",
    TAGS = "tags",
}

// ─── 选项配置 ────────────────────────────────────────────────────
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

// ─── 人设类型卡片配置 ────────────────────────────────────────────
const personTypes = [
    { type: 1, name: "个人IP", desc: "创作达人/自媒体" },
    { type: 2, name: "企业服务", desc: "B2B/软件/制造" },
    { type: 3, name: "本地商家", desc: "餐饮/美业/门店" },
];

// ─── 板块字段配置 ────────────────────────────────────────────────
const PLATE_CONFIG: Record<number, { title: string; fields: any[] }[]> = {
    1: [
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
                    placeholder: "如：从月薪三千到大厂总监",
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
                    placeholder: "如：想吸引创业者、宝妈、年轻人",
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
    2: [
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
                    placeholder: "如：财税一体化、获客系统",
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
    3: [
        {
            title: "门店招牌与人设",
            fields: [
                { key: "store_name", type: FieldType.INPUT, label: "门店名称+所在商圈", placeholder: "请输入店铺名称" },
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
                    placeholder: "如：为了让大家吃到正宗家乡味",
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
                    placeholder: "如：年轻人、宝妈、职场精英",
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
};

// ─── 表单数据 ────────────────────────────────────────────────────
const formData = reactive<Record<string, any>>({
    id: "",
    persona_type: 1,
    persona_name: "",
    avatar_url: "",
    // 个人IP
    nickname: "",
    identity: [],
    personality_tags: [],
    core_value: "",
    highlight_story: "",
    target_audience: "",
    monetize_paths: [],
    // 企业服务
    brand_name: "",
    spokesperson: [],
    brand_tone: [],
    main_product: "",
    industry_case: "",
    target_customer: "",
    account_goal: [],
    // 本地商家
    store_name: "",
    store_spokesperson: [],
    store_atmosphere: [],
    signature_feature: "",
    open_story: "",
    content_preference: [],
});

// ─── 当前板块（联动 persona_type）────────────────────────────────
const currentPlate = computed(() => ({
    plates: PLATE_CONFIG[formData.persona_type] ?? [],
}));

// 用于模板 v-model 绑定
const currentSubForm = computed(() => formData);

// ─── 切换类型（重置当前类型下的字段）────────────────────────────
const RESET_MAP: Record<number, Record<string, any>> = {
    1: {
        nickname: "",
        identity: [],
        personality_tags: [],
        core_value: "",
        highlight_story: "",
        target_audience: "",
        monetize_paths: [],
    },
    2: {
        brand_name: "",
        spokesperson: [],
        brand_tone: [],
        main_product: "",
        industry_case: "",
        target_customer: "",
        account_goal: [],
    },
    3: {
        store_name: "",
        store_spokesperson: [],
        store_atmosphere: [],
        signature_feature: "",
        open_story: "",
        target_customer: "",
        content_preference: [],
    },
};

const handleTypeChange = (type: number) => {
    if (formData.persona_type === type) return;
    formData.persona_type = type;
    // Object.assign(formData, RESET_MAP[type]);

    // 切换类型后，tags 字段默认选中第一个选项
    const plates = PLATE_CONFIG[type] ?? [];
    for (const plate of plates) {
        for (const field of plate.fields) {
            if (field.type === FieldType.TAGS && field.options?.length) {
                formData[field.key] = [field.options[0]];
            }
        }
    }
};

// ─── 标签交互 ────────────────────────────────────────────────────
const isActive = (key: string, item: string): boolean => {
    const value = formData[key];
    return Array.isArray(value) ? value.includes(item) : value === item;
};

const toggleOption = (key: string, item: string): void => {
    const arr: string[] = Array.isArray(formData[key]) ? [...formData[key]] : [];
    const index = arr.indexOf(item);
    formData[key] = index > -1 ? arr.filter((v) => v !== item) : [...arr, item];
};

// ─── 表单校验 ─────────────────────────────────────────────────────
const validate = (): boolean => {
    for (const plate of currentPlate.value.plates) {
        for (const field of plate.fields) {
            const val = formData[field.key];
            if (val === undefined || val === "" || (Array.isArray(val) && val.length === 0)) {
                feedback.msgWarning(`请完善「${field.label}」`);
                return false;
            }
        }
    }
    return true;
};

// ─── 检查内容长度（少于300字时弹窗确认）─────────────────────────────
const checkContentLength = (): Promise<boolean> => {
    const MIN_TOTAL_LENGTH = 300;
    let totalLength = 0;

    for (const plate of currentPlate.value.plates) {
        for (const field of plate.fields) {
            if (field.type === FieldType.INPUT || field.type === FieldType.TEXTAREA) {
                const val = formData[field.key];
                if (typeof val === "string") totalLength += val.trim().length;
            }
        }
    }

    if (totalLength >= MIN_TOTAL_LENGTH) return Promise.resolve(true);

    return new Promise((resolve) => {
        ElMessageBox.confirm("当前内容过少，如果生成报告准确性将不确定，是否继续生成？", "内容过少", {
            confirmButtonText: "确定并生成",
            cancelButtonText: "继续修改",
        })
            .then(() => resolve(true))
            .catch(() => resolve(false));
    });
};

// ─── 组装提交 Payload ─────────────────────────────────────────────
const buildPayload = () => {
    const base = {
        persona_name: formData.persona_name,
        persona_type: formData.persona_type,
        avatar_url: formData.avatar_url,
    };

    if (formData.persona_type === 1) {
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

    if (formData.persona_type === 2) {
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

// ─── ✅ 报告相关字段定义（按类型）────────────────────────────────
/**
 * 只有这些字段发生变化时，才需要重新触发报告生成
 * persona_type 也纳入，切换类型必然触发重新生成
 */
const REPORT_RELATED_FIELDS: Record<number, string[]> = {
    1: [
        "persona_type",
        "nickname",
        "identity",
        "personality_tags",
        "core_value",
        "highlight_story",
        "target_audience",
        "monetize_paths",
    ],
    2: [
        "persona_type",
        "brand_name",
        "spokesperson",
        "brand_tone",
        "main_product",
        "industry_case",
        "target_customer",
        "account_goal",
    ],
    3: [
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

// 原始快照，open 时由 setFormData 赋值
const originSnapshot = ref<Record<string, any> | null>(null);

/**
 * 数组顺序不敏感的深比较
 */
const isEqual = (a: unknown, b: unknown): boolean => {
    if (Array.isArray(a) && Array.isArray(b)) {
        if (a.length !== b.length) return false;
        return [...a].sort().join(",") === [...b].sort().join(",");
    }
    return a === b;
};

/**
 * ✅ 检测当前 formData 与接口原始数据是否有变更
 * 任意报告相关字段变化即返回 true
 */
const hasFormChanged = (): boolean => {
    if (!originSnapshot.value) return true;
    const fields = REPORT_RELATED_FIELDS[formData.persona_type] ?? [];
    return fields.some((key) => !isEqual(formData[key], originSnapshot.value![key]));
};

// ─── 提交 ────────────────────────────────────────────────────────
const handleSubmit = async () => {
    if (!validate()) return;

    // ✅ 提前计算，后续逻辑统一使用
    const needGenerateReport = hasFormChanged();

    // 只在需要重新生成报告时才做内容长度检查
    if (needGenerateReport) {
        const shouldContinue = await checkContentLength();
        if (!shouldContinue) return;
    }

    await editPersona({
        id: formData.id,
        is_create_report: needGenerateReport ? 1 : 0,
        ...buildPayload(),
    });

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
            persona_id: formData.id,
            // @ts-ignore
            model: { 1: 4, 2: 5, 3: 6 }[formData.persona_type],
            contents,
        }).then(() => {
            getClueTouch({ id: formData.id });
            getWechatConfig({ id: formData.id });
        });
    }

    popupRef.value?.close();
    emit("success");
};

const handleClose = () => {
    emit("close");
};

// ─── 对外暴露 ────────────────────────────────────────────────────
const open = () => {
    popupRef.value?.open();
};

defineExpose({
    open,
    setFormData: (data: Record<string, any>) => {
        setFormData(data, formData);
        if (formData.persona_type === 3) {
            formData.store_spokesperson = data.spokesperson;
        }
        const fields = REPORT_RELATED_FIELDS[data.persona_type] ?? [];
        const snapshot: Record<string, any> = {};
        fields.forEach((key) => {
            const val = formData[key];
            snapshot[key] = Array.isArray(val) ? [...val] : val;
        });
        originSnapshot.value = snapshot;
    },
});
</script>

<style scoped>
:deep(.el-textarea__inner) {
    border-radius: 8px;
    background: #fff;
    resize: none;
}
:deep(.el-input__wrapper) {
    border-radius: 8px;
    background: #fff;
}
</style>
