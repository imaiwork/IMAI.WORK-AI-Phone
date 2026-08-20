<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <template v-if="!isGenerating">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[30rpx] font-bold text-[#0D1117]">分享主题</text>
                                    <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                                </view>
                                <view
                                    v-if="isSystemMode && personaLocked"
                                    class="flex items-center gap-[6rpx] px-[20rpx] py-[10rpx] rounded-full border border-solid border-[#BFDBFE] bg-[#EBF2FF]">
                                    <u-icon name="account-fill" color="#0065fb" size="18" />
                                    <text class="text-xs font-medium text-primary">
                                        {{ selectedPerson?.persona_name || "当前人设" }}
                                    </text>
                                </view>
                                <view
                                    v-else-if="isSystemMode"
                                    class="flex items-center gap-[8rpx] px-[20rpx] py-[10rpx] rounded-full border border-solid"
                                    :class="
                                        selectedPerson
                                            ? 'border-[#BFDBFE] bg-[#EBF2FF]'
                                            : 'border-[#E5E9F0] bg-[#F7F9FC]'
                                    "
                                    @click="openPersonPicker">
                                    <text
                                        class="text-xs font-medium"
                                        :class="selectedPerson ? 'text-primary' : 'text-[#9CA3AF]'">
                                        {{ selectedPerson?.persona_name || "选择人设 IP" }}
                                    </text>
                                    <u-icon
                                        name="arrow-down"
                                        :color="selectedPerson ? '#0065fb' : '#9CA3AF'"
                                        size="18" />
                                </view>
                            </view>
                            <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                                <textarea
                                    class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                    v-model="contentVal"
                                    :style="{ height: '320rpx' }"
                                    placeholder="点击输入您想生成的主题，如：北京旅游"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    :maxlength="CONTENT_MAX_LENGTH" />
                            </view>
                            <view class="flex justify-end px-[28rpx] pb-[20rpx]">
                                <text
                                    class="text-[22rpx]"
                                    :class="
                                        contentVal.length >= CONTENT_MAX_LENGTH
                                            ? 'text-[#EF4444] font-bold'
                                            : 'text-[#C0C4CC]'
                                    ">
                                    {{ contentVal.length }} / {{ CONTENT_MAX_LENGTH }}
                                </text>
                            </view>
                        </view>

                        <template v-if="isSystemMode">
                            <view
                                class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                <view class="flex items-center gap-[8rpx] mb-[20rpx]">
                                    <text class="font-bold text-[#0D1117]">口播字数</text>
                                    <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                                </view>
                                <view class="flex items-center gap-[16rpx]">
                                    <view
                                        v-for="item in PROMPT_OPTIONS"
                                        :key="item.id"
                                        class="flex-1 h-[76rpx] flex flex-col items-center justify-center rounded-[20rpx] transition-all duration-200"
                                        :class="
                                            currentPrompt?.id === item.id
                                                ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                : 'bg-[#F0F2F5]'
                                        "
                                        @click="currentPrompt = item">
                                        <text
                                            class="font-bold"
                                            :class="currentPrompt?.id === item.id ? 'text-primary' : 'text-[#9CA3AF]'">
                                            {{ item.name }}
                                        </text>
                                        <text
                                            class="text-[20rpx] mt-[2rpx]"
                                            :class="
                                                currentPrompt?.id === item.id ? 'text-[#0065fb]/60' : 'text-[#C0C4CC]'
                                            ">
                                            {{ item.length }}字
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <view
                                class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                <view class="flex items-center gap-[8rpx] mb-[20rpx]">
                                    <text class="font-bold text-[#0D1117]">生成数量</text>
                                    <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                                </view>
                                <view class="flex items-center gap-[12rpx] flex-wrap">
                                    <view
                                        v-for="item in PROMPT_NUM_OPTIONS"
                                        :key="item"
                                        class="w-[100rpx] h-[76rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                        :class="
                                            currentPromptNum === item
                                                ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                : 'bg-[#F0F2F5]'
                                        "
                                        @click="currentPromptNum = item">
                                        <text
                                            class="font-bold"
                                            :class="currentPromptNum === item ? 'text-primary' : 'text-[#9CA3AF]'">
                                            {{ item }}条
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </template>
                    </template>

                    <template v-else>
                        <view
                            v-if="isLoading"
                            class="gen-loading relative overflow-hidden rounded-[24rpx] px-[28rpx] py-[28rpx]">
                            <view class="flex items-center gap-[20rpx]">
                                <view class="relative w-[68rpx] h-[68rpx] flex-shrink-0">
                                    <view
                                        class="absolute inset-0 rounded-full border-[6rpx] border-solid border-[#D6E4FF]" />
                                    <view
                                        class="gen-spin absolute inset-0 rounded-full border-[6rpx] border-solid border-[transparent]"
                                        style="border-top-color: #0065fb" />
                                    <view class="absolute inset-0 flex items-center justify-center">
                                        <view class="gen-pulse w-[16rpx] h-[16rpx] rounded-full bg-primary" />
                                    </view>
                                </view>
                                <view class="flex-1 min-w-0">
                                    <view class="flex items-center gap-[8rpx]">
                                        <text class="text-[30rpx] font-extrabold text-[#0D1117]">AI 正在生成文案</text>
                                        <view class="flex items-end gap-[4rpx] pb-[8rpx]">
                                            <view class="gen-dot w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                            <view
                                                class="gen-dot gen-dot-2 w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                            <view
                                                class="gen-dot gen-dot-3 w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                        </view>
                                    </view>
                                    <text class="mt-[6rpx] block text-[24rpx] text-[#4B5563]">
                                        正在为你创作 {{ resultList.length }} 条文案，请稍候…
                                    </text>
                                </view>
                            </view>
                            <view class="gen-track mt-[22rpx] h-[8rpx] rounded-full overflow-hidden">
                                <view class="gen-track-bar h-full rounded-full" />
                            </view>
                        </view>

                        <view
                            v-for="(item, index) in resultList"
                            :key="index"
                            class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view v-if="item.status === 'pending'" class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-[#E5E9F0] rounded-l-[24rpx] skeleton-bar" />
                                <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[24rpx]">
                                    <view class="flex items-center gap-[10rpx] mb-[20rpx]">
                                        <view class="skeleton w-[40rpx] h-[40rpx] rounded-full" />
                                        <view class="skeleton h-[24rpx] w-[120rpx] rounded-full" />
                                    </view>
                                    <view class="flex flex-col gap-[12rpx]">
                                        <view class="skeleton h-[26rpx] w-full rounded-full" />
                                        <view class="skeleton h-[26rpx] w-[88%] rounded-full" />
                                        <view class="skeleton h-[26rpx] w-[72%] rounded-full" />
                                        <view class="skeleton h-[26rpx] w-[80%] rounded-full" />
                                        <view class="skeleton h-[26rpx] w-[60%] rounded-full" />
                                    </view>
                                </view>
                            </view>

                            <view v-else class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                                <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[18rpx]">
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <view class="flex items-center gap-[10rpx]">
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF]">AI 生成文案</text>
                                        </view>
                                        <view
                                            class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                            @click="handleDeleteItem(index)">
                                            <u-icon name="close" color="#9CA3AF" size="16" />
                                        </view>
                                    </view>

                                    <template v-if="isNewsBodyMode">
                                        <view
                                            class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#F0F2F5]">
                                            <u-input
                                                v-model="item.title"
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                type="textarea"
                                                placeholder="请输入文案标题"
                                                :maxlength="CONTENT_MAX_LENGTH" />
                                            <view class="mt-[12rpx] flex justify-end">
                                                <text class="text-[22rpx] text-[#C0C4CC]"
                                                    >{{ item.title.length }} / {{ CONTENT_MAX_LENGTH }}</text
                                                >
                                            </view>
                                        </view>
                                    </template>

                                    <template v-else>
                                        <view
                                            v-if="montageType !== MontageTypeEnum.STORYBOARD_MIX"
                                            class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5] mb-[16rpx]">
                                            <u-input
                                                v-model="item.title"
                                                placeholder="请输入文案标题"
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                :maxlength="30" />
                                        </view>
                                        <view
                                            class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5]">
                                            <u-input
                                                v-model="item.content"
                                                type="textarea"
                                                placeholder="请输入文案内容"
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                :maxlength="CONTENT_MAX_LENGTH"
                                                :auto-height="false" />
                                            <view class="mt-[12rpx] flex justify-end">
                                                <text class="text-[22rpx] text-[#C0C4CC]"
                                                    >{{ item.content.length }} / {{ CONTENT_MAX_LENGTH }}</text
                                                >
                                            </view>
                                        </view>
                                    </template>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[50rpx]">
            <view v-if="!isGenerating">
                <view
                    class="rounded-[24rpx] flex flex-col items-center justify-center gap-[6rpx] relative overflow-hidden transition-all duration-200 px-[24rpx] py-[18rpx]"
                    :class="contentVal.length > 0 ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                    :style="
                        contentVal.length > 0
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleSubmit">
                    <view class="flex items-center justify-center gap-[10rpx]">
                        <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成文案</text>
                        <view
                            v-if="isSystemMode"
                            class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                            <text class="text-[22rpx] text-white font-medium"> 消耗 {{ totalTokenCost }} 算力 </text>
                        </view>
                    </view>
                    <view v-if="isSystemMode && needsTitleRequest" class="flex items-center gap-[8rpx]">
                        <text class="text-[20rpx] text-[#ffffff]/60"
                            >内容 {{ tokenCost }} + 标题 {{ titleTokenCost }}</text
                        >
                        <view
                            class="bg-[#ffffff]/15 border border-solid border-[#ffffff]/30 rounded-full px-[12rpx] py-[4rpx]">
                            <text class="text-[20rpx] text-[#ffffff]/80 font-medium">含标题生成</text>
                        </view>
                    </view>
                </view>
            </view>

            <view v-else-if="isLoading" class="w-full">
                <view
                    class="w-full h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[14rpx] shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                    <view class="relative w-[36rpx] h-[36rpx] flex-shrink-0">
                        <view
                            class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[#ffffff]/40" />
                        <view
                            class="gen-spin absolute inset-0 rounded-full border-[4rpx] border-solid border-[transparent]"
                            style="border-top-color: #ffffff" />
                    </view>
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">AI 生成中…</text>
                </view>
            </view>

            <view v-else class="flex items-center gap-[16rpx]">
                <view
                    class="w-[220rpx] h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-[#F7F9FC]"
                    @click="handleSubmit">
                    <u-icon name="reload" color="#4B5563" size="24" />
                    <text class="text-[28rpx] font-bold text-[#4B5563]">重新生成</text>
                </view>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] relative overflow-hidden transition-all duration-200"
                    :class="isAllGenerated ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                    :style="
                        isAllGenerated
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="confirmUseContent">
                    <u-icon name="checkmark" color="#fff" size="20" />
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">使用文案</text>
                </view>
            </view>
        </view>
    </view>

    <choose-person
        v-if="showChoosePerson"
        v-model="showChoosePerson"
        :limit="1"
        :skip-un-config="true"
        :is-config="false"
        @select="onPersonSelected" />
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useAgent from "@/ai_modules/digital_human/hooks/useAgent";

