<template>
    <popup-bottom v-model:show="showPopup" title="找回密码" height="85%">
        <template #content>
            <view class="h-full px-[64rpx]">
                <view class="pt-[30rpx]">
                    <view>
                        <view>
                            <view
                                class="account-ipt form-ipt"
                                :class="{
                                    'is-focus': formInput.mobile.isFocus,
                                    'is-error': formInput.mobile.isError,
                                }">
                                <u-input
                                    class="flex-1"
                                    v-model="formData.mobile"
                                    type="number"
                                    maxlength="11"
                                    placeholder="请输入手机号"
                                    :placeholder-style="`color:${
                                        formInput.mobile.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                    };font-size:26rpx;`"
                                    @blur="formInput.mobile.isFocus = false"
                                    @focus="formInput.mobile.isFocus = true"></u-input>
                            </view>
                        </view>
                        <view
                            class="passcode-ipt form-ipt mt-[20rpx]"
                            :class="{
                                'is-focus': formInput.code.isFocus,
                                'is-error': formInput.code.isError,
                            }">
                            <u-input
                                v-model="formData.code"
                                class="flex-1"
                                placeholder="请输入验证码"
                                :placeholder-style="`color:${
                                    formInput.code.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                };font-size:26rpx;`"
                                @blur="formInput.code.isFocus = false"
                                @focus="formInput.code.isFocus = true"></u-input>
                            <view class="h-[28rpx] w-[2rpx] bg-[#0000000d]"> </view>
                            <view class="flex-shrink-0 ml-[28rpx]" @click="handleSendSms">
                                <u-verification-code
                                    ref="uCodeRef"
                                    start-text="发送验证码"
                                    change-text="发送验证码（xs）"
                                    :seconds="60"
                                    @change="codeChange" />
                                <text class="text-[26rpx]" :class="isValidMobile ? 'text-primary' : 'text-muted'">
                                    {{ codeTips }}
                                </text>
                            </view>
                        </view>
                        <view
                            class="password-ipt form-ipt mt-[20rpx]"
                            :class="{
                                'is-focus': formInput.password.isFocus,
                                'is-error': formInput.password.isError,
                            }">
                            <u-input
                                v-model="formData.password"
                                class="flex-1"
                                placeholder="6-20位数字+字母或符号组合"
                                :placeholder-style="`color:${
                                    formInput.password.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                };font-size:26rpx;`"
                                @blur="formInput.password.isFocus = false"
                                @focus="formInput.password.isFocus = true"></u-input>
                        </view>
                        <view
                            class="password-ipt form-ipt mt-[20rpx]"
                            :class="{
                                'is-focus': formInput.password_confirm.isFocus,
                                'is-error': formInput.password_confirm.isError,
                            }">
                            <u-input
                                v-model="formData.password_confirm"
                                class="flex-1"
                                placeholder="再次输入新密码"
                                :placeholder-style="`color:${
                                    formInput.password_confirm.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                };font-size:26rpx;`"
                                @blur="formInput.password_confirm.isFocus = false"
                                @focus="formInput.password_confirm.isFocus = true"></u-input>
                        </view>
                        <view class="mt-[30rpx]">
                            <u-button
                                type="primary"
                                shape="circle"
                                :custom-style="{
                                    height: '90rpx',
                                    'box-shadow': '0px 6px 12px 0px rgba(0,101,251,0.2)',
                                }"
                                @click="handleConfirmUpdate()">
                                <view class="text-[26rpx]">确认修改</view>
                            </u-button>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { forgotPassword } from "@/api/user";
import { useRouter } from "uniapp-router-next";

const props = defineProps({
    show: {
        type: Boolean,
    },
});
const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
    (event: "success"): void;
}>();

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});

enum ForgotPwdSceneEnum {
    MOBILE = "2",
}

const formData = reactive({
    email: "",
    mobile: "",
    password: "",
    scene: ForgotPwdSceneEnum.MOBILE,
    code: "",
    password_confirm: "",
});

const formInput = reactive({
    mobile: {
        isFocus: false,
        isError: false,
    },
    code: {
        isFocus: false,
        isError: false,
    },
    password: {
        isFocus: false,
        isError: false,
    },
    password_confirm: {
        isFocus: false,
        isError: false,
    },
});

const router = useRouter();

const isValidMobile = computed(() => uni.$u.test.mobile(formData.mobile));

const uCodeRef = shallowRef();
const codeTips = ref("");
const codeChange = (text: string) => {
    codeTips.value = text;
};

const validateMobile = () => {
    if (!isValidMobile.value) {
        formInput.mobile.isError = true;
        const msg = !formData.mobile ? "请输入手机号码" : "请输入正确的手机号码";
        uni.$u.toast(msg);
        return false;
    }
    formInput.mobile.isError = false;
    return true;
};

const handleSendSms = async () => {
    if (!validateMobile()) return;

    if (!uCodeRef.value?.canGetCode) return;

    try {
        await smsSend({
            scene: SMSEnum.FIND_PASSWORD,
            mobile: formData.mobile,
        });
        uni.$u.toast("发送成功");
        uCodeRef.value?.start();
    } catch (error) {
        uni.$u.toast(error || "发送失败");
    }
};

const validateForm = () => {
    if (!validateMobile()) return false;

    if (!formData.code) {
        formInput.code.isError = true;
        uni.$u.toast("请输入验证码");
        return false;
    }
    formInput.code.isError = false;

    if (!formData.password) {
        formInput.password.isError = true;
        uni.$u.toast("请输入密码");
        return false;
    }
    formInput.password.isError = false;

    if (!formData.password_confirm) {
        formInput.password_confirm.isError = true;
        uni.$u.toast("请输入确认密码");
        return false;
    }
    formInput.password_confirm.isError = false;

    if (formData.password !== formData.password_confirm) {
        formInput.password.isError = true;
        formInput.password_confirm.isError = true;
        uni.$u.toast("两次输入的密码不一致");
        return false;
    }
    formInput.password.isError = false;
    formInput.password_confirm.isError = false;

    return true;
};

const handleConfirmUpdate = async () => {
    if (!validateForm()) return;

    await forgotPassword(formData);
    setTimeout(() => {
        router.navigateBack();
    }, 1500);
};
</script>

<style scoped></style>
