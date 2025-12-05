<template>
    <div class="flex items-center max-w-2xl w-full">
        <div>
            <div class="flex items-center gap-6">
                <!-- 上一首按钮 -->
                <button @click="prev()">
                    <Icon name="local-icon-music_left" :size="24" color="var(--color-primary)"></Icon>
                </button>
                <!-- 播放/暂停按钮 -->
                <button class="w-12 h-12 flex items-center justify-center bg-primary rounded-full" @click="toggle()">
                    <Icon
                        :name="isPlaying ? 'local-icon-music_pause' : 'local-icon-music_play'"
                        color="#ffffff"
                        :size="24"></Icon>
                </button>
                <!-- 下一首按钮 -->
                <button @click="next()">
                    <Icon name="local-icon-music_right" :size="24" color="var(--color-primary)"></Icon>
                </button>
            </div>
        </div>
        <div class="flex items-center ml-14 grow gap-4">
            <div>{{ currentTime }}</div>
            <div class="grow">
                <!-- 进度条 -->
                <ElSlider
                    v-model="progress"
                    :show-tooltip="false"
                    :disabled="!music"
                    @input="updateSeek"
                    @change="seek" />
            </div>
            <div>{{ duration }}</div>
        </div>
        <!-- 音频元素 -->
        <audio
            ref="audio"
            v-if="music"
            class="hidden"
            @ended="end"
            @timeupdate="updateTime"
            @canplay="onCanPlay"
            @loadedmetadata="onLoadedMetadata"
            @error="onError"></audio>
    </div>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";

// 定义组件属性类型
interface Props {
    music?: string | undefined;
}

// 定义Props和Emit事件
const props = withDefaults(defineProps<Props>(), {
    music: "",
});
const emit = defineEmits(["play", "pause", "next", "prev", "end"]);

// 定义ref变量
const audio = ref<HTMLAudioElement | null>(null);
const progress = ref<number>(0);
const isPlaying = ref<boolean>(false);
const currentTime = ref<string>("00:00");
const duration = ref<string>("00:00");
const isLoad = ref<boolean>(false);
const isError = ref<boolean>(false);
const isSeeking = ref<boolean>(false);

const music = ref<string>(props.music);

// 初始化音频
const initAudio = async () => {
    await nextTick();
    if (audio.value) {
        audio.value.src = music.value;
        audio.value.load();
        play();
    }
};

// 设置音频
const setAudio = async (url: string) => {
    music.value = url;
    await nextTick();
    initAudio();
};

// 音频元数据加载事件处理
const onLoadedMetadata = () => {
    if (audio.value) {
        isLoad.value = true;
        duration.value = formatAudioTime(audio.value.duration);
    }
};

// 音频可以播放事件处理
const onCanPlay = () => {
    if (isPlaying.value && audio.value) {
        audio.value.play();
    }
    isLoad.value = false;
};

// 音频错误事件处理
const onError = () => {
    isError.value = true;
    isLoad.value = false;
};

const isErrorMusic = () => {
    if (isError.value || !music.value) {
        feedback.msgError("无法播放音乐");
        isPlaying.value = false;
        return false;
    }
    if (isLoad.value) {
        feedback.msgError("播放器正在加载，请稍等");
        isPlaying.value = false;
        return false;
    }
    if (!audio.value) {
        feedback.msgError("播放器初始化失败");
        initAudio();
        isPlaying.value = false;
        return false;
    }
    return true;
};

// 播放音频
const play = () => {
    const isValid = isErrorMusic();
    if (!isValid) return;
    isPlaying.value = true;
    audio.value.play();
    emit("play");
};

// 暂停音频
const pause = () => {
    emit("pause");
    isPlaying.value = false;
    audio.value.pause();
};

// 切换播放/暂停状态
const toggle = () => {
    if (isPlaying.value) {
        pause();
    } else {
        play();
    }
};

// 下一首歌曲
const next = async () => {
    emit("next");
    await nextTick();
};

// 上一首歌曲
const prev = async () => {
    emit("prev");
    await nextTick();
};

// 重置播放器
const resetPlayer = () => {
    if (audio.value) {
        audio.value.src = props.music;
        audio.value.load();
    }
    isPlaying.value = true;
    isError.value = false;
    isLoad.value = false;
    currentTime.value = "00:00";
    duration.value = "00:00";
    progress.value = 0;
    isErrorMusic();
};

// 音频结束事件处理
const end = () => {
    isPlaying.value = false;
    emit("end");
};

// 拖动进度条时更新进度
const updateSeek = (value: number) => {
    if (audio.value) {
        const seekTime = (value / 100) * audio.value.duration;
        currentTime.value = formatAudioTime(seekTime);
        isSeeking.value = true;
    }
};

// 松开进度条时跳转到指定位置
const seek = (value: number) => {
    if (audio.value) {
        const seekTime = (value / 100) * audio.value.duration;
        audio.value.currentTime = seekTime;
        currentTime.value = formatAudioTime(seekTime);
        isSeeking.value = false;
        if (isPlaying.value) {
            audio.value.play();
        }
    }
};

// 更新播放时间
const updateTime = () => {
    if (audio.value && !isSeeking.value) {
        currentTime.value = formatAudioTime(audio.value.currentTime);
        progress.value = (audio.value.currentTime / audio.value.duration) * 100;
    }
};

// 组件挂载时初始化音频
onMounted(() => {
    initAudio();
});

// 定义暴露的方法
defineExpose({
    play,
    pause,
    seek,
    next,
    end,
    toggle,
    resetPlayer,
    setAudio,
});
</script>

<style scoped lang="scss">
:deep(.el-sidebar) {
    .el-slider__button {
        display: none;
    }
    &:hover {
        .el-slider__button {
            display: inline-block;
        }
    }
}
</style>
