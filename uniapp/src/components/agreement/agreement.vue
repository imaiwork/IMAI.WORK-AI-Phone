<template>
    <view class="agreement" :class="{ shake: isShake }">
        <view class="">
            <u-checkbox v-model="isActive" shape="circle" size="26">
                <view class="text-xs flex">
                    已阅读并同意
                    <view @click.stop class="text-primary">
                        <router-navigate to="/packages/pages/agreement/agreement?type=service">
                            服务协议
                        </router-navigate>
                    </view>
                    和
                    <view @click.stop class="text-primary">
                        <router-navigate to="/packages/pages/agreement/agreement?type=privacy">
                            隐私协议
                        </router-navigate>
                    </view>
                </view>
            </u-checkbox>
        </view>
    </view>
    <u-popup v-model="showConfirm" mode="center" border-radius="24" width="90%">
        <view class="p-[28rpx]">
            <view class="font-bold text-[30rpx] text-center mt-4">温馨提示</view>
            <view class="text-[#00000080] w-[62%] text-center text-[26rpx] mx-auto mt-5 leading-6">
                请勾选已阅读并同意《服务协议》和《隐私协议》
            </view>
            <view class="mt-[48rpx]">
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{ height: '90rpx', 'box-shadow': '0px 6px 12px 0px rgba(0,101,251,0.2)' }"
                    @click="confirmActive"
                    >确定</u-button
                >
            </view>
        </view>
    </u-popup>
</template>
<script lang="ts" setup>
import { useAppStore } from "@/stores/app";
import { computed, ref } from "vue";
const appStore = useAppStore();
const isActive = ref(false);
const isShake = ref(false);
const isOpenAgreement = computed(() => appStore.getLoginConfig.login_agreement == 1);

const showConfirm = ref<boolean>(false);

const checkAgreement = () => {
    if (!isActive.value && isOpenAgreement.value) {
        showConfirm.value = true;
    } else if (!isOpenAgreement.value) {
        return true;
    }
    return isActive.value;
};

const confirmActive = () => {
    isActive.value = true;
    showConfirm.value = false;
};

defineExpose({
    isActive,
    checkAgreement,
});
</script>

<style lang="scss">
.shake {
    animation: shake 0.82s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
    transform: translate3d(0, 0, 0);
}
@keyframes shake {
    10%,
    90% {
        transform: translate3d(-1px, 0, 0);
    }
    20%,
    80% {
        transform: translate3d(2px, 0, 0);
    }
    30%,
    50%,
    70% {
        transform: translate3d(-4px, 0, 0);
    }
    40%,
    60% {
        transform: translate3d(4px, 0, 0);
    }
}
</style>
