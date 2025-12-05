<template>
    <div class="w-full h-full">
        <div class="w-full h-full flex items-center justify-between px-4 gap-x-3">
            <div class="h-full gap-2 w-[120px]">
                <div class="text-[18px]">
                    {{ !isPaused ? "录音中..." : "录音已暂停" }}
                </div>
                <div class="text-xs text-[#101316] mt-[2px]">
                    <span>{{ msToTime(recordTime) }}</span>
                    <span class="mx-[5px] opacity-45">/</span>
                    <span class="opacity-45">{{ msToTime(recordTimeLimit) }}</span>
                </div>
            </div>
            <div ref="waveRef" class="h-[60px] grow px-6"></div>
            <div class="flex items-center gap-x-4">
                <ElTooltip content="结束录音" placement="top">
                    <div
                        class="text-danger hover:text-white rounded-md w-[44px] h-[36px] bg-white hover:bg-danger cursor-pointer flex items-center justify-center"
                        @click="handleStopRecord">
                        <Icon name="local-icon-stop" :size="18"></Icon>
                    </div>
                </ElTooltip>
                <ElTooltip content="结束录音" placement="top">
                    <template #content>
                        <div>
                            {{ isPaused ? "继续录音" : "暂停录音" }}
                        </div>
                    </template>
                    <div
                        class="text-primary hover:text-white font-bold rounded-md w-[44px] h-[36px] bg-white hover:bg-primary cursor-pointer flex items-center justify-center"
                        @click="toggleRecording">
                        <Icon :name="isPaused ? 'local-icon-mic' : 'local-icon-pause'" :size="18"></Icon>
                    </div>
                </ElTooltip>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Recorder from "recorder-core";
import "recorder-core/src/engine/mp3";
import "recorder-core/src/engine/mp3-engine";
import "recorder-core/src/extensions/waveview";
import { uploadAudio } from "@/api/app";
import useHandleApi from "../../_hooks/useHandleApi";

const props = defineProps<{
    disabled?: boolean;
    isError?: boolean;
}>();

const emit = defineEmits<{
    (e: "change", value: string): void;
}>();

const nuxtApp = useNuxtApp();
const { userTokens, tokensValue } = useHandleApi();

// 把tokensValue 转为秒
const tokensValueSecond = computed(() => {
    return tokensValue.value.score / 60;
});

const waveRef = shallowRef<Element>(null);
const wave = ref<any>(null);

const recorder = ref<any>(null);
const isRecording = ref<boolean>(false);
const isPaused = ref<boolean>(false);
const isUploadError = ref<boolean>(false);
// 录音时长限制 单位ms 默认3小时
const recordTimeLimit = 60 * 60 * 1000 * 3;
// 录音时长
const recordTime = ref<number>(0);

const startTime = ref<number>(0);
const processTime = ref<number>(0);

const openRecorder = () => {
    recorder.value = Recorder({
        type: "mp3",
        bitRate: 16,
        sampleRate: 16000,
        audioTrackSet: {
            noiseSuppression: true,
            echoCancellation: true,
            autoGainControl: true,
        },
        onProcess: (buffers, powerLevel, bufferDuration, bufferSampleRate, newBufferIdx, asyncEnd) => {
            // 记录录音时长
            recordTime.value = bufferDuration;
            if (
                recordTime.value > recordTimeLimit ||
                (recordTime.value / 1000) * tokensValueSecond.value > userTokens.value
            ) {
                stopRecord();
                return;
            }
            // 可视化图形绘制
            if (wave.value) {
                wave.value?.input(buffers[buffers.length - 1], powerLevel, bufferSampleRate);
            }
            processTime.value = Date.now();
        },
    });
    recorder.value.open(
        () => {
            if (waveRef.value) {
                wave.value = Recorder.WaveView({ elem: waveRef.value });
            }
            isIntercept();
            startRecord();
        },
        (msg: string, isUserNotAllow: any) => {
            feedback.msgWarning((isUserNotAllow ? "UserNotAllow，" : "") + "无法录音:" + msg);
        }
    );
};

// 将毫秒转换为时间格式
const msToTime = (milliseconds: number) => {
    let totalSeconds = Math.floor(milliseconds / 1000);
    let hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    let minutes = Math.floor(totalSeconds / 60);
    let seconds = totalSeconds % 60;

    // 确保小时、分钟、秒的显示格式为两位数字
    // @ts-ignore
    hours = hours.toString().padStart(2, "0");
    // @ts-ignore
    minutes = minutes.toString().padStart(2, "0");
    // @ts-ignore
    seconds = seconds.toString().padStart(2, "0");

    return `${hours}:${minutes}:${seconds}`;
};

// 开始录音
const startRecord = () => {
    if (recorder.value && Recorder.IsOpen()) {
        isRecording.value = true;
        isPaused.value = false;
        recorder.value.start();
        var wdt = (recorder.value.watchDogTimer = setInterval(function () {
            if (!recorder.value || wdt != recorder.value.watchDogTimer) {
                clearInterval(wdt);
                return;
            }
            if (Date.now() < recorder.value.wdtPauseT) return;
            if (Date.now() - (processTime.value || startTime.value) > 1500) {
                clearInterval(wdt);
            }
        }, 1000));
        startTime.value = Date.now();
        recorder.value.wdtPauseT = 0;
        processTime.value = 0;
    }
};

// 暂停录音
const pauseRecord = () => {
    if (recorder.value) {
        recorder.value.pause();
        recorder.value.wdtPauseT = Date.now() * 2;
    }
};

// 继续录音
const resumeRecord = () => {
    if (recorder.value) {
        recorder.value.resume();
        recorder.value.wdtPauseT = Date.now() + 1000;
    }
};

// 关闭录音
const closeRecord = () => {
    if (recorder.value) {
        recorder.value.close();
    }
};

// 结束录音
const stopRecord = () => {
    if (recorder.value) {
        recorder.value.watchDogTimer = 0;
        recorder.value.stop(async (blob: any, duration: number) => {
            recorder.value.close();
            window.onbeforeunload = null;
            const file = new File([blob], `${Date.now()}.mp3`, {
                type: "audio/mp3",
            });
            const result = await uploadAudio({
                file,
            });
            emit("change", result);
        });
        isRecording.value = false;
        isPaused.value = true;
    }
};

// 重置录音， 时间， 录音状态
const resetRecord = () => {
    closeRecord();
    recordTime.value = 0;
    isRecording.value = false;
    isPaused.value = false;
};

// 切换录音状态
const toggleRecording = () => {
    if (isRecording.value) {
        isPaused.value = !isPaused.value;
        if (isPaused.value) {
            pauseRecord();
        } else {
            resumeRecord();
        }
    } else {
        feedback.msgWarning("录音已结束");
    }
};

const handleStopRecord = async () => {
    if (props.disabled) {
        feedback.msgWarning("录音已结束");
        return;
    }
    nuxtApp.$confirm({
        message: "确定要结束录音吗？",
        onConfirm: async () => {
            stopRecord();
        },
    });
};

// 用户刷新页面拦截提醒
const isIntercept = () => {
    window.onbeforeunload = () => {
        return "请勿刷新页面";
    };
};

onMounted(async () => {
    await nextTick();
    openRecorder();
});

onUnmounted(() => {
    closeRecord();
});

defineExpose({
    openRecorder,
    stopRecord,
    closeRecord,
    resetRecord,
});
</script>

<style scoped></style>
