<template>
    <view class="resource-empty">
        <view class="resource-empty-art" aria-hidden="true">
            <view class="resource-empty-art-back"></view>
            <view class="resource-empty-art-front">
                <u-icon :name="icon" color="#3075CE" size="48"></u-icon>
            </view>
        </view>
        <text class="resource-empty-title">{{ title }}</text>
        <text
            class="resource-empty-description"
            :class="{
                'resource-empty-description--reserved': reserveDescriptionSpace,
                'resource-empty-description--readonly': !actionText
            }"
        >
            {{ description }}
        </text>
        <view
            v-if="actionText"
            class="resource-empty-action active:opacity-85"
            role="button"
            @click="emit('action')"
        >
            <u-icon name="plus" color="#075ECF" size="36"></u-icon>
            <text>{{ actionText }}</text>
        </view>
        <slot name="footer"></slot>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        icon: string
        title: string
        description: string
        actionText?: string
        reserveDescriptionSpace?: boolean
    }>(),
    { actionText: '', reserveDescriptionSpace: false }
)

const emit = defineEmits<{
    (event: 'action'): void
}>()
</script>

<style lang="scss" scoped>
.resource-empty {
    @apply bg-white rounded-[36rpx] px-[40rpx] py-[60rpx] flex flex-col items-center text-center text-[#667487];
}

.resource-empty-art {
    @apply relative w-[164rpx] h-[132rpx] mx-auto;
}

.resource-empty-art-back {
    @apply absolute left-[12rpx] top-[2rpx] w-[102rpx] h-[110rpx] border-[2rpx] border-solid border-[#C6DAF9] rounded-[18rpx] bg-[#EDF4FF];
    transform: rotate(-11deg);
}

.resource-empty-art-front {
    @apply absolute right-[12rpx] top-[22rpx] w-[98rpx] h-[110rpx] border-[2rpx] border-solid border-[#A9C6F0] rounded-[18rpx] bg-white flex items-center justify-center;
    box-shadow: 0 6rpx 16rpx #1258a10e;
}

.resource-empty-title {
    @apply mt-[32rpx] mb-[18rpx] text-[34rpx] leading-[1.5] font-bold text-[#233047];
}

.resource-empty-description {
    @apply block max-w-[600rpx] mx-auto mb-[32rpx] text-[26rpx] leading-[1.8];
}

.resource-empty-description--reserved {
    @apply min-h-[94rpx];
}

.resource-empty-description--readonly {
    @apply mb-[0];
}

.resource-empty-action {
    @apply min-h-[88rpx] px-[32rpx] py-[20rpx] mx-auto rounded-[24rpx] border-[2rpx] border-solid border-[transparent] bg-[#EDF4FF] inline-flex items-center justify-center gap-[14rpx] text-[28rpx] leading-[1.4] font-semibold text-[#075ECF];
}
</style>
