<template>
    <view class="p-[26rpx]">
        <view class="rounded-[20rpx] bg-white p-5 flex flex-col items-center">
            <view class="text-[30rpx] font-bold">设备绑定码</view>
            <view class="text-[30rpx] text-[#0000004d] mt-[20rpx]">请勿与任何人分享此代码</view>
            <view class="w-[500rpx] h-[500rpx] mt-[56rpx]">
                <image :src="qrcode" class="w-full h-full rounded-[20rpx]"></image>
            </view>
            <view class="font-bold mt-5"> 请前往RPA启用摄像头扫描此二维码 </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getRpaQrcode, getRpaQrcodeStatus } from "@/api/user";
import usePolling from "@/hooks/usePolling";

const qrcode = ref<string>("");

const { start, end } = usePolling(
    async () => {
        const data = await getRpaQrcodeStatus();
        if (data.status == 1) {
            end();
            uni.showToast({
                title: "绑定成功",
                icon: "none",
                duration: 3000,
            });
            uni.$u.route({
                url: "/pages/phone/phone",
                type: "reLaunch",
            });
        }
    },
    {
        time: 4500,
    }
);

const getRpaQrcodeData = async () => {
    const data = await getRpaQrcode();
    qrcode.value = data.url;
    start();
};

onMounted(() => {
    getRpaQrcodeData();
});

onUnload(() => {
    end();
});
</script>

<style scoped></style>
