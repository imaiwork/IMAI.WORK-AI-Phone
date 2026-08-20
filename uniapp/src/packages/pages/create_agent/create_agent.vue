<template>
    <view class="min-h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            :title="navTitle"
            title-bold
            back-icon-color="#1D2129" />

        <scroll-view scroll-y class="flex-1 min-h-0">
            <view class="flex flex-col items-center pt-[36rpx] pb-[40rpx]">
                <view class="avatar-big" @click="handleChooseAvatar">
                    <image v-if="avatarPreview" :src="avatarPreview" class="avatar-img" mode="aspectFill" />
                    <view v-else class="avatar-placeholder">
                        <u-icon name="camera-fill" color="#B6C2D9" :size="46" />
                    </view>
                    <view class="avatar-edit">
                        <u-icon name="plus" color="#ffffff" :size="22" />
                    </view>
                </view>
            </view>

            <view class="card">
                <view class="field-row">
                    <text class="field-label">名称</text>
                    <input v-model="formData.name" class="field-input" placeholder="输入名称" maxlength="30" />
                </view>
                <view class="field-row no-border">
                    <text class="field-label">简介</text>
                    <input
                        v-model="formData.intro"
                        class="field-input"
                        placeholder="一句话描述你的智能体"
                        maxlength="500" />
                </view>
            </view>

            <view class="card">
                <view class="field">
                    <view class="flex items-center justify-between">
                        <text class="field-title">提示词</text>
                        <view class="field-action" @click="handleFillExample">
                            <u-icon name="bulb" color="#2F73F6" :size="26" />
                            <text class="text-[26rpx] text-[#2F73F6] ml-[4rpx]">填入示例</text>
                        </view>
                    </view>
                    <textarea
                        v-model="formData.roles_prompt"
                        class="field-textarea"
                        :maxlength="-1"
                        placeholder="请输入详细的提示词..." />
                </view>
            </view>

            <view class="block-title">
                <view class="block-bar"></view>
                <text class="block-bar-text">初始引导</text>
            </view>
            <view class="card">
                <view class="field no-border">
                    <text class="field-title">对话欢迎语</text>
                    <text class="field-sub">
                        用户进入对话窗口时显示的开场白。添加双井号（如 #示例问题#）可快速生成引导词。
                    </text>
                    <textarea
                        v-model="formData.welcome_introducer"
                        class="field-textarea !min-h-[140rpx]"
                        maxlength="500"
                        placeholder="你好！我是你的 AI 助理，你可以试着问我：#帮我写一则关于夏天的文案#" />
                    <view class="text-right text-[22rpx] text-[#C0C8D8]">
                        {{ formData.welcome_introducer.length }}/500
                    </view>
                </view>
            </view>

            <view class="card">
                <view class="row no-border" @click="showModel = true">
                    <view class="row-icon bg-[#EBF2FF]">
                        <u-icon name="grid" color="#2F73F6" :size="28" />
                    </view>
                    <text class="row-label">模型</text>
                    <text class="row-value">{{ currModelLabel }}</text>
                    <u-icon name="arrow-right" color="#C0C8D8" :size="26" />
                </view>
            </view>

            <view class="adv-toggle" :class="{ open: showAdvanced }" @click="showAdvanced = !showAdvanced">
                <u-icon name="plus" color="#2F73F6" :size="28" />
                <text class="text-[30rpx] font-semibold text-[#2F73F6]">
                    {{ showAdvanced ? "收起进阶设置" : "进阶设置" }}
                </text>
            </view>

            <view v-show="showAdvanced">
                <text class="sec-title">知识库配置</text>
                <view class="card">
                    <!-- 搜索并选择知识库（对齐设计稿） -->
                    <view class="row" @click="showKbSelect = true">
                        <view class="row-icon bg-[#F0FDF4]">
                            <u-icon name="file-text" color="#16A34A" :size="28" />
                        </view>
                        <text class="row-label">知识库</text>
                        <text class="row-value" :class="{ 'row-value--on': selectedKbList.length }">
                            {{ kbSelectLabel }}
                        </text>
                        <u-icon name="arrow-right" color="#C0C8D8" :size="26" />
                    </view>

                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">引用 Tokens 上限</text>
                            <text class="slider-val">{{ formData.search_tokens }}</text>
                        </view>
                        <slider
                            :value="formData.search_tokens"
                            :min="100"
                            :max="20000"
                            :step="100"
                            activeColor="#2F73F6"
                            backgroundColor="#E5E9F0"
                            block-size="18"
                            @changing="formData.search_tokens = $event.detail.value"
                            @change="formData.search_tokens = $event.detail.value" />
                    </view>

                    <view class="group-title">
                        召回策略
                        <text class="group-sub">控制 AI 从知识库中检索内容的方式</text>
                    </view>

                    <view class="field">
                        <text class="field-label">检索模式</text>
                        <view class="seg">
                            <view
                                v-for="item in SEARCH_MODES"
                                :key="item.value"
                                class="seg-btn"
                                :class="{ sel: formData.search_mode === item.value }"
                                @click="formData.search_mode = item.value">
                                {{ item.label }}
                            </view>
                        </view>
                    </view>

                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">引用上下文条数</text>
                            <text class="slider-val">{{ formData.context_num }}</text>
                        </view>
                        <slider
                            :value="formData.context_num"
                            :min="0"
                            :max="5"
                            :step="1"
                            activeColor="#2F73F6"
                            backgroundColor="#E5E9F0"
                            block-size="18"
                            @changing="formData.context_num = $event.detail.value"
                            @change="formData.context_num = $event.detail.value" />
                    </view>

                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">最低相似度</text>
                            <text class="slider-val">{{ Number(formData.search_similar || 0).toFixed(2) }}</text>
                        </view>
                        <!-- 小程序 scroll-view 内浮点 step 易失灵：用 0~100 整数滑动，外层拦住纵向滚动 -->
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.search_similar || 0) * 100)"
                                :min="0"
                                :max="100"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.search_similar = Number(($event.detail.value / 100).toFixed(2))"
                                @change="formData.search_similar = Number(($event.detail.value / 100).toFixed(2))" />
                        </view>
                    </view>

                    <view class="row tgl-row" :class="{ 'no-border': formData.ranking_status !== 1 }">
                        <view class="tgl-info">
                            <text class="tgl-title">语义重排</text>
                            <text class="tgl-desc">开启后对检索内容进行二次精密排序，建议混合检索时开启。</text>
                        </view>
                        <u-switch v-model="formData.ranking_status" :active-value="1" :inactive-value="0" size="44" />
                    </view>
                    <view v-if="formData.ranking_status === 1" class="field no-border">
                        <view class="flex items-center justify-between">
                            <text class="field-label">重排过滤分数</text>
                            <text class="slider-val">{{ Number(formData.ranking_score || 0).toFixed(3) }}</text>
                        </view>
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.ranking_score || 0) * 1000)"
                                :min="0"
                                :max="1000"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.ranking_score = Number(($event.detail.value / 1000).toFixed(3))"
                                @change="formData.ranking_score = Number(($event.detail.value / 1000).toFixed(3))" />
                        </view>
                    </view>

                    <view class="group-title">
                        兜底策略
                        <text class="group-sub">未命中知识库时 AI 如何回复</text>
                    </view>
                    <view class="field no-border">
                        <view class="radio-row !mt-0">
                            <view
                                v-for="item in EMPTY_TYPES"
                                :key="item.value"
                                class="radio-item"
                                :class="{ selected: formData.search_empty_type === item.value }"
                                @click="formData.search_empty_type = item.value">
                                <view class="radio-dot"></view>
                                <text class="radio-text">{{ item.label }}</text>
                            </view>
                        </view>
                        <textarea
                            v-if="formData.search_empty_type === EmptyType.CUSTOM"
                            v-model="formData.search_empty_text"
                            class="field-textarea !min-h-[120rpx] mt-[16rpx]"
                            maxlength="500"
                            placeholder="当搜索无结果时，回复此处内容..." />
                    </view>
                </view>

                <text class="sec-title">拟人化 · 模型参数</text>
                <view class="card">
                    <view class="field">
                        <text class="field-label">回复风格</text>
                        <view class="seg">
                            <view
                                v-for="item in MODE_TYPES"
                                :key="item.value"
                                class="seg-btn"
                                :class="{ sel: formData.mode_type === item.value }"
                                @click="handleChangeMode(item.value)">
                                {{ item.label }}
                            </view>
                        </view>
                        <text class="field-sub">{{ modeDesc }}</text>
                    </view>

                    <!-- 与 PC 拟人化一致：各模式均展示参数，切换预设会写入对应默认值 -->
                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">上下文记忆条数</text>
                            <text class="slider-val">{{ formData.context_num }}</text>
                        </view>
                        <slider
                            :value="formData.context_num"
                            :min="0"
                            :max="5"
                            :step="1"
                            activeColor="#2F73F6"
                            backgroundColor="#E5E9F0"
                            block-size="18"
                            @changing="formData.context_num = $event.detail.value"
                            @change="formData.context_num = $event.detail.value" />
                    </view>
                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">最大回复长度 (Max Tokens)</text>
                            <text class="slider-val">{{ formData.max_tokens }}</text>
                        </view>
                        <slider
                            :value="formData.max_tokens"
                            :min="1"
                            :max="getMaxTokens"
                            :step="1"
                            activeColor="#2F73F6"
                            backgroundColor="#E5E9F0"
                            block-size="18"
                            @changing="formData.max_tokens = $event.detail.value"
                            @change="formData.max_tokens = $event.detail.value" />
                    </view>
                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">结果随机性 (Temperature)</text>
                            <text class="slider-val">{{ Number(formData.temperature || 0).toFixed(1) }}</text>
                        </view>
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.temperature || 0) * 10)"
                                :min="1"
                                :max="Math.round(getMaxTemperature * 10)"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.temperature = Number(($event.detail.value / 10).toFixed(1))"
                                @change="formData.temperature = Number(($event.detail.value / 10).toFixed(1))" />
                        </view>
                    </view>
                    <view v-if="currModel.model_id != ModelIdEnum.CLAUDE_SONNET_4_5" class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">核采样 (Top P)</text>
                            <text class="slider-val">{{ Number(formData.top_p || 0).toFixed(1) }}</text>
                        </view>
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.top_p || 0) * 10)"
                                :min="1"
                                :max="10"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.top_p = Number(($event.detail.value / 10).toFixed(1))"
                                @change="formData.top_p = Number(($event.detail.value / 10).toFixed(1))" />
                        </view>
                    </view>
                    <view class="field">
                        <view class="flex items-center justify-between">
                            <text class="field-label">重复惩罚 (Frequency Penalty)</text>
                            <text class="slider-val">{{ Number(formData.frequency_penalty || 0).toFixed(1) }}</text>
                        </view>
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.frequency_penalty || 0) * 10)"
                                :min="-20"
                                :max="20"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.frequency_penalty = Number(($event.detail.value / 10).toFixed(1))"
                                @change="formData.frequency_penalty = Number(($event.detail.value / 10).toFixed(1))" />
                        </view>
                    </view>
                    <view class="field" :class="{ 'no-border': !formData.logprobs }">
                        <view class="flex items-center justify-between">
                            <text class="field-label">存在惩罚 (Presence Penalty)</text>
                            <text class="slider-val">{{ Number(formData.presence_penalty || 0).toFixed(1) }}</text>
                        </view>
                        <view @touchmove.stop.prevent>
                            <slider
                                :value="Math.round(Number(formData.presence_penalty || 0) * 10)"
                                :min="0"
                                :max="10"
                                :step="1"
                                activeColor="#2F73F6"
                                backgroundColor="#E5E9F0"
                                block-size="18"
                                @changing="formData.presence_penalty = Number(($event.detail.value / 10).toFixed(1))"
                                @change="formData.presence_penalty = Number(($event.detail.value / 10).toFixed(1))" />
                        </view>
                        <text class="field-sub">部分模型（如 DeepSeek）可能不支持存在惩罚、重复惩罚参数</text>
                    </view>
                    <view v-if="currModel.model_id != ModelIdEnum.DEEPSEEK" class="row tgl-row no-border">
                        <view class="tgl-info">
                            <text class="tgl-title">对数概率分析 (Logprobs)</text>
                            <text class="tgl-desc">显示模型输出词汇的概率分布，通常用于精细化调试。</text>
                        </view>
                        <u-switch v-model="formData.logprobs" :active-value="1" :inactive-value="0" size="44" />
                    </view>
                    <view
                        v-if="currModel.model_id != ModelIdEnum.DEEPSEEK && formData.logprobs"
                        class="field no-border">
                        <view class="flex items-center justify-between">
                            <text class="field-label">候选词对数概率展示数量</text>
                            <text class="slider-val">{{ formData.top_logprobs }}</text>
                        </view>
                        <slider
                            :value="formData.top_logprobs"
                            :min="0"
                            :max="20"
                            :step="1"
                            activeColor="#2F73F6"
                            backgroundColor="#E5E9F0"
                            block-size="18"
                            @changing="formData.top_logprobs = $event.detail.value"
                            @change="formData.top_logprobs = $event.detail.value" />
                    </view>
                </view>
            </view>

            <view class="h-[calc(180rpx+env(safe-area-inset-bottom))]"></view>
        </scroll-view>

        <view class="bottom-cta">
            <view class="cta-btn" :class="{ 'cta-btn--loading': isLock }" @click="lockFn">
                <text class="text-white font-bold text-[32rpx]">{{ ctaText }}</text>
            </view>
        </view>

        <popup-bottom v-model="showModel" title="选择模型" height="55%" custom-class="bg-[#F4F6FB]">
            <template #content>
                <scroll-view scroll-y class="h-full">
                    <view class="px-[32rpx] pt-[24rpx] pb-[48rpx]">
                        <view class="grid grid-cols-2 gap-[20rpx]">
                            <view
                                v-for="item in aiModels"
                                :key="item.id"
                                class="model-cell"
                                :class="{ 'model-cell--active': currModel.id == item.id }"
                                @click="handleSelectModel(item)">
                                <image
                                    v-if="item.logo"
                                    :src="item.logo"
                                    class="w-[48rpx] h-[48rpx] rounded-[14rpx]"
                                    mode="aspectFill" />
                                <text
                                    class="flex-1 text-[24rpx] font-semibold line-clamp-1"
                                    :class="currModel.id == item.id ? 'text-primary' : 'text-[#424242]'">
                                    {{ item.name }}
                                </text>
                                <view
                                    v-if="currModel.id == item.id"
                                    class="w-[16rpx] h-[16rpx] rounded-full bg-primary flex-shrink-0"></view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </template>
        </popup-bottom>

        <kb-select-popup v-model="showKbSelect" :selected="selectedKbList" @confirm="handleKbConfirm" />
    </view>
