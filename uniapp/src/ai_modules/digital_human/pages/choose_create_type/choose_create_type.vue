<template>
    <view class="h-screen dh-bg flex flex-col">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="选择视频类型"
            title-bold>
        </u-navbar>
        <view class="grow min-h-0 mt-4">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[26rpx] pb-[100rpx]">
                    <view class="flex flex-col gap-y-4">
                        <view
                            v-for="(item, index) in typeList"
                            :key="index"
                            class="px-[32rpx] py-[30rpx] bg-white rounded-[16rpx] flex gap-x-5"
                            @click="handleClick(item)">
                            <view class="w-[180rpx] h-[240rpx] flex-shrink-0 relative bg-[#F3F4FB] rounded-[12rpx]">
                                <view class="w-full h-full" @click.stop="handlePlay(item.videoUrl)">
                                    <image
                                        :src="item.imageUrl || CommonBg"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        v-if="!item.disabled"
                                        class="absolute top-[50%] left-[50%] z-[888]"
                                        style="transform: translate(-50%, -50%)">
                                        <view
                                            class="rounded-full bg-[#ffffff33] w-[68rpx] h-[68rpx]"
                                            style="backdrop-filter: blur(5px)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                    <view
                                        class="absolute right-0 top-0 w-[68rpx] h-[30rpx] flex items-center justify-center rounded-tr-[12rpx] rounded-bl-[12rpx] bg-[#00000080]">
                                        <text class="text-white text-[20rpx]"> 示例 </text>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-0 left-0 w-full h-full z-[888] bg-[#00000080] rounded-[12rpx]"
                                    v-if="item.disabled"></view>
                            </view>
                            <view class="flex-1 mt-[28rpx]">
                                <view class="flex items-center gap-x-2">
                                    <view class="text-[30rpx] font-bold">{{ item.title }}</view>
                                    <view v-if="item.is_dh || item.disabled" class="dh-badge" style="">{{
                                        item.disabled ? "等待上线" : "含数字人"
                                    }}</view>
                                </view>
                                <view class="mt-[12rpx] text-xs text-[#00000066]">
                                    {{ item.desc }}
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
    <video-preview v-model:show="showVideoPreview" title="视频预览" :video-url="videoUrl" />
</template>

<script setup lang="ts">
import config from "@/config";
import CommonBg from "@/ai_modules/digital_human/static/images/common/bg.jpg";
// 类型列表
const typeList = [
    {
        key: "dh",
        title: "数字人纯口播视频",
        desc: "输出无任何标题字幕包装的数字人口播视频，适合专业剪辑大神二次创作",
        is_dh: true,
        path: "/ai_modules/digital_human/pages/video_create/video_create",
        videoUrl: config.baseUrl + "static/videos/dh/dh1.mp4",
        imageUrl: config.baseUrl + "static/videos/dh/dh1.jpg",
    },
    {
        key: "montage",
        title: "数字人口播混剪",
        desc: "数字人+文案+素材智能混剪，自动加字幕/标题/特效，生成爆款视频",
        is_dh: true,
        path: "/ai_modules/digital_human/pages/montage_create/montage_create",
        videoUrl: config.baseUrl + "static/videos/dh/dh2.mp4",
        imageUrl: config.baseUrl + "static/videos/dh/dh2.jpg",
    },
    {
        key: "dh",
        title: "真人口播视频智剪",
        desc: "上传真人口播视频+素材，AI自动剪辑气口、加包装，输出网感口播视频",
        is_dh: true,
        disabled: true,
    },
    {
        title: "素材混剪神器",
        desc: "文案+AI配音+多场景素材混剪，自动生成商品种草/产品解说/产品介绍视频",
        disabled: true,
    },
    {
        title: "新闻体视频",
        desc: "流量收割机！上传素材+标题+音乐=秒出新闻体混剪视频",
        disabled: true,
    },
    {
        title: "Sora2",
        desc: "新一代AI视频创作模型，一句话生成视频，音频同步生成等",
        disabled: true,
    },
];

const showVideoPreview = ref(false);
const videoUrl = ref("");

const handleClick = (item: any) => {
    if (item.disabled) {
        uni.$u.toast("开发中...");
        return;
    }
    uni.navigateTo({
        url: item.path,
    });
};
const handlePlay = (url?: string) => {
    if (!url) return;
    videoUrl.value = url;
    showVideoPreview.value = true;
};
</script>

<style scoped lang="scss">
.dh-badge {
    background: linear-gradient(
        90deg,
        rgba(8, 131, 254, 1) 0%,
        rgba(24, 237, 245, 1) 50.35%,
        rgba(89, 255, 167, 1) 100%
    );

    @apply w-[100rpx] h-[38rpx] flex items-center justify-center rounded-tr-[12rpx] rounded-bl-[12rpx] text-[20rpx];
}
</style>
