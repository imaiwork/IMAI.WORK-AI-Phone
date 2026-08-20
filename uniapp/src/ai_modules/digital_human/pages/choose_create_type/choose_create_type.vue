<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="选择视频类型"
            title-bold />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pt-[16rpx] pb-[40rpx] flex flex-col gap-[16rpx]">
                    <view
                        v-for="(item, index) in typeList"
                        :key="index"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)] flex items-stretch active:scale-[0.99] transition-all duration-150"
                        @click="handleClick(item)">
                        <view
                            class="w-[180rpx] flex-shrink-0 relative bg-[#F0F2F5] m-[16rpx] rounded-[16rpx] overflow-hidden">
                            <view
                                class="w-full h-full"
                                style="aspect-ratio: 9/12"
                                @click.stop="handlePlay(videoCaseLists[index]?.video_case_url)">
                                <image
                                    :src="videoCaseLists[index]?.image || CommonBg"
                                    class="w-full h-full"
                                    mode="aspectFill" />

                                <view v-if="isShowVideoCase" class="absolute inset-0 flex items-center justify-center">
                                    <image src="/static/images/icons/play.svg" class="w-[56rpx] h-[56rpx]" />
                                </view>

                                <view
                                    class="absolute right-0 top-0 px-[12rpx] h-[32rpx] flex items-center justify-center rounded-tr-[16rpx] rounded-bl-[14rpx]"
                                    style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px)">
                                    <text class="text-white text-[18rpx] font-medium">示例</text>
                                </view>
                            </view>
                        </view>

                        <view class="flex-1 min-w-0 py-[24rpx] pr-[24rpx] flex flex-col justify-between">
                            <view>
                                <view class="flex items-center gap-[10rpx] flex-wrap mb-[10rpx]">
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">{{ item.title }}</text>
                                    <view v-if="item.is_dh" class="dh-badge flex-shrink-0"> 含数字人 </view>
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed line-clamp-3">{{
                                    item.desc
                                }}</text>
                            </view>

                            <view class="flex items-center justify-end mt-[16rpx]">
                                <view
                                    class="flex items-center gap-[6rpx] h-[52rpx] px-[20rpx] rounded-[14rpx]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                    <text class="text-[22rpx] font-bold text-white"> 立即使用 </text>
                                    <u-icon name="arrow-right" color="#ffffff" size="16" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>

    <video-preview v-model="showVideoPreview" title="视频预览" :video-url="videoUrl" />
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import CommonBg from "@/ai_modules/digital_human/static/images/common/bg.jpg";
import VideoPreview from "@/components/video-preview/video-preview.vue";

const appStore = useAppStore();

const isShowVideoCase = computed(() => appStore.getDigitalHumanConfig?.video_case_open == "1");
const videoCaseLists = computed(() => appStore.getDigitalHumanConfig?.video_case || []);

const typeList = [
    {
        key: "dh",
        title: "数字人纯口播视频",
        desc: "可输出带有任何标题字幕包装的数字人口播视频",
        is_dh: true,
        path: "/ai_modules/digital_human/pages/szr_create/szr_create",
    },
    {
        key: "montage",
        title: "数字人口播混剪",
        desc: "数字人+文案+素材智能混剪，自动加字幕/标题/特效，生成爆款视频",
        is_dh: true,
        path: "/ai_modules/digital_human/pages/montage_create/montage_create",
    },
    {
        key: "real_person",
        title: "真人口播视频混剪",
        desc: "上传真人口播视频+素材，AI自动剪辑气口、加包装，输出网感口播视频",
        path: "/ai_modules/digital_human/pages/montage_person_create/montage_person_create",
    },
    {
        key: "montage_material",
        title: "素材混剪神器",
        desc: "文案+AI配音+多场景素材混剪，自动生成商品种草/产品解说/产品介绍视频",
        path: "/ai_modules/digital_human/pages/montage_material_create/montage_material_create",
    },
    {
        key: "news",
        title: "新闻体视频",
        desc: "流量收割机！上传素材+标题+音乐=秒出新闻体混剪视频",
        path: "/ai_modules/digital_human/pages/montage_news_create/montage_news_create",
    },
    {
        title: "一句话生成视频",
        desc: "聚合多款顶尖AI创作大模型，一句话即可生成视频，轻松呈现影视级创作效果。",
        path: "/ai_modules/digital_human/pages/sora_create/sora_create",
    },
    {
        key: "storyboard",
        title: "分镜混剪视频",
        desc: "支持创建多镜头分组素材，字幕分组等，智能匹配自动出片",
        path: "/ai_modules/digital_human/pages/montage_storyboard_create/montage_storyboard_create",
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
    if (!url || !isShowVideoCase.value) return;
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
