<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-bold text-[30rpx]">图片素材（共{{ imageList.length }}张）</view>
            <view class="mt-1 text-xs text-[#0000004d]"> 最多可传{{ limit }}张图片 </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <scroll-view scroll-y class="h-full">
                <view class="px-4">
                    <view class="grid grid-cols-3 gap-2">
                        <view
                            v-if="imageList.length < limit"
                            class="bg-white rounded-[20rpx] h-[220rpx] flex flex-col items-center justify-center"
                            @click="openUploadDialog">
                            <view
                                class="w-[32rpx] h-[32rpx] bg-[#00000066] flex items-center justify-center rounded-full">
                                <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                            </view>
                            <text class="mt-3 text-[#00000066]">添加图片</text>
                        </view>
                        <view
                            v-for="(image, index) in imageList"
                            :key="index"
                            class="relative w-full h-[220rpx] bg-white overflow-hidden rounded-[20rpx]">
                            <image :src="image" mode="aspectFill" class="w-full h-full rounded-[20rpx]"></image>
                            <view
                                class="absolute top-1 right-1 flex items-center justify-center bg-black rounded-full w-5 h-5"
                                @click="handleDeleteImage(index)">
                                <u-icon name="close" size="24" color="#ffffff"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-2 px-4">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '12rpx', fontWeight: 'bold' }"
                @click="handleConfirm"
                >确定保存</u-button
            >
        </view>
    </view>
    <confirm-dialog v-model="showUploadTip" :content="getTipsContent" confirm-text="去上传" @confirm="handleUpload" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import { uploadFile } from "@/api/app";
import { chooseFile } from "@/components/file-upload/choose-file";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";

// 每组最多图片数
const limit = 9;
// 图片上传大小
const imageSize = 50;
// 上传格式
const uploadFormat = ["jpg", "png"];
// 图片分辨率
const imageResolution = [5000, 5000];
// 图片列表
const imageList = ref<any[]>([]);
// 上传素材列表
const uploadMaterialList = ref<any[]>([]);
// 是否是初次打开
const isInitialOpen = ref(true);
// 是否显示上传提示
const showUploadTip = ref(false);
// 是否显示上传进度
const showUploadProgress = ref(false);

// 获取上传提示内容
const getTipsContent = computed(() => {
    return `
        <div>· 图片素材支持：${uploadFormat.join("、")}格式，${imageSize}M以内，分辨率不超过${imageResolution[0]}*${
        imageResolution[1]
    }</div>
    <div class="mt-2">· 最多可传${limit}张图片</div>
    <div class="mt-2">· 不符合条件的图片会被自动删除</div>
    `;
});

const openUploadDialog = () => {
    if (!isInitialOpen.value) {
        handleUpload();
        return;
    }
    isInitialOpen.value = false;
    showUploadTip.value = true;
};

const handleUpload = async () => {
    uploadMaterialList.value = [];
    try {
        const { tempFiles } = await chooseFile({
            type: "image",
            count: limit,
            extension: uploadFormat,
        });
        // 先过滤图片
        const fileList = [];
        for (const file of tempFiles) {
            /**
             * 判断条件
             * 1. 图片大小不能超过50M
             * 2. 图片分辨率不能超过2000*2000
             */
            try {
                // 1. 获取图片宽高
                const { width, height } = await uni.getImageInfo({
                    src: file.tempFilePath,
                });
                if (width > imageResolution[0] || height > imageResolution[1]) {
                    continue;
                }
                if (file.size > imageSize * 1024 * 1024) {
                    continue;
                }
                fileList.push(file);
            } catch (error) {
                continue;
            }
        }
        if (fileList.length === 0) {
            uni.$u.toast(`所选图片素材均不符合条件，重新上传`);
            return;
        }
        uploadMaterialList.value = fileList.map((file: any) => ({ ...file, progress: 0 }));
        showUploadProgress.value = true;
        for (const item of uploadMaterialList.value) {
            const fileRes: any = await uploadFile("image", { filePath: item.tempFilePath }, (progress) =>
                progressCallback(progress, item)
            );
            imageList.value.push(fileRes.uri);
        }
        if (uploadMaterialList.value.every((item) => item.progress === 100)) {
            showUploadProgress.value = false;
        }
    } catch (error) {
        uni.$u.toast(error);
        uploadMaterialList.value = [];
        showUploadProgress.value = false;
    }
};

/**
 * 上传进度回调函数
 * @param progress - 进度值 (0-100)
 * @param options - 上传选项，包含 filePath
 */
const progressCallback = (progress: number, options: { tempFilePath: string }) => {
    const targetIndex = uploadMaterialList.value.findIndex(
        (material) => material.tempFilePath === options.tempFilePath
    );
    if (targetIndex !== -1) {
        uploadMaterialList.value[targetIndex] = {
            ...uploadMaterialList.value[targetIndex],
            progress: progress,
        };
    }
};

const handleDeleteImage = (index: number) => {
    imageList.value.splice(index, 1);
};

const handleConfirm = () => {
    if (imageList.value.length === 0) {
        uni.$u.toast(`至少需要上传1张图`);
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_IMG,
        data: imageList.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.imgs) {
        imageList.value = JSON.parse(options.imgs);
    }
});
</script>

<style scoped></style>
