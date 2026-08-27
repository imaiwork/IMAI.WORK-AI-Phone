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
                <view class="h-full flex flex-col">
                    <view class="flex justify-center items-center grow min-h-0">
                        <image
                            :src="siteLogo"
                            mode="aspectFit"
                            class="h-[88rpx] w-[88rpx] rounded-full"></image>
                    </view>
                    <view class="w-full px-[60rpx]">
                        <weixin @login="wxLogin" :loading="wxIsLock" :auth-key="authKey" />
                    </view>
                </view>
            </scroll-view>
        </view>
        <update-user-info
            v-model:show="showLoginPopup"
            :logo="siteShopLogo"
            :title="siteName"
            :userInfo="loginData"
            :require-invite="needInviteCode"
            :invite-code="defaultInviteCode"
            @update="handleUpdateUser" />
        <bind-mobile
            v-model:show="showBindMobilePopup"
            :userInfo="loginData"
            @success="bindMobileSuccess"
            @close="removeWxQuery" />
        <register-closed v-model:show="showRegisterClosedPopup" />
    </view>
</template>

<script setup lang="ts">
import Weixin from "./components/weixin.vue";
import UpdateUserInfo from "./components/update-user-info.vue";
import BindMobile from "./components/bind-mobile.vue";
import RegisterClosed from "./components/register-closed.vue";
import { useLoginWay, LoginWayEnum } from "./components/hooks";

const {
    loginWay,
    siteLogo,
    siteShopLogo,
    siteName,
    loginData,
    showLoginPopup,
    showBindMobilePopup,
    showRegisterClosedPopup,
    needInviteCode,
    defaultInviteCode,
    wxIsLock,
    isLoginAfter,
    bindMobileSuccess,
    handleUpdateUser,
    wxLoginLock,
    pcLogin,
    removeWxQuery,
} = useLoginWay();

loginWay.value = LoginWayEnum.WEIXIN;

const authKey = ref("");
// 扫码登录命中「补邀请码注册」时,先扣住授权结果,等注册拿到 token 再回传 PC
const pendingPcRes = ref<any>(null);

const wxLogin = async (res: any) => {
    await wxLoginLock(res);
    if (!authKey.value) return;
    if (needInviteCode.value) {
        pendingPcRes.value = res;
        return;
    }
    pcLogin({ ...res, authKey: authKey.value });
};

// 邀请码补完(注册成功)后再回传 PC;用户取消弹窗则没有 token,直接丢弃
watch(needInviteCode, (val) => {
    if (val || !pendingPcRes.value) return;
    const res = pendingPcRes.value;
    pendingPcRes.value = null;
    if (!loginData.value?.token) return;
    pcLogin({ ...res, authKey: authKey.value });
});

onLoad((options: any) => {
    const scene = decodeURIComponent(options.scene);
    const parameters = scene.split("&");
    const queryParams: any = {};
    parameters.forEach((param) => {
        const [key, value] = param.split("=");
        // @ts-ignore
        queryParams[key] = value;
    });
    if (queryParams.auth_key) {
        authKey.value = queryParams.auth_key;
        isLoginAfter.value = false;
    }
});
</script>

<style lang="scss"></style>
