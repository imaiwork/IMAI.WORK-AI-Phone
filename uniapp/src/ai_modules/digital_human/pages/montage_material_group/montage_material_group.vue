<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="px-4">
            <view class="font-medium text-[30rpx]"> 素材（共{{ materialList.length }}张） </view>
            <view class="mt-1 text-xs text-[#0000004d]">
                <template v-if="!isStoryboard">
                    <material-duration-bar ref="materialDurationBarRef" :material-list="materialList" />
                </template>
                <template v-else>总量限制：素材最多不能超过200个</template>
            </view>
        </view>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4">
                    <material-container
                        :material-list="materialList"
                        @preview="previewMaterial"
                        @replace="handleReplaceMaterial"
                        @delete="handleDeleteMaterial"
                        @upload="chooseUploadType()" />
                </view>
            </scroll-view>
        </view>

        <view class="bg-white flex-shrink-0 pb-5 pt-2 px-4">
            <view
                class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleConfirm">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">确定保存</text>
            </view>
        </view>
    </view>

    <choose-history
        v-model="showChooseHistory"
        :limit="replaceMaterialIndex === -1 ? 9 : 1"
        @select="handleSelectHistory" />
    <upload-category-panel v-model="showUploadCategoryPanel" @select="handleSelectCategory" />
    <choose-material
        v-model="showMaterialLibrary"
        :limit="uploadMaterialType === 'image' || replaceMaterialIndex === -1 ? 9 : 1"
        :type="uploadMaterialType"
        :mode="uploadMaterialMode"
        @select="handleSelectMaterial" />
    <video-preview
        v-if="showVideoPreview"
        v-model="showVideoPreview"
        :video-url="videoPreview.url"
        :poster="videoPreview.poster"
        @update:show="showVideoPreview = false" />
    <upload-rule-pop v-model="showUploadTip" @handle-upload="uploadAndProcessFiles(uploadMaterialType)" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { montageConfig } from "@/ai_modules/digital_human/config";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import MaterialDurationBar from "@/ai_modules/digital_human/components/material-duration-bar/material-duration-bar.vue";
import MaterialContainer from "@/ai_modules/digital_human/components/material-container/material-container.vue";

// ─── 复用 useMaterialStep ────────────────────────────────────────
import { useMaterialStep } from "../../hooks/useMaterialStep";

// ─── 页面状态 ─────────────────────────────────────────────────────

const { emit } = useEventBusManager();
const materialType = ref("");
const isStoryboard = computed(() => materialType.value === "storyboard");

const materialListWrapper = reactive({ materialList: [] as any[] });

// ─── 初始化 useMaterialStep ──────────────────────────────────────

const {
    showUploadCategoryPanel,
    showMaterialLibrary,
    showChooseHistory,
    showVideoPreview,
    showUploadTip,
    uploadMaterialType,
    uploadMaterialMode,
    replaceMaterialIndex,
    videoPreview,
    showUploadProgress,
    uploadMaterialList,
    uploadAndProcessFiles,
    getMaterialTotalDuration,
    handleSelectCategory,
    chooseUploadType,
    handleSelectMaterial,
    handleSelectHistory,
    previewMaterial,
    handleReplaceMaterial,
    handleDeleteMaterial,
} = useMaterialStep({
    formData: materialListWrapper,
});

// ─── 便捷访问 materialList ───────────────────────────────────────

const materialList = computed(() => materialListWrapper.materialList);

// ─── 确定保存 ────────────────────────────────────────────────────

const handleConfirm = (): void => {
    if (!isStoryboard.value && getMaterialTotalDuration() > montageConfig.materialTotalDuration * 60) {
        uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟`);
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.MONTAGE_MATERIAL_GROUP,
        data: materialListWrapper.materialList,
    });
    uni.navigateBack();
};

// ─── 生命周期 ────────────────────────────────────────────────────

onLoad((options: any) => {
    if (options.materialList) {
        materialListWrapper.materialList = JSON.parse(options.materialList);
    }
    materialType.value = options.type ?? "";
});
</script>
