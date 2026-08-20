<template>
    <view class="h-screen flex flex-col page-bg">
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            title="登录"
            title-bold>
        </u-navbar>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="h-full flex flex-col items-center pt-[15%] px-4">
                    <view class="rounded-[48rpx] bg-primary w-full">
                        <view class="px-[48rpx] pt-[60rpx] pb-[150rpx]">
                            <view class="text-white font-medium">登录后，体验更多 AI功能</view>
                            <view class="text-white opacity-50 mt-2">
                                专为企业打造的下一代 AI 工具，助你抢占先机。
                            </view>
                        </view>
                    </view>
                    <view
                        class="-mt-[100rpx] rounded-[48rpx] bg-white w-full px-4 py-[60rpx]"
                        style="box-shadow: 0px 0px 6px 4px rgba(0, 0, 0, 0.1)">
                        <view class="text-[30rpx] font-medium">
                            {{ pageTitle }}
                        </view>

                        <!-- 注册模式 + 后台关闭注册:替换为提示 + 客服二维码 -->
                        <template v-if="isRegisterMode && isRegisterClosed">
                            <view class="pt-[48rpx] pb-[20rpx] flex flex-col items-center text-center">
                                <view
                                    class="w-[112rpx] h-[112rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center">
                                    <u-icon name="lock" :size="52" color="#9CA3AF"></u-icon>
                                </view>
                                <view class="text-[30rpx] font-medium text-[#1F2937] mt-[28rpx]">
                                    系统暂时关闭了新用户注册
                                </view>
                                <view class="text-[26rpx] text-[#6B7280] leading-[1.6] mt-[12rpx]">
                                    如有疑问请联系客服，已注册账号可正常登录
                                </view>
                                <view v-if="serviceQrcode" class="mt-[32rpx] flex flex-col items-center">
                                    <image
                                        :src="serviceQrcode"
                                        mode="aspectFit"
                                        class="w-[260rpx] h-[260rpx] rounded-[16rpx] border border-[#F0F0F0]"></image>
                                    <view class="text-[26rpx] text-[#6B7280] mt-[12rpx]">扫码联系客服</view>
                                </view>
                            </view>
                            <view class="text-center text-xs mt-[40rpx]">
                                <text class="text-primary" @click="switchPageMode(PageModeEnum.LOGIN)">
                                    已有账号，去登录
                                </text>
                            </view>
                        </template>

                        <template v-else>
                        <view class="mt-[60rpx]">
                            <view
                                class="account-ipt form-ipt"
                                :class="{
                                    'is-focus': formInput.account.isFocus,
                                    'is-error': formInput.account.isError,
                                }">
                                <u-input
                                    class="flex-1"
                                    v-model="formData.account"
                                    type="number"
                                    placeholder="请输入手机号"
                                    maxlength="11"
                                    :placeholder-style="`color:${
                                        formInput.account.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                    };font-size:26rpx;`"
                                    @blur="formInput.account.isFocus = false"
                                    @focus="formInput.account.isFocus = true"></u-input>
                            </view>
                            <view
                                class="passcode-ipt form-ipt mt-[20rpx]"
                                :class="{
                                    'is-focus': formInput.code.isFocus || formInput.password.isFocus,
                                    'is-error': formInput.code.isError || formInput.password.isError,
                                }">
                                <template v-if="isCodeLogin">
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
                                    <view class="flex-shrink-0 ml-[28rpx]" @click="sendSms">
                                        <u-verification-code
                                            ref="uCodeRef"
                                            start-text="发送验证码"
                                            change-text="发送验证码（xs）"
                                            :seconds="60"
                                            @change="codeChange" />
                                        <text class="" :class="isValidMobile ? 'text-primary' : 'text-muted'">
                                            {{ codeTips }}
                                        </text>
                                    </view>
                                </template>
                                <template v-if="isPasswordLogin">
                                    <u-input
                                        v-model="formData.password"
                                        class="flex-1"
                                        type="password"
                                        placeholder="请输入密码"
                                        :placeholder-style="`color:${
                                            formInput.password.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                        };font-size:26rpx;`"
                                        @blur="formInput.password.isFocus = false"
                                        @focus="formInput.password.isFocus = true" />
                                    <view class="h-[28rpx] w-[2rpx] bg-[#0000000d] mx-3"> </view>
                                    <view class="text-muted" @click="showUpdatePassword = true">忘记密码</view>
                                </template>
                            </view>
                            <view
                                v-if="isRegisterMode && isRequireInvite"
                                class="passcode-ipt form-ipt mt-[20rpx]"
                                :class="{
                                    'is-focus': formInput.inviteCode.isFocus,
                                    'is-error': formInput.inviteCode.isError,
                                }">
                                <u-input
                                    v-model="formData.invite_code"
                                    class="flex-1"
                                    placeholder="请输入邀请码"
                                    maxlength="32"
                                    :placeholder-style="`color:${
                                        formInput.inviteCode.isError ? '#FB0000' : ' rgba(0,0,0,.2)'
                                    };font-size:26rpx;`"
                                    @blur="formInput.inviteCode.isFocus = false"
                                    @focus="formInput.inviteCode.isFocus = true"></u-input>
                            </view>
                        </view>
                        <view class="my-[48rpx]">
                            <agreement ref="agreementRef" />
                        </view>
                        <u-button
                            type="primary"
                            shape="circle"
                            :custom-style="{
                                height: '90rpx',
                                'box-shadow': '0px 6px 12px 0px rgba(0,101,251,0.2)',
                            }"
                            @click="handleLogin()">
                            <view class="">{{ isRegisterMode ? "注册并登录" : "登录" }}</view>
                        </u-button>
                        <view
                            v-if="!isRegisterMode"
                            class="flex justify-center items-center gap-x-[60rpx] mt-[40rpx] text-xs">
                            <view
                                :class="[formData.scene == MobileSceneEnum.CODE ? '' : 'text-[#B2B2B2]']"
                                @click="changeLoginScene(MobileSceneEnum.CODE)"
                                >验证码登录</view
                            >
                            <view class="h-[28rpx] w-[2rpx] bg-[#0000000d]"> </view>
                            <view
                                :class="[formData.scene == MobileSceneEnum.PASSWORD ? '' : 'text-[#B2B2B2]']"
                                @click="changeLoginScene(MobileSceneEnum.PASSWORD)"
                                >密码登录</view
                            >
                        </view>
                        <view class="text-center text-xs mt-[40rpx]">
                            <text v-if="isRegisterMode" class="text-primary" @click="switchPageMode(PageModeEnum.LOGIN)">
                                已有账号，去登录
                            </text>
                            <text v-else class="text-primary" @click="switchPageMode(PageModeEnum.REGISTER)">
                                没有账号？去注册
                            </text>
                        </view>
                        </template>
                    </view>
                </view>
            </scroll-view>
        </view>
        <update-user-info
            v-model:show="showLoginPopup"
            :logo="websiteConfig.shop_logo"
            :title="websiteConfig.shop_name"
            :userInfo="loginData"
            @update="handleUpdateUser" />
        <bind-mobile
            v-model:show="showBindMobilePopup"
            :userInfo="loginData"
            @success="bindMobileSuccess"
            @close="removeWxQuery" />
        <update-password v-model:show="showUpdatePassword" @success="bindMobileSuccess" @close="removeWxQuery" />
        <register-closed v-model:show="showRegisterClosedPopup" />
    </view>
</template>

<script setup lang="ts">
import { smsSend } from "@/api/app";
import { SMSEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import cache from "@/utils/cache";
import { USER_ID, USER_SN } from "@/enums/constantEnums";
import { useLoginWay, LoginWayEnum } from "./components/hooks";
import UpdateUserInfo from "./components/update-user-info.vue";
import BindMobile from "./components/bind-mobile.vue";
import UpdatePassword from "./components/update-password.vue";
import RegisterClosed from "./components/register-closed.vue";

const {
    loginWay,
    loginConfig,
    websiteConfig,
    isRegisterClosed,
    isRequireInvite,
    loginData,
    showLoginPopup,
    showBindMobilePopup,
    showRegisterClosedPopup,
    mobileLoginLock,
    bindMobileSuccess,
    handleUpdateUser,
    removeWxQuery,
} = useLoginWay();

loginWay.value = LoginWayEnum.MOBILE;

enum MobileSceneEnum {
    CODE = 2,
    PASSWORD = 1,
}

enum PageModeEnum {
    LOGIN = "login",
    REGISTER = "register",
}

const pageMode = ref<PageModeEnum>(PageModeEnum.LOGIN);
const isRegisterMode = computed(() => pageMode.value === PageModeEnum.REGISTER);
const pageTitle = computed(() => {
    if (isRegisterMode.value) return "新用户注册";
    return isCodeLogin.value ? "验证码登录" : "手机号登录";
});
const serviceQrcode = computed(() => websiteConfig.value?.customer_service?.wx_image || "");

const switchPageMode = (mode: PageModeEnum) => {
    pageMode.value = mode;
    formInput.account.isError = false;
    formInput.code.isError = false;
    formInput.password.isError = false;
    formInput.inviteCode.isError = false;
    // 注册强制走验证码
    if (mode === PageModeEnum.REGISTER) formData.scene = MobileSceneEnum.CODE;
};

const formData = reactive({
    scene: MobileSceneEnum.CODE,
    account: "",
    captcha: "",
    code: "",
    password: "",
    // 打开小程序若携带代理 sn / 旧版 code,自动预填,用户可修改
    invite_code: cache.get(USER_SN) || cache.get(USER_ID) || "",
    is_register: 0,
});

const formInput = reactive({
    account: {
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
    inviteCode: {
        isFocus: false,
        isError: false,
    },
});

const showUpdatePassword = ref(false);

const isCodeLogin = computed(() => formData.scene === MobileSceneEnum.CODE);
const isPasswordLogin = computed(() => formData.scene === MobileSceneEnum.PASSWORD);
const isValidMobile = computed(() => uni.$u.test.mobile(formData.account));

const changeLoginScene = (scene: MobileSceneEnum) => {
    // getCaptchaFn();
    formData.scene = scene;
};

const uCodeRef = shallowRef();
const codeTips = ref("");
const codeChange = (text: string) => {
    codeTips.value = text;
};

const sendSms = async () => {
    if (!formData.account || !isValidMobile.value) {
        formInput.account.isError = true;
        uni.$u.toast("请输入手机号码");
        return;
    }
    formInput.account.isError = false;
    if (!formData.captcha.length && loginConfig.value.is_captcha) {
        uni.$u.toast("请先输入图形验证码");
        return;
    }
    if (uCodeRef.value?.canGetCode) {
        try {
            await smsSend({
                captcha: formData.captcha,
                scene: SMSEnum.LOGIN,
                mobile: formData.account,
            });
            uni.$u.toast("发送成功");
            uCodeRef.value?.start();
        } catch (error) {
            uni.$u.toast(error || "发送失败");
        }
    }
};

const agreementRef = shallowRef();
const isAgreement = computed(() => agreementRef.value?.isActive);
const handleLogin = async () => {
    if (!formData.account) {
        formInput.account.isError = true;
        uni.$u.toast("请输入手机号码");
        return;
    }
    formInput.account.isError = false;

    if (formData.scene == MobileSceneEnum.PASSWORD) {
        if (!formData.password) {
            formInput.password.isError = true;
            uni.$u.toast("请输入密码");
            return;
        }
        formInput.password.isError = false;
    }
    if (formData.scene == MobileSceneEnum.CODE) {
        if (!formData.code) {
            formInput.code.isError = true;
            uni.$u.toast("请输入验证码");
            return;
        }
        formInput.code.isError = false;
    }
    if (isRegisterMode.value && isRequireInvite.value && !formData.invite_code) {
        formInput.inviteCode.isError = true;
        uni.$u.toast("请输入邀请码");
        return;
    }
    formInput.inviteCode.isError = false;
    if (!agreementRef.value?.checkAgreement()) return;
    formData.is_register = isRegisterMode.value ? 1 : 0;
    await mobileLoginLock(formData);
};
</script>

<style lang="scss" scoped>
:deep(.uni-input-input) {
    font-size: 26rpx;
}
</style>