// ─── 常量 ─────────────────────────────────────────────────────────
const CONTENT_MAX_LENGTH = 1000;

const PROMPT_OPTIONS = [
    { id: 1, name: "长", length: 500 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "短", length: 150 },
] as const;

const PROMPT_NUM_OPTIONS = [1, 3, 5, 10, 20] as const;

// ─── 页面参数 ──────────────────────────────────────────────────────
const agentData = ref({
    type: "",
    agentType: 0,
    agentId: -1,
    engine: "1",
});
const montageType = ref<MontageTypeEnum>();
/** 由人设管理等场景带人设进入时锁定，不允许在本页切换人设 */
const personaLocked = ref(false);

/** 是否系统 Agent 模式 */
const isSystemMode = computed(() => agentData.value.agentType === 1);
/** 是否新闻稿模式 */
const isNewsBodyMode = computed(() => montageType.value === MontageTypeEnum.NEWS_BODY);
/** 是否分镜混剪模式 */
const isStoryboardMixMode = computed(() => montageType.value === MontageTypeEnum.STORYBOARD_MIX);

// ─── Hook：人设 IP 选择 ───────────────────────────────────────────
const { showChoosePerson, selectedPerson, openPersonPicker, onPersonSelected } = usePersonIP();

// ─── Hook：字数档位 & 数量 ────────────────────────────────────────
const { currentPrompt, currentPromptNum } = usePromptConfig();

