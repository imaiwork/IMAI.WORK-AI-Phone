<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">配置信息</div>
            <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
                <el-form-item label="说明手册跳转链接" prop="course_url">
                    <el-input v-model="formData.course_url" class="w-[500px]" />
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['ai_application.redbook/setConfig']" type="primary" @click="lockSubmit" :loading="isLock">
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
    course_url: config.value?.app_config?.course_url,
});
const rules = {
    course_url: [
        { required: true, message: "请输入说明手册跳转链接", trigger: "blur" },
        {
            validator: (rule: any, value: any, callback: any) => {
                function isValidUrl(value: string) {
                    try {
                        new URL(value);
                        return true;
                    } catch (_) {
                        return false;
                    }
                }
                if (!isValidUrl(value)) {
                    callback(new Error("请输入正确的链接"));
                } else {
                    callback();
                }
            },
        },
    ],
};

const submit = async () => {
    await formRef.value?.validate();
    await saveConfig({
        data: formData,
        type: "app_config",
        name: "redbook",
    });
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);
</script>
