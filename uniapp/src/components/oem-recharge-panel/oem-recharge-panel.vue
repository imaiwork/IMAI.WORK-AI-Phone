<template>
    <view class="oem-panel">
        <view class="text-center">
            <view class="oem-icon mx-auto">
                <u-icon name="coupon" color="#FFFFFF" size="44"></u-icon>
            </view>
            <text class="mt-[24rpx] block text-[34rpx] font-bold text-[#1A1A1A]">获取算力</text>
            <text class="mt-[12rpx] block text-[24rpx] leading-[38rpx] text-[#7B8494]">
                本站不支持在线购买算力，请联系管理员获取卡密，或直接填写兑换码
            </text>
        </view>

        <view v-if="adminQr" class="oem-qr-wrap mt-[32rpx]">
            <image :src="adminQr" show-menu-by-longpress class="oem-qr" mode="aspectFill" />
            <text class="mt-[16rpx] block text-center text-[22rpx] text-[#9CA3AF]">长按识别二维码联系管理员</text>
        </view>
        <view v-else class="oem-empty mt-[32rpx]">
            <text class="block text-[26rpx] font-semibold text-[#475569]">管理员暂未配置联系方式</text>
            <text class="mt-[8rpx] block text-[22rpx] text-[#9CA3AF]">请通过其他渠道联系管理员获取卡密</text>
        </view>

        <view class="mt-[32rpx]">
            <text class="mb-[12rpx] block text-[26rpx] font-semibold text-[#475569]">算力兑换码</text>
            <view class="oem-input">
                <u-input
                    v-model="sn"
                    placeholder="请输入卡密编号"
                    :clearable="true"
                    :border="false"
                    :custom-style="{ fontSize: '28rpx' }" />
            </view>
            <u-button
                type="primary"
                shape="circle"
                :loading="loading"
                :custom-style="{ height: '88rpx', fontSize: '28rpx', marginTop: '24rpx' }"
                @click="handleRedeem">
                立即兑换
            </u-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { checkRedeemCode, useRedeemCode } from "@/api/recharge";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { useLockFn } from "@/hooks/useLockFn";

const emit = defineEmits<{
    (e: "success"): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();

const adminQr = computed(() => String((appStore.getOemConfig as any)?.admin_qr || ""));
const sn = ref("");

const { lockFn: handleRedeem, isLock: loading } = useLockFn(async () => {
    const code = sn.value.trim();
    if (!code) {
        uni.$u.toast("请输入兑换码");
        return;
    }
    if (!userStore.isLogin) {
        uni.$u.toast("请先登录");
        return;
    }
    try {
        // OEM 获取算力：仅兑算力卡，拒绝会员兑换码
        await checkRedeemCode({ sn: code, scene: "tokens" });
        await useRedeemCode({ sn: code, scene: "tokens" });
        uni.$u.toast("兑换成功");
        sn.value = "";
        await userStore.getUser();
        emit("success");
    } catch (e: any) {
        uni.$u.toast(typeof e === "string" ? e : e?.msg || "兑换失败");
    }
});
</script>

<style lang="scss" scoped>
.oem-panel {
    @apply w-full;
}
.oem-icon {
    @apply w-[96rpx] h-[96rpx] rounded-[28rpx] flex items-center justify-center;
    background: linear-gradient(135deg, #0065fb, #4f9dff);
    box-shadow: 0 16rpx 36rpx -8rpx rgba(0, 101, 251, 0.4);
}
.oem-qr-wrap {
    @apply rounded-[24rpx] px-[24rpx] py-[28rpx] bg-[#F8FAFC] border border-solid border-[#EEF2F7];
}
.oem-qr {
    @apply block w-[320rpx] h-[320rpx] mx-auto rounded-[16rpx] bg-white;
}
.oem-empty {
    @apply rounded-[24rpx] px-[24rpx] py-[40rpx] text-center bg-[#F8FAFC];
    border: 2rpx dashed #e2e8f0;
}
.oem-input {
    @apply rounded-[20rpx] px-[24rpx] bg-[#F8FAFC] border border-solid border-[#E8EEF5];
    min-height: 88rpx;
    display: flex;
    align-items: center;
}
</style>