// ─── Hook：文案生成核心 ───────────────────────────────────────────
const {
    contentVal,
    resultList,
    isGenerating,
    isLoading,
    isAllGenerated,
    tokenCost,
    titleTokenCost,
    totalTokenCost,
    needsTitleRequest,
    handleSubmit,
    handleDeleteItem,
    confirmUseContent,
} = useCopywriterGenerate({
    agentData,
    isSystemMode,
    isNewsBodyMode,
    isStoryboardMixMode,
    currentPrompt,
    currentPromptNum,
    selectedPerson,
});

// ─── 页面生命周期 ─────────────────────────────────────────────────
const { pollingEnd } = useAgent({ onfinish: () => {}, onerror: () => {} });

onLoad((options: any) => {
    if (options?.montageType) {
        montageType.value = parseInt(options.montageType);
    }
    if (options?.agentData) {
        agentData.value = JSON.parse(options.agentData);
    }
    // 由人设场景（如文案库）进入时预置人设，锁定不可切换
    if (options?.personaId) {
        selectedPerson.value = {
            id: options.personaId,
            persona_name: options.personaName ? decodeURIComponent(options.personaName) : "当前人设",
        };
        personaLocked.value = true;
    }
});

onUnload(() => {
    pollingEnd.value?.();
});

// ════════════════════════════════════════════════════════════════
// Hook：usePersonIP —— 人设 IP 选择
// ════════════════════════════════════════════════════════════════
function usePersonIP() {
    const showChoosePerson = ref(false);
    const selectedPerson = ref<any>(null);

    const openPersonPicker = () => {
        showChoosePerson.value = true;
    };

    const onPersonSelected = (person: any) => {
        selectedPerson.value = person;
        showChoosePerson.value = false;
    };

    return { showChoosePerson, selectedPerson, openPersonPicker, onPersonSelected };
}

