<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title="线索词AI生成"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }"
            :custom-back="back" />

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <template v-if="!isGenerating">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-bold text-[#0D1117]">您想获取的线索方向</text>
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                            <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                                <textarea
                                    v-model="contentVal"
                                    focus
                                    class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                    :style="{ height: '320rpx' }"
                                    placeholder="请输入您想获取客户的行业，如：家居用品"
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
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view class="flex items-center gap-[8rpx] mb-[20rpx]">
                                <text class="font-bold text-[#0D1117]">线索词数量</text>
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                            <view class="flex items-center gap-[12rpx] flex-wrap">
                                <view
                                    v-for="item in promptNumList"
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

                    <template v-else>
                        <view
                            v-for="(item, index) in chatContentList"
                            :key="index"
                            class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view v-if="item.status === 'pending'" class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-[#E5E9F0] rounded-l-[24rpx] skeleton-bar" />
                                <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[24rpx]">
                                    <view class="flex items-center gap-[10rpx] mb-[20rpx]">
                                        <view class="skeleton w-[40rpx] h-[40rpx] rounded-full" />
                                        <view class="skeleton h-[24rpx] w-[160rpx] rounded-full" />
                                    </view>
                                    <view class="flex flex-col gap-[12rpx]">
                                        <view class="skeleton h-[26rpx] w-full rounded-full" />
                                        <view class="skeleton h-[26rpx] w-[75%] rounded-full" />
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
                                            <text class="text-xs text-[#9CA3AF]">AI 生成线索词</text>
                                        </view>
                                        <view
                                            class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                            @click="handleDelete(index)">
                                            <u-icon name="close" color="#9CA3AF" size="16" />
                                        </view>
                                    </view>
                                    <view
                                        class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5]"
                                        @click="handleEdit(index)">
                                        <text class="text-[28rpx] text-[#0D1117] leading-relaxed">{{
                                            item.content
                                        }}</text>
                                    </view>
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
                    class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden transition-all duration-200"
                    :class="contentVal.length > 0 ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                    :style="
                        contentVal.length > 0
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="contentPost(contentVal)">
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成线索词</text>
                    <view class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                        <text class="text-[22rpx] text-white font-medium">消耗 {{ getToken }} 算力</text>
                    </view>
                </view>
            </view>

            <view v-else class="flex items-center gap-[16rpx]">
                <view
                    class="w-[220rpx] h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-[#F7F9FC]"
                    @click="reloadContent">
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
                    @click="useContent">
                    <u-icon name="checkmark" color="#fff" size="20" />
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">使用线索词</text>
                </view>
            </view>
        </view>
    </view>

    <clue-edit ref="clueEditRef" v-model="showEdit" @confirm="handleConfirm" @close="showEdit = false" />
</template>

<script setup lang="ts">
import { getAiKeywords } from "@/api/sph";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/sph/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ClueEdit from "@/ai_modules/sph/components/clue-edit/clue-edit.vue";

const { emit } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const genType = ref<any>();

const contentVal = ref<string>("");
const contentMaxLength = 500;
const textLimit = ref<number>(150);
const chatContentList = ref<any[]>([]);

// 口播数量
const promptNumList = [10, 20, 30, 40, 50];
const currentPromptNum = ref<number>(promptNumList[0]);

const editIndex = ref<number>(-1);
const showEdit = ref<boolean>(false);
const clueEditRef = ref<any>(null);

// 是否正在生成
const isGenerating = ref<boolean>(false);

// 是否生成好
const isGenerated = computed(() => {
    return chatContentList.value.every((item) => item.status === "success");
});

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SPH_AI_CLUE)?.score;
    return parseFloat(token) * currentPromptNum.value;
});

const contentPost = async (userInput: string) => {
    if (!userInput.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (isGenerating.value) return;

    if (userTokens.value < getToken.value) {
        powerInsufficientTip();
        return;
    }

    try {
        isGenerating.value = true;

        chatContentList.value = Array.from({ length: currentPromptNum.value }, () => ({
            title: "",
            content: "",
            status: "pending",
        }));
        // 这里要根据生成数量来请求接口, 要并发请求
        const results = await getAiKeywords({
            keyword: contentVal.value,
            targetCount: currentPromptNum.value,
            channelVersion: genType.value,
        });
        chatContentList.value = results
            .filter((item: any) => item.indexOf("=") == -1)
            .map((item: any) => ({
                content: item.trim(),
                status: "success",
            }));
        userStore.getUser();
    } catch (err: any) {
        isGenerating.value = false;
        uni.showToast({
            title: err || "生成失败，请重试",
            icon: "none",
            duration: 3000,
        });
    }
};

const reloadContent = () => {
    if (isGenerating.value) {
        if (isGenerated.value) {
            isGenerating.value = false;
        } else {
            uni.$u.toast("正在生成中，请稍后再试");
            return;
        }
    }
    chatContentList.value = [];
    isGenerating.value = false;
    contentPost(contentVal.value);
};

const handleEdit = async (index: number) => {
    editIndex.value = index;
    showEdit.value = true;
    await nextTick();
    clueEditRef.value.setFormData(chatContentList.value[index].content);
};

const handleConfirm = (val: string) => {
    chatContentList.value[editIndex.value].content = val;
    showEdit.value = false;
};

const handleDelete = (index: number) => {
    chatContentList.value.splice(index, 1);
    if (chatContentList.value.length === 0) {
        isGenerating.value = false;
    }
};

const useContent = () => {
    if (!isGenerated.value) {
        uni.$u.toast("文案在生成中...");
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.TASK_AI_CLUE,
        data: chatContentList.value.map((item: any) => item.content),
    });
    chatContentList.value = [];
    uni.navigateBack();
};

const back = () => {
    if (chatContentList.value.length > 0) {
        chatContentList.value = [];
        isGenerating.value = false;
    } else {
        uni.navigateBack();
    }
};

onLoad((options: any) => {
    textLimit.value = options.limit;
    genType.value = options.type;
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
