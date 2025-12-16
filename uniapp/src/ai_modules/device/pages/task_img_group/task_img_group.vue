<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-bold text-[30rpx]">图片素材（共{{ imageList.length }}张）</view>
            <view class="mt-1 text-xs text-[#0000004d]"> 最多可传{{ limit }}张图片 </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-3 p-4">
                    <view
                        v-for="(image, index) in imageList"
                        :key="index"
                        class="relative w-full h-[220rpx] bg-white rounded-[20rpx]"
                        @click="previewImage(index)">
                        <image :src="image" mode="aspectFill" class="w-full h-full rounded-[20rpx]"></image>
                        <view
                            class="absolute -top-2 -right-2 flex items-center justify-center bg-black rounded-full w-5 h-5"
                            @click.stop="handleDeleteImage(index)">
                            <u-icon name="close" size="20" color="#ffffff"></u-icon>
                        </view>
                    </view>
                    <view
                        v-if="imageList.length < limit"
                        class="bg-white rounded-[20rpx] h-[220rpx] flex flex-col items-center justify-center"
                        @click="chooseUploadType">
                        <view class="w-[32rpx] h-[32rpx] bg-[#00000066] flex items-center justify-center rounded-full">
                            <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                        </view>
                        <text class="mt-3 text-[#00000066]">添加图片</text>
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
    <confirm-dialog
        v-model="showUploadTip"
        :content="getTipsContent"
        confirm-text="去上传"
        @confirm="uploadAndProcessFiles(uploadType)"
        @close="
            showUploadTip = false;
            isFirstOpen = false;
        " />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material
        v-model="showImgMaterial"
        type="image"
        :limit="limit - imageList.length"
        @select="handleSelectImgMaterial" />
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useUpload from "@/hooks/useUpload";

const { emit } = useEventBusManager();

// 每组最多图片数
const limit = 9;
// 图片上传大小
const imageSize = 50;
// 上传格式
const uploadFormat = ["jpg", "png", "jpeg"];
// 图片分辨率
const imageResolution = [5000, 5000];
// 图片列表
const imageList = ref<any[]>([]);
// 是否显示上传提示
const showUploadTip = ref(false);
const isFirstOpen = ref(true);
const uploadType = ref<"all" | "image">("image");
// 是否显示选择素材
const showImgMaterial = ref(false);
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

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: limit,
    imageAccept: uploadFormat,
    imageResolution: imageResolution,
    imageSize: imageSize,
    onSuccess: (res: any[]) => {
        // 这里要计算已有图片和上传图片的差值，然后上传差值数量的图片
        const diff = limit - imageList.value.length;
        const uploadImages = res.slice(0, diff);
        imageList.value = imageList.value.concat(uploadImages.map((item: any) => item.url));
    },
});

const chooseUploadType = () => {
    showUploadTip.value = false;
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex == 0 || res.tapIndex == 2) {
                if (isFirstOpen.value) {
                    isFirstOpen.value = false;
                    showUploadTip.value = true;
                    return;
                }
                uploadType.value = res.tapIndex == 0 ? "all" : "image";
                if (!isFirstOpen.value) {
                    uploadAndProcessFiles(uploadType.value);
                }
            }
            if (res.tapIndex == 1) {
                showImgMaterial.value = true;
            }
        },
    });
};

const handleSelectImgMaterial = async (res: any[]) => {
    const imageCheckPromises = res.map(
        (item: any) =>
            new Promise((resolve) => {
                wx.getImageInfo({
                    src: item.content,
                    success: (info: any) => {
                        const { type, width, height } = info;
                        // 判断是否符合条件
                        const isAccord =
                            width <= imageResolution[0] && height <= imageResolution[1] && uploadFormat.includes(type);
                        if (isAccord) {
                            resolve(item.content);
                        } else {
                            uni.showToast({
                                title: `选择的图片包含不符合条件的图片，已自动过滤`,
                                icon: "none",
                            });
                            resolve(null);
                        }
                    },
                    fail: () => {
                        resolve(null);
                    },
                });
            })
    );
    const uploadImages = (await Promise.all(imageCheckPromises)).filter((url) => url);
    imageList.value = imageList.value.concat(uploadImages);
};

const previewImage = (index: number) => {
    uni.previewImage({
        urls: imageList.value,
        current: index,
    });
};

const handleDeleteImage = (index: number) => {
    imageList.value.splice(index, 1);
};

const handleConfirm = () => {
    if (imageList.value.length === 0) {
        uni.$u.toast(`至少需要上传1张图`);
        return;
    }
    emit("confirm", {
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
