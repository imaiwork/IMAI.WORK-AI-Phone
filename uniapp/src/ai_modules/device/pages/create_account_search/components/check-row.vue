<template>
    <view
        class="flex items-start gap-[16rpx] py-[20rpx] border-[0] border-b border-solid border-[#F4F6F9] last:border-[0]"
        @click="handleClick"
    >
        <view
            class="w-[36rpx] h-[36rpx] rounded-[10rpx] border border-solid flex items-center justify-center flex-shrink-0 mt-[2rpx]"
            :class="checkboxClass"
        >
            <u-icon v-if="checked" name="checkmark" color="#fff" size="18" />
        </view>
        <view class="flex-1 min-w-0">
            <text class="text-[27rpx] font-semibold text-[#0D1117]">{{ name }}</text>
            <text v-if="desc" class="block mt-[8rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                {{ desc }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    checked: boolean
    name: string
    desc?: string
    /** 硬规则：勾上且不给关 */
    lock?: boolean
}>()

const emit = defineEmits<{
    (e: 'change'): void
}>()

const checkboxClass = computed(() => {
    if (props.lock) return 'bg-[#9DC0FF] border-[#9DC0FF]'
    return props.checked ? 'bg-primary border-primary' : 'bg-white border-[#D8DDE6]'
})

const handleClick = () => {
    if (props.lock) return
    emit('change')
}
</script>

<style scoped></style>
