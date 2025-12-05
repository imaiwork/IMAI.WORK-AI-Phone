<template>
    <popup
        ref="uploadPopRef"
        width="778px"
        class="digital-human-upload-popup"
        style="padding: 0; background-color: var(--app-bg-color-2)"
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false"
        :append-to-body="false"
        @close="close">
        <div class="rounded-[24px] flex overflow-hidden container">
            <div class="absolute top-2 right-2">
                <div class="w-6 h-6" @click="close">
                    <close-btn />
                </div>
            </div>
            <div class="w-[400px] bg-[#101010] py-[30px] px-[20px] flex-shrink-0">
                <div class="title-text">
                    上传视频 <br />
                    同步创建形象并生成内容
                </div>
                <div class="flex items-center gap-x-2 mt-4">
                    <Icon name="local-icon-success_fill" color="var(--color-primary)" :size="18"></Icon>
                    <span class="text-lg text-white font-bold">视频要求（{{ getModelName }}）</span>
                </div>
                <div class="mt-[15px]">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <img src="../../../_assets/images/video_upload_temp.png" class="w-[134px]" />
                        </div>
                        <div class="flex flex-col gap-y-[6px]">
                            <div v-for="(item, index) in uploadTemplateContentLists" :key="index" class="text-[10px]">
                                <span class="text-[rgba(255,255,255,0.8)] flex-shrink-0">{{ item.name }}</span>
                                <span class="text-[rgba(255,255,255,0.3)]">·</span>
                                <span class="text-[rgba(255,255,255,0.3)]">{{ item.value }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center gap-x-2 mt-4">
                            <Icon name="local-icon-error_fill" color="#ffffff" :size="18"></Icon>
                            <span class="text-lg text-white font-bold">错误示例</span>
                        </div>
                        <img src="../../../_assets/images/video_upload_error_temp.png" class="mt-[15px]" />
                    </div>
                </div>
            </div>
            <div class="flex-1 p-[30px] text-base">
                <div class="text-[#ffffff80]">请上传一段视频，作为驱动数字人的 底版视频</div>
                <div class="cursor-pointer text-white mt-[10px] inline-block" @click="openExampleVideo">
                    查看教程示例
                </div>
                <ElPopover
                    :visible="modelVersionVisible"
                    popper-class="!w-[318px] !p-2 !bg-app-bg-2 !border-[--app-border-color-1] !rounded-xl"
                    teleported
                    :show-arrow="false">
                    <template #reference>
                        <div
                            class="mt-[15px] border border-[var(--app-border-color-1)] bg-app-bg-1 rounded-lg h-[48px] flex items-center justify-between px-3 cursor-pointer"
                            @click="modelVersionVisible = !modelVersionVisible">
                            <div class="text-[#ffffff80]">模型选择</div>
                            <div class="flex items-center gap-x-3">
                                <div class="text-white">
                                    {{ getModelName }}
                                </div>
                                <Icon name="local-icon-up_down" :size="20" color="#ffffff0d"></Icon>
                            </div>
                        </div>
                    </template>
                    <div>
                        <div class="flex flex-col gap-y-1">
                            <div
                                v-for="item in modelChannel"
                                :key="item.id"
                                class="flex items-center h-11 rounded-md hover:bg-app-bg-1 border border-[transparent] hover:border-[var(--app-border-color-1)] gap-x-3 px-3 cursor-pointer"
                                :class="{
                                    'bg-app-bg-1 border-[var(--app-border-color-1)]': formData.model_version == item.id,
                                }"
                                @click="changeModel(item.id)">
                                <img :src="item.icon" class="w-5 h-5" />
                                <div class="text-white font-bold">
                                    {{ item.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </ElPopover>
                <div class="flex items-center gap-x-1 rounded-full bg-[#ffffff08] p-1 mt-5">
                    <Icon name="local-icon-tips" :size="16"></Icon>
                    <div class="text-[#ffffff4d] text-xs">注意：切换模型版本，需要重新上传视频及相关内容。</div>
                </div>
                <div class="rounded-xl grow min-h-0 mt-4">
                    <upload
                        class="w-full h-full"
                        type="video"
                        drag
                        show-progress
                        :limit="1"
                        :show-file-list="false"
                        :max-size="uploadLimit[formData.model_version].size"
                        :min-duration="uploadLimit[formData.model_version].videoMinDuration"
                        :max-duration="uploadLimit[formData.model_version].videoMaxDuration"
                        :video-min-width="uploadLimit[formData.model_version].minResolution"
                        :video-max-width="uploadLimit[formData.model_version].maxResolution"
                        :accept="uploadVideoFormat"
                        @success="getVideo">
                        <div class="w-full rounded-lg h-[256px] bg-app-bg-1">
                            <template v-if="!formData.url">
                                <div class="flex flex-col items-center justify-center h-full">
                                    <div
                                        class="w-12 h-12 rounded-xl flex items-center justify-center border border-dashed border-[#ffffff1a] hover:border-[#ffffff33] cursor-pointer">
                                        <Icon name="el-icon-Plus" color="#ffffff"></Icon>
                                    </div>
                                    <div class="gap-2 mt-[15px] flex flex-col">
                                        <span class="text-white">点击此开始上传视频</span
                                        ><span class="text-xs text-[#ffffff80]">支持拖拽上传</span>
                                    </div>
                                    <div
                                        class="absolute bottom-2 left-0 w-full text-center text-[#ffffff80] text-[10px]">
                                        为保证形象的复刻效果，推荐阅读左侧要求后录制上传
                                    </div>
                                </div>
                            </template>
                            <div v-else class="w-full h-full relative" v-loading="parseLoading">
                                <template v-if="!parseLoading">
                                    <div class="absolute top-0 left-0 w-full h-full">
                                        <img
                                            :src="formData.pic"
                                            class="w-full h-full rounded-lg object-cover filter blur-sm" />
                                    </div>
                                    <div
                                        class="w-[45%] mx-auto h-full flex flex-col items-center justify-center rounded-lg relative overflow-hidden">
                                        <img :src="formData.pic" class="w-full h-full rounded-lg object-cover" />
                                        <div
                                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 cursor-pointer"
                                            @click.stop="emit('playVideo', formData.url)">
                                            <Icon name="local-icon-play" :size="50" color="#ffffff"></Icon>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 z-[11]">
                                        <ElTooltip content="删除">
                                            <ElButton circle color="rgba(255,255,255,0.1)" @click.stop="changeModel()">
                                                <Icon name="el-icon-Delete" :size="14" color="#ffffff"></Icon>
                                            </ElButton>
                                        </ElTooltip>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </upload>
                </div>
                <div class="mt-4 flex justify-center">
                    <ElTooltip placement="top" v-if="modelType == ModeTypeEnum.FIGURE">
                        <ElButton
                            type="primary"
                            class="w-full !h-[50px] !rounded-full"
                            :loading="isLock || parseLoading"
                            :disabled="!isAvailable"
                            @click="lockSubmit">
                            立即克隆
                        </ElButton>
                        <template #content>
                            <div>扣除算力：{{ tokensValue || 0 }}</div>
                        </template>
                    </ElTooltip>
                    <ElButton
                        v-if="modelType == ModeTypeEnum.VIDEO"
                        type="primary"
                        class="w-full !h-[50px] !rounded-full"
                        :loading="isLock || parseLoading"
                        :disabled="!isAvailable"
                        @click="lockSubmit">
                        立即创建
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { uploadImage } from "@/api/app";
import { createAnchor } from "@/api/digital_human";
import { useAppStore } from "@/stores/app";
import { dayjs } from "element-plus";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import { getVideoFirstFrame } from "@/utils/util";
import Popup from "@/components/popup/index.vue";
import { DigitalHumanModelVersionEnum } from "../../../_enums";
import { commonUploadLimit, uploadLimit } from "../../../_config";
import ExampleVideo from "../../../_assets/video/example.mp4";

enum ModeTypeEnum {
    VIDEO = 1,
    FIGURE = 2,
}

const emit = defineEmits<{
    (e: "create", value?: any): void;
    (e: "close"): void;
    (e: "playVideo", value: string): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();
const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.filter((item) => item.status == 1 && DigitalHumanModelVersionEnum.CHANJING == item.id);
    }
    return [];
});

