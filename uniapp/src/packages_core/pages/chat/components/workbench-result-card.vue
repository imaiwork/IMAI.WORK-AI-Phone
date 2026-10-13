<template>
    <view class="mt-[8rpx]">
        <view
            v-if="title"
            class="text-[24rpx] mb-[12rpx]"
            :class="kind === 'image' && urls.length ? 'text-[#16A34A]' : 'text-[#6B7280]'">
            {{ title }}
        </view>

        <!-- 图片结果 -->
        <view v-if="kind === 'image' && urls.length" class="wb-grid">
            <image
                v-for="(url, idx) in urls"
                :key="idx"
                :src="url"
                mode="aspectFill"
                class="w-full h-[280rpx] rounded-[16rpx] bg-[#EEF2F7]"
                @click="previewImage(idx)" />
        </view>

        <!-- 视频结果 -->
        <view v-else-if="kind === 'video' && urls.length" class="flex flex-col gap-y-[12rpx]">
            <video
                v-for="(url, idx) in urls"
                :key="idx"
                :src="url"
                class="w-full h-[360rpx] rounded-[16rpx] bg-[#111111]"
                controls
                object-fit="contain" />
        </view>

        <!-- PPT 结果 -->
        <scroll-view v-else-if="kind === 'ppt' && slides.length" scroll-x class="w-full">
            <view class="flex gap-x-[16rpx]">
                <view v-for="slide in slides" :key="slide.page" class="w-[280rpx] flex-shrink-0">
                    <image
                        v-if="slide.url"
                        :src="slide.url"
                        mode="aspectFill"
                        class="w-[280rpx] h-[160rpx] rounded-[12rpx] bg-[#EEF2F7]"
                        @click="previewPpt(slide.url)" />
                    <view
                        v-else
                        class="w-[280rpx] h-[160rpx] rounded-[12rpx] bg-[#EEF2F7] flex items-center justify-center text-[22rpx] text-[#9CA3AF]">
                        {{ slide.error || (slide.loading ? "生成中..." : "暂无图") }}
                    </view>
                    <text class="block mt-[8rpx] text-[22rpx] text-[#4B5563] whitespace-normal">
                        {{ slide.page }}. {{ slide.title }}
                    </text>
                </view>
            </view>
        </scroll-view>

        <!-- 地图结果 -->
        <view v-else-if="kind === 'map' && cards.length">
            <view
                v-for="(card, idx) in cards"
                :key="card.key || idx"
                class="py-[16rpx] border-b border-solid border-[#F1F5F9]">
                <view class="text-[28rpx] font-semibold text-[#111827] mb-[6rpx] line-clamp-1">
                    {{ idx + 1 }}. {{ card.name || "未命名" }}
                </view>
                <view class="text-[24rpx] text-[#6B7280] leading-normal break-all">
                    地址：{{ card.addr || "-" }}
                </view>
                <view class="text-[24rpx] text-[#6B7280] leading-normal">
                    电话：{{ card.phone || "-" }}
                </view>
                <view class="flex gap-x-[16rpx] mt-[6rpx] text-[22rpx] text-[#9CA3AF]">
                    <text v-if="card.tag">{{ card.tag }}</text>
                    <text v-if="card.rating">评分 {{ card.rating }}</text>
                </view>
            </view>
        </view>

        <view v-else-if="text" class="wb-text">{{ text }}</view>
    </view>
</template>

<script setup lang="ts">
interface WorkbenchSlideItem {
    page: number;
    title: string;
    content?: string;
    url?: string;
    loading?: boolean;
    error?: string;
}

interface WorkbenchMapCard {
    key: string;
    name: string;
    addr: string;
    phone: string;
    tag: string;
    rating: string | number;
}

const props = withDefaults(
    defineProps<{
        kind: "image" | "video" | "ppt" | "map" | "text";
        title?: string;
        text?: string;
        urls?: string[];
        slides?: WorkbenchSlideItem[];
        cards?: WorkbenchMapCard[];
    }>(),
    {
        title: "",
        text: "",
        urls: () => [],
        slides: () => [],
        cards: () => [],
    },
);

const previewImage = (index: number) => {
    uni.previewImage({ urls: props.urls, current: props.urls[index] });
};

const previewPpt = (url: string) => {
    const urls = props.slides.map((s) => s.url).filter(Boolean) as string[];
    uni.previewImage({ urls, current: url });
};
</script>

<style lang="scss" scoped>
/* grid / pre-wrap 小程序原子类不稳定，保留少量自定义 */
.wb-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12rpx;
}
.wb-text {
    @apply text-[26rpx] text-[#374151] leading-relaxed;
    white-space: pre-wrap;
}
</style>
