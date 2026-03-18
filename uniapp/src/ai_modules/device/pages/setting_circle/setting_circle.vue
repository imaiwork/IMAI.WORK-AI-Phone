<template>
    <view class="min-h-screen pb-[250rpx] p-4" v-if="!loading">
        <view class="text-[30rpx] font-medium">内容生成设置</view>
        <view class="bg-white rounded-[16rpx] px-[4rpx] w-[412rpx] mt-[28rpx]">
            <view class="grid grid-cols-2 relative h-[80rpx]">
                <view
                    v-for="(item, index) in [
                        { label: 'AI自动创作', value: 1 },
                        { label: '指定素材', value: 0 },
                    ]"
                    class="flex flex-col items-center justify-center rounded-[16rpx] relative z-10 transition-colors duration-500 text-xs font-medium"
                    :class="{ 'text-white relative': formData.is_ai == item.value }"
                    @click="
                        formData.is_ai = item.value;
                        contentIndex = index;
                    ">
                    {{ item.label }}
                </view>
                <view
                    class="tab-slider"
                    :style="{
                        transform: `translateX(${contentIndex * 100}%)`,
                    }"></view>
            </view>
        </view>
        <view class="mt-[20rpx]">
            <view class="bg-white rounded-[20rpx] px-[40rpx] py-[36rpx] mb-[40rpx]" v-if="contentIndex === 0">
                <view class="flex items-center justify-between">
                    <view class="text-[30rpx] font-medium">AI创作方向</view>
                    <view
                        class="bg-[#F6F6F6] rounded-[100rpx] text-[22rpx] text-[#000000]/70 font-medium px-[16rpx] py-[8rpx]"
                        >结合IP人设</view
                    >
                </view>
                <view class="mt-[30rpx] text-[22rpx] text-[#000000]/30 font-medium">
                    AI自动从素材库里抽取内容，并配上符合<text class="text-primary">【{{ detail.industryType }}】</text
                    >人设的文案，自动防折叠。
                </view>
                <view class="mt-[36rpx]">
                    <view class="text-[#0089bf] text-[22rpx]"> 发布示例 </view>
                    <view class="mt-[14rpx] bg-[#f6f6f6] rounded-[20rpx] p-[34rpx]">
                        <view class="flex gap-x-[26rpx]">
                            <image
                                src="/static/images/common/wechat_s.png"
                                class="w-[72rpx] h-[72rpx] shrink-0"></image>
                            <view class="flex-1">
                                <view class="text-[#486799] font-medium">
                                    {{ detail.wechatName }}
                                </view>
                                <view class="mt-[10rpx]">
                                    {{ detail.copywrighting }}
                                </view>
                                <view
                                    class="w-[172rpx] h-[230rpx] relative bg-black mt-[28rpx] rounded-[20rpx] overflow-hidden">
                                    <image
                                        :src="detail.example?.cover"
                                        class="w-full h-full"
                                        mode="aspectFill"
                                        @click="previewImage(detail.example?.cover)"></image>
                                    <view
                                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
                                        v-if="detail.example?.type == 'video'">
                                        <view
                                            class="w-[52rpx] h-[52rpx]"
                                            @click="previewVideo(detail.example?.fileUrl)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <view class="text-[30rpx] font-medium">素材库内容</view>
            <view class="bg-white rounded-[20rpx] px-[36rpx] py-[34rpx] mt-[18rpx]">
                <view>
                    <view class="flex items-center justify-between">
                        <view class="text-[30rpx] font-medium"> 视频素材 ({{ videoList.length }}) </view>
                        <view class="text-xs font-medium text-[#00000050]" @click="toPage('video_material')">
                            添加<u-icon name="arrow-right" color="#00000050" size="20"></u-icon>
                        </view>
                    </view>
                    <view class="mt-[18rpx]">
                        <scroll-view scroll-x v-if="videoList.length > 0">
                            <view class="flex whitespace-nowrap gap-x-[20rpx]">
                                <view
                                    class="shrink-0 w-[172rpx] h-[230rpx] rounded-[20rpx]"
                                    v-for="(item, index) in videoList"
                                    :key="index">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                </view>
                            </view>
                        </scroll-view>
                        <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                            <empty :size="250" />
                        </view>
                    </view>
                </view>
                <view class="mt-[40rpx]">
                    <view class="flex items-center justify-between">
                        <view class="text-[30rpx] font-medium"> 图片素材 ({{ imageList.length }}) </view>
                        <view class="text-xs font-medium text-[#00000050]" @click="toPage('image_material')">
                            添加<u-icon name="arrow-right" color="#00000050" size="20"></u-icon>
                        </view>
                    </view>
                    <view class="mt-[18rpx]">
                        <scroll-view scroll-x v-if="imageList.length > 0">
                            <view
                                class="flex whitespace-nowrap gap-x-[20rpx]"
                                :class="`grid-cols-${formData.image_material.length}`">
                                <view
                                    class="shrink-0 w-[172rpx] h-[230rpx] rounded-[20rpx]"
                                    v-for="(item, index) in imageList"
                                    :key="index">
                                    <image
                                        :src="item.url"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                </view>
                            </view>
                        </scroll-view>

                        <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                            <empty :size="250" />
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="fixed bottom-0 left-0 right-0 bg-white pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-medium flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
    <video-preview v-if="showPreviewVideo" v-model="showPreviewVideo" :video-url="previewVideoUrl" />
