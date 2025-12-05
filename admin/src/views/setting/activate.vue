<template>
    <div>
        <el-card class="!border-none mb-4" shadow="never">
            <el-alert type="warning" show-icon :closable="false">
                <div class="text-sm">
                    注意：请妥善保管授权信息，后期需更换授权账号，只修改账号即可，授权密码不会修改！
                </div>
            </el-alert>
        </el-card>
        <el-form :model="formData" :rules="rules" ref="formRef" label-width="100px">
            <el-card class="mb-4 !border-none" shadow="never">
                <div>
                    <div class="text-xl font-medium mb-[20px]">配置信息</div>
                    <div>
                        <el-form-item label="授权账号" prop="cdkey">
                            <div>
                                <el-input v-model="formData.cdkey" class="w-[380px]" />
                            </div>
                        </el-form-item>
                    </div>
                    <el-button v-perms="['setting.activate/setConfig']" type="primary" @click="handleSubmit"
                        >保存</el-button
                    >
                </div>
            </el-card>
            <el-card class="mb-4 !border-none" shadow="never">
                <div class="flex justify-between">
                    <div class="text-xl font-medium">自定义标识</div>
                    <div>
                        <template v-if="config.is_auth_by == 1">
                            <el-button type="primary" @click="handleBuy">修改版权信息</el-button>
                        </template>
                        <template v-else>
                            <el-popover width="200">
                                <template #reference>
                                    <el-button type="primary">联系客服自定义标识</el-button>
                                </template>
                                <div class="flex justify-center items-center">
                                    <img class="w-[150px] h-[150px]" src="@/assets/images/support.png" />
                                </div>
                            </el-popover>
                        </template>
                    </div>
                </div>
            </el-card>
        </el-form>
    </div>
    <popup ref="popupRef" title="版权信息修改" width="500px" async @confirm="handleSaveAuth">
        <div>
            <el-form :model="authFormData" :rules="authRules" ref="formRef" label-width="100px">
                <el-form-item label="技术标识" prop="byname">
                    <el-input v-model="authFormData.byname" class="w-[380px]" />
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { saveAuthByName } from "@/api/app";
import type { FormInstance } from "element-plus";
import useAppStore from "@/stores/modules/app";
import { rechargeSecret } from "@/api/marketing/recharge";
import feedback from "@/utils/feedback";
const appStore = useAppStore();
const { config } = toRefs(appStore);

const getModelConfig = computed(() => {
    const { model_key } = config.value;
    return model_key;
});

const formRef = shallowRef<FormInstance>();
const formData = reactive({
    cdkey: "",
    project_key: "",
});

const rules = {
    cdkey: [{ required: true, message: "请输入授权账号" }],
};

const popupRef = shallowRef();
const authFormData = reactive({
    byname: config.value.by_name,
});

const authRules = {
    byname: [{ required: true, message: "请输入技术标识" }],
};

const handleBuy = () => {
    popupRef.value?.open();
};

const handleSaveAuth = async () => {
    await formRef.value?.validate();
    try {
        await saveAuthByName(authFormData);
        appStore.getConfig();
        popupRef.value?.close();
    } catch (error: any) {
        feedback.msgError(error);
    }
};

const setFormData = () => {
    formData.cdkey = getModelConfig.value?.api_key || "";
    formData.project_key = getModelConfig.value?.project_key || "";
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    await rechargeSecret(formData);
    appStore.getConfig();
};

onMounted(async () => {
    await appStore.getConfig();
    setFormData();
});
</script>

<style scoped></style>
