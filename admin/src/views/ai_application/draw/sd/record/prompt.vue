<template>
    <popup
        width="700px"
        :title="`${mode === 'image' ? '图片获取' : '文字获取'}提示词配置`"
        ref="popRef"
        confirm-button-text=""
        cancel-button-text=""
        @close="close"
        @confirm="savePromptConfig">
        <el-form :model="promptConfig">
            <el-form-item label="提示词">
                <div>
                    <el-input
                        v-model="promptConfig.prompt_text"
                        type="textarea"
                        placeholder="请输入提示词"
                        class="w-[500px]"
                        :rows="20" />
                </div>
            </el-form-item>
        </el-form>
        <div class="flex justify-end mt-4">
            <el-button type="primary" @click="lockSavePromptConfig" :loading="isSavePromptConfig">保存</el-button>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getChatPrompt, saveChatPrompt } from "@/api/chat";
import { useLockFn } from "@/hooks/useLockFn";
import popup from "@/components/popup/index.vue";

const emit = defineEmits(["close", "success"]);
const mode = ref<string>("image");

const popRef = shallowRef();

const promptConfig = ref<any>({});

const getPromptConfig = async () => {
    const data = await getChatPrompt();
    promptConfig.value = data.find((item: any) => item.id === (mode.value === "image" ? 3 : 4));
};

const savePromptConfig = async () => {
    await saveChatPrompt(promptConfig.value);
    close();
};

const { lockFn: lockSavePromptConfig, isLock: isSavePromptConfig } = useLockFn(savePromptConfig);

const open = (type: string) => {
    mode.value = type;
    popRef.value?.open();
    getPromptConfig();
};

const close = () => {
    emit("close");
};

defineExpose({ open, close });
</script>

<style scoped></style>
