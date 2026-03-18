<template>
    <view class="h-screen flex flex-col">
        <view class="font-medium text-[30rpx] mx-4 mt-4">图片素材({{ dataList.length }})</view>
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
                        class="aspect-[3/4] relative"
                        @click="previewImage(index)">
                        <image :src="item.url" class="w-full h-full rounded-[20rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
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
    <choose-history v-model="showHistory" type="image" :limit="9999" @select="handleSelectHistory" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material v-model="showChooseMaterial" type="image" @select="handleChooseMaterial" />
</template>

<script setup lang="ts">
import useMaterialStore from "@/ai_modules/device/stores/material";
import useUpload from "@/hooks/useUpload";
import ChooseHistory from "@/ai_modules/device/components/choose-history/choose-history.vue";

const materialStore = useMaterialStore();
const { imageList } = storeToRefs(materialStore);

const dataList = ref<any[]>(JSON.parse(JSON.stringify(imageList.value)));
const showChooseMaterial = ref(false);
const showHistory = ref(false);

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    imageAccept: ["jpg", "jpeg", "png", "webp"],
    fileAccept: ["jpg", "jpeg", "png", "webp"],
    onSuccess: (res: any[]) => {
        dataList.value.push(...res.map(({ pic, url }) => ({ pic: pic ?? url, url, type: "image" })));
    },
});

const appendImageItem = (src: string, pic?: string) =>
    new Promise<void>((resolve) => {
        uni.getImageInfo({
            src,
            success: ({ width, height }) => {
                if (width > 2000 || height > 2000) {
                    uni.$u.toast("选择素材中宽高2000以上的图片将被过滤");
                } else {
                    dataList.value.push({ pic: pic ?? src, url: src, type: "image" });
                }
                resolve();
            },
            fail: () => resolve(),
        });
    });

const chooseUploadType = () => {
    const actions = [
        () => uploadAndProcessFiles("file"),
        () => uploadAndProcessFiles("image"),
        () => (showChooseMaterial.value = true),
        () => (showHistory.value = true),
    ];
    uni.showActionSheet({
        itemList: ["从微信聊天中选择", "从相册选择图片", "从素材库中选择", "从创作库选择素材"],
        success: ({ tapIndex }) => actions[tapIndex]?.(),
    });
};

const handleChooseMaterial = (list: any[]) => {
    list.forEach((item) => appendImageItem(item.url, item.pic));
};

const handleSelectHistory = (list: any[]) => {
    list.forEach((item) => appendImageItem(item.image));
};

const handleDelete = (index: number) => dataList.value.splice(index, 1);

const previewImage = (index: number) => {
    uni.previewImage({ urls: dataList.value.map((item) => item.pic), current: index });
};

const handleConfirm = () => {
    if (!dataList.value.length) {
        uni.$u.toast("请至少选择一个图片素材");
        return;
    }
    materialStore.setList("imageList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