const formData = reactive<any>({
    model_version: DigitalHumanModelVersionEnum.STANDARD,
    anchor_name: dayjs().format("YYYYMMDDHHmmss").substring(2),
    width: "",
    height: "",
    pic: "",
    url: "",
});

const tokensValue = computed(() => {
    return {
        [DigitalHumanModelVersionEnum.STANDARD]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR)?.score,
        [DigitalHumanModelVersionEnum.SUPER]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_PRO)?.score,
        [DigitalHumanModelVersionEnum.ADVANCED]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_ADVANCED)
            ?.score,
        [DigitalHumanModelVersionEnum.ELITE]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_ELITE)?.score,
        [DigitalHumanModelVersionEnum.CHANJING]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_CHANJING)
            ?.score,
    }[formData.model_version];
});

// 获取当前模型名称
const getModelName = computed(() => {
    return modelChannel.value.find((item) => item.id == formData.model_version)?.name;
});

// 上传视频格式限制
const uploadVideoFormat = `.mp4,.mov`;

const commonUploadRequirements = {
    resolution: `${commonUploadLimit.minResolution}P-${commonUploadLimit.maxResolution}P`,
    fileSize: commonUploadLimit.size,
    videoMinDuration: commonUploadLimit.videoMinDuration,
    videoMaxDuration: commonUploadLimit.videoMaxDuration,
};

