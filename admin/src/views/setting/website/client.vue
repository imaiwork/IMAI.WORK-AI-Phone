<!-- 网站信息 -->
<template>
    <div class="website-information">
        <el-card shadow="never" class="!border-none">
            <div class="text-xl font-medium mb-[20px]">基本设置</div>
            <el-alert title="提示：链接必须带上http://或https://" type="warning" />
            <el-form ref="formRef" class="ls-form mt-4" :model="formData" label-width="180px">
                <el-form-item label="Windows客户端下载地址">
                    <div class="w-[580px]">
                        <el-input
                            v-model.trim="formData.windows.url"
                            placeholder="请输入Windows客户端下载地址"
                            show-word-limit />
                        <div class="flex items-center gap-2 mt-2">
                            <span>页面是否显示：</span>
                            <el-switch
                                class="flex-shrink-0"
                                v-model="formData.windows.status"
                                active-value="1"
                                inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="Mac Intel芯片客户端下载地址">
                    <div class="w-[580px]">
                        <el-input
                            v-model.trim="formData.mac_intel.url"
                            placeholder="请输入Mac客户端下载地址"
                            show-word-limit />
                        <div class="flex items-center gap-2 mt-2">
                            <span>页面是否显示：</span>
                            <el-switch
                                class="flex-shrink-0"
                                v-model="formData.mac_intel.status"
                                active-value="1"
                                inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="Mac M芯片客户端下载地址">
                    <div class="w-[580px]">
                        <el-input
                            v-model.trim="formData.mac_apple.url"
                            placeholder="请输入Mac客户端下载地址"
                            show-word-limit />
                        <div class="flex items-center gap-2 mt-2">
                            <span>页面是否显示：</span>
                            <el-switch
                                class="flex-shrink-0"
                                v-model="formData.mac_apple.status"
                                active-value="1"
                                inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="Android客户端下载地址">
                    <div class="w-[580px]">
                        <el-input
                            v-model.trim="formData.android.url"
                            placeholder="请输入Android客户端下载地址"
                            show-word-limit />
                        <div class="flex items-center gap-2 mt-2">
                            <span>页面是否显示：</span>
                            <el-switch
                                class="flex-shrink-0"
                                v-model="formData.android.status"
                                active-value="1"
                                inactive-value="0" />
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="小程序下载二维码">
                    <div class="w-[580px] flex items-end gap-2">
                        <material-picker v-model="formData.mini_programs.url" :limit="1" />
                        <el-switch
                            class="flex-shrink-0"
                            v-model="formData.mini_programs.status"
                            active-text="启用"
                            inactive-text="禁用"
                            active-value="1"
                            inactive-value="0" />
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

        <footer-btns v-perms="['setting.web.web_setting/setClient']">
            <el-button type="primary" :loading="isLock" @click="lockFn">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="webInformation">
import { getClient, setClient } from "@/api/setting/website";
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";
import type { FormInstance } from "element-plus";
const formRef = ref<FormInstance>();

const appStore = useAppStore();
// 表单数据
const formData = reactive({
    windows: {
        url: "",
        status: "1",
    },
    mac_intel: {
        url: "",
        status: "1",
    },
    mac_apple: {
        url: "",
        status: "1",
    },
    android: {
        url: "",
        status: "1",
    },
    mini_programs: {
        url: "",
        status: "1",
    },
    h5: {
        url: "",
        status: "1",
    },
});

const getData = async () => {
    const data = await getClient();
    for (const key in formData) {
        //@ts-ignore
        formData[key] = data[key] || { url: "", status: "1" };
    }
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    await setClient(formData);
};

const { lockFn, isLock } = useLockFn(handleSubmit);

getData();
</script>

<style lang="scss" scoped></style>
