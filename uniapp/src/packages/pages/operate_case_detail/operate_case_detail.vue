<template>
    <view class="flex flex-col bg-[#F4F7FA] min-h-screen pb-[200rpx]">
        <u-navbar
            :title="detail?.title"
            title-bold
            :border-bottom="false"
            :background="{ background: '#F4F7FA' }"></u-navbar>

        <scroll-view scroll-y class="flex-1" v-if="detail">
            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex justify-between items-start mb-[28rpx]">
                    <view>
                        <text class="text-xs text-[#6B7280] block mb-[8rpx]">累计曝光（次）</text>
                        <text class="text-[64rpx] font-extrabold text-primary leading-none">{{
                            formatNumber(detail.exposure)
                        }}</text>
                    </view>
                </view>
                <view class="flex items-center bg-[#F8FAFC] rounded-[20rpx] py-[28rpx]">
                    <view class="flex-1 flex flex-col items-center gap-[8rpx]">
                        <text class="text-xs text-[#6B7280]">获取精准线索</text>
                        <view class="flex items-baseline gap-[4rpx]">
                            <text class="text-[48rpx] font-bold text-[#1F2937]">{{ detail.leads }}</text>
                            <text class="text-xs text-[#6B7280]">条</text>
                        </view>
                    </view>
                    <view class="w-[2rpx] h-[60rpx] bg-[#E5E7EB]"></view>
                    <view class="flex-1 flex flex-col items-center gap-[8rpx]">
                        <text class="text-xs text-[#6B7280]">转化意向客户</text>
                        <view class="flex items-baseline gap-[4rpx]">
                            <text class="text-[48rpx] font-bold text-[#1F2937]">{{ detail.convert_users }}</text>
                            <text class="text-xs text-[#6B7280]">人</text>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex items-center gap-[12rpx] mb-[24rpx]">
                    <u-icon name="account" color="#0066FF" size="36"></u-icon>
                    <text class="text-[30rpx] font-semibold text-[#1F2937]">案例拓客人群</text>
                </view>
                <view class="flex flex-wrap gap-[16rpx]">
                    <view
                        v-for="(user, index) in detail.target_users"
                        :key="index"
                        class="px-[28rpx] py-[12rpx] rounded-full font-medium"
                        :style="tagStyle(index)">
                        {{ user }}
                    </view>
                </view>
            </view>

            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex items-center gap-[12rpx] mb-[28rpx]">
                    <u-icon name="list" color="#0066FF" size="36"></u-icon>
                    <text class="text-[30rpx] font-semibold text-[#1F2937] flex-1">包含任务节点</text>
                    <text class="text-[22rpx] text-[#9CA3AF]">标准模板配置</text>
                </view>

                <view class="flex flex-col">
                    <view v-for="(task, index) in detail.task_types" :key="index" class="flex items-start gap-[24rpx]">
                        <view class="flex flex-col items-center flex-shrink-0">
                            <view
                                class="w-[52rpx] h-[52rpx] rounded-full bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center">
                                <text class="text-[22rpx] font-bold text-primary">{{
                                    String(index + 1).padStart(2, "0")
                                }}</text>
                            </view>
                            <view
                                v-if="index < detail.task_types.length - 1"
                                class="w-[2rpx] flex-1 bg-[#E5E7EB] my-[6rpx] min-h-[32rpx]"></view>
                        </view>

                        <view
                            class="flex-1 flex items-center justify-between"
                            :class="index === detail.task_types.length - 1 ? 'pb-0' : 'pb-[28rpx]'">
                            <text class="text-[28rpx] font-medium text-[#1F2937] leading-[52rpx]">{{ task.type }}</text>

                            <view class="flex items-center gap-[8rpx] bg-[#EFF6FF] rounded-full px-[20rpx] py-[8rpx]">
                                <u-icon name="clock" color="#2563EB" size="24"></u-icon>
                                <text class="text-[22rpx] text-[#2563EB] font-medium">{{ task.time }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex items-center gap-[12rpx] mb-[24rpx]">
                    <u-icon name="file-text" color="#0066FF" size="36"></u-icon>
                    <text class="text-[30rpx] font-semibold text-[#1F2937]">方案说明</text>
                </view>
                <view class="flex flex-col gap-[16rpx]">
                    <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                        <text class="text-xs text-primary font-semibold block mb-[8rpx]">适用场景</text>
                        <text class="text-[#374151] leading-[1.7]">{{ detail.detail_content }}</text>
                    </view>
                    <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                        <text class="text-xs text-primary font-semibold block mb-[8rpx]">目标人群</text>
                        <text class="text-[#374151] leading-[1.7]">{{ detail.detail_users }}</text>
                    </view>
                    <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                        <text class="text-xs text-primary font-semibold block mb-[8rpx]">执行动作</text>
                        <text class="text-[#374151] leading-[1.7]">{{ detail.detail_task_types }}</text>
                    </view>
                </view>
            </view>

            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex items-center gap-[12rpx] mb-[24rpx]">
                    <u-icon name="photo" color="#0066FF" size="36"></u-icon>
                    <text class="text-[30rpx] font-semibold text-[#1F2937]">真实转化截图</text>
                </view>
                <scroll-view scroll-x class="w-full">
                    <view class="flex whitespace-nowrap gap-[20rpx]">
                        <image
                            v-for="(image, index) in detail.detail_images"
                            :key="index"
                            :src="image"
                            mode="aspectFill"
                            class="w-[220rpx] h-[360rpx] rounded-[20rpx] shrink-0"
                            @click="previewImage(index)" />
                    </view>
                </scroll-view>
            </view>

            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex items-center gap-[12rpx] mb-[24rpx]">
                    <u-icon name="play-circle" color="#0066FF" size="36"></u-icon>
                    <text class="text-[30rpx] font-semibold text-[#1F2937]">视频演示</text>
                </view>
                <scroll-view scroll-x class="w-full">
                    <view class="flex whitespace-nowrap gap-[20rpx]">
                        <view v-for="(video, index) in detail.detail_videos" :key="index" class="relative">
                            <video
                                :src="video"
                                mode="aspectFill"
                                :controls="false"
                                :show-fullscreen-btn="false"
                                :show-center-play-btn="false"
                                :show-play-btn="false"
                                class="w-[220rpx] h-[360rpx] rounded-[20rpx] shrink-0" />
                            <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                <view
                                    class="w-[68rpx] h-[68rpx] rounded-full bg-[#ffffff33] flex items-center justify-center"
                                    @click="previewVideo(video)">
                                    <u-icon name="play-circle" color="#ffffff" size="60"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </scroll-view>
    </view>
    <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getOperateCaseDetail } from "@/api/app";
