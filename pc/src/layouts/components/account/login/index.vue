<template>
    <div class="h-full flex flex-col px-[30px] relative">
        <ElTooltip
            v-if="
                [LoginPopupTypeEnum.LOGIN, LoginPopupTypeEnum.MOBILE_LOGIN, LoginPopupTypeEnum.WECHAT_LOGIN].includes(
                    loginType,
                )
            "
            :content="loginType == LoginPopupTypeEnum.WECHAT_LOGIN ? '切换账号登录' : '微信扫码登录'"
            :show-arrow="false"
            :popper-options="{
                modifiers: [{ name: 'offset', options: { offset: [40, 10] } }],
            }">
            <div class="absolute w-10 h-10 right-[18px] top-[18px] cursor-pointer" @click="toggleLoginType">
                <Icon name="local-icon-scan" :size="40" color="var(--color-primary)"></Icon>
            </div>
        </ElTooltip>
        <div class="pt-[44px]">
            <div class="text-[20px] font-medium">
                <template v-if="loginType === LoginPopupTypeEnum.LOGIN"> 手机号登录 </template>
                <template v-else-if="loginType === LoginPopupTypeEnum.MOBILE_LOGIN"> 验证码登录 </template>
                <template v-else-if="loginType === LoginPopupTypeEnum.REGISTER"> 新用户注册 </template>
                <template v-else-if="loginType === LoginPopupTypeEnum.FORGOT_PWD_MOBILE"> 找回密码 </template>
            </div>
            <div
                class="text-xs text-[#0000004d] mt-2"
                v-if="[LoginPopupTypeEnum.LOGIN, LoginPopupTypeEnum.MOBILE_LOGIN].includes(loginType)">
                请使用已注册的账号登录。还没有账号?点击右上角「注册」。
            </div>
            <div class="text-xs text-[#0000004d] mt-2" v-if="loginType === LoginPopupTypeEnum.REGISTER">
                完成验证后将自动登录,无需重复操作。
            </div>
            <div class="text-xs text-[#0000004d] mt-2" v-if="LoginPopupTypeEnum.FORGOT_PWD_MOBILE == loginType">
                简单验证即可立即重设新密码,为你的账户安全护航。
            </div>
            <div class="mt-4">
                <mobile-login
                    v-show="
                        [
                            LoginPopupTypeEnum.LOGIN,
                            LoginPopupTypeEnum.MOBILE_LOGIN,
                            LoginPopupTypeEnum.REGISTER,
                        ].includes(loginType)
                    " />
                <forget-pwd v-show="LoginPopupTypeEnum.FORGOT_PWD_MOBILE == loginType" />
                <wechat-login v-show="LoginPopupTypeEnum.WECHAT_LOGIN == loginType" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { LoginPopupTypeEnum } from "@/enums/appEnums";
import MobileLogin from "./mobile-login.vue";
import ForgetPwd from "./forget-pwd.vue";
import WechatLogin from "./wechat-login.vue";
import { useUserLogin } from "../hooks/userLogin";

const { loginType, changeLoginType } = useUserLogin();

const toggleLoginType = () => {
    if (loginType.value == LoginPopupTypeEnum.WECHAT_LOGIN) {
        changeLoginType(LoginPopupTypeEnum.LOGIN);
    } else {
        changeLoginType(LoginPopupTypeEnum.WECHAT_LOGIN);
    }
};
</script>
