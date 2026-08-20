<template>
    <popup-bottom v-model="show" title="AI生成线索词" custom-class="bg-[#F7F9FC]" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view v-if="!isGenerating" class="px-4 pt-[16rpx] pb-[8rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">您想获取的线索方向</text>
                        </view>
                        <view class="px-[28rpx] pt-[16rpx] pb-[8rpx]">
                            <textarea
                                class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                v-model="contentVal"
                                focus
                                :style="{ height: '280rpx' }"
                                placeholder="请输入您想获取客户的行业，如：家居用品"
                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                :maxlength="contentMaxLength" />
                        </view>
                        <view class="flex justify-end px-[28rpx] pb-[16rpx]">
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
                    </view>
                </view>

                <scroll-view class="grow min-h-0" scroll-y>
                    <view v-if="isGenerating" class="p-4 flex flex-col gap-[12rpx]">
                        <view
                            v-for="(item, index) in chatContentList"
                            :key="index"
                            class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view v-if="item.status === 'pending'" class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-[#E5E9F0] rounded-l-[24rpx] skeleton-bar" />
                                <view class="flex-1 px-[24rpx] pt-[20rpx] pb-[20rpx]">
                                    <view class="flex items-center gap-[10rpx] mb-[16rpx]">
                                        <view class="skeleton w-[36rpx] h-[36rpx] rounded-full" />
                                        <view class="skeleton h-[22rpx] w-[140rpx] rounded-full" />
                                    </view>
                                    <view class="flex flex-col gap-[10rpx]">
                                        <view class="skeleton h-[24rpx] w-full rounded-full" />
                                        <view class="skeleton h-[24rpx] w-[70%] rounded-full" />
                                    </view>
                                </view>
                            </view>

                            <view v-else class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                                <view class="flex-1 flex items-center justify-between px-[24rpx] py-[20rpx]">
                                    <view class="flex items-center gap-[12rpx] flex-1 min-w-0">
                                        <view
                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                            <text class="text-[20rpx] font-bold text-primary">{{ index + 1 }}</text>
                                        </view>
                                        <text class="text-[#0D1117] font-medium flex-1">{{ item.content }}</text>
                                    </view>
                                    <view
                                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center flex-shrink-0 ml-[12rpx]"
                                        @click="handleDelete(index)">
                                        <u-icon name="close" color="#9CA3AF" size="16" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view
                    class="px-4 pt-[16rpx] pb-[calc(16rpx+env(safe-area-inset-bottom))] border-[0] border-t border-solid border-[#F0F2F5] bg-white">
                    <view v-if="!isGenerating">
                        <view
                            class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden transition-all duration-200"
                            :class="
                                contentVal.length > 0 ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'
                            "
                            :style="
                                contentVal.length > 0
                                    ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                    : 'background: #C0C4CC'
                            "
                            @click="generateClue(contentVal)">
                            <text class="text-[30rpx] font-extrabold text-white tracking-wide">立即生成</text>
                            <view
                                class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                                <text class="text-[22rpx] text-white font-medium">消耗 {{ getToken }} 算力</text>
                            </view>
                        </view>
                    </view>

                    <view v-else class="flex items-center gap-[16rpx]">
                        <view
                            v-if="isGenerated"
                            class="w-[220rpx] h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-[#F7F9FC]"
                            @click="handleReload">
                            <u-icon name="reload" color="#4B5563" size="24" />
                            <text class="text-[28rpx] font-bold text-[#4B5563]">重新生成</text>
                        </view>
                        <view
                            class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] relative overflow-hidden transition-all duration-200"
                            :class="isGenerated ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                            :style="
                                isGenerated
                                    ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                    : 'background: #C0C4CC'
                            "
                            @click="handleConfirm">
                            <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定使用</text>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { getAiKeywords } from "@/api/sph";
import { TokensSceneEnum } from "@/enums/appEnums";
import requestCancel from "@/utils/request/cancel";
const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", value: any[]): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const currentPromptNum = 30;
const chatContentList = ref<any[]>([]);
const contentVal = ref<string>("");
const contentMaxLength = 500;
const isGenerating = ref<boolean>(false);
const isGenerated = computed(() => {
    return chatContentList.value.every((item) => item.status === "success");
});

const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SPH_AI_CLUE)?.score;
    return parseFloat(token) * currentPromptNum;
});

const generateClue = async (content: string) => {
    if (!contentVal.value) {
        uni.$u.toast("请输入您想获取的线索方向");
        return;
    }
    if (isGenerating.value) return;
    if (userTokens.value < getToken.value) {
        powerInsufficientTip();
        return;
    }
    try {
        isGenerating.value = true;

        chatContentList.value = Array.from({ length: currentPromptNum }, () => ({
            title: "",
            content: "",
            status: "pending",
        }));
        // 这里要根据生成数量来请求接口, 要并发请求
        const results = await getAiKeywords({
            keyword: contentVal.value,
            targetCount: currentPromptNum,
            channelVersion: 2,
        });
        chatContentList.value = results
            .filter((item: any) => item.indexOf("=") == -1)
            .map((item: any) => ({
                content: item.trim(),
                status: "success",
            }));
    } catch (err: any) {
        isGenerating.value = false;
        uni.showToast({
            title: err || "生成失败，请重试",
            icon: "none",
            duration: 3000,
        });
    }
};
const handleDelete = (index: number) => {
    chatContentList.value.splice(index, 1);
};

const handleReload = () => {
    if (isGenerating.value) {
        if (isGenerated.value) {
            isGenerating.value = false;
        } else {
            uni.$u.toast("正在生成中，请稍后再试");
            return;
        }
    }
    isGenerating.value = false;
    chatContentList.value = [];
    generateClue(contentVal.value);
};

const handleConfirm = () => {
    emit(
        "confirm",
        chatContentList.value.map((item) => item.content),
    );
    close();
};

const close = () => {
    chatContentList.value = [];
    contentVal.value = "";
    isGenerating.value = false;
    requestCancel.remove("/sv.tools/getSearchTerms");
    show.value = false;
    emit("close");
};
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
