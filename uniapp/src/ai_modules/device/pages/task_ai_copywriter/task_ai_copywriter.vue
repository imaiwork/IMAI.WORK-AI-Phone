<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <template v-if="!isGenerating">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-bold text-[#0D1117]">分享主题</text>
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                            <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                                <textarea
                                    class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                    v-model="contentVal"
                                    :style="{ height: '320rpx' }"
                                    placeholder="点击输入您想生成的主题，如：北京旅游"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    :maxlength="contentMaxLength"
                                    focus />
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
                                <text class="font-bold text-[#0D1117]">生成数量</text>
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
                                            @click="handleDeleteCopywriter(index)">
                                            <u-icon name="close" color="#9CA3AF" size="16" />
                                        </view>
                                    </view>

                                    <view
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
                                            maxlength="500"
                                            :auto-height="false" />
                                        <view class="mt-[12rpx] flex justify-end">
                                            <text class="text-[22rpx] text-[#C0C4CC]"
                                                >{{ item.content.length }} / 500</text
                                            >
                                        </view>
                                    </view>

                                    <view
                                        v-if="item.topic && item.topic.length > 0"
                                        class="mt-[16rpx] flex flex-wrap gap-[10rpx]">
                                        <view
                                            v-for="(topic, topicIndex) in item.topic"
                                            :key="topicIndex"
                                            class="relative flex items-center gap-[6rpx] bg-[#EBF2FF] rounded-full px-[20rpx] h-[56rpx] border border-solid border-[#0065fb]/20"
                                            @click="handleEditTopic(index, topicIndex)">
                                            <text class="text-[22rpx] font-semibold text-primary">{{ topic }}</text>
                                            <view
                                                class="absolute -top-[8rpx] -right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-[#374151]/50 flex items-center justify-center shadow-sm"
                                                @click.stop="handleDeleteTopic(index, topicIndex)">
                                                <u-icon name="close" size="12" color="#ffffff" />
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[50rpx] shadow-[0_-2rpx_12rpx_rgba(0,0,0,0.06)]">
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
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成文案</text>
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
                    <text class="text-[30rpx] font-extrabold text-white tracking-wide">使用文案</text>
                </view>
            </view>
        </view>
    </view>

    <u-popup v-model="showEditTopicPopup" mode="center" width="90%" :border-radius="20">
        <view class="bg-white rounded-[28rpx] overflow-hidden">
            <view
                class="flex items-center justify-center h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5] relative">
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">编辑标签</text>
                <view
                    class="absolute right-[24rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                    @click="showEditTopicPopup = false">
                    <u-icon name="close" size="18" color="#6B7280" />
                </view>
            </view>
            <view class="px-[32rpx] pt-[32rpx] pb-[40rpx]">
                <view class="bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                    <u-input
                        v-model="newTopic"
                        placeholder="请输入标签内容"
                        maxlength="20"
                        placeholder-style="color:#C0C4CC;font-size:28rpx;" />
                </view>
                <text class="text-[20rpx] text-[#C0C4CC] mt-[12rpx] block text-right"> {{ newTopic.length }}/20 </text>
                <view class="flex items-center gap-[16rpx] mt-[32rpx]">
                    <view
                        class="flex-1 h-[88rpx] flex items-center justify-center rounded-[20rpx] bg-[#F3F4F6]"
                        @click="showEditTopicPopup = false">
                        <text class="text-[28rpx] font-semibold text-[#6B7280]">取消</text>
                    </view>
                    <view
                        class="flex-1 h-[88rpx] flex items-center justify-center rounded-[20rpx] shadow-[0_6rpx_16rpx_rgba(0,101,251,0.28)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleEditTopicConfirm">
                        <text class="text-[28rpx] font-extrabold text-white">确定</text>
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { generateMatrixPrompt } from "@/api/device";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const contentVal = ref<string>("");
const contentMaxLength = 500;
const textLimit = ref<number>(150);
const chatContentList = ref<any[]>([]);

const promptNumList = [1, 3, 5, 10, 20, 30];
const currentPromptNum = ref<number>(1);
const isGenerating = ref<boolean>(false);

const showEditTopicPopup = ref<boolean>(false);
const newTopic = ref<string>("");
const editTopicIndex = ref<number[]>([]);

const isGenerated = computed(() => chatContentList.value.every((item) => item.status === "success"));

const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.MATRIX_COPYWRITER)?.score;
    return parseFloat(token) * currentPromptNum.value;
});

const contentPost = async (userInput: string) => {
    if (!userInput.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (isGenerating.value) return;
    if (userTokens.value <= getToken.value) {
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
        const results = await generateMatrixPrompt({
            keywords: userInput,
            number: currentPromptNum.value,
        });
        chatContentList.value = results.map((item: any) => ({
            title: item.title,
            content: item.content,
            topic: item.topic,
            status: "success",
        }));
        userStore.getUser();
    } catch (err: any) {
        isGenerating.value = false;
        chatContentList.value = [];
        uni.showToast({ title: err || "生成失败，请重试", icon: "none", duration: 3000 });
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

const handleDeleteCopywriter = (index: number) => {
    chatContentList.value.splice(index, 1);
    if (chatContentList.value.length === 0) isGenerating.value = false;
};

const handleEditTopic = (index: number, topicIndex: number) => {
    editTopicIndex.value = [index, topicIndex];
    newTopic.value = chatContentList.value[index].topic[topicIndex];
    showEditTopicPopup.value = true;
};

const handleDeleteTopic = (index: number, topicIndex: number) => {
    chatContentList.value[index].topic.splice(topicIndex, 1);
};

const handleEditTopicConfirm = () => {
    chatContentList.value[editTopicIndex.value[0]].topic[editTopicIndex.value[1]] = newTopic.value;
    showEditTopicPopup.value = false;
};

const useContent = () => {
    if (!isGenerated.value) {
        uni.$u.toast("文案在生成中...");
        return;
    }
    try {
        emit("confirm", {
            type: ListenerTypeEnum.TASK_AI_COPYWRITER,
            data: chatContentList.value
                .filter((item) => item.title)
                .map((item) => ({
                    is_title_show: 1,
                    title: item.title,
                    content: item.content,
                    topic: item.topic,
                })),
        });
        chatContentList.value = [];
        uni.navigateBack();
    } catch (error) {
        console.log("error", error);
    }
};

onLoad((options: any) => {
    textLimit.value = options.limit;
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
