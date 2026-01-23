<template>
    <u-popup v-model="showModel" mode="bottom" :mask="true" border-radius="50" height="45%">
        <view class="recorder-panel bg-white">
            <view class="flex items-center justify-between px-6 pt-5">
                <text class="text-[#94A3B8] text-[28rpx]" @click="closePop">取消</text>
                <view class="flex items-center gap-1.5">
                    <view class="w-2 h-2 bg-[#FF4D4F] rounded-full animate-pulse shadow-[0_0_8rpx_#FF4D4F]"></view>
                    <text class="text-[30rpx] font-black text-[#1E293B]">正在聆听...</text>
                </view>
                <text class="text-primary font-bold text-[28rpx]" @click="confirm">完成</text>
            </view>

            <view class="grow flex flex-col items-center justify-center mt-4">
                <view class="relative w-full h-[140rpx] flex items-center justify-center">
                    <view class="absolute w-[300rpx] h-[60rpx] bg-primary/5 blur-[40rpx] rounded-full"></view>
                    <canvas type="2d" class="audio-canvas w-full h-[120rpx] z-10" :height="100"></canvas>
                </view>

                <view class="mt-4 px-4 py-1.5 bg-[#F1F5F9] rounded-full">
                    <text class="text-[#1E293B] font-mono text-[36rpx] font-black tracking-wider">
                        {{ formatAudioTime(recordTime / 1000) }}
                    </text>
                </view>
            </view>

            <view class="flex flex-col items-center justify-center pb-10">
                <view
                    class="w-[100rpx] h-[100rpx] rounded-full bg-white shadow-[0_8rpx_30rpx_rgba(0,0,0,0.08)] border border-[#F1F5F9] flex items-center justify-center active:scale-90 transition-all"
                    @click="reply">
                    <u-icon name="reload" color="#64748B" size="36"></u-icon>
                </view>
                <view class="w-full text-center mt-2">
                    <text class="text-[22rpx] text-[#CBD5E0] tracking-[4rpx] uppercase">重录</text>
                </view>
            </view>
        </view>
    </u-popup>
</template>
<script setup lang="ts">
import { uploadFile } from "@/api/app";
import { formatAudioTime } from "@/utils/util";
import { lpSceneSpeechToText } from "@/api/ladder_player";
import useRecorder from "@/ai_modules/digital_human/hooks/useRecorder";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        minDuration: number;
    }>(),
    {
        modelValue: false,
        minDuration: 15,
    }
);

const emit = defineEmits<{
    (event: "update:modelValue", modelValue: boolean): void;
    (event: "success", data: any): void;
    (event: "close"): void;
}>();

const showModel = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const { proxy }: any = getCurrentInstance();
const isReply = ref(false);
const isClose = ref(false);
const isError = ref(false);
const recorderResult = ref<any>(null);
const { start, stop, pause, resume, authorize, recordTime } = useRecorder(
    {
        duration: 3 * 60 * 1000,
    },
    {
        onstart: () => {
            isReply.value = false;
        },
        ondata: (data: any) => {
            const { pcmData, powerLevel, sampleRate } = data;
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
                `this.audioCanvas=Recorder.WaveView({compatibleCanvas:canvas1,lineWidth: 5,width: 300, height: 100,linear1: [0, "#0065fb", 1, "#3b82f6"],});`,
                (canvas1: any) => {
                    proxy.audioCanvas = Recorder.WaveView({
                        compatibleCanvas: canvas1,
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
    start(proxy);
    uni.hideLoading();
};

const confirm = async () => {
    if (isError.value) {
        upload();
        return;
    }
    if (recordTime.value < props.minDuration * 1000) {
        uni.$u.toast(`说话时间太短，最少需要${props.minDuration}秒`);
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
        lpSceneSpeechToText({
            audio: uri,
        })
            .then((res) => {
                resolve(res.message);
            })
            .catch((err) => {
                reject(err);
            });
    });
};

watch(
    showModel,
    async (value) => {
        if (value) {
            isClose.value = false;
            isReply.value = false;
            start(proxy);
        }
    },
    {
        immediate: true,
    }
);

onUnmounted(() => {
    stop();
});

defineExpose({
    authorize,
    proxy,
});
</script>

<!-- #ifdef APP -->
<script module="recorder" lang="renderjs">
import 'recorder-core'
import RecordApp from 'recorder-core/src/app-support/app'
import '../../static/Recorder-UniCore/app-uni-support.js'

//按需引入你需要的录音格式支持文件，和插件
import 'recorder-core/src/engine/mp3'
import 'recorder-core/src/engine/mp3-engine'

import 'recorder-core/src/extensions/waveview'

//@ts-ignore
export default {
    mounted(){
        RecordApp.UniRenderjsRegister(this);
    },
    methods: {
        //@ts-ignore
        callback(data) {
             const { pcmData, powerLevel, sampleRate } = data
             //@ts-ignore
            if (this.audioCanvas) {
                //@ts-ignore
                this.audioCanvas.input(pcmData, powerLevel, sampleRate)
            }
        }
    }
}
</script>
<!-- #endif -->

<style lang="scss" scoped>
.recorder-panel {
    @apply h-full flex flex-col relative bg-white;
}

/* 脉冲动画 */
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-pulse {
    animation: pulse 1.5s infinite ease-in-out;
}
</style>
