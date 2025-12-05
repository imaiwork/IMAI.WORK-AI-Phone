<template>
    <div>
        <ElTooltip
            content="返回上一步"
            :show-arrow="false"
            :popper-options="{
                modifiers: [{ name: 'offset', options: { offset: [40, 10] } }],
            }">
            <div class="absolute top-[18px] right-[18px]">
                <ElButton circle color="#f2f2f2" class="!w-10 !h-10" @click="back">
                    <Icon name="el-icon-Back" :size="20" />
                </ElButton>
            </div>
        </ElTooltip>
        <ElForm :model="formData" ref="formRef" :rules="formRules">
            <template v-if="nextStep == 1">
                <ElFormItem prop="mobile">
                    <ElInput v-model="formData.mobile" placeholder="请输入手机号码" maxlength="11"> </ElInput>
                </ElFormItem>
                <ElFormItem prop="code">
                    <ElInput
                        v-model="formData.code"
                        placeholder="请输入验证码"
                        @keydown.enter="confirmLock"
                        class="sms-code-input">
                    </ElInput>
                    <div
                        class="absolute right-[18px] top-0 h-full flex items-center before:content-[''] before:left-0 before:mr-[14px] before:w-[1px] before:h-[14px] before:bg-[rgba(0,0,0,0.05)]">
                        <VerificationCode
                            ref="verificationCodeRef"
                            class="sms-code-btn !text-[rgba(0,0,0,0.5)]"
                            @click-get="sendSms" />
                    </div>
                </ElFormItem>
            </template>
            <template v-if="nextStep == 2">
                <ElFormItem prop="password">
                    <ElInput v-model="formData.password" type="password" show-password placeholder="设置新密码">
                    </ElInput>
                </ElFormItem>
                <ElFormItem prop="password_confirm">
                    <ElInput v-model="formData.password_confirm" type="password" show-password placeholder="确认新密码">
                    </ElInput>
                </ElFormItem>
            </template>
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
                    @click="confirmLock">
                    {{ nextStep == 1 ? "下一步" : "确定" }}
                </ElButton>
            </ElFormItem>
            <ElFormItem>
                <div class="text-[rgba(0,0,0,0.30)] text-base text-center w-full">
                    <template v-if="nextStep == 1">
                        <span class="text-[rgba(0,0,0,0.50)]">第一步：</span>请输入绑定的手机号，接收验证码
                    </template>
                    <template v-else-if="nextStep == 2">
                        <span class="text-[rgba(0,0,0,0.50)]">第二步：</span>输入新密码，确认无误后重置密码
                    </template>
                </div>
            </ElFormItem>
        </ElForm>
    </div>
</template>

<script setup lang="ts">
import { type FormInstance, type FormRules } from "element-plus";
import { forgotPassword } from "@/api/user";
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { useUserLogin } from "../hooks/userLogin";
import { LoginPopupTypeEnum } from "@/enums/appEnums";

enum ForgotPwdSceneEnum {
    MOBILE = "2",
}

const nextStep = ref(1);

const formRef = shallowRef<FormInstance>();
const formData = reactive({
    mobile: "",
    password: "",
    code: "",
    password_confirm: "",
    scene: ForgotPwdSceneEnum.MOBILE,
});
const formRules: FormRules = {
    mobile: [
        {
            required: true,
            message: "请输入手机号",
        },
    ],
    password: [
        {
            required: true,
            message: "请输入密码",
        },
    ],
    password_confirm: [
        {
            required: true,
            message: "请输入确认密码",
        },
        {
            validator: (rule, value, callback) => {
                if (value != formData.password) {
                    callback(new Error("两次密码不一致"));
                } else {
                    callback();
                }
            },
        },
    ],
    code: [
        {
            required: true,
            message: "请输入验证码",
        },
    ],
};

const { changeLoginType } = useUserLogin();

const verificationCodeRef = shallowRef();
const isSendSms = ref(false);
const sendSms = async () => {
    await formRef.value?.validateField(["mobile"]);
    try {
        await smsSend({
            scene: SMSEnum.FIND_PASSWORD,
            mobile: formData.mobile,
        });
        isSendSms.value = true;
        verificationCodeRef.value?.start();
    } catch (error) {
        feedback.msgError(error || "发送失败");
    }
};

const agreementRef = shallowRef();

const { lockFn: confirmLock, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    if (!(await agreementRef.value?.checkAgreement())) return;
    if (nextStep.value == 1) {
        if (!isSendSms.value) {
            feedback.msgError("请点击【发送验证码】");
            return;
        } else {
            nextStep.value = 2;
        }
    } else {
        const data = await forgotPassword({
            ...formData,
        });
        changeLoginType(LoginPopupTypeEnum.LOGIN);
    }
});

const back = () => {
    if (nextStep.value == 1) {
        changeLoginType(LoginPopupTypeEnum.LOGIN);
    } else {
        nextStep.value = 1;
    }
};
</script>

<style scoped lang="scss"></style>