</template>

<script lang="ts" setup>
import { createAgent, updateAgent, getAgentDetail } from "@/api/agent";
import { vectorKnowledgeBaseLists } from "@/api/knowledge_base";
import { useAppStore } from "@/stores/app";
import { ModelIdEnum } from "@/enums/appEnums";
import { setFormData } from "@/utils/util";
import { useLockFn } from "@/hooks/useLockFn";
import useUpload from "@/hooks/useUpload";
import KbSelectPopup from "./components/kb-select-popup.vue";

// 知识库类型：2=向量库（与 PC KnbTypeEnum.VECTOR 对齐）
const KB_TYPE_VECTOR = 2;

enum SearchMode {
    MIX = "mix",
    SIMILAR = "similar",
    FULL = "full",
}
const SEARCH_MODES = [
    { label: "混合检索", value: SearchMode.MIX },
    { label: "语义检索", value: SearchMode.SIMILAR },
    { label: "全文检索", value: SearchMode.FULL },
];

enum EmptyType {
    AI = 1,
    CUSTOM = 2,
}
const EMPTY_TYPES = [
    { label: "AI 自由发挥", value: EmptyType.AI },
    { label: "指定自定义内容", value: EmptyType.CUSTOM },
];

enum ModeType {
    CUSTOM = 1,
    BALANCE = 2,
    PRECISE = 3,
    CREATIVE = 4,
}
const MODE_TYPES = [
    { label: "平衡", value: ModeType.BALANCE },
    { label: "精准", value: ModeType.PRECISE },
    { label: "创意", value: ModeType.CREATIVE },
    { label: "自定义", value: ModeType.CUSTOM },
];
const MODE_DESC: Record<number, string> = {
    [ModeType.BALANCE]: "平衡模式：兼顾准确性与创造力，适合大多数对话场景",
    [ModeType.PRECISE]: "精准模式：低随机性，回复严谨、准确度高，适合知识查询和数据分析",
    [ModeType.CREATIVE]: "创意模式：高随机性，回复发散、富有想象力，适合文案创作和头脑风暴",
    [ModeType.CUSTOM]: "自定义模式：手动调整所有参数，建议有经验的用户使用",
};

