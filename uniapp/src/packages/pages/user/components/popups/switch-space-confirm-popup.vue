<template>
    <popup-bottom
        :model-value="modelValue"
        title="切换空间"
        height="62%"
        custom-class="bg-white"
        :mask-close-able="!loading"
        :clearable="!loading"
        @update:model-value="emit('update:modelValue', $event)">
        <template #content>
            <view class="flex h-full min-h-0 flex-col px-[40rpx]">
                <view class="min-h-0 flex-1 overflow-y-auto pb-[24rpx]">
                    <text class="block text-center text-[28rpx] leading-[44rpx] text-[#64748B]">
                        确认切换到「
                        <text class="font-semibold text-[#0F172A]">{{ targetName }}</text>
                        」？
                    </text>

                    <view class="mt-[28rpx] flex items-stretch gap-[16rpx]">
                        <view class="min-w-0 flex-1 rounded-[24rpx] border-[2rpx] border-solid border-[#EEF2F7] bg-[#F8FAFC] px-[24rpx] py-[22rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="h-[12rpx] w-[12rpx] rounded-full bg-[#94A3B8]" />
                                <text class="text-[22rpx] font-semibold text-[#94A3B8]">当前</text>
                            </view>
                            <text class="mt-[10rpx] block truncate text-[28rpx] font-bold text-[#0F172A]">
                                {{ fromName }}
                            </text>
                        </view>
                        <view class="flex flex-shrink-0 items-center text-[#94A3B8]">
                            <u-icon name="arrow-right" color="#94A3B8" size="28" />
                        </view>
                        <view
                            class="min-w-0 flex-1 rounded-[24rpx] border-[2rpx] border-solid border-[rgba(37,99,235,0.18)] bg-[#EEF4FF] px-[24rpx] py-[22rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="h-[12rpx] w-[12rpx] rounded-full bg-primary" />
                                <text class="text-[22rpx] font-semibold text-[#64748B]">目标</text>
                            </view>
                            <text class="mt-[10rpx] block truncate text-[28rpx] font-bold text-[#0F172A]">
                                {{ targetName }}
                            </text>
                        </view>
                    </view>

                    <view class="mt-[28rpx] rounded-[24rpx] border-[2rpx] border-solid border-[#FDE68A] bg-[#FFFBEB] px-[28rpx] py-[24rpx]">
                        <text class="mb-[16rpx] block text-[24rpx] font-bold text-[#B45309]">切换后需注意</text>
                        <view
                            v-for="(item, index) in TEAM_SPACE_SWITCH_IMPACTS"
                            :key="index"
                            class="mb-[16rpx] flex items-start gap-[16rpx] last:mb-0">
                            <view
                                class="mt-[4rpx] flex h-[36rpx] w-[36rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#FEF3C7] text-[22rpx] font-bold text-[#B45309]">
                                {{ index + 1 }}
                            </view>
                            <text class="flex-1 text-[26rpx] leading-[40rpx] text-[#57534E]">{{ item }}</text>
                        </view>
                    </view>
                </view>

                <view
                    class="grid flex-shrink-0 grid-cols-2 gap-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F1F5F9] pt-[24rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="flex items-center justify-center rounded-[28rpx] border-[2rpx] border-solid border-[#E2E8F0] bg-[#F8FAFC] py-[26rpx] text-[28rpx] font-semibold text-[#475569]"
                        :class="loading ? 'opacity-50' : ''"
                        @click="!loading && emit('cancel')">
                        取消
                    </view>
                    <view
                        class="flex items-center justify-center rounded-[28rpx] bg-primary py-[26rpx] text-[28rpx] font-semibold text-white shadow-[0_8rpx_28rpx_rgba(37,99,235,0.3)]"
                        :class="loading ? 'opacity-70' : ''"
                        @click="!loading && emit('confirm')">
                        {{ loading ? "切换中..." : "确认切换" }}
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { TEAM_SPACE_SWITCH_IMPACTS } from "@/utils/teamSwitchTip";

defineProps<{
    modelValue: boolean;
    fromName: string;
    targetName: string;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm"): void;
    (e: "cancel"): void;
}>();
</script>
