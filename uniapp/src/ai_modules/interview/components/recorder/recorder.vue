<template>
    <u-popup v-model="showModel" mode="bottom" :mask="false" :mask-close-able="false" border-radius="24" height="28%">
        <view class="recorder-container">
            <view class="pt-8">
                <view class="items-center flex justify-center flex-col">
                    <view class="flex justify-center items-center h-[170rpx]">
                        <canvas
                            type="2d"
                            class="audio-canvas flex h-full flex-col justify-center items-center"
                            :height="100"></canvas>
                    </view>
                    <view class="text-white mt-2"
                        >录音中<text class="ml-2 font-bold">{{ formatAudioTime(recordTime / 1000) }}</text></view
                    >
                </view>
            </view>
            <view class="flex justify-between px-[80rpx] gap-4 mt-5">
                <view
                    class="w-[246rpx] rounded-full border border-solid border-white text-white h-[76rpx] text-xl flex items-center justify-center"
                    @click="reply()">
                    重录
                </view>
                <view
                    class="w-[246rpx] rounded-full bg-white text-primary h-[76rpx] text-xl font-bold flex items-center justify-center"
                    @click="confirm()">
                    发送
                </view>
            </view>
            <view class="absolute top-2 right-3">
                <text class="text-white" @click="closePop">关闭</text>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { uploadFile } from "@/api/app";
import { formatAudioTime } from "@/utils/util";
import useRecorder from "../../hooks/useRecorder";
import { speechToText } from "@/api/interview";

const props = withDefaults(
    defineProps<{
        show: boolean;
    }>(),
    {
        show: false,
    }
);

const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
    (event: "success", data: any): void;
    (event: "close"): void;
}>();

const showModel = computed({
    get() {
        return props.show;
    },
    set(value) {
        emit("update:show", value);
    },
});

const { proxy }: any = getCurrentInstance();
const isReply = ref(false);
const isClose = ref(false);
const isError = ref(false);
const recorderResult = ref<any>(null);
const { start, stop, pause, resume, authorize, recordTime, isRecording } = useRecorder(
    {
        duration: 3 * 60 * 1000,
    },
    {
        onstart: () => {
            isReply.value = false;
        },
        ondata: (data: any) => {
            const { pcmData, powerLevel, sampleRate, duration } = data;
            if (proxy.audioCanvas) {
                proxy.audioCanvas.input(pcmData, powerLevel, sampleRate);
            }
        },
        onstop: async (data: any) => {
            if (isReply.value || isClose.value) return;
            recorderResult.value = data;
            await upload();
        },
        ondrawAudio: (findCanvas: Function, Recorder: any) => {
            findCanvas(
                proxy,
                [".audio-canvas"],
                `proxy.audioCanvas=Recorder.WaveView({compatibleCanvas:canvas,lineWidth: 5,width: 300, height: 100});`,
                (canvas: any) => {
                    proxy.audioCanvas = Recorder.WaveView({
                        compatibleCanvas: canvas,
                        width: 300,
                        height: 100,
                        lineWidth: 3,
                        keep: false,
                    });
                }
            );
        },
    }
);

const reply = async () => {
    pause();
    const result = await uni.showModal({
        content: "确认重新录制么？",
        cancelText: "考虑一下",
        confirmText: "立即重录",
    });
    if (result.cancel) {
        resume();
        return;
    }
    isReply.value = true;
    uni.showLoading({
        mask: true,
        title: "正在重新录制中",
    });
    stop();
    start();
    uni.hideLoading();
};

const confirm = async () => {
    if (isError.value) {
        upload();
        return;
    }
    if (recordTime.value < 1000) {
        uni.$u.toast("说话时间太短");
        return;
    }
    stop();
};

const closePop = () => {
    showModel.value = false;
    isClose.value = true;
    stop();
    emit("close");
};

const upload = async () => {
    const { tempFilePath, duration } = recorderResult.value;
    uni.showLoading({
        mask: true,
        title: "正在上传中",
    });
    try {
        const { uri }: any = await uploadFile("audio", {
            filePath: tempFilePath,
        });
        // message 如果为空，重新获取，直到获取到为止，最多获取 3 次，
        let message;
        for (let i = 0; i < 3; i++) {
            message = await getMessage(uri);
            if (message) break;
        }
        uni.hideLoading();
        emit("success", {
            link: uri,
            duration,
            message,
        });
        recorderResult.value = null;
        isError.value = false;
    } catch (error: any) {
        isError.value = true;
        uni.hideLoading();
        uni.showToast({
            title: error || "上传失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getMessage = async (uri: string) => {
    return new Promise((resolve, reject) => {
        speechToText({
            audio_url: uri,
        })
            .then((res) => {
                resolve(res.message);
            })
            .catch((err) => {
                reject(err);
            });
    });
};

watch(showModel, async (value) => {
    if (value) {
        isClose.value = false;
        isReply.value = false;
        start();
    }
});

defineExpose({
    authorize,
});
</script>

<style lang="scss" scoped>
.recorder-container {
    background: linear-gradient(
        117.59deg,
        rgba(95, 144, 217, 1) 0%,
        rgba(148, 198, 255, 1) 38.9%,
        rgba(110, 176, 248, 1) 74.98%,
        rgba(113, 165, 248, 1) 100%
    );
    @apply rounded-tl-xl rounded-tr-xl h-full flex flex-col;
}
</style>
