<template>
    <view class="h-screen flex flex-col">
        <view class="font-bold text-[30rpx] mx-4 mt-4">剪辑素材({{ dataList.length }})</view>
        <view class="mt-1 text-xs text-[#0000004d] mx-4">
            至少需要{{ minVideoCount }}个视频+{{ minImageCount }}张图片</view
        >
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-[26rpx] p-4 pb-[100rpx]">
                    <view
                        class="h-[250rpx] bg-white rounded-[20rpx] flex flex-col items-center justify-center"
                        @click="chooseUploadType">
                        <image src="@/ai_modules/device/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-[28rpx] text-[#000000b3] mt-1">添加</text>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="h-[250rpx] relative rounded-[12rpx]"
                        @click="handlePreview(item)">
                        <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
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
                        <view class="absolute bottom-4 w-full z-[89] flex justify-center">
                            <view class="dh-version-name" @click.stop="handleReplace(index)"> 替换 </view>
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
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material v-model="showChooseMaterial" type="video" @select="handleChooseMaterial" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import useMaterialStore from "@/ai_modules/device/stores/material";
import useMontageMaterial from "@/hooks/useMontageMaterial";

const materialStore = useMaterialStore();
const { videoList } = storeToRefs(materialStore);
const dataList = ref<any[]>(JSON.parse(JSON.stringify(videoList.value)));
const minImageCount = 3;
const minVideoCount = 1;
const replaceMaterialIndex = ref(-1);
const showChooseMaterial = ref(false);
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useMontageMaterial({
    count: 9,
    onSuccess: (res: any[]) => {
        dataList.value.push(...res);
    },
});

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ["选择图片素材", "选择视频素材"],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("image");
            else if (res.tapIndex === 1) uploadAndProcessFiles("video");
        },
    });
};

const handleChooseMaterial = (list: any[]) => {
    dataList.value.push(...list.map((item) => ({ pic: item.pic, url: item.content, type: "video" })));
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
        uni.previewImage({
            urls: [item.pic],
        });
    } else {
        playData.value = { url: item.url, pic: item.pic };
        showVideoPreview.value = true;
    }
};

const handleConfirm = () => {
    const imageCount = dataList.value.filter((item) => item.type === "image").length;
    const videoCount = dataList.value.filter((item) => item.type === "video").length;
    if (imageCount < minImageCount || videoCount < minVideoCount) {
        uni.$u.toast(`请至少选择${minImageCount}张图片和${minVideoCount}个视频素材`);
        return;
    }
    materialStore.setList("videoList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
