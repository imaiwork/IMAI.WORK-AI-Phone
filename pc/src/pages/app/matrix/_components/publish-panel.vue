<template>
    <div class="h-full bg-white rounded-[20px] overflow-x-auto dynamic-scroller">
        <div class="h-full flex flex-col">
            <div class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-[#F3F4F6]">
                <div
                    class="group flex items-center gap-2 cursor-pointer transition-all hover:translate-x-[-4px]"
                    @click="handleBack">
                    <div
                        class="w-8 h-8 rounded-full bg-[#F9FAFB] flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                        <Icon name="el-icon-ArrowLeft" :size="14"></Icon>
                    </div>
                    <span class="text-sm font-medium text-[#6B7280] group-hover:text-[#111827] transition-colors"
                        >返回上一步</span
                    >
                </div>

                <div class="flex items-center gap-3">
                    <ElButton
                        class="!rounded-xl !h-10 !px-8 !border-[#E5E7EB] !bg-white !text-[#374151] font-medium hover:!bg-[#F9FAFB] active:scale-95 transition-all"
                        :disabled="isSubmitting"
                        @click="handleCancel">
                        取消
                    </ElButton>
                    <ElButton
                        type="primary"
                        class="!rounded-xl !h-10 !px-8 font-medium shadow-[#0065fb]/20 active:scale-95 transition-all"
                        :loading="isSubmitting"
                        @click="handleNext">
                        {{ isLastStep ? "提交任务" : "继续下一步" }}
                    </ElButton>
                </div>
            </div>
            <div class="px-8">
                <div class="flex items-center justify-center h-[120px] max-w-[900px] mx-auto relative">
                    <div v-for="(item, index) in steps" :key="index" class="flex items-center flex-1 last:flex-none">
                        <div
                            class="relative z-10 flex flex-col items-center gap-3 cursor-pointer group"
                            @click="handleStep(item.step)">
                            <div
                                class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm transition-all duration-300"
                                :class="[
                                    step >= item.step
                                        ? 'bg-primary text-white shadow-[#0065fb]/30'
                                        : 'bg-[#F3F4F6] text-[#9CA3AF]',
                                ]">
                                <i v-if="step == item.step" class="el-icon-check font-medium"></i>
                                <span v>{{ index + 1 }}</span>
                            </div>

                            <div
                                class="text-xs font-medium transition-colors duration-300 whitespace-nowrap"
                                :class="[step >= item.step ? 'text-[#111827]' : 'text-[#9CA3AF]']">
                                {{ item.title }}
                            </div>

                            <div
                                v-if="step === item.step"
                                class="absolute -top-1 -right-1 w-3 h-3 bg-primary rounded-full border-2 border-white animate-pulse"></div>
                        </div>

                        <div
                            v-if="index != steps.length - 1"
                            class="grow h-[3px] mx-6 rounded-full transition-all duration-500 bg-[#F3F4F6] relative overflow-hidden">
                            <div
                                class="absolute inset-0 bg-primary transition-all duration-500 origin-left"
                                :style="{ transform: step > item.step ? 'scaleX(1)' : 'scaleX(0)' }"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 内容区域 -->
            <div class="grow min-h-0 flex flex-col p-5">
                <!-- 步骤一：基本信息 -->
                <div class="grow min-h-0 flex flex-col w-[520px] mx-auto pb-6" v-show="step == 1">
                    <div class="flex items-center justify-between flex-shrink-0 mb-6 px-2">
                        <div>
                            <h2 class="text-[22px] font-black text-[#111827] tracking-tight">
                                选择{{ publishTypeMap[type] }}
                            </h2>
                            <p class="text-xs text-[#9CA3AF] font-medium mt-1">请上传并管理您的任务素材</p>
                        </div>
                    </div>

                    <div
                        class="grow min-h-0 flex flex-col bg-white border border-[#E5E7EB] rounded-[32px] overflow-hidden">
                        <div
                            class="flex-shrink-0 px-6 py-5 bg-[#F9FAFB] border-b border-[#F3F4F6] flex justify-between items-center">
                            <div class="flex flex-col gap-y-1">
                                <template v-if="isVideoMode">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                        <span class="text-sm font-black text-[#374151]">视频素材库</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-[11px] text-[#6B7280] bg-white px-2 py-0.5 rounded border border-[#E5E7EB]">
                                            已添加:
                                            <span class="text-primary font-medium">{{
                                                materialFormData.media_url.length
                                            }}</span>
                                        </span>
                                        <span class="text-[11px] text-[#9CA3AF]"
                                            >限制: ≤{{ videoLimit }}个 / {{ videoSize }}MB以内</span
                                        >
                                    </div>
                                </template>

                                <template v-if="isImageMode">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                                        <span class="text-sm font-black text-[#374151]">图文素材库</span>
                                    </div>
                                    <div class="text-[11px] text-[#9CA3AF]">每组上限: {{ maxImageCount }} 张</div>
                                </template>
                            </div>

                            <button
                                v-if="isImageMode"
                                @click="handleAddImageGroup"
                                class="h-9 px-4 rounded-xl bg-primary text-white text-xs font-medium hover:bg-[#4338CA] transition-all active:scale-95 flex items-center gap-2">
                                <i class="el-icon-plus"></i>
                                <span>添加图片组</span>
                            </button>
                        </div>

                        <div class="grow min-h-0">
                            <ElScrollbar>
                                <div class="p-6">
                                    <div
                                        class="border-2 border-dashed border-[#E5E7EB] rounded-[24px] p-3 transition-all hover:border-[#0065fb]/30 bg-[#F9FAFB]/50"
                                        v-if="isVideoMode">
                                        <MaterialPicker
                                            v-model:material-list="materialFormData.media_url"
                                            :type="type"
                                            :accept="videoFormat"
                                            :max-video-count="videoLimit"
                                            :max-size="videoSize"
                                            @preview-video="handlePreviewVideo"
                                            @update:material-list="handleUpdateMaterialList"
                                            @import-material="handleImportMaterial($event, 0)"
                                            @change-material="handleChangeMaterial" />
                                    </div>

                                    <template v-if="isImageMode">
                                        <div class="space-y-6">
                                            <div
                                                v-for="(item, index) in materialFormData.media_url"
                                                :key="index"
                                                class="relative border border-[#F3F4F6] bg-white p-4 rounded-[24px] transition-all group">
                                                <div
                                                    class="absolute -left-3 -top-3 w-8 h-8 rounded-full bg-[#111827] text-white flex items-center justify-center text-[10px] font-black border-4 border-white">
                                                    {{ index + 1 }}
                                                </div>
                                                <div
                                                    class="absolute -right-2 -top-2 w-7 h-7 z-10"
                                                    @click="handleDeleteMaterialGroup(index)">
                                                    <close-btn :icon-size="12"></close-btn>
                                                </div>

                                                <div class="pt-2">
                                                    <MaterialPicker
                                                        v-model:material-list="item.url"
                                                        :type="type"
                                                        :max-size="imageSize"
                                                        :max-image-count="maxImageCount"
                                                        @update:material-list="handleUpdateMaterialList"
                                                        @import-material="handleImportMaterial($event, index)"
                                                        @change-material="handleChangeMaterial" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                </div>
                <!-- 步骤二：选择内容 -->
                <div class="grow min-h-0 flex flex-col" v-show="step == 2">
                    <div class="flex items-center justify-between flex-shrink-0">
                        <div class="font-medium text-[20px]">填写文案</div>
                        <ElButton type="primary" class="!rounded-full !h-10 w-[106px]" @click="openCopywritingMaterial"
                            >智能文案</ElButton
                        >
                    </div>
                    <div class="grow min-h-0 mt-5 flex gap-x-[18px]">
                        <!-- 标题与描述 -->
                        <div class="content-item title-content">
                            <copywriting-card
                                v-model:model-value="materialFormData.title"
                                :type="1"
                                :publish-type-name="publishTypeMap[type]"
                                @update:model-value="updateCopywritingMaterial(materialFormData)" />
                        </div>
                        <div class="content-item desc-content">
                            <copywriting-card
                                v-model:model-value="materialFormData.subtitle"
                                :type="2"
                                :publish-type-name="publishTypeMap[type]"
                                @update:model-value="updateCopywritingMaterial(materialFormData)" />
                        </div>
                    </div>
                </div>
                <!-- 步骤三：发布设置 -->
                <ElScrollbar v-show="step == 3">
                    <div class="w-[480px] mx-auto bg-white rounded-[24px] p-8 shadow-sm border border-slate-100">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-1.5 h-6 bg-primary rounded-full"></div>
                            <div class="font-black text-[22px] text-[#0F172A] tracking-tight">发布设置</div>
                        </div>

                        <div class="flex flex-col gap-y-8">
                            <div class="form-item">
                                <div class="item-label">
                                    <Icon name="el-icon-Document" />
                                    <span class="ml-2">任务名称</span>
                                </div>
                                <ElInput
                                    v-model="formData.name"
                                    placeholder="请输入任务名称"
                                    maxlength="30"
                                    class="custom-input !w-full">
                                </ElInput>
                            </div>
                            <div class="form-item">
                                <div class="item-label">
                                    <Icon name="el-icon-User" />
                                    <span class="ml-2">发布账号</span>
                                </div>
                                <ElSelect
                                    v-model="formData.accounts"
                                    placeholder="请选择发布的账号"
                                    class="custom-select !w-full"
                                    multiple
                                    collapse-tags
                                    collapse-tags-tooltip
                                    :show-arrow="true">
                                    <ElOption
                                        v-for="item in accountList"
                                        :key="item.account"
                                        :label="`${item.nickname}（${item.account}）`"
                                        :value="item.account">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <img :src="getPlatform(item.type)?.icon" class="w-4 h-4 rounded-sm" />
                                                <span class="font-medium text-[13px]">{{ item.nickname }}</span>
                                            </div>
                                            <span class="text-[11px] text-slate-400 opacity-70">{{
                                                item.account
                                            }}</span>
                                        </div>
                                    </ElOption>
                                </ElSelect>
                            </div>
                            <div class="form-item">
                                <div class="item-label">
                                    <Icon name="el-icon-Location" />
                                    <span class="ml-2">标记地点(选填)</span>
                                </div>
                                <ElInput
                                    v-model="formData.location"
                                    placeholder="请输入标记地点"
                                    maxlength="100"
                                    show-word-limit
                                    class="custom-input !w-full">
                                </ElInput>
                            </div>
                            <div class="form-item">
                                <div class="item-label">
                                    <Icon name="el-icon-Timer" />
                                    <span class="ml-2">每日发布频率</span>
                                </div>
                                <div class="grid grid-cols-4 gap-2 mt-3">
                                    <div
                                        v-for="(item, index) in publishFrequencyOptions"
                                        :key="index"
                                        class="freq-card"
                                        :class="{
                                            'is-active':
                                                formData.publish_frep == item && currentPublishFrequencyIdx != 5,
                                        }"
                                        @click="handlePublishFrequency(item)">
                                        <span class="text-[14px] font-black">{{ item }}</span>
                                        <span class="text-[10px] opacity-60 ml-0.5">条/日</span>
                                    </div>
                                    <div
                                        class="freq-card is-custom"
                                        :class="{ 'is-active': currentPublishFrequencyIdx == 5 }"
                                        @click="openCustomFreqDialog">
                                        <Icon name="el-icon-Setting" :size="14" />
                                        <span class="text-[13px] font-medium">{{
                                            customPublishFrep ? `${customPublishFrep}条` : "自定义"
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="item-label">
                                    <Icon name="el-icon-Calendar" />
                                    <span class="ml-2">任务周期</span>
                                </div>
                                <div class="grid grid-cols-4 gap-2 mt-3">
                                    <div
                                        v-for="(item, index) in taskFrequencyOptions"
                                        :key="index"
                                        class="freq-card"
                                        :class="{
                                            'is-active': formData.task_frep == item && currentTaskFrequencyIdx != 5,
                                        }"
                                        @click="handleTaskFrequency(item)">
                                        <span class="text-[14px] font-black">{{ item }}</span>
                                        <span class="text-[10px] opacity-60 ml-0.5">天</span>
                                    </div>
                                    <div
                                        class="freq-card is-custom"
                                        :class="{ 'is-active': currentTaskFrequencyIdx == 5 }"
                                        @click="currentTaskFrequencyIdx = 5">
                                        <Icon name="el-icon-Setting" :size="14" />
                                        <span class="text-[13px] font-medium">按日期</span>
                                    </div>
                                </div>

                                <Transition name="el-zoom-in-top">
                                    <div
                                        v-if="currentTaskFrequencyIdx == 5"
                                        class="mt-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            placeholder="请点击选择多个具体日期"
                                            type="dates"
                                            value-format="YYYY-MM-DD"
                                            :disabled-date="getDisabledTaskDate"
                                            class="modern-date-picker !w-full"
                                            @change="changeCustomDate" />
                                    </div>
                                </Transition>
                            </div>

                            <div class="form-item">
                                <div class="item-label mb-4">
                                    <Icon name="el-icon-AlarmClock" />
                                    <span class="ml-2">发布时间轴配置</span>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        class="time-config-card"
                                        :class="{
                                            'has-error':
                                                timeErrorIndex.length > 0 &&
                                                timeErrorIndex.some((item) => item.configIndex == index),
                                        }"
                                        v-for="(item, index) in formData.time_config"
                                        :key="index">
                                        <div
                                            class="flex items-center justify-between mb-4 border-b border-slate-50 pb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                                                <span class="text-[15px] font-[900] text-tx-primary">{{
                                                    dayjs(item.date).format("YYYY-MM-DD")
                                                }}</span>
                                            </div>
                                            <ElTag
                                                size="small"
                                                effect="plain"
                                                round
                                                class="!border-slate-200 !text-slate-400"
                                                >待发布</ElTag
                                            >
                                        </div>

                                        <ElScrollbar max-height="240px">
                                            <div class="space-y-4 pr-3 pb-2">
                                                <div
                                                    v-for="(time, timeIndex) in item.times"
                                                    :key="timeIndex"
                                                    class="relative pl-6">
                                                    <div
                                                        v-if="timeIndex !== item.times.length - 1"
                                                        class="absolute left-[7px] top-4 bottom-[-16px] w-[1px] bg-dashed"></div>
                                                    <div
                                                        class="absolute left-0 top-1.5 w-[15px] h-[15px] rounded-full border-2 border-primary bg-white flex items-center justify-center">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                                    </div>

                                                    <div
                                                        class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2"
                                                        :class="{
                                                            '!text-red-500':
                                                                timeErrorIndex.length > 0 &&
                                                                timeErrorIndex.some((item) =>
                                                                    item.errorIndexes.includes(timeIndex)
                                                                ),
                                                        }">
                                                        第{{ timeIndex + 1 }}个内容任务发布时间
                                                    </div>
                                                    <ElTimePicker
                                                        class="modern-time-picker !w-full"
                                                        :class="{
                                                            'has-error':
                                                                timeErrorIndex.length > 0 &&
                                                                timeErrorIndex.some((item) =>
                                                                    item.errorIndexes.includes(timeIndex)
                                                                ),
                                                        }"
                                                        v-model="item.times[timeIndex]"
                                                        is-range
                                                        range-separator="至"
                                                        format="HH:mm"
                                                        value-format="HH:mm"
                                                        :show-arrow="false" />
                                                </div>
                                            </div>
                                        </ElScrollbar>
                                    </div>

                                    <Transition name="el-fade-in-linear">
                                        <div v-if="taskErrorMsg" class="error-notice">
                                            <Icon name="el-icon-WarningFilled" />
                                            <span>{{ taskErrorMsg }}</span>
                                        </div>
                                    </Transition>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
    <popup
        ref="publishNumberPopRef"
        v-model="showPublishNumberPop"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        width="420px"
        @close="showPublishNumberPop = false">
        <div>
            <div class="flex gap-3 mb-8">
                <div
                    class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center border border-[#0065fb]/10 shadow-light shadow-[#0065fb]/5">
                    <Icon name="el-icon-Timer" :size="20" />
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[16px] font-black text-[#0F172A]">设定每日频率</span>
                        <ElTooltip content="发布条数受时间间隔限制，系统已自动为您计算最大上限。">
                            <div class="text-slate-300 cursor-help leading-[0]">
                                <Icon name="el-icon-QuestionFilled" :size="14" />
                            </div>
                        </ElTooltip>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">请合理规划每日发布节奏，避免频率过高</p>
                </div>
            </div>

            <div class="bg-[#f8fafc]/50 rounded-[24px] border border-slate-100 p-6 flex flex-col items-center">
                <div class="text-[13px] text-slate-500 font-black mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    设定每日发布总量
                </div>

                <div class="bg-[#0065fb]/5 rounded-[28px] p-6 border border-[#0065fb]/10">
                    <div class="flex items-end justify-center gap-2 mb-6">
                        <span class="text-[42px] font-medium text-primary leading-none tracking-tighter">{{
                            tempCustomFreq
                        }}</span>
                        <span class="text-[14px] font-black text-primary/60 mb-1.5">条 / 24h</span>
                    </div>

                    <div class="px-2 mb-8">
                        <ElSlider
                            v-model="tempCustomFreq"
                            :min="1"
                            :max="(24 * 60) / TIME_INTERVAL"
                            :show-tooltip="false"
                            class="modern-slider" />
                        <div class="flex justify-between mt-2 px-1">
                            <span class="text-[10px] font-black text-slate-400">MIN: 1</span>
                            <span class="text-[10px] font-black text-slate-400"
                                >MAX: {{ (24 * 60) / TIME_INTERVAL }}</span
                            >
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <div
                            class="inline-flex items-center bg-white rounded-full p-1 shadow-sm border border-slate-100">
                            <div
                                class="px-3 text-[11px] font-black text-slate-400 uppercase tracking-wider border-r border-slate-50">
                                快速微调
                            </div>
                            <ElInputNumber
                                v-model="tempCustomFreq"
                                :min="1"
                                :max="(24 * 60) / TIME_INTERVAL"
                                controls-position="right"
                                size="small"
                                class="!w-[80px] custom-number-step" />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex items-start gap-2 text-[11px] leading-relaxed text-orange-600 bg-[#fff7ed]/80 border border-[#ffedd5]/50 px-4 py-3 rounded-xl w-full">
                    <Icon name="el-icon-WarningFilled" />
                    <span class="font-medium ml-0.5">修改此数值将导致下方已设置的时间段重置，请确认后再操作。</span>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <ElButton @click="showPublishNumberPop = false" class="modern-btn-secondary !h-11 !px-8">
                    取消
                </ElButton>
                <ElButton type="primary" @click="handleConfirmCustomFreq" class="modern-btn-primary !h-11 !px-8">
                    立即生效
                </ElButton>
            </div>
        </div>
    </popup>
    <!-- 弹窗 -->
    <content-copywriting-material
        v-if="showCopywritingMaterial"
        ref="copywritingMaterialRef"
        @close="showCopywritingMaterial = false"
        @confirm="handleChooseCopywriting" />
    <material-popup
        v-if="showMaterialPop"
        ref="materialPopRef"
        :type="materialTypeMap[type]"
        :limit="materialPickerLimit()"
        :multiple="isBatchPickingMaterial"
        @close="showMaterialPop = false"
        @confirm="handleChooseMaterial" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { ElScrollbar } from "element-plus";
import { getPublishAccountList, addMatrixTask, publishDeviceTask } from "@/api/device";
import { uploadImage } from "@/api/app";
import { PublishTaskTypeEnum, MaterialActionType, MaterialTypeEnum, SidebarTypeEnum } from "../_enums";
import ContentCopywritingMaterial from "./content-copywriting-material.vue";
import CopywritingCard from "./copywriting-card.vue";
import MaterialPicker from "./material-picker.vue";
import MaterialPopup from "./material-popup.vue";
import useMaterial from "../_hooks/useMaterial";
import { validateSchedule } from "./utils";

// =================================================================================================
// Props & Emits
// =================================================================================================

const props = withDefaults(defineProps<{ type: PublishTaskTypeEnum }>(), {
    type: PublishTaskTypeEnum.VIDEO,
});

const emit = defineEmits<{ (e: "back"): void }>();

// =================================================================================================
// Composable & Utils
// =================================================================================================

const route = useRoute();
const router = useRouter();
const nuxtApp = useNuxtApp();
const { updateCopywritingMaterial } = useMaterial();
const { getPlatform } = useSocialPlatform();

// =================================================================================================
// Enums & Constants
// =================================================================================================

const publishTypeMap = {
    [PublishTaskTypeEnum.VIDEO]: "视频",
    [PublishTaskTypeEnum.IMAGE]: "图文",
};

const materialTypeMap = {
    [PublishTaskTypeEnum.VIDEO]: MaterialTypeEnum.VIDEO,
    [PublishTaskTypeEnum.IMAGE]: MaterialTypeEnum.IMAGE,
};

const steps = [
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "设定时间" },
];

// 视频上传限制
const videoLimit = 99;
// 视频上传大小
const videoSize = 100;
// 视频上传格式
const videoFormat = ".mp4,.mov";
// 图片上传大小
const imageSize = 50;

const maxImageCount = 9;
const publishFrequencyOptions = [1, 2, 3, 5, 10];
const taskFrequencyOptions = [1, 3, 5, 10, 30];
const TIME_INTERVAL = 30; // 分钟
// =================================================================================================
// State
// =================================================================================================

const { type } = toRefs(props);
const query = searchQueryToObject();
const materialId = computed(() => query.material_id);

const step = ref(1);

// 表单数据
const formData = reactive<{
    name: string;
    accounts: string[];
    media_type: PublishTaskTypeEnum;
    time_config: any[];
    publish_frep: number;
    custom_date: string[];
    task_frep: number;
    location: string;
}>({
    name: `${publishTypeMap[type.value]}矩阵任务${dayjs().format("YYYYMMDDHHmm")}`,
    accounts: [],
    media_type: type.value,
    time_config: [],
    publish_frep: 2,
    custom_date: [],
    task_frep: 1,
    location: "",
});

// 素材数据
const materialFormData = reactive({
    id: materialId.value,
    name: formData.name,
    media_type: type.value,
    media_url: [],
    title: [] as Array<{ content: string }>,
    subtitle: [] as Array<{ content: string; topic: string[] }>,
});

// 弹窗显示状态
const showCopywritingMaterial = ref(false);
const showMaterialPop = ref(false);
const showPreviewVideo = ref(false);

// Refs
const copywritingMaterialRef = ref();
const materialPopRef = shallowRef<InstanceType<typeof MaterialPopup>>();
const previewVideoRef = ref();
const timeConfigScrollbarRef = ref<InstanceType<typeof ElScrollbar>>();
const timeConfigWrapperRef = ref<HTMLDivElement>();
const currentPublishFrequencyIdx = ref(0);
const currentTaskFrequencyIdx = ref(0);
const accountList = ref([]);

// 错误状态
const timeErrorIndex = ref([]);
//错误提示
const taskErrorMsg = ref<string>("");

// =================================================================================================
// Computed
// =================================================================================================

const isLastStep = computed(() => step.value === steps.length);
const isVideoMode = computed(() => type.value === PublishTaskTypeEnum.VIDEO);
const isImageMode = computed(() => type.value === PublishTaskTypeEnum.IMAGE);

// =================================================================================================
// Navigation
// =================================================================================================

const handleBack = () => {
    if (step.value === 1) {
        closePanel();
    } else {
        step.value--;
    }
};

const handleStep = (stepValue: number) => {
    if (stepValue > step.value) {
        handleNext();
    } else {
        step.value = stepValue;
    }
};

const handleNext = async () => {
    let success = false;
    if (step.value === 1) {
        success = await submitStep1();
    } else if (step.value === 2) {
        success = await submitStep2();
    } else if (isLastStep.value) {
        await submitForm();
        return; // 提交后直接返回，不进入下一步
    }

    if (success) {
        step.value++;
        replaceState({ ...route.query, step: step.value });
    }
};

const handleCancel = () => {
    nuxtApp.$confirm({
        message: "确定要取消创建吗？",
        onConfirm: closePanel,
    });
};

const closePanel = () => {
    emit("back");
    step.value = 1;
};

// =================================================================================================
// Step 1: 基本信息
// =================================================================================================

const submitStep1 = async () => {
    if (materialFormData.media_url.length === 0) {
        feedback.msgWarning("请选择素材");
        return false;
    }
    if (type.value === PublishTaskTypeEnum.IMAGE) {
        // 检查每一个图片组是否都有效
        if (
            materialFormData.media_url.some(
                (item) => !Array.isArray(item.url) || item.url.length === 0 || item.url.some((url) => !url)
            )
        ) {
            feedback.msgWarning("请为每个图片组添加有效的图片，或删除空的图片组");
            return false;
        }
    }

    return true;
};

// =================================================================================================
// Step 2: 文案选择
// =================================================================================================

const submitStep2 = async () => {
    if (materialFormData.title.length === 0 || materialFormData.title.every((item) => !item.content)) {
        feedback.msgWarning("请填写标题");
        return false;
    }

    if (materialFormData.subtitle.length === 0 || materialFormData.subtitle.every((item) => !item.content)) {
        feedback.msgWarning("请填写描述");
        return false;
    }
    initPublishJsonForStep3();
    return true;
};

// --- 素材选择 ---
const currMaterialGroupIndex = ref(0);
const currMaterialIndex = ref(-1); // -1 表示批量添加, >-1 表示替换

const materialPickerLimit = () => {
    if (isVideoMode.value) {
        return currMaterialIndex.value === -1 ? videoLimit : videoLimit - materialFormData.media_url.length;
    }
    if (isImageMode.value) {
        const group = materialFormData.media_url[currMaterialGroupIndex.value];
        return currMaterialGroupIndex.value === -1 ? maxImageCount : maxImageCount - (group?.url.length || 0);
    }
    return 0;
};

const isBatchPickingMaterial = computed(() => currMaterialIndex.value === -1);

const handleImportMaterial = async (event: any, groupIndex: number) => {
    currMaterialGroupIndex.value = groupIndex;
    currMaterialIndex.value = event.index > -1 ? event.index : -1;
    showMaterialPop.value = true;
    await nextTick();
    materialPopRef.value?.open();
};

const handleChooseMaterial = async (lists: any[]) => {
    if (isVideoMode.value) {
        if (isBatchPickingMaterial.value) {
            for (const item of lists) {
                const data = {
                    url: item.url,
                    pic: item.pic,
                };
                if (!item.pic) {
                    try {
                        const { file } = await getVideoFirstFrame(item.url);
                        const res = await uploadImage({ file });
                        data.pic = res.uri;
                    } catch (error) {}
                }

                materialFormData.media_url.push(data);
                addTitleAndDesc(item);
            }
        } else {
            materialFormData.media_url[currMaterialIndex.value] = lists[0];
        }
    } else if (isImageMode.value) {
        const group = materialFormData.media_url[currMaterialGroupIndex.value];
        if (isBatchPickingMaterial.value) {
            group.url.push(...lists);
        } else {
            group.url[currMaterialIndex.value] = lists[0];
        }
    }
    updateCopywritingMaterial(materialFormData);
};

const handleUpdateMaterialList = () => updateCopywritingMaterial(materialFormData);

const handleChangeMaterial = (data: any) => {
    if (data.type === MaterialActionType.ADD && isVideoMode.value) {
        addTitleAndDesc();
    }
};

const handleAddImageGroup = () => {
    materialFormData.media_url.push({ url: [], date: "" });
    addTitleAndDesc();
};

const handleDeleteMaterialGroup = (index: number) => {
    nuxtApp.$confirm({
        message: "确定要删除该素材组吗？",
        onConfirm: () => {
            materialFormData.media_url.splice(index, 1);
            updateCopywritingMaterial(materialFormData);
        },
    });
};

// --- 智能文案 ---
const openCopywritingMaterial = async () => {
    showCopywritingMaterial.value = true;
    await nextTick();
    copywritingMaterialRef.value.open();
};

const handleChooseCopywriting = (data: { titleList: any[]; contentList: any[] }) => {
    const { titleList, contentList } = data;
    const newTitles = [...titleList];
    const newContents = [...contentList];

    if (newTitles.length > 0) {
        for (const title of materialFormData.title) {
            if (!title.content && newTitles.length > 0) {
                const newTitle = newTitles.shift();
                if (newTitle) {
                    title.content = newTitle.content;
                }
            }
        }
        if (newTitles.length > 0) {
            materialFormData.title.push(...newTitles);
        }
    }

    if (newContents.length > 0) {
        for (const subtitle of materialFormData.subtitle) {
            if (!subtitle.content && newContents.length > 0) {
                const newContent = newContents.shift();
                if (newContent) {
                    subtitle.content = newContent.content;
                    subtitle.topic = newContent.topic || [];
                }
            }
        }
        if (newContents.length > 0) {
            materialFormData.subtitle.push(...newContents);
        }
    }

    if (titleList.length > 0 || contentList.length > 0) {
        updateCopywritingMaterial(materialFormData);
    }
};

// --- 标题与描述 ---
const addTitleAndDesc = (isUpdate = true) => {
    materialFormData.title.push({ content: "" });
    // 使用setTimeout确保在不同更新周期中执行，避免冲突
    setTimeout(() => materialFormData.subtitle.push({ content: "", topic: [] }), 100);
    if (isUpdate) updateCopywritingMaterial(materialFormData);
};

// =================================================================================================
// Step 3: 时间设置
// =================================================================================================
// 禁用当前日期之前的日期
const getDisabledTaskDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const validatePublishSettings = async () => {
    const { valid, errors } = validateTimeConfig();

    if (formData.accounts.length === 0) {
        feedback.msgWarning("请选择发布账号");
        return false;
    }
    if (!valid) {
        timeErrorIndex.value = errors;
        feedback.msgWarning("请检查时间配置");
        return false;
    } else {
        timeErrorIndex.value = [];
    }
    // 如果任务周期选择的是自定义，需要检查是不是有选择日期
    if (currentTaskFrequencyIdx.value === 5 && formData.custom_date.length === 0) {
        feedback.msgWarning("请选择任务日期");
        return false;
    }

    // 判断如果素材只有1条的时候，需要判断账号是不是有多个相同的平台的账号，如果有需要提示用户
    if (materialFormData.media_url.length === 1) {
        const typeCountMap = accountList.value.reduce<Record<string, number>>((acc, account) => {
            acc[account.type] = (acc[account.type] || 0) + 1;
            return acc;
        }, {});
        const duplicateTypes = Object.entries(typeCountMap)
            .filter(([, count]) => count > 1)
            .map(([type]) => type);
        if (duplicateTypes.length > 0) {
            const confirmed = await new Promise<boolean>((resolve) => {
                useNuxtApp().$confirm({
                    message: `当前素材只有1条，但您选择了多个相同平台的账号，将只选择一个账号发布内容，是否继续？`,
                    onConfirm: () => {
                        resolve(true);
                    },
                    onCancel: () => {
                        resolve(false);
                    },
                });
            });
            if (!confirmed) return false;
        }
    }

    return true;
};

const showPublishNumberPop = ref(false);
const tempCustomFreq = ref(1);
const customPublishFrep = ref<number | null>(null);
const openCustomFreqDialog = () => {
    tempCustomFreq.value = customPublishFrep.value || 1;
    showPublishNumberPop.value = true;
};

const handleConfirmCustomFreq = () => {
    const maxFrequency = Math.floor((24 * 60) / TIME_INTERVAL);
    if (tempCustomFreq.value < 1) {
        feedback.msgWarning("请输入有效的发布数量");
        return;
    }
    if (tempCustomFreq.value > maxFrequency) {
        feedback.msgWarning(`每日发布频率最高为${maxFrequency}次`);
        return;
    }
    currentPublishFrequencyIdx.value = 5;
    formData.publish_frep = tempCustomFreq.value;
    customPublishFrep.value = tempCustomFreq.value;
    showPublishNumberPop.value = false;
    changeTimeConfig();
};

const changeCustomDate = (e: any) => {
    if (!e) {
        currentTaskFrequencyIdx.value = 0;
        formData.custom_date = [];
    }
    changeTimeConfig();
};

// 核心：重新生成时间配置
const changeTimeConfig = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // 归一化到今天0点

    const generateTimesForDay = () => {
        return Array.from({ length: formData.publish_frep }, (_, i) => {
            const startMs = today.getTime() + i * TIME_INTERVAL * 60 * 1000;
            const endMs = startMs + TIME_INTERVAL * 60 * 1000;

            // 处理跨天逻辑：如果结束时间跨天，设为当天23:59
            const startDate = new Date(startMs);
            let endDate = new Date(endMs);
            if (endDate.getDate() !== startDate.getDate()) {
                endDate = new Date(startDate);
                endDate.setHours(23, 59, 59, 999);
            }

            return [dayjs(startDate).format("HH:mm"), dayjs(endDate).format("HH:mm")];
        });
    };

    if (currentTaskFrequencyIdx.value === 5 && formData.custom_date.length > 0) {
        formData.time_config = formData.custom_date.map((dateStr) => ({
            date: dateStr,
            times: generateTimesForDay(),
        }));
    } else {
        formData.time_config = Array.from({ length: formData.task_frep }, (_, i) => {
            const dateObj = new Date(today.getTime() + i * 24 * 60 * 60 * 1000);
            return {
                date: dayjs(dateObj).format("YYYY-MM-DD"),
                times: generateTimesForDay(),
            };
        });
    }
};

const validateTimeConfig = () => {
    const errors = [];
    let isAllValid = true;

    // 循环验证每个时间配置
    for (const [index, item] of formData.time_config.entries()) {
        const { valid, errorIndexes } = validateSchedule(item.times);

        if (!valid) {
            isAllValid = false;
            errors.push({
                configIndex: index,
                errorIndexes: errorIndexes,
            });
        }
    }
    // 返回验证结果和错误信息
    return {
        valid: isAllValid,
        errors: errors,
    };
};

const handlePublishFrequency = (item: number) => {
    if (item == formData.publish_frep) return;
    formData.publish_frep = item;
    currentPublishFrequencyIdx.value = 0;
    changeTimeConfig();
};

const handleTaskFrequency = (item: number) => {
    if (item == formData.task_frep) return;
    formData.task_frep = item;
    formData.custom_date = [];
    currentTaskFrequencyIdx.value = item;
    changeTimeConfig();
};

const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
    if (!(await validatePublishSettings())) return;
    try {
        const copywriterList = materialFormData.title
            .filter((item) => item.content)
            .map((item, index) => {
                const content = materialFormData.subtitle[index]?.content;
                const topic = materialFormData.subtitle[index]?.topic;
                return {
                    title: item.content,
                    content: content,
                    topic: topic,
                };
            });
        let media_url = [];
        if (type.value === PublishTaskTypeEnum.VIDEO) {
            media_url = materialFormData.media_url.map((item) => ({
                url: [item.pic, item.url],
            }));
        } else {
            media_url = materialFormData.media_url.map((item) => ({
                url: item.url.map((val) => val.url),
            }));
        }

        const { id } = await addMatrixTask({
            name: formData.name,
            media_url,
            copywriting: copywriterList,
            media_type: type.value,
        });

        const accountIds = accountList.value
            .filter((item) => formData.accounts.includes(item.account))
            .map((item) => ({ account: item.account, id: item.id, type: item.type }));
        await publishDeviceTask({
            name: formData.name,
            matrix_media_setting_id: id,
            time_config: formData.time_config.map((item: any) => ({
                date: item.date,
                times: item.times.map((time: any) => `${time[0]}-${time[1]}`),
            })),
            accounts: accountIds,
            publish_frep: formData.publish_frep,
            media_type: type.value,
            task_type: 3,
            scene: 2,
            data_type: 0,
            poi: formData.location,
        });
        feedback.msgSuccess("发布成功");
        router.push(`/app/matrix?type=${SidebarTypeEnum.ME_PUBLISH}`);
        setTimeout(() => {
            window.location.reload();
        }, 500);
    } catch (error) {
        taskErrorMsg.value = error;
        feedback.msgError(error);
    }
});

