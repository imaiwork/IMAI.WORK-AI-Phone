<template>
    <view class="h-screen flex flex-col">
        <view class="font-medium text-[30rpx] mx-4 mt-4">剪辑素材({{ dataList.length }})</view>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-[26rpx] p-4 pb-[100rpx]">
                    <view
                        class="aspect-[3/4] bg-white rounded-[20rpx] flex flex-col items-center justify-center"
                        @click="chooseUploadType">
                        <image src="@/ai_modules/device/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-[28rpx] text-[#000000b3] mt-1">添加</text>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="aspect-[3/4] relative rounded-[12rpx] leading-[0]"
                        @click="handlePreview(item)">
                        <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                        <view
                            v-if="item.type === 'video'"
                            class="absolute top-0 left-0 h-full w-full z-[89] flex items-center justify-center">
                            <image src="/static/images/icons/play.svg" class="w-[48rpx] h-[48rpx]"></image>
                        </view>
                        <view
                            class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] rounded-b-[12rpx] flex items-center justify-center z-[88]">
                            <image
                                v-if="item.type === 'image'"
                                src="@/ai_modules/digital_human/static/icons/pic.svg"
                                class="w-[24rpx] h-[24rpx]"></image>
                            <image
                                v-else
                                src="@/ai_modules/digital_human/static/icons/video.svg"
                                class="w-[24rpx] h-[24rpx]"></image>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white px-6 pt-4 pb-5">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '16rpx', fontWeight: 'bold' }"
                @click="handleConfirm"
                >确定保存</u-button
            >
        </view>
    </view>
    <choose-history v-model="showHistory" :type="showHistoryType" :limit="9999" @select="handleSelectHistory" />
    <choose-material v-model="showChooseMaterial" :type="showHistoryType" @select="handleChooseMaterial" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import { batchGetVideoInfoByUrl } from "@/api/app";
import useMaterialStore from "@/ai_modules/device/stores/material";
import useUpload from "@/hooks/useUpload";
import ChooseHistory from "@/ai_modules/device/components/choose-history/choose-history.vue";

const materialStore = useMaterialStore();
const { videoList } = storeToRefs(materialStore);
const dataList = ref<any[]>(JSON.parse(JSON.stringify(videoList.value)));
const replaceMaterialIndex = ref(-1);
const showChooseMaterial = ref(false);
const showHistory = ref(false);
const showHistoryType = ref<"video" | "image">("video");
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    count: 9,
    videoDuration: [1, 59],
    onSuccess: (res: any[]) => {
        res.map((item) => (item.duration = item.type == "image" ? 2 : item.duration));
        if (replaceMaterialIndex.value !== -1) {
            dataList.value[replaceMaterialIndex.value] = res[0];
        } else {
            dataList.value.push(...res);
        }
        replaceMaterialIndex.value = -1;
    },
});

/**
 * 尝试通过接口补全列表中视频的时长信息
 * 若接口报错，静默处理，不影响后续逻辑
 * @param list 需要补全时长的数据列表（type === 'video' 的项）
 */
const fillVideoDuration = async (list: any[]) => {
    const videoItems = list.filter((item) => item.type === "video");
    if (videoItems.length === 0) return;
    const videoUrls = videoItems.filter((item) => !item.duration || item.duration <= 0).map((item) => item.url);

    if (videoUrls.length > 0) {
        uni.showLoading({ title: "信息获取中...", mask: true });

        try {
            const { results } = await batchGetVideoInfoByUrl({ video_urls: videoUrls });
            results
                .filter((result: any) => result.data.duration <= 59)
                .forEach((result: any) => {
                    videoItems[result.index].duration = result.data.duration;
                });
        } finally {
            uni.hideLoading();
        }
    }
};

/**
 * 将数据插入/替换到 dataList
 */
const applyToDataList = (list: any[]) => {
    const index = replaceMaterialIndex.value;
    if (index !== -1) {
        dataList.value.splice(index, 1, ...list);
    } else {
        dataList.value.push(...list);
    }
    replaceMaterialIndex.value = -1;
};

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ["从相册选择图片", "从相册选择视频", "从图片素材库选择", "从视频素材库选择", "从创作库选择"],
        success: (res) => {
            const { tapIndex } = res;
            if ([0, 1].includes(tapIndex)) uploadAndProcessFiles(tapIndex === 0 ? "image" : "video");
            else if ([2, 3].includes(tapIndex)) {
                showHistoryType.value = tapIndex === 3 ? "video" : "image";
                showChooseMaterial.value = true;
            } else if (tapIndex === 4) {
                showHistory.value = true;
            }
        },
    });
};

const handleChooseMaterial = async (list: any[]) => {
    // 是否有超过60秒的视频
    const hasOver60Video = list.some((item) => item.duration > 59);
    if (hasOver60Video) {
        uni.$u.toast("素材中存在超过60秒的视频，将自动过滤掉");
    }
    const newList = list
        .filter((item) => item.duration <= 59)
        .map((item) => ({
            pic: item.pic,
            url: item.url,
            duration: item.m_type === 1 ? 2 : item.duration,
            type: item.m_type === 1 ? "image" : "video",
        }));
    await fillVideoDuration(newList);
    applyToDataList(newList);
};

const handleSelectHistory = async (list: any[]) => {
    const isVideo = showHistoryType.value === "video";
    // 是否有超过60秒的视频
    const hasOver60Video = list.some((item) => item.duration > 59);
    if (hasOver60Video) {
        uni.$u.toast("素材中存在超过60秒的视频，将自动过滤掉");
    }
    const newList = list
        .filter((item) => item.duration <= 59)
        .map((item) => ({
            pic: item.pic || item.image,
            url: isVideo ? item.clip_result_url || item.video_result_url : item.content || item.image,
            type: showHistoryType.value,
            duration: parseFloat(item.duration),
        }));
    await fillVideoDuration(newList);
    applyToDataList(newList);
};

const handleReplace = (index: number) => {
    replaceMaterialIndex.value = index;
    chooseUploadType();
};

const handleDelete = (index: number) => {
    dataList.value.splice(index, 1);
};

const handlePreview = (item: any) => {
    if (item.type === "image") {
        uni.previewImage({ urls: [item.pic] });
    } else {
        playData.value = { url: item.url, pic: item.pic };
        showVideoPreview.value = true;
    }
};

const handleConfirm = () => {
    if (dataList.value.length <= 0) {
        uni.$u.toast("请至少选择一个素材");
        return;
    }
    materialStore.setList("videoList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
