<template>
    <view class="fixed top-0 bottom-0 left-0 right-0 bg-black z-[7799]">
        <view class="h-full flex flex-col items-center justify-center">
            <view class="progress-loader">
                <div class="light"></div>
                <div class="black_overlay"></div>
            </view>
            <view class="text-white mt-[32rpx] text-[30rpx]">
                {{ loadingText || "视频正在上传中..." }} {{ progress }}%
            </view>
            <view class="text-[#ffffff80] mt-[32rpx] text-[26rpx]"> 请勿息屏或切换应用 </view>
            <view class="mt-[32rpx]">
                <view
                    class="text-white rounded-full flex items-center justify-center h-[100rpx] w-[342rpx] border border-solid border-[#474747]"
                    @click="emit('cancel')"
                    >取消</view
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps({
    progress: {
        type: [String, Number],
        default: 0,
    },
    isSuccess: {
        type: Boolean,
        default: false,
    },
    loadingText: {
        type: String,
        default: "视频正在上传中...",
    },
    showBack: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["cancel", "confirm", "back"]);
</script>

<style scoped lang="scss"></style>
