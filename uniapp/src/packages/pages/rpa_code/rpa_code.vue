<template>
    <view class="p-[26rpx]">
        <view class="rounded-[20rpx] bg-white p-5 flex flex-col items-center">
            <view class="text-[30rpx] font-bold">设备绑定码</view>
            <view class="text-[30rpx] text-[#0000004d] mt-[20rpx]">请勿与任何人分享此代码</view>
            <view class="w-[500rpx] h-[500rpx] mt-[56rpx]">
                <image :src="qrcode" class="w-full h-full rounded-[20rpx]"></image>
            </view>
            <view class="font-bold mt-5"> 请前往AI获客启用摄像头扫描此二维码 </view>
        </view>
    </view>
    <confirm-dialog
        v-model="showConfirmDialog"
        center
        :content="content"
        @close="handleClose"
        @confirm="handleTaskConfirm" />
</template>

<script setup lang="ts">
import { getRpaQrcode, getRpaQrcodeStatus } from "@/api/user";
import usePolling from "@/hooks/usePolling";

const qrcode = ref<string>("");
const deviceCode = ref<string>("");
const showConfirmDialog = ref(false);

const content = `<div>您已添加新的设备，是否需要为该设备设置24小时自动任务？注意：设备必须在线才能拉取账号</div>`;

const { start, end } = usePolling(
    async () => {
        const data = await getRpaQrcodeStatus();
        if (data.status == 1) {
            end();
            deviceCode.value = data.device_code;
            showConfirmDialog.value = true;
            uni.showToast({
                title: "绑定成功",
                icon: "none",
                duration: 3000,
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

const handleClose = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
};

const handleTaskConfirm = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/create_auto_task/create_auto_task",
        type: "reLaunch",
        params: {
            device_code: deviceCode.value,
            is_auto: 1,
        },
    });
};

onMounted(() => {
    getRpaQrcodeData();
});

onUnload(() => {
    end();
});
</script>

<style scoped></style>