const PROMPT_EXAMPLE = `角色：

你是一个售后客服。

性格热情耐心，表达简洁、亲切，能体贴地安抚客户情绪。

职责：为用户解答产品和服务相关的售后问题。

目标：快速、准确地帮助客户解决问题。

回答主题

仅回答服务相关的问题：

包括系统使用、账户问题、售后支持、功能咨询等。

工作流程

直接回答客户的问题。

根据知识库内容进行解答。

若知识库中无相关答案，统一回复：

“关于这个问题，我建议您添加人工客服，人工客服会为您提供最专业的解答。”

限制

严禁编造或推断知识库外的信息。

禁止输出敏感或违法内容。

回复规范

风格：亲切、专业、简短、自然。

语言：与客户一致。

长度：尽可能简洁。

格式：纯文本，无代码或符号格式。

避免生硬表达，使用自然口语化语气。`;

const appStore = useAppStore();
// 用户未选头像时，使用网站配置的 shop_logo（服务端 URL，小程序无需上传）
const defaultAvatar = computed(() => appStore.getWebsiteConfig.shop_logo || "");
const avatarPreview = computed(() => formData.image || defaultAvatar.value);

const aiModels = computed(() => appStore.getAllowedChatModel);
const allChatModels = computed(() => appStore.getChatModel);

