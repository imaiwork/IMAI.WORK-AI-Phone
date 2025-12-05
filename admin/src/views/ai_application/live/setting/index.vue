<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">配置信息</div>
            <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
                <el-form-item label="APK下载链接" prop="apk_url">
                    <el-input v-model="formData.apk_url" class="w-[500px]" />
                </el-form-item>
                <el-form-item label="使用说明" prop="description">
                    <el-input v-model="formData.description" class="w-[500px]" />
                </el-form-item>
                <el-form-item label="充值入口二维码" prop="recharge_entrance_qr_code">
                    <material-picker v-model="formData.recharge_entrance_qr_code" :limit="1" />
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['ai_application.live/setConfig']" type="primary" @click="lockSubmit" :loading="isLock">
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { saveConfig } from "@/api/app";
import useAppStore from "@/stores/modules/app";
import { useLockFn } from "@/hooks/useLockFn";
import type { FormInstance } from "element-plus";

const appStore = useAppStore();

const config = computed(() => appStore.config);

const formRef = shallowRef<FormInstance>();

const formData = reactive<Record<string, any>>({
    apk_url: config.value?.ai_live?.apk_url,
    description: config.value?.ai_live?.description,
    recharge_entrance_qr_code:
        config.value?.ai_live?.recharge_entrance_qr_code || config.value?.customer_service?.wx_image,
});
const rules = {
    apk_url: [{ required: true, message: "请输入APK下载链接", trigger: "blur" }],
    description: [{ required: true, message: "请输入使用说明", trigger: "blur" }],
    recharge_entrance_qr_code: [{ required: true, message: "请上传充值入口二维码", trigger: "blur" }],
};

const submit = async () => {
    await formRef.value?.validate();
    await saveConfig({
        data: formData,
        type: "ai_live",
        name: "config",
    });
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);
</script>
