<template>
    <scroll-view scroll-x class="w-full">
        <view class="flex items-center gap-[12rpx] pb-[4rpx]">
            <text class="text-[23rpx] text-[#9AA3B2] shrink-0">账号</text>
            <view
                v-for="(item, index) in accounts"
                :key="item.id"
                class="shrink-0 flex items-center gap-[10rpx] px-[20rpx] py-[12rpx] rounded-[20rpx] border-[2rpx] border-solid"
                :class="
                    index === activeIndex
                        ? 'border-primary bg-primary-light-9'
                        : 'border-[#EEF1F6] bg-white'
                "
                @click="emit('change', index)"
            >
                <text
                    class="text-[24rpx] font-semibold"
                    :class="index === activeIndex ? 'text-primary' : 'text-[#4E5969]'"
                >
                    {{ item.nickname || item.account }}
                </text>
                <text v-if="item.device_code" class="text-[21rpx] text-[#9AA3B2]">
                    {{ item.device_code }}
                </text>
            </view>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
import type { PersonaAccount } from '../enums'

defineProps<{
    accounts: PersonaAccount[]
    activeIndex: number
}>()

const emit = defineEmits<{
    (e: 'change', index: number): void
}>()
</script>
