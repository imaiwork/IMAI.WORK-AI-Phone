<template>
    <popup
        ref="popupRef"
        width="400px"
        async
        style="padding: 18px"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        @close="close">
        <div class="-my-4">
            <div class="w-6 h-6 absolute top-4 right-4" @click="close">
                <close-btn />
            </div>
            <div class="flex items-center gap-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <rect width="20" height="20" rx="10" fill="#0065FB" />
                    <path d="M6 8V12" stroke="white" stroke-width="1.2" />
                    <path d="M14 8V12" stroke="white" stroke-width="1.2" />
                    <path d="M10 6V14" stroke="white" stroke-width="1.2" />
                </svg>
                <span class="font-medium text-[20px]">上传本地音频</span>
            </div>
            <div class="flex items-center gap-x-1 rounded-full p-1 mt-5 w-fit">
                <Icon name="local-icon-tips2" :size="16"></Icon>
                <span class="text-xs">注意：请根据性别上传对应的音频文件素材。</span>
            </div>
            <ElForm
                class="mt-[17px]"
                :model="formData"
                :rules="formRules"
                ref="formRef"
                label-position="top"
                :disabled="isLock">
                <ElFormItem label="音色名称" prop="name">
                    <ElInput
                        v-model="formData.name"
                        class="!h-11 custom-input"
                        placeholder="请输入音色名称"
                        maxlength="30" />
                </ElFormItem>
                <ElFormItem label="使用模型" prop="model_version">
                    <ElSelect
                        v-model="formData.model_version"
                        class="!h-11 custom-select"
                        :show-arrow="false"
                        placeholder="请选择模型">
                        <ElOption
                            v-for="item in modelChannel"
                            :key="item.id"
                            :value="item.model_version"
                            :label="item.name">
                            <div class="flex items-center gap-2">
                                <img :src="item.logo" class="w-4 h-4" />
                                <span class="font-medium">{{ item.name }}</span>
                            </div>
                        </ElOption>
                        <template #label="{ label, value }">
                            <div class="flex items-center gap-2">
                                <img
                                    :src="modelChannel.find((item) => item.model_version == value)?.logo"
                                    class="w-4 h-4" />
                                <span class="font-medium">{{ label }}</span>
                            </div>
                        </template>
                    </ElSelect>
                </ElFormItem>

                <ElFormItem label="上传音频" prop="url">
                    <upload
                        ref="uploadRef"
                        class="w-full"
                        drag
                        type="audio"
                        show-progress
                        :show-file-list="false"
                        :limit="1"
                        :max-size="maxSize"
                        :accept="getAccept"
                        @success="handleFileSuccess">
                        <template v-if="!fileInfo">
                            <div
                                class="h-[166px] bg-[#f8fafc] rounded-lg flex flex-col justify-center items-center border border-dashed border-[#0000001a] hover:border-[#0065fb33] transition-colors cursor-pointer">
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center border border-dashed border-[#0000001a] hover:border-[#00000033] flex-shrink-0">
                                    <Icon name="el-icon-Plus"></Icon>
                                </div>
                                <div class="text-xs text-[#00000080] text-center mt-4">
                                    文件不超过{{ maxSize }}MB，支持 .mp3 / .m4a / .wav
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="bg-[#f8fafc] p-3 flex flex-col gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-[#1a1a1a] truncate text-left">
                                            {{ fileInfo.name }}
                                        </div>
                                    </div>

                                    <div
                                        class="w-7 h-7 rounded-full bg-white border border-[#e4e9f0] flex items-center justify-center cursor-pointer hover:bg-[#fff0f0] hover:border-[#ffb3b3] transition-colors flex-shrink-0"
                                        title="删除文件"
                                        @click.stop="handleDeleteFile">
                                        <Icon name="el-icon-Delete" color="#f56c6c" :size="14"></Icon>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-center gap-1.5 h-8 rounded-lg border border-dashed border-[#0065fb55] bg-[#f0f6ff] text-[#0065fb] text-xs font-medium cursor-pointer hover:bg-[#e0eeff] hover:border-[#0065fb] transition-colors">
                                    重新上传（覆盖当前文件）
                                </div>
                            </div>
                        </template>
                    </upload>
                </ElFormItem>
            </ElForm>

            <div class="px-[35px] mt-[18px]">
                <ElButton type="primary" class="w-full !h-[50px] !rounded-full" :loading="isLock" @click="lockSubmit">
                    开始转写
                    <template v-if="tokensValue">（消耗{{ tokensValue }}算力）</template>
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { voiceClone, shanjianVoiceClone, minimaxAudioUpload, minimaxVoiceClone } from "@/api/digital_human";
import type { FormInstance } from "element-plus";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum, ThemeEnum } from "@/enums/appEnums";
import { DigitalHumanModelVersionEnum } from "../../../_enums";

const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const modelChannel = computed(() => {
    const models = appStore.getAiModels.humanModels;
    return models;
});

const tokensValue = computed(() => {
    return {
        [DigitalHumanModelVersionEnum.CHANJING]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_CHANJING)?.score,
        [DigitalHumanModelVersionEnum.SHANJIAN]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN)?.score,
        [DigitalHumanModelVersionEnum.MINIMAX_HD]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_MINIMAX_HD)
            ?.score,
        [DigitalHumanModelVersionEnum.MINIMAX_TURBO]: userStore.getTokenByScene(
            TokensSceneEnum.HUMAN_VOICE_MINIMAX_TURBO,
        )?.score,
    }[formData.model_version];
});

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formRef = ref<FormInstance>();
const uploadRef = shallowRef();
const formData = reactive({
    url: "",
    name: "",
    gender: "male" as "male" | "female",
    model_version: DigitalHumanModelVersionEnum.CHANJING,
    file_id: "",
});

