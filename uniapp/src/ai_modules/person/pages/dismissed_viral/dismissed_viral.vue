<template>
    <view class="min-h-screen bg-[#F4F6FA] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
        <view class="bg-white">
            <u-navbar
                :border-bottom="false"
                :background="{ background: '#FFFFFF' }"
                title="不感兴趣"
                title-color="#1A1A1A"
                back-icon-color="#1A1A1A"
                title-bold />
        </view>

        <view class="px-[32rpx] pt-[28rpx]">
            <view class="notice-card">
                <view class="notice-icon">
                    <u-icon name="eye-off" color="#888888" size="28"></u-icon>
                </view>
                <text class="notice-text">
                    以下内容已被标记为不感兴趣，AI 将不再推荐同类型内容。如需恢复，点击右侧「撤回」按钮。
                </text>
            </view>

            <view class="count-row">
                <view class="flex items-center min-w-0">
                    <text class="text-[24rpx] text-[#BBBBBB]">共</text>
                    <text class="mx-[8rpx] text-[24rpx] font-bold text-[#888888]">
                        {{ dismissedViralList.length }}
                    </text>
                    <text class="text-[24rpx] text-[#BBBBBB]">条记录</text>
                </view>
                <button
                    class="plain-btn clear-btn"
                    :class="{ disabled: !dismissedViralList.length }"
                    :disabled="!dismissedViralList.length"
                    @click="handleClearDismissedViral">
                    全部清除
                </button>
            </view>

            <view v-if="dismissedViralList.length" class="flex flex-col gap-[20rpx]">
                <view v-for="item in dismissedViralList" :key="item.listKey || item.id" class="dismiss-card">
                    <view class="flex gap-[22rpx] p-[24rpx]">
                        <image
                            v-if="item.cover"
                            :src="item.cover"
                            mode="aspectFill"
                            lazy-load
                            class="w-[136rpx] h-[136rpx] rounded-[20rpx] shrink-0 bg-[#F3F4F6]" />
                        <view
                            v-else
                            class="w-[136rpx] h-[136rpx] rounded-[20rpx] shrink-0 flex flex-col items-center justify-center gap-[6rpx]"
                            :style="{
                                background: `linear-gradient(145deg, ${getPlatformMeta(item.platforms[0] || 'all').color}99, ${getPlatformMeta(item.platforms[0] || 'all').color})`,
                            }">
                            <u-icon name="attach" color="#FFFFFF" size="32"></u-icon>
                            <text class="text-[18rpx] font-semibold text-white opacity-90">
                                {{ item.statusText || "无封面" }}
                            </text>
                        </view>
                        <view class="flex-1 min-w-0">
                            <view class="flex items-center gap-[10rpx] mb-[8rpx] flex-wrap">
                                <text
                                    v-for="platform in item.platforms"
                                    :key="platform"
                                    class="platform-tag"
                                    :style="{ background: getPlatformMeta(platform).color }">
                                    {{ getPlatformMeta(platform).label }}
                                </text>
                                <text
                                    v-if="item.isManualImport"
                                    class="text-[18rpx] font-semibold text-primary bg-[#EBF2FF] px-[12rpx] py-[4rpx] rounded-[8rpx]">
                                    手动入库
                                </text>
                                <text class="text-[20rpx] text-[#888888] line-clamp-1"> {{ item.date }}收录 </text>
                            </view>
                            <text class="title-text">
                                {{ item.title }}
                            </text>
                            <text v-if="item.remark" class="text-[20rpx] text-[#5C7ECC] mt-[8rpx] leading-relaxed block">
                                {{ item.remark }}
                            </text>
                            <!-- <view class="flex items-center gap-[24rpx] mt-[14rpx]">
                                <view class="flex items-center gap-[6rpx]">
                                    <u-icon name="heart" color="#F87171" size="22"></u-icon>
                                    <text class="meta-text">{{ item.likes }}</text>
                                </view>
                                <view class="flex items-center gap-[6rpx]">
                                    <u-icon name="chat" color="#2B6EFF" size="22"></u-icon>
                                    <text class="meta-text">{{ item.comments }}</text>
                                </view>
                            </view> -->
                        </view>
                        <button class="plain-btn restore-btn" @click="handleRestoreDismissedViral(item)">撤回</button>
                    </view>

                    <view class="card-foot">
                        <u-icon name="clock" color="#BBBBBB" size="20"></u-icon>
                        <text class="text-[20rpx] text-[#BBBBBB]">标记于 {{ item.dismissedAt }}</text>
                        <text class="keyword-tag">{{ item.keyword }}</text>
                    </view>
                </view>
            </view>

            <view v-else class="empty-state">
                <view class="empty-icon">
                    <u-icon name="checkmark-circle" color="#2B6EFF" size="56"></u-icon>
                </view>
                <text class="text-[28rpx] font-bold text-[#1A1A1A]">暂无不感兴趣的内容</text>
                <text class="text-[24rpx] text-[#888888] mt-[10rpx]"> AI 会持续为你推荐高质量爆款内容 </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useViralTab, type ViralRealPlatformKey } from "@/ai_modules/person/pages/detail/hooks/useViralTab";

