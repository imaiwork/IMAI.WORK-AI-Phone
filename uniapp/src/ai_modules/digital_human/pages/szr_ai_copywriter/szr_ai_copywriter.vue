<template>
    <view class="h-full flex flex-col bg-[#F7F9FC]">
        <view class="flex-shrink-0 px-4 pt-4 pb-2 space-y-3">
            <view class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[14rpx]">
                <u-icon name="info-circle" color="#0065fb" size="24" />
                <text class="text-xs text-[#4B5563]">
                    不知道该写什么，试试
                    <text class="text-primary font-semibold" @click="fillExampleCopywriter">一键填入示例</text>
                </text>
            </view>

            <view
                class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <view
                    class="flex items-center justify-between px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[30rpx] font-bold text-[#0D1117]">分享主题</text>
                    </view>
                    <view
                        v-if="isSystemMode"
                        class="flex items-center gap-[8rpx] px-[20rpx] py-[10rpx] rounded-full border border-solid"
                        :class="selectedPerson ? 'border-[#BFDBFE] bg-[#EBF2FF]' : 'border-[#E5E9F0] bg-[#F7F9FC]'"
                        @click="openPersonPicker">
                        <text class="text-xs font-medium" :class="selectedPerson ? 'text-primary' : 'text-[#9CA3AF]'">
                            {{ selectedPerson?.persona_name || "选择人设 IP" }}
                        </text>
                        <u-icon name="arrow-down" :color="selectedPerson ? '#0065fb' : '#9CA3AF'" size="18" />
                    </view>
                </view>
                <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                    <textarea
                        class="w-full text-xs leading-relaxed"
                        v-model="contentVal"
                        placeholder="请输入或粘贴您的文案内容..."
                        :maxlength="CONTENT_MAX_LENGTH" />
                </view>
                <view class="flex items-center justify-end px-[28rpx] pb-[20rpx]">
                    <text
                        class="text-[22rpx]"
                        :class="
                            contentVal.length >= CONTENT_MAX_LENGTH ? 'text-[#F56C6C] font-bold' : 'text-[#C0C4CC]'
                        ">
                        {{ contentVal.length }} / {{ CONTENT_MAX_LENGTH }}
                    </text>
                </view>
            </view>

            <view
                v-if="isSystemMode"
                class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <text class="font-bold text-[#0D1117] block mb-[20rpx]">生成字数</text>
                <view class="flex items-center gap-[16rpx]">
                    <view
                        v-for="item in availablePrompts"
                        :key="item.id"
                        class="flex-1 h-[76rpx] flex items-center justify-center rounded-[20rpx] font-semibold transition-all duration-200"
                        :class="
                            currentPrompt?.id === item.id
                                ? 'bg-[#EBF2FF] text-primary shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                : 'bg-[#F0F2F5] text-[#9CA3AF]'
                        "
                        @click="currentPrompt = item">
                        {{ item.name }}
                    </view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y :scroll-top="scrollTop">
                <view class="px-4 flex flex-col gap-[16rpx] pb-4 content-box">
                    <view
                        v-for="(item, index) in resultList"
                        :key="index"
                        class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view class="flex">
                            <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                            <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[18rpx]">
                                <view class="flex items-center gap-[10rpx] mb-[14rpx]">
                                    <view
                                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                        <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                    </view>
                                    <text class="text-xs text-[#9CA3AF]">AI 生成文案</text>
                                </view>
                                <text class="text-[28rpx] text-[#0D1117] leading-relaxed">{{ item }}</text>
                                <view
                                    class="mt-[20rpx] pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5] flex items-center justify-between">
                                    <text class="text-[22rpx] text-[#9CA3AF]">点击使用将自动填入</text>
                                    <view
                                        class="flex items-center gap-[6rpx] bg-primary px-[28rpx] py-[12rpx] rounded-full shadow-[0_4rpx_12rpx_rgba(28,111,235,0.25)]"
                                        @click="confirmUseContent(item)">
                                        <u-icon name="checkmark" color="#fff" size="20" />
                                        <text class="text-xs font-semibold text-white">使用文案</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="flex-shrink-0 px-4 pt-[16rpx] pb-[60rpx]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleGenerate">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成文案</text>
                <view
                    v-if="isSystemMode"
                    class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                    <text class="text-[22rpx] text-white font-medium">消耗 {{ tokenCost }} 算力</text>
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
        @select="handleSelectPerson" />
</template>

<script setup lang="ts">
import { aiTemplateCopywriter } from "@/ai_modules/digital_human/config/copywriter";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { getCopyWritingGenerate } from "@/api/agent";
import { getRect } from "@/utils/util";
import useAgent from "@/ai_modules/digital_human/hooks/useAgent";

// ─── 常量 ─────────────────────────────────────────────────────────
const CONTENT_MAX_LENGTH = 500;

const PROMPT_OPTIONS = [
    { id: 1, name: "短", length: 150 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "长", length: 1000 },
] as const;

// ─── 页面参数（onLoad 初始化） ─────────────────────────────────────
const agentData = ref({
    type: "",
    genType: 0,
    agentType: 0,
    agentId: -1,
    engine: "1",
});
const textLimit = ref(150);

/** 是否为系统 Agent 模式 */
const isSystemMode = computed(() => agentData.value.agentType === 1);

// ─── Hook：人设 IP 选择 ───────────────────────────────────────────
const { showChoosePerson, selectedPerson, openPersonPicker, handleSelectPerson } = usePersonIP();

// ─── Hook：字数档位 ───────────────────────────────────────────────
const { availablePrompts, currentPrompt } = usePrompt(textLimit);

// ─── Hook：文案生成 ───────────────────────────────────────────────
const { contentVal, resultList, scrollTop, tokenCost, handleGenerate, confirmUseContent, fillExampleCopywriter } =
    useCopywriterGenerate({
        agentData,
        isSystemMode,
        currentPrompt,
        selectedPerson,
        textLimit,
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

    const handleSelectPerson = (person: any) => {
        selectedPerson.value = person;
    };

    return { showChoosePerson, selectedPerson, openPersonPicker, handleSelectPerson };
}

// ════════════════════════════════════════════════════════════════
// Hook：usePrompt —— 字数档位选择
// ════════════════════════════════════════════════════════════════
function usePrompt(textLimit: Ref<number>) {
    /** 根据 textLimit 过滤可用档位 */
    const availablePrompts = computed(() => PROMPT_OPTIONS.filter((item) => item.length <= textLimit.value));

    /** 当前选中档位，默认取第一个 */
    const currentPrompt = ref<any>(availablePrompts.value[0]);

    /** textLimit 变化时重置为第一个可用档位 */
    watch(availablePrompts, (list) => {
        if (!list.find((i) => i.id === currentPrompt.value?.id)) {
            currentPrompt.value = list[0];
        }
    });

    return { availablePrompts, currentPrompt };
}

// ════════════════════════════════════════════════════════════════
// Hook：useCopywriterGenerate —— 文案生成核心逻辑
// ════════════════════════════════════════════════════════════════
function useCopywriterGenerate(options: {
    agentData: Ref<any>;
    isSystemMode: ComputedRef<boolean>;
    currentPrompt: Ref<any>;
    selectedPerson: Ref<any>;
    textLimit: Ref<number>;
}) {
    const { agentData, isSystemMode, currentPrompt, selectedPerson } = options;

    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);
    const { emit } = useEventBusManager();
    const { proxy }: any = getCurrentInstance();

    // ── 状态 ──
    const contentVal = ref("");
    const resultList = ref<string[]>([]);
    const scrollTop = ref(0);

    // ── 算力消耗 ──
    const tokenCost = computed(() => {
        const token =
            agentData.value.engine == 1
                ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
                : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
        return parseFloat(token);
    });

    // ── 示例填入 ──
    const fillExampleCopywriter = () => {
        contentVal.value = aiTemplateCopywriter[0 % aiTemplateCopywriter.length];
    };

    // ── 滚动到底部 ──
    const scrollToBottom = async () => {
        await nextTick();
        getRect(".content-box", false, proxy).then((res: any) => {
            scrollTop.value = res.height;
        });
    };

    // ── useAgent（流式生成模式） ──
    const {
        result,
        getDetail,
        handleGenerate: agentGenerate,
    } = useAgent({
        onfinish: () => {
            resultList.value.push(result.value);
            userStore.getUser();
            uni.hideLoading();
            scrollToBottom();
        },
        onerror: (error: string) => {
            uni.hideLoading();
            uni.$u.toast(error || "生成失败，请重试");
        },
    });

    // 非系统模式时拉取 Agent 详情（供外部 onLoad 调用）
    watch(
        () => agentData.value.agentId,
        (id) => {
            if (!isSystemMode.value && id !== -1) {
                getDetail(id, agentData.value.agentType);
            }
        },
        { immediate: true },
    );

    // ── 校验 ──
    const validate = (input: string): boolean => {
        if (!input.trim()) {
            uni.$u.toast("请输入文案");
            return false;
        }
        if (userTokens.value < (isSystemMode.value ? tokenCost.value : 0)) {
            powerInsufficientTip();
            return false;
        }
        return true;
    };

    // ── 系统模式生成 ──
    const generateBySystem = async (input: string) => {
        const { content } = await getCopyWritingGenerate({
            sn: agentData.value.agentId,
            number: 1,
            keywords: input,
            length: currentPrompt.value.length,
            persona_id: selectedPerson.value?.id,
            type: agentData.value.engine,
        });
        if (content?.length > 0) {
            resultList.value.push(...content);
            userStore.getUser();
        } else {
            uni.$u.toast("生成失败，请重试");
        }
    };

    // ── Agent 模式生成 ──
    const generateByAgent = async (input: string) => {
        await agentGenerate(input, agentData.value.agentType);
    };

    // ── 主入口：生成文案 ──
    const handleGenerate = async () => {
        if (!validate(contentVal.value)) return;
        uni.showLoading({ title: "生成中...", mask: true });
        try {
            if (isSystemMode.value) {
                await generateBySystem(contentVal.value);
                uni.hideLoading();
            } else {
                await generateByAgent(contentVal.value);
            }
        } catch (err: any) {
            uni.hideLoading();
            uni.$u.toast(err || "生成失败，请重试");
        } finally {
            setTimeout(scrollToBottom, 500);
        }
    };

    // ── 使用文案：emit 回调并返回上一页 ──
    const confirmUseContent = (content: string) => {
        emit("confirm", {
            type: ListenerTypeEnum.AI_COPYWRITER,
            data: content,
        });
        resultList.value = [];
        uni.navigateBack();
    };

    return {
        contentVal,
        resultList,
        scrollTop,
        tokenCost,
        handleGenerate,
        confirmUseContent,
        fillExampleCopywriter,
    };
}

// ─── 页面初始化 ───────────────────────────────────────────────────
onLoad((options: any) => {
    if (options?.agentData) {
        agentData.value = JSON.parse(options.agentData);
    }
    if (options?.limit) {
        textLimit.value = parseInt(options.limit);
    }
});
</script>

<style scoped lang="scss">
.send-btn {
    @apply w-[50rpx] h-[50rpx] rounded-full flex items-center justify-center;
}
</style>
