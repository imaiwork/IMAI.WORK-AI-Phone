<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="mt-4">
                <div class="text-xl font-medium mb-[20px]">客服配置</div>
                <el-form :model="formData" ref="formRef" label-width="120px">
                    <el-form-item label="客服标题" prop="title">
                        <el-input v-model="formData.title" class="w-[380px]" maxlength="20"></el-input>
                    </el-form-item>
                    <el-form-item label="客服时间" prop="time">
                        <el-input v-model="formData.time" class="w-[380px]" maxlength="50"></el-input>
                    </el-form-item>
                    <el-form-item label="客服电话" prop="phone">
                        <el-input v-model="formData.phone" type="tel" class="w-[380px]" maxlength="50"></el-input>
                    </el-form-item>
                    <el-form-item label="客服二维码" prop="logo">
                        <div>
                            <material-picker v-model="formData.wx_image" />
                            <div class="form-tips">建议图片尺寸：200*200像素；图片格式：jpg、png、jpeg</div>
                        </div>
                    </el-form-item>
                    <el-form-item label="飞书客服二维码" prop="logo" v-if="false">
                        <div>
                            <material-picker v-model="formData.fs_image" />
                            <div class="form-tips">建议图片尺寸：200*200像素；图片格式：jpg、png、jpeg</div>
                        </div>
                    </el-form-item>
                </el-form>
            </div>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['setting.service/setConfig']" type="primary" @click="lockFn" :loading="isLock"
            >保存</el-button
        >
    </footer-btns>
</template>

<script setup lang="ts">
import { saveConfig } from "@/api/app";
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";
import { cloneDeep } from "lodash-es";

const { config, getConfig } = useAppStore();
const { customer_service } = toRefs(config);

const formData = reactive({
    wx_image: "",
    fs_image: "",
    title: "",
    time: "",
    phone: "",
});

const setFormData = () => {
    if (customer_service.value) {
        Object.keys(formData).forEach((key) => {
            // @ts-ignore
            formData[key] = customer_service.value[key];
        });
    }
};

const handleSave = async () => {
    await saveConfig({
        type: "website",
        name: "customer_service",
        data: formData,
    });
    await getConfig();
};

const { lockFn, isLock } = useLockFn(handleSave);

setFormData();
</script>

<style scoped></style>
