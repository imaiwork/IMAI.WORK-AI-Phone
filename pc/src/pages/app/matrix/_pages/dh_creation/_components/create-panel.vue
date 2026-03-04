<template>
    <VoiceDefineTemplate>
        <div class="flex flex-col gap-y-3 p-1">
            <div
                class="group flex items-center gap-x-4 p-3 rounded-xl border border-[transparent] hover:border-[#0065fb]/20 hover:bg-slate-50 transition-all cursor-pointer"
                @click="handleSelectVoice">
                <div
                    class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#EEF2FF] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                    <Icon name="local-icon-windows" :size="20"></Icon>
                </div>
                <div class="flex flex-col">
                    <span class="text-[13px] font-black text-[#1E293B]">选择已有音色</span>
                    <span class="text-[11px] text-[#94A3B8]">从您的个人音色库中快速导入</span>
                </div>
            </div>

            <div
                v-if="formData.model_version == DigitalHumanModelVersionEnum.CHANJING"
                class="h-[1px] bg-[#F1F5F9] mx-2"></div>

            <upload
                v-if="formData.model_version == DigitalHumanModelVersionEnum.CHANJING"
                class="w-full"
                show-progress
                type="audio"
                accept=".mp3,.wav"
                :limit="1"
                :show-file-list="false"
                @success="getUploadVoiceSuccess">
                <div
                    class="group flex items-center gap-x-4 p-3 rounded-xl border border-transparent hover:border-[#0065fb]/20 hover:bg-slate-50 transition-all cursor-pointer text-left">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#F0FDF4] flex items-center justify-center text-[#10B981] group-hover:bg-[#10B981] group-hover:text-white transition-colors">
                        <Icon name="local-icon-upload" :size="20"></Icon>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[13px] font-black text-[#1E293B]">本地上传音频</span>
                        <span class="text-[11px] text-[#94A3B8]">支持 mp3/wav 格式的录音文件</span>
                    </div>
                </div>
            </upload>
        </div>
    </VoiceDefineTemplate>
    <div class="h-full rounded-[20px] min-w-[1000px]">
        <div class="h-full bg-slate-50 rounded-[24px] overflow-hidden flex flex-col">
            <div
                class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] bg-white border-b border-[#F1F5F9] z-10">
                <div class="flex items-center gap-6">
                    <div
                        class="group flex items-center gap-2 cursor-pointer transition-all hover:translate-x-[-4px]"
                        @click="handleBack">
                        <div
                            class="w-8 h-8 rounded-full bg-[#F9FAFB] flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                            <Icon name="el-icon-ArrowLeft" :size="14"></Icon>
                        </div>
                        <span class="text-sm font-medium text-[#64748B] group-hover:text-[#1E293B]">返回列表</span>
                    </div>
                    <div class="w-[1px] h-6 bg-[#E2E8F0]"></div>
                    <h2 class="text-base font-black text-[#1E293B]">
                        {{ formData.id ? "编辑任务" : "创建批量数字人任务" }}
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <ElButton
                        class="!rounded-xl !h-10 !px-8 !border-br !bg-white !text-[#475569] font-medium hover:!bg-slate-50"
                        @click="handleCancel"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!rounded-xl !h-10 !px-8 font-medium shadow-[#0065fb]/20 active:scale-95 transition-all"
                        :loading="isCreateLock"
                        @click="handleCreateLockFn(CreateType.Create)">
                        {{ formData.id ? "更新任务" : "开始生成" }}
                    </ElButton>
                </div>
            </div>

            <div class="grow min-h-0 flex flex-col p-6 overflow-hidden" v-loading="loading">
                <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-2xl border border-[#F1F5F9]">
                    <div class="flex items-center gap-x-4">
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] font-black text-[#64748B] uppercase tracking-wider">任务名称</span>
                            <ElInput
                                v-model="formData.name"
                                placeholder="请输入任务名称"
                                clearable
                                class="custom-form-input !w-[320px]"
                                maxlength="30"
                                show-word-limit
                                @blur="handleUpdateCreateTask()" />
                        </div>
                    </div>
                    <ElTooltip placement="left">
                        <div
                            class="flex items-center gap-x-2 px-3 py-1.5 bg-slate-50 rounded-lg border border-br cursor-help">
                            <span class="text-xs font-medium text-[#64748B]">扣费规则</span>
                            <Icon name="local-icon-tips2" :size="14"></Icon>
                        </div>
                        <template #content>
                            <div class="text-xs leading-6 p-1">
                                1、若选择原视频音色，音色数量将按照视频数量进行扣费<br />
                                2、若视频生成失败而音色成功，将扣除音色费用，退回视频费用<br />
                                3、按照每个视频对应的时长收取合成费用
                            </div>
                        </template>
                    </ElTooltip>
                </div>

                <div class="grow min-h-0 flex gap-x-6">
                    <div class="content-card flex-1">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-[#10B981]"></div>
                                <span class="title">形象库</span>
                            </div>
                            <span class="subtitle">需上传封面素材</span>
                        </div>
                        <div class="grow min-h-0 p-4">
                            <ElScrollbar>
                                <MaterialPicker
                                    v-model:material-list="formData.anchor"
                                    :type="1"
                                    :max-video-count="30"
                                    @update:material-list="handleUpdateCreateTask()"
                                    @preview-video="handlePreviewVideo"
                                    @import-material="handleImportMaterial"
                                    @change-material="handleChangeMaterial" />
                            </ElScrollbar>
                        </div>
                    </div>

                    <div class="content-card flex-1">
                        <div class="card-header flex justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-primary"></div>
                                <span class="title">口播文案</span>
                            </div>
                            <div class="flex gap-2">
                                <ElButton
                                    link
                                    type="primary"
                                    class="!text-xs font-medium"
                                    @click="handleCopywriting('fill')"
                                    >文案库填充</ElButton
                                >
                                <ElButton
                                    link
                                    type="primary"
                                    class="!text-xs font-medium"
                                    @click="handleCopywriting('add')"
                                    >新增</ElButton
                                >
                            </div>
                        </div>
                        <div class="grow min-h-0 p-4">
                            <ElScrollbar>
                                <div class="flex flex-col gap-y-4">
                                    <div
                                        v-for="(item, index) in formData.copywriting"
                                        :key="index"
                                        class="group border border-[#F1F5F9] bg-slate-50 p-4 rounded-[20px] transition-all hover:border-[#0065fb]/30 hover:bg-white">
                                        <div class="flex justify-between items-center mb-3">
                                            <span
                                                class="w-6 h-6 rounded-full bg-white flex items-center justify-center text-[11px] font-black text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                                                {{ index + 1 }}
                                            </span>
                                            <div
                                                class="w-5 h-5 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity"
                                                @click="handleCopywritingDelete(index)">
                                                <close-btn :icon-size="10"></close-btn>
                                            </div>
                                        </div>
                                        <ElInput
                                            v-model="item.content"
                                            type="textarea"
                                            :rows="4"
                                            resize="none"
                                            placeholder="请输入口播内容..."
                                            class="custom-textarea"
                                            @blur="handleUpdateCreateTask()" />
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>

                    <div class="content-card flex-1">
                        <div class="card-header flex justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-[#F59E0B]"></div>
                                <span class="title">生成设置</span>
                            </div>
                            <ElButton
                                link
                                type="primary"
                                class="!text-xs font-medium"
                                @click="handleOpenAdvancedSetting()"
                                v-if="clipConfig.is_open"
                                >高级参数</ElButton
                            >
                        </div>

                        <div class="grow min-h-0 p-5 space-y-4">
                            <div class="space-y-3">
                                <label class="text-xs font-black text-[#64748B] uppercase tracking-wider ml-1"
                                    >训练通道</label
                                >
                                <ElSelect
                                    :model-value="formData.model_version"
                                    class="custom-form-select w-full"
                                    :show-arrow="false"
                                    @change="handleChangeModelVersion">
                                    <ElOption
                                        v-for="item in getModelChannel"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id" />
                                </ElSelect>
                            </div>
                            <div class="mt-2">
                                <div class="text-xs font-black text-[#64748B] uppercase tracking-wider ml-1">
                                    音色设置
                                </div>
                                <div class="flex items-center gap-x-[30px] mt-[18px]">
                                    <div
                                        v-for="item in voiceType"
                                        :key="item.value"
                                        class="flex items-center gap-x-2 cursor-pointer"
                                        @click="handleVoiceType(item.value)">
                                        <div
                                            class="w-4 h-4 rounded-full shadow-[0_0_0_1px_rgba(255,255,255,0.1)] p-[4px]">
                                            <div
                                                v-if="item.value == formData.extra.currentVoiceType"
                                                class="w-full h-full rounded-full bg-primary"></div>
                                        </div>
                                        <div class="text-[11px]">
                                            {{ item.label }}
                                        </div>
                                        <ElTooltip
                                            placement="top"
                                            popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2"
                                            :show-arrow="false">
                                            <div
                                                class="w-4 h-4 rounded-full flex items-center justify-center shadow-[0_0_0_1px_rgba(255,255,255,0.2)] cursor-pointer">
                                                <Icon name="local-icon-tips2" :size="16"></Icon>
                                            </div>
                                            <template #content>
                                                <div class="p-1 space-y-1 w-[212px]" v-html="item.tips"></div>
                                            </template>
                                        </ElTooltip>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="formData.extra.currentVoiceType == VoiceType.Custom"
                                class="grow min-h-0 flex flex-col overflow-hidden">
                                <div class="grow min-h-0 mb-4" v-if="formData.voice.length > 0">
                                    <ElScrollbar>
                                        <div class="px-3 flex flex-col gap-y-3">
                                            <div v-for="(item, index) in formData.voice" :key="index">
                                                <ElPopover
                                                    trigger="click"
                                                    width="220"
                                                    popper-class="!rounded-2xl !bg-white !shadow-xl !border-[#F1F5F9] !p-2"
                                                    :show-arrow="false">
                                                    <template #reference>
                                                        <div
                                                            class="group h-12 px-4 rounded-xl flex items-center justify-between gap-x-3 cursor-pointer border border-[#F1F5F9] bg-white transition-all hover:border-[#0065fb]/40 hover:"
                                                            @click="handleClickVoice(index)">
                                                            <div
                                                                class="flex-1 flex items-center gap-x-2 overflow-hidden">
                                                                <div
                                                                    class="w-1.5 h-1.5 rounded-full bg-[#0065fb]/40 group-hover:bg-primary"></div>
                                                                <span
                                                                    class="text-[13px] font-medium text-[#1E293B] truncate break-all">
                                                                    {{ item.name }}
                                                                </span>
                                                            </div>

                                                            <div class="flex items-center gap-x-3 flex-shrink-0">
                                                                <span
                                                                    class="text-[10px] px-1.5 py-0.5 rounded-md font-black uppercase tracking-tighter transition-colors"
                                                                    :class="[
                                                                        item.voice_id
                                                                            ? 'bg-[#ECFDF5] text-[#10B981]'
                                                                            : 'bg-[#F1F5F9] text-[#94A3B8]',
                                                                    ]">
                                                                    {{ item.voice_id ? "已训练" : "未训练" }}
                                                                </span>

                                                                <div
                                                                    class="flex items-center gap-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                    <div class="w-[1px] h-3 bg-[#E2E8F0]"></div>
                                                                    <div
                                                                        class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-[#FEE2E2] hover:text-[#EF4444] transition-colors"
                                                                        @click.stop="handleDeleteVoice(index)">
                                                                        <close-btn :icon-size="8"></close-btn>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <VoiceUseTemplate />
                                                </ElPopover>
                                            </div>
                                        </div>
                                    </ElScrollbar>
                                </div>
                                <ElPopover
                                    trigger="click"
                                    width="280"
                                    popper-class="!rounded-2xl !bg-white !shadow-xl !border-[#F1F5F9] !p-2"
                                    :show-arrow="false">
                                    <template #reference>
                                        <div
                                            class="w-full h-12 rounded-xl border-2 border-dashed border-br flex items-center justify-center gap-x-2 text-[#64748B] hover:border-[#0065fb] hover:text-primary hover:bg-slate-50 transition-all cursor-pointer group active:scale-[0.98]"
                                            @click="handleAddVoice">
                                            <Icon name="local-icon-upload3"></Icon>
                                            <span class="text-[13px] font-black">添加音色库</span>
                                        </div>
                                    </template>
                                    <VoiceUseTemplate />
                                </ElPopover>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <advanced-setting
        v-if="showAdvancedSetting"
        ref="advancedSettingRef"
        @close="showAdvancedSetting = false"
        @success="handleAdvancedSettingSuccess" />
    <kb-copywriting-material
        v-if="showKbCopywritingMaterial"
        ref="kbCopywritingMaterialRef"
        @close="showKbCopywritingMaterial = false"
        @confirm="handleChooseCopywriting" />
    <material-popup
        v-if="showVideoMaterial"
        ref="materialPopupRef"
        :type="MaterialTypeEnum.VIDEO"
        :show-tab="false"
        :multiple="replaceMaterialIndex == -1"
        @close="showVideoMaterial = false"
        @confirm="getChooseVideo" />
    <voice-material
        v-if="showVoiceMaterial"
        ref="voiceMaterialRef"
        :is_show_original="false"
        :multiple="toneIsMultiple"
        @close="showVoiceMaterial = false"
        @confirm="getChooseTone" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { uploadImage, getClipConfig } from "@/api/app";
import { getDigitalHumanDetail, addDigitalHuman, updateDigitalHuman } from "@/api/matrix";
import { ThemeEnum, AppTypeEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { DigitalHumanModelVersionEnum } from "@/pages/app/digital_human/_enums";
import { uploadLimit } from "@/pages/app/digital_human/_config";
import VoiceMaterial from "@/pages/app/_components/choose-tone.vue";
import MaterialPicker from "../../../_components/material-picker.vue";
import KbCopywritingMaterial from "../../../_components/kb-copywriting-material.vue";
import MaterialPopup from "../../../_components/material-popup.vue";
import AdvancedSetting from "./advanced-setting.vue";
import { MaterialTypeEnum, MaterialActionType, SidebarTypeEnum } from "../../../_enums";

const emit = defineEmits(["back"]);

const route = useRoute();

const appStore = useAppStore();
const userStore = useUserStore();

const { userTokens } = toRefs(userStore);

interface RedbookCreationFormData {
    id?: string;
    type: AppTypeEnum;
    name: string;
    status: 0 | 1 | 2 | 3 | 4 | 5; //状态-0草稿箱,1待处理,2生成中,3已完成,4失败,5部分完成
    speed: number;
    anchor: any[];
    voice: any[];
    copywriting: any[];
    pic: string;
    extra: Record<string, any>;
    automatic_clip: number | string;
    music: Array<{
        url: string;
        name: string;
    }>;
    clip: Array<{
        type: number | string;
    }>;
    music_type: Array<{
        type: number | string;
    }>;
    model_version: DigitalHumanModelVersionEnum;
}

enum VoiceType {
    Custom = "custom",
    Original = "original",
}

// 获取模型通道
const getModelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        const modelChannel = channel.filter((item) => {
            item.id = parseInt(item.id);
            if (
                item.status == 1 &&
                [DigitalHumanModelVersionEnum.CHANJING, DigitalHumanModelVersionEnum.ADVANCED].includes(item.id)
            ) {
                return item;
            }
        });
        if (modelChannel.length > 0) {
            formData.model_version = modelChannel[0].id;
            return modelChannel;
        }
        return [];
    }
    return [];
});

const formData = reactive<RedbookCreationFormData>({
    id: "",
    type: AppTypeEnum.XHS,
    name: "数字人任务" + " " + dayjs().format("YYYYMMDDHHmmss").substring(2),
    status: 0,
    speed: 1,
    anchor: [],
    voice: [],
    copywriting: [],
    pic: "",
    extra: {
        currentVoiceType: VoiceType.Custom,
    },
    automatic_clip: 0,
    music: [],
    clip: [],
    music_type: [{ type: 1 }],
    model_version: DigitalHumanModelVersionEnum.ADVANCED,
});

const handleBack = () => {
    emit("back");
};

const handleCancel = () => {
    useNuxtApp().$confirm({
        message: "确定要取消创建吗？",
        onConfirm: () => {
            handleBack();
        },
    });
};

enum CreateType {
    Create = "create",
    Publish = "publish",
}

// 选择形象 Start

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();

const videoUploadParams = computed(() => {
    if (formData.model_version) {
        return uploadLimit[formData.model_version];
    }
    return {};
});

const handlePreviewVideo = async (uri: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value.open();
    previewVideoRef.value.setUrl(uri);
};

const showVideoMaterial = ref(false);
const materialPopupRef = shallowRef<InstanceType<typeof MaterialPopup>>();

const handleChangeMaterial = (data: any) => {
    const { type } = data;
    if (type == MaterialActionType.ADD) {
        handleCopywriting("add");
    }
};

const replaceMaterialIndex = ref(-1);
const handleImportMaterial = async (data: any) => {
    const { type, index } = data;
    if (type == MaterialActionType.REPLACE) {
        replaceMaterialIndex.value = index;
    }
    showVideoMaterial.value = true;
    await nextTick();
    materialPopupRef.value.open();
};

const getChooseVideo = async (lists: any[]) => {
    const validatePromises = lists.map((item) => {
        return new Promise<string | null>((resolve) => {
            const video = document.createElement("video");
            video.src = item.url;
            video.muted = true;
            video.playsInline = true;
            video.preload = "auto";
            video.crossOrigin = "anonymous";
            video.addEventListener("loadedmetadata", () => {
                const { videoWidth, duration } = video;
                const { minResolution, maxResolution, videoMinDuration, videoMaxDuration } = videoUploadParams.value;
                const isResolutionValid = videoWidth >= minResolution && videoWidth <= maxResolution;
                const isDurationValid = duration >= videoMinDuration && duration <= videoMaxDuration;
                if (!isResolutionValid) {
                    feedback.msgError(`选择的视频分辨率不能满足${minResolution}*${maxResolution}`);
                    resolve(null);
                } else if (!isDurationValid) {
                    feedback.msgError(`选择的视频时长不能小于${videoMinDuration}秒或大于${videoMaxDuration}秒`);
                    resolve(null);
                } else {
                    resolve(item.url);
                }
            });
            video.addEventListener("error", () => {
                feedback.msgError(`视频加载失败`);
                resolve(null);
            });
        });
    });

    const validLists = (await Promise.all(validatePromises)).filter(Boolean) as string[];
    if (validLists.length > 0) {
        if (replaceMaterialIndex.value == -1) {
            formData.anchor.push(...validLists.map((item) => ({ url: item })));
            validLists.forEach(() => {
                handleCopywriting("add", false);
            });
        } else {
            formData.anchor[replaceMaterialIndex.value] = { url: validLists[0] };
        }
        replaceMaterialIndex.value = -1;
        handleUpdateCreateTask();
    }
};

// 选择形象 End

// 文案设置 Start

const showKbCopywritingMaterial = ref(false);
const kbCopywritingMaterialRef = ref();

const getCopywritingCount = computed(() => {
    return formData.copywriting.filter((item) => item.content).length;
});

const handleCopywriting = async (type: string, isUpdate: boolean = false) => {
    if (type == "fill") {
        showKbCopywritingMaterial.value = true;
        await nextTick();
        kbCopywritingMaterialRef.value.open();
    }
    if (type == "add") {
        formData.copywriting.push({ content: "" });
        if (isUpdate) {
            handleUpdateCreateTask();
        }
    }
};

const handleCopywritingDelete = (index: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除吗？",
        onConfirm: () => {
            formData.copywriting.splice(index, 1);
            handleUpdateCreateTask();
        },
    });
};

const handleChooseCopywriting = (data: any) => {
    const { lists } = data;
    if (lists.length > 0) {
        formData.copywriting.push(...lists);
        handleUpdateCreateTask();
    }
};

// 文案设置 End

// 视频设置 Start

const voiceType = computed(() => {
    const types = [
        {
            label: "自选音色",
            value: VoiceType.Custom,
            tips: `1.若所选音色为系统内已克隆音色，则无需额外扣费。 <br />2.若上传新的音色以进行克隆，则将根据所选音色数量扣除相应费用。`,
        },
    ];
    if (formData.model_version != DigitalHumanModelVersionEnum.ADVANCED) {
        types.push({
            label: "原视频音色",
            value: VoiceType.Original,
            tips: `1.当选择原视频音色时，系统将对当前视频中的音色进行克隆并进行扣费，且在合成视频时保持原有音色的一致性。`,
        });
    }
    return types;
});
const currentVoiceIndex = ref();
const showVoiceMaterial = ref(false);
const voiceMaterialRef = shallowRef<InstanceType<typeof VoiceMaterial>>();
const toneIsMultiple = ref(true);

const clipConfig = reactive({
    is_open: false,
});

const getClipConfigData = async () => {
    const { code } = await getClipConfig();
    clipConfig.is_open = code == 10000;
};

const handleChangeModelVersion = (value: number) => {
    useNuxtApp().$confirm({
        message: "切换模型将会清空形象、音色数据，是否继续？",
        onConfirm: () => {
            formData.model_version = value;
            formData.anchor.length = 0;
            formData.voice.length = 0;
            formData.extra.currentVoiceType = VoiceType.Custom;
            handleUpdateCreateTask();
        },
    });
};

const handleVoiceType = (value: VoiceType) => {
    formData.extra.currentVoiceType = value;
    handleUpdateCreateTask();
};

const handleAddVoice = () => {
    toneIsMultiple.value = true;
};

const handleSelectVoice = async () => {
    showVoiceMaterial.value = true;
    await nextTick();
    voiceMaterialRef.value.open(formData.model_version);
};

const handleClickVoice = (index: number) => {
    currentVoiceIndex.value = index;
    toneIsMultiple.value = false;
};

const getUploadVoiceSuccess = (result: any) => {
    const { uri, name } = result.data;
    const data = {
        voice_urls: uri,
        name: name.split(".")[0],
        model_version: formData.model_version,
        voice_id: "",
    };
    if (currentVoiceIndex.value > -1) {
        formData.voice[currentVoiceIndex.value] = data;
    } else {
        formData.voice.push(data);
    }
    currentVoiceIndex.value = -1;
    handleUpdateCreateTask();
};

const getChooseTone = (result: any) => {
    if (!toneIsMultiple.value) {
        const { voice_id, name, builtin } = result;
        const data = {
            voice_id,
            name,
            model_version: formData.model_version,
            voice_urls: "",
            voice_type: builtin,
        };
        if (currentVoiceIndex.value > -1) {
            formData.voice[currentVoiceIndex.value] = data;
        } else {
            formData.voice.push(data);
        }
    } else {
        const voiceList = result.map((item: any) => {
            const data = {
                voice_id: item.voice_id,
                name: item.name,
                model_version: formData.model_version,
                voice_urls: "",
                voice_type: item.builtin,
            };
            return data;
        });
        formData.voice.push(...voiceList);
    }
    currentVoiceIndex.value = -1;
    handleUpdateCreateTask();
};

const handleDeleteVoice = (index: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除吗？",
        onConfirm: () => {
            formData.voice.splice(index, 1);
            handleUpdateCreateTask();
        },
    });
};

