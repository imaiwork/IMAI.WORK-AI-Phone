<!-- 网站信息 -->
<template>
    <div class="user-setup">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-7">基本设置</div>
            <el-form ref="formRef" :model="formData" label-width="120px">
                <el-form-item label="用户默认头像">
                    <div>
                        <material-picker v-model="formData.default_avatar" :limit="1" />
                    </div>
                </el-form-item>
                <el-form-item>
                    <div>
                        <div class="form-tips">
                            用户注册时给的默认头像，建议尺寸：400*400像素，支持jpg，jpeg，png格式
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="赠送算力值">
                    <el-input type="number" class="w-64" :min="0" v-model="formData.default_tokens" placeholder="">
                        <template #append>算力值</template>
                    </el-input>
                </el-form-item>
            </el-form>
            <el-button
                v-perms="['setting.user.user/setConfig']"
                type="primary"
                :loading="isBaseLock"
                @click="handleBaseSubmit"
                >保存</el-button
            >
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="font-medium mb-7">问卷设置</div>
            <div>
                <el-form :model="formData" label-width="100px">
                    <el-form-item label="是否启用" prop="enable">
                        <el-switch v-model="surveyFormData.enable" inactive-value="0" active-value="1" />
                    </el-form-item>
                    <el-form-item label="提醒天数" prop="remind_days">
                        <el-input v-model="surveyFormData.remind_days" class="w-[120px]" type="number" :min="1" />
                    </el-form-item>
                </el-form>
                <el-button
                    v-perms="['setting.user.user/setConfig']"
                    type="primary"
                    :loading="isSurveyLock"
                    @click="saveSurveyFormLock"
                    >保存</el-button
                >
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="userSetup">
import { getConfig, saveConfig } from "@/api/app";
import { getUserSetup, setUserSetup } from "@/api/setting/user";
import { useLockFn } from "@/hooks/useLockFn";

// 问卷设置表单数据
const surveyFormData = reactive({
    enable: "0", // 是否启用
    remind_days: "", // 提醒天数
});

// 获取配置
const getSurveyConfig = async () => {
    const { survey } = await getConfig();
    surveyFormData.enable = survey.enable;
    surveyFormData.remind_days = survey.remind_days;
};

const saveSurveyForm = async () => {
    await saveConfig({
        type: "website",
        name: "survey",
        data: surveyFormData,
    });
    getSurveyConfig();
};

const { lockFn: saveSurveyFormLock, isLock: isSurveyLock } = useLockFn(saveSurveyForm);

// 表单数据
const formData = reactive({
    default_avatar: "", // 用户默认头像
    default_tokens: "", // 用户默认算力值
});

// 获取用户设置数据
const getData = async () => {
    try {
        const data = await getUserSetup();
        for (const key in formData) {
            //@ts-ignore
            formData[key] = data[key];
        }
    } catch (error) {
        console.log("获取=>", error);
    }
};

// 保存用户设置数据
const handleSubmit = async () => {
    try {
        await setUserSetup(formData);
        getData();
    } catch (error) {
        console.log("保存=>", error);
    }
};

const { lockFn: handleBaseSubmit, isLock: isBaseLock } = useLockFn(handleSubmit);

getSurveyConfig();
getData();
</script>

<style lang="scss" scoped></style>
