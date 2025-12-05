<template>
    <div>
        <popup
            title="制作音频"
            ref="popupRef"
            :async="true"
            width="500px"
            confirm-button-text=""
            cancel-button-text=""
            @close="handleClose">
            <ElForm :model="formData" :rules="formRules" ref="formRef" label-position="top" :disabled="isLock">
                <ElFormItem label="音频名称" prop="name">
                    <ElInput v-model="formData.name" placeholder="请输入音频名称" />
                </ElFormItem>
                <ElFormItem label="使用模型" prop="model_version">
                    <ElSelect v-model="formData.model_version" placeholder="请选择模型" @change="handleChangeModel">
                        <ElOption
                            v-for="item in modelChannel"
                            :key="item.id"
                            :value="item.id"
                            :label="item.name"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="音色" prop="voice_id">
                    <ElSelect v-model="formData.voice_id" :disabled="!formData.model_version" placeholder="请选择音色">
                        <ElOption
                            :value="item.voice_id"
                            :label="item.name"
                            v-for="(item, index) in toneLists"></ElOption>
                    </ElSelect>
                </ElFormItem>

                <ElFormItem label="音频内容" prop="msg">
                    <ElInput
                        v-model="formData.msg"
                        placeholder="请输入音频名称"
                        type="textarea"
                        :maxlength="150"
                        show-word-limit
                        :rows="8" />
                </ElFormItem>
            </ElForm>
            <div class="flex justify-end mt-6">
                <ElButton type="primary" :loading="isLock" @click="lockSubmit"
                    >开始转写
                    <template v-if="tokensValue">（消耗{{ tokensValue }}算力）</template>
                </ElButton>
            </div>
        </popup>
    </div>
</template>

<script setup lang="ts">
import { getVoiceList, createAudio } from "@/api/digital_human";
import Popup from "@/components/popup/index.vue";
import type { FormInstance } from "element-plus";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel);

const getHumanAudioToken = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AUDIO)?.score;
const getHumanAudioProToken = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AUDIO_PRO)?.score;
const tokensValue = computed(() => {
    if (formData.model_version === "1") {
        return getHumanAudioProToken;
    }
    if (formData.model_version === "2") {
        return getHumanAudioToken;
    }
    return null;
});

const popupRef = shallowRef<InstanceType<typeof Popup>>();

const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const formRef = ref<FormInstance>();
const formData = reactive({
    name: "",
    msg: "",
    voice_id: "",
    model_version: "",
});

const formRules = {
    name: [{ required: true, message: "请输入音频名称" }],
    msg: [{ required: true, message: "请输入音频内容" }],
    voice_id: [{ required: true, message: "请选择音色" }],
    model_version: [{ required: true, message: "请选择模型" }],
};

const handleChangeModel = (value: string) => {
    getToneList();
};

const toneLists = ref<any[]>([]);
const getToneList = async () => {
    const { lists } = await getVoiceList({
        page_no: 1,
        page_size: 9999,
        model_version: formData.model_version,
    });
    toneLists.value = lists.length ? lists.filter((item: any) => item.status == 1) : [];
};

const getAccept = computed(() => {
    return ".mp3";
});

const handleFileSuccess = (data: any) => {};

const open = () => {
    popupRef.value.open();
    getToneList();
};

const handleSubmit = async () => {
    if (userTokens.value < tokensValue.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    await formRef.value?.validate();
    try {
        await createAudio(formData);
        popupRef.value?.close();
        userStore.getUser();
        emit("success");
    } catch (error) {
        feedback.msgError(error || "转写失败！");
    }
};

const handleClose = () => {
    emit("close");
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

defineExpose({
    open,
});
</script>

<style scoped></style>
