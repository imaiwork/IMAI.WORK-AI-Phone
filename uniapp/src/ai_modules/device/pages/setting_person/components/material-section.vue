<template>
    <view class="flex flex-col gap-3">
        <view class="flex items-center justify-between">
            <view class="flex items-baseline gap-1">
                <text class="text-[28rpx] font-bold text-[#1A1A1A]">{{ title }}</text>
                <text class="text-xs text-[#999999] font-medium">({{ count }})</text>
            </view>
            <view class="flex items-center text-[#999999]" @click="emit('more')">
                <text class="text-xs">更多</text>
                <u-icon name="arrow-right" size="20"></u-icon>
            </view>
        </view>

        <scroll-view scroll-x class="w-full">
            <view class="flex gap-2.5 pb-1 whitespace-nowrap">
                <view
                    class="inline-flex flex-col items-center justify-center w-[160rpx] h-[214rpx] rounded-[20rpx] bg-[#F4F5F7] flex-shrink-0"
                    @click="emit('add')">
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#000000]/5 flex items-center justify-center mb-1.5">
                        <u-icon name="plus" color="#666666" size="28"></u-icon>
                    </view>
                    <text class="text-xs text-[#666666] font-medium">添加</text>
                </view>

                <view
                    v-for="(item, index) in list"
                    :key="index"
                    class="relative inline-block w-[160rpx] h-[214rpx] rounded-[20rpx] overflow-hidden bg-[#ececec] flex-shrink-0 shadow-[0_2rpx_8rpx_rgba(0,0,0,0.03)]">
                    <image
                        :src="item.pic"
                        class="w-full h-full object-cover"
                        mode="aspectFill"
                        @click.stop="handlePreview(item)"></image>
                    <view
                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[22]"
                        v-if="isVideo">
                        <view
                            class="w-[60rpx] h-[60rpx] rounded-full bg-[#ffffff]/30 border border-solid border-[#ffffff]/40 flex items-center justify-center pl-0.5"
                            @click.stop="emit('play', item)">
                            <u-icon name="play-right-fill" color="#ffffff" size="28"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </scroll-view>

        <view
            class="flex items-center gap-2 px-3 py-2.5 rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#D0E6FF]"
            @click="emit('more')">
            <u-icon name="info-circle" color="#4A90E2" size="26"></u-icon>
            <text class="text-[22rpx] text-[#4A90E2] flex-1">
                当前只展示数据最新<text class="font-bold">3</text>条，点击
                <text class="font-bold underline">查看更多</text>
                可管理全部{{ title }}
            </text>
            <u-icon name="arrow-right" color="#4A90E2" size="22"></u-icon>
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps({
    title: { type: String, required: true },
    count: { type: Number, default: 0 },
    list: { type: Array as () => any[], default: () => [] },
    isVideo: { type: Boolean, default: true },
});

const emit = defineEmits(["add", "more", "play"]);

const handlePreview = (item: any) => {
    uni.previewImage({
        urls: [item.pic],
    });
};
</script>
