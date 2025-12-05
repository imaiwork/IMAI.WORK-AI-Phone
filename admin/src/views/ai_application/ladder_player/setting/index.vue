<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-5">基本配置</div>
            <el-form :model="formData" ref="formRef" :rules="rules" label-width="140px">
                <el-form-item label="分析报告配置" prop="directions">
                    <div class="w-[380px] rounded border border-[rgba(220,223,230,1)] p-4">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="(item, index) in formData.directions"
                                :key="index"
                                class="flex items-center gap-4">
                                <div>分析考察方向{{ index + 1 }}</div>
                                <div class="flex-1">
                                    <el-input
                                        v-model="formData.directions[index]"
                                        maxlength="7"
                                        :placeholder="`请输入分析考察方向${index + 1}`" />
                                </div>
                            </div>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="随机头像素材库" prop="avatars">
                    <material-picker v-model="formData.avatars" :limit="20" />
                </el-form-item>
                <el-form-item label="音色管理" prop="voice">
                    <div class="flex flex-wrap gap-2">
                        <div v-for="(item, index) in formData.voice" :key="index" class="">
                            <material-picker v-model="item.logo" />
                            <div class="flex items-center gap-2">
                                <div>{{ item.name }}</div>
                                <div>
                                    <el-switch v-model="item.status" inactive-value="0" active-value="1" />
                                </div>
                            </div>
                        </div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['ai_application.lp/setConfig']" type="primary" :loading="isLock" @click="lockSubmit"
            >保存</el-button
        >
    </footer-btns>
</template>

<script setup lang="ts">
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";
import type { FormInstance } from "element-plus";
import { saveConfig } from "@/api/app";

const appStore = useAppStore();

const formData = computed(
    () =>
        appStore.config.lianlian || {
            avatars: [],
            voice: [],
            directions: [],
        }
);
const formRef = shallowRef<FormInstance>();
const rules = {
    avatars: [{ required: true, message: "请选择随机头像素材库", trigger: "blur" }],
    voice: [{ required: true, message: "请填写音色管理", trigger: "blur" }],
    directions: [
        { required: true, message: "请输入分析报告配置", trigger: "blur" },
        // 分析报告配置数据都不能为空
        {
            validator: (rule: any, value: any, callback: any) => {
                const emptyIndex = value.findIndex((item: any) => !item);
                if (emptyIndex !== -1) {
                    callback(new Error(`请输入分析报告配置${emptyIndex + 1}`));
                    return;
                }
                callback();
            },
            trigger: "blur",
        },
    ],
};

const handleLanguageAdd = () => {
    // 判断上个元素是否为空, 否则提示
    if (!formData.value.language[formData.value.language.length - 1] && formData.value.language.length > 0) {
        feedback.msgError("请先填写上个元素");
        return;
    }
    formData.value.language.push("");
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    await saveConfig({
        type: "lianlian",
        name: "config",
        data: formData.value,
    });
    appStore.getConfig();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);
</script>

<style scoped></style>
