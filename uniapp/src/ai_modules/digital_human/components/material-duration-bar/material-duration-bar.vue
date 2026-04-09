<template>
    <view class="mx-4 flex items-start justify-between">
        <view class="flex-1 pr-[20rpx]">
            <view class="flex items-center">
                <text class="font-medium">混剪素材（共{{ materialList.length }}个）</text>
                <view
                    class="flex items-center px-[16rpx] py-[10rpx] rounded-full shrink-0 mt-[4rpx] transition-colors"
                    :class="remainingDuration > 0 ? 'bg-[#F0F5FF]' : 'bg-[#FFF0F0]'">
                    <u-icon name="clock" :color="remainingDuration > 0 ? '#0065fb' : '#FF3141'" size="24"> </u-icon>
                    <text
                        class="text-[22rpx] ml-[6rpx]"
                        :class="remainingDuration > 0 ? 'text-[#0065fb]' : 'text-[#FF3141]'">
                        剩余 <text class="font-extrabold mx-[4rpx] text-[26rpx]">{{ remainingDuration }}</text> 秒
                    </text>
                </view>
            </view>
            <view class="mt-1 text-xs text-[#0000004d] leading-relaxed">
                总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                    montageConfig.imageDuration
                }}秒/张，视频按实际时长/个)
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { montageConfig } from "@/ai_modules/digital_human/config";

const props = defineProps<{
    materialList: any[];
}>();

const remainingDuration = computed(() => {
    if (!montageConfig || !montageConfig.materialTotalDuration) return 0;

    const maxDurationInSeconds = Number(montageConfig.materialTotalDuration) * 60;

    const imgDuration = Number(montageConfig.imageDuration) || 0;

    const currentList = props.materialList || [];
    const usedDuration = currentList.reduce((total, item) => {
        if (item.type === "image") {
            return total + imgDuration;
        } else {
            return total + (Number(item.duration) || 0);
        }
    }, 0);

    const remain = maxDurationInSeconds - usedDuration;
    return remain > 0 ? Math.floor(remain) : 0;
});
</script>

<style scoped></style>