// 模型要求对应的上传要求描述
const modelUploadRequirements: any = {
    [DigitalHumanModelVersionEnum.STANDARD]: {
        resolution: `${uploadLimit[DigitalHumanModelVersionEnum.STANDARD].minResolution}P-${
            uploadLimit[DigitalHumanModelVersionEnum.STANDARD].maxResolution
        }P`,
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.STANDARD].size,
        videoMinDuration: uploadLimit[DigitalHumanModelVersionEnum.STANDARD].videoMinDuration,
        videoMaxDuration: uploadLimit[DigitalHumanModelVersionEnum.STANDARD].videoMaxDuration,
    },
    [DigitalHumanModelVersionEnum.SUPER]: {
        resolution: `${uploadLimit[DigitalHumanModelVersionEnum.SUPER].minResolution}P-${
            uploadLimit[DigitalHumanModelVersionEnum.SUPER].maxResolution
        }P`,
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.SUPER].size,
        videoMinDuration: uploadLimit[DigitalHumanModelVersionEnum.SUPER].videoMinDuration,
        videoMaxDuration: uploadLimit[DigitalHumanModelVersionEnum.SUPER].videoMaxDuration,
    },
    [DigitalHumanModelVersionEnum.ADVANCED]: commonUploadRequirements,
    [DigitalHumanModelVersionEnum.ELITE]: commonUploadRequirements,
    [DigitalHumanModelVersionEnum.CHANJING]: {
        resolution: `${uploadLimit[DigitalHumanModelVersionEnum.CHANJING].minResolution}P-${
            uploadLimit[DigitalHumanModelVersionEnum.CHANJING].maxResolution
        }P`,
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.CHANJING].size,
        videoMinDuration: uploadLimit[DigitalHumanModelVersionEnum.CHANJING].videoMinDuration,
        videoMaxDuration: uploadLimit[DigitalHumanModelVersionEnum.CHANJING].videoMaxDuration,
    },
};

// 上传格式
const extension = ["mp4", "mov"];

const uploadTemplateContentLists = computed(() => {
    return [
        {
            name: "录制视频",
            value: "视频全程只能有一个人物，嘴型不得遮挡，不得多人出镜",
        },
        {
            name: "文件大小",
            value: `文件≤${modelUploadRequirements[formData.model_version]?.fileSize}MB，${
                modelUploadRequirements[formData.model_version]?.videoMinDuration
            }秒-${modelUploadRequirements[formData.model_version]?.videoMaxDuration}秒`,
        },
        { name: "文件格式", value: extension.join("、") },
        { name: "视频帧率", value: "15fps≤帧率≤60fps" },
        {
            name: "视频要求",
            value: `清晰度必须为${modelUploadRequirements[formData.model_version]?.resolution}`,
        },
        {
            name: "声音要求",
            value: "视频内音频需要清晰、响亮且无嘈杂背景音等干扰",
        },
        {
            name: "录制框架",
            value: "视频应为人物正面出镜的近景画面，避免大角度侧脸或人脸过小",
        },
    ];
});

