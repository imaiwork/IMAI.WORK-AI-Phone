<template>
    <view class="act-row" @click.stop="emit('click')">
        <text class="w-[36%] shrink-0 text-[26rpx] text-[#617087] leading-relaxed">{{
            title
        }}</text>
        <text
            class="flex-1 min-w-0 text-[26rpx] text-[#1E293B] text-right leading-relaxed break-all"
            >{{ desc }}</text
        >
        <u-icon v-if="showArrow" name="arrow-right" color="#617087" size="24"></u-icon>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string
        desc?: string
        showArrow?: boolean
    }>(),
    {
        desc: '',
        showArrow: true
    }
)

const emit = defineEmits<{
    (event: 'click'): void
}>()
</script>

<style scoped lang="scss">
.act-row {
    @apply relative flex items-start min-h-[88rpx] py-[24rpx] gap-[20rpx];
}

.act-row::after {
    @apply absolute right-0 bottom-0 left-0 h-[2rpx] bg-[#E7ECF2];

    content: '';
}

.act-row:last-child::after {
    @apply hidden;
}
</style>
