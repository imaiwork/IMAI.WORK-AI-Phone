<template>
    <div>
        <ElForm ref="formRef" :model="formData" :rules="formRules">
            <ElFormItem prop="account">
                <ElInput v-model="formData.account" placeholder="请输入手机号码" maxlength="11"> </ElInput>
            </ElFormItem>
            <ElFormItem prop="code" v-if="LoginPopupTypeEnum.MOBILE_LOGIN == loginType">
                <ElInput
                    v-model="formData.code"
                    placeholder="请输入验证码"
                    class="sms-code-input"
                    @keydown.enter="loginLock">
                </ElInput>
                <div
                    class="absolute right-[18px] top-0 h-full flex items-center before:content-[''] before:left-0 before:mr-[14px] before:w-[1px] before:h-[14px] before:bg-[rgba(0,0,0,0.05)]">
                    <VerificationCode
                        ref="verificationCodeRef"
                        class="sms-code-btn !text-[rgba(0,0,0,0.5)]"
                        @click-get="sendSms" />
                </div>
            </ElFormItem>
            <ElFormItem prop="password" v-if="LoginPopupTypeEnum.LOGIN == loginType">
                <ElInput
                    v-model="formData.password"
                    placeholder="请输入密码"
                    class="forget-password-input"
                    show-password
                    @keydown.enter="loginLock">
                </ElInput>
                <div
                    class="absolute right-[18px] top-0 h-full flex items-center before:content-[''] before:left-0 before:mr-[14px] before:w-[1px] before:h-[14px] before:bg-[rgba(0,0,0,0.05)]">
                    <div
                        class="forget-password-btn !text-[rgba(0,0,0,0.5)] text-base cursor-pointer"
                        @click="forgetPassword">
                        忘记密码
                    </div>
                </div>
            </ElFormItem>
            <ElFormItem>
                <div class="px-2">
                    <agreement ref="agreementRef" />
                </div>
            </ElFormItem>
            <ElFormItem>
                <ElButton
                    class="w-full !h-[46px] !rounded-[48px] shadow-[0_6px_12px_0_rgba(0,101,251,0.20)]"
                    type="primary"
                    :loading="isLock"
                    @click="loginLock">
                    登录
                </ElButton>
            </ElFormItem>
            <div class="mt-[30px] flex items-center justify-center text-base">
                <div
                    class="cursor-pointer"
                    :class="[
                        LoginPopupTypeEnum.MOBILE_LOGIN == loginType
                            ? 'text-[rgba(0,0,0,0.8)]'
                            : 'text-[rgba(0,0,0,0.3)]',
                    ]"
                    @click="
                        changeLoginType(LoginPopupTypeEnum.MOBILE_LOGIN);
                        formData.scene = MobileSceneEnum.CODE;
                    ">
                    验证码登录
                </div>
                <ElDivider direction="vertical" class="!mx-[30px]" />
                <div
                    class="cursor-pointer"
                    :class="[
                        LoginPopupTypeEnum.LOGIN == loginType ? 'text-[rgba(0,0,0,0.8)]' : 'text-[rgba(0,0,0,0.3)]',
                    ]"
                    @click="
                        changeLoginType(LoginPopupTypeEnum.LOGIN);
                        formData.scene = MobileSceneEnum.PASSWORD;
                    ">
                    密码登录
                </div>
            </div>
        </ElForm>
    </div>
</template>

<script setup lang="ts">
import { type FormInstance, type FormRules } from "element-plus";
import { useUserStore } from "@/stores/user";
import { login } from "@/api/account";
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { LoginPopupTypeEnum } from "@/enums/appEnums";
import { useUserLogin } from "../hooks/userLogin";

const userStore = useUserStore();

enum MobileSceneEnum {
    CODE = 2,
    PASSWORD = 1,
}

const formRef = shallowRef<FormInstance>();
const formData = reactive({
    account: "",
    code: "",
    password: "",
    scene: MobileSceneEnum.CODE,
});
const formRules: FormRules = {
    account: [
        {
            required: true,
            message: "请输入手机号",
        },
        {
            trigger: "blur",
            validator: (rule, value, callback) => {
                if (!/^1[3-9]\d{9}$/.test(value)) {
                    callback(new Error("请输入正确的手机号"));
                }
                callback();
            },
        },
    ],

    code: [
        {
            required: true,
            message: "请输入验证码",
        },
    ],
    password: [
        {
            required: true,
            message: "请输入密码",
        },
    ],
};

const { loginType, changeLoginType } = useUserLogin();

const verificationCodeRef = shallowRef();
const isSendSmsDisabled = ref(false);

const sendSms = async () => {
    await formRef.value?.validateField(["account"]);
    try {
        isSendSmsDisabled.value = true;
        await smsSend({
            scene: SMSEnum.LOGIN,
            mobile: formData.account,
        });
        verificationCodeRef.value?.start();
    } catch (error) {
        feedback.msgError(error);
    } finally {
        isSendSmsDisabled.value = false;
    }
};

const forgetPassword = () => {
    changeLoginType(LoginPopupTypeEnum.FORGOT_PWD_MOBILE);
};

const agreementRef = shallowRef();

const { lockFn: loginLock, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    if (!(await agreementRef.value?.checkAgreement())) {
        return;
    }
    const data = await login({
        ...formData,
    });
    userStore.login(data.token);
    await userStore.getUser();
    window.location.reload();
});
</script>
