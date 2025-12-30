<template>
    <view class="h-screen flex flex-col">
        <view class="font-bold text-[30rpx] mx-4 mt-4">图片素材({{ dataList.length }})</view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-[26rpx] p-4 pb-[100rpx]">
                    <view
                        class="h-[220rpx] bg-white rounded-[20rpx] flex flex-col items-center justify-center"
                        @click="chooseUploadType">
                        <image src="@/ai_modules/device/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-[28rpx] text-[#000000b3] mt-1">添加</text>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="h-[220rpx] relative"
                        @click="previewImage(index)">
                        <image :src="item.pic" class="w-full h-full rounded-[20rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click="handleDelete(index)">
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
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material v-model="showChooseMaterial" type="image" @select="handleChooseMaterial" />
</template>

<script setup lang="ts">
import useMaterialStore from "@/ai_modules/device/stores/material";
import useUpload from "@/hooks/useUpload";

const materialStore = useMaterialStore();
const { imageList } = storeToRefs(materialStore);

const dataList = ref<any[]>(JSON.parse(JSON.stringify(imageList.value)));
const showChooseMaterial = ref(false);

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    imageAccept: ["jpg", "jpeg", "png", "webp"],
    fileAccept: ["jpg", "jpeg", "png", "webp"],
    onSuccess: (res: any[]) => {
        dataList.value.push(...res.map((item) => ({ pic: item.pic || item.url, url: item.url, type: "image" })));
    },
});

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("file");
            else if (res.tapIndex === 1) showChooseMaterial.value = true;
            else if (res.tapIndex === 2) uploadAndProcessFiles("image");
        },
    });
};

const handleChooseMaterial = (list: any[]) => {
    dataList.value.push(...list.map((item) => ({ pic: item.pic, url: item.content, type: "image" })));
};

const handleDelete = (index: number) => {
    dataList.value.splice(index, 1);
};

const previewImage = (index: number) => {
    uni.previewImage({
        urls: dataList.value.map((item) => item.pic),
        current: index,
    });
};

const handleConfirm = () => {
    if (dataList.value.length === 0) {
        uni.$u.toast("请至少选择一个图片素材");
        return;
    }
    materialStore.setList("imageList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