const showAdvancedSetting = ref(false);
const advancedSettingRef = shallowRef<InstanceType<typeof AdvancedSetting>>();

const handleOpenAdvancedSetting = async () => {
    showAdvancedSetting.value = true;
    await nextTick();
    advancedSettingRef.value?.open();
    advancedSettingRef.value.setFormData(formData);
};

const handleAdvancedSettingSuccess = (result: any) => {
    showAdvancedSetting.value = false;
    formData.music = result.music;
    formData.clip = result.clip;
    formData.automatic_clip = result.automatic_clip;
    handleUpdateCreateTask();
};

const handleUpdateCreateTask = async () => {
    return new Promise<any>(async (resolve, reject) => {
        const { id, name, anchor, voice, pic }: any = formData;
        if (!id || !name) return;
        if (anchor.length && !pic) {
            if (anchor[0]) {
                const getVideoPicFn = async (): Promise<string> => {
                    return new Promise(async (resolve, reject) => {
                        getVideoFirstFrame(anchor[0].url).then(({ file }) => {
                            if (file) {
                                uploadImage({
                                    file,
                                }).then((res) => {
                                    resolve(res.uri);
                                });
                            } else {
                                reject(new Error("获取视频封面失败"));
                            }
                        });
                    });
                };
                const pic = await getVideoPicFn();
                formData.pic = pic;
            }
        } else if (anchor.length == 0) {
            formData.pic = "";
        }
        await updateDigitalHuman({
            ...formData,
            extra: JSON.stringify(formData.extra),
            anchor: anchor.map((item) => ({
                model_version: formData.model_version,
                anchor_url: item.url,
                name: item.url.slice(item.url.lastIndexOf("/") + 1),
            })),
            voice:
                VoiceType.Custom == formData.extra.currentVoiceType
                    ? voice.map((item) => ({
                          model_version: formData.model_version,
                          ...item,
                      }))
                    : [],
        })
            .then((res) => {
                resolve(res);
            })
            .catch((err) => {
                reject(err);
            });
    });
};

