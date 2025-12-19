<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-bold text-[30rpx]">素材（共{{ materialList.length }}张）</view>
            <view class="mt-1 text-xs text-[#0000004d]"> 至少需要1个视频+3张图片</view>
            <view class="mt-1 text-xs text-[#0000004d]">
                总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                    montageConfig.imageDuration
                }}秒/张，视频按实际时长/个)
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4 grid grid-cols-3 gap-[26rpx]">
                    <view
                        v-for="(item, index) in materialList"
                        :key="index"
                        class="w-full h-[220rpx] relative rounded-[12rpx]">
                        <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click="handleDeleteMaterial(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                        <view
                            class="absolute bottom-0 h-[40rpx] w-full bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-[88]">
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
                            <view class="dh-version-name" @click="handleReplaceMaterial(index)"> 替换 </view>
                        </view>
                    </view>
                    <view
                        class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                        @click="chooseUploadType">
                        <image
                            src="@/ai_modules/digital_human/static/icons/add.svg"
                            class="w-[40rpx] h-[40rpx]"></image>
                        <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
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
    <upload-rule-pop v-model="showUploadTip" @handle-upload="handleUploadVideo" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import useMontageMaterial from "@/ai_modules/digital_human/hooks/useMontageMaterial";
import { montageConfig } from "@/ai_modules/digital_human/config";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const materialList = ref<any[]>([]);
const showUploadTip = ref(false);
const replaceMaterialIndex = ref(-1);

// 获取当前素材总时长
const getCurrentTotalDuration = computed(() => {
    const imageCount = materialList.value.filter((item: any) => item.type === "image").length;
    // 单张图片计算为 2秒 + 视频时长，所有素材总时长不能超过5分钟，
    const totalDuration =
        materialList.value.reduce((acc, item) => (item.type === "video" ? acc + item.duration : acc), 0) +
        imageCount * montageConfig.imageDuration;
    return totalDuration;
});

const chooseUploadType = () => {
    showUploadTip.value = false;
    uni.showActionSheet({
        itemList: ["选择图片素材", "选择视频素材"],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("image");
            else if (res.tapIndex === 1) {
                uploadAndProcessFiles("video");
            }
        },
    });
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useMontageMaterial({
    onSuccess: (res: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            materialList.value[replaceMaterialIndex.value] = res[0];
        } else {
            materialList.value = materialList.value.concat(res);
        }
        replaceMaterialIndex.value = -1;
    },
});

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    chooseUploadType();
};

const handleUploadVideo = () => {};

const handleDeleteMaterial = (index: number) => {
    materialList.value.splice(index, 1);
};

const handleConfirm = () => {
    // 判断素材是否符合要求
    const videoCount = materialList.value.filter((item: any) => item.type === "video").length;
    const imageCount = materialList.value.filter((item: any) => item.type === "image").length;
    if (videoCount < 1 || imageCount < 3) {
        uni.showToast({
            title: "至少需要1个视频+3张图片",
            icon: "none",
        });
        return;
    }
    // 单张图片计算为 2秒 + 视频时长，所有素材总时长不能超过5分钟，
    if (getCurrentTotalDuration.value > montageConfig.materialTotalDuration * 60) {
        uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟`);
        return;
    }

    emit("confirm", {
        type: ListenerTypeEnum.MONTAGE_MATERIAL_GROUP,
        data: materialList.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.materialList) {
        materialList.value = JSON.parse(options.materialList);
    }
});
</script>

<style scoped></style>
