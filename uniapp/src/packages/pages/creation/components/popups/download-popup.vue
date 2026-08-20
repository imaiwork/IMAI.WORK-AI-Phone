<template>
    <u-popup v-model="show" mode="center" width="90%" :border-radius="32">
        <view class="bg-white rounded-[32rpx] p-[40rpx]">
            <view class="flex items-center gap-x-[12rpx] mb-[8rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full"></view>
                <text class="text-[30rpx] font-bold text-[#212121]">选择下载版本</text>
            </view>
            <text class="text-[22rpx] text-[#676767] ml-[18rpx] mb-[32rpx] block"> 请选择您需要保存到本地的视频 </text>

            <view class="flex gap-[16rpx] mb-[8rpx]">
                <view
                    class="flex-1 py-[32rpx] px-[16rpx] flex flex-col items-center gap-[8rpx] rounded-[24rpx] bg-[#F4F6FB] border border-solid border-[#E5E7EB] active:opacity-70"
                    @click="emit('download', 1)">
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-white flex items-center justify-center shadow-sm mb-[4rpx]">
                        <text class="text-[24rpx] font-bold text-[#676767]">原</text>
                    </view>
                    <text class="text-[26rpx] font-bold text-[#212121]">生成视频</text>
                    <text class="text-[20rpx] text-[#676767]">原始版本</text>
                </view>

                <view
                    v-if="operateItem?.clip_result_url && operateItem?.automatic_clip"
                    class="flex-1 py-[32rpx] px-[16rpx] flex flex-col items-center gap-[8rpx] rounded-[24rpx] relative overflow-hidden active:opacity-90"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)"
                    @click="emit('download', 2)">
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#ffffff]/20 flex items-center justify-center border border-solid border-[#ffffff]/30 mb-[4rpx]">
                        <text class="text-[24rpx] font-bold text-white">AI</text>
                    </view>
                    <text class="text-[26rpx] font-bold text-white">剪辑视频</text>
                    <text class="text-[20rpx] text-[#ffffff]/70">智能处理</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    modelValue: boolean;
    operateItem: any;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "download", type: 1 | 2): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