/** 详情接口返回的模型展示名（model 字段不在 formData 里） */
const savedModelName = ref("");
const detailLoaded = ref(false);

/** 在 channel 中按 model_id / model_sub_id / id 查找模型 */
const findModelInChannel = (list: any[], modelId?: string | number, modelSubId?: string | number) => {
    if (!list.length || modelId == null || modelId === "") return null;
    return (
        list.find((m: any) => m.model_id == modelId && m.model_sub_id == modelSubId) ||
        list.find((m: any) => m.model_id == modelId) ||
        list.find((m: any) => m.id == modelId) ||
        null
    );
};

const currModelId = ref<string | number>("");
const currModel = computed(() => {
    if (currModelId.value) {
        const byId = allChatModels.value.find((m: any) => m.id == currModelId.value);
        if (byId) return byId;
    }
    const matched = findModelInChannel(allChatModels.value, formData.model_id, formData.model_sub_id);
    if (matched) return matched;
    if (savedModelName.value) {
        return { name: savedModelName.value, model_id: formData.model_id, model_sub_id: formData.model_sub_id };
    }
    return {} as any;
});

const currModelLabel = computed(() => currModel.value?.name || savedModelName.value || "请选择模型");

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    imageResolution: [99999, 99999],
    onSuccess: (materials) => {
        if (materials[0]) formData.image = materials[0].url;
    },
});

