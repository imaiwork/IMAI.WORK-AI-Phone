<template>
    <popup-bottom
        :model-value="modelValue"
        height="auto"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[40rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[66rpx] rounded bg-[#E5EAF3]" />
                    </view>
                </view>
                <view class="mt-[32rpx] mb-[8rpx] flex items-center justify-between">
                    <text class="text-[34rpx] font-bold text-[#0F172A]">邀请成员</text>
                    <view
                        class="flex h-[56rpx] w-[56rpx] items-center justify-center rounded-full bg-[#F1F5F9]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#64748B" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="flex flex-col items-center px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <text class="mb-[32rpx] text-center text-[26rpx] text-[#64748B]">
                    任意成员都可分享邀请码，对方在「加入团队」中输入即可
                </text>
                <view
                    class="flex items-center gap-[20rpx] rounded-[28rpx] border-[3rpx] border-dashed border-[#C6D4EC] bg-[#F4F7FD] px-[44rpx] py-[28rpx]"
                    @click="emit('copy')">
                    <text class="text-[40rpx] font-extrabold tracking-[8rpx] text-primary">
                        {{ code || '--------' }}
                    </text>
                    <u-icon name="file-text" size="32" color="#94A3B8" />
                </view>
                <view
                    class="mt-[40rpx] w-full rounded-[32rpx] bg-primary py-[28rpx] text-center text-[30rpx] font-bold text-white"
                    @click="emit('copy')">
                    复制邀请码
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean
    code: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'copy'): void
}>()
</script>
