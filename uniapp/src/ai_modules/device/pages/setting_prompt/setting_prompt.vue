<template>
    <view class="min-h-screen flex flex-col bg-[#F5F6FA]">
        <view
            class="mx-[32rpx] mt-[32rpx] rounded-[20rpx] bg-[#EEF3FF] px-[32rpx] py-[24rpx] flex items-start gap-[16rpx]">
            <text class="text-[28rpx] mt-[2rpx]">💡</text>
            <view class="flex-1">
                <text class="text-[26rpx] text-primary leading-[1.6]">
                    自定义机器人的评论风格与规则，修改后将影响所有自动评论的内容。
                </text>
            </view>
        </view>

        <view class="mx-[32rpx] mt-[24rpx] flex-1">
            <view class="bg-white rounded-[24rpx] overflow-hidden shadow-sm">
                <view
                    class="flex items-center justify-between px-[32rpx] pt-[28rpx] pb-[20rpx] border-b border-[#F5F5F5]">
                    <view class="flex items-center gap-[12rpx]">
                        <view class="w-[8rpx] h-[32rpx] rounded-full bg-primary"></view>
                        <text class="text-[28rpx] font-medium text-[#1A1A1A]">提示词内容</text>
                    </view>
                    <text class="text-[24rpx] text-[#BBBBBB]">{{ charCount }} 字</text>
                </view>

                <view class="px-[32rpx] py-[24rpx]">
                    <textarea
                        v-model="commentPrompt"
                        class="w-full text-[28rpx] text-[#333] leading-[1.8]"
                        :style="{ minHeight: '480rpx', background: 'transparent', border: 'none', outline: 'none' }"
                        placeholder="请输入提示词内容..."
                        placeholder-style="color: #CCCCCC; font-size: 28rpx;"
                        :maxlength="-1"
                        :auto-height="false"
                        @input="onInput" />
                </view>

                <view class="px-[32rpx] pb-[24rpx] flex items-center gap-[8rpx]">
                    <text class="text-[22rpx] text-[#CCCCCC]">✎ 点击内容区域即可编辑</text>
                </view>
            </view>
        </view>

        <view class="px-[32rpx] pt-[32rpx] pb-[48rpx] flex gap-[24rpx]">
            <view
                class="flex-1 h-[96rpx] rounded-[20rpx] border border-[#E0E0E0] bg-white text-[#666] font-medium text-[30rpx] flex items-center justify-center"
                @click="handleBack">
                取消
            </view>
            <view
                class="flex-[2] h-[96rpx] rounded-[20rpx] bg-[#4A6CF7] font-medium text-[30rpx] flex items-center justify-center text-white"
                @click="handleSaveConfig">
                <text v-if="!saving">保存设置</text>
                <text v-else>保存中...</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const commentPrompt = ref("");
const saving = ref(false);

const charCount = computed(() => commentPrompt.value.length || 0);

const onInput = (e: any) => {
    commentPrompt.value = e.detail.value;
};

const handleBack = () => {
    uni.navigateBack();
};

const handleSaveConfig = async () => {
    emit("confirm", {
        type: ListenerTypeEnum.CIRCLE_INTERACT_PROMPT,
        data: commentPrompt.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.prompt) {
        commentPrompt.value = options.prompt;
    }
});
</script>

<style scoped>
textarea {
    resize: none;
}
</style>
