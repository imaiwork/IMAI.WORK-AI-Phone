<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium">扣子配置</div>
            <div class="form-tips mb-[20px]">用于扣子工作流相关信息配置</div>
            <div>
                <el-form ref="formRef" :model="formData" label-width="120px">
                    <el-form-item label="扣子秘钥" prop="secret_token">
                        <el-input
                            v-model="formData.secret_token"
                            class="w-[380px]"
                            placeholder="请输入"
                            resize="none" />
                    </el-form-item>
                </el-form>
            </div>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['ai_application.agent/setConfig']" type="primary" :loading="isLock" @click="lockFn"
            >保存</el-button
        >
    </footer-btns>
</template>

<script setup lang="ts">
import { getCozeAgentConfig, cozeConfigAdd, cozeConfigUpdate } from "@/api/ai_application/agent/coze";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";

const formRef = ref();
const formData = reactive({
    id: "",
    secret_token: "",
});

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    formData.id ? await cozeConfigUpdate(formData) : await cozeConfigAdd(formData);
});

const getDetail = async () => {
    const data = await getCozeAgentConfig();
    setFormData(data, formData);
};
getDetail();
</script>

<style scoped></style>
