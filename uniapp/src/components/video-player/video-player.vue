<template>
    <view
        class="h-full w-full bg-no-repeat relative"
        :style="{
            backgroundImage: `url(${poster})`,
            backgroundSize: 'cover',
            borderRadius: borderRadius + 'rpx',
        }">
        <view class="relative z-[88] w-full h-full video-box" :class="{ 'opacity-0': !isShowVideo }">
            <video
                id="video-player"
                class="w-full h-full"
                :style="{
                    borderRadius: borderRadius + 'rpx',
                }"
                object-fit="contain"
                :src="videoUrl"
                :controls="isFullScreen"
                @play="onVideoPlay"
                @pause="onVideoPause"
                @ended="onVideoEnded"
                @loadedmetadata="loadedmetadata"
                @timeupdate="timeupdate"
                @fullscreenchange="fullscreenchange"></video>
        </view>
        <view
            v-if="showClose"
            class="absolute left-0 w-[64rpx] h-[64rpx] z-[787]"
            :style="{ top: statusBarHeight + 'px' }"
            @click="$emit('close')">
            <image src="/static/images/icons/close.svg" class="w-full h-full"></image>
        </view>
        <view
            class="absolute top-[50%] left-[50%] z-[888]"
            style="transform: translate(-50%, -50%)"
            v-if="!isPlaying || isEnded"
            @click.stop="toggleVideo()">
            <image
                src="/static/images/common/video_play.png"
                :style="{ width: `${playIconSize}rpx`, height: `${playIconSize}rpx` }"></image>
        </view>
        <view class="absolute bottom-4 left-0 right-0 z-[888] px-[40rpx]">
            <view class="text-white text-[26rpx] flex items-center justify-center gap-x-1">
                <text>{{ formatAudioTime(currDuration) }}</text> /
                <text class="opacity-50">{{ formatAudioTime(videoDuration) }}</text>
            </view>
            <view class="h-[60rpx] mt-[20rpx] relative">
                <view
                    class="h-full w-full backdrop-blur-sm rounded-[48rpx] absolute"
                    style="background-color: rgba(255, 255, 255, 0.1)"></view>
                <view class="h-full flex items-center px-[24rpx] gap-x-2 relative z-[8]">
                    <view class="leading-[0] flex-shrink-0" @click.stop="toggleVideo()">
                        <image
                            v-if="!isPlaying || isEnded"
                            src="/static/images/icons/video_play.svg"
                            class="w-[28rpx] h-[28rpx]"></image>
                        <image v-else src="/static/images/icons/video_stop.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                    <view class="flex-1 py-1 progress-box" @click.stop="clickProgress">
                        <view class="bg-white flex-1 relative h-[4rpx]">
                            <view
                                class="bg-primary h-full absolute left-0"
                                :style="{
                                    width: `${videoProgress}%`,
                                }"></view>
                        </view>
                    </view>
                    <view class="leading-[0] flex-shrink-0" @click.stop="clickFullScreen()">
                        <image src="/static/images/icons/video_full_screen.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatAudioTime, getRect } from "@/utils/util";

const props = withDefaults(
    defineProps<{
        poster: string;
        videoUrl: string;
        playIconSize?: number;
        borderRadius?: number;
        showClose?: boolean;
    }>(),
    {
        poster: "",
        videoUrl: "",
        playIconSize: 108,
        borderRadius: 48,
        showClose: false,
    }
);

const { statusBarHeight } = uni.$u.sys();

const videoContent = ref<any>();

const videoDuration = ref<number>(0);
const currDuration = ref<number>(0);
const videoProgress = ref<number>(0);
const isPlaying = ref<boolean>(false);
const isEnded = ref<boolean>(false);
const isShowVideo = ref<boolean>(false);
const isFullScreen = ref<boolean>(false);
const loadedmetadata = async (e: any) => {
    const { duration } = e.detail;
    videoDuration.value = duration;
};

const timeupdate = (e: any) => {
    const { currentTime, duration } = e.detail;
    currDuration.value = currentTime;
    videoProgress.value = (currentTime / duration) * 100;
};

const fullscreenchange = (e: any) => {
    const { fullScreen } = e.detail;
    isFullScreen.value = fullScreen;
};

const { proxy }: any = getCurrentInstance();
// 点击进度条， 设置播放进度
const clickProgress = async (e: any) => {
    const clickX = e.detail.x;
    const res: any = await getRect(".progress-box", false, proxy);
    const progressStartX = res.left;
    const progressWidth = res.width;

    // 计算点击点相对于进度条的距离
    const relativeClickX = clickX - progressStartX;

    const percentage = (relativeClickX / progressWidth) * 100;
    const seconds = (percentage / 100) * videoDuration.value;
    videoContent.value.seek(seconds);
};

const clickFullScreen = () => {
    videoContent.value.requestFullScreen();
};

const onVideoPlay = () => {
    isShowVideo.value = true;
    isPlaying.value = true;
};

const onVideoPause = () => {
    isPlaying.value = false;
};

const onVideoEnded = () => {
    isEnded.value = true;
};

const toggleVideo = async () => {
    isShowVideo.value = true;
    if (isEnded.value) {
        videoContent.value.seek(0);
        videoContent.value.play();
        isEnded.value = false;
        return;
    }
    if (isPlaying.value) {
        videoContent.value.pause();
        isPlaying.value = false;
    } else {
        await nextTick();
        videoContent.value.play();
        isPlaying.value = true;
    }
};

const getVideoContent = async () => {
    await nextTick();
    videoContent.value = uni.createVideoContext("video-player", proxy);
};

onMounted(async () => {
    await getVideoContent();
});

defineExpose({
    toggleVideo,
});
</script>

<style scoped lang="scss">
:deep(.video-box) {
    .video-player {
        width: 100%;
        height: 100%;
        border-radius: 48rpx;
        object-fit: cover;
    }
}
</style>
