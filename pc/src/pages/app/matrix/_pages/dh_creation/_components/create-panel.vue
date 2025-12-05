<template>
    <div class="h-full bg-app-bg-2 rounded-[20px] overflow-x-auto dynamic-scroller">
        <div class="h-full flex flex-col min-w-[1000px]">
            <div
                class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-[0] border-b-[1px] border-[#ffffff1a]">
                <div class="flex items-center gap-2 cursor-pointer" @click="handleBack">
                    <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                    <div class="text-white">返回上一步</div>
                </div>
                <div class="flex items-center gap-1">
                    <ElButton
                        class="!rounded-full !h-10 w-[98px] !border-app-border-2"
                        color="#181818"
                        @click="handleCancel"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!rounded-full !h-10 w-[98px]"
                        :loading="isCreateLock"
                        @click="handleCreateLockFn(CreateType.Create)">
                        {{ formData.id ? "更新" : "创建" }}
                    </ElButton>
                </div>
            </div>
            <div class="grow min-h-0 flex flex-col p-5" v-loading="loading">
                <div class="flex justify-between items-center gap-x-2 flex-shrink-0">
                    <div class="flex items-center gap-x-2">
                        <div class="text-white whitespace-nowrap">任务名称</div>
                        <ElInput
                            v-model="formData.name"
                            placeholder="请输入任务名称"
                            clearable
                            class="!h-11 !w-[240px]"
                            maxlength="30"
                            show-word-limit
                            @blur="handleUpdateCreateTask()" />
                    </div>
                    <div>
                        <ElTooltip placement="left">
                            <div class="flex items-center gap-x-2">
                                <div class="text-white">扣费规则</div>
                                <div
                                    class="w-4 h-4 rounded-full flex items-center justify-center shadow-[0_0_0_1px_rgba(255,255,255,0.2)] cursor-pointer">
                                    <Icon name="local-icon-tips2" color="#ffffff" :size="16"></Icon>
                                </div>
                            </div>
                            <template #content>
                                <div class="text-[#ffffff80] text-[11px] leading-6">
                                    <div>
                                        1、若选择原视频音色，音色数量将按照视频数量进行扣费
                                        <br />
                                        2、若视频生成视频，而音色生成成功，将扣除音色费用，退回视频合成费用
                                        <br />
                                        3、在合成时将按照每个不同视频对应的时常收取合成费用
                                    </div>
                                </div>
                            </template>
                        </ElTooltip>
                    </div>
                </div>
                <div class="grow min-h-0 flex gap-x-[18px] mt-5">
                    <div class="content-item">
                        <div class="px-[14px]">
                            <div class="text-white font-bold">形象列表</div>
                            <div class="text-[#ffffff80] text-[11px] mt-2">
                                请确保上传一个形象素材，作为视频封面，否则列表将无法正常显示
                            </div>
                        </div>
                        <div class="grow min-h-0 mt-[14px]">
                            <ElScrollbar>
                                <div class="px-[14px]">
                                    <MaterialPicker
                                        v-model:material-list="formData.anchor"
                                        :type="1"
                                        :max-video-count="30"
                                        :max-size="videoUploadParams.size"
                                        :video-min-duration="videoUploadParams.videoMinDuration"
                                        :video-max-duration="videoUploadParams.videoMaxDuration"
                                        :video-min-resolution="videoUploadParams.minResolution"
                                        :video-max-resolution="videoUploadParams.maxResolution"
                                        @preview-video="handlePreviewVideo"
                                        @update:material-list="handleUpdateCreateTask()"
                                        @import-material="handleImportMaterial"
                                        @change-material="handleChangeMaterial" />
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                    <div class="content-item">
                        <div class="px-[14px]">
                            <div class="text-white font-bold">文案设置</div>
                            <div class="flex items-center gap-x-2 mt-2">
                                <div class="text-[#ffffff80] text-[11px]">
                                    口播文案（共{{ formData.copywriting.length }}个条，已配置{{
                                        getCopywritingCount
                                    }}条）
                                </div>
                                <div>
                                    <ElTooltip
                                        placement="top"
                                        popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2"
                                        :show-arrow="false">
                                        <div
                                            class="w-4 h-4 rounded-full flex items-center justify-center shadow-[0_0_0_1px_rgba(255,255,255,0.2)] cursor-pointer">
                                            <Icon name="local-icon-tips2" color="#ffffff" :size="16"></Icon>
                                        </div>
                                        <template #content>
                                            <div class="text-[#ffffff80] text-[11px] leading-6 w-[212px]">
                                                1.如配置口播文案等于形象数量，将按照形象顺序匹配标题。
                                                <br />
                                                2.如配置标题数量不等于形象数量，将口播文案随机匹配给各形象。
                                            </div>
                                        </template>
                                    </ElTooltip>
                                </div>
                            </div>
                            <div class="mt-[14px]">
                                <ElButton
                                    class="!h-10 w-[106px] !border-[#ffffff1a]"
                                    color="#262626"
                                    @click="handleCopywriting('fill')"
                                    >文案库填充</ElButton
                                >
                                <ElButton
                                    class="!h-10 w-[106px] !border-[#ffffff1a]"
                                    color="#262626"
                                    @click="handleCopywriting('add')"
                                    >新增文案</ElButton
                                >
                            </div>
                        </div>
                        <div class="grow min-h-0 mt-[14px]">
                            <ElScrollbar>
                                <div class="px-3 flex flex-col gap-y-2">
                                    <div
                                        v-for="(item, index) in formData.copywriting"
                                        class="border border-app-border-2 p-3 rounded-md h-fit"
                                        :key="index">
                                        <div class="flex justify-between gap-x-2">
                                            <div
                                                class="rounded-[100px] text-white min-w-[32px] h-5 flex items-center justify-center"
                                                :class="[
                                                    item.content
                                                        ? 'bg-[#3BB840]'
                                                        : 'shadow-[0_0_0_1px_var(--app-border-color-2)]',
                                                ]">
                                                {{ index + 1 }}
                                            </div>
                                            <div>
                                                <div class="w-4 h-4" @click="handleCopywritingDelete(index)">
                                                    <close-btn :icon-size="10"></close-btn>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <ElInput
                                                v-model="item.content"
                                                maxlength="1000"
                                                show-word-limit
                                                placeholder="请输入内容"
                                                type="textarea"
                                                resize="none"
                                                input-style="font-size: 11px"
                                                :rows="6"
                                                @blur="handleUpdateCreateTask()" />
                                        </div>
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                    <div class="content-item">
                        <div class="flex-shrink-0 flex items-center justify-between px-[14px]">
                            <div class="text-white font-bold">视频设置</div>
                            <ElButton
                                link
                                type="primary"
                                @click="handleOpenAdvancedSetting()"
                                v-if="clipConfig.is_open">
                                高级设置
                            </ElButton>
                        </div>
                        <div class="grow min-h-0 flex flex-col">
                            <div class="px-[14px]">
                                <div class="mt-5">
                                    <div class="text-white text-xs">通道选择</div>
                                    <div class="mt-[18px]">
                                        <ElSelect
                                            :model-value="formData.model_version"
                                            class="!h-11"
                                            placeholder="请选择通道"
                                            popper-class="dark-select-popper"
                                            :show-arrow="false"
                                            @change="handleChangeModelVersion">
                                            <ElOption
                                                v-for="item in getModelChannel"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id"></ElOption>
                                        </ElSelect>
                                    </div>
                                </div>
                            </div>
                            <div class="px-[14px]">
                                <div class="mt-5">
                                    <div class="text-white text-xs">音色设置</div>
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
                                            <div class="text-white text-[11px]">
                                                {{ item.label }}
                                            </div>
                                            <ElTooltip
                                                placement="top"
                                                popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2"
                                                :show-arrow="false">
                                                <div
                                                    class="w-4 h-4 rounded-full flex items-center justify-center shadow-[0_0_0_1px_rgba(255,255,255,0.2)] cursor-pointer">
                                                    <Icon name="local-icon-tips2" color="#ffffff" :size="16"></Icon>
                                                </div>
                                                <template #content>
                                                    <div
                                                        class="text-[#ffffff80] text-[11px] leading-6 w-[212px]"
                                                        v-html="item.tips"></div>
                                                </template>
                                            </ElTooltip>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="grow min-h-0 mt-[18px] flex flex-col gap-y-2"
                                v-if="formData.extra.currentVoiceType == VoiceType.Custom">
                                <VoiceDefineTemplate>
                                    <div class="flex flex-col gap-y-2">
                                        <div class="type-menu-item" @click="handleSelectVoice">
                                            <span class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                                                <Icon name="local-icon-windows" color="#ffffff"></Icon>
                                            </span>
                                            <span class="text-[#ffffffcc]"> 选择已有音色 </span>
                                        </div>
                                        <upload
                                            v-if="formData.model_version == DigitalHumanModelVersionEnum.CHANJING"
                                            class="w-full"
                                            show-progress
                                            type="audio"
                                            accept=".mp3,.wav"
                                            :limit="1"
                                            :show-file-list="false"
                                            @success="getUploadVoiceSuccess">
                                            <div class="type-menu-item">
                                                <span
                                                    class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                                                    <Icon name="local-icon-upload" color="#ffffff"></Icon>
                                                </span>
                                                <span class="text-[#ffffffcc]"> 本地上传 </span>
                                            </div>
                                        </upload>
                                    </div>
                                </VoiceDefineTemplate>
                                <div class="flex-shrink-1 min-h-0">
                                    <ElScrollbar>
                                        <div class="px-3 flex flex-col gap-y-[14px]">
                                            <div v-for="(item, index) in formData.voice" :key="index">
                                                <ElPopover
                                                    trigger="click"
                                                    width="212"
                                                    popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2 choose-type-popover"
                                                    :show-arrow="false">
                                                    <template #reference>
                                                        <div
                                                            class="h-11 px-[15px] rounded-md flex items-center justify-between gap-x-2 cursor-pointer hover:bg-app-bg-1 border border-app-border-2"
                                                            @click="handleClickVoice(index)">
                                                            <div
                                                                class="text-white text-[11px] flex-1 line-clamp-1 break-all">
                                                                {{ item.name }}
                                                            </div>
                                                            <div class="text-[#ffffff80] text-[11px]">
                                                                {{ item.voice_id ? "已训练" : "未训练" }}
                                                            </div>
                                                            <div class="flex-shrink-0 items-center flex gap-x-2">
                                                                <div class="w-[1px] h-[12px] bg-[#ffffff1a]"></div>
                                                                <div
                                                                    class="w-4 h-4"
                                                                    @click.stop="handleDeleteVoice(index)">
                                                                    <close-btn :icon-size="10"></close-btn>
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
                                <div class="flex-shrink-0 px-3">
                                    <ElPopover
                                        trigger="click"
                                        width="212"
                                        popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2 choose-type-popover"
                                        :show-arrow="false">
                                        <template #reference>
                                            <div
                                                class="w-full h-11 rounded-md shadow-[0_0_0_1px_var(--app-border-color-1)] flex items-center justify-center gap-x-2 text-white hover:bg-[#ffffff0d] cursor-pointer"
                                                @click="handleAddVoice">
                                                <Icon name="local-icon-upload3"></Icon>
                                                <div class="text-xs">添加音色</div>
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
import { AppTypeEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { getDigitalHumanDetail, addDigitalHuman, updateDigitalHuman } from "@/api/redbook";
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
        theme: "dark",
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
            formData.anchor[replaceMaterialIndex.value] = validLists[0];
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
        theme: "dark",
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
        theme: "dark",
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
        theme: "dark",
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
        formData.anchor = data.anchor.map((item) => item.anchor_url);
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
.content-item {
    @apply rounded-xl bg-app-bg-3 py-[14px] border border-app-border-1 flex flex-col grow min-h-0 flex-1;
    // :deep(.el-select__wrapper) {
    //     background-color: var(--app-bg-color-1) !important;
    // }
    :deep(.el-input) {
        .el-input__wrapper {
            background-color: transparent !important;
            box-shadow: none !important;
        }
    }
}
</style>