// ════════════════════════════════════════════════════════════════
// Hook：usePromptConfig —— 字数档位 & 生成数量
// ════════════════════════════════════════════════════════════════
function usePromptConfig() {
    const currentPrompt = ref<(typeof PROMPT_OPTIONS)[number]>(PROMPT_OPTIONS[0]);
    const currentPromptNum = ref<number>(1);

    return { currentPrompt, currentPromptNum };
}

// ════════════════════════════════════════════════════════════════
// Hook：useCopywriterGenerate —— 文案生成核心逻辑
// ════════════════════════════════════════════════════════════════
function useCopywriterGenerate(options: {
    agentData: Ref<any>;
    isSystemMode: ComputedRef<boolean>;
    isNewsBodyMode: ComputedRef<boolean>;
    isStoryboardMixMode: ComputedRef<boolean>;
    currentPrompt: Ref<any>;
    currentPromptNum: Ref<number>;
    selectedPerson: Ref<any>;
}) {
    const {
        agentData,
        isSystemMode,
        isNewsBodyMode,
        isStoryboardMixMode,
        currentPrompt,
        currentPromptNum,
        selectedPerson,
    } = options;

    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);
    const { emit } = useEventBusManager();

    // ── 状态 ──
    const contentVal = ref("");
    const resultList = ref<any[]>([]);
    const isGenerating = ref(false);
    const pendingTitle = ref("");

    const isAllGenerated = computed(
        () => resultList.value.length > 0 && resultList.value.every((item) => item.status === "success"),
    );

    // 有占位项处于 pending 即为生成中，用于驱动醒目 loading
    const isLoading = computed(() => resultList.value.some((item) => item.status === "pending"));

    const needsTitleRequest = computed(() => !isNewsBodyMode.value && !isStoryboardMixMode.value);

    const tokenCost = computed(() => {
        const token =
            agentData.value.engine == 1
                ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
                : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
        return parseFloat(token) * currentPromptNum.value;
    });

    const titleTokenCost = computed(() => {
        if (!needsTitleRequest.value) return 0;
        const token =
            agentData.value.engine == 1
                ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
                : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
        const num = isSystemMode.value ? currentPromptNum.value : 1;
        return parseFloat(token) * num;
    });

    /** 总算力 = 内容算力 + 标题算力 */
    const totalTokenCost = computed(() => tokenCost.value + titleTokenCost.value);

    // ── 构建 pending 占位列表 ──
    const buildPendingList = (count: number) =>
        Array.from({ length: count }, () => ({ title: "", content: "", status: "pending" }));

    // ── 重置结果 ──
    const resetResult = () => {
        resultList.value = [];
        isGenerating.value = false;
    };

    // ── 校验 ──
    const validate = (input: string): boolean => {
        if (!input.trim()) {
            uni.$u.toast("请输入文案");
            return false;
        }
        if (userTokens.value < totalTokenCost.value) {
            powerInsufficientTip();
            return false;
        }
        return true;
    };

    // ── 构建标题请求参数 ──
    const buildTitleParams = (input: string, count: number) => ({
        sn: 8,
        keywords: input,
        number: count,
        length: currentPrompt.value.length,
        persona_id: selectedPerson.value?.id,
        type: agentData.value.engine,
    });

    // ── useAgent（流式模式） ──
    const {
        result,
        systemChat,
        handleGenerate: agentGenerate,
        getDetail,
    } = useAgent({
        onfinish: () => {
            if (resultList.value[0]) {
                resultList.value[0].status = "success";
                resultList.value[0].title = isNewsBodyMode.value ? result.value : pendingTitle.value;
                resultList.value[0].content = result.value;
            }
            userStore.getUser();
        },
        onerror: (err: any) => {
            resetResult();
            uni.$u.toast(err || "生成失败，请重试");
        },
    });

    watch(
        () => agentData.value.agentId,
        (id) => {
            if (!isSystemMode.value && id !== -1) {
                getDetail(id, agentData.value.agentType);
            }
        },
        { immediate: true },
    );

    // ── 系统模式生成 ──
    const generateBySystem = async (input: string) => {
        resultList.value = buildPendingList(currentPromptNum.value);

        // 新闻 / 分镜混剪模式：不请求标题，不额外扣算力
        if (isNewsBodyMode.value || isStoryboardMixMode.value) {
            const { content } = await systemChat({
                sn: agentData.value.agentId,
                keywords: input,
                number: currentPromptNum.value,
                length: currentPrompt.value.length,
                persona_id: selectedPerson.value?.id,
                type: agentData.value.engine,
            });
            if (content?.length > 0) {
                resultList.value = content.map((item: any) => ({
                    title: item,
                    content: item,
                    status: "success",
                }));
                userStore.getUser();
            } else {
                uni.$u.toast("生成失败，请重试");
                resetResult();
            }
            return;
        }

        // 非新闻 / 非分镜模式：内容与标题并行请求，额外扣标题算力
        const [contentRes, titleRes] = await Promise.all([
            systemChat({
                sn: agentData.value.agentId,
                keywords: input,
                number: currentPromptNum.value,
                length: currentPrompt.value.length,
                persona_id: selectedPerson.value?.id,
                type: agentData.value.engine,
            }),
            systemChat(buildTitleParams(input, currentPromptNum.value)),
        ]);

        if (contentRes.content?.length > 0) {
            resultList.value = contentRes.content.map((item: any, index: number) => ({
                title: titleRes.content?.[index] ?? `标题 ${index + 1}`,
                content: item,
                status: "success",
            }));
            userStore.getUser();
        } else {
            uni.$u.toast("生成失败，请重试");
            resetResult();
        }
    };

    // ── Agent 模式生成 ──
    const generateByAgent = async (input: string) => {
        resultList.value = buildPendingList(1);
        pendingTitle.value = "";

        if (isNewsBodyMode.value || isStoryboardMixMode.value) {
            await agentGenerate(input, agentData.value.agentType);
        } else {
            const [_] = await Promise.all([
                agentGenerate(input, agentData.value.agentType),
                // systemChat(buildTitleParams(input, 1)),
            ]);
            // pendingTitle.value = titleRes.content?.[0] ?? "";
        }
    };

    // ── 主入口：生成 / 重新生成 ──
    const handleSubmit = async () => {
        if (!validate(contentVal.value)) return;
        isGenerating.value = true;
        try {
            if (isSystemMode.value) {
                await generateBySystem(contentVal.value);
            } else {
                await generateByAgent(contentVal.value);
            }
        } catch (err: any) {
            resetResult();
            uni.$u.toast(err || "生成失败，请重试");
        }
    };

    // ── 删除单条结果 ──
    const handleDeleteItem = (index: number) => {
        resultList.value.splice(index, 1);
        if (resultList.value.length === 0) {
            isGenerating.value = false;
        }
    };

    // ── 确认使用文案 ──
    const confirmUseContent = () => {
        if (!isAllGenerated.value) {
            uni.$u.toast("文案生成中，请稍候...");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.AI_COPYWRITER,
            data:
                isNewsBodyMode.value || isStoryboardMixMode.value
                    ? resultList.value.filter((item) => item.title.trim()).map((item) => item.title)
                    : resultList.value
                          .filter((item) => item.content)
                          .map((item) => ({ title: item.title, content: item.content })),
        });
        resultList.value = [];
        uni.navigateBack();
    };

    return {
        contentVal,
        resultList,
        isGenerating,
        isLoading,
        isAllGenerated,
        tokenCost,
        titleTokenCost,
        totalTokenCost,
        needsTitleRequest,
        handleSubmit,
        handleDeleteItem,
        confirmUseContent,
    };
}
</script>