const {
    dismissedViralList,
    fetchDismissedList,
    handleClearDismissedViral,
    handleRestoreDismissedViral,
    viralPlatformTabs,
} = useViralTab();

const getPlatformMeta = (key: ViralRealPlatformKey) =>
    viralPlatformTabs.find((item) => item.key === key) ?? viralPlatformTabs[0];

onShow(() => {
    // 每次进入或返回页面都拉一次最新列表
    fetchDismissedList();
});
</script>

<style lang="scss" scoped>
.clear-btn {
    @apply shrink-0 h-[52rpx] px-[20rpx] m-0 rounded-full text-[22rpx] font-semibold text-[#888888] bg-white border-[2rpx] border-solid border-[#E8ECF0] flex items-center justify-center;

    &.disabled {
        @apply text-[#C9CDD4] bg-[#F4F6FA];
    }
}

.notice-card {
    @apply flex items-start gap-[18rpx] bg-white rounded-[28rpx] px-[28rpx] py-[24rpx];
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.notice-icon {
    @apply w-[36rpx] h-[36rpx] shrink-0 mt-[4rpx] flex items-center justify-center;
}

.notice-text {
    @apply flex-1 text-[24rpx] leading-relaxed text-[#888888];
}

.count-row {
    @apply flex items-center justify-between gap-[20rpx] py-[22rpx];
}

.dismiss-card {
    @apply bg-white rounded-[28rpx] overflow-hidden;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.platform-tag {
    @apply text-[18rpx] font-bold text-white px-[12rpx] py-[5rpx] rounded-[8rpx] shrink-0;
}

.title-text {
    @apply block text-[28rpx] font-semibold leading-snug text-[#1A1A1A] line-clamp-2;
}

.meta-text {
    @apply text-[22rpx] text-[#888888];
}

.restore-btn {
    @apply self-center shrink-0 h-[54rpx] px-[22rpx] rounded-full text-[22rpx] font-semibold text-primary bg-[#EBF2FF] border-[2rpx] border-solid border-[#BAD4FF] flex items-center justify-center whitespace-nowrap;
}

.card-foot {
    @apply mx-[24rpx] py-[16rpx] border-0 border-t border-solid border-[#F3F4F6] flex items-center gap-[8rpx];
}

.keyword-tag {
    @apply ml-auto text-[20rpx] text-[#888888] bg-[#F4F6FA] px-[12rpx] py-[4rpx] rounded-full max-w-[180rpx] line-clamp-1;
}

.empty-state {
    @apply flex flex-col items-center justify-center text-center px-[56rpx] py-[160rpx];
}

.empty-icon {
    @apply w-[128rpx] h-[128rpx] rounded-[32rpx] bg-[#EBF2FF] flex items-center justify-center mb-[28rpx];
}
</style>
