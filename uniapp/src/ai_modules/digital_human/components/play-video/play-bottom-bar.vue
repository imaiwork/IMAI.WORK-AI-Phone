<template>
    <view
        class="fixed left-0 right-0 bottom-0 bg-white px-[32rpx] pt-[20rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))] bar-shadow z-50"
    >
        <view class="flex items-center gap-[16rpx]">
            <view
                class="flex flex-col items-center gap-[6rpx] flex-shrink-0 px-[8rpx]"
                @click="emit('cost')"
            >
                <image
                    src="@/static/images/digital_human/star.svg"
                    class="w-[36rpx] h-[36rpx]"
                />
                <text class="text-[20rpx] text-[#9CA3AF] font-medium">算力消耗</text>
            </view>
            <view
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center btn-gradient"
                :class="loading ? 'opacity-70' : 'active:opacity-80'"
                @click="emit('submit')"
            >
                <u-loading v-if="loading" mode="circle" size="32" color="#fff" />
                <text v-else class="text-[28rpx] font-extrabold text-white">{{ text }}</text>
            </view>
        </view>
        <text class="text-[18rpx] text-[#C0C6D0] text-center block mt-[8rpx]"
            >生成失败自动退还算力</text
        >
    </view>
</template>

<script setup lang="ts">
defineProps<{ text: string; loading?: boolean }>()
const emit = defineEmits<{ (e: 'cost'): void; (e: 'submit'): void }>()
</script>

<style lang="scss" scoped>
.bar-shadow {
    box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.06);
}
.btn-gradient {
    background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%);
    box-shadow: 0 8rpx 24rpx rgba(0, 101, 251, 0.3);
}
</style>