const formData = reactive({
    name: "",
    intro: "",
    image: "",
    bg_image: "",
    icons: "",
    model_id: "" as string | number,
    model_sub_id: "" as string | number,
    roles_prompt: "",
    welcome_introducer: "",
    copyright: "",
    // 知识库
    kb_type: KB_TYPE_VECTOR,
    kb_ids: [] as (string | number)[],
    search_mode: SearchMode.SIMILAR as string,
    search_tokens: 3000,
    search_similar: 0.4,
    context_num: 3,
    ranking_status: 0,
    ranking_score: 0.5,
    optimize_ask: 0,
    optimize_m_id: "",
    optimize_s_id: "",
    search_empty_type: EmptyType.AI as number,
    search_empty_text: "",
    // 拟人化
    mode_type: ModeType.BALANCE as number,
    max_tokens: 4096,
    temperature: 0.6,
    top_p: 0.9,
    presence_penalty: 0.2,
    frequency_penalty: 0.2,
    logprobs: 0,
    top_logprobs: 10,
    // 其他
    is_public: 0,
    is_enable: 1,
    menus: [] as any[],
    threshold: 0.5,
});

const showModel = ref(false);
const showAdvanced = ref(false);
const showKbSelect = ref(false);
const selectedKbList = ref<any[]>([]);

const kbSelectLabel = computed(() => {
    if (!selectedKbList.value.length) return "搜索并选择知识库";
    if (selectedKbList.value.length === 1) return selectedKbList.value[0].name;
    return `已选 ${selectedKbList.value.length} 个知识库`;
});

const editId = ref<string | number>("");
const navTitle = computed(() => (editId.value ? "编辑智能体" : "新建智能体"));
const ctaText = computed(() => (editId.value ? "保存" : "完成创建"));

const modeDesc = computed(() => MODE_DESC[formData.mode_type] || "");

const getMaxTemperature = computed(() => (currModel.value.model_id == ModelIdEnum.DEEPSEEK ? 2 : 1));
const getMaxTokens = computed(() => (currModel.value.model_id == ModelIdEnum.DEEPSEEK ? 4096 : 10000));

const normalizeKbIds = (raw: any): string[] => {
    if (Array.isArray(raw)) return raw.map(String).filter(Boolean);
    if (typeof raw === "string") {
        const trimmed = raw.trim();
        if (!trimmed) return [];
        if (trimmed.startsWith("[")) {
            try {
                const parsed = JSON.parse(trimmed);
                return Array.isArray(parsed) ? parsed.map(String) : [];
            } catch {
                return trimmed
                    .split(",")
                    .map((s) => s.trim())
                    .filter(Boolean);
            }
        }
        return trimmed
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
    }
    if (raw == null || raw === "") return [];
    return [String(raw)];
};