const initPublishJsonForStep3 = () => {
    getAccountList();
    changeTimeConfig();
};
// =================================================================================================
// Other Actions
// =================================================================================================

const getAccountList = async () => {
    const { lists } = await getPublishAccountList({ page_size: 999 });
    accountList.value = lists;
};
initPublishJsonForStep3();

const handlePreviewVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};
</script>

<style scoped lang="scss">
:deep(.el-button.is-disabled) {
    background-color: #f9fafb !important;
    border-color: #f3f4f6 !important;
    color: #d1d5db !important;
}

.group:active .w-9 {
    transform: scale(0.9);
}

.content-item {
    @apply rounded-xl border border-[var(--el-border-color)] py-[14px] flex flex-col grow min-h-0 flex-1;

    :deep(.el-input__inner::placeholder) {
        font-size: 10px;
    }
}

.account-select.el-select :deep(.el-select__wrapper) {
    border-radius: var(--el-input-border-radius, var(--el-border-radius-base));
}
:deep() {
    .el-date-editor.el-input__wrapper.time-error {
        box-shadow: 0 0 0 1px var(--el-color-error);
    }
}

.item-label {
    @apply flex items-center text-[13px] font-black text-slate-500 uppercase tracking-wider mb-3 pl-1;
    .el-icon {
        @apply text-[#0065fb]/60;
    }
}

.freq-card {
    @apply flex flex-col items-center justify-center h-14 bg-white border border-slate-100 rounded-xl cursor-pointer transition-all;
    &:hover {
        @apply border-primary bg-[#0065fb]/5 -translate-y-0.5;
    }

    &.is-custom {
        @apply flex-row gap-1.5 bg-slate-50 text-slate-500 border-dashed border-slate-200;
    }
    &.is-active {
        @apply bg-primary border-primary text-white shadow-[#0065fb]/20;
        span {
            @apply text-white;
        }
    }
}

.time-config-card {
    @apply bg-[#FBFCFE] border border-slate-100 rounded-[22px] p-5 transition-all;
    &:hover {
        @apply border-[#0065fb]/20;
    }
    &.has-error {
        @apply border-red-500;
    }
}

.bg-dashed {
    background-image: linear-gradient(to bottom, #0065fb 50%, transparent 50%);
    background-size: 1px 6px;
}

.error-notice {
    @apply mt-4 p-3 bg-red-50 text-red-500 rounded-xl text-xs font-medium flex items-center gap-2 border border-red-100;
}

.modern-btn-secondary {
    @apply rounded-xl bg-white border-slate-100 text-slate-500 font-black text-[13px] hover:bg-slate-50 hover:text-slate-700 transition-all;
}

.modern-btn-primary {
    @apply rounded-xl bg-primary border-none text-white font-black text-[13px] shadow-[#0065fb]/20 hover:shadow-[#0065fb]/30 transition-all;
}

.custom-number-step :deep(.el-input__wrapper) {
    @apply bg-[transparent] shadow-[none];
}

:deep(.modern-freq-popup) {
    @apply rounded-[28px] overflow-hidden;
    .el-dialog__header {
        @apply hidden;
    }
}
</style>
<style lang="scss">
.modern-time-picker {
    &.has-error {
        box-shadow: 0 0 0 1px var(--el-color-error) !important;
    }
}
</style>
