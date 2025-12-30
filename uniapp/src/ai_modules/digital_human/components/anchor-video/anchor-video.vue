<template>
    <view class="w-full h-full relative flex flex-col">
        <view class="grow min-h-0 relative overflow-hidden rounded-lg bg-black">
            <view class="h-full w-full flex items-center justify-center flex-col">
                <view class="flex justify-center items-center h-full w-full relative z-10">
                    <image :src="item.pic" class="w-full mx-auto h-full rounded-lg" mode="aspectFill"></image>
                </view>
            </view>
            <view class="absolute top-1 right-1 z-[8888]">
                <view class="p-2" style="transform: rotate(90deg)" @click="handleMore">
                    <u-icon name="more-dot-fill" color="#fff"></u-icon>
                </view>
            </view>
            <view class="absolute top-0 left-0 z-50 w-full h-full">
                <template v-if="getStatus(item) == 1">
                    <view class="w-full h-full flex items-center justify-center gap-1 text-center px-2 text-white">
                        <view class="rounded-full bg-[#ffffff33] w-[48rpx] h-[48rpx]" @click="handlePlay(item.url)">
                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                        </view>
                    </view>
                </template>
                <template v-else>
                    <view class="bg-[#0000005E] w-full h-full flex flex-col items-center justify-center pt-4">
                        <template class="" v-if="getStatus(item) == 2">
                            <view class="w-6 h-6 flex items-center justify-center rounded-full bg-error mb-2">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/video2.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                            <view class="text-center text-white text-[22rpx] h-[68rpx]">
                                {{ item.remark || "生成失败" }}
                            </view>
                            <view class="text-[#ffffff80] text-center text-[22rpx] h-[68rpx]">
                                （请检查训练的视频文件）
                            </view>
                        </template>
                        <template v-else>
                            <view class="w-6 h-6 flex items-center justify-center rounded-full bg-primary mb-2">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/pic2.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                            <view class="text-xs text-white h-[68rpx]">正在生成中</view>
                            <view class="text-[22rpx] text-white h-[68rpx]">几分钟即可生成形象</view>
                        </template>
                    </view>
                </template>
            </view>
        </view>
        <view class="px-2 mt-2" v-if="showName">
            <text class="line-clamp-1 text-center text-sm">
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
    }
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

    return anchorStatusMapping[source_type][status] || anchorStatusMapping[source_type]["default"];
};

const handlePlay = (url: string) => {
    emit("play", url);
};

const handleMore = () => {
    const { id, url, source_type } = props.item;
    uni.showActionSheet({
        itemList: ["下载视频", "播放视频", "删除"],
        success: (res) => {
            const { tapIndex } = res;
            if (tapIndex == 0) {
                saveVideoToPhotosAlbum(url);
            }
            if (tapIndex == 1) {
                emit("play", url);
            }
            if (tapIndex == 2) {
                emit("delete", id, source_type);
            }
        },
    });
};
</script>