/**
 * 按 model_id 回填展示模型（从全量 channel 查找，不限会员允许列表）
 */
const syncSelectedModel = (): boolean => {
    if (!formData.model_id || !allChatModels.value.length) return !!savedModelName.value;
    const matched = findModelInChannel(allChatModels.value, formData.model_id, formData.model_sub_id);
    if (!matched) return !!savedModelName.value;
    currModelId.value = matched.id;
    if (!formData.model_sub_id && matched.model_sub_id) {
        formData.model_sub_id = matched.model_sub_id;
    }
    if (formData.model_id != matched.model_id) {
        formData.model_id = matched.model_id;
    }
    return true;
};

/** 编辑：保留已保存模型展示；新建：默认选允许列表第一项 */
const ensureModelSelection = (): void => {
    if (editId.value) {
        if (!detailLoaded.value) return;
        syncSelectedModel();
        return;
    }
    if (syncSelectedModel()) return;
    if (aiModels.value[0]) handleSelectModel(aiModels.value[0], false);
};

const syncSelectedKbList = async (ids: string[]) => {
    formData.kb_ids = ids;
    if (!ids.length) {
        selectedKbList.value = [];
        return;
    }
    try {
        const { lists } = await vectorKnowledgeBaseLists({ page_no: 1, page_size: 25000 });
        const idSet = new Set(ids.map(String));
        selectedKbList.value = (lists || []).filter((item: any) => idSet.has(String(item.id)));
    } catch (error) {
        selectedKbList.value = ids.map((id) => ({ id, name: `知识库 ${id}` }));
    }
};

const handleKbConfirm = (list: any[]) => {
    selectedKbList.value = list;
    formData.kb_ids = list.map((item) => String(item.id));
};

const handleSelectModel = (item: any, close = true) => {
    currModelId.value = item.id;
    formData.model_id = item.model_id;
    formData.model_sub_id = item.model_sub_id;
    if (close) showModel.value = false;
};

const loadDetail = async (id: string | number) => {
    uni.showLoading({ title: "加载中...", mask: true });
    try {
        // 编辑进入时先刷配额,避免详情回填模型时用到旧列表
        await appStore.ensureMemberQuota(true);
        const data: any = await getAgentDetail({ id });
        // 团队共享智能体:非创建者不可编辑
        if (Number(data.is_owner) !== 1) {
            uni.$u.toast("仅创建者可编辑该智能体");
            setTimeout(() => uni.navigateBack(), 500);
            return;
        }
        savedModelName.value = String(data.model || "");
        setFormData(data, formData);
        // 接口数字字段可能是字符串，进入滑块前统一归一化
        const numberKeys = [
            "search_tokens",
            "search_similar",
            "context_num",
            "ranking_status",
            "ranking_score",
            "optimize_ask",
            "search_empty_type",
            "mode_type",
            "max_tokens",
            "temperature",
            "top_p",
            "presence_penalty",
            "frequency_penalty",
            "logprobs",
            "top_logprobs",
        ] as const;
        numberKeys.forEach((key) => {
            (formData as any)[key] = Number((formData as any)[key]) || 0;
        });
        formData.roles_prompt = String(formData.roles_prompt || "");
        formData.welcome_introducer = String(formData.welcome_introducer || "");
        if (!formData.mode_type) formData.mode_type = ModeType.BALANCE;
        if (!formData.search_empty_type) formData.search_empty_type = EmptyType.AI;
        // 回填知识库选中态
        await syncSelectedKbList(normalizeKbIds(formData.kb_ids));
        detailLoaded.value = true;
        ensureModelSelection();
    } finally {
        uni.hideLoading();
    }
};

onLoad((options: any) => {
    if (options?.id) {
        editId.value = options.id;
        loadDetail(options.id);
    }
});

onMounted(async () => {
    // 新建进入时刷配额;编辑由 loadDetail 负责,避免重复请求
    if (!editId.value) {
        await appStore.ensureMemberQuota(true);
        ensureModelSelection();
    }
});

watch([allChatModels, aiModels, () => formData.model_id], ensureModelSelection);

const handleChooseAvatar = () => {
    uploadAndProcessFiles("image");
};

const handleFillExample = () => {
    formData.roles_prompt = PROMPT_EXAMPLE;
};