import config from "@/config";

const templateId = ref<string>("");
const detail = ref<any>(null);
const showVideoPreview = ref(false);
const videoUrl = ref<string>("");

const tagStyles = [
    { background: "#EFF6FF", color: "#1D4ED8" },
    { background: "#FFF7ED", color: "#C2410C" },
    { background: "#F5F3FF", color: "#6D28D9" },
    { background: "#ECFDF5", color: "#065F46" },
];

function tagStyle(index: number) {
    const s = tagStyles[index % tagStyles.length];
    return `background:${s.background}; color:${s.color}`;
}

function formatNumber(val: string) {
    if (!val) return "0";
    if (val.includes("w")) {
        const num = parseFloat(val.replace("w", "")) * 10000;
        return num.toLocaleString();
    }
    return val;
}

const loadDetail = async (id: string) => {
    const data = await getOperateCaseDetail({ id });
    detail.value = data;
};

const previewImage = (index: number) => {
    uni.previewImage({
        urls: detail.value?.detail_images || [],
        current: index,
    });
};

const previewVideo = (video: string) => {
    videoUrl.value = video;
    showVideoPreview.value = true;
};

onLoad((options: any) => {
    const id = options.id || "";
    templateId.value = id;
    loadDetail(id);
});
</script>

<style scoped></style>