</template>

<script setup lang="ts">
import config from "@/config";
import {
    getDeviceDetail,
    marketingAnalysisDetail,
    getAutoCirclePublishTaskConfigDetail,
    updateAutoCirclePublishTaskConfig,
} from "@/api/device";
import useMaterialStore from "@/ai_modules/device/stores/material";
import { AppTypeEnum } from "@/enums/appEnums";
import { setFormData } from "@/utils/util";

const materialStore = useMaterialStore();
const { videoList, imageList } = storeToRefs(materialStore);

// 默认图
const defaultImage = config.baseUrl + "static/images/mp/circle_example.png";

const deviceCode = ref("");
const detail = ref<any>({
    id: "",
    wechatName: "",
    industryType: "",
    example: {},
    copywrighting: "",
});
const formData = reactive<{
    id: string | number;
    is_ai: number;
    device_config_id: string | number;
    image_material: any[];
    video_material: any[];
    industry_type: string;
}>({
    id: "",
    is_ai: 1,
    device_config_id: "",
    image_material: [],
    video_material: [],
    industry_type: "",
});

const contentIndex = ref(0);

const toPage = (page: string) => {
    uni.navigateTo({
        url: `/ai_modules/device/pages/${page}/${page}`,
    });
};

const showPreviewVideo = ref(false);
const previewVideoUrl = ref("");
const previewVideo = (url: string) => {
    previewVideoUrl.value = url;
    showPreviewVideo.value = true;
};

const previewImage = (url: string) => {
    uni.previewImage({
        urls: [url],
    });
};

const handleSaveConfig = async () => {
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await updateAutoCirclePublishTaskConfig({
            ...formData,
            device_code: deviceCode.value,
            industry_type: detail.value.industryType,
            video_material: videoList.value.map((item: any) => ({
                type: item.type,
                fileUrl: item.url,
                cover: item.pic,
                duration: item.duration,
            })),
            image_material: imageList.value.map((item: any) => ({
                type: item.type,
                fileUrl: item.url,
                cover: item.pic,
                duration: item.duration,
            })),
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};
const loading = ref(true);
const getDetail = async () => {
    try {
        const { accounts } = await getDeviceDetail({
            device_code: deviceCode.value,
        });
        const analysisRes = await marketingAnalysisDetail({
            device_code: deviceCode.value,
        });
        const configRes = await getAutoCirclePublishTaskConfigDetail({
            device_code: deviceCode.value,
        });

        detail.value = {
            id: configRes.id,
            wechatName: accounts.find((item: any) => item.type === AppTypeEnum.WECHAT)?.nickname,
            industryType: analysisRes.report?.result?.Operations?.industryType,
            example: { type: "image", cover: defaultImage },
            copywrighting: configRes.copywrighting,
        };
        setFormData(configRes, formData);
        videoList.value = configRes.video_material.map((item: any) => ({
            type: item.type,
            url: item.fileUrl,
            pic: item.cover,
            duration: item.duration,
        }));
        imageList.value = configRes.image_material.map((item: any) => ({
            type: item.type,
            url: item.fileUrl,
            pic: item.cover,
            duration: item.duration,
        }));
        contentIndex.value = formData.is_ai == 1 ? 0 : 1;
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
});
</script>

<style scoped lang="scss">
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-primary absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