const handleChangeMode = (type: ModeType) => {
    formData.mode_type = type;
    // 与 PC humanize-setting 预设一致；自定义模式保留当前参数
    if (type === ModeType.BALANCE) {
        Object.assign(formData, { top_p: 0.9, temperature: 0.6, presence_penalty: 0.2, frequency_penalty: 0.2 });
    } else if (type === ModeType.PRECISE) {
        Object.assign(formData, { top_p: 0.8, temperature: 0.3, presence_penalty: 0, frequency_penalty: 0 });
    } else if (type === ModeType.CREATIVE) {
        Object.assign(formData, { top_p: 1, temperature: 0.9, presence_penalty: 0.5, frequency_penalty: 0.3 });
    }
};

const MODEL_NOT_ALLOWED_TIP = "当前模型不在会员等级可用范围内，请重新选择";

/** 当前 model 是否在会员等级允许列表内 */
const isModelAllowed = (modelId?: string | number, modelSubId?: string | number) => {
    if (modelId == null || modelId === "") return false;
    return aiModels.value.some((m: any) => m.model_id == modelId);
};

const validate = () => {
    if (!formData.name.trim()) return "请输入智能体名称";
    if (!formData.intro.trim()) return "请输入智能体简介";
    if (!formData.model_id) return "请选择模型";
    return "";
};

/** 不可用模型时弹窗提示，确认后打开模型选择 */
const confirmReselectModel = () =>
    new Promise<boolean>((resolve) => {
        uni.showModal({
            title: "提示",
            content: MODEL_NOT_ALLOWED_TIP,
            confirmText: "去选择",
            cancelText: "取消",
            success: ({ confirm }) => {
                if (confirm) showModel.value = true;
                resolve(confirm);
            },
            fail: () => resolve(false),
        });
    });

