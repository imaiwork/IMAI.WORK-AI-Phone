<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">配置信息</div>
            <el-form ref="formRef" label-position="top">
                <el-form-item label="好友请求语自动润色提示词设置" prop="prompt_name">
                    <el-input
                        v-model="friendPrompt.prompt_text"
                        class="w-[500px]"
                        type="textarea"
                        maxlength="3000"
                        show-word-limit
                        :rows="8" />
                </el-form-item>
                <el-form-item label="自动私聊自动润色提示词设置" prop="prompt_text">
                    <el-input
                        v-model="privatePrompt.prompt_text"
                        class="w-[500px]"
                        type="textarea"
                        maxlength="3000"
                        show-word-limit
                        :rows="8" />
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button
            v-perms="['ai_application.sph.setting/setConfig']"
            type="primary"
            @click="lockSubmit"
            :loading="isLock">
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { getChatPrompt, saveChatPrompt } from "@/api/chat";
import { useLockFn } from "@/hooks/useLockFn";
import type { FormInstance } from "element-plus";

const formRef = shallowRef<FormInstance>();

const friendPrompt = ref<any>({});
const privatePrompt = ref<any>({});

const submit = async () => {
    await saveChatPrompt(friendPrompt.value);
    await saveChatPrompt(privatePrompt.value);
    await getConfig();
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);

const getConfig = async () => {
    const data = await getChatPrompt();
    friendPrompt.value = data.find((item: any) => item.id === 21);
    privatePrompt.value = data.find((item: any) => item.id === 22);
};
getConfig();
</script>

<style scoped lang="scss">
.banner-upload {
    :deep(.upload-btn) {
        width: 500px !important;
        height: 145px !important;
    }
    :deep(.file-item) {
        width: 500px !important;
        height: 145px !important;
    }
}
</style>