<style scoped lang="scss">
.gen-loading {
    background: linear-gradient(135deg, #ebf2ff 0%, #f5f9ff 100%);
    box-shadow: inset 0 0 0 1rpx rgba(191, 219, 254, 0.6);
}

.gen-spin {
    animation: gen-rotate 0.8s linear infinite;
}

.gen-pulse {
    animation: gen-pulse 1.2s ease-in-out infinite;
}

.gen-dot {
    animation: gen-bounce 1.2s ease-in-out infinite;
}

.gen-dot-2 {
    animation-delay: 0.15s;
}

.gen-dot-3 {
    animation-delay: 0.3s;
}

.gen-track {
    background: rgba(191, 219, 254, 0.5);
}

.gen-track-bar {
    width: 40%;
    background: linear-gradient(90deg, rgba(0, 101, 251, 0) 0%, #0065fb 50%, rgba(14, 165, 233, 0) 100%);
    animation: gen-indeterminate 1.2s ease-in-out infinite;
}

@keyframes gen-rotate {
    to {
        transform: rotate(360deg);
    }
}

@keyframes gen-pulse {
    0%,
    100% {
        transform: scale(0.6);
        opacity: 0.5;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes gen-bounce {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    50% {
        transform: translateY(-8rpx);
        opacity: 1;
    }
}

@keyframes gen-indeterminate {
    0% {
        transform: translateX(-120%);
    }
    100% {
        transform: translateX(320%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .gen-spin,
    .gen-pulse,
    .gen-dot,
    .gen-track-bar {
        animation: none;
    }
    .gen-track-bar {
        width: 100%;
    }
}

.skeleton {
    background: linear-gradient(90deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.4s ease infinite;
}

.skeleton-bar {
    background: linear-gradient(180deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 100% 400%;
    animation: skeleton-shimmer-v 1.4s ease infinite;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes skeleton-shimmer-v {
    0% {
        background-position: 50% 100%;
    }
    100% {
        background-position: 50% 0%;
    }
}
</style>
