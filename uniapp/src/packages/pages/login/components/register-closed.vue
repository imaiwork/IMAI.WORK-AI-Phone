<template>
    <u-popup v-model="showPopup" mode="center" border-radius="28" :mask-close-able="true">
        <view class="w-[600rpx] px-[48rpx] pt-[60rpx] pb-[48rpx] text-center">
            <view class="flex justify-center">
                <view class="w-[112rpx] h-[112rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center">
                    <u-icon name="lock" :size="52" color="#9CA3AF"></u-icon>
                </view>
            </view>
            <view class="text-[34rpx] font-medium text-[#1F2937] mt-[32rpx]">系统暂时关闭了新用户注册</view>
            <view class="text-[26rpx] text-[#6B7280] leading-[1.6] mt-[16rpx]">
                如有疑问请联系客服，已注册账号可正常登录
            </view>
            <view v-if="serviceQrcode" class="mt-[40rpx] flex flex-col items-center">
                <image
                    :src="serviceQrcode"
                    mode="aspectFit"
                    class="w-[280rpx] h-[280rpx] rounded-[16rpx] border border-[#F0F0F0]"></image>
                <view class="text-[26rpx] text-[#6B7280] mt-[16rpx]">扫码联系客服</view>
            </view>
            <view class="mt-[48rpx]">
                <button
                    class="bg-primary rounded-full text-white text-lg h-[80rpx] leading-[80rpx]"
                    hover-class="none"
                    @click="showPopup = false">
                    我知道了
                </button>
            </view>
        </view>
    </u-popup>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import { useAppStore } from "@/stores/app";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
}>();

const appStore = useAppStore();
const serviceQrcode = computed(() => appStore.getWebsiteConfig?.customer_service?.wx_image || "");

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});
</script>

<style lang="scss" scoped></style>
