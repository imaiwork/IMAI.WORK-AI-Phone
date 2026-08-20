<template>
    <view class="bg-white rounded-t-[48rpx] pt-[18rpx] pb-[28rpx] px-[32rpx]">
        <view class="w-[80rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-[28rpx]"></view>
        <view class="flex items-center gap-[20rpx]">
            <view
                v-if="customer"
                class="w-[80rpx] h-[80rpx] rounded-[24rpx] flex items-center justify-center shrink-0 overflow-hidden"
                :class="customer.avatarClass">
                <image v-if="customer.avatar" :src="customer.avatar" class="w-full h-full" mode="aspectFill"></image>
                <text v-else class="text-white text-[18rpx] font-bold text-center leading-tight">
                    {{ avatarText }}
                </text>
            </view>
            <view class="flex-1 min-w-0">
                <text class="text-sm font-bold text-[#111827] block">{{ title }}</text>
                <text class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] block line-clamp-1">
                    {{ subtitleText }}
                </text>
            </view>
            <view
                class="w-[64rpx] h-[64rpx] rounded-full bg-[#F4F6FA] flex items-center justify-center"
                @click.stop="emit('close')">
                <u-icon name="close" color="#6B7280" size="26"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
interface PopupCustomer {
    name: string;
    avatarClass: string;
    avatar?: string;
}

const props = withDefaults(
    defineProps<{
        customer?: PopupCustomer | null;
        title: string;
        subtitle?: string;
    }>(),
    {
        customer: null,
        subtitle: "",
    },
);

const emit = defineEmits<{
    (event: "close"): void;
}>();

const avatarText = computed(() => props.customer?.name.slice(0, 2) || "");
const subtitleText = computed(() => props.subtitle || props.customer?.name || "");
</script>
