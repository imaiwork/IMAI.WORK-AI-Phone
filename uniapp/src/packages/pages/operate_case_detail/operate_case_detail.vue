<template>
    <view class="flex flex-col bg-[#F4F7FA] min-h-screen pb-[200rpx]">
        <u-navbar :title="detail?.title" title-bold :border-bottom="false"></u-navbar>

        <scroll-view scroll-y class="flex-1" v-if="detail">
            <view
                class="mx-4 mt-[24rpx] bg-white rounded-[28rpx] px-[32rpx] py-[32rpx]"
                style="box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06)">
                <view class="flex justify-between items-start mb-[28rpx]">
                    <view>
                        <text class="text-[24rpx] text-[#6B7280] block mb-[8rpx]">累计曝光（次）</text>
                        <text class="text-[64rpx] font-extrabold text-primary leading-none">{{
                            formatNumber(detail.exposure)
                        }}</text>
                    </view>
                </view>
                <view class="flex items-center bg-[#F8FAFC] rounded-[20rpx] py-[28rpx]">
                    <view class="flex-1 flex flex-col items-center gap-[8rpx]">
                        <text class="text-[24rpx] text-[#6B7280]">获取精准线索</text>
                        <view class="flex items-baseline gap-[4rpx]">
                            <text class="text-[48rpx] font-bold text-[#1F2937]">{{ detail.leads }}</text>
                            <text class="text-[24rpx] text-[#6B7280]">条</text>
                        </view>
                    </view>
                    <view class="w-[2rpx] h-[60rpx] bg-[#E5E7EB]"></view>
                    <view class="flex-1 flex flex-col items-center gap-[8rpx]">
                        <text class="text-[24rpx] text-[#6B7280]">转化意向客户</text>
                        <view class="flex items-baseline gap-[4rpx]">
                            <text class="text-[48rpx] font-bold text-[#1F2937]">{{ detail.convertUsers || "86" }}</text>
                            <text class="text-[24rpx] text-[#6B7280]">人</text>
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
                        v-for="(user, index) in detail.targetUsers"
                        :key="index"
                        class="px-[28rpx] py-[12rpx] rounded-full text-[26rpx] font-medium"
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
                    <view v-for="(task, index) in detail.taskTypes" :key="index" class="flex items-start gap-[24rpx]">
                        <view class="flex flex-col items-center flex-shrink-0">
                            <view
                                class="w-[52rpx] h-[52rpx] rounded-full bg-[#EFF6FF] border border-[#BFDBFE] flex items-center justify-center">
                                <text class="text-[22rpx] font-bold text-primary">{{
                                    String(index + 1).padStart(2, "0")
                                }}</text>
                            </view>
                            <view
                                v-if="index < detail.taskTypes.length - 1"
                                class="w-[2rpx] flex-1 bg-[#E5E7EB] my-[6rpx]"
                                style="min-height: 32rpx"></view>
                        </view>

                        <view class="flex-1 pb-[28rpx]" :class="index === detail.taskTypes.length - 1 ? 'pb-0' : ''">
                            <text class="text-[28rpx] font-medium text-[#1F2937] leading-[52rpx]">{{ task }}</text>
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
                        <text class="text-[24rpx] text-primary font-semibold block mb-[8rpx]">适用场景</text>
                        <text class="text-[26rpx] text-[#374151] leading-[1.7]">{{
                            detail.detailPage && detail.detailPage.content
                        }}</text>
                    </view>
                    <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                        <text class="text-[24rpx] text-primary font-semibold block mb-[8rpx]">目标人群</text>
                        <text class="text-[26rpx] text-[#374151] leading-[1.7]">{{
                            (detail.targetUsers || []).join("、")
                        }}</text>
                    </view>
                    <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                        <text class="text-[24rpx] text-primary font-semibold block mb-[8rpx]">执行动作</text>
                        <text class="text-[26rpx] text-[#374151] leading-[1.7]">{{
                            (detail.taskTypes || []).join("、")
                        }}</text>
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
                            v-for="(image, index) in detail?.detailPage?.images"
                            :key="index"
                            :src="`${config.baseUrl}static/case/templates/${templateId}/${image}`"
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
                        <view v-for="(video, index) in detail?.detailPage?.videos" :key="index" class="relative">
                            <video
                                :src="`${config.baseUrl}static/case/templates/${templateId}/${video}`"
                                mode="aspectFill"
                                :controls="false"
                                :show-fullscreen-btn="false"
                                :show-center-play-btn="false"
                                :show-play-btn="false"
                                class="w-[220rpx] h-[360rpx] rounded-[20rpx] shrink-0" />
                            <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                <view
                                    class="w-[68rpx] h-[68rpx] rounded-full bg-[#ffffff33] flex items-center justify-center"
                                    @click="previewVideo(index)">
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

onMounted(() => {
    const pages = getCurrentPages();
    const page = pages[pages.length - 1];
    const options = (page as any).$page?.options || (page as any).options || {};
    const id = options.id || "";
    templateId.value = id;
    loadDetail(id);
});

function loadDetail(id: string) {
    uni.request({
        url: `${config.baseUrl}static/case/templates/case.json`,
        method: "GET",
        success: (res: any) => {
            const found = res.data.list.find((item: any) => item.id === id);
            if (found) detail.value = found;
        },
    });
}

const previewImage = (index: number) => {
    const images = detail.value?.detailPage?.images || [];
    uni.previewImage({
        urls: images.map((image: string) => `${config.baseUrl}static/case/templates/${templateId.value}/${image}`),
        current: index,
    });
};

const previewVideo = (index: number) => {
    const videos = detail.value?.detailPage?.videos || [];
    videoUrl.value = `${config.baseUrl}static/case/templates/${templateId.value}/${videos[index]}`;
    showVideoPreview.value = true;
};
</script>

<style scoped></style>
