<template>
    <view class="h-screen relative">
        <camera
            v-if="showCamera"
            device-position="front"
            flash="off"
            class="w-full h-full"
            @initdone="handleUploadAuthCamera"
            @error="handleError"
            @stop="stopCountdown" />
        <view
            class="absolute top-[200rpx] left-0 right-0 p-4 bg-[#0000007F] rounded-[24rpx] mx-[36rpx] text-white font-bold text-[32rpx] leading-5">
            我是 _（真实姓名），我授权{{
                shanjianAuth
            }}使用视频中的肖像、声音，为我生成定制数字人及声音，并在本人账号中创作使用。
        </view>
        <view class="absolute bottom-8 left-0 w-full z-[888]">
            <view class="w-full flex flex-col items-center justify-center">
                <view
                    class="bg-[#0000007F] w-[178rpx] h-[78rpx] flex items-center justify-center text-white font-bold text-[32rpx] rounded-[48rpx]">
                    {{ formatAudioTime(duration) }}
                </view>
                <view
                    class="mt-4 w-[144rpx] h-[144rpx] border-2 border-solid border-white rounded-full flex items-center justify-center"
                    @click="stopCountdown">
                    <view class="w-[72rpx] h-[72rpx] bg-[#D43030] rounded-[8rpx]"></view>
                </view>
            </view>
        </view>
    </view>
    <view v-if="loading" class="fixed top-0 left-0 w-full h-full bg-white flex flex-col items-center justify-center">
        <u-loading size="40" />
        <view class="text-gray-500 mt-4">正在初始化摄像头...</view>
    </view>
</template>

<script setup lang="ts">
import { getVideoTranscodeResult, videoTranscode } from "@/api/app";
import { uploadFile } from "@/api/app";
import { formatAudioTime } from "@/utils/util";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();
const shanjianAuth = computed(() => appStore.getDigitalHumanConfig.shanjian_auth);

const loading = ref(true);
const duration = ref(0);
const maxDuration = ref(3 * 60); // 分钟
let timer = ref();
let cameraContext = ref<any>();
const showCamera = ref(true);
const handleUploadAuthCamera = () => {
    loading.value = false;
    cameraContext.value = uni.createCameraContext();
    cameraContext.value.startRecord({
        success: (res: any) => {
            startCountdown();
        },
        fail: (err: any) => {
            uni.showToast({
                title: err || "录制失败",
                icon: "none",
                duration: 3000,
            });
        },
    });
};

// 倒计时处理
const startCountdown = () => {
    timer.value = setInterval(() => {
        duration.value++;
        if (duration.value >= maxDuration.value) {
            stopCountdown();
        }
    }, 1000);
};

// 停止录制
const stopCountdown = async () => {
    if (!showCamera.value) return;
    //判断录制时间不能小于5秒
    if (duration.value < 5) {
        uni.showToast({
            title: "录制时间不能小于5秒",
            icon: "none",
            duration: 3000,
        });
        return;
    }
    clearInterval(timer.value);

    uni.showLoading({
        title: "正在结束录制...",
        mask: true,
    });
    cameraContext.value.stopRecord({
        success: async (res: any) => {
            const { tempThumbPath, tempVideoPath } = res;
            uni.hideLoading();
            uni.showLoading({
                title: "正在上传...",
                mask: true,
            });
            try {
                // 上传图片
                const [imageRes, videoRes]: any = await Promise.all([
                    uploadFile("image", {
                        filePath: tempThumbPath,
                    }),
                    uploadFile("video", {
                        filePath: tempVideoPath,
                    }),
                ]);
                uni.hideLoading();
                uni.showToast({
                    title: "上传成功，即将返回上一页",
                    icon: "none",
                    duration: 3000,
                });
                uni.navigateBack();
                uni.$emit("confirm", {
                    type: ListenerTypeEnum.UPLOAD_AUTH_CAMERA,
                    data: {
                        ...res,
                        pic: imageRes.uri,
                        url: videoRes.uri,
                    },
                });
            } catch (error: any) {
                uni.hideLoading();
                uni.showToast({
                    title: error || "上传失败，请重新上传",
                    icon: "none",
                    duration: 3000,
                });
            }
        },
        fail: (err: any) => {
            uni.hideLoading();
        },
    });
};

const handleError = (err: any) => {
    uni.hideLoading();
    uni.showToast({
        title: err,
        icon: "none",
        duration: 3000,
    });
};

onUnload(() => {
    showCamera.value = false;
    clearInterval(timer.value);
    uni.hideLoading();
});
</script>

<style scoped></style>
