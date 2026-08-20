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
            <div class="font-medium mb-7">用户注册方式</div>
            <el-form :model="registerFormData" label-width="120px">
                <el-form-item label="注册模式">
                    <el-radio-group v-model="registerFormData.register_mode" class="register-mode-group">
                        <el-radio :value="1" class="register-mode-item">
                            <div class="rm-title">开放手机号注册</div>
                            <div class="rm-desc">手机号 + 验证码即可注册,任何人都能进入</div>
                        </el-radio>
                        <el-radio :value="2" class="register-mode-item">
                            <div class="rm-title">邀请码注册</div>
                            <div class="rm-desc">
                                手机号 + 验证码 + 邀请码三项都通过才可注册,保证每个新用户都有明确推荐人
                            </div>
                        </el-radio>
                        <el-radio :value="4" class="register-mode-item">
                            <div class="rm-title">关闭注册</div>
                            <div class="rm-desc">全站暂停新增账号,已有账号正常使用</div>
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="默认邀请来源" v-if="[1, 2].includes(registerFormData.register_mode)">
                    <div class="invite-source-wrap">
                        <el-input
                            class="!w-[260px]"
                            v-model="registerFormData.default_invite_source"
                            placeholder="留空显示为「系统」" />
                        <div class="form-tips">新用户没填邀请码时,在用户列表里把"邀请来源"这一栏显示成这个名字。</div>
                    </div>
                </el-form-item>
                <el-form-item>
                    <el-button
                        v-perms="['setting.user.user/setConfig']"
                        type="primary"
                        :loading="isRegisterLock"
                        @click="saveRegisterFormLock"
                        >保存</el-button
                    >
                </el-form-item>
            </el-form>
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
import useAppStore from "@/stores/modules/app";

const appStore = useAppStore();

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

// ─── 注册方式 ───────────────────────────────────────────
// 1=开放手机号 / 2=邀请码 / 3=手机号+邀请码 / 4=关闭
const registerFormData = reactive<{ register_mode: number; default_invite_source: string }>({
    register_mode: 1,
    default_invite_source: "",
});

const getRegisterConfig = () => {
    const r = appStore.config?.register ?? {};
    // 兼容历史值:旧的「手机号+邀请码」(=3)已合并进「邀请码」(=2)
    let m = Number(r.register_mode ?? 1) || 1;
    if (m === 3) m = 2;
    registerFormData.register_mode = m;
    registerFormData.default_invite_source = String(r.default_invite_source ?? "");
};

const saveRegisterForm = async () => {
    await saveConfig({
        type: "user",
        name: "register",
        data: registerFormData,
    });
    await appStore.getConfig();
    getRegisterConfig();
};
const { lockFn: saveRegisterFormLock, isLock: isRegisterLock } = useLockFn(saveRegisterForm);
getRegisterConfig();

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

<style lang="scss" scoped>
// 注册模式选择:垂直列表,每条一行
.register-mode-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
}
.register-mode-item {
    display: flex;
    align-items: flex-start;
    margin: 0;
    padding: 12px 14px;
    border: 1px solid #ebeef5;
    border-radius: 8px;
    background: #fff;
    height: auto;
    width: 100%;
    transition: all 0.15s;
    cursor: pointer;
    :deep(.el-radio__input) {
        margin-top: 2px;
    }
    :deep(.el-radio__label) {
        padding-left: 10px;
        flex: 1;
        white-space: normal;
    }
    &:hover {
        border-color: #c6e2ff;
        background: #f5f7fa;
    }
    &.is-checked {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
    }
    .rm-title {
        font-size: 14px;
        font-weight: 500;
        color: #303133;
        line-height: 1.4;
    }
    .rm-desc {
        margin-top: 4px;
        font-size: 12px;
        color: #909399;
        line-height: 1.5;
    }
    &.is-checked .rm-title {
        color: var(--el-color-primary);
    }
    &.is-checked .rm-desc {
        color: #5b88c5;
    }
}
.invite-source-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
}
.form-tips {
    font-size: 12px;
    color: #909399;
    line-height: 1.5;
}
</style>