const modelVersionVisible = ref<boolean>(false);

// 是否可用
const isAvailable = computed(() => {
    return formData.url && formData.anchor_name;
});

const getVideo = (result: any) => {
    const {
        data: { uri },
    } = result;
    formData.url = uri;
    parseVideo(uri);
};

const parseLoading = ref<boolean>(false);
const parseVideo = async (url: string) => {
    parseLoading.value = true;
    const { file, width, height } = await getVideoFirstFrame(url);
    formData.width = width;
    formData.height = height;
    if (file) {
        uploadImage({
            file,
        }).then((res) => {
            formData.pic = res.uri;
            parseLoading.value = false;
        });
    } else {
        parseLoading.value = false;
    }
};

const changeModel = (value?: number) => {
    formData.url = "";
    formData.pic = "";
    modelVersionVisible.value = false;
    if (value) {
        formData.model_version = value;
    }
};

const handleCreate = async () => {
    const { model_version, gender, pic, url, width, height } = formData;
    if (!url) {
        feedback.msgError("请上传视频");
        return;
    }
    if (modelType.value == ModeTypeEnum.VIDEO) {
        emit("create", {
            modelType: modelType.value,
            formData,
        });
        close();
    } else if (modelType.value == ModeTypeEnum.FIGURE) {
        try {
            await createAnchor({
                name: formData.anchor_name,
                gender,
                url,
                pic,
                width,
                height,
                model_version,
            });
            userStore.getUser();
            feedback.msgSuccess("克隆成功");
            emit("create", {
                modelType: modelType.value,
                formData,
            });
            close();
        } catch (error) {
            feedback.msgError(error || "克隆失败");
        }
    }
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleCreate);

const uploadPopRef = ref<InstanceType<typeof Popup>>();

const modelType = ref<ModeTypeEnum>(ModeTypeEnum.VIDEO);

const open = async (type: ModeTypeEnum) => {
    modelType.value = type;
    uploadPopRef.value?.open();
};

const close = () => {
    emit("close");
};

const openExampleVideo = async () => {
    emit("playVideo", ExampleVideo);
};

watch(
    () => modelChannel.value,
    (newVal) => {
        if (newVal && newVal.length) {
            formData.model_version = newVal[0].id;
        }
    },
    {
        deep: true,
        immediate: true,
    }
);

defineExpose({
    open,
});
</script>

<style lang="scss" scoped>
.digital-human-upload-popup {
    :deep() {
        .el-dialog__header,
        .el-dialog__footer {
            display: none;
            padding: 0;
        }
        .el-dialog__body {
            .container {
                position: relative;
                &::after,
                &::before {
                    position: absolute;
                    content: "";
                    width: 100%;
                    height: 100%;
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: top left;
                    pointer-events: none;
                }
                &::after {
                    left: -14px;
                    top: -14px;
                    background-image: url("../../../_assets/images/upload_line_tl.png");
                    background-position: left top;
                }
                &::before {
                    bottom: -14px;
                    right: -14px;
                    background-position: bottom right;
                    background-image: url("../../../_assets/images/upload_line_br.png");
                }
            }
        }
        .el-upload-dragger {
            background-color: transparent;
            border-radius: 10px;
            border-color: var(--app-border-color-1);
            &:hover {
                border-color: #ffffff33;
            }
            &.is-dragover {
                border-width: 1px;
                border-color: #ffffff33;
            }
        }
    }
}

.name-input {
    :deep() {
        .el-input__wrapper {
            padding: 0;
        }
    }
}

.title-text {
    background: linear-gradient(90deg, #fff 3%, #0065fb 44%, #e02188 72%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 20px;
    font-weight: bold;
}
</style>
