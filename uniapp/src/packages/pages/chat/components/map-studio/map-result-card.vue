<template>
    <view class="map-card">
        <!-- 抓取策略 -->
        <view class="strategy">
            <text class="strategy__title">本次抓取策略：</text>
            <view class="strategy__row">
                <text class="strategy__icon">📄</text>
                <text class="strategy__text">
                    关键词：
                    <text class="strategy__em">{{ query || "—" }}</text>
                    <text v-if="pageLabel">，分页 {{ pageLabel }}</text>
                </text>
            </view>
            <view class="strategy__row strategy__row--last">
                <text class="strategy__icon">🔍</text>
                <text class="strategy__text">过滤含有效电话的商家，自动去重</text>
            </view>
        </view>

        <!-- 商家列表 -->
        <view v-if="!cards.length" class="empty">
            <text class="empty__text">{{ error || "未找到匹配商家" }}</text>
        </view>
        <view v-for="(card, idx) in cards" :key="card.key || idx" class="shop">
            <text class="shop__name">{{ card.name || "未命名" }}</text>
            <view class="shop__row">
                <text class="shop__icon">📍</text>
                <text class="shop__meta">{{ card.addr || "-" }}</text>
            </view>
            <view class="shop__row shop__row--phone">
                <text class="shop__icon">📞</text>
                <text class="shop__meta">{{ card.phone || "-" }}</text>
            </view>
            <view class="shop__footer">
                <text v-if="card.tag" class="shop__tag">{{ card.tag }}</text>
                <view v-if="card.rating !== '' && card.rating != null" class="shop__rating">
                    <text>⭐ {{ card.rating }}</text>
                </view>
                <view
                    v-if="card.phone"
                    class="shop__copy"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="copyPhone(card.phone)">
                    <u-icon name="file-text" :size="22" color="#6B7280"></u-icon>
                    <text>复制</text>
                </view>
            </view>
        </view>

        <!-- 一键存入意向客户：暂无接口，先隐藏 -->
    </view>
</template>

<script setup lang="ts">
import type { MapLeadCard } from "@/api/map_lead";
import { useCopy } from "@/hooks/useCopy";

const props = withDefaults(
    defineProps<{
        cards?: MapLeadCard[];
        query?: string;
        pageLabel?: string;
        error?: string;
    }>(),
    {
        cards: () => [],
        query: "",
        pageLabel: "",
        error: "",
    },
);

const { copy } = useCopy();

const copyPhone = (phone: string) => {
    const p = String(phone || "").trim();
    if (!p) {
        uni.$u.toast("暂无电话");
        return;
    }
    copy(p);
};
</script>

<style lang="scss" scoped>
.map-card {
    @apply bg-white rounded-[28rpx] px-[30rpx] py-[28rpx];
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.strategy {
    @apply border border-solid border-[#EEF1F6] rounded-[24rpx] px-[28rpx] py-[24rpx];
}
.strategy__title {
    @apply block text-[28rpx] font-bold text-[#1F2937] mb-[18rpx];
}
.strategy__row {
    @apply flex items-start gap-x-[14rpx] mb-[12rpx];
}
.strategy__row--last {
    @apply mb-0;
}
.strategy__icon {
    @apply text-[26rpx] leading-[40rpx] flex-shrink-0;
}
.strategy__text {
    @apply text-[26rpx] text-[#6B7280] leading-[40rpx] flex-1;
}
.strategy__em {
    @apply font-semibold text-[#374151];
}
.empty {
    @apply py-[40rpx] flex justify-center;
}
.empty__text {
    @apply text-[26rpx] text-[#9CA3AF];
}
.shop {
    @apply border border-solid border-[#EEF1F6] rounded-[28rpx] px-[30rpx] py-[26rpx] mt-[20rpx];
}
.shop__name {
    @apply block text-[30rpx] font-bold text-primary mb-[18rpx];
}
.shop__row {
    @apply flex items-start gap-x-[14rpx] mb-[14rpx];
}
.shop__row--phone {
    @apply mb-[20rpx];
}
.shop__icon {
    @apply text-[26rpx] leading-[40rpx] flex-shrink-0;
}
.shop__meta {
    @apply text-[26rpx] text-[#4B5563] leading-[40rpx] flex-1 break-all;
}
.shop__footer {
    @apply flex items-center gap-x-[16rpx];
}
.shop__tag {
    @apply text-[22rpx] font-semibold text-primary bg-[#EFF6FF] rounded-[12rpx] px-[18rpx] py-[6rpx];
}
.shop__rating {
    @apply text-[26rpx] font-bold text-[#F59E0B];
}
.shop__copy {
    @apply ml-auto inline-flex items-center gap-x-[8rpx] text-[24rpx] text-[#6B7280] bg-[#F3F4F6] rounded-[16rpx] px-[20rpx] py-[10rpx];
}
</style>
