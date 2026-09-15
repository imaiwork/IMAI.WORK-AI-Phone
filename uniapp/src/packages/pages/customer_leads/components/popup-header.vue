<template>
    <view class="popup-header">
        <view class="w-[64rpx] h-[8rpx] rounded-full bg-[#DCE3EB] mx-auto mb-[12rpx]"></view>
        <view class="flex items-center gap-[16rpx]">
            <view class="flex-1 min-w-0">
                <text class="text-[34rpx] font-semibold leading-snug text-[#1E293B] block">{{
                    title
                }}</text>
                <text
                    v-if="subtitleText"
                    class="text-[24rpx] leading-relaxed text-[#617087] mt-[8rpx] block break-all"
                >
                    {{ subtitleText }}
                </text>
            </view>
            <view
                class="w-[88rpx] h-[88rpx] shrink-0 flex items-center justify-center"
                hover-class="opacity-70"
                role="button"
                aria-label="关闭弹窗"
                @click.stop="emit('close')"
            >
                <view
                    class="w-[56rpx] h-[56rpx] rounded-full bg-[#F1F4F8] text-[#617087] flex items-center justify-center"
                >
                    <u-icon name="close" color="currentColor" size="28"></u-icon>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
interface PopupCustomer {
    name: string
    avatarClass: string
    avatar?: string
}

const props = withDefaults(
    defineProps<{
        customer?: PopupCustomer | null
        title: string
        subtitle?: string
    }>(),
    {
        customer: null,
        subtitle: ''
    }
)

const emit = defineEmits<{
    (event: 'close'): void
}>()

const subtitleText = computed(() =>
    [props.customer?.name, props.subtitle].filter(Boolean).join(' · ')
)
</script>

<style scoped lang="scss">
.popup-header {
    @apply bg-white rounded-t-[48rpx] pt-[16rpx] pb-[24rpx] pl-[32rpx] pr-[16rpx] border-[0] border-b-[2rpx] border-solid border-[#E7ECF2];
}
</style>
