<template>
    <view class="h-screen flex flex-col bg-[#F5F8FF]">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            :custom-back="back">
        </u-navbar>
        <view class="grow mih-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="flex justify-center items-center h-full">
                    <canvas type="2d" class="recorder-wave-view"></canvas>
                </view>
            </scroll-view>
        </view>
        <view class="px-[32rpx]">
            <view class="flex items-center justify-between h-full">
                <view>
                    <view class="h-full gap-2">
                        <view class="text-[18px]">
                            {{ !isPaused ? "录音中..." : "录音已暂停" }}
                        </view>
                        <view class="text-xs text-[#101316] mt-[4rpx]">
                            <text>{{ msToTime(recordTime) }}</text>
                            <text class="mx-[5px] opacity-45">/</text>
                            <text class="opacity-45">{{ msToTime(recordTimeLimit) }}</text>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-4">
                    <template v-if="!isUploadError">
                        <view
                            class="text-danger hover:text-white rounded-md w-[88rpx] h-[72rpx] bg-white hover:bg-danger cursor-pointer flex items-center justify-center"
                            @click="handleStopRecord">
                            <image src="@/ai_modules/meeting_minutes/static/icons/stop.svg" class="w-[42rpx] h-[42rpx]">
                            </image>
                        </view>
                        <view
                            class="text-primary hover:text-white font-bold rounded-md w-[88rpx] h-[72rpx] bg-white hover:bg-primary cursor-pointer flex items-center justify-center"
                            @click="toggleRecording">
                            <image
                                src="@/ai_modules/meeting_minutes/static/icons/mic.svg"
                                class="w-[42rpx] h-[42rpx]"
                                v-if="!isPaused"></image>
                            <image
                                src="@/ai_modules/meeting_minutes/static/icons/pause.svg"
                                class="w-[42rpx] h-[42rpx]"
                                v-else></image>
                        </view>
                    </template>
                    <template v-else>
                        <view
                            class="text-primary hover:text-white rounded-md w-[88rpx] h-[72rpx] bg-white hover:bg-danger cursor-pointer flex items-center justify-center"
                            @click="handleRetry">
                            <image
                                src="@/ai_modules/meeting_minutes/static/icons/loop_left.svg"
                                class="w-[42rpx] h-[42rpx]"></image>
                        </view>
                        <view
                            class="text-primary hover:text-white rounded-md w-[88rpx] h-[72rpx] bg-white hover:bg-danger cursor-pointer flex items-center justify-center"
                            @click="handleUpload">
                            <image
                                src="@/ai_modules/meeting_minutes/static/icons/upload_cloud.svg"
                                class="w-[42rpx] h-[42rpx]"></image>
                        </view>
                    </template>
                </view>
            </view>
        </view>
        <view class="flex items-center justify-center gap-x-1 mt-4 mb-[34rpx]">
            <u-icon name="info-circle" :size="20"></u-icon>
            <text class="text-[20rpx] text-gray-500">
                您的算力目前可以转写{{ userRecordTimeLimit }}分钟，算力不足时将终止录音</text
            >
        </view>
    </view>
    <u-popup v-model="showPopup" mode="bottom" :mask="false" border-radius="40" height="35%">
        <view class="h-full flex flex-col">
            <view class="flex items-center justify-center gap-2 h-[112rpx]">
                <u-icon name="info-circle" color="#FE975F" :size="32"></u-icon>
                <text class="text-[#2C2C36] text-xl font-bold">确定结束录音吗？</text>
            </view>
            <view>
                <u-line />
            </view>
            <view class="mt-4 px-4 grow">
                <view class="text-xl font-bold">选择发言人数量</view>
                <view class="mt-4">
                    <view class="flex flex-wrap gap-[24rpx]">
                        <view
                            v-for="(item, index) in speakerOptions"
                            :key="index"
                            class="bg-[#F5F5F5] rounded-lg px-[50rpx] py-2 cursor-pointer"
                            :class="{
                                'bg-[#f1f6ff] text-primary': formData.speaker === item.value,
                            }"
                            @click="formData.speaker = item.value">
                            {{ item.text }}
                        </view>
                    </view>
                </view>
            </view>
            <view class="flex justify-between gap-4 px-[56rpx] pb-[56rpx]">
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] rounded-full shadow-[0_0_0_2rpx_rgba(232,234,242,1)]"
                    @click="closePopup">
                    再考虑下
                </view>
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] text-white rounded-full bg-[#EB2F2F]"
                    @click="confirmEnd">
                    确定结束
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import Recorder from "recorder-core";
import RecordApp from "recorder-core/src/app-support/app";
import "recorder-core/src/engine/mp3";
import "recorder-core/src/engine/mp3-engine";
import "recorder-core/src/extensions/waveview";
import "../../static/Recorder-UniCore/app-uni-support.js";
// #ifdef MP-WEIXIN
import "recorder-core/src/app-support/app-miniProgram-wx-support.js";
// #endif
import { uploadFile } from "@/api/app";
import { meetingMinutesCreate } from "@/api/meeting_minutes";
import useHandleApi from "../../hooks/useHandleApi";

const { speakerOptions, userTokens, tokensValue } = useHandleApi();

// 防止报错 i18n is not defined
Recorder.install = true;

// 计算当前用户能录音多长时间
const userRecordTimeLimit = computed(() => {
    return Math.floor(userTokens.value / 3);
});

// 把tokensValue 转为秒
const tokensValueSecond = computed(() => {
    return tokensValue.score / 60;
});

const formData = reactive({
    language: "",
    speaker: 0,
    translation: 0,
    name: "",
    url: "",
    task_type: 1,
});

const { proxy }: any = getCurrentInstance();

const showPopup = ref<boolean>(false);

const isRecording = ref<boolean>(false);
const isPaused = ref<boolean>(false);
const isUploadError = ref<boolean>(false);
// 录音时长限制 单位ms 默认3小时
const recordTimeLimit = 60 * 60 * 1000 * 3;
// 录音时长
const recordTime = ref<number>(0);
// 录音结果
const recordResult = ref<any>(null);
const openRecorder = () => {
    isRecording.value = true;
    RecordApp.UniWebViewActivate(proxy);
    RecordApp.RequestPermission(() => {
        startRecord();
    });
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
    RecordApp.UniWebViewActivate(proxy);
    RecordApp.Start(
        {
            type: "mp3",
            bitRate: 16,
            sampleRate: 16000,
            onProcess: (buffers: any, powerLevel: any, bufferDuration: any, bufferSampleRate: any) => {
                // 记录录音时长
                recordTime.value = bufferDuration;
                if (
                    recordTime.value > recordTimeLimit ||
                    (recordTime.value / 1000) * tokensValueSecond.value > userTokens.value
                ) {
                    stopRecord();
                    return;
                }
                if (proxy.waveView) {
                    proxy.waveView.input(buffers[buffers.length - 1], powerLevel, bufferSampleRate);
                }
            },
            onProcess_renderjs: `function(buffers,powerLevel,duration,bufferSampleRate,newBufferIdx,asyncEnd){
                if(this.waveView) this.waveView.input(buffers[buffers.length-1],powerLevel,bufferSampleRate);
            }`,
        },
        () => {
            RecordApp.UniFindCanvas(
                proxy,
                [".recorder-wave-view"],
                `this.waveView=Recorder.WaveView({compatibleCanvas:canvas1,lineWidth: 5,width: 375, height: 200});`,
                (canvas1: any) => {
                    proxy.waveView = Recorder.WaveView({
                        compatibleCanvas: canvas1,
                        width: 375,
                        height: 200,
                        lineWidth: 5,
                        keep: false,
                    });
                }
            );
        }
    );
};

// 结束录音
const stopRecord = async () => {
    const fileName = `${uni.$u.timeFormat(new Date(), "yyyy-mm-dd hh:MM:ss")} 记录`;
    await new Promise((resolve, reject) => {
        RecordApp.Stop((aBuf: any, duration: any, mime: any) => {
            RecordApp.UniSaveLocalFile(
                "record.mp3",
                aBuf,
                async (result: any) => {
                    recordResult.value = result;
                    uni.showLoading({
                        title: "上传中",
                        mask: true,
                    });
                    try {
                        await uploadAudio();
                        formData.name = fileName;
                        await cratedTask();
                        resolve(true);
                        uni.hideLoading();
                    } catch (error: any) {
                        uni.hideLoading();
                        uni.showToast({
                            title: error || "上传失败",
                            icon: "none",
                            duration: 3000,
                        });
                        isUploadError.value = true;
                        reject(false);
                    }
                },
                (err: any) => {
                    isUploadError.value = true;
                    reject(false);
                }
            );
            isRecording.value = false;
            isPaused.value = true;
        });
    });
};

// 暂停录音
const pauseRecord = () => {
    if (RecordApp.GetCurrentRecOrNull()) {
        RecordApp.Pause();
    }
};

// 继续录音
const resumeRecord = () => {
    if (RecordApp.GetCurrentRecOrNull()) {
        RecordApp.Resume();
    }
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
        uni.$u.toast("请先开始录音");
    }
};

