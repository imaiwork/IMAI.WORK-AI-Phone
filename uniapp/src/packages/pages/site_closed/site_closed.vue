<template>
    <view class="box-border flex min-h-screen items-center justify-center bg-[#F8FAFC] px-[48rpx] py-[48rpx]">
        <view
            class="box-border w-full max-w-[640rpx] rounded-[32rpx] border-[2rpx] border-solid border-[#EEF2F7] bg-white px-[48rpx] py-[64rpx] text-center">
            <view
                class="mx-auto flex h-[120rpx] w-[120rpx] items-center justify-center rounded-[32rpx]"
                style="background: linear-gradient(135deg, #fef3c7, #fde68a)">
                <u-icon name="error-circle" color="#B45309" size="56" />
            </view>
            <text class="mt-[32rpx] block text-[40rpx] font-extrabold text-[#0F172A]">站点已关闭</text>
            <text class="mt-[16rpx] block text-[28rpx] leading-[1.65] text-[#64748B]">{{ message }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();
const message = computed(
    () => String((appStore.getOemConfig as any)?.message || "").trim() || "该企业站点已解散或关闭，暂时无法访问。"
);

// 阻止返回到业务页
onBackPress(() => true);
</script>
