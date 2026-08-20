<template>
    <view class="w-full h-full relative flex flex-col">
        <view class="grow min-h-0 relative overflow-hidden rounded-[24rpx] bg-black">
            <image :src="item.pic" class="w-full h-full rounded-[24rpx]" mode="aspectFill" />

            <view
                v-if="showMore"
                class="absolute top-[12rpx] right-[12rpx] z-[8888] w-[56rpx] h-[56rpx] rounded-full bg-[#000000]/30 flex items-center justify-center active:opacity-70"
                @click="handleMore">
                <u-icon name="more-dot-fill" color="#fff" size="22" />
            </view>

            <view class="absolute top-0 left-0 z-50 w-full h-full">
                <template v-if="getStatus(item) == 1">
                    <view class="w-full h-full flex items-center justify-center" @click="handlePlay(item.result_url)">
                        <image src="/static/images/icons/play.svg" class="w-[64rpx] h-[64rpx]" />
                    </view>
                </template>

                <template v-else>
                    <view class="w-full h-full bg-[#000000]/50 flex flex-col items-center justify-center px-[16rpx]">
                        <template v-if="getStatus(item) == 2">
                            <view class="bg-[#EF4444] px-[16rpx] py-[6rpx] rounded-full mb-[10rpx]">
                                <text class="text-[22rpx] text-white font-medium">生成失败</text>
                            </view>
                            <text
                                class="text-[20rpx] text-[#ffffff]/70 text-center leading-relaxed line-clamp-2 whitespace-pre-line">
                                {{ item.remark || "请检查训练的视频文件" }}
                            </text>
                        </template>

                        <template v-else>
                            <text class="rotation mb-[8rpx]"></text>
                            <text class="text-[24rpx] text-white font-medium mb-[6rpx]">正在生成中</text>
                            <text class="text-[20rpx] text-[#ffffff]/60">几分钟即可生成形象</text>
                        </template>
                    </view>
                </template>
            </view>
        </view>

        <view class="px-[8rpx] mt-[12rpx]" v-if="showName">
            <text class="text-[24rpx] font-medium text-gray-700 text-center line-clamp-1 block">
                {{ item.name }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { saveVideoToPhotosAlbum } from "@/utils/file";
const props = withDefaults(
    defineProps<{
        item: Record<string, any>;
        showName?: boolean;
        showMore?: boolean;
    }>(),
    {
        item: () => ({
            id: 0,
            name: "",
            pic: "",
            status: 0,
            url: "",
            remark: "",
            source_type: "",
        }),
        showName: true,
        showMore: true,
    },
);

const emit = defineEmits(["play", "delete", "download"]);

const getStatus = (data: Record<string, any>): number => {
    const { status, source_type } = data;

    const anchorStatusMapping: Record<string, any> = {
        human_anchor: {
            1: 1,
            2: 2,
            default: 0,
        },
        shanjian_anchor: {
            1: 1,
            2: 2,
            5: 2,
            3: 3,
            default: 0,
        },
        public_anchor: {
            1: 0,
            2: 1,
            3: 2,
            default: 0,
        },
    };
    return anchorStatusMapping?.[source_type]?.[status] || anchorStatusMapping?.[source_type]?.["default"];
};

const handlePlay = (url: string) => {
    emit("play", url);
};

const handleMore = () => {
    const { id, result_url, source_type } = props.item;
    uni.showActionSheet({
        itemList: ["下载视频", "播放视频", "删除"],
        success: (res) => {
            const { tapIndex } = res;
            if (tapIndex == 0) {
                saveVideoToPhotosAlbum(result_url);
            }
            if (tapIndex == 1) {
                emit("play", result_url);
            }
            if (tapIndex == 2) {
                emit("delete", id, source_type);
            }
        },
    });
};
</script>

<style scoped lang="scss"></style>
