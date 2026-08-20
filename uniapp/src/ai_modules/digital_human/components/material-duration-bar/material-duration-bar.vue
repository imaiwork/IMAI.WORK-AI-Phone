<template>
    <view>
        <view class="flex gap-2">
            <view class="flex-1 rounded-[16rpx] bg-[#F8FAFF] border border-[#E8EFFF] px-3 py-[14rpx]">
                <view class="flex items-center gap-1 mb-1">
                    <u-icon name="clock" color="#6B7280" size="20" />
                    <text class="text-[20rpx] text-[#6B7280]">总限制</text>
                </view>
                <view class="flex items-end gap-[4rpx]">
                    <text class="text-[36rpx] font-extrabold text-[#374151] leading-none">
                        {{ totalLimit }}
                    </text>
                    <text class="text-[20rpx] text-[#9CA3AF] mb-[2rpx]">秒</text>
                </view>
            </view>

            <view class="flex-1 rounded-[16rpx] bg-[#F0F5FF] border border-[#C7D9FF] px-3 py-[14rpx]">
                <view class="flex items-center gap-1 mb-1">
                    <u-icon name="checkmark-circle" color="#0065fb" size="20" />
                    <text class="text-[20rpx] text-primary">已使用</text>
                </view>
                <view class="flex items-end gap-[4rpx]">
                    <text class="text-[36rpx] font-extrabold text-primary leading-none">
                        {{ usedDuration }}
                    </text>
                    <text class="text-[20rpx] text-[#0065fb]/60 mb-[2rpx]">秒</text>
                </view>
            </view>

            <view
                class="flex-1 rounded-[16rpx] border px-3 py-[14rpx] transition-colors"
                :class="remainingDuration > 0 ? 'bg-[#F0FFF4] border-[#BBF7D0]' : 'bg-[#FFF0F0] border-[#FECACA]'">
                <view class="flex items-center gap-1 mb-1">
                    <u-icon
                        name="arrow-right-circle"
                        :color="remainingDuration > 0 ? '#16A34A' : '#EF4444'"
                        size="20" />
                    <text class="text-[20rpx]" :class="remainingDuration > 0 ? 'text-[#16A34A]' : 'text-[#EF4444]'">
                        剩余可传
                    </text>
                </view>
                <view class="flex items-end gap-[4rpx]">
                    <text
                        class="text-[36rpx] font-extrabold leading-none"
                        :class="remainingDuration > 0 ? 'text-[#16A34A]' : 'text-[#EF4444]'">
                        {{ remainingDuration }}
                    </text>
                    <text
                        class="text-[20rpx] mb-[2rpx]"
                        :class="remainingDuration > 0 ? 'text-[#16A34A]/60' : 'text-[#EF4444]/60'">
                        秒
                    </text>
                </view>
            </view>
        </view>

        <view class="mt-3 w-full h-[8rpx] bg-[#E5E7EB] rounded-full overflow-hidden">
            <view
                class="h-full rounded-full transition-all duration-500"
                :class="usagePercent >= 100 ? 'bg-[#EF4444]' : 'bg-primary'"
                :style="{ width: Math.min(usagePercent, 100) + '%' }" />
        </view>
        <view class="flex items-center justify-between mt-1">
            <text class="text-[20rpx] text-[#9CA3AF]">
                图片按 {{ montageConfig.imageDuration }}s/张，视频按实际时长
            </text>
            <text class="text-[20rpx] font-medium" :class="usagePercent >= 100 ? 'text-[#EF4444]' : 'text-[#6B7280]'">
                {{ usagePercent }}%
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { montageConfig } from "@/ai_modules/digital_human/config";

const props = defineProps<{
    materialList: any[];
}>();

/** 总限制（秒） */
const totalLimit = computed(() => {
    return Number(montageConfig.materialTotalDuration) * 60;
});

/** 已使用时长（秒）—— 保留1位小数，避免浮点长尾 */
const usedDuration = computed(() => {
    const imgDuration = Number(montageConfig.imageDuration) || 0;
    const raw = props.materialList.reduce((acc, item) => {
        return acc + (item.type === "image" ? imgDuration : Number(item.duration) || 0);
    }, 0);
    // 保留1位小数，去掉浮点长尾（如 12.300000000001 → 12.3）
    return Math.round(raw * 10) / 10;
});

/** 剩余可上传（秒）—— 同样保留1位小数 */
const remainingDuration = computed(() => {
    const remain = totalLimit.value - usedDuration.value;
    return remain > 0 ? Math.round(remain * 10) / 10 : 0;
});

/** 使用百分比（用于进度条） */
const usagePercent = computed(() => {
    if (!totalLimit.value) return 0;
    return Math.min(Math.round((usedDuration.value / totalLimit.value) * 100), 100);
});

defineExpose({ remainingDuration });
</script>

<style scoped></style>
