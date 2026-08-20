<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-bold text-[#0D1117]">请说说您的想法</text>
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                        </view>
                        <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                            <textarea
                                class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                v-model="contentVal"
                                :style="{ height: '320rpx' }"
                                placeholder="请输入或粘贴您的文案 ..."
                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                :maxlength="contentMaxLength" />
                        </view>
                        <view class="flex justify-end px-[28rpx] pb-[20rpx]">
                            <text
                                class="text-[22rpx]"
                                :class="
                                    contentVal.length >= contentMaxLength
                                        ? 'text-[#EF4444] font-bold'
                                        : 'text-[#C0C4CC]'
                                ">
                                {{ contentVal.length }} / {{ contentMaxLength }}
                            </text>
                        </view>
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
                                        :class="currentPrompt?.id === item.id ? 'text-[#0065fb]/60' : 'text-[#C0C4CC]'">
                                        {{ item.length }}字
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-for="(item, index) in chatContentList"
                        :key="item.id ?? index"
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
                                            <text class="text-[22rpx] font-bold text-primary">
                                                {{ chatContentList.length - index }}
                                            </text>
                                        </view>
                                        <text class="text-xs text-[#9CA3AF]">AI 生成文案</text>
                                    </view>
                                    <view
                                        class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                        @click="handleDeleteItem(index)">
                                        <u-icon name="close" color="#9CA3AF" size="16" />
                                    </view>
                                </view>

                                <view
                                    class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5]">
                                    <u-input
                                        v-model="item.content"
                                        type="textarea"
                                        placeholder="请输入文案内容"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        :maxlength="500"
                                        :auto-height="false" />
                                    <view class="mt-[12rpx] flex justify-between items-center">
                                        <text class="text-[22rpx] text-[#C0C4CC]">
                                            {{ item.content.length }} / 500
                                        </text>
                                        <view
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                            @click="useContent(item)">
                                            <u-icon name="checkmark" color="#fff" size="14" />
                                            <text class="text-[22rpx] font-bold text-white">使用文案</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[50rpx]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden transition-all duration-200"
                :class="canGenerate ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                :style="
                    canGenerate
                        ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                        : 'background: #C0C4CC'
                "
                @click="contentPost">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成文案</text>
                <view
                    v-if="isSystem"
                    class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                    <text class="text-[22rpx] text-white font-medium">消耗 {{ getToken }} 算力</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useAgent from "@/ai_modules/digital_human/hooks/useAgent";

const PROMPT_OPTIONS = [
    { id: 1, name: "长", length: 500 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "短", length: 150 },
] as const;

const { emit } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const agentData = ref<{
    type: string;
    genType: number;
    agentType: number;
    agentId: number;
    engine: number;
    persona_id: number | null;
}>({
    type: "",
    genType: 0,
    agentType: 0,
    agentId: -1,
    engine: 1,
    persona_id: null,
});

const contentVal = ref<string>("");
const contentMaxLength = 500;
const chatContentList = ref<any[]>([]);

const isGenerating = ref<boolean>(false);

const isSystem = computed(() => agentData.value.agentType == 1);

/** 按钮是否可点击 */
const canGenerate = computed(() => contentVal.value.length > 0 && !isGenerating.value);

const getToken = computed(() => {
    const token =
        agentData.value.engine == 1
            ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
            : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
    return parseFloat(token);
});

const { currentPrompt, currentPromptNum } = usePromptConfig();

function usePromptConfig() {
    const currentPrompt = ref<(typeof PROMPT_OPTIONS)[number]>(PROMPT_OPTIONS[0]);
    const currentPromptNum = ref<number>(1);

    return { currentPrompt, currentPromptNum };
}

const { result, getDetail, handleGenerate, systemChat } = useAgent({
    onfinish: () => {
        chatContentList.value.forEach((item) => {
            if (item.status === "pending") {
                item.status = "success";
                item.content = result.value;
            }
        });
        isGenerating.value = false;
        userStore.getUser();
        uni.hideLoading();
    },
    onerror: (error) => {
        const pendingIndex = chatContentList.value.findIndex((item) => item.status === "pending");
        if (pendingIndex !== -1) {
            chatContentList.value.splice(pendingIndex, 1);
        }
        isGenerating.value = false;
        uni.hideLoading();
        uni.$u.toast(error || "生成失败，请重试");
    },
});

const contentPost = async () => {
    if (!contentVal.value.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (isGenerating.value) {
        uni.$u.toast("文案在生成中...");
        return;
    }
    if (isSystem.value && userTokens.value < getToken.value) {
        powerInsufficientTip();
        return;
    }
    const chatContent = reactive({ content: "", status: "pending" });
    isGenerating.value = true;
    chatContentList.value.unshift(chatContent);

    if (isSystem.value) {
        try {
            const { content } = await systemChat({
                sn: agentData.value.agentId,
                keywords: contentVal.value,
                number: 1,
                length: currentPrompt.value.length,
                type: agentData.value.engine,
                persona_id: agentData.value.persona_id,
            });
            if (content && content.length > 0) {
                chatContent.content = content[0];
                chatContent.status = "success";
                isGenerating.value = false;
                userStore.getUser();
            }
        } catch (err: any) {
            isGenerating.value = false;
            chatContentList.value.shift();
            uni.$u.toast(err || "生成失败，请重试");
        }
    } else {
        try {
            await handleGenerate(contentVal.value, agentData.value.agentType);
        } catch (err: any) {
            isGenerating.value = false;
            chatContentList.value.shift();
            uni.hideLoading();
            uni.$u.toast(err || "生成失败，请重试");
        }
    }
};

/** 删除单条结果 */
const handleDeleteItem = (index: number) => {
    chatContentList.value.splice(index, 1);
};

const useContent = (item: any) => {
    if (item.status === "pending") {
        uni.$u.toast("文案在生成中...");
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.SORA_AI_COPYWRITER,
        data: item.content,
    });
    uni.navigateBack();
};

onLoad(async (options: any) => {
    if (options.agentData) {
        agentData.value = JSON.parse(options.agentData);
        if (!isSystem.value) {
            await getDetail(agentData.value.agentId, agentData.value.agentType);
        }
    }
    if (options.content) {
        contentVal.value = options.content;
        contentPost();
    }
});
</script>

<style scoped lang="scss">
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