const handleSubmit = async () => {
    await appStore.ensureMemberQuota(true);
    const tip = validate();
    if (tip) {
        uni.$u.toast(tip);
        return;
    }
    if (!isModelAllowed(formData.model_id, formData.model_sub_id)) {
        await confirmReselectModel();
        return;
    }
    const isEdit = !!editId.value;
    uni.showLoading({ title: isEdit ? "保存中..." : "创建中...", mask: true });
    try {
        // 用户未选头像时回退到网站 shop_logo（服务端 URL，小程序无需上传）
        // context_num 后端 edit 校验上限为 5，提交前钳制，避免保存失败
        const payload = {
            ...formData,
            image: formData.image || defaultAvatar.value,
            kb_ids: selectedKbList.value.map((item) => String(item.id)),
            context_num: Math.min(5, Math.max(0, Number(formData.context_num) || 0)),
        };
        if (isEdit) {
            await updateAgent({ ...payload, id: editId.value });
        } else {
            // 旧版 add 只建壳丢字段：创建后立刻 edit 写全量；新版 add 已落全字段，二次 edit 幂等
            const res: any = await createAgent(payload);
            const newId = res?.id;
            if (!newId) {
                throw "创建失败，未返回智能体 ID";
            }
            await updateAgent({ ...payload, id: newId });
        }
        uni.hideLoading();
        uni.showToast({ title: isEdit ? "保存成功" : "创建成功", icon: "none", duration: 3000 });
        uni.$emit("agentCreated");
        setTimeout(() => uni.navigateBack(), 800);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

const { lockFn, isLock } = useLockFn(handleSubmit);
</script>

<style lang="scss" scoped>
.avatar-big {
    @apply w-[176rpx] h-[176rpx] rounded-full bg-white flex items-center justify-center relative;
    box-shadow: 0 8rpx 32rpx rgba(126, 82, 224, 0.18);
}
.avatar-img {
    @apply w-full h-full rounded-full;
}
.avatar-placeholder {
    @apply w-full h-full rounded-full flex items-center justify-center bg-[#F2F4FA];
}
.avatar-edit {
    @apply absolute right-[6rpx] bottom-[6rpx] w-[52rpx] h-[52rpx] rounded-full bg-[#2F73F6] flex items-center justify-center;
    box-shadow: 0 4rpx 16rpx rgba(47, 115, 246, 0.45);
}

.card {
    @apply bg-white rounded-[28rpx] mx-[24rpx] mb-[24rpx] overflow-hidden;
}

.field,
.field-row,
.row {
    @apply px-[32rpx] py-[28rpx] border-0 border-b border-solid border-[#F0F2F7];
}
.no-border {
    @apply border-b-0;
}

.field-row {
    @apply flex items-center gap-x-[24rpx];
}
.field-label {
    @apply text-[30rpx] font-semibold text-[#1D2129] flex-shrink-0;
}
.field-input {
    @apply flex-1 text-[28rpx] text-[#1D2129];
}
.field-title {
    @apply text-[32rpx] font-bold text-[#1D2129];
}
.field-sub {
    @apply block text-[22rpx] text-[#94A3B8] mt-[8rpx] leading-relaxed;
}
.field-action {
    @apply flex items-center active:opacity-60;
}
.field-textarea {
    @apply w-full text-[28rpx] text-[#1D2129] leading-relaxed mt-[20rpx] min-h-[180rpx];
}

.row {
    @apply flex items-center gap-x-[20rpx];
}
.row-icon {
    @apply w-[56rpx] h-[56rpx] rounded-[14rpx] flex items-center justify-center flex-shrink-0;
}
.row-label {
    @apply text-[30rpx] font-medium text-[#1D2129] flex-shrink-0;
}
.row-value {
    @apply flex-1 text-right text-[28rpx] text-[#9CA3AF] truncate;
}
.row-value--on {
    @apply text-[#1D2129];
}

.group-title {
    @apply px-[32rpx] pt-[28rpx] pb-[8rpx] text-[26rpx] font-bold text-[#1D2129];
}
.group-sub {
    @apply text-[22rpx] font-medium text-[#94A3B8] ml-[8rpx];
}

.adv-toggle {
    @apply flex items-center justify-center gap-x-[10rpx] mx-[24rpx] my-[12rpx] py-[24rpx] active:opacity-60;
    .u-icon {
        transition: transform 0.25s;
    }
    &.open .u-icon {
        transform: rotate(45deg);
    }
}

.sec-title {
    @apply block text-[24rpx] font-bold text-[#6B7280] px-[36rpx] pt-[16rpx] pb-[12rpx];
}

.block-title {
    @apply flex items-center gap-x-[12rpx] px-[36rpx] pt-[24rpx] pb-[16rpx];
}
.block-bar {
    @apply w-[8rpx] h-[32rpx] rounded-full bg-[#0065fb] flex-shrink-0;
}
.block-bar-text {
    @apply text-[30rpx] font-bold text-[#0F172A];
}

.seg {
    @apply flex bg-[#F1F3F6] rounded-[20rpx] p-[8rpx] mt-[20rpx];
}
.seg-btn {
    @apply flex-1 text-center py-[16rpx] rounded-[16rpx] text-[24rpx] font-bold text-[#9CA3AF] transition-all;
    &.sel {
        @apply bg-white text-[#1D2129];
        box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.08);
    }
}

.slider-val {
    @apply text-[26rpx] font-bold text-[#1D2129];
}

.tgl-row {
    @apply flex items-start;
}
.tgl-info {
    @apply flex-1 min-w-0 pr-[24rpx];
}
.tgl-title {
    @apply block text-[30rpx] font-semibold text-[#1D2129];
}
.tgl-desc {
    @apply block text-[24rpx] text-[#9CA3AF] mt-[6rpx] leading-relaxed;
}

.radio-row {
    @apply flex flex-wrap gap-x-[48rpx] gap-y-[20rpx] mt-[20rpx];
}
.radio-item {
    @apply flex items-center gap-x-[14rpx];
}
.radio-dot {
    @apply w-[34rpx] h-[34rpx] rounded-full border-[3rpx] border-solid border-[#D1D5DB] flex items-center justify-center flex-shrink-0;
}
.radio-item.selected .radio-dot {
    @apply border-[#2F73F6];
    &::after {
        content: "";
        @apply w-[18rpx] h-[18rpx] rounded-full bg-[#2F73F6];
    }
}
.radio-text {
    @apply text-[28rpx] text-[#4B5563];
}
.radio-item.selected .radio-text {
    @apply text-[#2F73F6] font-semibold;
}

.model-cell {
    @apply bg-white rounded-full px-[28rpx] py-[20rpx] flex items-center gap-x-[16rpx] border border-solid border-[#f9f9f9];
    &--active {
        @apply border-primary bg-[#EEF4FF];
    }
}

.bottom-cta {
    @apply fixed left-0 right-0 bottom-0 bg-white px-[32rpx] pt-[20rpx] z-20;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eaeef5;
}
.cta-btn {
    @apply w-full h-[96rpx] rounded-[28rpx] flex items-center justify-center active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 12rpx 40rpx rgba(47, 115, 246, 0.28);
    &--loading {
        @apply opacity-60;
    }
}
</style>
