<template>
    <view>
        <view class="grid grid-cols-3 gap-[26rpx] p-4">
            <view v-for="(item, index) in materialList" :key="index" class="relative">
                <view class="h-[220rpx] rounded-[12rpx] relative overflow-hidden" @click="emit('preview', item)">
                    <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                    <view
                        class="absolute bottom-0 h-[40rpx] w-full bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-[88]">
                        <image
                            v-if="item.type === 'image'"
                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                            class="w-[24rpx] h-[24rpx]"></image>
                        <image
                            v-else
                            src="@/ai_modules/digital_human/static/icons/video.svg"
                            class="w-[24rpx] h-[24rpx]"></image>
                    </view>
                    <view class="absolute bottom-4 w-full z-[89] flex justify-center">
                        <view class="dh-version-name" @click.stop="emit('replace', index)"> 替换 </view>
                    </view>
                </view>
                <view
                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                    @click="emit('delete', index)">
                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                </view>
            </view>
            <view
                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                @click="emit('upload')">
                <image src="@/ai_modules/digital_human/static/icons/add.svg" class="w-[40rpx] h-[40rpx]"></image>
                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
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
