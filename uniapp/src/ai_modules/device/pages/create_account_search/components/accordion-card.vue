<template>
    <!-- 不加 overflow-hidden：卡里的 data-select 下拉是绝对定位的，
         祖先一旦裁剪就会被切掉半截（同 sph/create_task 加微微信那张卡） -->
    <view class="bg-white rounded-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
        <view
            class="flex gap-[10rpx] px-[28rpx] py-[22rpx]"
            :class="[
                open
                    ? 'items-start border-[0] border-b border-solid border-[#F0F2F5]'
                    : 'items-center'
            ]"
            @click="emit('toggle')"
        >
            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full flex-shrink-0 mt-[4rpx]" />
            <view class="flex-1 min-w-0">
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">{{ title }}</text>
                <text
                    v-if="open && sub"
                    class="block mt-[8rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed"
                >
                    {{ sub }}
                </text>
            </view>
            <!-- 展开时值就在下面看得见，不用在头上重复一遍 -->
            <text
                v-if="!open"
                class="text-[22rpx] flex-shrink-0 max-w-[46%] line-clamp-1"
                :class="warn ? 'text-[#F86E21] font-semibold' : 'text-[#8A93A0]'"
            >
                {{ value }}
            </text>
            <slot name="head" />
            <u-icon
                :name="open ? 'arrow-up' : 'arrow-down'"
                size="20"
                color="#C0C4CC"
                class="flex-shrink-0"
            />
        </view>
        <view v-if="open">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    title: string
    sub?: string
    value?: string
    warn?: boolean
    open: boolean
}>()

const emit = defineEmits<{
    (e: 'toggle'): void
}>()
</script>

<style scoped></style>
