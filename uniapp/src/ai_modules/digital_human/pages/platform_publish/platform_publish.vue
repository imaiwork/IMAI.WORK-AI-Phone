<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="发布视频创建"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="step" @step="handleStep" />
        </view>

        <view class="grow min-h-0">
            <view class="h-full flex flex-col" v-show="step === 1">
                <view class="px-4 pt-[16rpx] space-y-[16rpx]">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">选择素材</text>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF]">
                            已选
                            <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                            个
                        </text>
                    </view>
                    <view class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="info-circle" color="#0065fb" size="20" />
                        <text class="text-xs text-primary font-medium">支持同时添加图片与视频素材</text>
                    </view>
                </view>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[16rpx] p-4">
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view
                                    class="aspect-[3/4] rounded-[20rpx] relative overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]"
                                    @click="previewMaterial(item)">
                                    <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                    <view
                                        class="absolute top-1 left-1 flex items-center gap-[4rpx] bg-[#00000066] rounded-full px-[8rpx] py-[2rpx]">
                                        <text class="text-[18rpx] text-white font-mono">
                                            {{ item.type === "image" ? "图片" : formatAudioTime(item.duration || 0) }}
                                        </text>
                                    </view>
                                </view>

                                <view class="absolute bottom-2 w-full z-[89] flex justify-center">
                                    <view
                                        class="px-3 py-1 text-white text-xs rounded-full border border-solid border-[#ffffff]/30"
                                        @click.stop="handleReplaceMaterial(index)">
                                        替换
                                    </view>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteMaterial(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                class="aspect-[3/4] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[10rpx]"
                                @click="showUploadCategoryPanel = true">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <u-icon name="plus" color="#0065fb" size="28" />
                                </view>
                                <text class="text-xs text-primary font-semibold">添加素材</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view class="h-full flex flex-col" v-show="step === 2">
                <view class="px-4 pt-[16rpx] space-y-[16rpx]">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">填写文案</text>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF]">
                            共
                            <text class="text-primary font-bold">{{ formData.copywriterList.length }}</text>
                            条
                        </text>
                    </view>
                    <view class="flex gap-[12rpx]">
                        <view
                            class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                            @click="handleShowCopywriter()">
                            <u-icon name="edit-pen" color="#4B5563" size="22" />
                            <text class="text-[28rpx] font-bold text-[#334155]">手动输入</text>
                        </view>
                        <view
                            class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                            @click="showChooseAgent = true">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                            <text class="text-[28rpx] font-bold text-white">AI 智能生成</text>
                        </view>
                    </view>
                </view>

                <view class="grow min-h-0 mt-[16rpx]">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-[40rpx]">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="relative bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleEditCopywriter(index)">
                                <view class="absolute left-0 top-0 w-[6rpx] h-full bg-primary rounded-l-[24rpx]" />
                                <view class="pl-[32rpx] pr-[24rpx] pt-[22rpx] pb-[20rpx]">
                                    <view class="flex items-center gap-[12rpx] mb-[16rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                            <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                        </view>
                                        <text class="flex-1 text-[28rpx] font-bold text-[#0D1117] truncate">{{
                                            item.title
                                        }}</text>
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                            @click.stop="handleDeleteCopywriter(index)">
                                            <u-icon name="close" color="#9CA3AF" size="14" />
                                        </view>
                                    </view>
                                    <text class="text-[#4B5563] leading-relaxed">{{ item.content }}</text>
                                    <view
                                        v-if="item.topic && item.topic.length > 0"
                                        class="mt-[16rpx] flex items-center flex-wrap gap-[8rpx]">
                                        <view
                                            v-for="(tag, tindex) in item.topic"
                                            :key="tindex"
                                            class="bg-[#F0F6FF] rounded-full px-[14rpx] py-[4rpx]">
                                            <text class="text-[22rpx] text-primary">#{{ tag }}</text>
                                        </view>
                                    </view>
                                    <view v-if="item.poi" class="flex items-center gap-[6rpx] mt-[12rpx]">
                                        <u-icon name="map-fill" size="22" color="#9CA3AF" />
                                        <text class="text-[22rpx] text-[#9CA3AF] font-medium">{{ item.poi }}</text>
                                    </view>
                                    <view
                                        class="flex justify-end mt-[12rpx] pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                        <text class="text-[22rpx] text-[#C0C4CC]">{{ item.content.length }} 字</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <copywriter-empty v-else />
                </view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                v-if="step === 1"
                class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                :class="formData.materialList.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                <text
                    class="text-[32rpx] font-extrabold leading-none"
                    :class="formData.materialList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ formData.materialList.length }}
                </text>
                <text
                    class="text-[20rpx] mt-[4rpx]"
                    :class="formData.materialList.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                    已选
                </text>
            </view>

            <view
                v-else-if="step < STEPS.length"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>

            <view
                v-if="step < STEPS.length"
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                :class="canNext ? 'bg-primary shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'bg-[#E5E7EB]'"
                @click="handleStep(step, 'next')">
                <text class="text-[30rpx] font-bold" :class="canNext ? 'text-white' : 'text-[#9CA3AF]'"> 下一步 </text>
            </view>

            <view
                v-else
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateVideo">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide relative z-10">创建任务</text>
            </view>
        </view>
    </view>

    <upload-category-panel v-model="showUploadCategoryPanel" @select="handleSelectCategory" />
    <choose-history v-model="showHistory" :limit="replaceMaterialIndex === -1 ? 9 : 1" @select="handleSelectHistory" />
    <choose-material
        v-model="showMaterialLibrary"
        :mode="uploadMaterialMode"
        :limit="replaceMaterialIndex === -1 ? 9 - formData.materialList.length : 1"
        :type="uploadMaterialType"
        @select="handleSelectMaterial" />
    <choose-agent v-if="showChooseAgent" v-model="showChooseAgent" @select="handleSelectAgent" />

    <video-preview
        v-if="showVideoPreview"
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
    <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频发布任务创建成功"
        desc="您可以立即去设置发布任务"
        to-text=""
        @seek="toRecord" />
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";
import { formatAudioTime } from "@/utils/util";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import Steps from "@/ai_modules/digital_human/components/steps/steps.vue";
import CopywriterEmpty from "@/ai_modules/digital_human/components/copywriter-empty/copywriter-empty.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";

import { STEPS, createDefaultFormData } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useMaterialStep } from "./hooks/useMaterialStep";
import { useCopywriter } from "./hooks/useCopywriter";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();

const formData = reactive(createDefaultFormData());

const { step, canNext, handleStep } = useStep(formData);
const {
    showUploadCategoryPanel,
    uploadMaterialType,
    uploadMaterialMode,
    showHistory,
    showMaterialLibrary,
    replaceMaterialIndex,
    showVideoPreview,
    playItem,
    showUploadProgress,
    uploadMaterialList,
    handleSelectCategory,
    previewMaterial,
    handleReplaceMaterial,
    handleDeleteMaterial,
    handleSelectHistory,
    handleSelectMaterial,
} = useMaterialStep(formData);
const {
    showChooseAgent,
    handleSelectAgent,
    handleShowCopywriter,
    handleEditCopywriter,
    handleDeleteCopywriter,
    onCopywriterConfirm,
} = useCopywriter(formData);

const { showCreateSuccess, handleCreateVideo, toRecord } = useCreateTask(formData);

onLoad(async (options: any) => {
    if (options?.source === "creation_video") {
        const videoIds: string[] = JSON.parse(options.ids || "[]");
        const { lists } = await getVideoCreationRecord({ page_size: 99999 });
        lists
            ?.filter((item: any) => videoIds.includes(item.task_id))
            .forEach((item: any) => {
                formData.materialList.push({
                    url: item.clip_result_url || item.video_result_url,
                    pic: item.pic,
                    type: "video",
                    duration: item.duration,
                });
            });
    }
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.PLATFORM_PUBLISH_COPYWRITER || type === ListenerTypeEnum.AI_COPYWRITER) {
            onCopywriterConfirm(data);
        }
    });
});
</script>
