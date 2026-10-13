<template>
    <view class="grid grid-cols-3 gap-[26rpx]">
        <view v-for="(item, index) in materialList" :key="index" class="relative">
            <view class="h-[220rpx] rounded-[12rpx] relative overflow-hidden" @click="emit('preview', item)">
                <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                <view
                    class="absolute top-1 left-1 flex items-center gap-[4rpx] bg-[#00000066] rounded-full px-[8rpx] py-[2rpx]">
                    <text class="text-[18rpx] text-white font-mono">
                        {{ item.type === "image" ? "图片" : formatAudioTime(item.duration) }}
                    </text>
                </view>
                <view class="absolute bottom-2 w-full z-[89] flex justify-center">
                    <view
                        class="px-3 py-1 text-white text-xs rounded-full border border-solid border-[#ffffff]/30"
                        @click.stop="emit('replace', index)">
                        替换
                    </view>
                </view>
            </view>
            <view
                class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                @click="emit('delete', index)">
                <u-icon name="close" color="#ffffff" size="16"></u-icon>
            </view>
        </view>
        <view
            class="bg-white h-[220rpx] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[10rpx]"
            @click="emit('upload')">
            <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                <u-icon name="plus" color="#0065fb" size="28" />
            </view>
            <text class="text-xs text-primary font-semibold">添加素材</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";

const props = defineProps<{
    materialList: any[];
}>();

const emit = defineEmits<{
    (e: "preview", item: any): void;
    (e: "replace", index: number): void;
    (e: "delete", index: number): void;
    (e: "upload"): void;
}>();
</script>

<style scoped></style>
