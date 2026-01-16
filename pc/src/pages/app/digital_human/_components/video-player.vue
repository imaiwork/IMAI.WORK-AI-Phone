<template>
    <div class="h-full w-full bg-no-repeat relative group overflow-hidden">
        <div class="relative z-10 w-full h-full video-box">
            <video
                ref="videoRef"
                id="video-player"
                class="w-full h-full object-cover"
                :style="{
                    borderRadius: borderRadius + 'px',
                }"
                :src="videoUrl"
                :controls="isFullScreen"
                :poster="poster"
                playsinline
                webkit-playsinline
                @play="onVideoPlay"
                @pause="onVideoPause"
                @ended="onVideoEnded"
                @loadedmetadata="loadedmetadata"
                @timeupdate="timeupdate"
                @fullscreenchange="fullscreenchange"></video>
        </div>

        <div
            v-if="showClose"
            class="absolute left-2 w-8 h-8 z-50 top-4 cursor-pointer hover:opacity-80 transition-opacity"
            @click="$emit('close')">
            <Icon name="local-icon-close" size="24" color="#ffffff" />
        </div>
        <div
            class="absolute top-1/2 left-1/2 z-40 cursor-pointer hover:scale-110 transition-transform"
            style="transform: translate(-50%, -50%)"
            v-if="!isPlaying || isEnded"
            @click.stop="toggleVideo()">
            <Icon name="local-icon-video_play" :size="playIconSize" color="#ffffff" />
        </div>

        <div class="absolute bottom-2 left-0 right-0 z-40 px-5">
            <div class="text-white text-sm flex items-center justify-center gap-x-1 select-none">
                <span>{{ formatTime(currDuration) }}</span> /
                <span class="opacity-50">{{ formatTime(videoDuration) }}</span>
            </div>

            <div class="h-9 mt-2 relative">
                <div
                    class="h-full w-full backdrop-blur-sm rounded-full absolute top-0 left-0 pointer-events-none"
                    style="background-color: rgba(255, 255, 255, 0.1)"></div>

                <div class="h-full flex items-center px-3 gap-x-2 relative z-10">
                    <div class="leading-none flex-shrink-0 cursor-pointer" @click.stop="toggleVideo()">
                        <Icon
                            :name="!isPlaying || isEnded ? 'local-icon-video_play' : 'local-icon-video_stop'"
                            size="24"
                            color="#ffffff" />
                    </div>

                    <div
                        ref="progressBoxRef"
                        class="flex-1 py-1 cursor-pointer group/progress"
                        @click.stop="clickProgress">
                        <div class="bg-white/30 flex-1 relative h-1 rounded-full overflow-hidden">
                            <div class="w-full h-full bg-white opacity-20 absolute"></div>
                            <div
                                class="bg-primary h-full absolute left-0 rounded-full transition-all duration-100 ease-linear"
                                :style="{
                                    width: `${videoProgress}%`,
                                }"></div>
                        </div>
                    </div>

                    <div class="leading-none flex-shrink-0 cursor-pointer" @click.stop="clickFullScreen()">
                        <Icon name="local-icon-video_full_screen" size="24" color="#ffffff" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// 如果没有 utils，这里提供一个简易的时间格式化函数
const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
};

const props = withDefaults(
    defineProps<{
        poster?: string;
        videoUrl: string;
        playIconSize?: number;
        borderRadius?: number;
        showClose?: boolean;
    }>(),
    {
        poster: "",
        videoUrl: "",
        playIconSize: 36, // web端 px通常比rpx小，调整了默认值
        borderRadius: 24,
        showClose: false,
    }
);

const emit = defineEmits(["close"]);

// DOM Refs
const videoRef = ref<HTMLVideoElement | null>(null);
const progressBoxRef = ref<HTMLElement | null>(null);

// State
const videoDuration = ref<number>(0);
const currDuration = ref<number>(0);
const videoProgress = ref<number>(0);
const isPlaying = ref<boolean>(false);
const isEnded = ref<boolean>(false);
const isShowVideo = ref<boolean>(false);
const isFullScreen = ref<boolean>(false);

// Video Events
const loadedmetadata = () => {
    if (videoRef.value) {
        videoDuration.value = videoRef.value.duration;
    }
};

const timeupdate = () => {
    if (videoRef.value) {
        const currentTime = videoRef.value.currentTime;
        const duration = videoRef.value.duration || 1; // 防止除以0
        currDuration.value = currentTime;
        videoProgress.value = (currentTime / duration) * 100;
    }
};

const fullscreenchange = () => {
    // 监听 Web 标准的全屏变化
    if (document.fullscreenElement) {
        isFullScreen.value = true;
    } else {
        isFullScreen.value = false;
    }
};

// Actions
const clickProgress = (e: MouseEvent) => {
    if (!progressBoxRef.value || !videoRef.value) return;

    const rect = progressBoxRef.value.getBoundingClientRect();
    const clickX = e.clientX;
    const progressStartX = rect.left;
    const progressWidth = rect.width;

    // 计算点击位置
    const relativeClickX = clickX - progressStartX;
    const percentage = Math.max(0, Math.min(1, relativeClickX / progressWidth)); // 限制在 0-1 之间

    const seconds = percentage * videoDuration.value;

    // 设置视频时间
    videoRef.value.currentTime = seconds;
    videoProgress.value = percentage * 100;
};

const clickFullScreen = async () => {
    if (!videoRef.value) return;

    try {
        if (!document.fullscreenElement) {
            await videoRef.value.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    } catch (err) {
        console.error("Fullscreen error:", err);
    }
};

const onVideoPlay = () => {
    isShowVideo.value = true;
    isPlaying.value = true;
    isEnded.value = false;
};

const onVideoPause = () => {
    isPlaying.value = false;
};

const onVideoEnded = () => {
    isEnded.value = true;
    isPlaying.value = false;
};

const toggleVideo = async () => {
    if (!videoRef.value) return;

    isShowVideo.value = true;

    if (isEnded.value) {
        videoRef.value.currentTime = 0;
        try {
            await videoRef.value.play();
        } catch (e) {
            console.error(e);
        }
        isEnded.value = false;
        return;
    }

    if (isPlaying.value) {
        videoRef.value.pause();
    } else {
        try {
            await videoRef.value.play();
        } catch (e) {
            console.error(e);
        }
    }
};

onMounted(() => {
    document.addEventListener("fullscreenchange", fullscreenchange);
    document.addEventListener("webkitfullscreenchange", fullscreenchange);
});

onUnmounted(() => {
    document.removeEventListener("fullscreenchange", fullscreenchange);
    document.removeEventListener("webkitfullscreenchange", fullscreenchange);
    // 移除视频元素
    if (videoRef.value) {
        videoRef.value.remove();
    }
});

defineExpose({
    toggleVideo,
});
</script>

<style scoped lang="scss">
/* 隐藏原生 video 的控件，如果不想完全依赖原生控件的话 (除了全屏时) */
/* 注意：Web 上全屏时通常浏览器会强制显示原生控件，这是正常的 */
</style>
