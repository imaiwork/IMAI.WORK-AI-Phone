<template>
    <popup
        ref="popupRef"
        width="400px"
        async
        style="padding: 18px; background-color: var(--app-bg-color-2)"
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
                <span class="text-white font-bold text-[20px]">上传本地音频</span>
            </div>
            <div class="flex items-center gap-x-1 rounded-full bg-[#ffffff08] p-1 mt-5 w-fit">
                <Icon name="local-icon-tips" :size="16"></Icon>
                <span class="text-[#ffffff4d] text-xs">注意：请根据性别上传对应的音频文件素材。</span>
            </div>
            <ElForm
                class="mt-[17px]"
                :model="formData"
                :rules="formRules"
                ref="formRef"
                label-position="top"
                :disabled="isLock">
                <ElFormItem label="音色名称" prop="name">
                    <ElInput v-model="formData.name" class="!h-11" placeholder="请输入音色名称" maxlength="30" />
                </ElFormItem>
                <ElFormItem label="性别" prop="gender">
                    <ElSelect
                        v-model="formData.gender"
                        class="!h-11"
                        placeholder="请选择性别"
                        popper-class="dark-select-popper"
                        :show-arrow="false">
                        <ElOption value="male" label="男"></ElOption>
                        <ElOption value="female" label="女"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="使用模型" prop="model_version">
                    <ElSelect
                        v-model="formData.model_version"
                        class="!h-11"
                        popper-class="dark-select-popper"
                        :show-arrow="false"
                        placeholder="请选择模型">
                        <ElOption
                            v-for="item in modelChannel"
                            :key="item.id"
                            :value="item.id"
                            :label="item.name"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="上传音频" prop="url">
                    <upload
                        class="w-full"
                        drag
                        type="audio"
                        list-type="text"
                        :limit="1"
                        :size="20"
                        :accept="getAccept"
                        @success="handleFileSuccess">
                        <div class="h-[166px] bg-app-bg-1 rounded-lg flex flex-col justify-center items-center">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center border border-dashed border-[#ffffff1a] hover:border-[#ffffff33] cursor-pointer mt-8 flex-shrink-0">
                                <Icon name="el-icon-Plus" color="#ffffff"></Icon>
                            </div>
                            <div class="text-white text-xs mt-3">上传录音文件</div>
                            <div class="text-xs text-[rgba(255,255,255,0.3)] text-center mt-4">
                                文件不超过20MB,不支持gif/avif等音频格式以外的文件
                            </div>
                        </div>
                    </upload>
                </ElFormItem>
            </ElForm>

            <div class="px-[35px] mt-[18px]">
                <ElButton type="primary" class="w-full !h-[50px] !rounded-full" :loading="isLock" @click="lockSubmit"
                    >开始转写
                    <template v-if="tokensValue">（消耗{{ tokensValue }}算力）</template>
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { voiceClone } from "@/api/digital_human";
import type { FormInstance } from "element-plus";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { DigitalHumanModelVersionEnum } from "../../../_enums";

const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        const modelChannel = channel.filter((item) => {
            item.id = parseInt(item.id);
            if (item.status == 1 && DigitalHumanModelVersionEnum.CHANJING == item.id) {
                return item;
            }
        });
        return modelChannel;
    }
    return [];
});

const tokensValue = computed(() => {
    return {
        [DigitalHumanModelVersionEnum.STANDARD]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE)?.score,
        [DigitalHumanModelVersionEnum.SUPER]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_PRO)?.score,
        [DigitalHumanModelVersionEnum.ADVANCED]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_ADVANCED)?.score,
        [DigitalHumanModelVersionEnum.ELITE]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_ELITE)?.score,
        [DigitalHumanModelVersionEnum.CHANJING]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_CHANJING)?.score,
    }[formData.model_version];
});

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formRef = ref<FormInstance>();
const formData = reactive({
    url: "",
    name: "",
    gender: "male" as "male" | "female",
    model_version: DigitalHumanModelVersionEnum.STANDARD,
});

const formRules = {
    name: [{ required: true, message: "请输入音色名称" }],
    url: [{ required: true, message: "请上传音频" }],
    model_version: [{ required: true, message: "请选择模型" }],
};

const getAccept = computed(() => {
    return ".mp3,.m4a,.wav";
});

const handleFileSuccess = (result: any) => {
    const {
        data: { uri },
    } = result;
    formData.url = uri;
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
        await voiceClone(formData);
        popupRef.value?.close();
        userStore.getUser();
        emit("success");
    } catch (error) {
        feedback.msgError(error || "转写失败!");
    }
};

const close = () => {
    emit("close");
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

watch(
    () => modelChannel.value,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            formData.model_version = newVal[0].id;
        }
    },
    {
        immediate: true,
    }
);

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";

:deep(.el-upload-dragger) {
    padding: 0;
    border-color: var(--app-border-color-1);
    background-color: transparent;
    border-radius: 10px;
    &:hover {
        border-color: #ffffff33;
    }
    &.is-dragover {
        border-width: 1px;
        border-color: #ffffff33;
    }
}

:deep(.el-form) {
    .el-form-item {
        &__label {
            color: #ffffff;
        }
    }
}
</style>
