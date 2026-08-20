<template>
    <div>
        <!-- 注册模式下配置未加载完成:先占位,避免闪现表单 -->
        <div v-if="isRegister && !registerModeLoaded" class="mode-loading" v-loading="true" />

        <!-- 注册模式 + 后台已关闭:整张表单禁用,只显示提示 -->
        <div v-else-if="isRegister && registerMode.closed" class="closed-notice">
            <div class="closed-icon">
                <Icon name="el-icon-Lock" :size="32" />
            </div>
            <div class="closed-title">系统暂时关闭了新用户注册</div>
            <div class="closed-desc">如有疑问请联系管理员,已注册账号可正常登录</div>
            <div v-if="serviceQrcode" class="closed-qrcode">
                <img :src="serviceQrcode" class="closed-qrcode-img" />
                <div class="closed-qrcode-tip">扫码联系客服</div>
            </div>
        </div>

        <ElForm v-else ref="formRef" :model="formData" :rules="formRules">
            <ElFormItem prop="account">
                <ElInput v-model="formData.account" placeholder="请输入手机号码" maxlength="11"> </ElInput>
            </ElFormItem>
            <ElFormItem prop="code" v-if="needCode">
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
            <!-- 邀请码:仅注册路径下、且后台要求邀请码时显示 -->
            <ElFormItem prop="invite_code" v-if="isRegister && registerMode.require_invite">
                <ElInput
                    v-model="formData.invite_code"
                    placeholder="请输入邀请码"
                    maxlength="32"
                    @keydown.enter="loginLock">
                </ElInput>
            </ElFormItem>

            <ElFormItem prop="password" v-if="!isRegister && LoginPopupTypeEnum.LOGIN == loginType">
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
                    {{ isRegister ? "注册并登录" : "登录" }}
                </ElButton>
            </ElFormItem>
            <!-- 登录路径下显示「验证码 / 密码」切换;注册路径不需要 -->
            <div v-if="!isRegister" class="mt-[30px] flex items-center justify-center text-base">
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
import { useAppStore } from "@/stores/app";
import { login, register, getRegisterMode } from "@/api/account";
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { LoginPopupTypeEnum } from "@/enums/appEnums";
import { useUserLogin } from "../hooks/userLogin";

const userStore = useUserStore();
const appStore = useAppStore();

const serviceQrcode = computed(() => appStore.getWebsiteConfig?.customer_service?.wx_image || "");

enum MobileSceneEnum {
    CODE = 2,
    PASSWORD = 1,
}

const formRef = shallowRef<FormInstance>();
const formData = reactive({
    account: "",
    code: "",
    password: "",
    invite_code: "",
    scene: MobileSceneEnum.CODE,
});

// 注册模式(关闭时禁登录新号 / 邀请码模式时显示输入框)
const registerMode = reactive({
    mode: 1,
    require_invite: false,
    require_phone: true,
    closed: false,
    default_invite_source: "",
});
const registerModeLoaded = ref(false);
(async () => {
    try {
        const res: any = await getRegisterMode();
        Object.assign(registerMode, res ?? {});
    } catch (e) {
        console.warn("[register-mode] load failed", e);
    } finally {
        registerModeLoaded.value = true;
    }
})();
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
    invite_code: [
        {
            validator: (_rule: any, value: string, callback: any) => {
                if (registerMode.require_invite && !value) {
                    callback(new Error("当前为邀请注册,必须填邀请码"));
                    return;
                }
                callback();
            },
            trigger: "blur",
        },
    ],
};

const { loginType, changeLoginType } = useUserLogin();

// 注册路径(右上角点了「注册」按钮才会进)
const isRegister = computed(() => loginType.value === LoginPopupTypeEnum.REGISTER);
// 何时需要短信验证码:验证码登录 / 注册
const needCode = computed(() => isRegister.value || loginType.value === LoginPopupTypeEnum.MOBILE_LOGIN);

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
    // 注册走独立接口;登录走 account,用户不存在直接报错
    try {
        let data: any;
        if (isRegister.value) {
            data = await register({
                account: formData.account,
                code: formData.code,
                invite_code: formData.invite_code,
            });
        } else {
            data = await login({ ...formData, is_register: 0 });
        }
        userStore.login(data.token);
        await userStore.getUser();
        window.location.reload();
    } catch (error) {
        feedback.msgError(error);
    }
});
</script>

<style scoped>
.mode-loading {
    min-height: 220px;
}
.closed-notice {
    padding: 24px 24px 0;
    text-align: center;
}
.closed-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #9ca3af;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}
.closed-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}
.closed-desc {
    font-size: 11px;
    color: #6b7280;
    line-height: 1.6;
}
.closed-qrcode {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.closed-qrcode-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}
.closed-qrcode-tip {
    margin-top: 8px;
    font-size: 13px;
    color: #6b7280;
}
</style>
