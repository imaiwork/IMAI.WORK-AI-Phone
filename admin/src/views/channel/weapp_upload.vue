<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div>
                <div class="text-[24px] font-bold text-center mt-10">一键更新您的小程序</div>
                <div class="text-center mt-4 w-[70%] mx-auto">
                    微信小程序自动化更新解决方案提供了一站式配置与部署工具，助力开发者快速完成小程序版本更新。通过标准化的云端配置流程与安全的密钥管理机制，实现代码上传、版本控制与权限管理的无缝集成，显著降低人工操作风险，提升运维效率。
                </div>
                <div class="mt-8">
                    <div class="grid grid-cols-3 gap-10">
                        <div v-for="(item, index) in steps" :key="index">
                            <div>
                                <el-progress
                                    :show-text="false"
                                    :percentage="item.id <= currStep ? 100 : 0"
                                    :status="item.id < currStep ? 'success' : ''" />
                            </div>
                            <div class="mt-4">
                                <div class="font-bold" :class="{ 'text-primary': item.id == currStep }">
                                    {{ item.title }}
                                </div>
                                <div class="mt-2">{{ item.decs }}</div>
                            </div>
                        </div>
                    </div>
                    <el-card class="!border-none mt-10">
                        <template #header>
                            <div class="text-lg font-bold">{{ steps[currStep - 1].headerTitle }}</div>
                        </template>
                        <div v-if="currStep == 1">
                            <div class="xl:w-[50%] mx-auto text-center leading-6">
                                小程序上传前，请先到本系统的<router-link
                                    :to="getRoutePath('channel.mnp_settings/setting')"
                                    class="text-[#FF6A00]"
                                    >小程序配置</router-link
                                >设置代码上传密钥，代码上传密钥需要前往<span class="text-[#FF6A00]"
                                    >微信公众开发平台</span
                                >进行下载配置，并同步设置站点的<span class="text-[#FF6A00]">白名单IP</span
                                >，如已配置，请忽略并点击下一步
                            </div>
                            <div class="flex justify-center mt-10">
                                <a href="https://mp.weixin.qq.com/" target="_blank">
                                    <el-button type="success">前往微信公众开发平台</el-button>
                                </a>
                            </div>
                        </div>
                        <div v-if="currStep == 2">
                            <el-form :model="formData" ref="formRef" label-width="150px" :rules="rules">
                                <el-form-item label="版本号" prop="upload_version">
                                    <el-input
                                        v-model="formData.upload_version"
                                        placeholder="请输入版本号，必须大于当前版本号"
                                        class="w-[380px]" />
                                </el-form-item>
                                <el-form-item label="项目更新备注" prop="upload_desc">
                                    <el-input
                                        v-model="formData.upload_desc"
                                        placeholder="请输入项目更新备注"
                                        type="textarea"
                                        class="w-[380px]"
                                        :rows="5" />
                                </el-form-item>
                                <el-form-item v-if="uploadInfo.status !== -1">
                                    <div class="bg-page w-80 p-[20px] rounded">
                                        <div class="flex items-center" v-if="uploadInfo.status == 1">
                                            <Icon name="el-icon-SuccessFilled" class="text-success" :size="18" />
                                            <span class="font-bold ml-[10px]"> 上传成功 </span>
                                        </div>
                                        <div class="flex items-center" v-if="uploadInfo.status == 0">
                                            <Icon name="el-icon-CircleCloseFilled" class="text-error" :size="18" />
                                            <span class="font-bold ml-[10px]"> 上传失败 </span>
                                        </div>
                                        <span
                                            class="msg break-words"
                                            :class="{
                                                'text-error': uploadInfo.status == 0,
                                                'text-tx-secondary': uploadInfo.status == 1,
                                            }">
                                            {{ uploadInfo.msg }}
                                        </span>
                                        <div class="text-tx-secondary text-sm">{{ uploadInfo.time }}</div>
                                    </div>
                                </el-form-item>
                            </el-form>
                        </div>
                        <div v-if="currStep == 3" class="text-center py-10">
                            <a href="https://mp.weixin.qq.com/" target="_blank">
                                <el-button type="success">前往微信公众开发平台</el-button>
                            </a>
                        </div>
                    </el-card>
                    <div class="flex justify-center mt-20">
                        <el-button v-if="currStep > 1 && currStep < 4" @click="handlePrev()">上一步</el-button>
                        <el-button v-if="currStep < 3" type="primary" @click="handleNext">下一步</el-button>
                    </div>
                </div>
            </div>
        </el-card>
    </div>
</template>
<script lang="ts" setup name="weappUpload">
import { uploadMnp, getWeappVersion as getWeappVersionApi } from "@/api/channel/weapp";
import { useLockFn } from "@/hooks/useLockFn";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";
import type { FormInstance } from "element-plus";
import cache from "@/utils/cache";
import { timeFormat } from "@/utils/util";
import { getRoutePath } from "@/router";
const steps = [
    {
        id: 1,
        title: "第一步",
        decs: "前往微信公众服务平台下载上传秘钥并配置",
        headerTitle: "前往微信公众服务平台下载上传秘钥并配置",
    },
    {
        id: 2,
        title: "第二步",
        decs: "为您的小程序同本此次的版本上传",
        headerTitle: "输入此次更新的备注并点击提交上传小程序",
    },
    {
        id: 3,
        title: "第三步",
        decs: "前往微信开发平台手动将更新包送审",
        headerTitle: "体验测试二维码，确认无误后前往公众平台提交新版本审核",
    },
];
const currStep = ref<number>(1);

const cacheKey = "mnp_upload_info";
const formData = reactive({
    upload_version: "",
    upload_desc: "",
});

const rules = {
    upload_version: [{ required: true, message: "请输入版本号" }],
    upload_desc: [{ required: true, message: "请输入项目更新备注" }],
};
const formRef = shallowRef<FormInstance>();
const uploadInfo = reactive({
    status: -1,
    msg: "",
    time: "",
});
const { lockFn, isLock } = useLockFn(async () => {
    feedback.loading("正在上传中...");
    try {
        const { code } = await uploadMnp(formData);
        uploadInfo.status = code;
        if (code === 1) {
            uploadInfo.msg = "请前往小程序后台提交审核！";
        } else {
            uploadInfo.msg = "请重新上传";
        }
    } catch (error) {
        uploadInfo.status = 0;
        uploadInfo.msg = "请重新上传";
    } finally {
        uploadInfo.time = timeFormat(Date.now(), "yyyy-mm-dd hh:MM:ss");
        cache.set(cacheKey, uploadInfo, (24 * 3600).toString());
        feedback.closeLoading();
    }
});

const handlePrev = () => {
    uploadInfo.status = -1;
    uploadInfo.msg = "";
    uploadInfo.time = "";
    cache.remove(cacheKey);
    currStep.value--;
};

const handleNext = async () => {
    if (currStep.value == 2) {
        await formRef.value?.validate();
        await lockFn();
        if (uploadInfo.status == 1) {
            currStep.value++;
        }
    } else {
        currStep.value++;
    }
};

const getWeappVersion = async () => {
    const data = await getWeappVersionApi();
    formData.upload_version = data.version;
};

onMounted(() => {
    const info = cache.get(cacheKey);
    info && Object.assign(uploadInfo, info);
    getWeappVersion();
});
</script>