const fileInfo = ref<{ name: string; size: string } | null>(null);

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
};

const formRules = {
    name: [{ required: true, message: "请输入音色名称" }],
    url: [{ required: true, message: "请上传音频" }],
    model_version: [{ required: true, message: "请选择模型" }],
};

const maxSize = 10;
const getAccept = computed(() => {
    return formData.model_version == DigitalHumanModelVersionEnum.SHANJIAN ? ".mp3,.wav" : ".mp3,.m4a,.wav";
});

const handleFileSuccess = async (result: any) => {
    const uri = result?.data?.uri;
    const file = result?.file;
    if (!uri) return;
    formData.url = uri;

    fileInfo.value = {
        name: file?.name ?? uri.split("/").pop() ?? "audio",
        size: file?.size ? formatFileSize(file.size) : "",
    };

    if (
        [DigitalHumanModelVersionEnum.MINIMAX_HD, DigitalHumanModelVersionEnum.MINIMAX_TURBO].includes(
            formData.model_version,
        )
    ) {
        try {
            const { file_id } = await minimaxAudioUpload({
                audio_url: formData.url,
            });
            formData.file_id = file_id;
        } catch (error) {
            handleDeleteFile();
            feedback.msgError(error || "上传失败");
            return;
        }
    }
};

const handleDeleteFile = () => {
    formData.url = "";
    formData.file_id = "";
    fileInfo.value = null;
};

const open = () => {
    popupRef.value.open();
};

const handleSubmit = async () => {
    if (userTokens.value < tokensValue.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    await formRef.value.validate();
    try {
        const currModel = modelChannel.value.find((item) => item.model_version == formData.model_version);
        const callMinimax = () =>
            minimaxVoiceClone({
                name: formData.name,
                model: currModel?.alias,
                file_id: formData.file_id,
                text: "亲爱的顾客朋友们，注意啦！本周末我们将迎来年度最大的促销活动。全场商品低至五折起，更有神秘大奖等你来拿！记得带上你的亲朋好友，一起享受这场购物盛宴。错过今天，再等一年！快来加入我们，让这个周末充满惊喜和欢乐",
            });

        const apis: Partial<Record<DigitalHumanModelVersionEnum, () => Promise<any>>> = {
            [DigitalHumanModelVersionEnum.SHANJIAN]: () =>
                shanjianVoiceClone({
                    name: formData.name,
                    audio_url: formData.url,
                }),
            [DigitalHumanModelVersionEnum.CHANJING]: () => voiceClone(formData),
            [DigitalHumanModelVersionEnum.MINIMAX_HD]: () => callMinimax(),
            [DigitalHumanModelVersionEnum.MINIMAX_TURBO]: () => callMinimax(),
        };

        const apiFn = apis[formData.model_version];
        if (!apiFn) {
            feedback.msgError("不支持的模型版本");
            return;
        }
        await apiFn();

        popupRef.value?.close();
        userStore.getUser();
        emit("success");
    } catch (error) {
        feedback.msgError(error || "克隆失败");
    }
};

const close = () => {
    emit("close");
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
:deep(.el-upload-dragger) {
    padding: 0;
}
</style>
