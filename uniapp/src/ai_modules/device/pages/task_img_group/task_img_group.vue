<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-medium text-[30rpx]">图片素材（共{{ imageList.length }}张）</view>
            <view class="mt-1 text-xs text-[#0000004d]"> 最多可传{{ limit }}张图片 </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-3 p-4">
                    <view
                        v-for="(image, index) in imageList"
                        :key="index"
                        class="relative aspect-[3/4] bg-white rounded-[20rpx]"
                        @click="previewImage(index)">
                        <image :src="image" mode="aspectFill" class="w-full h-full rounded-[20rpx]"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDeleteImage(index)">
                            <u-icon name="close" size="20" color="#ffffff"></u-icon>
                        </view>
                        <div class="absolute bottom-2 w-full z-[33] flex justify-center">
                            <view
                                class="px-3 py-1 text-white text-xs rounded-full border border-solid border-[#ffffff]/30"
                                @click.stop="handleReplaceImage(index)">
                                替换
                            </view>
                        </div>
                    </view>
                    <view
                        v-if="imageList.length < limit"
                        class="bg-white aspect-[3/4] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[10rpx]"
                        @click="showUploadCategoryPanel = true">
                        <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                            <u-icon name="plus" color="#0065fb" size="28" />
                        </view>
                        <text class="text-xs text-primary font-semibold">添加图片</text>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleConfirm">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">确定保存</text>
            </view>
        </view>
    </view>
    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="[UploadAlbumTypeEnum.Image, UploadCategoryEnum.Library, UploadCategoryEnum.Creation]"
        @select="handleSelectCategory" />
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
        :limit="replaceImageIndex == -1 ? limit - imageList.length : 1"
        @select="handleSelectImgMaterial" />
    <choose-history
        v-model="showHistory"
        type="image"
        :limit="replaceImageIndex == -1 ? limit - imageList.length : 1"
        @select="handleSelectHistory" />
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useUpload from "@/hooks/useUpload";
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";

const { emit } = useEventBusManager();

const showUploadCategoryPanel = ref(false);
// 每组最多图片数
const limit = 9;
// 图片上传大小
const imageSize = 50;
// 上传格式
const uploadFormat = ["jpg", "png", "jpeg", "webp"];
// 图片列表
const imageList = ref<any[]>([]);
// 是否显示上传提示
const showUploadTip = ref(false);
const isFirstOpen = ref(true);
const uploadType = ref<"file" | "image">("image");
// 是否显示选择素材
const showImgMaterial = ref(false);
// 替换图片索引
const replaceImageIndex = ref(-1);
// 是否显示创作历史
const showHistory = ref(false);
// 获取上传提示内容
const getTipsContent = computed(() => {
    return `
        <div>· 图片素材支持：${uploadFormat.join("、")}格式，${imageSize}M以内</div>
    <div class="mt-2">· 最多可传${limit}张图片</div>
    <div class="mt-2">· 不符合条件的图片会被自动删除</div>
    `;
});

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    count: limit,
    imageAccept: uploadFormat,
    imageSize: imageSize,
    fileAccept: uploadFormat,
    fileSize: imageSize,
    onSuccess: (res: any[]) => {
        // 这里要判断是否超过最大限制，如果超过，则过滤掉多余的图片
        if (imageList.value.length + res.length > limit) {
            res.splice(0, res.length - (limit - imageList.value.length));
        }

        if (replaceImageIndex.value !== -1) {
            imageList.value[replaceImageIndex.value] = res[0].url;
        } else {
            imageList.value = imageList.value.concat(res.map((item: any) => item.url));
        }
        replaceImageIndex.value = -1;
    },
});

const handleSelectCategory = (category: "image" | "video" | "file" | "library" | "creation") => {
    if (category === "image" || category === "video" || category === "file") {
        uploadAndProcessFiles(category);
    } else if (category === "library") {
        showImgMaterial.value = true;
    } else if (category === "creation") {
        showHistory.value = true;
    }
};

const handleSelectImgMaterial = async (res: any[]) => {
    const uploadImages = res.map((item: any) => item.url);
    if (replaceImageIndex.value !== -1) {
        imageList.value[replaceImageIndex.value] = uploadImages[0];
    } else {
        imageList.value = imageList.value.concat(uploadImages);
    }
    replaceImageIndex.value = -1;
};

const handleSelectHistory = (res: any[]) => {
    if (replaceImageIndex.value !== -1) {
        imageList.value[replaceImageIndex.value] = res[0].url;
    } else {
        imageList.value = imageList.value.concat(res.map((item: any) => item.url));
    }
    replaceImageIndex.value = -1;
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

const handleReplaceImage = (index: number) => {
    replaceImageIndex.value = index;
    showUploadCategoryPanel.value = true;
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
