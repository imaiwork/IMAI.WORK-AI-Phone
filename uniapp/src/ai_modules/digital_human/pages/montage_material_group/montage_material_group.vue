<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-medium text-[30rpx]">素材（共{{ materialList.length }}张）</view>
            <view class="mt-1 text-xs text-[#0000004d]">
                <template v-if="!isStoryboard">
                    总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                        montageConfig.imageDuration
                    }}秒/张，视频按实际时长/个)
                </template>
                <template v-else> 总量限制：素材最多不能超过200个 </template>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4 grid grid-cols-3 gap-[26rpx]">
                    <view
                        v-for="(item, index) in materialList"
                        :key="index"
                        class="w-full aspect-[3/4] relative"
                        @click="previewMaterial(item)">
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDeleteMaterial(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                        <view class="relative leading-[0] rounded-[12rpx] overflow-hidden w-full h-full">
                            <image
                                :src="item.pic || item.content"
                                class="w-full h-full rounded-[12rpx]"
                                mode="aspectFill"></image>
                            <view
                                class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] flex items-center justify-center z-[88]">
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
                        <view class="absolute bottom-4 w-full z-[89] flex justify-center">
                            <view class="dh-version-name" @click.stop="handleReplaceMaterial(index)"> 替换 </view>
                        </view>
                    </view>
                    <view
                        class="bg-white rounded-[12rpx] flex flex-col items-center justify-center aspect-[3/4]"
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
    <choose-history v-model="showHistory" :limit="1" @select="handleSelectHistory" />
    <choose-material
        v-model="showMaterialLibrary"
        :limit="uploadMaterialType == 'image' || replaceMaterialIndex === -1 ? 9 : 1"
        :type="uploadMaterialType"
        @select="handleSelectMaterial" />
    <video-preview
        v-if="showVideoPreview"
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview>
    <upload-rule-pop v-model="showUploadTip" @handle-upload="uploadAndProcessFiles(uploadMaterialType)" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import useUpload from "@/hooks/useUpload";
import { montageConfig } from "@/ai_modules/digital_human/config";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";
import ChooseHistory from "@/ai_modules/digital_human/components/choose-history/choose-history.vue";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";

const { emit } = useEventBusManager();

const materialType = ref("");

const materialList = ref<any[]>([]);
const isFirstUpload = ref(true);
const showUploadTip = ref(false);
const uploadMaterialType = ref<any>();
const showHistory = ref(false);
const showMaterialLibrary = ref(false);
const replaceMaterialIndex = ref(-1);
const playItem = reactive({
    pic: "",
    url: "",
});
const showVideoPreview = ref(false);

const isStoryboard = computed(() => materialType.value === "storyboard");

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
    uni.showActionSheet({
        itemList: ["从相册选择图片", "从相册选择视频", "从图片素材库选择", "从视频素材库选择", "从创作库选择"],
        success: async (res) => {
            const { tapIndex } = res;
            if ([0, 1].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 0 ? "image" : "video";

                if (isFirstUpload.value) {
                    isFirstUpload.value = false;
                    showUploadTip.value = true;
                    return;
                }
                uploadAndProcessFiles(uploadMaterialType.value);
            } else if ([2, 3].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 2 ? "image" : "video";
                showMaterialLibrary.value = true;
            } else if (tapIndex === 4) {
                showHistory.value = true;
            }
        },
    });
};

const { processAndAppend } = useMaterial(toRef(materialList, "value"));

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isFetchVideoInfo: true,
    isTranscode: true,
    videoDuration: [1, 59],
    onSuccess: (res: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            materialList.value[replaceMaterialIndex.value] = res[0];
        } else {
            materialList.value = materialList.value.concat(res);
        }
        replaceMaterialIndex.value = -1;
    },
});

const handleSelectHistory = async (lists: any[]) => {
    const normalized = lists.map((item) => ({
        ...item,
        actualUrl: item.clip_result_url || item.video_result_url,
    }));

    await processAndAppend({
        rawList: normalized,
        urlField: "actualUrl",
        type: "video",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showHistory.value = false),
    });
};

const handleSelectMaterial = async (res: any[]) => {
    const type = uploadMaterialType.value;
    await processAndAppend({
        rawList: res,
        urlField: "url",
        type: type as "video" | "image",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showMaterialLibrary.value = false),
    });
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    chooseUploadType();
};

const previewMaterial = (item: any) => {
    if (item.type === "image") {
        uni.previewImage({
            urls: [item.pic],
        });
    } else {
        playItem.pic = item.pic || item.content;
        playItem.url = item.url;
        showVideoPreview.value = true;
    }
};

const handleDeleteMaterial = (index: number) => {
    materialList.value.splice(index, 1);
};

const handleConfirm = () => {
    if (!isStoryboard.value && getCurrentTotalDuration.value > montageConfig.materialTotalDuration * 60) {
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
    materialType.value = options.type;
});
</script>

<style scoped></style>