const closePopup = () => {
    showPopup.value = false;
    resumeRecord();
};

const confirmEnd = () => {
    showPopup.value = false;
    stopRecord();
};

const handleStopRecord = async () => {
    showPopup.value = true;
    pauseRecord();
};

const handleUpload = async () => {
    uni.showModal({
        title: "提示",
        content: "确定尝试重新上传吗？",
        success: (res) => {
            if (res.confirm) {
                cratedTask();
            }
        },
    });
};

// 重新录音
const handleRetry = async () => {
    uni.showModal({
        title: "提示",
        content: "确定尝试重新录音吗？",
        success: (res) => {
            if (res.confirm) {
                isUploadError.value = false;
                initRecorder();
            }
        },
    });
};

const uploadAudio = async () => {
    const { uri }: any = await uploadFile("audio", {
        filePath: recordResult.value,
    });
    formData.url = uri;
};

const cratedTask = async () => {
    try {
        uni.showLoading({
            title: "开始创建会议纪要...",
            mask: true,
        });
        if (recordResult.value && !formData.url) {
            await uploadAudio();
        }
        await meetingMinutesCreate({
            ...formData,
            translation: formData.translation == 0 ? "" : formData.translation,
        });
        uni.hideLoading();
        uni.showToast({
            title: "创建成功，即将返回首页",
            icon: "none",
            duration: 3000,
        });
        setTimeout(() => {
            uni.$u.route({
                url: "/ai_modules/meeting_minutes/pages/index/index",
                type: "reLaunch",
            });
        }, 1000);
    } catch (error: any) {
        isUploadError.value = true;
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const initRecorder = async () => {
    uni.setKeepScreenOn({
        keepScreenOn: true,
    });
    await nextTick();
    uni.showLoading({
        title: "正在加载录音器...",
    });
    recordTime.value = 0;
    isPaused.value = false;
    isRecording.value = false;

    setTimeout(() => {
        uni.hideLoading();
        openRecorder();
    }, 2000);
};

const back = () => {
    if (isRecording.value) {
        pauseRecord();
        uni.showModal({
            title: "提示",
            content: "确定返回首页吗？",
            success: async (res) => {
                if (res.confirm) {
                    await stopRecord();
                }
                {
                    resumeRecord();
                }
            },
        });
    } else {
        uni.navigateBack();
    }
};

onMounted(async () => {
    await nextTick();
    RecordApp.UniPageOnShow(proxy);
    initRecorder();
});

onUnmounted(() => {
    RecordApp.Stop();
});

onLoad((options: any) => {
    formData.language = options.language;
    formData.translation = options.translation;
});

onHide(() => {
    stopRecord();
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
	mounted() {
		RecordApp.UniRenderjsRegister(this);
	},
}
</script>
<!-- #endif -->
