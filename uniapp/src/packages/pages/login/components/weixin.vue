<template>
    <view class="my-[30rpx]">
        <!-- #ifdef MP -->
        <!-- 关闭注册时仍走一键登录:授权后由后端区分新老用户,新用户才弹窗 -->
        <u-button
            class=""
            type="primary"
            shape="circle"
            :open-type="canGetPhoneNumber ? 'getPhoneNumber' : ''"
            :custom-style="{
                height: '90rpx',
                'box-shadow': '0px 6px 12px 0px rgba(0,101,251,0.2)',
                marginBottom: authKey ? '120rpx' : '0rpx',
            }"
            @click="handleLogin"
            @getphonenumber="getPhoneNumber">
            <text class="">一键登录</text>
        </u-button>
        <!-- #endif -->
        <template v-if="!authKey">
            <view class="mt-4" v-if="showOtherWayBtn || isRegisterClosed">
                <u-button
                    shape="circle"
                    :custom-style="{
                        height: '90rpx',
                        border: '1rpx solid #DCDDDE',
                        backgroundColor: 'transparent',
                    }"
                    @click="changeLoginWay()">
                    <view class="text-[#C0C1C2]">手机号登录</view>
                </u-button>
            </view>
            <view class="my-[60rpx] flex justify-center">
                <agreement ref="agreementRef" />
            </view>
            <view class="text-center text-[20rpx] text-[#B3B4B5] pb-6 pt-4" style="border-top: 1rpx solid #eff0f1">
                <text>本小程序需要用户进行手机号码授权登录后才能正常使用</text>
            </view>
        </template>
    </view>
</template>
<script setup lang="ts">
import { getMobileNumber } from "@/api/account";
import { useLoginWay } from "./hooks";

const props = defineProps<{
    loading: boolean;
    authKey: string;
}>();
const emit = defineEmits<{
    (event: "login", data?: any): void;
}>();

const { showOtherWayBtn, isRegisterClosed } = useLoginWay();

const agreementRef = shallowRef();
const isAgreement = computed(() => agreementRef.value?.isActive);
const canGetPhoneNumber = computed(() => !!(isAgreement.value || props.authKey));

const handleLogin = () => {
    agreementRef.value?.checkAgreement();
};

const getPhoneNumber = async (e: any) => {
    const { encryptedData, iv, code, errno, errMsg } = e.detail;
    try {
        if (!code) throw errMsg;
        uni.showLoading({
            title: "获取中...",
            mask: true,
        });
        const res = await getMobileNumber({
            code,
        });
        uni.hideLoading();
        emit("login", res);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "获取手机号失败",
            icon: "none",
            duration: 5000,
        });
    }
};

const changeLoginWay = () => {
    uni.$u.route({
        url: "/packages/pages/login/mobile",
        type: "redirect",
    });
};
</script>
<style scoped lang="scss"></style>