const handleCreate = async (type: CreateType) => {
    const { name, anchor, copywriting, voice } = formData;
    if (!name) {
        feedback.msgWarning("请输入任务名称");
        return;
    } else if (anchor.length == 0) {
        feedback.msgWarning("请添加形象素材");
        return;
    } else if (copywriting.length == 0) {
        feedback.msgWarning("请添加文案");
        return;
    } else if (copywriting.length == 1 && copywriting[0].content.length == 0) {
        feedback.msgWarning("文案不能为空");
        return;
    } else if (VoiceType.Custom == formData.extra.currentVoiceType && voice.length == 0) {
        feedback.msgWarning("请添加音色");
        return;
    } else if (userTokens.value == 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        // 过滤copywriting
        formData.copywriting = formData.copywriting.filter((item) => item.content);
        // 更改创建状态为待处理
        formData.status = 1;
        const data = await handleUpdateCreateTask();
        if (type == CreateType.Create) {
            emit("back");
        }
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn: handleCreateLockFn, isLock: isCreateLock } = useLockFn(handleCreate);

const { DefineTemplate: VoiceDefineTemplate, UseTemplate: VoiceUseTemplate } = useTemplate();

// 视频设置 End
const loading = ref(false);
const createEmptyTask = async () => {
    return new Promise(async (resolve, reject) => {
        try {
            loading.value = true;
            const data = await addDigitalHuman({
                ...formData,
                extra: JSON.stringify(formData.extra),
            });
            getTaskDetail(data.id);
            replaceState({
                ...route.query,
                create_id: data.id,
            });
            resolve(data);
        } catch (error) {
            reject(error);
        } finally {
            loading.value = false;
        }
    });
};

const getTaskDetail = async (id: string) => {
    if (!id) return;
    try {
        loading.value = true;
        const data = await getDigitalHumanDetail({ id });
        setFormData(data, formData);
        formData.anchor = data.anchor.map((item) => ({ url: item.anchor_url }));
        formData.extra = isJson(data.extra) ? JSON.parse(data.extra) : { currentVoiceType: VoiceType.Custom };
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    getClipConfigData();
    const query = searchQueryToObject();
    await getTaskDetail(query.create_id as string);
});

defineExpose({
    createEmptyTask,
    getTaskDetail,
});
</script>

<style scoped lang="scss">
// .content-item {
//     @apply rounded-xl bg-app-bg-3 py-[14px] border border-app-border-1 flex flex-col grow min-h-0 flex-1;
//     // :deep(.el-select__wrapper) {
//     //     background-color: var(--app-bg-color-1) !important;
//     // }
//     :deep(.el-input) {
//         .el-input__wrapper {
//             background-color: transparent !important;
//             box-shadow: none !important;
//         }
//     }
// }
.content-card {
    @apply bg-white rounded-[24px] border border-[#F1F5F9]  flex flex-col overflow-hidden transition-all;
    &:hover {
        @apply border-br;
    }
}

.card-header {
    @apply px-5 py-4 bg-slate-50 border-b border-[#F1F5F9] items-center;
    .title {
        @apply text-[14px] font-black text-[#1E293B] tracking-tight;
    }
    .subtitle {
        @apply text-[11px] text-[#94A3B8] ml-2 font-medium;
    }
}

:deep(.custom-form-input) {
    .el-input__wrapper {
        background-color: #f8fafc;
        box-shadow: none !important;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        height: 44px;
        &.is-focus {
            border-color: #4f46e5;
            background-color: white;
        }
    }
}

:deep(.custom-form-select) {
    .el-select__wrapper {
        background-color: #f8fafc;
        box-shadow: none !important;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        height: 48px;
    }
}

:deep(.el-loading-mask) {
    background-color: rgba(248, 250, 252, 0.8);
    backdrop-filter: blur(4px);
}
</style>
