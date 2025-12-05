<template>
    <view class="w-full h-full relative flex flex-col">
        <view class="grow min-h-0 relative overflow-hidden rounded-lg bg-black">
            <view class="h-full w-full flex items-center justify-center flex-col">
                <view class="flex justify-center items-center h-full w-full relative z-10">
                    <image :src="item.pic" class="w-full mx-auto h-full rounded-lg" mode="aspectFill"></image>
                    <view v-if="item.automatic_clip == 1" class="absolute left-2 top-2 text-[20rpx] text-white"
                        >AI剪辑</view
                    >
                </view>
            </view>
            <view class="absolute top-2 right-2 z-[8888]" v-if="showMore">
                <view class="" style="transform: rotate(90deg)" @click="handleMore">
                    <u-icon name="more-dot-fill" color="#fff"></u-icon>
                </view>
            </view>
            <view class="absolute top-0 left-0 z-50 w-full h-full">
                <template v-if="item.status == 1">
                    <view class="w-full h-full flex items-center justify-center gap-1 text-center px-2 text-white">
                        <view
                            v-if="showPlay"
                            class="rounded-full bg-[#ffffff33] w-[68rpx] h-[68rpx]"
                            @click="handlePlay(item.clip_video_url || item.video_url)">
                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                        </view>
                    </view>
                    <slot name="content"></slot>
                    <view
                        v-if="item.automatic_clip == 1"
                        class="absolute bottom-[160rpx] left-0 w-full z-[51] text-[#ffffff80] text-[22rpx] text-center">
                        <template v-if="item.clip_status == 1 || item.clip_status == 2"> AI智能剪辑中... </template>
                        <template v-if="item.clip_status == 3">AI智能剪辑完成</template>
                        <template v-if="item.clip_status == 4">AI智能剪辑失败</template>
                    </view>
                </template>
                <template v-else>
                    <view class="bg-[#0000005E] w-full h-full flex flex-col items-center justify-center pt-4">
                        <template class="" v-if="item.status == 2">
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
                            <view class="text-[22rpx] text-white h-[68rpx]">几分钟即可生成视频</view>
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
import { useAppStore } from "@/stores/app";
import { saveVideoToPhotosAlbum } from "@/utils/file";
const props = withDefaults(
    defineProps<{
        item: Record<string, any>;
        showVersion?: boolean;
        showPlay?: boolean;
        showMore?: boolean;
        showName?: boolean;
    }>(),
    {
        item: () => ({
            id: 0,
            name: "",
            pic: "",
            status: 0,
            video_url: "",
            clip_video_url: "",
            model_version: "",
            remark: "",
            automatic_clip: 0,
            clip_status: "",
        }),
        showVersion: true,
        showPlay: true,
        showMore: false,
        showName: true,
    }
);

const emit = defineEmits(["play", "delete", "retry", "download", "preview"]);

const appStore = useAppStore();
const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel || []);

const modelVersionMap = computed(() => {
    if (modelChannel.value.length > 0) {
        return modelChannel.value.reduce((acc: Record<string, string>, item: any) => {
            acc[item.id] = item.name;
            return acc;
        }, {});
    }
    return {};
});

const handlePlay = (url: string) => {
    emit("play", url);
};

const handleMore = () => {
    const { status, id, clip_status, automatic_clip } = props.item;
    let itemList = [];
    if (status == 1) {
        itemList.push("下载视频", "播放克隆视频");
        if (automatic_clip == 1 && clip_status == 3) {
            itemList.push("下载剪辑视频", "播放剪辑视频");
        }
    } else {
        itemList.push("重试");
    }
    itemList.push("删除");

    uni.showActionSheet({
        itemList,
        success: (res) => {
            const { tapIndex } = res;
            if (status == 1) {
                if (tapIndex == 0) {
                    saveVideoToPhotosAlbum(props.item.video_url);
                    emit("download", props.item.video_url);
                }
                if (tapIndex == 1) {
                    handlePlay(props.item.video_url);
                }
                if (automatic_clip == 1 && clip_status == 3) {
                    if (tapIndex == 2) {
                        saveVideoToPhotosAlbum(props.item.clip_video_url);
                        emit("download", props.item.clip_video_url);
                    }
                    if (tapIndex == 3) {
                        handlePlay(props.item.clip_video_url);
                    }
                }
            } else if (tapIndex == 0) {
                uni.showModal({
                    title: "提示",
                    content: "确定要重试吗？",
                    success: (res) => {
                        if (res.confirm) {
                            emit("retry", id);
                        }
                    },
                });
            }

            if (tapIndex === itemList.length - 1) {
                uni.showModal({
                    title: "提示",
                    content: "确定要删除吗？",
                    success: (res) => {
                        if (res.confirm) {
                            emit("delete", id);
                        }
                    },
                });
            }
        },
    });
};
</script>
