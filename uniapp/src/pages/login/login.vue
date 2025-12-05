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
                            :src="websiteConfig.pc_logo"
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
            :logo="websiteConfig.shop_logo"
            :title="websiteConfig.shop_name"
            :userInfo="loginData"
            @update="handleUpdateUser" />
        <bind-mobile
            v-model:show="showBindMobilePopup"
            :userInfo="loginData"
            @success="bindMobileSuccess"
            @close="removeWxQuery" />
    </view>
</template>

<script setup lang="ts">
import Weixin from "./components/weixin.vue";
import UpdateUserInfo from "./components/update-user-info.vue";
import BindMobile from "./components/bind-mobile.vue";
import { useLoginWay, LoginWayEnum } from "./components/hooks";

const {
    loginWay,
    websiteConfig,
    loginData,
    showLoginPopup,
    showBindMobilePopup,
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

const wxLogin = async (res: any) => {
    await wxLoginLock(res);
    if (authKey.value) {
        pcLogin({ ...res, authKey: authKey.value });
    }
};

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
