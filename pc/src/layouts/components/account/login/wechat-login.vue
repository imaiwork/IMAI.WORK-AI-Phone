<template>
    <div class="h-full flex flex-col items-center justify-center">
        <div
            class="w-[200px] h-[200px] rounded-xl p-1 border border-token-primary relative overflow-hidden"
            v-loading="loading">
            <img :src="qrcode" class="w-full h-full" v-if="!loading" />
            <div
                v-if="isScanSuccess"
                class="absolute top-0 left-0 w-full h-full bg-black/5 flex flex-col items-center justify-center text-white">
                <div class="text-white">扫码成功</div>
            </div>
        </div>
        <div class="mt-5 text-base">微信扫一扫登录</div>
        <ElTooltip content="打开微信扫一扫登录，然后点击一键授权登录即可">
            <div
                class="mt-[18px] flex items-center gap-x-1 rounded-full border border-token-primary px-2 py-1 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path
                        opacity="0.3"
                        d="M5 1.5L9 1.5C10.3807 1.5 11.5 2.61929 11.5 4L11.5 10C11.5 11.3807 10.3807 12.5 9 12.5L5 12.5C3.61929 12.5 2.5 11.3807 2.5 10L2.5 4C2.5 2.61929 3.61929 1.5 5 1.5Z"
                        stroke="black" />
                    <path opacity="0.5" d="M5 10L9 10" stroke="black" />
                    <circle opacity="0.8" cx="7" cy="6" r="2" fill="black" />
                </svg>
                <span class="text-[11px]">如何扫码</span>
            </div>
        </ElTooltip>
    </div>
</template>

<script setup lang="ts">
import { getMnpQrcode } from "@/api/app";
import { getMnpScanStatus } from "@/api/account";
import { useUserLogin } from "../hooks/userLogin";
import { LoginPopupTypeEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();

const { loginType, changeLoginType } = useUserLogin();

const qrcode = ref("");
const authKey = ref("");
const loading = ref(true);

const getCode = async () => {
    loading.value = true;
    try {
        const { url, auth_key } = await getMnpQrcode({
            path: "pages/login/login",
            mnp_auth: "1",
        });
        qrcode.value = url;
        authKey.value = auth_key;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const loopTimer = ref(null);
const isScanSuccess = ref(false);

watch(
    () => loginType.value,
    (newVal) => {
        if (newVal == LoginPopupTypeEnum.WECHAT_LOGIN) {
            getCode();
            // 轮询查询扫码状态
            loopTimer.value = setInterval(async () => {
                const { token } = await getMnpScanStatus({
                    auth_key: authKey.value,
                });
                if (token) {
                    isScanSuccess.value = true;
                    userStore.login(token);
                    clearInterval(loopTimer.value);
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            }, 1500);
        } else {
            clearInterval(loopTimer.value);
            isScanSuccess.value = false;
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
